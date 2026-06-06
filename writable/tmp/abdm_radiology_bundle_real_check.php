<?php

declare(strict_types=1);

use App\Libraries\FhirR4Builder;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

function parseEnvFile(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $vars = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $vars[trim((string) $parts[0])] = trim(trim((string) $parts[1]), " \t\n\r\0\x0B\"'");
    }
    return $vars;
}

function envVal(array $env, string $key, string $fallback = ''): string
{
    if (isset($env[$key]) && $env[$key] !== '') {
        return (string) $env[$key];
    }
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return (string) $value;
    }
    return $fallback;
}

function tableExists(mysqli $mysqli, string $table): bool
{
    $safe = $mysqli->real_escape_string($table);
    $res = $mysqli->query("SHOW TABLES LIKE '{$safe}'");
    if (!$res) {
        return false;
    }
    $exists = $res->num_rows > 0;
    $res->free();
    return $exists;
}

function unitToUcum(string $unit): string
{
    $map = ['mg/dl' => 'mg/dL', 'g/dl' => 'g/dL', 'mmhg' => 'mm[Hg]'];
    $u = strtolower(trim($unit));
    if ($u === '') {
        return '';
    }
    return $map[$u] ?? trim($unit);
}

$root = dirname(__DIR__, 2);
$env = parseEnvFile($root . '/.env');
$host = envVal($env, 'database.default.hostname', '127.0.0.1');
$user = envVal($env, 'database.default.username', 'root');
$pass = envVal($env, 'database.default.password', '');
$db = envVal($env, 'database.default.database', '');
$port = (int) envVal($env, 'database.default.port', '3306');

if ($db === '') {
    fwrite(STDERR, "Could not determine database.default.database from .env\n");
    exit(1);
}

$mysqli = @new mysqli($host, $user, $pass, $db, $port);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "DB connection failed: " . $mysqli->connect_error . "\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

$columns = [];
$colResult = $mysqli->query('SHOW COLUMNS FROM lab_request');
while ($colResult && ($row = $colResult->fetch_assoc())) {
    $columns[] = (string) ($row['Field'] ?? '');
}
$colResult?->free();

$patientIdCol = null;
foreach (['patient_id', 'pid', 'patient_master_id', 'pat_id'] as $candidate) {
    if (in_array($candidate, $columns, true)) {
        $patientIdCol = $candidate;
        break;
    }
}
$patientSelect = $patientIdCol !== null ? ", {$patientIdCol} AS resolved_patient_id" : ', 0 AS resolved_patient_id';

$usedFallback = false;
$sql = "SELECT id, patient_name, lab_type, charge_id, Report_Data, report_data_Impression, status, reported_time, lab_repo_id {$patientSelect} FROM lab_request WHERE lab_type = 6 AND COALESCE(Report_Data,'') <> '' ORDER BY id DESC LIMIT 1";
$labResult = $mysqli->query($sql);
$labRow = $labResult ? $labResult->fetch_assoc() : null;
$labResult?->free();

if (!$labRow) {
    $usedFallback = true;
    $fallbackSql = "SELECT id, patient_name, lab_type, charge_id, Report_Data, report_data_Impression, status, reported_time, lab_repo_id {$patientSelect} FROM lab_request WHERE COALESCE(Report_Data,'') <> '' ORDER BY id DESC LIMIT 1";
    $fallbackResult = $mysqli->query($fallbackSql);
    $labRow = $fallbackResult ? $fallbackResult->fetch_assoc() : null;
    $fallbackResult?->free();
}

if (!$labRow) {
    fwrite(STDERR, "No lab_request row found with non-empty Report_Data for fallback validation\n");
    exit(2);
}

$labReqId = (int) ($labRow['id'] ?? 0);
$patientId = (int) ($labRow['resolved_patient_id'] ?? 0);

$patientRow = [];
if ($patientId > 0 && tableExists($mysqli, 'patient_master')) {
    $stmt = $mysqli->prepare('SELECT id, p_fname, p_lname, gender, dob FROM patient_master WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $patientId);
        $stmt->execute();
        $res = $stmt->get_result();
        $patientRow = $res ? ($res->fetch_assoc() ?: []) : [];
        $res?->free();
        $stmt->close();
    }
}

$patName = trim(trim((string) ($patientRow['p_fname'] ?? '')) . ' ' . trim((string) ($patientRow['p_lname'] ?? '')));
if ($patName === '') {
    $patName = trim((string) ($labRow['patient_name'] ?? ''));
}
if ($patName === '') {
    $patName = 'Unknown';
}

$testTitle = 'Radiology Report';
$chargeId = (int) ($labRow['charge_id'] ?? 0);
if ($chargeId > 0 && tableExists($mysqli, 'charge_master')) {
    $chargeStmt = $mysqli->prepare('SELECT charge_name FROM charge_master WHERE id = ? LIMIT 1');
    if ($chargeStmt) {
        $chargeStmt->bind_param('i', $chargeId);
        $chargeStmt->execute();
        $res = $chargeStmt->get_result();
        $row = $res ? ($res->fetch_assoc() ?: []) : [];
        $res?->free();
        $chargeStmt->close();
        $name = trim((string) ($row['charge_name'] ?? ''));
        if ($name !== '') {
            $testTitle = $name;
        }
    }
}

$repoLoinc = '';
$labRepoId = (int) ($labRow['lab_repo_id'] ?? 0);
if ($labRepoId > 0 && tableExists($mysqli, 'lab_repo')) {
    $repoStmt = $mysqli->prepare('SELECT loinc_code FROM lab_repo WHERE mstRepoKey = ? LIMIT 1');
    if ($repoStmt) {
        $repoStmt->bind_param('i', $labRepoId);
        $repoStmt->execute();
        $res = $repoStmt->get_result();
        $row = $res ? ($res->fetch_assoc() ?: []) : [];
        $res?->free();
        $repoStmt->close();
        $repoLoinc = trim((string) ($row['loinc_code'] ?? ''));
    }
}

$hospitalName = '';
$hfrId = '';
if (tableExists($mysqli, 'hospital_setting')) {
    $hs = $mysqli->query("SELECT s_name, s_value FROM hospital_setting WHERE s_name IN ('ABDM_HMS_NAME','ABDM_HFR_ID','H_Name')");
    $settings = [];
    while ($hs && ($row = $hs->fetch_assoc())) {
        $settings[(string) $row['s_name']] = (string) ($row['s_value'] ?? '');
    }
    $hs?->free();
    $hospitalName = trim((string) ($settings['ABDM_HMS_NAME'] ?? $settings['H_Name'] ?? ''));
    $hfrId = trim((string) ($settings['ABDM_HFR_ID'] ?? ''));
}

$observations = [];
if (tableExists($mysqli, 'lab_request_item') && tableExists($mysqli, 'lab_tests')) {
    $obsSql = "SELECT lri.lab_test_id, lri.lab_test_value, lt.Test, lt.Unit, lt.FixedNormals, lt.loinc_code, lt.loinc_scale FROM lab_request_item lri LEFT JOIN lab_tests lt ON lt.mstTestKey = lri.lab_test_id WHERE lri.lab_request_id = ? ORDER BY lri.id ASC";
    $obsStmt = $mysqli->prepare($obsSql);
    if ($obsStmt) {
        $obsStmt->bind_param('i', $labReqId);
        $obsStmt->execute();
        $obsRes = $obsStmt->get_result();
        while ($obsRes && ($row = $obsRes->fetch_assoc())) {
            $rawValue = trim((string) ($row['lab_test_value'] ?? ''));
            if ($rawValue === '' || strtolower($rawValue) === 'n/a') {
                continue;
            }
            $scale = strtolower(trim((string) ($row['loinc_scale'] ?? '')));
            $isNumeric = is_numeric($rawValue) && $scale !== 'nom' && $scale !== 'ord';
            $valueType = $isNumeric ? 'quantity' : 'string';
            $unit = trim((string) ($row['Unit'] ?? ''));
            $observations[] = [
                'test_name' => trim((string) ($row['Test'] ?? '')) ?: ('Test-' . (string) ($row['lab_test_id'] ?? 'X')),
                'loinc_code' => trim((string) ($row['loinc_code'] ?? '')),
                'value_type' => $valueType,
                'value' => $rawValue,
                'unit' => $unit,
                'ucum_code' => unitToUcum($unit),
                'status' => ((string) ($labRow['status'] ?? '0')) === '1' ? 'final' : 'preliminary',
            ];
        }
        $obsRes?->free();
        $obsStmt->close();
    }
}

$reportedRaw = trim((string) ($labRow['reported_time'] ?? ''));
$reportedAt = $reportedRaw !== '' ? (new DateTime($reportedRaw, new DateTimeZone('Asia/Kolkata')))->format('Y-m-d\\TH:i:sP') : '';

$patient = [
    'id' => (string) ($patientId > 0 ? $patientId : ('RAD-' . $labReqId)),
    'name' => $patName,
    'gender' => trim((string) ($patientRow['gender'] ?? '')),
    'birthDate' => !empty($patientRow['dob']) ? date('Y-m-d', strtotime((string) $patientRow['dob'])) : '',
    'abhaAddress' => 'unknown@abdm',
];

$diagnosticReport = [
    'id' => (string) $labReqId,
    'title' => $testTitle,
    'status' => ((string) ($labRow['status'] ?? '0')) === '1' ? 'final' : 'preliminary',
    'conclusion' => trim((string) ($labRow['report_data_Impression'] ?? '')),
    'reported_at' => $reportedAt,
    'report_html' => trim((string) ($labRow['Report_Data'] ?? '')),
    'is_imaging' => true,
    'report_domain' => 'imaging',
    'section_title' => 'Computed tomography imaging report',
    'section_snomed_code' => '371531008',
    'section_snomed_display' => 'Computed tomography imaging report',
];
if ($repoLoinc !== '') {
    $diagnosticReport['loinc_code'] = $repoLoinc;
}

$organization = $hospitalName !== '' ? ['name' => $hospitalName, 'hfr_id' => $hfrId] : null;
$encounter = [
    'id' => 'RAD-' . $labReqId,
    'status' => 'finished',
    'class_code' => 'AMB',
    'period_start' => $reportedAt,
];

$attachment = [
    'title' => $testTitle . ' PDF Report',
    'content_type' => 'application/pdf',
    'data_base64' => base64_encode('%PDF-1.4 mock radiology report%'),
];

$builder = new FhirR4Builder();
$bundle = $builder->buildLabReportBundle($patient, $diagnosticReport, $observations, null, $organization, $encounter, $attachment);

$resources = [];
foreach (($bundle['entry'] ?? []) as $entry) {
    $r = $entry['resource'] ?? null;
    if (is_array($r) && isset($r['resourceType'])) {
        $resources[] = $r;
    }
}
$byType = [];
foreach ($resources as $res) {
    $type = (string) ($res['resourceType'] ?? '');
    if ($type !== '') {
        $byType[$type][] = $res;
    }
}

$composition = $byType['Composition'][0] ?? [];
$diagReport = $byType['DiagnosticReport'][0] ?? [];
$section = $composition['section'][0] ?? [];
$sectionEntries = (array) ($section['entry'] ?? []);
$hasTypedDiag = false;
$hasTypedDocRef = false;
foreach ($sectionEntries as $entry) {
    if (($entry['type'] ?? '') === 'DiagnosticReport') {
        $hasTypedDiag = true;
    }
    if (($entry['type'] ?? '') === 'DocumentReference') {
        $hasTypedDocRef = true;
    }
}

$checks = [
    ['check' => 'Bundle profile DocumentBundle', 'ok' => in_array('https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentBundle', (array) (($bundle['meta']['profile'] ?? [])), true)],
    ['check' => 'Composition profile DiagnosticReportRecord', 'ok' => in_array('https://nrces.in/ndhm/fhir/r4/StructureDefinition/DiagnosticReportRecord', (array) (($composition['meta']['profile'] ?? [])), true)],
    ['check' => 'DiagnosticReport profile DiagnosticReportImaging', 'ok' => in_array('https://nrces.in/ndhm/fhir/r4/StructureDefinition/DiagnosticReportImaging', (array) (($diagReport['meta']['profile'] ?? [])), true)],
    ['check' => 'Composition section has typed DiagnosticReport entry', 'ok' => $hasTypedDiag],
    ['check' => 'Composition section has typed DocumentReference entry', 'ok' => $hasTypedDocRef],
    ['check' => 'DiagnosticReport.resultsInterpreter present', 'ok' => count((array) ($diagReport['resultsInterpreter'] ?? [])) >= 1],
    ['check' => 'DiagnosticReport.conclusion non-empty', 'ok' => trim((string) ($diagReport['conclusion'] ?? '')) !== ''],
    ['check' => 'DiagnosticReport.media present', 'ok' => count((array) ($diagReport['media'] ?? [])) >= 1],
    ['check' => 'Media resource present in bundle', 'ok' => !empty($byType['Media'])],
];

$out = $root . '/writable/tmp/abdm_radiology_bundle_real_sample.json';
file_put_contents($out, json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

$failed = array_values(array_filter($checks, static fn(array $c): bool => empty($c['ok'])));

echo "ABDM Real Radiology Bundle Check\n";
echo "================================\n";
echo 'Lab Request ID: ' . $labReqId . "\n";
echo 'Source lab_type: ' . (string) ($labRow['lab_type'] ?? '') . "\n";
echo 'Fallback source used: ' . ($usedFallback ? 'yes' : 'no') . "\n";
echo 'Patient ID: ' . ($patientId > 0 ? (string) $patientId : 'N/A') . "\n";
echo 'Observations: ' . count($observations) . "\n\n";
foreach ($checks as $c) {
    echo sprintf("[%s] %s\n", $c['ok'] ? 'PASS' : 'FAIL', $c['check']);
}

echo "\nSaved radiology sample bundle to writable/tmp/abdm_radiology_bundle_real_sample.json\n";
if ($failed !== []) {
    echo 'Remaining gaps found: ' . count($failed) . "\n";
    exit(2);
}

echo "No gaps found in this radiology profile sanity check.\n";
$mysqli->close();
exit(0);

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
        $key = trim((string) $parts[0]);
        $value = trim((string) $parts[1]);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $vars[$key] = $value;
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

function mapLabTypeToTitle(int $labType): string
{
    return match ($labType) {
        1 => 'Haematology',
        2 => 'Biochemistry',
        3 => 'Serology',
        4 => 'Microbiology',
        5 => 'Pathology / Cytology',
        6 => 'Radiology',
        7 => 'Urology',
        8 => 'Molecular Diagnostics',
        default => '',
    };
}

function unitToUcum(string $unit): string
{
    $unit = trim($unit);
    if ($unit === '') {
        return '';
    }

    $map = [
        'g/dl' => 'g/dL',
        'mg/dl' => 'mg/dL',
        'mg/l' => 'mg/L',
        'mmol/l' => 'mmol/L',
        'umol/l' => 'umol/L',
        'u/l' => 'U/L',
        'iu/l' => 'IU/L',
        'iu/ml' => 'IU/mL',
        'cells/cumm' => '10*3/uL',
        'cells/mm3' => '10*3/uL',
        '10^9/l' => '10*9/L',
        '10^3/ul' => '10*3/uL',
        'fl' => 'fL',
        'pg' => 'pg',
        '%' => '%',
        'sec' => 's',
        'min' => 'min',
        'mmhg' => 'mm[Hg]',
        'meq/l' => 'meq/L',
    ];

    return $map[strtolower($unit)] ?? $unit;
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

$root = dirname(__DIR__, 2);
$env = parseEnvFile($root . '/.env');

$host = envVal($env, 'database.default.hostname', '127.0.0.1');
$user = envVal($env, 'database.default.username', 'root');
$pass = envVal($env, 'database.default.password', '');
$db   = envVal($env, 'database.default.database', '');
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

$sql = "SELECT id, patient_name, lab_type, charge_id, Report_Data, report_data_Impression, status, reported_time, lab_repo_id {$patientSelect} FROM lab_request WHERE COALESCE(Report_Data, '') <> '' ORDER BY id DESC LIMIT 1";
$labResult = $mysqli->query($sql);
$labRow = $labResult ? $labResult->fetch_assoc() : null;
$labResult?->free();

if (!$labRow) {
    fwrite(STDERR, "No lab_request row found with non-empty Report_Data\n");
    exit(2);
}

$labReqId = (int) ($labRow['id'] ?? 0);
$patientId = (int) ($labRow['resolved_patient_id'] ?? 0);

$patientRow = [];
if ($patientId > 0 && tableExists($mysqli, 'patient_master')) {
    $patientStmt = $mysqli->prepare('SELECT id, p_fname, p_lname, gender, dob FROM patient_master WHERE id = ? LIMIT 1');
    if ($patientStmt) {
        $patientStmt->bind_param('i', $patientId);
        $patientStmt->execute();
        $res = $patientStmt->get_result();
        $patientRow = $res ? ($res->fetch_assoc() ?: []) : [];
        $res?->free();
        $patientStmt->close();
    }
}

$patName = trim(trim((string) ($patientRow['p_fname'] ?? '')) . ' ' . trim((string) ($patientRow['p_lname'] ?? '')));
if ($patName === '') {
    $patName = trim((string) ($labRow['patient_name'] ?? ''));
}

$testTitle = '';
$chargeId = (int) ($labRow['charge_id'] ?? 0);
if ($chargeId > 0 && tableExists($mysqli, 'charge_master')) {
    $chargeStmt = $mysqli->prepare('SELECT charge_name FROM charge_master WHERE id = ? LIMIT 1');
    if ($chargeStmt) {
        $chargeStmt->bind_param('i', $chargeId);
        $chargeStmt->execute();
        $res = $chargeStmt->get_result();
        $chargeRow = $res ? ($res->fetch_assoc() ?: []) : [];
        $res?->free();
        $chargeStmt->close();
        $testTitle = trim((string) ($chargeRow['charge_name'] ?? ''));
    }
}
if ($testTitle === '') {
    $testTitle = mapLabTypeToTitle((int) ($labRow['lab_type'] ?? 0));
}
if ($testTitle === '') {
    $testTitle = 'Laboratory Report';
}

$repoLoinc = '';
$labRepoId = (int) ($labRow['lab_repo_id'] ?? 0);
if ($labRepoId > 0 && tableExists($mysqli, 'lab_repo')) {
    $repoStmt = $mysqli->prepare('SELECT loinc_code FROM lab_repo WHERE mstRepoKey = ? LIMIT 1');
    if ($repoStmt) {
        $repoStmt->bind_param('i', $labRepoId);
        $repoStmt->execute();
        $res = $repoStmt->get_result();
        $repoRow = $res ? ($res->fetch_assoc() ?: []) : [];
        $res?->free();
        $repoStmt->close();
        $repoLoinc = trim((string) ($repoRow['loinc_code'] ?? ''));
    }
}

$hospitalName = '';
$hfrId = '';
if (tableExists($mysqli, 'hospital_setting')) {
    $hsResult = $mysqli->query("SELECT s_name, s_value FROM hospital_setting WHERE s_name IN ('ABDM_HMS_NAME','ABDM_HFR_ID','H_Name')");
    $settings = [];
    while ($hsResult && ($row = $hsResult->fetch_assoc())) {
        $settings[(string) $row['s_name']] = (string) ($row['s_value'] ?? '');
    }
    $hsResult?->free();
    $hospitalName = trim((string) ($settings['ABDM_HMS_NAME'] ?? $settings['H_Name'] ?? ''));
    $hfrId = trim((string) ($settings['ABDM_HFR_ID'] ?? ''));
}

$obsSql = "SELECT lri.lab_test_id, lri.lab_test_value, lri.lab_test_remark, lt.Test, lt.Unit, lt.FixedNormals, lt.loinc_code, lt.loinc_scale FROM lab_request_item lri LEFT JOIN lab_tests lt ON lt.mstTestKey = lri.lab_test_id WHERE lri.lab_request_id = ? ORDER BY lri.id ASC";
$obsStmt = (tableExists($mysqli, 'lab_request_item') && tableExists($mysqli, 'lab_tests')) ? $mysqli->prepare($obsSql) : false;
$observations = [];
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

        $refLow = '';
        $refHigh = '';
        $normals = trim((string) ($row['FixedNormals'] ?? ''));
        if ($normals !== '' && str_contains($normals, '-')) {
            $parts = explode('-', $normals, 2);
            if (count($parts) === 2 && is_numeric(trim((string) $parts[0])) && is_numeric(trim((string) $parts[1]))) {
                $refLow = trim((string) $parts[0]);
                $refHigh = trim((string) $parts[1]);
            }
        }

        $interpretation = 'N';
        if ($isNumeric && $refLow !== '' && $refHigh !== '') {
            $fVal = (float) $rawValue;
            if ($fVal < (float) $refLow) {
                $interpretation = 'L';
            } elseif ($fVal > (float) $refHigh) {
                $interpretation = 'H';
            }
        }

        $unit = trim((string) ($row['Unit'] ?? ''));
        $observations[] = [
            'test_name' => trim((string) ($row['Test'] ?? '')) ?: ('Test-' . (string) ($row['lab_test_id'] ?? 'X')),
            'loinc_code' => trim((string) ($row['loinc_code'] ?? '')),
            'value_type' => $valueType,
            'value' => $rawValue,
            'unit' => $unit,
            'ucum_code' => unitToUcum($unit),
            'ref_low' => $refLow,
            'ref_high' => $refHigh,
            'interpretation' => $interpretation,
            'status' => ((string) ($labRow['status'] ?? '0')) === '1' ? 'final' : 'preliminary',
        ];
    }
    $obsRes?->free();
    $obsStmt->close();
}

$reportedRaw = trim((string) ($labRow['reported_time'] ?? ''));
$reportedAt = $reportedRaw !== '' ? (new DateTime($reportedRaw, new DateTimeZone('Asia/Kolkata')))->format('Y-m-d\\TH:i:sP') : '';

$patient = [
    'id' => (string) ($patientId > 0 ? $patientId : ('LAB-' . $labReqId)),
    'name' => $patName !== '' ? $patName : 'Unknown',
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
    'section_title' => 'Hematology report',
    'section_snomed_code' => '4321000179101',
    'section_snomed_display' => 'Hematology report',
];
if ($repoLoinc !== '') {
    $diagnosticReport['loinc_code'] = $repoLoinc;
}

$organization = $hospitalName !== '' ? ['name' => $hospitalName, 'hfr_id' => $hfrId] : null;
$encounter = [
    'id' => 'LAB-' . $labReqId,
    'status' => 'finished',
    'class_code' => 'AMB',
    'period_start' => $reportedAt,
];

$builder = new FhirR4Builder();
$bundle = $builder->buildLabReportBundle($patient, $diagnosticReport, $observations, null, $organization, $encounter, null);

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
$entries = (array) ($section['entry'] ?? []);

$hasTypedDiagReport = false;
$hasTypedDocRef = false;
foreach ($entries as $entry) {
    if (($entry['type'] ?? '') === 'DiagnosticReport') {
        $hasTypedDiagReport = true;
    }
    if (($entry['type'] ?? '') === 'DocumentReference') {
        $hasTypedDocRef = true;
    }
}

$checks = [
    ['check' => 'Bundle profile DocumentBundle', 'ok' => in_array('https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentBundle', (array) (($bundle['meta']['profile'] ?? [])), true)],
    ['check' => 'Composition profile DiagnosticReportRecord', 'ok' => in_array('https://nrces.in/ndhm/fhir/r4/StructureDefinition/DiagnosticReportRecord', (array) (($composition['meta']['profile'] ?? [])), true)],
    ['check' => 'Composition section count = 1', 'ok' => count((array) ($composition['section'] ?? [])) === 1],
    ['check' => 'Section entry count between 1 and 2', 'ok' => count($entries) >= 1 && count($entries) <= 2],
    ['check' => 'Section has typed DiagnosticReport entry', 'ok' => $hasTypedDiagReport],
    ['check' => 'DiagnosticReport profile DiagnosticReportLab', 'ok' => in_array('https://nrces.in/ndhm/fhir/r4/StructureDefinition/DiagnosticReportLab', (array) (($diagReport['meta']['profile'] ?? [])), true)],
    ['check' => 'DiagnosticReport.resultsInterpreter present', 'ok' => count((array) ($diagReport['resultsInterpreter'] ?? [])) >= 1],
    ['check' => 'DiagnosticReport.result present', 'ok' => count((array) ($diagReport['result'] ?? [])) >= 1],
    ['check' => 'DiagnosticReport.conclusion non-empty', 'ok' => trim((string) ($diagReport['conclusion'] ?? '')) !== ''],
];

$samplePath = $root . '/writable/tmp/abdm_lab_bundle_real_sample.json';
file_put_contents($samplePath, json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

$failed = array_values(array_filter($checks, static fn(array $c): bool => empty($c['ok'])));

echo "ABDM Real Lab Bundle Check\n";
echo "==========================\n";
echo 'Lab Request ID: ' . $labReqId . "\n";
echo 'Patient ID: ' . ($patientId > 0 ? (string) $patientId : 'N/A') . "\n";
echo 'Observations: ' . count($observations) . "\n";
echo 'DocReference typed section entry present: ' . ($hasTypedDocRef ? 'yes' : 'no') . "\n\n";

foreach ($checks as $c) {
    echo sprintf("[%s] %s\n", $c['ok'] ? 'PASS' : 'FAIL', $c['check']);
}

echo "\nSaved real sample bundle to writable/tmp/abdm_lab_bundle_real_sample.json\n";

if ($failed !== []) {
    echo 'Remaining gaps found: ' . count($failed) . "\n";
    exit(2);
}

echo "No gaps found in this real-data profile sanity check.\n";
$mysqli->close();
exit(0);

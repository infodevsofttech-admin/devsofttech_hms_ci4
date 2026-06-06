<?php

declare(strict_types=1);

use App\Libraries\FhirR4Builder;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$builder = new FhirR4Builder();

$patient = [
    'id' => '2',
    'name' => 'ABC Male',
    'given_name' => 'ABC',
    'family_name' => 'Male',
    'gender' => 'male',
    'birthDate' => '1981-01-12',
    'abhaAddress' => 'abha_2@sbx',
    'abha_id' => '22-7225-4829-5255',
];

$diagnosticReport = [
    'id' => '5234342',
    'title' => 'Diagnostic Report- Lab',
    'status' => 'final',
    'conclusion' => 'Elevated cholesterol/high density lipoprotein ratio',
    'reported_at' => '2020-07-10T11:45:33+05:30',
    'loinc_code' => '24331-1',
    'category_snomed_code' => '708196005',
    'category_snomed_display' => 'Hematology service',
    'section_title' => 'Hematology report',
    'section_snomed_code' => '4321000179101',
    'section_snomed_display' => 'Hematology report',
    'report_html' => '<html><body><h1>Lab Report</h1><p>CBC and lipid panel.</p></body></html>',
];

$observations = [
    [
        'test_name' => 'Triglyceride [Mass/volume] in Serum or Plasma',
        'loinc_code' => '2571-8',
        'value_type' => 'quantity',
        'value' => '146',
        'unit' => 'mg/dL',
        'ucum_code' => 'mg/dL',
        'ref_high' => '150',
        'interpretation' => 'N',
    ],
    [
        'test_name' => 'Cholesterol in HDL [Mass/volume] in Serum or Plasma',
        'loinc_code' => '2085-9',
        'value_type' => 'quantity',
        'value' => '45',
        'unit' => 'mg/dL',
        'ucum_code' => 'mg/dL',
        'ref_low' => '40',
        'interpretation' => 'N',
    ],
];

$organization = [
    'name' => 'XYZ Lab Pvt.Ltd.',
    'hfr_id' => 'IN0510000828',
];

$encounter = [
    'id' => 'LAB-5234342',
    'status' => 'finished',
    'class_code' => 'AMB',
    'period_start' => '2020-07-10T11:45:33+05:30',
];

$attachment = [
    'title' => 'Laboratory report',
    'content_type' => 'application/pdf',
    'data_base64' => base64_encode('%PDF-1.4 mock lab report%'),
];

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
$docRefs = $byType['DocumentReference'] ?? [];

$checks = [];
$checks[] = ['check' => 'Bundle profile DocumentBundle', 'ok' => in_array('https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentBundle', (array) (($bundle['meta']['profile'] ?? [])), true)];
$checks[] = ['check' => 'Composition profile DiagnosticReportRecord', 'ok' => in_array('https://nrces.in/ndhm/fhir/r4/StructureDefinition/DiagnosticReportRecord', (array) (($composition['meta']['profile'] ?? [])), true)];
$checks[] = ['check' => 'Composition section count = 1', 'ok' => count((array) ($composition['section'] ?? [])) === 1];

$section = $composition['section'][0] ?? [];
$entries = (array) ($section['entry'] ?? []);
$checks[] = ['check' => 'Section entry count between 1 and 2', 'ok' => count($entries) >= 1 && count($entries) <= 2];

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
$checks[] = ['check' => 'Section has typed DiagnosticReport entry', 'ok' => $hasTypedDiagReport];
$checks[] = ['check' => 'Section has typed DocumentReference entry (optional but present here)', 'ok' => $hasTypedDocRef];

$checks[] = ['check' => 'DiagnosticReport profile DiagnosticReportLab', 'ok' => in_array('https://nrces.in/ndhm/fhir/r4/StructureDefinition/DiagnosticReportLab', (array) (($diagReport['meta']['profile'] ?? [])), true)];
$checks[] = ['check' => 'DiagnosticReport.resultsInterpreter present', 'ok' => count((array) ($diagReport['resultsInterpreter'] ?? [])) >= 1];
$checks[] = ['check' => 'DiagnosticReport.result present', 'ok' => count((array) ($diagReport['result'] ?? [])) >= 1];
$checks[] = ['check' => 'DiagnosticReport.conclusion non-empty', 'ok' => trim((string) ($diagReport['conclusion'] ?? '')) !== ''];

$docRefWithData = false;
foreach ($docRefs as $docRef) {
    $content = $docRef['content'][0]['attachment'] ?? [];
    if (!empty($content['data'])) {
        $docRefWithData = true;
        break;
    }
}
$checks[] = ['check' => 'At least one DocumentReference has attachment.data', 'ok' => $docRefWithData];

$failed = array_values(array_filter($checks, static fn(array $c): bool => empty($c['ok'])));

echo "ABDM Lab Bundle Check\n";
echo "====================\n";
foreach ($checks as $c) {
    echo sprintf("[%s] %s\n", $c['ok'] ? 'PASS' : 'FAIL', $c['check']);
}

echo "\nResource Counts\n";
echo "---------------\n";
foreach ($byType as $type => $items) {
    echo sprintf("%s: %d\n", $type, count($items));
}

file_put_contents(dirname(__DIR__, 2) . '/writable/tmp/abdm_lab_bundle_sample.json', json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "\nSaved sample bundle to writable/tmp/abdm_lab_bundle_sample.json\n";
if ($failed !== []) {
    echo "Remaining gaps found: " . count($failed) . "\n";
    exit(2);
}

echo "No gaps found in this profile sanity check.\n";
exit(0);

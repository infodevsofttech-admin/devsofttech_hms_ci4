<?php

require_once 'vendor/autoload.php';
require_once 'vendor/codeigniter4/framework/system/Test/bootstrap.php';

$source = [
    'record_id' => 'A26090000009',
    'patient_id' => 11,
    'admission_date' => '2026-08-28T10:00:00+05:30',
    'discharge_date' => '2026-09-06T07:37:53+05:30',
    'patient' => [
        'id' => 11,
        'name' => 'DEVENDER SINGH',
        'gender' => 'male',
        'dob' => '1979-03-28',
        'abha_number' => '91510165305101',
    ],
    'doctor' => [
        'id' => 4,
        'name' => 'Dr. R.K.SUNDRIYAL',
        'hpr_id' => 'HPR-4',
    ],
    'hospital' => [
        'name' => 'E-Atria Hospital',
        'hfr_id' => 'IN0510000871',
    ],
    'chief_complaints' => [
        ['text' => 'Abdominal Pain', 'code' => '21522000'],
        ['text' => 'Cough', 'code' => '49727002'],
        ['text' => 'Vomiting', 'code' => '422400008'],
    ],
    'diagnoses' => [
        ['text' => 'VIRAL DISEASE', 'code' => '404684003'],
    ],
    'procedures' => [
        ['text' => 'Laparoscopy of rectum', 'code' => '86174004', 'date' => '2026-08-28T00:00:00+05:30'],
    ],
    'medications' => [
        ['name' => 'ACILOC', 'dosage' => 'EMPTY STOMACH | BD | 5 DAYS'],
        ['name' => 'PANTOP DSR', 'dosage' => 'EMPTY STOMACH | OD | 5 DAYS'],
    ],
    'follow_up' => [
        'text' => 'Review After 1 Month (02-10-2026) or as and when required',
    ],
];

$codingResolver = new \App\Libraries\Abdm\Fhir\Coding\DefaultCodingResolver();
$generator = new \App\Libraries\Abdm\Fhir\Generators\DischargeFhirGenerator($codingResolver);

$bundle = $generator->generate($source);

echo "Bundle Generated Successfully!\n";
echo "Total Entries: " . count($bundle['entry']) . "\n\n";

echo "=== CHECKING CONDITIONS ===\n";
foreach ($bundle['entry'] as $e) {
    if ($e['resource']['resourceType'] === 'Condition') {
        echo "- {$e['resource']['code']['text']} | recordedDate: " . ($e['resource']['recordedDate'] ?? 'MISSING') . "\n";
    }
}

echo "\n=== CHECKING MEDICATION REQUESTS ===\n";
foreach ($bundle['entry'] as $e) {
    if ($e['resource']['resourceType'] === 'MedicationRequest') {
        $mr = $e['resource'];
        $cat = $mr['category'][0]['coding'][0]['display'] ?? 'MISSING';
        $route = $mr['dosageInstruction'][0]['route']['coding'][0]['display'] ?? 'MISSING';
        $method = $mr['dosageInstruction'][0]['method']['coding'][0]['display'] ?? 'MISSING';
        echo "- {$mr['medicationCodeableConcept']['text']}:\n";
        echo "  category: $cat\n";
        echo "  route: $route\n";
        echo "  method: $method\n";
        echo "  text: " . $mr['dosageInstruction'][0]['text'] . "\n";
    }
}

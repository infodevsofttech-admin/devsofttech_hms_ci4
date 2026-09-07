<?php

require __DIR__ . '/../vendor/autoload.php';

// Instantiate DischargeFhirGenerator and build a mock source payload with ACILOC (no dosage) and CAP PANTOP DSR (dosage "NA")
$generator = new \App\Libraries\Abdm\Fhir\Generators\DischargeFhirGenerator();

$source = [
    'record_id' => 6,
    'completed_at' => date(DATE_ATOM),
    'patient' => [
        'id' => 11,
        'uhid' => 'A26080000011',
        'name' => 'Test Patient',
        'gender' => 'male',
        'dob' => '1990-01-01',
        'abha_id' => '91510165305101',
    ],
    'encounter' => [
        'id' => 6,
        'ipd_no' => 'IPD-A26080000006',
        'class_code' => 'IMP',
        'start' => '2026-08-15T10:30:00+05:30',
        'end' => '2026-08-25T14:00:00+05:30',
    ],
    'medications' => [
        ['name' => 'ACILOC', 'dosage' => ''],
        ['name' => 'CAP PANTOP DSR', 'dosage' => 'NA'],
    ],
    'care_plans' => [
        ['title' => 'Discharge Advice', 'description' => '<p>TEst Discharge Summary</p>'],
    ],
];

$result = $generator->generate($source);
$bundle = $result['fhir_bundle'];

foreach ($bundle['entry'] as $entry) {
    $res = $entry['resource'];
    if ($res['resourceType'] === 'CarePlan') {
        echo "=== CarePlan ===\n";
        echo json_encode($res, JSON_PRETTY_PRINT) . "\n\n";
    }
}

if (str_contains($json, '"NA"') || str_contains($json, '"N/A"')) {
    echo "\nFAILED: 'NA' or 'N/A' found in bundle!\n";
    exit(1);
} else {
    echo "\nSUCCESS: No 'NA' or 'N/A' placeholders found in bundle!\n";
    exit(0);
}

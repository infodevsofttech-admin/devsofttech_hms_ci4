<?php

require_once __DIR__ . '/verify_user_bundle.php'; // loads $bundle

// Update all Conditions
foreach ($bundle['entry'] as &$e) {
    if ($e['resource']['resourceType'] === 'Condition') {
        $e['resource']['recordedDate'] = '2026-08-28T10:00:00+05:30';
    }
}
unset($e);

// Update all MedicationRequests
foreach ($bundle['entry'] as &$e) {
    if ($e['resource']['resourceType'] === 'MedicationRequest') {
        $mr = &$e['resource'];
        $mr['category'] = [
            [
                'coding' => [
                    [
                        'system'  => 'http://terminology.hl7.org/CodeSystem/medicationrequest-category',
                        'code'    => 'discharge',
                        'display' => 'Discharge',
                    ],
                ],
                'text' => 'Discharge',
            ],
        ];
        $mr['medicationCodeableConcept']['coding'][0]['display'] = $mr['medicationCodeableConcept']['text'];
        $mr['dosageInstruction'][0]['method'] = [
            'coding' => [
                [
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '421521009',
                    'display' => 'Swallow',
                ],
            ],
        ];
    }
}
unset($e);

file_put_contents(__DIR__ . '/user_updated_bundle.json', json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "Saved updated bundle to scratch/user_updated_bundle.json\n";

// Validate again
$defDir = 'd:/Workplace/HMS_CI4_OLD/ABDM_FHIR/definitions.json/';
require_once __DIR__ . '/validate_against_profiles.php';

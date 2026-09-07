<?php

require_once __DIR__ . '/verify_user_bundle.php'; // loads $bundle

// Test modifying one MedicationRequest with category, method, and reasonReference
$mr = $bundle['entry'][10]['resource'];

$mr['category'] = [
    [
        'coding' => [
            [
                'system' => 'http://terminology.hl7.org/CodeSystem/medicationrequest-category',
                'code' => 'discharge',
                'display' => 'Discharge',
            ],
        ],
        'text' => 'Discharge',
    ],
];

$mr['dosageInstruction'][0]['method'] = [
    'coding' => [
        [
            'system' => 'http://snomed.info/sct',
            'code' => '421521009',
            'display' => 'Swallow',
        ],
    ],
    'text' => 'Swallow',
];

$bundle['entry'][10]['resource'] = $mr;

// Also add recordedDate to Condition
$cond = $bundle['entry'][4]['resource'];
$cond['recordedDate'] = '2026-08-28T10:00:00+05:30';
$bundle['entry'][4]['resource'] = $cond;

// Now validate against StructureDefinitions
$defDir = 'd:/Workplace/HMS_CI4_OLD/ABDM_FHIR/definitions.json/';
require_once __DIR__ . '/validate_against_profiles.php';

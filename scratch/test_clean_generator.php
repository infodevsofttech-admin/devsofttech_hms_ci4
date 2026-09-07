<?php
require __DIR__ . '/../vendor/autoload.php';

// Instantiate DischargeFhirGenerator and build a realistic IPD 9 source
$generator = new \App\Libraries\Abdm\Fhir\Generators\DischargeFhirGenerator();

$source = [
    'record_id' => 9,
    'completed_at' => '2026-09-06T01:08:04+05:30',
    'patient' => [
        'id' => 11,
        'uhid' => 'A26080000011',
        'name' => 'DEVENDER SINGH',
        'gender' => 'male',
        'dob' => '1979-03-28',
        'abha_id' => '91510165305101',
        'abha_address' => 'singhdevender0328@sbx',
    ],
    'doctor' => [
        'id' => 4,
        'name' => 'Dr. R.K.SUNDRIYAL',
        'hpr_id' => 'HPR-4',
    ],
    'organization' => [
        'id' => 'IN0510000871',
        'name' => 'E-Atria Hospital',
    ],
    'encounter' => [
        'id' => 9,
        'ipd_no' => 'A26090000009',
        'start' => '2026-08-28T10:00:00+05:30',
        'end' => '2026-09-06T01:08:04+05:30',
        'class_code' => 'IMP',
        'location_display' => 'Ward: General Ward, Bed: GW-12',
    ],
    'chief_complaints' => [
        ['name' => 'Abdominal Pain', 'code' => '21522000'],
        ['name' => 'Cough', 'code' => '49727002'],
        ['name' => 'Vomiting', 'code' => '422400008'],
        ['name' => 'Acute chest pain', 'code' => '29857009'],
        ['name' => 'Chest Pain', 'code' => '29857009'],
    ],
    'diagnoses' => [
        ['name' => 'VIRAL DISEASE', 'code' => '404684003'],
    ],
    'procedures' => [
        ['name' => 'Laparoscopy of rectum', 'code' => '86174004'],
    ],
    'medications' => [
        [
            'name' => 'ACILOC',
            'dosage' => 'EMPTY STOMACH | BD |    |     (BD)',
            'frequency' => 2,
            'period_unit' => 'd',
            'route' => 'Oral',
            'instructions' => 'Before meals',
            'duration' => '5 days',
        ],
        [
            'name' => 'PANTOP DSR',
            'dosage' => 'EMPTY STOMACH | OD |    |     (OD)',
            'frequency' => 1,
            'period_unit' => 'd',
            'route' => 'Oral',
            'instructions' => 'Before meals',
            'duration' => '5 days',
        ],
    ],
    'care_plans' => [
        [
            'title' => 'Dietary Advice',
            'description' => '1. Fruits and Vegetables: पर्याप्त विटामिन और खनिज सुनिश्चित करने के लिए प्रतिदिन विभिन्न प्रकार के ताजे फल और सब्जियों का सेवन करें। 2. Avoid Processed Foods: मीठे, नमकीन और तले हुए खाद्य पदार्थों का सेवन सीमित करें।',
        ],
        [
            'title' => 'Follow Up',
            'description' => 'Review After: 1 Month (02-10-2026) Days / as and when required',
        ],
    ],
    'documents' => [
        [
            'title' => 'IPD Discharge Summary',
            'content_type' => 'application/pdf',
            'data' => "JVBERi0xLjQKJeLjz9MKMSAwIG9iajw8L1R5cGUvQ2F0YWxvZy9QYWdlcyAyIDAgUj4+ZW5kb2JqCjIgMCBvYmo8PC9UeXBlL1BhZ2VzL0tpZHNbMyAwIFJdL0NvdW50IDE+PmVuZG9iagozIDAgb2JqPDwvVHlwZS9QYWdlL1BhcmVudCAyIDAgUi9NZWRpYUJveFswIDAgNTk1IDg0Ml0+PmVuZG9iagp4cmVmCjAgNAowMDAwMDAwMDAwIDY1NTM1IGYKMDAwMDAwMDAwOSAwMDAwMCBuCjAwMDAwMDAwNTYgMDAwMDAgbgowMDAwMDAwMTE1IDAwMDAwIG4KdHJhaWxlcjw8L1NpemUgNC9Sb290IDEgMCBSPj4Kc3RhcnR4cmVmCjE3MwolaUVPRg==",
            'created_at' => '2026-09-06T01:08:04+05:30',
        ]
    ]
];

$result = $generator->generate($source);
$bundle = $result['fhir_bundle'] ?? $result;

$validator = new \App\Libraries\Abdm\Fhir\Support\FhirBundleValidator();
$val = $validator->validate($bundle);

echo "Validation Result:\n";
echo "Valid: " . ($val->valid ? "YES" : "NO") . " | Score: " . $val->score . "%\n";
if (!empty($val->errors)) {
    echo "Errors: " . print_r($val->errors, true) . "\n";
}

file_put_contents('d:/Workplace/HMS_CI4_OLD/scratch/generated_clean_bundle.json', json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Saved clean bundle (" . strlen(json_encode($bundle)) . " bytes)\n";

<?php
require __DIR__ . '/../vendor/autoload.php';

$generator = new \App\Libraries\Abdm\Fhir\Generators\DischargeFhirGenerator();

$pdfPath = __DIR__ . '/../writable/uploads/abdm/ipd/9/discharge-summary.pdf';
$pdfBinary = file_get_contents($pdfPath);

$source = [
    'record_id' => 9,
    'completed_at' => '2026-09-06T01:30:00+05:30',
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
        'end' => '2026-09-06T01:30:00+05:30',
        'class_code' => 'IMP',
        'location_display' => 'Ward: General Ward, Bed: GW-12',
    ],
    'chief_complaints' => [
        ['name' => 'Abdominal Pain', 'code' => '21522000'],
        ['name' => 'Cough', 'code' => '49727002'],
        ['name' => 'Vomiting', 'code' => '422400008'],
        ['name' => 'Acute chest pain', 'code' => '423341008'],
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
            'dosage' => 'Take 1 tablet twice daily before meals',
            'frequency' => 2,
            'period_unit' => 'd',
            'route' => 'Oral route',
        ],
        [
            'name' => 'PANTOP DSR',
            'dosage' => 'Take 1 tablet once daily before meals',
            'frequency' => 1,
            'period_unit' => 'd',
            'route' => 'Oral route',
        ],
    ],
    'observations' => [
        [
            'text' => 'Pulse /min',
            'value' => '70',
            'category' => 'Condition on Admission Time',
            'loinc_code' => '8867-4',
            'loinc_display' => 'Heart rate',
        ],
        [
            'text' => 'Respiration /min',
            'value' => '20',
            'category' => 'Condition on Admission Time',
            'loinc_code' => '9279-1',
            'loinc_display' => 'Respiratory rate',
        ],
        [
            'text' => 'BP mmHg',
            'value' => '140/90',
            'category' => 'Condition on Admission Time',
            'loinc_code' => '85354-9',
            'loinc_display' => 'Blood pressure panel with all children optional',
        ],
        [
            'text' => 'SPO2',
            'value' => '95',
            'category' => 'Condition on Admission Time',
            'loinc_code' => '59408-5',
            'loinc_display' => 'Oxygen saturation in Arterial blood by Pulse oximetry',
        ],
        [
            'text' => 'Temp F',
            'value' => '96',
            'category' => 'Condition on Admission Time',
            'loinc_code' => '8310-5',
            'loinc_display' => 'Body temperature',
        ],
    ],
    'care_plans' => [
        [
            'title' => 'Dietary Advice',
            'description' => 'Fruits and Vegetables: Consume fresh fruits and vegetables daily. Avoid processed, sugary, and salty foods.',
        ],
        [
            'title' => 'Follow Up',
            'description' => 'Review After: 1 Month (02-10-2026) / as and when required',
        ],
    ],
    'documents' => [
        [
            'title' => 'IPD Discharge Summary',
            'content_type' => 'application/pdf',
            'data' => base64_encode($pdfBinary),
            'created_at' => '2026-09-06T01:30:00+05:30',
        ]
    ]
];

$result = $generator->generate($source);
$bundle = $result['fhir_bundle'] ?? $result;

$validator = new \App\Libraries\Abdm\Fhir\Support\FhirBundleValidator();
$val = $validator->validate($bundle);

echo "Validation Result:\n";
echo "Valid: " . ($val->valid ? "YES" : "NO") . " | Score: " . $val->score . "%\n";
echo "PDF attached size: " . strlen($pdfBinary) . " bytes\n";
if (!empty($val->errors)) {
    echo "Errors: " . print_r($val->errors, true) . "\n";
}

<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Libraries\Abdm\Fhir\FhirGeneratorFactory;

$factory = new FhirGeneratorFactory();
$generator = $factory->healthDocument();

$source = [
    'record_id' => '4',
    'session_id' => '4',
    'visit_date' => '2026-08-26',
    'completed_at' => date(DATE_ATOM),
    'document_title' => 'Medical Certificate',
    'document_content_html' => '<div style="font-family:sans-serif;"><h3>Medical Certificate</h3><p>This is to certify that Mr. Devender Singh was under treatment from 20-Aug-2026 to 25-Aug-2026 and is now fit to resume duty.</p></div>',
    'doctor_name' => 'Dr. Mayank Agarwal',
    'organization' => [
        'id' => 'IN0100000001',
        'name' => 'E-Atria Hospital',
    ],
    'patient' => [
        'id' => '11',
        'uhid' => 'P26061000011',
        'name' => 'DEVENDER SINGH',
        'gender' => 'male',
        'dob' => '1979-03-28',
        'abha_id' => '91510165305101',
        'abha_address' => 'devender@abdm',
    ],
    'practitioner' => [
        'id' => '1',
        'name' => 'Dr. Mayank Agarwal',
    ],
];

$result = $generator->generate($source);

echo "=== HEALTH DOCUMENT RECORD GENERATION RESULT ===\n";
echo "HI-Type: " . $result['hi_type'] . "\n";
echo "Care Context Ref: " . $result['care_context_reference'] . "\n";
echo "Care Context Display: " . $result['care_context_display'] . "\n";
echo "Validation Status: " . json_encode($result['validation']) . "\n\n";

echo "=== FHIR BUNDLE JSON ===\n";
echo json_encode($result['fhir_bundle'], JSON_PRETTY_PRINT) . "\n";

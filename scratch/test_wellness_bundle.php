<?php

require_once 'd:/Workplace/HMS_CI4_OLD/vendor/autoload.php';
defined('FCPATH') || define('FCPATH', 'd:/Workplace/HMS_CI4_OLD/public/');
require_once 'd:/Workplace/HMS_CI4_OLD/vendor/codeigniter4/framework/system/Test/bootstrap.php';

use App\Libraries\Abdm\Fhir\FhirGeneratorFactory;

$factory = new FhirGeneratorFactory();
$generator = $factory->wellness();

$source = [
    'record_id' => '33710',
    'session_id' => '0',
    'visit_date' => '2026-09-06',
    'completed_at' => '2026-09-06T18:11:14+05:30',
    'patient' => [
        'id' => '15350',
        'uhid' => '15350',
        'name' => 'DEVENDER SINGH 0',
        'gender' => 'male',
        'dob' => '1979-03-28',
        'abha_id' => '91510165305101',
        'abha_address' => 'singhdevender0328@sbx',
        'mobile' => '+919720958717',
    ],
    'practitioner' => [
        'id' => '1',
        'name' => 'Dr. Sanjay Kumar',
    ],
    'organization' => [
        'id' => 'IN0510000828',
        'name' => 'DevSoft Tech',
    ],
    'vitals' => [
        ['code' => '8480-6', 'display' => 'Systolic blood pressure', 'value' => 120, 'unit' => 'mmHg', 'ucum_code' => 'mm[Hg]'],
        ['code' => '8462-4', 'display' => 'Diastolic blood pressure', 'value' => 80, 'unit' => 'mmHg', 'ucum_code' => 'mm[Hg]'],
        ['code' => '8867-4', 'display' => 'Heart rate', 'value' => 30, 'unit' => '/min', 'ucum_code' => '/min'],
        ['code' => '8310-5', 'display' => 'Body temperature', 'value' => 36.666666666666664, 'unit' => 'Cel', 'ucum_code' => 'Cel'],
        ['code' => '9279-1', 'display' => 'Respiratory rate', 'value' => 20, 'unit' => '/min', 'ucum_code' => '/min'],
        ['code' => '59408-5', 'display' => 'Oxygen saturation in Arterial blood by Pulse oximetry', 'value' => 95, 'unit' => '%', 'ucum_code' => '%'],
    ],
    'physical_examination' => [
        'Complaints: Abdominal muscle pain, Acute Q fever\nComplaint Duration (Days): 1 days\nComplaint Severity: mild',
        'Diagnosis: viral infection',
        'Investigation: Pathology : CBC, LFT, KFT\nX-Ray : X-Ray Chest (PA View)',
    ],
    'advice' => [
        'Smoking status: Yes',
        'Alcohol use: Yes',
    ],
];

$output = $generator->generate($source);
$bundle = $output['fhir_bundle'];

// Let's inspect the bundle entry 0 (Composition)
echo "=== COMPOSITION IN TEST BUNDLE ===\n";
$comp = $bundle['entry'][0]['resource'];
file_put_contents('scratch/updated_wellness_bundle.json', json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Saved to scratch/updated_wellness_bundle.json\n";








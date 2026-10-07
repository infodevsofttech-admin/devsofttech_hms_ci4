<?php
require 'vendor/autoload.php';
define('FCPATH', __DIR__ . '/../public/');
defined('ENVIRONMENT') || define('ENVIRONMENT', 'development');
require __DIR__ . '/../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$source = [
    'record_id' => '101',
    'visit_date' => '2026-10-07',
    'completed_at' => date(DATE_ATOM),
    'hfr_id' => 'IN0510000828',
    'patient' => [
        'id' => 15353,
        'name' => 'DHAIRYA SINGH BISHT',
        'gender' => 'female',
        'dob' => '1995-05-15',
        'mobile' => '9720958717',
        'abha_id' => '91310013085603',
        'abha_address' => '91310013085603@sbx',
        'p_code' => 'P26081015353',
    ],
    'practitioner' => [
        'id' => '1',
        'name' => 'Rita (NUR-805)',
    ],
    'vitals' => [
        // Standard Vitals
        ['loinc_code' => '8480-6', 'display' => 'Systolic blood pressure', 'value' => 120, 'unit' => 'mmHg', 'ucum_code' => 'mm[Hg]'],
        ['loinc_code' => '8462-4', 'display' => 'Diastolic blood pressure', 'value' => 80, 'unit' => 'mmHg', 'ucum_code' => 'mm[Hg]'],
        ['loinc_code' => '8867-4', 'display' => 'Heart rate', 'value' => 72, 'unit' => '/min', 'ucum_code' => '/min'],
        ['loinc_code' => '8302-2', 'display' => 'Body height', 'value' => 165, 'unit' => 'cm', 'ucum_code' => 'cm'],
        ['loinc_code' => '29463-7', 'display' => 'Body weight', 'value' => 68, 'unit' => 'kg', 'ucum_code' => 'kg'],
        ['loinc_code' => '39156-5', 'display' => 'Body Mass Index', 'value' => 24.98, 'unit' => 'kg/m2', 'ucum_code' => 'kg/m2'],
        ['loinc_code' => '8310-5', 'display' => 'Body temperature', 'value' => 37, 'unit' => 'Cel', 'ucum_code' => 'Cel'],
        ['loinc_code' => '9279-1', 'display' => 'Respiratory rate', 'value' => 18, 'unit' => '/min', 'ucum_code' => '/min'],
        ['loinc_code' => '59408-5', 'display' => 'Oxygen saturation in Arterial blood by Pulse oximetry', 'value' => 98, 'unit' => '%', 'ucum_code' => '%'],
        ['loinc_code' => '72514-3', 'display' => 'Pain severity - 0-10 verbal numeric rating', 'value' => 2, 'unit' => '{score}', 'ucum_code' => '{score}'],

        // Anthropometry / Body Measurements
        ['loinc_code' => '56115-9', 'display' => 'Waist circumference', 'value' => 82.5, 'unit' => 'cm', 'ucum_code' => 'cm'],
        ['loinc_code' => '56114-2', 'display' => 'Hip circumference', 'value' => 98.0, 'unit' => 'cm', 'ucum_code' => 'cm'],
        ['loinc_code' => '8280-0', 'display' => 'Waist to hip ratio', 'value' => 0.84, 'unit' => 'ratio', 'ucum_code' => '{ratio}'],

        // Blood Glucose & POC Labs
        ['loinc_code' => '2339-0', 'display' => 'Glucose [Mass/volume] in Blood', 'value' => 110, 'unit' => 'mg/dL', 'ucum_code' => 'mg/dL'],
        ['loinc_code' => '1558-6', 'display' => 'Fasting glucose [Mass/volume] in Blood', 'value' => 92, 'unit' => 'mg/dL', 'ucum_code' => 'mg/dL'],
        ['loinc_code' => '1521-4', 'display' => 'Glucose [Mass/volume] in Blood 2 hours post meal', 'value' => 128, 'unit' => 'mg/dL', 'ucum_code' => 'mg/dL'],
        ['loinc_code' => '4548-4', 'display' => 'Hemoglobin A1c/Hemoglobin.total in Blood', 'value' => 5.4, 'unit' => '%', 'ucum_code' => '%'],
        ['loinc_code' => '718-7', 'display' => 'Hemoglobin [Mass/volume] in Blood', 'value' => 13.8, 'unit' => 'g/dL', 'ucum_code' => 'g/dL'],

        // Physical Activity & Sleep
        ['loinc_code' => '55423-8', 'display' => 'Number of steps in 24 hour Measured', 'value' => 8500, 'unit' => '{steps}', 'ucum_code' => '{steps}'],
        ['loinc_code' => '93832-4', 'display' => 'Sleep duration', 'value' => 7.5, 'unit' => 'h', 'ucum_code' => 'h'],
        ['loinc_code' => '55411-3', 'display' => 'Exercise duration', 'value' => 30, 'unit' => 'min/d', 'ucum_code' => 'min/d'],
    ],
    'women_wellness' => [
        'lmp' => '2026-09-20',
        'pregnancy_status' => 'Not Pregnant',
    ],
    'lifestyle' => [
        ['code' => '81663-7', 'display' => 'Diet Type', 'value' => 'Vegetarian'],
        ['code' => '365981007', 'display' => 'Tobacco Smoking Status', 'value' => 'Never Smoked'],
        ['code' => '228273003', 'display' => 'Alcohol Consumption Status', 'value' => 'Lifetime Non-Drinker'],
        ['code' => 'diet-lifestyle', 'display' => 'Diet & Lifestyle Guidance', 'value' => '30 min daily walking, balanced hydration'],
        ['code' => 'general-nursing', 'display' => 'General Nursing Observations', 'value' => 'Patient conscious, alert and vitals stable'],
    ],
];

$gen = new \App\Libraries\Abdm\Fhir\Generators\WellnessFhirGenerator();
$res = $gen->generate($source);
echo "HI_TYPE: " . ($res['hi_type'] ?? '') . "\n";
echo "CARE_CONTEXT: " . ($res['care_context_reference'] ?? '') . "\n";
echo "DISPLAY: " . ($res['care_context_display'] ?? '') . "\n";
echo "BUNDLE ENTRIES: " . count($res['fhir_bundle']['entry'] ?? []) . "\n";

$composition = null;
$sections = [];
foreach ($res['fhir_bundle']['entry'] ?? [] as $entry) {
    if (($entry['resource']['resourceType'] ?? '') === 'Composition') {
        $composition = $entry['resource'];
        foreach ($composition['section'] ?? [] as $sec) {
            $sections[] = ($sec['title'] ?? 'Untitled') . ' (' . count($sec['entry'] ?? []) . ' refs)';
        }
    }
}
echo "COMPOSITION SECTIONS (" . count($sections) . "):\n - " . implode("\n - ", $sections) . "\n";
echo "VALIDATION: valid=" . (($res['validation']['valid'] ?? false) ? 'YES' : 'NO') . ", errors=" . count($res['validation']['errors'] ?? []) . "\n";
if (!empty($res['validation']['errors'])) {
    echo "ERRORS: " . json_encode($res['validation']['errors']) . "\n";
}

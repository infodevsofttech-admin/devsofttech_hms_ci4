<?php
require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

$config = [
    'DSN'      => '',
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'hms_data_ci4',
    'DBDriver' => 'MySQLi',
    'DBPrefix' => '',
    'pConnect' => false,
    'DBDebug'  => true,
    'charset'  => 'utf8',
    'DBCollat' => 'utf8_general_ci',
    'swapPre'  => '',
    'encrypt'  => false,
    'compress' => false,
    'strictOn' => false,
    'failover' => [],
    'port'     => 3306,
];
$db = \Config\Database::connect($config);

// Let's test what a canonical builder outputs
$builder = new \App\Libraries\FhirR4Builder();
$patient = [
    'id' => '11',
    'name' => 'DEVENDER SINGH',
    'gender' => 'male',
    'birthDate' => '1979-03-28',
    'phone' => '9818512600',
];
$immunizations = [[
    'id' => 10,
    'vaccine_name' => 'Fractional IPV',
    'vaccine_code' => '10',
    'vaccine_code_system' => 'http://hl7.org/fhir/sid/cvx',
    'given_date' => '2026-08-15 19:33:00',
    'dose_number' => 1,
    'series_doses' => 2,
    'series_name' => 'Fractional IPV',
    'target_disease_code' => '398102009',
    'target_disease_name' => 'Poliomyelitis',
    'manufacturer' => 'Cipla',
    'lot_number' => '0002145',
    'expiry_date' => '2026-08-29',
    'site_code' => '368209003',
    'site_name' => 'Right Upper Arm',
    'route_code' => '372464004',
    'route_name' => 'Intradermal',
    'notes' => 'Fractional dose 0.1 ml intradermal',
]];
$context = [
    'practitioner' => [
        'id' => '1',
        'name' => 'Dr. his demo',
        'registration_number' => '1',
    ],
    'organization' => [
        'name' => 'E-Atria Hospital',
        'hfr_id' => 'IN0510000871',
        'phone' => '90125 12505',
        'email' => 'contact@e-atria.in',
    ],
    'recommendation' => [
        'vaccine_name' => 'Fractional IPV',
        'vaccine_code' => '10',
        'vaccine_code_system' => 'http://hl7.org/fhir/sid/cvx',
        'due_date' => '2026-09-15',
        'dose_number' => 2,
        'series_doses' => 2,
        'series_name' => 'Fractional IPV',
    ],
    'care_context_reference' => 'IMM-10',
];

$bundle = $builder->buildImmunizationRecordBundle($patient, $immunizations, $context);
echo "Current Bundle entries count: " . count($bundle['entry']) . "\n";
foreach ($bundle['entry'] as $idx => $e) {
    echo "  $idx: " . $e['resource']['resourceType'] . "\n";
}

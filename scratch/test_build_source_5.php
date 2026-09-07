<?php

$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}

$patientDocId = 5;
$res = $mysqli->query("SELECT * FROM patient_doc WHERE id = " . $patientDocId);
$patientDoc = $res->fetch_assoc();

echo "patient_doc:\n";
print_r($patientDoc);

$patientId = (int) ($patientDoc['p_id'] ?? 0);
$resPat = $mysqli->query("SELECT * FROM patient_master WHERE id = " . $patientId);
$patientRow = $resPat->fetch_assoc();
echo "patient_master:\n";
print_r($patientRow);

$docFormatId = (int) ($patientDoc['doc_format_id'] ?? 0);
$resTpl = $mysqli->query("SELECT * FROM doc_format_master WHERE df_id = " . $docFormatId);
$templateRow = $resTpl ? $resTpl->fetch_assoc() : [];
echo "doc_format_master:\n";
print_r($templateRow);

$rawJson = json_decode($patientDoc['raw_data'] ?? '', true);
echo "raw_data decoded:\n";
print_r($rawJson);

// Now let's check what DoctorDocument::generatePatientDocPdfBytes(5) does!
$nabhScans = $mysqli->query("SHOW TABLES LIKE 'nabh_ipd_scans'");
echo "nabh_ipd_scans exists? " . ($nabhScans->num_rows > 0 ? 'YES' : 'NO') . "\n";
if ($nabhScans->num_rows > 0) {
    $scanRes = $mysqli->query("SELECT * FROM nabh_ipd_scans WHERE patient_doc_id = 5 OR ipd_id = " . (int)($rawJson['ipd_id'] ?? 0));
    while ($s = $scanRes->fetch_assoc()) {
        echo "nabh_ipd_scans row:\n";
        print_r($s);
        $fp = $s['file_path'];
        $candidates = [
            $fp,
            'public/' . $fp,
            'writable/' . $fp,
            'd:/Workplace/HMS_CI4_OLD/' . $fp,
            'd:/Workplace/HMS_CI4_OLD/public/' . $fp,
            'd:/Workplace/HMS_CI4_OLD/writable/' . $fp,
        ];
        foreach ($candidates as $c) {
            echo "File exists at $c? " . (file_exists($c) ? 'YES (' . filesize($c) . ' bytes)' : 'NO') . "\n";
        }
    }
}

require_once 'vendor/autoload.php';


// Check if public/uploads/nabh_ipd file can be read
$pdfPath = 'public/uploads/nabh_ipd/NABH_IPD_5_NABH_IPD_01_20260901_065133.pdf';
$pdfBytes = file_get_contents($pdfPath);
echo "\nRead PDF bytes: " . strlen($pdfBytes) . " bytes\n";

$source = [
    'record_id' => '5',
    'session_id' => '5',
    'visit_date' => '2026-09-01',
    'completed_at' => '2026-09-01T06:51:33+05:30',
    'document_title' => 'Admission Request & Initial Assessment Form',
    'document_data_base64' => base64_encode($pdfBytes),
    'content_type' => 'application/pdf',
    'doctor_name' => 'Dr. Sanjay Kumar',
    'hfr_id' => 'IN0510000828',
    'organization' => [
        'id' => 'IN0510000828',
        'name' => 'DevSoft Tech',
    ],
    'patient' => [
        'id' => '15',
        'uhid' => '15',
        'name' => 'MEERA BISHT',
        'gender' => 'female',
        'dob' => '1985-01-01',
        'abha_id' => '91178766183200',
        'abha_address' => 'meerabisht@sbx',
    ],
    'practitioner' => [
        'id' => '1',
        'name' => 'Dr. Sanjay Kumar',
    ],
];

echo "Generating HealthDocument FHIR bundle...\n";
$start = microtime(true);
$gen = new \App\Libraries\Abdm\Fhir\Generators\HealthDocumentFhirGenerator();
$res = $gen->generate($source);
$time = microtime(true) - $start;
echo "Generated in " . round($time, 3) . "s!\n";
echo "Bundle ID: " . $res['fhir_bundle']['id'] . "\n";
echo "Entries count: " . count($res['fhir_bundle']['entry']) . "\n";



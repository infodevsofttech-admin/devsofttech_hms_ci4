<?php
require __DIR__ . '/../vendor/autoload.php';

// Let's create a minimal valid 1-page PDF using mPDF or standard PDF header
$html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
      . 'body{font-family:freeserif;font-size:12pt;color:#1e293b;padding:20px;}'
      . 'h1{color:#0369a1;font-size:18pt;border-bottom:2px solid #0284c7;padding-bottom:5px;}'
      . 'table{width:100%;border-collapse:collapse;margin-top:15px;}'
      . 'th,td{border:1px solid #cbd5e1;padding:8px;text-align:left;font-size:10pt;}'
      . 'th{background:#f1f5f9;}'
      . '</style></head><body>'
      . '<h1>E-Atria Hospital</h1>'
      . '<p><b>DISCHARGE SUMMARY</b></p>'
      . '<table>'
      . '<tr><td><b>Patient Name:</b> DEVENDER SINGH</td><td><b>IPD No:</b> A26090000009</td></tr>'
      . '<tr><td><b>Patient ID:</b> 11</td><td><b>Gender / Age:</b> Male / 47 Yrs</td></tr>'
      . '<tr><td><b>Admission Date:</b> 28-08-2026</td><td><b>Discharge Date:</b> 06-09-2026</td></tr>'
      . '<tr><td><b>Doctor:</b> Dr. R.K.SUNDRIYAL</td><td><b>Diagnosis:</b> VIRAL DISEASE</td></tr>'
      . '</table>'
      . '<h3 style="margin-top:20px;color:#0369a1;">Discharge Medications</h3>'
      . '<table>'
      . '<tr><th>Medicine</th><th>Dosage</th></tr>'
      . '<tr><td>ACILOC</td><td>Take 1 tablet twice daily before meals</td></tr>'
      . '<tr><td>PANTOP DSR</td><td>Take 1 tablet once daily before meals</td></tr>'
      . '</table>'
      . '<h3 style="margin-top:20px;color:#0369a1;">Advice / Follow-up</h3>'
      . '<p>Dietary Advice: Fruits and Vegetables daily. Avoid spicy & oily food.</p>'
      . '<p>Review After: 1 Month (02-10-2026)</p>'
      . '</body></html>';

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 15,
    'margin_bottom' => 15,
    'default_font' => 'freeserif'
]);
$mpdf->WriteHTML($html);
$pdfBytes = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
$pdfBase64 = base64_encode($pdfBytes);

echo "Generated PDF size: " . strlen($pdfBytes) . " bytes (" . strlen($pdfBase64) . " base64 chars)\n";

// Load bundle without pdf
$bundle = json_decode(file_get_contents(__DIR__ . '/bundle_without_pdf.json'), true);

$docRefId = "1863138e-1e2b-475a-89bf-e3a9bcb73eaa";
$docRefUuid = "urn:uuid:" . $docRefId;

// 1. Add section in Composition
$bundle['entry'][0]['resource']['section'][] = [
    "title" => "Document Reference",
    "code" => [
        "coding" => [
            [
                "system" => "http://snomed.info/sct",
                "code" => "373942005",
                "display" => "Discharge summary"
            ]
        ]
    ],
    "entry" => [
        [
            "reference" => $docRefUuid,
            "display" => "Discharge Summary"
        ]
    ]
];

// 2. Add DocumentReference entry strictly adhering to NRCES profile
$bundle['entry'][] = [
    "fullUrl" => $docRefUuid,
    "resource" => [
        "resourceType" => "DocumentReference",
        "id" => $docRefId,
        "meta" => [
            "profile" => [
                "https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentReference"
            ]
        ],
        "status" => "current",
        "docStatus" => "final",
        "type" => [
            "coding" => [
                [
                    "system" => "http://snomed.info/sct",
                    "code" => "373942005",
                    "display" => "Discharge Summary"
                ]
            ],
            "text" => "Discharge Summary"
        ],
        "subject" => [
            "reference" => "urn:uuid:11cef8a6-4cd1-4733-afd0-92898eb57901",
            "display" => "DEVENDER SINGH"
        ],
        "content" => [
            [
                "attachment" => [
                    "contentType" => "application/pdf",
                    "language" => "en-IN",
                    "data" => $pdfBase64,
                    "title" => "Discharge Summary",
                    "creation" => "2026-09-06T02:08:00+05:30"
                ]
            ]
        ]
    ]
];

$bundle['id'] = "discharge-A26090000009-v" . time();
$bundle['identifier']['value'] = $bundle['id'];

$validator = new \App\Libraries\Abdm\Fhir\Support\FhirBundleValidator();
$val = $validator->validate($bundle);

echo "Validation Result (With Official DocumentReference):\n";
echo "Valid: " . ($val->valid ? "YES" : "NO") . " | Score: " . $val->score . "%\n";
echo "Total Entries: " . count($bundle['entry']) . "\n";
if (!empty($val->errors)) {
    echo "Errors: " . print_r($val->errors, true) . "\n";
}

file_put_contents(__DIR__ . '/bundle_with_pdf.json', json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

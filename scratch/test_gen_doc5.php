<?php

require_once 'vendor/autoload.php';

$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4');

$patientDoc = $mysqli->query("
    SELECT pd.*, dm.doc_name, dm.doc_desc, dm.default_print_type, dm.print_top_margin, dm.print_bottom_margin, dm.print_left_margin, dm.print_right_margin, dm.print_header_margin, dm.print_footer_margin, p.p_fname, p.p_relative, p.p_rname, p.p_code, p.gender, p.age, p.age_in_month, p.estimate_dob, p.dob, dr.p_fname as dr_name
    FROM patient_doc pd
    LEFT JOIN doc_format_master dm ON pd.doc_format_id=dm.df_id
    LEFT JOIN patient_master p ON pd.p_id=p.id
    LEFT JOIN doctor_master dr ON pd.dr_id=dr.id
    WHERE pd.id = 5
")->fetch_assoc();

echo "patientDoc fetched successfully\n";

$mpdf = new \Mpdf\Mpdf([
    'format' => 'A4',
    'margin_top' => 61,
    'margin_bottom' => 25,
    'margin_left' => 7,
    'margin_right' => 7,
    'margin_header' => 5,
    'margin_footer' => 15,
    'tempDir' => sys_get_temp_dir(),
]);

$html = '<div><h1>Test Document</h1><p>' . htmlspecialchars($patientDoc['raw_data']) . '</p></div>';
$mpdf->WriteHTML($html);
$bytes = $mpdf->Output('', 'S');
echo "PDF generated successfully: " . strlen($bytes) . " bytes\n";

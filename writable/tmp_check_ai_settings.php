<?php
$db = new mysqli('localhost','root','','hms_ci4_2026',3306);
$sqls = [];
$sqls[] = "CREATE TABLE IF NOT EXISTS doc_format_master (
  df_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  doc_name VARCHAR(150) NOT NULL,
  doc_desc VARCHAR(255) NOT NULL DEFAULT '',
  doc_raw_format LONGTEXT NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (df_id),
  KEY idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS doc_format_sub (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  doc_format_id INT UNSIGNED NOT NULL,
  input_name VARCHAR(120) NOT NULL,
  input_code VARCHAR(80) NOT NULL,
  input_type VARCHAR(20) NOT NULL DEFAULT 'text',
  input_default_value TEXT NULL,
  short_order INT NOT NULL DEFAULT 1,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_doc_format_id (doc_format_id),
  KEY idx_doc_format_code (doc_format_id,input_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS patient_doc (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  p_id INT UNSIGNED NOT NULL,
  doc_format_id INT UNSIGNED NOT NULL,
  dr_id INT UNSIGNED NOT NULL DEFAULT 0,
  date_issue DATE NULL,
  raw_data LONGTEXT NOT NULL,
  update_pre_value TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_p_id (p_id),
  KEY idx_doc_format_id (doc_format_id),
  KEY idx_dr_id (dr_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS patient_doc_raw (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  p_id INT UNSIGNED NOT NULL,
  p_doc_id INT UNSIGNED NOT NULL,
  p_doc_sub_id INT UNSIGNED NOT NULL,
  p_doc_raw_value TEXT NULL,
  update_data TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_p_doc_id (p_doc_id),
  KEY idx_p_doc_sub_id (p_doc_sub_id),
  KEY idx_p_id (p_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

foreach ($sqls as $sql) {
    if (! $db->query($sql)) {
        echo "Error: " . $db->error . PHP_EOL;
    }
}

$seedCheck = $db->query("SELECT COUNT(*) c FROM doc_format_master")->fetch_assoc();
if ((int)($seedCheck['c'] ?? 0) === 0) {
    $tpl = '<h1 style="text-align:center"><u>FITNESS CERTIFICATE</u></h1>\n\n<p style="text-align:justify">I, Dr {dr_name}, after carefully examining the case, hereby certify that {p_fname} ({p_code}) has been advised rest from {rest_from_date} to {rest_to_date}.\nHe/She may resume duty from {resume_date}.</p>\n\n<p style="text-align:right">Dr. {dr_name}<br>{dr_sign}</p>';
    $esc = $db->real_escape_string($tpl);
    $db->query("INSERT INTO doc_format_master (doc_name,doc_desc,doc_raw_format,active,created_at,updated_at) VALUES ('FITNESS CERTIFICATE','FITNESS CERTIFICATE','$esc',1,NOW(),NOW())");
    $dfId = (int) $db->insert_id;
    $inputs = [
        ['Suffering Diseases Name','DISEASES','text',''],
        ['Since From','SINCE','date',date('d/m/Y')],
        ['Rest From Date','rest_from_date','date',date('d/m/Y')],
        ['Rest To Date','rest_to_date','date',date('d/m/Y')],
        ['Duty Start Date','resume_date','date',date('d/m/Y')],
    ];
    $order = 1;
    foreach ($inputs as $in) {
        $n = $db->real_escape_string($in[0]);
        $c = $db->real_escape_string($in[1]);
        $t = $db->real_escape_string($in[2]);
        $d = $db->real_escape_string($in[3]);
        $db->query("INSERT INTO doc_format_sub (doc_format_id,input_name,input_code,input_type,input_default_value,short_order,active,created_at,updated_at) VALUES ($dfId,'$n','$c','$t','$d',$order,1,NOW(),NOW())");
        $order++;
    }
    echo "Seeded default FITNESS CERTIFICATE template." . PHP_EOL;
}

echo "Doctor document tables ready." . PHP_EOL;

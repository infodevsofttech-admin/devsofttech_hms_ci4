<?php
$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4', 3306);
if ($mysqli->connect_errno) {
    fwrite(STDERR, 'connect_error: ' . $mysqli->connect_error . PHP_EOL);
    exit(1);
}

$tables = ['patient_master', 'abdm_opd_tokens'];
foreach ($tables as $t) {
    echo "\n== {$t} ==\n";
    $res = $mysqli->query("SHOW COLUMNS FROM `{$t}`");
    while ($row = $res->fetch_assoc()) {
        if (stripos($row['Field'], 'abha') !== false) {
            echo $row['Field'] . ' | ' . $row['Type'] . ' | NULL=' . $row['Null'] . PHP_EOL;
        }
    }
}

echo "\n== patient_master data checks ==\n";
$q = [
  "SELECT COUNT(*) c FROM patient_master WHERE abha_id IS NOT NULL AND abha_id <> ''",
  "SELECT COUNT(*) c FROM patient_master WHERE abha_address IS NOT NULL AND abha_address <> ''",
  "SELECT COUNT(*) c FROM patient_master WHERE abha_id IS NOT NULL AND abha_id <> '' AND abha_id LIKE '%@%'",
  "SELECT COUNT(*) c FROM patient_master WHERE abha_id IS NOT NULL AND abha_id <> '' AND abha_address IS NOT NULL AND abha_address <> ''",
];
foreach ($q as $sql) {
  $r = $mysqli->query($sql);
  $row = $r->fetch_assoc();
  echo $sql . ' => ' . (int)$row['c'] . PHP_EOL;
}
?>

<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_data_ci4';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Clean any existing 'EditRemove' text from remark in DB
$stmt = $pdo->prepare("UPDATE ipd_discharge_prescrption_prescribed SET remark = '' WHERE remark IN ('EditRemove', 'Edit Remove', 'Remove', 'Edit')");
$stmt->execute();
echo "Updated " . $stmt->rowCount() . " rows in ipd_discharge_prescrption_prescribed\n";

$stmt = $pdo->query("SELECT id, ipd_id, med_name, dosage_when, remark FROM ipd_discharge_prescrption_prescribed WHERE ipd_id = 9");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

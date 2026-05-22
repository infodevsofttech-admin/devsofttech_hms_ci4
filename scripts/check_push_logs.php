<?php
$pdo = new PDO('mysql:host=localhost;port=3306;dbname=hms_data_ci4', 'root', '');

// Full detail for the latest push attempt
$r = $pdo->query("SELECT id, response_code, response_json, LEFT(request_json,400) as req, created_at FROM abdm_api_logs WHERE endpoint LIKE '%records/push%' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "ID:{$r['id']} | HTTP:{$r['response_code']} | {$r['created_at']}\n";
echo "RESPONSE: {$r['response_json']}\n";
echo "REQUEST(400): {$r['req']}\n\n";

// Token in DB
$t = $pdo->query("SELECT s_value FROM hospital_setting WHERE s_name='EATRIA_BRIDGE_TOKEN' LIMIT 1")->fetchColumn();
echo "TOKEN in DB: " . substr($t, 0, 8) . "***" . substr($t, -4) . " (len=" . strlen($t) . ")\n";

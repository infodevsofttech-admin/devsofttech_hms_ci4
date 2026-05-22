<?php
// Quick test: verify the DB token authenticates against the bridge
$pdo = new PDO('mysql:host=localhost;port=3306;dbname=hms_data_ci4', 'root', '');
$token = trim($pdo->query("SELECT s_value FROM hospital_setting WHERE s_name='EATRIA_BRIDGE_TOKEN' LIMIT 1")->fetchColumn());

echo "Token (masked): " . substr($token, 0, 8) . "***" . substr($token, -4) . " (len=" . strlen($token) . ")\n";

$hfrId = 'IN0510000828';

// Test 1: health endpoint
$ch = curl_init('https://abdm-bridge.e-atria.in/api/v3/health?hfr_id=' . urlencode($hfrId));
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Accept: application/json']]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$data = json_decode($body, true);
echo "GET /v3/health → HTTP $code | api_key_ok=" . ($data['api_key_ok'] ? 'true' : 'false') . "\n\n";

// Test 2: records/push with full required payload
$hfrId2 = trim($pdo->query("SELECT s_value FROM hospital_setting WHERE s_name='ABDM_HFR_ID' LIMIT 1")->fetchColumn());
$pushBody = json_encode([
    'patient_id'             => '2',
    'patient_name'           => 'TEST PATIENT',
    'abha_address'           => '91510165305101',
    'hfr_id'                 => $hfrId2,
    'record_type'            => 'prescription',
    'hi_type'                => 'OPConsultRecord',
    'visit_date'             => date('Y-m-d'),
    'care_context_reference' => 'TEST-' . date('Ymd-His'),
    'record_data'            => ['resourceType' => 'Bundle', 'type' => 'document', 'entry' => []],
]);
$ch2 = curl_init('https://abdm-bridge.e-atria.in/api/v3/records/push');
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $pushBody,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
]);
$body2 = curl_exec($ch2);
$code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);
echo "POST /v3/records/push → HTTP $code2\n";
echo "Response: $body2\n";

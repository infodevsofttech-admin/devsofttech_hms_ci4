<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$rows = $db->query("SELECT s_name, s_value FROM hospital_setting WHERE s_name IN ('EATRIA_BRIDGE_TOKEN','ABDM_HFR_ID')");
$settings = [];
while ($r = $rows->fetch_assoc()) {
    $settings[$r['s_name']] = $r['s_value'];
}
$db->close();

$token = trim((string) ($settings['EATRIA_BRIDGE_TOKEN'] ?? ''));
$hfrId = trim((string) ($settings['ABDM_HFR_ID'] ?? ''));

$requestId = $argv[1] ?? 'REQ-20260728030125-b03b873b';

$url = 'https://abdm-bridge.e-atria.in/api/v1/hiu/consent/status?' . http_build_query([
    'request_id' => $requestId,
    'hfr_id' => $hfrId,
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
    ],
]);
$raw = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$err = curl_error($ch);
curl_close($ch);

echo "URL: {$url}\n";
echo "HTTP Code: {$httpCode}\n";
echo "Curl error: {$err}\n";
if ($raw !== false) {
    echo "--- Headers ---\n" . substr($raw, 0, $headerSize) . "\n";
    echo "--- Body (raw bytes length=" . strlen(substr($raw, $headerSize)) . ") ---\n";
    echo substr($raw, $headerSize) . "\n";
}

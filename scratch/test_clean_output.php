<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_data_ci4';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Test cleaning on actual stored content
$stmt = $pdo->query("SELECT content FROM ipd_discharge WHERE ipd_id = 9");
$content = $stmt->fetchColumn();

// Apply the sanitizer regex
$pattern1 = '/<h[1-6]\b[^>]*class="[^"]*discharge-section-heading[^"]*"[^>]*>\s*Discharge\s+Summary\s*<\/h[1-6]>\s*(?:<div class="discharge-status">\s*<strong>\s*Discharge\s+Summary\s*<\/strong>\s*<\/div>\s*)?(?!<div class="discharge-summary-content">)/i';
$out = preg_replace($pattern1, '', $content);

$pattern2 = '/<div class="discharge-status">\s*<strong>\s*Discharge\s+Summary\s*<\/strong>\s*<\/div>/i';
$out = preg_replace($pattern2, '', $out);

echo "=== FIRST 400 CHARS OF SANITIZED OUTPUT ===\n";
echo substr(trim($out), 0, 400) . "\n";

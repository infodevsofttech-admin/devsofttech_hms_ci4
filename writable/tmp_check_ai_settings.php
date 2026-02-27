<?php
require __DIR__ . '/../vendor/autoload.php';

$db = Config\Database::connect();
$keys = [
    'AZURE_OPENAI_ENDPOINT',
    'AZURE_OPENAI_API_KEY',
    'AZURE_OPENAI_DEPLOYMENT',
    'AZURE_OPENAI_API_VERSION',
    'AZURE_DOCINTEL_ENDPOINT',
    'AZURE_DOCINTEL_KEY',
];
$rows = $db->table('hospital_setting')
    ->select('s_name, s_value')
    ->whereIn('s_name', $keys)
    ->get()
    ->getResultArray();

$map = [];
foreach ($rows as $row) {
    $map[$row['s_name']] = trim((string) ($row['s_value'] ?? ''));
}

foreach ($keys as $key) {
    $value = $map[$key] ?? '';
    $len = strlen($value);
    if ($len === 0) {
        $show = '<empty>';
    } elseif ($len <= 8) {
        $show = str_repeat('*', $len);
    } else {
        $show = substr($value, 0, 4) . str_repeat('*', $len - 8) . substr($value, -4);
    }

    echo $key . '=' . $show . PHP_EOL;
}

<?php
$json = json_decode(file_get_contents(__DIR__ . '/../ABDM_FHIR/examples.json/Bundle-ImmunizationRecord-example-07.json'), true);
function extractStrings($data, $prefix = '') {
    $res = [];
    if (is_string($data)) {
        if (strlen($data) < 100) $res[$prefix] = $data;
        return $res;
    }
    if (!is_array($data)) return [];
    foreach ($data as $k => $v) {
        $p = $prefix === '' ? $k : $prefix . '.' . $k;
        $res = array_merge($res, extractStrings($v, $p));
    }
    return $res;
}
$strings = extractStrings($json);
foreach ($strings as $p => $s) {
    if (!str_contains($p, 'uuid') && !str_contains($p, 'system') && !str_contains($p, 'profile') && !str_contains($p, 'div')) {
        echo sprintf("%-50s: %s\n", $p, $s);
    }
}

<?php
$b = json_decode(file_get_contents('writable/ipd9_bundle.json'), true);
foreach ($b['entry'] as $e) {
    if ($e['resource']['resourceType'] === 'MedicationRequest') {
        $r = $e['resource'];
        unset($r['text']);
        echo json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
    }
}

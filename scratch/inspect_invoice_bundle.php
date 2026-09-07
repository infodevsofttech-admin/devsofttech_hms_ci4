<?php

$bundle = json_decode(file_get_contents('ABDM_FHIR/examples.json/Bundle-InvoiceRecord-example-01.json'), true);

echo "Bundle resourceType: " . ($bundle['resourceType'] ?? '') . "\n";
echo "Bundle type: " . ($bundle['type'] ?? '') . "\n";
echo "Profiles: " . json_encode($bundle['meta']['profile'] ?? []) . "\n";

echo "\nEntries in Bundle-InvoiceRecord-example-01.json:\n";
foreach ($bundle['entry'] as $idx => $e) {
    $r = $e['resource'];
    echo "[$idx] {$r['resourceType']} (id: {$r['id']})\n";
}

$comp = $bundle['entry'][0]['resource'];
if ($comp['resourceType'] === 'Composition') {
    echo "\nComposition sections:\n";
    foreach ($comp['section'] ?? [] as $s) {
        $code = $s['code']['coding'][0]['code'] ?? 'no code';
        $title = $s['title'] ?? 'no title';
        echo " - Section: '$title' (code: $code)\n";
        foreach ($s['entry'] ?? [] as $re) {
            echo "    -> ref: " . ($re['reference'] ?? '') . "\n";
        }
    }
}

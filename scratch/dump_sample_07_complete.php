<?php
$data = json_decode(file_get_contents('ABDM_FHIR/examples.json/Bundle-ImmunizationRecord-example-07.json'), true);
echo "=== Bundle Meta & Identifiers ===\n";
echo "resourceType: " . $data['resourceType'] . "\n";
echo "id: " . $data['id'] . "\n";
echo "meta: " . json_encode($data['meta']) . "\n";
echo "identifier: " . json_encode($data['identifier']) . "\n";
echo "type: " . $data['type'] . "\n";
echo "timestamp: " . ($data['timestamp'] ?? 'NONE') . "\n";
echo "Total entries: " . count($data['entry']) . "\n\n";

foreach ($data['entry'] as $i => $entry) {
    echo "--------------------------------------------------------\n";
    echo "Entry {$i}: fullUrl = " . $entry['fullUrl'] . "\n";
    $res = $entry['resource'];
    echo "Resource: " . $res['resourceType'] . " (id: " . $res['id'] . ")\n";
    // echo JSON without text div
    unset($res['text']);
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

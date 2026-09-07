<?php
$b = json_decode(file_get_contents('ABDM_FHIR/examples.json/Bundle-DischargeSummary-example-04.json'), true);
function printResource($r) {
    echo "--- " . $r['resourceType'] . " (" . $r['id'] . ") ---\n";
    $copy = $r;
    unset($copy['text']); // suppress text for brevity
    if (isset($copy['content'][0]['attachment']['data'])) {
        $copy['content'][0]['attachment']['data'] = '<base64 truncated>';
    }
    echo json_encode($copy, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
}

foreach ($b['entry'] as $e) {
    $r = $e['resource'];
    if ($r['resourceType'] === 'Encounter') {
        printResource($r);
    }
}

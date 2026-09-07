<?php
$bundle = json_decode(file_get_contents('ABDM_FHIR/examples.json/Bundle-ImmunizationRecord-example-07.json'), true);

foreach ($bundle['entry'] as $i => $entry) {
    $res = $entry['resource'];
    echo "==================== ENTRY $i : " . $res['resourceType'] . " ====================\n";
    // omit large text/div or pdf data
    if (isset($res['text']['div'])) {
        $res['text']['div'] = substr($res['text']['div'], 0, 100) . '...';
    }
    if (isset($res['content'][0]['attachment']['data'])) {
        $res['content'][0]['attachment']['data'] = '[BASE64 DATA TRUNCATED]';
    }
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
}

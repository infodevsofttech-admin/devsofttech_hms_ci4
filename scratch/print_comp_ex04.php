<?php
$b = json_decode(file_get_contents('ABDM_FHIR/examples.json/Bundle-DischargeSummary-example-04.json'), true);
$comp = $b['entry'][0]['resource'];
unset($comp['text']);
echo json_encode($comp, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

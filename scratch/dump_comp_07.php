<?php
$bundle = json_decode(file_get_contents('ABDM_FHIR/examples.json/Bundle-ImmunizationRecord-example-07.json'), true);
echo json_encode($bundle['entry'][0]['resource'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

<?php
$sd = json_decode(file_get_contents('ABDM_FHIR/package/package/StructureDefinition-Immunization.json'), true);
foreach ($sd['snapshot']['element'] as $el) {
    if (strpos($el['id'], 'Immunization.location') === 0) {
        echo $el['id'] . ":\n";
        echo "  Type: " . json_encode($el['type']) . "\n";
    }
}

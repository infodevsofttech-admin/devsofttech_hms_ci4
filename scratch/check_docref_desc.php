<?php
$sd = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-DocumentReference.json'), true);
foreach ($sd['snapshot']['element'] as $el) {
    if ($el['id'] == 'DocumentReference.description') {
        echo "DocumentReference.description: min={$el['min']}, max={$el['max']}\n";
    }
}

<?php
$def = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-CarePlan.json'), true);
foreach ($def['snapshot']['element'] as $el) {
    if ($el['min'] > 0) {
        echo $el['id'] . " min=" . $el['min'] . "\n";
    }
}

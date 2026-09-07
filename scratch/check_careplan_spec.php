<?php
$def = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-CarePlan.json'), true);
foreach ($def['snapshot']['element'] as $el) {
    if (strpos($el['id'], 'CarePlan.activity') === 0 || strpos($el['id'], 'CarePlan.category') === 0) {
        echo $el['id'] . " [min: " . $el['min'] . ", max: " . $el['max'] . "]\n";
    }
}

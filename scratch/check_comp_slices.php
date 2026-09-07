<?php
$def = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-DischargeSummaryRecord.json'), true);
foreach ($def['snapshot']['element'] as $el) {
    if (strpos($el['id'], 'Composition.section:') === 0) {
        echo $el['id'] . " (min=" . $el['min'] . ", max=" . $el['max'] . ")\n";
    }
}

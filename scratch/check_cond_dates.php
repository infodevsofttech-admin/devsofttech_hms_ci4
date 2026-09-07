<?php
$sd = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-Condition.json'), true);
foreach ($sd['snapshot']['element'] as $el) {
    if (str_starts_with($el['id'], 'Condition.recordedDate') || str_starts_with($el['id'], 'Condition.onset')) {
        echo "{$el['id']} -> type: " . json_encode($el['type']) . ", min: {$el['min']}, max: {$el['max']}\n";
    }
}

<?php

$sd = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-MedicationRequest.json'), true);

echo "Checking MedicationRequest category & method elements:\n";
foreach ($sd['snapshot']['element'] as $el) {
    if ($el['id'] === 'MedicationRequest.category' || $el['id'] === 'MedicationRequest.dosageInstruction.method') {
        echo "{$el['id']} -> min: {$el['min']}, max: {$el['max']}\n";
    }
}

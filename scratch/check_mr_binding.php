<?php
$sd = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-MedicationRequest.json'), true);
foreach ($sd['snapshot']['element'] as $el) {
    if (str_starts_with($el['id'], 'MedicationRequest.category') || str_starts_with($el['id'], 'MedicationRequest.reason')) {
        echo "{$el['id']}\n";
        if (isset($el['binding'])) {
            echo "  binding: " . json_encode($el['binding']) . "\n";
        }
    }
}

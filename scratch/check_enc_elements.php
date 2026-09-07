<?php

$sd = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-Encounter.json'), true);

echo "=== ENCOUNTER ELEMENTS IN NRCES ===\n";
foreach ($sd['snapshot']['element'] as $el) {
    if (str_starts_with($el['id'], 'Encounter.hospitalization') || str_starts_with($el['id'], 'Encounter.diagnosis') || str_starts_with($el['id'], 'Encounter.reason')) {
        echo "{$el['id']}\n";
        if (isset($el['binding'])) {
            echo "  binding: " . json_encode($el['binding']) . "\n";
        }
        if (isset($el['type'])) {
            foreach ($el['type'] as $t) {
                echo "  type: " . $t['code'] . (isset($t['targetProfile']) ? (' -> ' . implode(',', $t['targetProfile'])) : '') . "\n";
            }
        }
    }
}

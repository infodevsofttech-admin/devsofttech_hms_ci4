<?php
$sd = json_decode(file_get_contents('ABDM_FHIR/package/package/StructureDefinition-ImmunizationRecord.json'), true);
foreach ($sd['snapshot']['element'] as $el) {
    if (strpos($el['id'], 'Composition.category') === 0 || strpos($el['id'], 'Composition.encounter') === 0) {
        echo $el['id'] . ":\n";
        if (isset($el['binding'])) {
            echo "  Binding: " . json_encode($el['binding']) . "\n";
        }
        if (isset($el['type'])) {
            echo "  Type: " . json_encode($el['type']) . "\n";
        }
    }
}

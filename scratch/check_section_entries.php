<?php
$sd = json_decode(file_get_contents(__DIR__ . '/../ABDM_FHIR/examples.json/StructureDefinition-ImmunizationRecord.json'), true);
echo "=== ImmunizationRecord Section Entries ===\n";
foreach ($sd['snapshot']['element'] as $el) {
    if (strpos($el['id'], 'Composition.section.entry') !== false) {
        echo $el['id'] . "\n";
        if (isset($el['type'])) {
            echo "  types: " . json_encode($el['type']) . "\n";
        }
    }
}

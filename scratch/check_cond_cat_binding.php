<?php
$def = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-Condition.json'), true);
foreach ($def['snapshot']['element'] as $el) {
    if (strpos($el['id'], 'Condition.category') === 0) {
        echo $el['id'] . "\n";
        if (isset($el['binding'])) {
            echo "  Binding: " . json_encode($el['binding']) . "\n";
        }
    }
}

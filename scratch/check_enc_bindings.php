<?php
$sd = json_decode(file_get_contents(__DIR__ . '/../ABDM_FHIR/examples.json/StructureDefinition-Encounter.json'), true);
foreach ($sd['snapshot']['element'] as $el) {
    if (strpos($el['id'], 'Encounter.serviceType') === 0 || strpos($el['id'], 'Encounter.type') === 0) {
        echo $el['id'] . "\n";
        if (isset($el['binding'])) {
            echo "  binding: " . json_encode($el['binding']) . "\n";
        }
        if (isset($el['definition'])) {
            echo "  def: " . $el['definition'] . "\n";
        }
    }
}

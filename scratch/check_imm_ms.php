<?php
$sd = json_decode(file_get_contents(__DIR__ . '/../ABDM_FHIR/examples.json/StructureDefinition-Immunization.json'), true);
echo "=== Immunization MustSupport elements ===\n";
foreach ($sd['snapshot']['element'] as $el) {
    if (!empty($el['mustSupport'])) {
        echo $el['id'] . " (min: " . $el['min'] . ")\n";
    }
}

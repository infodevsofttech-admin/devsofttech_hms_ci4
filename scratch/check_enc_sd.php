<?php
$sd = json_decode(file_get_contents(__DIR__ . '/../ABDM_FHIR/examples.json/StructureDefinition-Encounter.json'), true);
echo "=== Encounter elements ===\n";
foreach ($sd['snapshot']['element'] as $el) {
    if (substr_count($el['id'], '.') === 1) {
        $ms = !empty($el['mustSupport']) ? ' [MustSupport]' : '';
        echo $el['id'] . $ms . "\n";
    }
}

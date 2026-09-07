<?php
$sd = json_decode(file_get_contents(__DIR__ . '/../ABDM_FHIR/examples.json/StructureDefinition-ImmunizationRecord.json'), true);
echo "=== Elements in Composition (ImmunizationRecord) ===" . PHP_EOL;
foreach ($sd['snapshot']['element'] as $el) {
    $id = $el['id'];
    $min = $el['min'] ?? 0;
    $max = $el['max'] ?? '*';
    $ms = !empty($el['mustSupport']) ? ' [MustSupport]' : '';
    // Show top-level properties of Composition
    if (substr_count($id, '.') === 1) {
        $type = isset($el['type']) ? json_encode(array_column($el['type'], 'code')) : '';
        echo sprintf("%-30s %s..%-3s %s %s\n", $id, $min, $max, $ms, $type);
    }
}

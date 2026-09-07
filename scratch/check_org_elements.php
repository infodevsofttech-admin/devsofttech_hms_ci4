<?php
$sd = json_decode(file_get_contents('ABDM_FHIR/package/package/StructureDefinition-Organization.json'), true);
foreach ($sd['snapshot']['element'] as $el) {
    $id = $el['id'];
    $min = $el['min'] ?? 0;
    $max = $el['max'] ?? '*';
    $type = isset($el['type']) ? implode(',', array_column($el['type'], 'code')) : '';
    echo sprintf("%-40s | %s..%s | %s\n", $id, $min, $max, $type);
}

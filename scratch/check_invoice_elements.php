<?php

$sd = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-Invoice.json'), true);

echo "Elements in StructureDefinition-Invoice:\n";
foreach ($sd['snapshot']['element'] as $el) {
    $types = [];
    if (isset($el['type'])) {
        foreach ($el['type'] as $t) {
            $code = $t['code'] ?? '';
            $tp = isset($t['targetProfile']) ? (' -> ' . implode(',', $t['targetProfile'])) : '';
            $types[] = $code . $tp;
        }
    }
    echo "{$el['id']} [" . implode('|', $types) . "] min:{$el['min']} max:{$el['max']}\n";
}

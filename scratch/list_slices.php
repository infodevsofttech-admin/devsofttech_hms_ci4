<?php
$def = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-DischargeSummaryRecord.json'), true);
$slices = [];
foreach ($def['snapshot']['element'] as $el) {
    if (preg_match('/^Composition\.section:([a-zA-Z0-9]+)$/', $el['id'], $m)) {
        $slices[] = $m[1];
    }
}
echo "Slices: " . implode(', ', $slices) . "\n";

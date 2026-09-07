<?php

$sd = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-MedicationRequest.json'), true);

echo "=== MEDICATION REQUEST ELEMENTS ===\n";
foreach ($sd['snapshot']['element'] as $el) {
    $id = $el['id'];
    $parts = explode('.', $id);
    if (count($parts) <= 3) {
        $types = [];
        if (isset($el['type'])) {
            foreach ($el['type'] as $t) $types[] = $t['code'];
        }
        echo str_pad($id, 45) . " [" . implode('|', $types) . "] min:{$el['min']} max:{$el['max']}\n";
    }
}

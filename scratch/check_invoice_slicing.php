<?php

$sd = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-InvoiceRecord.json'), true);

foreach ($sd['snapshot']['element'] as $el) {
    if (isset($el['slicing'])) {
        echo "{$el['id']} has slicing:\n";
        echo json_encode($el['slicing'], JSON_PRETTY_PRINT) . "\n";
    }
}

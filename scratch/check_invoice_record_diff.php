<?php

$sd = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-InvoiceRecord.json'), true);

echo "Checking section slices in StructureDefinition-InvoiceRecord:\n";
$differential = $sd['differential']['element'];
foreach ($differential as $el) {
    if (str_starts_with($el['id'], 'Composition.section')) {
        echo "{$el['id']}\n";
        if (isset($el['sliceName'])) {
            echo "  sliceName: {$el['sliceName']}\n";
        }
        if (isset($el['code'])) {
            echo "  code: " . json_encode($el['code']) . "\n";
        }
        if (isset($el['type'])) {
            echo "  type: " . json_encode($el['type']) . "\n";
        }
    }
}

<?php

$sd = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-InvoiceRecord.json'), true);

echo "Snapshot Elements in StructureDefinition-InvoiceRecord:\n";
foreach ($sd['snapshot']['element'] as $el) {
    if (str_starts_with($el['id'], 'Composition.section')) {
        echo "{$el['id']}\n";
        if (isset($el['sliceName'])) {
            echo "  sliceName: {$el['sliceName']}\n";
        }
        if (isset($el['type'])) {
            foreach ($el['type'] as $t) {
                if (isset($t['targetProfile'])) {
                    echo "  targetProfile: " . json_encode($t['targetProfile']) . "\n";
                }
            }
        }
    }
}

<?php

$sd = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-Invoice.json'), true);

echo "Checking Attachment in Invoice SD:\n";
foreach ($sd['snapshot']['element'] as $el) {
    if (isset($el['type'])) {
        foreach ($el['type'] as $t) {
            if (in_array($t['code'], ['Attachment', 'Binary', 'base64Binary'])) {
                echo "{$el['id']} has type {$t['code']}\n";
            }
        }
    }
}
echo "Done checking Attachment in Invoice.\n";

<?php
$sd = json_decode(file_get_contents('ABDM_FHIR/package/package/StructureDefinition-Organization.json'), true);
foreach ($sd['snapshot']['element'] as $el) {
    if ($el['id'] === 'Organization.type') {
        echo json_encode($el['binding'] ?? [], JSON_PRETTY_PRINT) . "\n";
    }
}

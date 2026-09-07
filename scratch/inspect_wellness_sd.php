<?php
$file = 'ABDM_FHIR/definitions.json/StructureDefinition-WellnessRecord.json';
if (!file_exists($file)) {
    $file = 'ABDM_FHIR/package/package/StructureDefinition-WellnessRecord.json';
}
$sd = json_decode(file_get_contents($file), true);

$exFile = 'ABDM_FHIR/examples.json/Bundle-WellnessRecord-example-01.json';
if (file_exists($exFile)) {
    $ex = json_decode(file_get_contents($exFile), true);
    echo "=== EXAMPLE COMPOSITION ===\n";
    echo "=== COMPOSITION.TYPE IN SD ===\n";
    foreach ($sd['snapshot']['element'] as $el) {
        if (str_starts_with($el['id'], 'Composition.type')) {
            print_r($el);
        }
    }


}


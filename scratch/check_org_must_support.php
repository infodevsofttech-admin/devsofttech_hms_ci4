<?php
$sd = json_decode(file_get_contents('ABDM_FHIR/package/package/StructureDefinition-Organization.json'), true);
foreach ($sd['snapshot']['element'] as $el) {
    if (str_starts_with($el['id'], 'Organization.address') || str_starts_with($el['id'], 'Organization.telecom')) {
        echo $el['id'] . ' | min:' . $el['min'] . ' | max:' . $el['max'] . ' | mustSupport:' . (!empty($el['mustSupport']) ? 'YES' : 'NO') . PHP_EOL;
    }
}

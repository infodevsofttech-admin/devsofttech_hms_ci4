<?php
$sd = json_decode(file_get_contents('ABDM_FHIR/package/package/StructureDefinition-ImmunizationRecord.json'), true);
echo "Name: " . $sd['name'] . "\n";
echo "Type: " . $sd['type'] . "\n";
echo "BaseDefinition: " . $sd['baseDefinition'] . "\n";

foreach ($sd['snapshot']['element'] as $el) {
    $id = $el['id'];
    $min = $el['min'] ?? 0;
    $max = $el['max'] ?? '*';
    // show if min > 0 or sliced or interesting
    if ($min > 0 || (isset($el['sliceName'])) || strpos($id, 'Composition.section') === 0 || in_array($id, ['Composition.category', 'Composition.encounter', 'Composition.custodian', 'Composition.author', 'Composition.title', 'Composition.type'])) {
        $type = isset($el['type']) ? implode(',', array_column($el['type'], 'code')) : '';
        echo sprintf("%-40s | %s..%s | %s\n", $id, $min, $max, $type);
    }
}

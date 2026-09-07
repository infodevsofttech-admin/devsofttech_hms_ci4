<?php

$sd = json_decode(file_get_contents('ABDM_FHIR/definitions.json/StructureDefinition-DischargeSummaryRecord.json'), true);

echo "=== ALL SECTION SLICES IN DISCHARGE SUMMARY RECORD ===\n";
foreach ($sd['snapshot']['element'] as $el) {
    if (str_starts_with($el['id'], 'Composition.section:')) {
        $parts = explode('.', $el['id']);
        if (count($parts) === 2) {
            $slice = $parts[1];
            echo "\nSlice: $slice (min: {$el['min']}, max: {$el['max']})\n";
            if (isset($el['code']['coding'])) {
                foreach ($el['code']['coding'] as $c) {
                    echo "  Code: {$c['code']} - {$c['display']} ({$c['system']})\n";
                }
            }
        } elseif (count($parts) === 3 && $parts[2] === 'entry') {
            echo "  Target profiles for entry:\n";
            if (isset($el['type'])) {
                foreach ($el['type'] as $t) {
                    if (isset($t['targetProfile'])) {
                        foreach ($t['targetProfile'] as $tp) {
                            echo "    -> " . basename($tp) . "\n";
                        }
                    }
                }
            }
        }
    }
}

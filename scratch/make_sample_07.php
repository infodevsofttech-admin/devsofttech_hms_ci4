<?php
$b = json_decode(file_get_contents('ABDM_FHIR/examples.json/Bundle-ImmunizationRecord-example-07.json'), true);
// Strip big PDF
foreach ($b['entry'] as &$entry) {
    if (($entry['resource']['resourceType'] ?? '') === 'DocumentReference') {
        if (isset($entry['resource']['content'][0]['attachment']['data'])) {
            $entry['resource']['content'][0]['attachment']['data'] = '[TRUNCATED]';
        }
    }
}
unset($entry);
file_put_contents('d:/Workplace/HMS_CI4_OLD/scratch/sample_07_full.json', json_encode($b, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Saved sample 07 full without pdf\n";

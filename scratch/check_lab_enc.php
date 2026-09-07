<?php
$d = json_decode(file_get_contents('writable/tmp/abdm_lab_bundle_real_sample.json'), true);
foreach ($d['entry'] as $e) {
    if ($e['resource']['resourceType'] === 'Encounter') {
        print_r($e['resource']);
    }
}

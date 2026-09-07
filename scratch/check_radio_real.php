<?php
$d = json_decode(file_get_contents('writable/tmp/abdm_radiology_bundle_real_sample.json'), true);
echo "Composition:\n";
print_r($d['entry'][0]['resource']);

<?php
require __DIR__ . '/../vendor/autoload.php';

$bundleJson = file_get_contents('d:/Workplace/HMS_CI4_OLD/writable/ipd9_bundle.json');
$bundle = json_decode($bundleJson, true);

$validator = new \App\Libraries\Abdm\Fhir\Support\FhirBundleValidator();
$result = $validator->validate($bundle);

echo "=== FHIR Bundle Validation Result ===\n";
echo "Valid: " . ($result->valid ? 'YES' : 'NO') . " | Score: " . $result->score . "%\n";
if (!empty($result->errors)) {
    echo "Errors:\n";
    foreach ($result->errors as $err) {
        echo " - " . (is_array($err) ? json_encode($err) : $err) . "\n";
    }
}
if (!empty($result->warnings)) {
    echo "Warnings:\n";
    foreach ($result->warnings as $w) {
        echo " - " . (is_array($w) ? json_encode($w) : $w) . "\n";
    }
}

// Check every section and reference in Composition
$comp = $bundle['entry'][0]['resource'];
echo "\nComposition Type: " . json_encode($comp['type']) . "\n";
echo "Composition Subject: " . json_encode($comp['subject']) . "\n";
echo "Composition Author: " . json_encode($comp['author']) . "\n";
echo "Composition Custodian: " . json_encode($comp['custodian']) . "\n";
echo "Composition Encounter: " . json_encode($comp['encounter']) . "\n";

echo "\nComposition Sections (" . count($comp['section'] ?? []) . "):\n";
$entryUrls = [];
foreach ($bundle['entry'] as $e) {
    $entryUrls[$e['fullUrl']] = $e['resource']['resourceType'] . ' (ID: ' . ($e['resource']['id'] ?? '') . ')';
}

$missingRefs = [];
foreach ($comp['section'] ?? [] as $s) {
    echo "Section: " . ($s['title'] ?? 'Untitled') . " | Code: " . ($s['code']['coding'][0]['code'] ?? 'None') . " | Entries: " . count($s['entry'] ?? []) . "\n";
    foreach ($s['entry'] ?? [] as $ent) {
        $ref = $ent['reference'] ?? '';
        if (!isset($entryUrls[$ref])) {
            $missingRefs[] = $ref;
            echo "   [MISSING REF] -> $ref\n";
        } else {
            echo "   [OK] -> $ref => " . $entryUrls[$ref] . "\n";
        }
    }
}

if (!empty($missingRefs)) {
    echo "\nCRITICAL: There are missing references in Composition sections!\n";
} else {
    echo "\nALL Composition section references exist in Bundle.entry!\n";
}

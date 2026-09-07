<?php
$b = json_decode(file_get_contents('writable/ipd9_bundle.json'), true);

$entryUrls = array_column($b['entry'], 'fullUrl');
echo "Checking " . count($entryUrls) . " entries in bundle...\n";

// Check all UUIDs
foreach ($entryUrls as $url) {
    if (!preg_match('/^urn:uuid:[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $url)) {
        echo "WARNING: non-standard uuid fullUrl: $url\n";
    }
}

// Check all references
$jsonStr = json_encode($b);
preg_match_all('/"reference":"(urn:uuid:[^"]+)"/', $jsonStr, $matches);
$allRefs = array_unique($matches[1]);
$missing = [];
foreach ($allRefs as $ref) {
    if (!in_array($ref, $entryUrls, true)) {
        $missing[] = $ref;
    }
}
if ($missing === []) {
    echo "SUCCESS: All " . count($allRefs) . " references resolve within bundle!\n";
} else {
    echo "ERROR: Missing references: " . implode(', ', $missing) . "\n";
}

// Check profiles
echo "Bundle profile: " . json_encode($b['meta']['profile']) . "\n";
echo "Composition profile: " . json_encode($b['entry'][0]['resource']['meta']['profile']) . "\n";
echo "Composition type: " . json_encode($b['entry'][0]['resource']['type']) . "\n";

// Check MedicationRequest dosage
foreach ($b['entry'] as $e) {
    $r = $e['resource'];
    if ($r['resourceType'] === 'MedicationRequest') {
        echo "Medication: " . $r['medicationCodeableConcept']['text'] . "\n";
        echo "  Dosage instruction: " . json_encode($r['dosageInstruction']) . "\n";
    }
}

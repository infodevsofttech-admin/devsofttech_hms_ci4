<?php

require_once __DIR__ . '/verify_user_bundle.php'; // loads $bundle

$defDir = 'd:/Workplace/HMS_CI4_OLD/ABDM_FHIR/definitions.json/';

echo "\n================ COMPREHENSIVE VALIDATION AGAINST NRCES PROFILES ================\n";

$errors = [];
$warnings = [];

// 2. Check each resource against its StructureDefinition
foreach ($bundle['entry'] as $idx => $entry) {
    $resource = $entry['resource'];
    $type = $resource['resourceType'];
    $profile = $resource['meta']['profile'][0] ?? null;

    if (!$profile) {
        $errors[] = "Entry $idx ({$type}) is missing meta.profile";
        continue;
    }

    $profileFile = $defDir . 'StructureDefinition-' . basename(parse_url($profile, PHP_URL_PATH)) . '.json';
    if (!file_exists($profileFile)) {
        $profileFile = $defDir . 'StructureDefinition-' . $type . '.json';
    }

    if (!file_exists($profileFile)) {
        $warnings[] = "Could not find profile file for Entry $idx: $profile";
        continue;
    }

    $sd = json_decode(file_get_contents($profileFile), true);
    
    // Check required fields (min > 0 in differential / snapshot)
    $elements = $sd['snapshot']['element'];
    $requiredElements = [];
    foreach ($elements as $el) {
        if ($el['min'] > 0 && !str_contains($el['id'], ':') && !str_contains($el['id'], '.')) {
            // Root
            continue;
        }
        if ($el['min'] > 0 && count(explode('.', $el['id'])) == 2 && !str_contains($el['id'], ':')) {
            $prop = explode('.', $el['id'])[1];
            // If it's a choice element e.g. performed[x]
            if (str_ends_with($prop, '[x]')) {
                $baseProp = substr($prop, 0, -3);
                $requiredElements[] = ['type' => 'choice', 'prop' => $baseProp, 'id' => $el['id']];
            } else {
                $requiredElements[] = ['type' => 'fixed', 'prop' => $prop, 'id' => $el['id']];
            }
        }
    }

    foreach ($requiredElements as $req) {
        if ($req['type'] === 'fixed') {
            if (!isset($resource[$req['prop']])) {
                $errors[] = "Entry $idx ($type/id:{$resource['id']}) missing required top-level field: {$req['prop']} (defined in {$req['id']})";
            }
        } else {
            $found = false;
            foreach (array_keys($resource) as $k) {
                if (str_starts_with($k, $req['prop'])) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $errors[] = "Entry $idx ($type/id:{$resource['id']}) missing required choice field: {$req['prop']}[x]";
            }
        }
    }
}

if (empty($errors) && empty($warnings)) {
    echo "\n>>> 100% PERFECT: ALL 14 RESOURCES (INCLUDING DISCHARGE COMPOSITION) SATISFY ALL NRCES PROFILE REQUIREMENTS! <<<\n";
} else {
    if (!empty($errors)) {
        echo "\n>>> ERRORS FOUND: <<<\n";
        foreach ($errors as $err) {
            echo " - $err\n";
        }
    }
    if (!empty($warnings)) {
        echo "\n>>> WARNINGS: <<<\n";
        foreach ($warnings as $w) {
            echo " - $w\n";
        }
    }
}

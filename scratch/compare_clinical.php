<?php
$f1 = json_decode(file_get_contents('d:/Workplace/HMS_CI4_OLD/scratch/fhir_1.json'), true);
$our = json_decode(file_get_contents('d:/Workplace/HMS_CI4_OLD/writable/ipd9_bundle.json'), true);

echo "=== Comprehensive Resource Inspection ===\n";

// 1. Check Condition
echo "\n--- Condition [FHIR_1] ---\n";
print_r($f1['entry'][5]['resource']);

echo "\n--- Condition [our_FHIR] ---\n";
print_r($our['entry'][4]['resource']);

// 2. Check Procedure
echo "\n--- Procedure [FHIR_1] ---\n";
print_r($f1['entry'][8]['resource']);

echo "\n--- Procedure [our_FHIR] ---\n";
print_r($our['entry'][10]['resource']);

// 3. Check MedicationRequest
echo "\n--- MedicationRequest [FHIR_1] ---\n";
print_r($f1['entry'][9]['resource']);

echo "\n--- MedicationRequest [our_FHIR] ---\n";
print_r($our['entry'][11]['resource']);

// 4. Check CarePlan
echo "\n--- CarePlan [FHIR_1] ---\n";
print_r($f1['entry'][12]['resource']);

echo "\n--- CarePlan [our_FHIR] ---\n";
print_r($our['entry'][17]['resource']);

<?php

namespace App\Libraries\Abdm\Fhir\Generators;

class DischargeFhirGenerator extends \App\Libraries\Abdm\Fhir\Generators\AbstractModuleFhirGenerator
{
    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public function generate(array $source): array
    {
        $timestamp = (string) ($source['completed_at'] ?? date(DATE_ATOM));
        $recordId = (string) ($source['record_id'] ?? '0');
        $patientId = (string) ($source['patient']['id'] ?? '0');
        $sessionId = (string) ($source['session_id'] ?? '0');
        $visitDate = (string) ($source['visit_date'] ?? date('Y-m-d'));

        $careContextReference = 'DISCHARGE-' . $recordId . '-S' . $sessionId . '-' . $visitDate;
        $careContextDisplay = 'Discharge Summary ' . $visitDate;

        $builder = new \App\Libraries\Abdm\Fhir\FhirDocumentBuilder();
        $builder
            ->buildBundleMeta('discharge-' . $recordId . '-' . strtotime($timestamp), $timestamp)
            ->buildComposition($this->buildBaseComposition($source, 'Discharge Summary', '18842-5', 'Discharge summary'))
            ->addPatient($this->buildBasePatient($source));

        $encounter = $this->buildEncounter($source);
        if (is_array($encounter)) {
            $builder->addEncounter($encounter);
        }

        $patientRef = 'urn:uuid:patient-' . $patientId;
        $encounterRef = is_array($encounter) ? 'urn:uuid:' . (string) ($encounter['id'] ?? '') : null;

        foreach ((array) ($source['conditions'] ?? []) as $idx => $cond) {
            $text = trim((string) ($cond['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $resolved = $this->codingResolver->resolveSnomedForDiagnosisOrFinding((string) ($cond['code'] ?? ''), $text);
            $builder->addCondition([
                'resourceType' => 'Condition',
                'id' => 'discharge-condition-' . $recordId . '-' . $idx,
                'code' => [
                    'coding' => $resolved['coding'] ?? [],
                    'text' => $text,
                ],
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
            ]);
        }

        foreach ((array) ($source['procedures'] ?? []) as $idx => $proc) {
            $text = trim((string) ($proc['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $resolved = $this->codingResolver->resolveSnomedForDiagnosisOrFinding((string) ($proc['code'] ?? ''), $text);
            $builder->addProcedure([
                'resourceType' => 'Procedure',
                'id' => 'discharge-procedure-' . $recordId . '-' . $idx,
                'status' => 'completed',
                'code' => [
                    'coding' => $resolved['coding'] ?? [],
                    'text' => $text,
                ],
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'performedDateTime' => (string) ($proc['performed_at'] ?? $timestamp),
            ]);
        }

        foreach ((array) ($source['medications'] ?? []) as $idx => $med) {
            $name = trim((string) ($med['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $builder->addMedicationRequest([
                'resourceType' => 'MedicationRequest',
                'id' => 'discharge-medication-' . $recordId . '-' . $idx,
                'status' => 'active',
                'intent' => 'order',
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'medicationCodeableConcept' => ['text' => $name],
                'dosageInstruction' => [[
                    'text' => trim((string) ($med['dosage'] ?? '')),
                ]],
            ]);
        }

        foreach ((array) ($source['observations'] ?? []) as $idx => $obs) {
            $text = trim((string) ($obs['text'] ?? ''));
            $value = trim((string) ($obs['value'] ?? ''));
            if ($text === '' || $value === '') {
                continue;
            }

            $coding = [];
            $loincCode = trim((string) ($obs['loinc_code'] ?? ''));
            if ($loincCode !== '') {
                $coding[] = [
                    'system' => 'http://loinc.org',
                    'code' => $loincCode,
                    'display' => trim((string) ($obs['loinc_display'] ?? '')),
                ];
            }

            $builder->addObservation([
                'resourceType' => 'Observation',
                'id' => 'discharge-observation-' . $recordId . '-' . $idx,
                'status' => 'final',
                'category' => [[
                    'text' => trim((string) ($obs['category'] ?? 'vital-signs')),
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code' => trim((string) ($obs['category_code'] ?? 'vital-signs')),
                    ]],
                ]],
                'code' => [
                    'coding' => $coding,
                    'text' => $text,
                ],
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'effectiveDateTime' => (string) ($obs['effective_at'] ?? $timestamp),
                'valueString' => $value,
            ]);
        }

        foreach ((array) ($source['investigations'] ?? []) as $idx => $inv) {
            $text = trim((string) ($inv['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $coding = [];
            $loincCode = trim((string) ($inv['loinc_code'] ?? ''));
            if ($loincCode !== '') {
                $coding[] = [
                    'system' => 'http://loinc.org',
                    'code' => $loincCode,
                    'display' => trim((string) ($inv['loinc_display'] ?? '')),
                ];
            }

            $builder->addServiceRequest([
                'resourceType' => 'ServiceRequest',
                'id' => 'discharge-investigation-' . $recordId . '-' . $idx,
                'status' => 'active',
                'intent' => 'order',
                'code' => [
                    'coding' => $coding,
                    'text' => $text,
                ],
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'authoredOn' => (string) ($inv['authored_on'] ?? $timestamp),
            ]);
        }

        foreach ((array) ($source['allergies'] ?? []) as $idx => $alg) {
            $text = trim((string) ($alg['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $builder->addAllergyIntolerance([
                'resourceType' => 'AllergyIntolerance',
                'id' => 'discharge-allergy-' . $recordId . '-' . $idx,
                'clinicalStatus' => [
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical',
                        'code' => 'active',
                    ]],
                ],
                'verificationStatus' => [
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-verification',
                        'code' => 'unconfirmed',
                    ]],
                ],
                'type' => 'allergy',
                'category' => ['medication'],
                'criticality' => trim((string) ($alg['criticality'] ?? 'low')),
                'code' => [
                    'text' => $text,
                ],
                'patient' => ['reference' => $patientRef],
                'recordedDate' => (string) ($alg['recorded_at'] ?? $timestamp),
                'note' => isset($alg['note']) && trim((string) $alg['note']) !== ''
                    ? [['text' => trim((string) $alg['note'])]]
                    : null,
            ]);
        }

        foreach ((array) ($source['care_plans'] ?? []) as $idx => $cp) {
            $title = trim((string) ($cp['title'] ?? 'Discharge Advice'));
            $description = trim((string) ($cp['description'] ?? ''));
            if ($description === '') {
                continue;
            }

            $builder->addCarePlan([
                'resourceType' => 'CarePlan',
                'id' => 'discharge-careplan-' . $recordId . '-' . $idx,
                'status' => 'active',
                'intent' => 'plan',
                'title' => $title,
                'description' => $description,
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'created' => (string) ($cp['created_at'] ?? $timestamp),
            ]);
        }

        $bundle = $builder->toBundle();
        $validation = $this->validator->validate($bundle, 'discharge', ['resolved' => 1, 'unresolved' => 0, 'fallback_used' => 0]);

        return [
            'hi_type' => 'DischargeSummaryRecord',
            'care_context_reference' => $careContextReference,
            'care_context_display' => $careContextDisplay,
            'fhir_bundle' => $bundle,
            'validation' => $validation->toArray(),
        ];
    }
}

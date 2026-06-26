<?php

namespace App\Libraries\Abdm\Fhir\Generators;

class OpdFhirGenerator extends \App\Libraries\Abdm\Fhir\Generators\AbstractModuleFhirGenerator
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

        $careContextReference = 'OPD-' . $recordId . '-S' . $sessionId . '-' . $visitDate;
        $careContextDisplay = 'OPD Visit ' . $visitDate;

        $builder = new \App\Libraries\Abdm\Fhir\FhirDocumentBuilder();
        $builder
            ->buildBundleMeta('opd-' . $recordId . '-' . strtotime($timestamp), $timestamp)
            ->buildComposition($this->buildBaseComposition($source, 'OPD Consultation Summary', '11506-3', 'Progress note'))
            ->addPatient($this->buildBasePatient($source));

        $encounter = $this->buildEncounter($source);
        if (is_array($encounter)) {
            $builder->addEncounter($encounter);
        }

        $practitioner = $this->buildPractitioner($source);
        if (is_array($practitioner)) {
            $builder->addPractitioner($practitioner);
        }

        $organization = $this->buildOrganization($source);
        if (is_array($organization)) {
            $builder->addOrganization($organization);
        }

        $patientRef = 'urn:uuid:patient-' . $patientId;
        $encounterRef = is_array($encounter) ? 'urn:uuid:' . (string) ($encounter['id'] ?? '') : null;

        foreach ((array) ($source['diagnoses'] ?? []) as $idx => $diag) {
            $text = trim((string) ($diag['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $resolution = $this->codingResolver->resolveSnomedForDiagnosisOrFinding((string) ($diag['code'] ?? ''), $text);
            $condition = [
                'resourceType' => 'Condition',
                'id' => 'condition-' . $recordId . '-' . $idx,
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'code' => [
                    'coding' => $resolution['coding'] ?? [],
                    'text' => $text,
                ],
                'clinicalStatus' => ['coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                    'code' => 'active',
                ]]],
            ];
            $builder->addCondition($condition);
        }

        foreach ((array) ($source['medications'] ?? []) as $idx => $med) {
            $drugName = trim((string) ($med['name'] ?? ''));
            if ($drugName === '') {
                continue;
            }

            $builder->addMedicationRequest([
                'resourceType' => 'MedicationRequest',
                'id' => 'medication-' . $recordId . '-' . $idx,
                'status' => 'active',
                'intent' => 'order',
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'medicationCodeableConcept' => [
                    'text' => $drugName,
                ],
                'dosageInstruction' => [[
                    'text' => trim((string) ($med['dosage'] ?? '')),
                ]],
            ]);
        }

        foreach ((array) ($source['vitals'] ?? []) as $idx => $vital) {
            $display = trim((string) ($vital['display'] ?? ''));
            if ($display === '') {
                continue;
            }

            $loinc = $this->codingResolver->resolveLoincForLabTest((string) ($vital['code'] ?? ''), $display);
            $ucum = $this->codingResolver->resolveUnitUcUM((string) ($vital['unit'] ?? ''));
            $builder->addObservation([
                'resourceType' => 'Observation',
                'id' => 'observation-' . $recordId . '-' . $idx,
                'status' => 'final',
                'category' => [[
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code' => 'vital-signs',
                    ]],
                ]],
                'code' => [
                    'coding' => $loinc['coding'] ?? [],
                    'text' => $display,
                ],
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'effectiveDateTime' => $timestamp,
                'valueQuantity' => [
                    'value' => (float) ($vital['value'] ?? 0),
                    'unit' => (string) ($vital['unit'] ?? ''),
                    'system' => 'http://unitsofmeasure.org',
                    'code' => (string) ($ucum['code'] ?? ''),
                ],
            ]);
        }

        $bundle = $builder->toBundle();
        $validation = $this->validator->validate($bundle, 'opd', ['resolved' => 1, 'unresolved' => 0, 'fallback_used' => 0]);

        return [
            'hi_type' => 'OPConsultRecord',
            'care_context_reference' => $careContextReference,
            'care_context_display' => $careContextDisplay,
            'fhir_bundle' => $bundle,
            'validation' => $validation->toArray(),
        ];
    }
}

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
                'recordedDate' => $timestamp,
            ];
            $builder->addCondition($condition);
        }

        foreach ((array) ($source['medications'] ?? []) as $idx => $med) {
            $drugName = trim((string) ($med['name'] ?? ''));
            if (! $this->isMeaningfulValue($drugName)) {
                continue;
            }

            $dosageText = trim((string) ($med['dosage'] ?? ''));
            $medType = trim((string) ($med['formulation'] ?? $med['med_type'] ?? ''));
            $routeText = trim((string) ($med['route_text'] ?? ''));
            if (! $this->isMeaningfulValue($routeText)) {
                $medTypeUpper = strtoupper($medType);
                if (in_array($medTypeUpper, ['INJ', 'INJECTION', 'IV', 'IM'], true)) {
                    $routeText = 'Injection';
                    $routeCode = '47625008';
                } elseif (in_array($medTypeUpper, ['CREAM', 'OINT', 'OINTMENT', 'GEL', 'LOTION'], true)) {
                    $routeText = 'Topical';
                    $routeCode = '6064005';
                } else {
                    $routeText = 'Oral';
                    $routeCode = '260548002';
                }
            } else {
                $routeCode = '260548002';
            }

            $dosageInstruction = [[
                'text' => $dosageText,
                'route' => [
                    'coding' => [[
                        'system' => 'http://snomed.info/sct',
                        'code' => $routeCode,
                        'display' => $routeText,
                    ]],
                    'text' => $routeText,
                ],
            ]];

            $code = trim((string) ($med['code'] ?? ''));
            $coding = [];
            if ($code !== '') {
                $coding[] = [
                    'system' => 'http://snomed.info/sct',
                    'code' => $code,
                    'display' => $drugName,
                ];
            } else {
                $coding[] = [
                    'system' => 'http://snomed.info/sct',
                    'code' => '105904009',
                    'display' => $drugName,
                ];
            }

            $medResource = [
                'resourceType' => 'MedicationRequest',
                'id' => 'medication-' . $recordId . '-' . $idx,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/MedicationRequest']],
                'status' => 'active',
                'intent' => 'order',
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'medicationCodeableConcept' => [
                    'coding' => $coding,
                    'text' => $drugName,
                ],
                'dosageInstruction' => $dosageInstruction,
            ];

            $builder->addMedicationRequest($medResource);
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

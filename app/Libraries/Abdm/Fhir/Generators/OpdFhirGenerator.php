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
            ->addPatient($this->buildBasePatient($source));

        $patientRef = 'urn:uuid:patient-' . $patientId;

        $encounter = $this->buildEncounter($source);
        if (! is_array($encounter)) {
            $encounter = [
                'resourceType' => 'Encounter',
                'id' => 'encounter-' . $recordId,
                'status' => 'finished',
                'class' => [
                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                    'code' => 'AMB',
                    'display' => 'ambulatory',
                ],
                'subject' => ['reference' => $patientRef],
            ];
        }
        $builder->addEncounter($encounter);
        $encounterRef = 'urn:uuid:' . (string) $encounter['id'];

        $practitioner = $this->buildPractitioner($source);
        if (! is_array($practitioner)) {
            $fallbackDoctorName = trim((string) ($source['doctor_name'] ?? 'Dr. Attending Medical Officer'));
            $docId = (string) ($source['practitioner']['id'] ?? $source['doctor']['id'] ?? '1');
            $practitioner = [
                'resourceType' => 'Practitioner',
                'id' => 'practitioner-' . ($docId !== '' && $docId !== '0' ? $docId : '1'),
                'name' => [[
                    'text' => $fallbackDoctorName !== '' ? $fallbackDoctorName : 'Dr. Attending Medical Officer',
                ]],
            ];
        }
        $builder->addPractitioner($practitioner);

        $organization = $this->buildOrganization($source);
        if (! is_array($organization)) {
            $orgId = (string) ($source['organization']['id'] ?? $source['hfr_id'] ?? 'IN0510000871');
            $orgName = (string) ($source['organization']['name'] ?? 'Hospital');
            $organization = [
                'resourceType' => 'Organization',
                'id' => 'organization-' . ($orgId !== '' ? $orgId : 'IN0510000871'),
                'name' => $orgName,
            ];
        }
        $builder->addOrganization($organization);

        $conditionRefs = [];
        foreach ((array) ($source['diagnoses'] ?? []) as $idx => $diag) {
            $text = trim((string) ($diag['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $condId = 'condition-' . $recordId . '-' . $idx;
            $conditionRefs[] = ['reference' => 'urn:uuid:' . $condId];

            $resolution = $this->codingResolver->resolveSnomedForDiagnosisOrFinding((string) ($diag['code'] ?? ''), $text);
            $condition = [
                'resourceType' => 'Condition',
                'id' => $condId,
                'subject' => ['reference' => $patientRef],
                'encounter' => ['reference' => $encounterRef],
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

        $medicationRefs = [];
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

            $medId = 'medication-' . $recordId . '-' . $idx;
            $medicationRefs[] = ['reference' => 'urn:uuid:' . $medId];

            $medResource = [
                'resourceType' => 'MedicationRequest',
                'id' => $medId,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/MedicationRequest']],
                'status' => 'active',
                'intent' => 'order',
                'subject' => ['reference' => $patientRef],
                'encounter' => ['reference' => $encounterRef],
                'medicationCodeableConcept' => [
                    'coding' => $coding,
                    'text' => $drugName,
                ],
                'dosageInstruction' => $dosageInstruction,
            ];

            $builder->addMedicationRequest($medResource);
        }

        $observationRefs = [];
        foreach ((array) ($source['vitals'] ?? []) as $idx => $vital) {
            $display = trim((string) ($vital['display'] ?? ''));
            if ($display === '') {
                continue;
            }

            $obsId = 'observation-' . $recordId . '-' . $idx;
            $observationRefs[] = ['reference' => 'urn:uuid:' . $obsId];

            $loinc = $this->codingResolver->resolveLoincForLabTest((string) ($vital['code'] ?? ''), $display);
            $ucum = $this->codingResolver->resolveUnitUcUM((string) ($vital['unit'] ?? ''));
            $builder->addObservation([
                'resourceType' => 'Observation',
                'id' => $obsId,
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
                'encounter' => ['reference' => $encounterRef],
                'effectiveDateTime' => $timestamp,
                'valueQuantity' => [
                    'value' => (float) ($vital['value'] ?? 0),
                    'unit' => (string) ($vital['unit'] ?? ''),
                    'system' => 'http://unitsofmeasure.org',
                    'code' => (string) ($ucum['code'] ?? ''),
                ],
            ]);
        }

        $sections = [];
        if (! empty($conditionRefs)) {
            $sections[] = [
                'title' => 'Chief complaints',
                'code' => [
                    'coding' => [[
                        'system' => 'http://snomed.info/sct',
                        'code' => '422843007',
                        'display' => 'Chief complaint section',
                    ]],
                ],
                'entry' => $conditionRefs,
            ];
        }

        if (! empty($medicationRefs)) {
            $sections[] = [
                'title' => 'Medications',
                'code' => [
                    'coding' => [[
                        'system' => 'http://snomed.info/sct',
                        'code' => '721912009',
                        'display' => 'Medication summary document',
                    ]],
                ],
                'entry' => $medicationRefs,
            ];
        }

        if (! empty($observationRefs)) {
            $sections[] = [
                'title' => 'Physical Examination',
                'code' => [
                    'coding' => [[
                        'system' => 'http://snomed.info/sct',
                        'code' => '425044008',
                        'display' => 'Physical examination section',
                    ]],
                ],
                'entry' => $observationRefs,
            ];
        }

        if (empty($sections)) {
            $sections[] = [
                'title' => 'Clinical Consultation',
                'code' => [
                    'coding' => [[
                        'system' => 'http://snomed.info/sct',
                        'code' => '371530004',
                        'display' => 'Clinical consultation report',
                    ]],
                ],
                'entry' => [
                    ['reference' => $encounterRef],
                ],
            ];
        }

        $authorRef = 'urn:uuid:' . (string) $practitioner['id'];
        $authorDisplay = (string) ($practitioner['name'][0]['text'] ?? 'Dr. Attending Medical Officer');
        $custodianRef = 'urn:uuid:' . (string) $organization['id'];

        $composition = [
            'resourceType' => 'Composition',
            'id' => 'composition-' . $recordId,
            'meta' => [
                'versionId' => '1',
                'lastUpdated' => $timestamp,
                'profile' => [
                    'https://nrces.in/ndhm/fhir/r4/StructureDefinition/OPConsultRecord',
                ],
            ],
            'status' => 'final',
            'type' => [
                'coding' => [[
                    'system' => 'http://snomed.info/sct',
                    'code' => '371530004',
                    'display' => 'Clinical consultation report',
                ]],
                'text' => 'Clinical Consultation Record',
            ],
            'title' => 'OP Consultation Report',
            'date' => $timestamp,
            'subject' => [
                'reference' => $patientRef,
                'display' => (string) ($source['patient']['name'] ?? 'Patient'),
            ],
            'encounter' => [
                'reference' => $encounterRef,
            ],
            'author' => [[
                'reference' => $authorRef,
                'display' => $authorDisplay,
            ]],
            'custodian' => [
                'reference' => $custodianRef,
                'display' => (string) ($organization['name'] ?? 'Hospital'),
            ],
            'section' => $sections,
        ];

        $builder->buildComposition($composition);

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

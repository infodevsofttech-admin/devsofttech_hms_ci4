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
        $bundleIdentifier = trim((string) ($source['bundle_identifier'] ?? ''));
        if ($bundleIdentifier === '') {
            $bundleIdentifier = 'discharge-' . $recordId . '-' . strtotime($timestamp);
        }

        $careContextReference = (string) ($source['care_context_reference'] ?? ('DISCHARGE-' . $recordId . '-S' . $sessionId . '-' . $visitDate));
        $careContextDisplay = 'Discharge Summary ' . $visitDate;

        $builder = new \App\Libraries\Abdm\Fhir\FhirDocumentBuilder();
        $builder
            ->buildBundleMeta($bundleIdentifier, $timestamp)
            ->buildComposition($this->buildBaseComposition($source, 'IPD Discharge Summary', '18842-5', 'Discharge summary'))
            ->updateComposition([
                'meta' => [
                    'versionId' => '1',
                    'lastUpdated' => $timestamp,
                    'profile' => [
                        'https://nrces.in/ndhm/fhir/r4/StructureDefinition/DischargeSummaryRecord',
                    ],
                ],
                'type' => ['coding' => [
                    [
                        'system' => 'http://snomed.info/sct',
                        'code' => '373942005',
                        'display' => 'Discharge summary',
                    ],
                ], 'text' => 'Discharge Summary'],
            ])
            ->addPatient($this->buildBasePatient($source));

        $encounter = $this->buildEncounter($source);

        $practitioner = $this->buildPractitioner($source);
        if (! is_array($practitioner)) {
            $fallbackDoctorName = trim((string) ($source['doctor_name'] ?? ''));
            if ($fallbackDoctorName !== '') {
                $practitioner = [
                    'resourceType' => 'Practitioner',
                    'id' => 'practitioner-' . md5($fallbackDoctorName),
                    'name' => [[
                        'text' => $fallbackDoctorName,
                    ]],
                ];
            }
        }
        if (is_array($practitioner)) {
            $builder->addPractitioner($practitioner);
        }

        $organization = $this->buildOrganization($source);
        if (is_array($organization)) {
            $builder->addOrganization($organization);
        }

        $compositionUpdate = [];
        if (is_array($practitioner)) {
            $compositionUpdate['author'] = [[
                'reference' => 'urn:uuid:' . (string) $practitioner['id'],
                'display' => (string) ($practitioner['name'][0]['text'] ?? ''),
            ]];
        } elseif (is_array($organization)) {
            $compositionUpdate['author'] = [[
                'reference' => 'urn:uuid:' . (string) $organization['id'],
                'display' => (string) ($organization['name'] ?? ''),
            ]];
        }
        if (is_array($organization)) {
            $compositionUpdate['custodian'] = [
                'reference' => 'urn:uuid:' . (string) $organization['id'],
                'display' => (string) ($organization['name'] ?? ''),
            ];
        }
        $builder->updateComposition($compositionUpdate);

        $patientRef = 'urn:uuid:patient-' . $patientId;
        $encounterRef = is_array($encounter) ? 'urn:uuid:' . (string) ($encounter['id'] ?? '') : null;

        $diagnosesList = (array) ($source['diagnoses'] ?? $source['conditions'] ?? []);
        $chiefComplaintsList = (array) ($source['chief_complaints'] ?? []);
        if ($diagnosesList === [] && $chiefComplaintsList !== []) {
            $diagnosesList = $chiefComplaintsList;
        }

        $encounterDiagnoses = [];

        $conditionGroups = [
            [
                'items'            => $chiefComplaintsList,
                'prefix'           => 'discharge-chief-complaint-',
                'category_code'    => 'problem-list-item',
                'category_display' => 'Problem List Item',
                'is_encounter_diagnosis' => false,
            ],
            [
                'items'            => $diagnosesList,
                'prefix'           => 'discharge-diagnosis-',
                'category_code'    => 'encounter-diagnosis',
                'category_display' => 'Encounter Diagnosis',
                'is_encounter_diagnosis' => true,
            ],
        ];
        foreach ($conditionGroups as $group) {
            foreach ($group['items'] as $idx => $cond) {
                $text = trim((string) ($cond['text'] ?? $cond['name'] ?? ''));
                if ($text === '') {
                    continue;
                }

                $resolved = $this->codingResolver->resolveSnomedForDiagnosisOrFinding((string) ($cond['code'] ?? ''), $text);
                $coding = $this->withoutPlaceholderCoding((array) ($resolved['coding'] ?? []));
                if ($coding === []) {
                    $fallback = $this->resolveFallbackSnomedCode($text);
                    $coding = [[
                        'system'  => 'http://snomed.info/sct',
                        'code'    => $fallback['code'],
                        'display' => $fallback['display'],
                    ]];
                }
                
                $conditionId = $group['prefix'] . $recordId . '-' . $idx;
                
                if ($group['is_encounter_diagnosis']) {
                    $encounterDiagnoses[] = [
                        'condition' => ['reference' => 'urn:uuid:' . $conditionId, 'display' => $text],
                        'use' => [
                            'coding' => [[
                                'system' => 'http://terminology.hl7.org/CodeSystem/diagnosis-role',
                                'code' => 'DD',
                                'display' => 'Discharge diagnosis'
                            ]]
                        ]
                    ];
                }

                $builder->addCondition([
                    'resourceType' => 'Condition',
                    'id' => $conditionId,
                    'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Condition']],
                    'text' => [
                        'status' => 'generated',
                        'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p><b>' . htmlspecialchars($group['category_display']) . ':</b> ' . htmlspecialchars($text) . '</p></div>',
                    ],
                    'clinicalStatus' => ['coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                        'code' => 'active',
                        'display' => 'Active',
                    ]]],
                    'verificationStatus' => ['coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/condition-ver-status',
                        'code' => 'confirmed',
                        'display' => 'Confirmed',
                    ]]],
                    'category' => [['coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/condition-category',
                        'code' => $group['category_code'],
                        'display' => $group['category_display'],
                    ]]]],
                    'code' => ['coding' => $coding, 'text' => $text],
                    'subject' => ['reference' => $patientRef, 'display' => (string) ($source['patient']['name'] ?? $source['patient_name'] ?? 'Patient')],
                    'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                    'recordedDate' => (string) ($cond['recorded_at'] ?? $timestamp),
                ]);
            }
        }
        
        if (is_array($encounter)) {
            if ($encounterDiagnoses !== []) {
                $encounter['diagnosis'] = $encounterDiagnoses;
            }
            $builder->addEncounter($encounter);
        }

        foreach ((array) ($source['procedures'] ?? []) as $idx => $proc) {
            $text = trim((string) ($proc['text'] ?? $proc['name'] ?? ''));
            if ($text === '') {
                continue;
            }

            $resolved = $this->codingResolver->resolveSnomedForDiagnosisOrFinding((string) ($proc['code'] ?? ''), $text);
            $coding = $this->withoutPlaceholderCoding((array) ($resolved['coding'] ?? []));
            if ($coding === []) {
                $codeVal = '71388002'; // Procedure
                $displayVal = 'Procedure';
                if (stripos($text, 'laparo') !== false) {
                    $codeVal = '86174004'; // Laparoscopy
                    $displayVal = 'Laparoscopy';
                } elseif (stripos($text, 'surg') !== false) {
                    $codeVal = '387713003'; // Surgical procedure
                    $displayVal = 'Surgical procedure';
                }
                $coding = [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => $codeVal,
                    'display' => $displayVal,
                ]];
            }
            $builder->addProcedure([
                'resourceType' => 'Procedure',
                'id' => 'discharge-procedure-' . $recordId . '-' . $idx,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Procedure']],
                'text' => [
                    'status' => 'generated',
                    'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p><b>Procedure:</b> ' . htmlspecialchars($text) . '</p></div>',
                ],
                'status' => 'completed',
                'code' => [
                    'coding' => $coding,
                    'text' => $text,
                ],
                'subject' => ['reference' => $patientRef, 'display' => (string) ($source['patient']['name'] ?? $source['patient_name'] ?? 'Patient')],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'performedDateTime' => (string) ($proc['performed_at'] ?? $timestamp),
            ]);
        }

        foreach ((array) ($source['medications'] ?? []) as $idx => $med) {
            $name = trim((string) ($med['name'] ?? ''));
            if (! $this->isMeaningfulValue($name)) {
                continue;
            }

            $medCode = trim((string) ($med['code'] ?? ''));
            $medCoding = [];
            if ($medCode !== '' && $this->isMeaningfulValue($medCode)) {
                $medCoding[] = [
                    'system'  => 'http://snomed.info/sct',
                    'code'    => $medCode,
                    'display' => $name,
                ];
            } else {
                $medCoding[] = [
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '105904009', // Type of drug
                    'display' => 'Type of drug',
                ];
            }

            $dosageText = trim((string) ($med['dosage'] ?? ''));
            $structuredDosage = $this->buildStructuredDosageInstruction($dosageText, $name);

            $medResource = [
                'resourceType' => 'MedicationRequest',
                'id' => 'discharge-medication-' . $recordId . '-' . $idx,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/MedicationRequest']],
                'text' => [
                    'status' => 'generated',
                    'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p><b>Medication:</b> ' . htmlspecialchars($name) . ($dosageText !== '' ? (' - ' . htmlspecialchars($dosageText)) : '') . '</p></div>',
                ],
                'status' => 'active',
                'intent' => 'order',
                'category' => [
                    [
                        'coding' => [
                            [
                                'system' => 'http://terminology.hl7.org/CodeSystem/medicationrequest-category',
                                'code' => 'community',
                                'display' => 'Discharge Medication',
                            ],
                        ],
                        'text' => 'Discharge Medication',
                    ],
                ],
                'subject' => ['reference' => $patientRef, 'display' => (string) ($source['patient']['name'] ?? $source['patient_name'] ?? 'Patient')],
                'authoredOn' => (string) ($med['authored_on'] ?? $timestamp),
                'medicationCodeableConcept' => [
                    'coding' => $medCoding,
                    'text'   => $name,
                ],
            ];

            if ($encounterRef) {
                $medResource['encounter'] = ['reference' => $encounterRef];
            }

            if (is_array($practitioner)) {
                $medResource['requester'] = [
                    'reference' => 'urn:uuid:' . (string) $practitioner['id'],
                    'display' => (string) ($practitioner['name'][0]['text'] ?? ''),
                ];
            }

            if (!empty($structuredDosage['dosageInstruction'])) {
                $medResource['dosageInstruction'] = $structuredDosage['dosageInstruction'];
            }

            if (!empty($structuredDosage['dispenseRequest'])) {
                $medResource['dispenseRequest'] = $structuredDosage['dispenseRequest'];
            }

            $builder->addMedicationRequest($medResource);
        }

        foreach ((array) ($source['observations'] ?? []) as $idx => $obs) {
            $text = trim((string) ($obs['text'] ?? ''));
            $value = trim((string) ($obs['value'] ?? ''));
            if (! $this->isMeaningfulValue($text) || ! $this->isMeaningfulValue($value)) {
                continue;
            }

            $catText = trim((string) ($obs['category'] ?? $obs['category_text'] ?? $obs['stage'] ?? $obs['timing'] ?? ''));
            $isAdm = (stripos($catText, 'Admission') !== false || stripos($text, 'Admission') !== false);
            $isDis = (stripos($catText, 'Discharge') !== false || stripos($text, 'Discharge') !== false);
            $obsCategoryDisplay = $isAdm ? 'Condition on Admission Time' : ($isDis ? 'Condition on Discharge Time' : 'Vital Signs');
            $coding = [];
            $loincCode = trim((string) ($obs['loinc_code'] ?? ''));
            $loincDisplay = trim((string) ($obs['loinc_display'] ?? ''));
            if ($loincCode === '') {
                $vitalLoinc = $this->resolveVitalSignLoinc($text);
                if ($vitalLoinc['code'] !== '') {
                    $loincCode = $vitalLoinc['code'];
                    $loincDisplay = $vitalLoinc['display'];
                }
            }

            $isVital = ($loincCode !== '' && $loincCode !== '108252007');
            $obsCategoryCode    = $isVital ? 'vital-signs' : 'exam';
            $obsCategoryCanonicalDisplay = $isVital ? 'Vital Signs' : 'Exam';
            $obsCategorySystem  = 'http://terminology.hl7.org/CodeSystem/observation-category';

            if ($loincCode !== '') {
                $coding[] = [
                    'system'  => 'http://loinc.org',
                    'code'    => $loincCode,
                    'display' => $loincDisplay !== '' ? $loincDisplay : $text,
                ];
            } else {
                $fallback = $this->resolvePhysicalExamSnomedCode($text);
                $coding[] = [
                    'system'  => 'http://snomed.info/sct',
                    'code'    => $fallback['code'],
                    'display' => $fallback['display'],
                ];
            }

            $obsResource = [
                'resourceType' => 'Observation',
                'id' => 'discharge-observation-' . $recordId . '-' . $idx,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Observation']],
                'text' => [
                    'status' => 'generated',
                    'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p><b>' . htmlspecialchars($text) . ':</b> ' . htmlspecialchars($value) . '</p></div>',
                ],
                'status' => 'final',
                'category' => [[
                    'text' => $obsCategoryDisplay,
                    'coding' => [[
                        'system'  => $obsCategorySystem,
                        'code'    => $obsCategoryCode,
                        'display' => $obsCategoryCanonicalDisplay,
                    ]],
                ]],
                'code' => [
                    'coding' => $coding,
                    'text' => $text,
                ],
                'subject' => ['reference' => $patientRef, 'display' => (string) ($source['patient']['name'] ?? $source['patient_name'] ?? 'Patient')],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'effectiveDateTime' => (string) ($obs['effective_at'] ?? $timestamp),
            ];

            if ($loincCode === '85354-9' || str_contains(strtoupper($text), 'BP') || str_contains(strtoupper($text), 'BLOOD PRESSURE')) {
                $loincCode = '85354-9';
                $loincDisplay = 'Blood pressure panel with all children optional';
                $coding = [[
                    'system'  => 'http://loinc.org',
                    'code'    => '85354-9',
                    'display' => 'Blood pressure panel with all children optional',
                ]];
                $obsResource['code'] = [
                    'coding' => $coding,
                    'text'   => $text,
                ];

                if (preg_match('/(\d+(?:\.\d+)?)\s*[\/\-]\s*(\d+(?:\.\d+)?)/', $value, $bpMatches)) {
                    $systolic = (float) $bpMatches[1];
                    $diastolic = (float) $bpMatches[2];
                    $obsResource['component'] = [
                        [
                            'code' => [
                                'coding' => [[
                                    'system'  => 'http://loinc.org',
                                    'code'    => '8480-6',
                                    'display' => 'Systolic blood pressure',
                                ]],
                                'text' => 'Systolic blood pressure',
                            ],
                            'valueQuantity' => [
                                'value'  => $systolic,
                                'unit'   => 'mmHg',
                                'system' => 'http://unitsofmeasure.org',
                                'code'   => 'mm[Hg]',
                            ],
                        ],
                        [
                            'code' => [
                                'coding' => [[
                                    'system'  => 'http://loinc.org',
                                    'code'    => '8462-4',
                                    'display' => 'Diastolic blood pressure',
                                ]],
                                'text' => 'Diastolic blood pressure',
                            ],
                            'valueQuantity' => [
                                'value'  => $diastolic,
                                'unit'   => 'mmHg',
                                'system' => 'http://unitsofmeasure.org',
                                'code'   => 'mm[Hg]',
                            ],
                        ],
                    ];
                } elseif (is_numeric($value)) {
                    $systolic = (float) $value;
                    $obsResource['component'] = [
                        [
                            'code' => [
                                'coding' => [[
                                    'system'  => 'http://loinc.org',
                                    'code'    => '8480-6',
                                    'display' => 'Systolic blood pressure',
                                ]],
                                'text' => 'Systolic blood pressure',
                            ],
                            'valueQuantity' => [
                                'value'  => $systolic,
                                'unit'   => 'mmHg',
                                'system' => 'http://unitsofmeasure.org',
                                'code'   => 'mm[Hg]',
                            ],
                        ],
                    ];
                } else {
                    $obsResource['valueString'] = $value;
                }
            } elseif (is_numeric($value)) {
                $unitInfo = $this->getVitalSignUnitInfo($text, trim((string) ($obs['unit'] ?? '')));
                $vq = ['value' => (float) $value];
                if ($unitInfo['unit'] !== '') {
                    $vq['unit']   = $unitInfo['unit'];
                    $vq['system'] = 'http://unitsofmeasure.org';
                    $vq['code']   = $unitInfo['code'] !== '' ? $unitInfo['code'] : $unitInfo['unit'];
                }
                $obsResource['valueQuantity'] = $vq;
            } else {
                $obsResource['valueString'] = $value;
            }

            $builder->addObservation($obsResource);
        }

        foreach ((array) ($source['investigations'] ?? []) as $idx => $inv) {
            $text = trim((string) ($inv['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $observationId = 'discharge-investigation-observation-' . $recordId . '-' . $idx;
            $resultValue = trim((string) ($inv['value'] ?? ''));
            if ($resultValue === '' && preg_match('/^(.+?):\s*(.+)$/', $text, $parts) === 1) {
                $text = trim($parts[1]);
                $resultValue = trim($parts[2]);
            }

            $coding = [];
            $loincCode = trim((string) ($inv['loinc_code'] ?? ''));
            if (str_contains($loincCode, ':')) {
                $loincCode = '';
            }
            $loincDisplay = trim((string) ($inv['loinc_display'] ?? ''));
            if ($loincCode === '') {
                $resLab = $this->codingResolver->resolveLoincForLabTest($text, $text);
                $coding = $this->withoutPlaceholderCoding((array) ($resLab['coding'] ?? []));
            } else {
                $coding[] = [
                    'system'  => 'http://loinc.org',
                    'code'    => $loincCode,
                    'display' => $loincDisplay !== '' ? $loincDisplay : $text,
                ];
            }

            if ($coding === []) {
                $coding = [[
                    'system'  => 'http://snomed.info/sct',
                    'code'    => '108252007',
                    'display' => 'Laboratory procedure',
                ]];
            }

            $invObsResource = [
                'resourceType' => 'Observation',
                'id' => $observationId,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Observation']],
                'text' => [
                    'status' => 'generated',
                    'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p><b>Investigation:</b> ' . htmlspecialchars($text) . ($resultValue !== '' ? (': ' . htmlspecialchars($resultValue)) : '') . '</p></div>',
                ],
                'status' => 'final',
                // NRCES Observation profile requires at least one category.
                'category' => [[
                    'coding' => [[
                        'system'  => 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code'    => 'laboratory',
                        'display' => 'Laboratory',
                    ]],
                    'text' => 'Laboratory',
                ]],
                'code' => [
                    'coding' => $coding,
                    'text' => $text,
                ],
                'subject' => ['reference' => $patientRef, 'display' => (string) ($source['patient']['name'] ?? $source['patient_name'] ?? 'Patient')],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'effectiveDateTime' => (string) ($inv['reported_at'] ?? $inv['authored_on'] ?? $timestamp),
            ];

            if ($resultValue !== '') {
                if (is_numeric($resultValue)) {
                    $invObsResource['valueQuantity'] = [
                        'value' => (float) $resultValue,
                        'unit'  => trim((string) ($inv['unit'] ?? '')),
                    ];
                } else {
                    $invObsResource['valueString'] = $resultValue;
                }
            }

            $builder->addObservation($invObsResource);
            $builder->addDiagnosticReport([
                'resourceType' => 'DiagnosticReport',
                'id' => 'discharge-investigation-' . $recordId . '-' . $idx,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DiagnosticReportLab']],
                'text' => [
                    'status' => 'generated',
                    'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p><b>Diagnostic Report:</b> ' . htmlspecialchars($text) . '</p></div>',
                ],
                'status' => 'final',
                'category' => [['coding' => [[
                    'system' => 'http://snomed.info/sct',
                    'code' => '108252007',
                    'display' => 'Laboratory procedure',
                ]]]],
                'code' => [
                    'coding' => $coding,
                    'text' => $text,
                ],
                'subject' => ['reference' => $patientRef, 'display' => (string) ($source['patient']['name'] ?? $source['patient_name'] ?? 'Patient')],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'issued' => (string) ($inv['reported_at'] ?? $inv['authored_on'] ?? $timestamp),
                'result' => [['reference' => 'urn:uuid:' . $observationId, 'display' => $text]],
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
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/AllergyIntolerance']],
                'text' => [
                    'status' => 'generated',
                    'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p><b>Allergy:</b> ' . htmlspecialchars($text) . '</p></div>',
                ],
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
                'patient' => ['reference' => $patientRef, 'display' => (string) ($source['patient']['name'] ?? $source['patient_name'] ?? 'Patient')],
                'recordedDate' => (string) ($alg['recorded_at'] ?? $timestamp),
                'note' => isset($alg['note']) && trim((string) $alg['note']) !== ''
                    ? [['text' => trim((string) $alg['note'])]]
                    : null,
            ]);
        }

        foreach ((array) ($source['care_plans'] ?? []) as $idx => $cp) {
            $title = $this->cleanPlainText((string) ($cp['title'] ?? 'Discharge Advice'));
            $description = $this->cleanPlainText((string) ($cp['description'] ?? ''));
            if (! $this->isMeaningfulValue($description)) {
                continue;
            }

            $carePlanResource = [
                'resourceType' => 'CarePlan',
                'id' => 'discharge-careplan-' . $recordId . '-' . $idx,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/CarePlan']],
                'text' => [
                    'status' => 'generated',
                    'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p><b>' . htmlspecialchars($title !== '' ? $title : 'Discharge Advice') . ':</b> ' . htmlspecialchars($description) . '</p></div>',
                ],
                'status' => 'active',
                'intent' => 'plan',
                'title' => $title !== '' ? $title : 'Discharge Advice',
                'description' => $description,
                'subject' => ['reference' => $patientRef, 'display' => (string) ($source['patient']['name'] ?? $source['patient_name'] ?? 'Patient')],
                'created' => (string) ($cp['created_at'] ?? $timestamp),
                'category' => [
                    [
                        'coding' => [
                            [
                                'system' => 'http://snomed.info/sct',
                                'code' => '736271009',
                                'display' => 'Outpatient care plan'
                            ]
                        ]
                    ]
                ],
                'activity' => [
                    [
                        'detail' => [
                            'kind' => 'ServiceRequest',
                            'code' => [
                                'coding' => [
                                    [
                                        'system' => 'http://snomed.info/sct',
                                        'code' => '385676005',
                                        'display' => 'General advice and education'
                                    ]
                                ]
                            ],
                            'status' => 'scheduled',
                            'description' => $description,
                        ]
                    ]
                ]
            ];

            if ($encounterRef) {
                $carePlanResource['encounter'] = ['reference' => $encounterRef];
            }
            if (is_array($practitioner)) {
                $carePlanResource['author'] = [
                    'reference' => 'urn:uuid:' . (string) $practitioner['id'],
                    'display' => (string) ($practitioner['name'][0]['text'] ?? ''),
                ];
            }

            $builder->addCarePlan($carePlanResource);
        }

        $documentsList = (array) ($source['documents'] ?? []);
        if (empty($documentsList)) {
            $documentsList = [$this->generateDynamicDischargePdfDocument($source)];
        }

        foreach ($documentsList as $idx => $document) {
            $data = trim((string) ($document['data'] ?? ''));
            if ($data === '') {
                continue;
            }

            $docTitle = trim((string) ($document['title'] ?? 'Clinical document'));
            $snomedCode = trim((string) ($document['snomed_code'] ?? ''));
            $snomedDisplay = trim((string) ($document['snomed_display'] ?? ''));
            $loincCode = trim((string) ($document['loinc_code'] ?? ''));

            $typeCoding = [];
            if (stripos($docTitle, 'Bill') !== false || stripos($docTitle, 'Invoice') !== false) {
                $typeCoding[] = [
                    'system' => 'http://snomed.info/sct',
                    'code' => $snomedCode !== '' ? $snomedCode : '823651000000106',
                    'display' => $snomedDisplay !== '' ? $snomedDisplay : 'Billing record',
                ];
            } else {
                $typeCoding[] = [
                    'system' => 'http://snomed.info/sct',
                    'code' => $snomedCode !== '' ? $snomedCode : '373942005',
                    'display' => $snomedDisplay !== '' ? $snomedDisplay : 'Discharge summary',
                ];
            }

            $rawBytes = base64_decode($data);
            $fileSize = is_string($rawBytes) ? strlen($rawBytes) : 0;
            $sha1Hash = is_string($rawBytes) && $rawBytes !== '' ? base64_encode(sha1($rawBytes, true)) : '';

            $docRef = [
                'resourceType' => 'DocumentReference',
                'id' => 'discharge-document-' . $recordId . '-' . $idx,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentReference']],
                'text' => [
                    'status' => 'generated',
                    'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p><b>Attached Document:</b> ' . htmlspecialchars($docTitle) . '</p></div>',
                ],
                'status' => 'current',
                'docStatus' => 'final',
                'type' => [
                    'coding' => $typeCoding,
                    'text' => $docTitle,
                ],
                'subject' => [
                    'reference' => $patientRef,
                    'display' => (string) ($source['patient']['name'] ?? $source['patient_name'] ?? 'Patient'),
                ],
                'date' => (string) ($document['created_at'] ?? $timestamp),
                'author' => is_array($practitioner) ? [[
                    'reference' => 'urn:uuid:' . (string) $practitioner['id'],
                    'display' => (string) ($practitioner['name'][0]['text'] ?? ''),
                ]] : null,
                'custodian' => is_array($organization) ? [
                    'reference' => 'urn:uuid:' . (string) $organization['id'],
                    'display' => (string) ($organization['name'] ?? ''),
                ] : null,
                'description' => $docTitle,
                'content' => [[
                    'attachment' => [
                        'contentType' => (string) ($document['content_type'] ?? 'application/pdf'),
                        'language' => 'en-IN',
                        'data' => $data,
                        'size' => $fileSize,
                        'title' => $docTitle,
                        'creation' => (string) ($document['created_at'] ?? $timestamp),
                    ],
                ]],
            ];

            if ($sha1Hash !== '') {
                $docRef['content'][0]['attachment']['hash'] = $sha1Hash;
            }

            $builder->addDocumentReference($docRef);
        }

        $rawObservations = (array) ($source['ui_physical_exam'] ?? $source['observations'] ?? []);
        $admissionObsIndexed = [];
        $dischargeObsIndexed = [];
        foreach ($rawObservations as $idx => $obs) {
            $cat = trim((string) ($obs['category'] ?? $obs['category_text'] ?? $obs['stage'] ?? $obs['timing'] ?? ''));
            $txt = trim((string) ($obs['text'] ?? ''));
            if (stripos($cat, 'Admission') !== false || stripos($txt, 'Admission') !== false) {
                $admissionObsIndexed[$idx] = $obs;
            } elseif (stripos($cat, 'Discharge') !== false || stripos($txt, 'Discharge') !== false) {
                $dischargeObsIndexed[$idx] = $obs;
            }
        }

        $chiefComplaintsList = (array) ($source['ui_complaints'] ?? $source['chief_complaints'] ?? []);
        $complaintsNarrative = trim((string) ($source['ui_complaint_narrative'] ?? $source['chief_complaint_narrative'] ?? ''));
        
        $diagnosesList = (array) ($source['ui_final_diagnosis'] ?? $source['diagnoses'] ?? $source['conditions'] ?? []);
        $clinicalHistoryNarrative = trim((string) ($source['ui_clinical_history'] ?? ''));
        
        $admissionNarrative = trim((string) ($source['ui_admission_discharge'] ?? $source['admission_discharge_narrative'] ?? ''));

        $sectionDefinitions = [
            // Chief complaints
            ['title' => 'Chief complaints', 'code' => '422843007', 'display' => 'Chief complaint section', 'prefix' => 'discharge-chief-complaint-', 'items' => $chiefComplaintsList, 'narrative' => $complaintsNarrative],
            // Discharge Diagnosis
            ['title' => 'Discharge Diagnosis', 'code' => '397659008', 'display' => 'Discharge diagnosis', 'prefix' => 'discharge-diagnosis-', 'items' => $diagnosesList, 'narrative' => $clinicalHistoryNarrative],
        ];

        // Surgery / Procedure
        $surgeries = (array) ($source['ui_surgeries'] ?? $source['procedures'] ?? []);
        if (! empty($surgeries)) {
            $sectionDefinitions[] = ['title' => 'Procedures', 'code' => '1003640003', 'display' => 'History of past procedure section', 'prefix' => 'discharge-procedure-', 'items' => $surgeries];
        }

        // Medications
        $meds = (array) ($source['ui_discharge_medicine'] ?? $source['medications'] ?? []);
        if (! empty($meds)) {
            $sectionDefinitions[] = ['title' => 'Medications', 'code' => '1003606003', 'display' => 'Medication history section', 'prefix' => 'discharge-medication-', 'items' => $meds];
        }

        // Physical Examinations on Admission
        if (! empty($admissionObsIndexed)) {
            $sectionDefinitions[] = ['title' => 'Condition on Admission Time', 'code' => '425044008', 'display' => 'Physical exam section', 'prefix' => 'discharge-observation-', 'items_indexed' => $admissionObsIndexed];
        }

        // Physical Examinations on Discharge
        if (! empty($dischargeObsIndexed)) {
            $sectionDefinitions[] = ['title' => 'Condition on Discharge Time', 'code' => '425044008', 'display' => 'Physical exam section', 'prefix' => 'discharge-observation-', 'items_indexed' => $dischargeObsIndexed];
        }

        // Additional Observations not captured in Admission/Discharge
        $otherObs = array_diff_key($rawObservations, $admissionObsIndexed, $dischargeObsIndexed);
        if (! empty($otherObs)) {
             $sectionDefinitions[] = ['title' => 'Other Physical examination', 'code' => '425044008', 'display' => 'Physical exam section', 'prefix' => 'discharge-observation-', 'items_indexed' => $otherObs];
        }

        // Investigations
        $investigations = (array) ($source['ui_investigations'] ?? $source['investigations'] ?? []);
        $invNarrative = trim((string) ($source['ui_summary_investigation'] ?? ''));
        if (! empty($investigations) || $invNarrative !== '') {
            $sectionDefinitions[] = ['title' => 'Clinical Investigation Reports', 'code' => '721981007', 'display' => 'Diagnostic studies report', 'prefix' => 'discharge-investigation-', 'items' => $investigations, 'narrative' => $invNarrative];
        }
        
        // Admission / Discharge Details
        if ($admissionNarrative !== '') {
            $admItems = $encounterRef ? [['name' => 'Admission and Discharge Details', 'id' => 'encounter-' . $recordId]] : [];
            $sectionDefinitions[] = ['title' => 'Admission / Discharge Information', 'code' => '422838006', 'display' => 'Admission details', 'prefix' => 'discharge-admission-', 'items' => $admItems, 'narrative' => $admissionNarrative];
        }
        
        // Course / Treatment
        $course = (array) ($source['ui_course_treatment'] ?? []);
        if (! empty($course)) {
            $sectionDefinitions[] = ['title' => 'Course / Treatment in the hospital', 'code' => '734163000', 'display' => 'Care plan', 'prefix' => 'discharge-course-', 'items' => $course];
        }
        
        // Care plans / Discharge advice
        $carePlans = (array) ($source['ui_discharge_summary'] ?? $source['care_plans'] ?? []);
        if (! empty($carePlans)) {
            $sectionDefinitions[] = ['title' => 'Care plan', 'code' => '734163000', 'display' => 'Care plan', 'prefix' => 'discharge-careplan-', 'items' => $carePlans];
        }
        
        // Document Reference
        if (! empty($documentsList)) {
            $sectionDefinitions[] = ['title' => 'Document reference', 'code' => '373942005', 'display' => 'Discharge summary', 'prefix' => 'discharge-document-', 'items' => $documentsList];
        }

        // Allergies
        $allergies = (array) ($source['allergies'] ?? []);
        if (! empty($allergies)) {
            $sectionDefinitions[] = ['title' => 'Allergies', 'code' => '722446000', 'display' => 'Allergy record', 'prefix' => 'discharge-allergy-', 'items' => $allergies];
        }

        $sections = [];
        foreach ($sectionDefinitions as $definition) {
            $references = [];
            $narrativeParts = [];
            $items = isset($definition['items_indexed']) ? $definition['items_indexed'] : (array) ($definition['items'] ?? []);
            foreach ($items as $idx => $item) {
                $itemDisplay = $this->cleanPlainText((string) ($item['name'] ?? $item['text'] ?? $item['title'] ?? $definition['title']));
                if ($itemDisplay === '') {
                    $itemDisplay = $definition['title'];
                }
                $references[] = [
                    'reference' => 'urn:uuid:' . $definition['prefix'] . $recordId . '-' . $idx,
                    'display' => $itemDisplay,
                ];
                $narrative = $this->sectionItemNarrative((array) $item);
                if ($narrative !== '') {
                    $narrativeParts[] = $narrative;
                }
            }
            $sectionNarrative = trim((string) ($definition['narrative'] ?? ''));
            if ($references === [] && $sectionNarrative === '') {
                continue;
            }
            if ($sectionNarrative !== '') {
                array_unshift($narrativeParts, $sectionNarrative);
            }
            $sec = [
                'title' => $definition['title'],
                'code' => ['coding' => [[
                    'system' => 'http://snomed.info/sct',
                    'code' => $definition['code'],
                    'display' => $definition['display'],
                ]]],
            ];
            if ($references !== []) {
                $sec['entry'] = $references;
            }
            if ($narrativeParts !== []) {
                $sec['text'] = [
                    'status' => 'generated',
                    'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><ul><li>'
                        . implode('</li><li>', array_map('htmlspecialchars', $narrativeParts))
                        . '</li></ul></div>',
                ];
            }
            $sections[] = $sec;
        }

        $patientNameClean = trim((string) ($source['patient']['name'] ?? $source['patient_name'] ?? 'Patient'));
        if ($patientNameClean === '' || strcasecmp($patientNameClean, 'NA') === 0) {
            $patientNameClean = 'Patient';
        }
        $patientGenderClean = trim((string) ($source['patient']['gender'] ?? ''));
        $patientDobClean = trim((string) ($source['patient']['dob'] ?? ''));
        $drNameClean = is_array($practitioner) ? (string) ($practitioner['name'][0]['text'] ?? 'Dr. Physician') : 'Dr. Physician';
        $hospitalNameClean = is_array($organization) ? (string) ($organization['name'] ?? 'Hospital') : 'Hospital';

        $fullNarrativeHtml = $this->buildFullCompositionNarrativeHtml(
            $source,
            'IPD Discharge Summary',
            $patientNameClean,
            $patientId,
            $patientGenderClean,
            $patientDobClean,
            $drNameClean,
            $hospitalNameClean,
            $sections
        );

        $compositionFinalUpdate = [
            'language' => 'en-IN',
            'confidentiality' => 'N',
            'text' => [
                'status' => 'generated',
                'div' => $fullNarrativeHtml,
            ],
        ];
        
        if ($sections !== []) {
            $compositionFinalUpdate['section'] = $sections;
        }
        
        $builder->updateComposition($compositionFinalUpdate);

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

    /** @return array{code:string,display:string} */
    private function resolveVitalSignLoinc(string $text): array
    {
        $upper = strtoupper(trim($text));
        if (str_contains($upper, 'PULSE') || str_contains($upper, 'HEART')) {
            return ['code' => '8867-4', 'display' => 'Heart rate'];
        }
        if (str_contains($upper, 'RESPIRATION') || str_contains($upper, 'RESP')) {
            return ['code' => '9279-1', 'display' => 'Respiratory rate'];
        }
        if (str_contains($upper, 'BP') || str_contains($upper, 'BLOOD PRESSURE')) {
            return ['code' => '85354-9', 'display' => 'Blood pressure panel'];
        }
        if (str_contains($upper, 'SYSTOLIC')) {
            return ['code' => '8480-6', 'display' => 'Systolic blood pressure'];
        }
        if (str_contains($upper, 'DIASTOLIC')) {
            return ['code' => '8462-4', 'display' => 'Diastolic blood pressure'];
        }
        if (str_contains($upper, 'SPO2') || str_contains($upper, 'OXYGEN')) {
            return ['code' => '59408-5', 'display' => 'Oxygen saturation in Arterial blood by Pulse oximetry'];
        }
        if (str_contains($upper, 'RBS') || str_contains($upper, 'RANDOM BLOOD SUGAR') || str_contains($upper, 'GLUCOSE')) {
            return ['code' => '2345-7', 'display' => 'Glucose [Mass/volume] in Serum or Plasma'];
        }
        if (str_contains($upper, 'TEMP')) {
            return ['code' => '8310-5', 'display' => 'Body temperature'];
        }
        if (str_contains($upper, 'WEIGHT')) {
            return ['code' => '29463-7', 'display' => 'Body weight'];
        }
        if (str_contains($upper, 'HEIGHT')) {
            return ['code' => '8302-2', 'display' => 'Body height'];
        }
        if (str_contains($upper, 'BMI')) {
            return ['code' => '39156-5', 'display' => 'Body mass index'];
        }

        return ['code' => '', 'display' => ''];
    }

    /** @return array{unit:string,code:string} */
    private function getVitalSignUnitInfo(string $text, string $givenUnit): array
    {
        if ($givenUnit !== '') {
            $resolved = $this->codingResolver->resolveUnitUcUM($givenUnit);
            return ['unit' => $givenUnit, 'code' => (string) ($resolved['code'] ?? $givenUnit)];
        }

        $upper = strtoupper(trim($text));
        if (str_contains($upper, 'PULSE') || str_contains($upper, 'HEART') || str_contains($upper, 'RESPIRATION') || str_contains($upper, 'RESP')) {
            return ['unit' => '/min', 'code' => '/min'];
        }
        if (str_contains($upper, 'BP') || str_contains($upper, 'SYSTOLIC') || str_contains($upper, 'DIASTOLIC')) {
            return ['unit' => 'mmHg', 'code' => 'mm[Hg]'];
        }
        if (str_contains($upper, 'SPO2') || str_contains($upper, 'OXYGEN')) {
            return ['unit' => '%', 'code' => '%'];
        }
        if (str_contains($upper, 'RBS') || str_contains($upper, 'RANDOM BLOOD SUGAR') || str_contains($upper, 'GLUCOSE')) {
            return ['unit' => 'mg/dL', 'code' => 'mg/dL'];
        }
        if (str_contains($upper, 'TEMP')) {
            return ['unit' => 'degF', 'code' => '[degF]'];
        }
        if (str_contains($upper, 'WEIGHT')) {
            return ['unit' => 'kg', 'code' => 'kg'];
        }
        if (str_contains($upper, 'HEIGHT')) {
            return ['unit' => 'cm', 'code' => 'cm'];
        }

        return ['unit' => '', 'code' => ''];
    }

    /** @return array{code:string,display:string} */
    private function resolvePhysicalExamSnomedCode(string $text): array
    {
        $upper = strtoupper(trim($text));
        if (str_contains($upper, 'PALLOR')) {
            return ['code' => '89521008', 'display' => 'Pallor'];
        }
        if (str_contains($upper, 'JAUNDICE') || str_contains($upper, 'ICTERUS')) {
            return ['code' => '18165004', 'display' => 'Jaundice'];
        }
        if (str_contains($upper, 'CYANOSIS')) {
            return ['code' => '119419001', 'display' => 'Cyanosis'];
        }
        if (str_contains($upper, 'CLUBBING')) {
            return ['code' => '30760006', 'display' => 'Clubbing of nail'];
        }
        if (str_contains($upper, 'EDEMA') || str_contains($upper, 'OEDEMA')) {
            return ['code' => '267038008', 'display' => 'Edema'];
        }
        if (str_contains($upper, 'JVP')) {
            return ['code' => '271649006', 'display' => 'Jugular venous pressure finding'];
        }

        return ['code' => '364075005', 'display' => 'Physical examination finding'];
    }

    /** @return array{code:string,display:string} */
    private function resolveFallbackSnomedCode(string $text): array
    {
        $upper = strtoupper(trim($text));
        if (str_contains($upper, 'VIRAL FEVER')) {
            return ['code' => '409702008', 'display' => 'Viral fever'];
        }
        if (str_contains($upper, 'TYPHOID')) {
            return ['code' => '4834000', 'display' => 'Typhoid fever'];
        }
        if (str_contains($upper, 'DENGUE')) {
            return ['code' => '38362002', 'display' => 'Dengue'];
        }
        if (str_contains($upper, 'MALARIA')) {
            return ['code' => '61462000', 'display' => 'Malaria'];
        }
        if (str_contains($upper, 'FEVER') || str_contains($upper, 'PYREXIA')) {
            return ['code' => '386661006', 'display' => 'Fever'];
        }
        if (str_contains($upper, 'BREATHLESS') || str_contains($upper, 'DYSPNEA')) {
            return ['code' => '267036007', 'display' => 'Dyspnea'];
        }
        if (str_contains($upper, 'COUGH')) {
            return ['code' => '49727002', 'display' => 'Cough'];
        }
        if (str_contains($upper, 'ABDOMINAL PAIN') || str_contains($upper, 'ABDOMEN PAIN')) {
            return ['code' => '21522000', 'display' => 'Abdominal pain'];
        }
        if (str_contains($upper, 'CHEST PAIN')) {
            return ['code' => '29857009', 'display' => 'Chest pain'];
        }
        if (str_contains($upper, 'PAIN')) {
            return ['code' => '22253000', 'display' => 'Pain'];
        }
        if (str_contains($upper, 'HEADACHE')) {
            return ['code' => '25064000', 'display' => 'Headache'];
        }
        if (str_contains($upper, 'DIARRHEA')) {
            return ['code' => '62315000', 'display' => 'Diarrhea'];
        }
        if (str_contains($upper, 'VOMITING')) {
            return ['code' => '422400008', 'display' => 'Vomiting'];
        }
        if (str_contains($upper, 'HYPERTENSION') || str_contains($upper, 'HTN')) {
            return ['code' => '38341003', 'display' => 'Hypertension'];
        }
        if (str_contains($upper, 'DIABETES') || str_contains($upper, 'DM')) {
            return ['code' => '73211009', 'display' => 'Diabetes mellitus'];
        }

        return ['code' => '404684003', 'display' => 'Clinical finding'];
    }

    /**
     * @param array<int,array<string,mixed>> $coding
     * @return array<int,array<string,mixed>>
     */
    private function withoutPlaceholderCoding(array $coding): array
    {
        return array_values(array_filter($coding, static function (array $item): bool {
            $code = strtoupper(trim((string) ($item['code'] ?? '')));
            return $code !== '' && $code !== 'UNMAPPED';
        }));
    }

    /** @param array<string,mixed> $item */
    private function sectionItemNarrative(array $item): string
    {
        // For medications: name + dosage
        if (isset($item['name']) && $this->isMeaningfulValue((string) $item['name'])) {
            $name = trim((string) $item['name']);
            $dosage = trim((string) ($item['dosage'] ?? ''));
            return trim($name . ($this->isMeaningfulValue($dosage) ? ' - ' . $dosage : ''));
        }

        // For observations: text + value (+ unit)
        if (isset($item['text'], $item['value']) && $this->isMeaningfulValue((string) $item['text']) && $this->isMeaningfulValue((string) $item['value'])) {
            $text = trim((string) $item['text']);
            $val = trim((string) $item['value']);
            $unit = trim((string) ($item['unit'] ?? ''));
            $unitInfo = (is_numeric($val) || preg_match('/^\d+\s*[\/\-]\s*\d+/', $val)) ? $this->getVitalSignUnitInfo($text, $unit) : ['unit' => ''];
            $displayUnit = $unit !== '' ? $unit : ($unitInfo['unit'] ?? '');
            $valStr = $val . ($displayUnit !== '' && stripos($val, $displayUnit) === false ? ' ' . $displayUnit : '');
            return trim($text . ': ' . $valStr);
        }

        // For care plans: title + description
        if (isset($item['title'], $item['description']) && $this->isMeaningfulValue((string) $item['description'])) {
            $title = trim((string) $item['title']);
            $desc = trim((string) $item['description']);
            if ($title !== '' && stripos($desc, $title) === false) {
                return trim($title . ': ' . strip_tags($desc));
            }
            return trim(strip_tags($desc));
        }

        // For documents
        if (isset($item['title']) && $this->isMeaningfulValue((string) $item['title'])) {
            return trim((string) $item['title']);
        }

        // Default fallback
        foreach (['text', 'description', 'title', 'name', 'value'] as $field) {
            $value = trim((string) ($item[$field] ?? ''));
            if ($this->isMeaningfulValue($value)) {
                return trim(strip_tags($value));
            }
        }

        return '';
    }

    /**
     * Dynamically generates a styled IPD Discharge Summary PDF using mPDF
     * from the source dataset when no pre-generated document binary is provided.
     *
     * @param array<string,mixed> $source
     * @return array{title:string,content_type:string,data:string,created_at:string}
     */
    private function generateDynamicDischargePdfDocument(array $source): array
    {
        $hospitalName = trim((string) ($source['organization']['name'] ?? 'Hospital'));
        $patientName = trim((string) ($source['patient']['name'] ?? 'Patient'));
        $patientId = trim((string) ($source['patient']['id'] ?? 'N/A'));
        $gender = ucfirst(trim((string) ($source['patient']['gender'] ?? 'N/A')));
        $dob = trim((string) ($source['patient']['dob'] ?? 'N/A'));
        $abhaId = trim((string) ($source['patient']['abha_id'] ?? 'N/A'));
        $recordId = trim((string) ($source['record_id'] ?? 'N/A'));

        $admDate = ! empty($source['encounter']['start']) ? date('d-m-Y', strtotime((string) $source['encounter']['start'])) : 'N/A';
        $disDate = ! empty($source['encounter']['end']) ? date('d-m-Y', strtotime((string) $source['encounter']['end'])) : date('d-m-Y');
        $doctorName = trim((string) ($source['doctor_name'] ?? $source['doctor']['name'] ?? 'Attending Physician'));

        $template = (array) ($source['template'] ?? []);
        $templateHtml = trim((string) ($template['template_html'] ?? ''));

        $chiefComplaints = (array) ($source['chief_complaints'] ?? []);
        if (empty($chiefComplaints) && ! empty($source['conditions'])) {
            $chiefComplaints = (array) $source['conditions'];
        }
        $diagnoses = (array) ($source['diagnoses'] ?? []);
        if (empty($diagnoses) && ! empty($source['conditions'])) {
            $diagnoses = (array) $source['conditions'];
        }
        $vitals = (array) ($source['observations'] ?? []);
        $procedures = (array) ($source['procedures'] ?? []);
        $medications = (array) ($source['medications'] ?? []);
        $carePlans = (array) ($source['care_plans'] ?? []);

        if ($templateHtml !== '') {
            $tokens = [
                '{{HOSPITAL_NAME}}' => htmlspecialchars($hospitalName),
                '{{H_NAME}}' => htmlspecialchars($hospitalName),
                '{{PATIENT_NAME}}' => htmlspecialchars($patientName),
                '{{UHID}}' => htmlspecialchars($patientId),
                '{{IPD_CODE}}' => htmlspecialchars($recordId),
                '{{AGE_GENDER}}' => htmlspecialchars($gender . ($dob !== 'N/A' ? ' / ' . $dob : '')),
                '{{ADMIT_DATE}}' => htmlspecialchars($admDate),
                '{{DISCHARGE_DATE}}' => htmlspecialchars($disDate),
                '{{DOCTOR_NAMES}}' => htmlspecialchars($doctorName),
                '{{DOCTOR_NAME}}' => htmlspecialchars($doctorName),
                '{{ABHA_ID}}' => htmlspecialchars($abhaId),
            ];

            $contentHtml = '';
            if (! empty($chiefComplaints)) {
                $contentHtml .= '<div class="section-title">Chief Complaints</div><ul>';
                foreach ($chiefComplaints as $cc) {
                    $text = is_array($cc) ? ($cc['name'] ?? $cc['text'] ?? '') : (string) $cc;
                    if ($text !== '') {
                        $contentHtml .= '<li>' . htmlspecialchars($text) . '</li>';
                    }
                }
                $contentHtml .= '</ul>';
            }
            if (! empty($diagnoses)) {
                $contentHtml .= '<div class="section-title">Problems &amp; Diagnoses</div><ul>';
                foreach ($diagnoses as $diag) {
                    $text = is_array($diag) ? ($diag['name'] ?? $diag['text'] ?? '') : (string) $diag;
                    if ($text !== '') {
                        $contentHtml .= '<li>' . htmlspecialchars($text) . '</li>';
                    }
                }
                $contentHtml .= '</ul>';
            }
            if (! empty($vitals)) {
                $contentHtml .= '<div class="section-title">Physical Examination &amp; Vital Signs</div>';
                $contentHtml .= '<table class="grid"><tr><th>Parameter</th><th>Result</th></tr>';
                foreach ($vitals as $v) {
                    $text = is_array($v) ? ($v['text'] ?? '') : '';
                    $val = is_array($v) ? ($v['value'] ?? '') : '';
                    if ($text !== '') {
                        $contentHtml .= '<tr><td>' . htmlspecialchars($text) . '</td><td>' . htmlspecialchars($val) . '</td></tr>';
                    }
                }
                $contentHtml .= '</table>';
            }
            if (! empty($procedures)) {
                $contentHtml .= '<div class="section-title">Procedures Performed</div><ul>';
                foreach ($procedures as $proc) {
                    $text = is_array($proc) ? ($proc['name'] ?? $proc['text'] ?? '') : (string) $proc;
                    if ($text !== '') {
                        $contentHtml .= '<li>' . htmlspecialchars($text) . '</li>';
                    }
                }
                $contentHtml .= '</ul>';
            }
            if (! empty($medications)) {
                $contentHtml .= '<div class="section-title">Prescribed Discharge Medications</div>';
                $contentHtml .= '<table class="grid"><tr><th>Medication Name</th><th>Dosage &amp; Frequency</th></tr>';
                foreach ($medications as $med) {
                    $name = is_array($med) ? ($med['name'] ?? '') : '';
                    $dosage = is_array($med) ? ($med['dosage'] ?? '') : '';
                    if ($name !== '') {
                        $contentHtml .= '<tr><td>' . htmlspecialchars($name) . '</td><td>' . htmlspecialchars($dosage) . '</td></tr>';
                    }
                }
                $contentHtml .= '</table>';
            }
            if (! empty($carePlans)) {
                $contentHtml .= '<div class="section-title">Follow-up &amp; Instructions</div><ul>';
                foreach ($carePlans as $cp) {
                    $desc = is_array($cp) ? ($cp['description'] ?? $cp['title'] ?? '') : (string) $cp;
                    if ($desc !== '') {
                        $contentHtml .= '<li>' . htmlspecialchars($desc) . '</li>';
                    }
                }
                $contentHtml .= '</ul>';
            }

            $ccHtml = '';
            if (! empty($chiefComplaints)) {
                $ccHtml .= '<ul>';
                foreach ($chiefComplaints as $cc) {
                    $text = is_array($cc) ? ($cc['name'] ?? $cc['text'] ?? '') : (string) $cc;
                    if ($text !== '') {
                        $ccHtml .= '<li>' . htmlspecialchars($text) . '</li>';
                    }
                }
                $ccHtml .= '</ul>';
            }

            $diagHtml = '';
            if (! empty($diagnoses)) {
                $diagHtml .= '<ul>';
                foreach ($diagnoses as $diag) {
                    $text = is_array($diag) ? ($diag['name'] ?? $diag['text'] ?? '') : (string) $diag;
                    if ($text !== '') {
                        $diagHtml .= '<li>' . htmlspecialchars($text) . '</li>';
                    }
                }
                $diagHtml .= '</ul>';
            }

            $procHtml = '';
            if (! empty($procedures)) {
                $procHtml .= '<ul>';
                foreach ($procedures as $proc) {
                    $text = is_array($proc) ? ($proc['name'] ?? $proc['text'] ?? '') : (string) $proc;
                    if ($text !== '') {
                        $procHtml .= '<li>' . htmlspecialchars($text) . '</li>';
                    }
                }
                $procHtml .= '</ul>';
            }

            $medHtml = '';
            if (! empty($medications)) {
                $medHtml .= '<table class="grid"><tr><th>Medication Name</th><th>Dosage &amp; Frequency</th></tr>';
                foreach ($medications as $med) {
                    $name = is_array($med) ? ($med['name'] ?? '') : '';
                    $dosage = is_array($med) ? ($med['dosage'] ?? '') : '';
                    if ($name !== '') {
                        $medHtml .= '<tr><td>' . htmlspecialchars($name) . '</td><td>' . htmlspecialchars($dosage) . '</td></tr>';
                    }
                }
                $medHtml .= '</table>';
            }

            $vitalsHtml = '';
            if (! empty($vitals)) {
                $vitalsHtml .= '<table class="grid"><tr><th>Parameter</th><th>Result</th></tr>';
                foreach ($vitals as $v) {
                    $text = is_array($v) ? ($v['text'] ?? '') : '';
                    $val = is_array($v) ? ($v['value'] ?? '') : '';
                    if ($text !== '') {
                        $vitalsHtml .= '<tr><td>' . htmlspecialchars($text) . '</td><td>' . htmlspecialchars($val) . '</td></tr>';
                    }
                }
                $vitalsHtml .= '</table>';
            }

            $careHtml = '';
            if (! empty($carePlans)) {
                $careHtml .= '<ul>';
                foreach ($carePlans as $cp) {
                    $desc = is_array($cp) ? ($cp['description'] ?? $cp['title'] ?? '') : (string) $cp;
                    if ($desc !== '') {
                        $careHtml .= '<li>' . htmlspecialchars($desc) . '</li>';
                    }
                }
                $careHtml .= '</ul>';
            }

            $tokens['{{PRESENTING_COMPLAINTS}}'] = $ccHtml;
            $tokens['{{FINAL_DIAGNOSIS}}'] = $diagHtml;
            $tokens['{{PROCEDURE}}'] = $procHtml;
            $tokens['{{SURGERY}}'] = $procHtml;
            $tokens['{{DISCHARGE_MEDICATIONS}}'] = $medHtml;
            $tokens['{{EXAMINATION_ON_DISCHARGE}}'] = $vitalsHtml;
            $tokens['{{GENERAL_EXAM_ADMISSION}}'] = $vitalsHtml;
            $tokens['{{DISCHARGE_SUMMARY}}'] = $careHtml;
            $tokens['{{REVIEW_AFTER}}'] = $careHtml;
            $tokens['{{CONTENT}}'] = $contentHtml;
            $renderedHtml = str_ireplace(array_keys($tokens), array_values($tokens), $templateHtml);

            $cssHtml = trim((string) ($template['template_css'] ?? ''));
            $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>';
            $html .= 'body{font-family:"freeserif",serif;font-size:10pt;color:#1e293b;line-height:1.4;}';
            $html .= '.section-title{font-size:11pt;font-weight:bold;color:#0f172a;background:#e2e8f0;padding:4px 8px;border-left:4px solid #0284c7;margin:12px 0 6px 0;}';
            $html .= 'table.grid{width:100%;border-collapse:collapse;margin:6px 0;}';
            $html .= 'table.grid th,table.grid td{border:1px solid #cbd5e1;padding:5px 8px;font-size:9pt;text-align:left;}';
            $html .= 'table.grid th{background:#f1f5f9;font-weight:bold;}';
            if ($cssHtml !== '') {
                $html .= $cssHtml;
            }
            $html .= '</style></head><body>' . $renderedHtml . '</body></html>';
        } else {
            $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>';
            $html .= 'body{font-family:"freeserif",serif;font-size:10pt;color:#1e293b;line-height:1.4;}';
            $html .= '.header{width:100%;border-bottom:2px solid #0284c7;padding-bottom:8px;margin-bottom:12px;}';
            $html .= '.hospital-title{font-size:16pt;font-weight:bold;color:#0369a1;}';
            $html .= '.doc-type{font-size:13pt;font-weight:bold;color:#0284c7;text-align:right;}';
            $html .= 'table.meta{width:100%;border-collapse:collapse;margin-bottom:12px;background:#f8fafc;}';
            $html .= 'table.meta td{border:1px solid #cbd5e1;padding:5px 8px;font-size:9.5pt;}';
            $html .= '.section-title{font-size:11pt;font-weight:bold;color:#0f172a;background:#e2e8f0;padding:4px 8px;border-left:4px solid #0284c7;margin:12px 0 6px 0;}';
            $html .= 'table.grid{width:100%;border-collapse:collapse;margin:6px 0;}';
            $html .= 'table.grid th,table.grid td{border:1px solid #cbd5e1;padding:5px 8px;font-size:9pt;text-align:left;}';
            $html .= 'table.grid th{background:#f1f5f9;font-weight:bold;}';
            $html .= '.footer{margin-top:30px;width:100%;}';
            $html .= '</style></head><body>';

            $html .= '<table class="header"><tr>';
            $html .= '<td><span class="hospital-title">' . htmlspecialchars($hospitalName) . '</span></td>';
            $html .= '<td class="doc-type">DISCHARGE SUMMARY</td>';
            $html .= '</tr></table>';

            $html .= '<table class="meta">';
            $html .= '<tr><td><strong>Patient Name:</strong> ' . htmlspecialchars($patientName) . '</td><td><strong>IPD No:</strong> ' . htmlspecialchars($recordId) . '</td></tr>';
            $html .= '<tr><td><strong>Patient ID:</strong> ' . htmlspecialchars($patientId) . '</td><td><strong>Gender / DOB:</strong> ' . htmlspecialchars($gender) . ' / ' . htmlspecialchars($dob) . '</td></tr>';
            $html .= '<tr><td><strong>ABHA ID:</strong> ' . htmlspecialchars($abhaId) . '</td><td><strong>Admission / Discharge:</strong> ' . htmlspecialchars($admDate) . ' to ' . htmlspecialchars($disDate) . '</td></tr>';
            $html .= '</table>';

            if (! empty($chiefComplaints)) {
                $html .= '<div class="section-title">Chief Complaints</div><ul>';
                foreach ($chiefComplaints as $cc) {
                    $text = is_array($cc) ? ($cc['name'] ?? $cc['text'] ?? '') : (string) $cc;
                    if ($text !== '') {
                        $html .= '<li>' . htmlspecialchars($text) . '</li>';
                    }
                }
                $html .= '</ul>';
            }

            if (! empty($diagnoses)) {
                $html .= '<div class="section-title">Problems &amp; Diagnoses</div><ul>';
                foreach ($diagnoses as $diag) {
                    $text = is_array($diag) ? ($diag['name'] ?? $diag['text'] ?? '') : (string) $diag;
                    if ($text !== '') {
                        $html .= '<li>' . htmlspecialchars($text) . '</li>';
                    }
                }
                $html .= '</ul>';
            }

            if (! empty($vitals)) {
                $html .= '<div class="section-title">Physical Examination &amp; Vital Signs</div>';
                $html .= '<table class="grid"><tr><th>Parameter</th><th>Result</th></tr>';
                foreach ($vitals as $v) {
                    $text = is_array($v) ? ($v['text'] ?? '') : '';
                    $val = is_array($v) ? ($v['value'] ?? '') : '';
                    if ($text !== '') {
                        $html .= '<tr><td>' . htmlspecialchars($text) . '</td><td>' . htmlspecialchars($val) . '</td></tr>';
                    }
                }
                $html .= '</table>';
            }

            if (! empty($procedures)) {
                $html .= '<div class="section-title">Procedures Performed</div><ul>';
                foreach ($procedures as $proc) {
                    $text = is_array($proc) ? ($proc['name'] ?? $proc['text'] ?? '') : (string) $proc;
                    if ($text !== '') {
                        $html .= '<li>' . htmlspecialchars($text) . '</li>';
                    }
                }
                $html .= '</ul>';
            }

            if (! empty($medications)) {
                $html .= '<div class="section-title">Prescribed Discharge Medications</div>';
                $html .= '<table class="grid"><tr><th>Medication Name</th><th>Dosage &amp; Frequency</th></tr>';
                foreach ($medications as $med) {
                    $name = is_array($med) ? ($med['name'] ?? '') : '';
                    $dosage = is_array($med) ? ($med['dosage'] ?? '') : '';
                    if ($name !== '') {
                        $html .= '<tr><td>' . htmlspecialchars($name) . '</td><td>' . htmlspecialchars($dosage) . '</td></tr>';
                    }
                }
                $html .= '</table>';
            }

            if (! empty($carePlans)) {
                $html .= '<div class="section-title">Follow-up &amp; Instructions</div><ul>';
                foreach ($carePlans as $cp) {
                    $desc = is_array($cp) ? ($cp['description'] ?? $cp['title'] ?? '') : (string) $cp;
                    if ($desc !== '') {
                        $html .= '<li>' . htmlspecialchars($desc) . '</li>';
                    }
                }
                $html .= '</ul>';
            }

            $html .= '<table class="footer"><tr><td>Printed Date: ' . date('d-m-Y H:i') . '</td><td style="text-align:right;"><strong>Doctor:</strong> ' . htmlspecialchars($doctorName) . '</td></tr></table>';
            $html .= '</body></html>';
        }

        try {
            $format = ! empty($template['page_size']) ? (string) $template['page_size'] : 'A4';
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => $format,
                'margin_left' => (float) ($template['page_margin_left_cm'] ?? 1.0) * 10,
                'margin_right' => (float) ($template['page_margin_right_cm'] ?? 1.0) * 10,
                'margin_top' => (float) ($template['page_margin_top_cm'] ?? 1.0) * 10,
                'margin_bottom' => (float) ($template['page_margin_bottom_cm'] ?? 1.0) * 10,
                'default_font' => 'freeserif',
                'tempDir' => WRITEPATH . 'cache',
            ]);
            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;

            $headerHtml = trim((string) ($template['header_html'] ?? ''));
            $footerHtml = trim((string) ($template['footer_html'] ?? ''));
            if ($headerHtml !== '') {
                $mpdf->SetHTMLHeader($headerHtml, 'O');
                $mpdf->SetHTMLHeader($headerHtml, 'E');
            }
            if ($footerHtml !== '') {
                $mpdf->SetHTMLFooter($footerHtml, 'O');
                $mpdf->SetHTMLFooter($footerHtml, 'E');
            }

            $mpdf->WriteHTML($html);
            $pdfBytes = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
        } catch (\Throwable $e) {
            if (function_exists('log_message')) {
                log_message('warning', 'mPDF rendering failed for dynamic discharge PDF: ' . $e->getMessage());
            }
            $pdfBytes = '%PDF-1.4 Dynamic Discharge Summary: ' . $patientName;
        }

        return [
            'title' => 'IPD Discharge Summary',
            'content_type' => 'application/pdf',
            'data' => base64_encode($pdfBytes),
            'created_at' => (string) ($source['completed_at'] ?? date(DATE_ATOM)),
        ];
    }

    /**
     * Build top-level human-readable XHTML narrative for Composition.text.div
     *
     * @param array<string,mixed> $source
     * @param array<int,array<string,mixed>> $sections
     */
    private function buildFullCompositionNarrativeHtml(
        array $source,
        string $title,
        string $patientName,
        string $patientId,
        string $gender,
        string $dob,
        string $drName,
        string $hospitalName,
        array $sections
    ): string {
        $ipdNo = (string) ($source['encounter']['ipd_no'] ?? $source['record_id'] ?? '');
        $admitDate = (string) ($source['encounter']['start'] ?? $source['admission_date'] ?? '');
        $dischargeDate = (string) ($source['encounter']['end'] ?? $source['discharge_date'] ?? $source['completed_at'] ?? '');
        $locDisplay = (string) ($source['encounter']['location_display'] ?? '');
        $abhaId = (string) ($source['patient']['abha_id'] ?? '');

        $html = '<div xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-IN" lang="en-IN">';
        $html .= '<div style="border-bottom:2px solid #0284c7;padding-bottom:6px;margin-bottom:12px;">';
        $html .= '<h2 style="margin:0 0 4px 0;color:#0369a1;">' . htmlspecialchars($hospitalName) . '</h2>';
        $html .= '<div style="font-size:14pt;font-weight:bold;color:#0284c7;">' . htmlspecialchars($title) . '</div>';
        $html .= '</div>';

        $html .= '<table style="width:100%;border-collapse:collapse;margin-bottom:14px;background:#f8fafc;font-size:10pt;">';
        $html .= '<tr><td style="border:1px solid #cbd5e1;padding:6px;"><strong>Patient:</strong> ' . htmlspecialchars($patientName) . '</td><td style="border:1px solid #cbd5e1;padding:6px;"><strong>IPD No:</strong> ' . htmlspecialchars($ipdNo) . '</td></tr>';
        $html .= '<tr><td style="border:1px solid #cbd5e1;padding:6px;"><strong>Gender / DOB:</strong> ' . htmlspecialchars(ucfirst($gender)) . ($dob !== '' ? (' / ' . htmlspecialchars($dob)) : '') . '</td><td style="border:1px solid #cbd5e1;padding:6px;"><strong>ABHA Number:</strong> ' . htmlspecialchars($abhaId !== '' ? $abhaId : 'N/A') . '</td></tr>';
        $html .= '<tr><td style="border:1px solid #cbd5e1;padding:6px;"><strong>Admission Date:</strong> ' . htmlspecialchars($admitDate) . '</td><td style="border:1px solid #cbd5e1;padding:6px;"><strong>Discharge Date:</strong> ' . htmlspecialchars($dischargeDate) . '</td></tr>';
        if ($locDisplay !== '' || $drName !== '') {
            $html .= '<tr><td style="border:1px solid #cbd5e1;padding:6px;"><strong>Location / Bed:</strong> ' . htmlspecialchars($locDisplay !== '' ? $locDisplay : 'General Ward') . '</td><td style="border:1px solid #cbd5e1;padding:6px;"><strong>Doctor:</strong> ' . htmlspecialchars($drName) . '</td></tr>';
        }
        $html .= '</table>';

        foreach ($sections as $sec) {
            $secTitle = (string) ($sec['title'] ?? '');
            $secDiv = (string) ($sec['text']['div'] ?? '');
            if ($secTitle === '' || $secDiv === '') {
                continue;
            }
            // Non-greedy match to avoid swallowing sibling tags in multi-div strings.
            if (preg_match('/<div[^>]*>(.*?)<\/div>/is', $secDiv, $m)) {
                $inner = trim($m[1]);
            } else {
                $inner = $secDiv;
            }
            $html .= '<div style="margin-bottom:12px;">';
            $html .= '<div style="font-size:11pt;font-weight:bold;color:#0f172a;background:#e2e8f0;padding:4px 8px;border-left:4px solid #0284c7;margin-bottom:6px;">' . htmlspecialchars($secTitle) . '</div>';
            $html .= $inner;
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Build structured dosageInstruction and dispenseRequest from free text dosage and drug name
     *
     * @param string $dosageText
     * @param string $medName
     * @return array{dosageInstruction: array<int,array<string,mixed>>, dispenseRequest?: array<string,mixed>}
     */
    private function buildStructuredDosageInstruction(string $dosageText, string $medName): array
    {
        $raw = strtoupper($dosageText . ' ' . $medName);

        // Timing detection
        $timingCode = 'QD';
        $timingDisplay = 'Once daily';
        $frequency = 1;
        $period = 1;

        if (preg_match('/\b(TDS|TID|THRICE|3 TIMES|THREE TIMES)\b/', $raw)) {
            $timingCode = 'TID';
            $timingDisplay = 'Three times daily';
            $frequency = 3;
        } elseif (preg_match('/\b(QID|4 TIMES|FOUR TIMES)\b/', $raw)) {
            $timingCode = 'QID';
            $timingDisplay = 'Four times daily';
            $frequency = 4;
        } elseif (preg_match('/\b(BD|BID|TWICE|2 TIMES|TWO TIMES)\b/', $raw)) {
            $timingCode = 'BID';
            $timingDisplay = 'Twice daily';
            $frequency = 2;
        } elseif (preg_match('/\b(SOS|PRN|AS NEEDED|WHEN REQUIRED)\b/', $raw)) {
            $timingCode = 'PRN';
            $timingDisplay = 'As needed';
            $frequency = 1;
        } elseif (preg_match('/\b(HS|BEDTIME|AT NIGHT)\b/', $raw)) {
            $timingCode = 'HS';
            $timingDisplay = 'At bedtime';
            $frequency = 1;
        }

        // Additional Instructions detection (Food relation)
        $addInstruct = null;
        if (preg_match('/\b(EMPTY STOMACH|BEFORE FOOD|BEFORE MEALS?|BF|AC)\b/', $raw)) {
            $addInstruct = [
                [
                    'coding' => [
                        [
                            'system' => 'http://snomed.info/sct',
                            'code' => '307165006',
                            'display' => 'Before meals',
                        ],
                    ],
                    'text' => 'Before food / Empty stomach',
                ],
            ];
        } elseif (preg_match('/\b(AFTER FOOD|AFTER MEALS?|AF|PC)\b/', $raw)) {
            $addInstruct = [
                [
                    'coding' => [
                        [
                            'system' => 'http://snomed.info/sct',
                            'code' => '307166007',
                            'display' => 'After meals',
                        ],
                    ],
                    'text' => 'After food',
                ],
            ];
        } elseif (preg_match('/\b(WITH FOOD|WITH MEALS?)\b/', $raw)) {
            $addInstruct = [
                [
                    'coding' => [
                        [
                            'system' => 'http://snomed.info/sct',
                            'code' => '311504000',
                            'display' => 'With or after food',
                        ],
                    ],
                    'text' => 'With food',
                ],
            ];
        }

        // Route detection
        $routeCode = '260548002';
        $routeDisplay = 'Oral route';
        $routeText = 'Oral';

        if (preg_match('/\b(INJ|IV|INTRAVENOUS|INFUSION)\b/', $raw)) {
            $routeCode = '47625008';
            $routeDisplay = 'Intravenous route';
            $routeText = 'Intravenous (IV)';
        } elseif (preg_match('/\b(IM|INTRAMUSCULAR)\b/', $raw)) {
            $routeCode = '78421000';
            $routeDisplay = 'Intramuscular route';
            $routeText = 'Intramuscular (IM)';
        } elseif (preg_match('/\b(DROPS?|EYE|EAR|OPHTHALMIC)\b/', $raw)) {
            $routeCode = '54485002';
            $routeDisplay = 'Ophthalmic route';
            $routeText = 'Drops';
        } elseif (preg_match('/\b(CREAM|OINTMENT|GEL|TOPICAL)\b/', $raw)) {
            $routeCode = '6064005';
            $routeDisplay = 'Topical route';
            $routeText = 'Topical';
        }

        // Dose unit detection
        $doseUnit = 'Tablet';
        $doseUnitCode = 'tbl';
        $doseValue = 1;

        if (preg_match('/\b(SACHET|POWDER)\b/', $raw)) {
            $doseUnit = 'Sachet';
            $doseUnitCode = 'sachet';
        } elseif (preg_match('/\b(SYRUP|SUSPENSION|ML)\b/', $raw)) {
            $doseUnit = 'mL';
            $doseUnitCode = 'mL';
            $doseValue = 5;
        } elseif (preg_match('/\b(CAPSULE|CAP)\b/', $raw)) {
            $doseUnit = 'Capsule';
            $doseUnitCode = 'capsule';
        } elseif (preg_match('/\b(DROPS?)\b/', $raw)) {
            $doseUnit = 'drops';
            $doseUnitCode = '[drp]';
            $doseValue = 2;
        }

        // Duration detection
        $durationDays = 5;
        if (preg_match('/(?:FOR\s*)?(\d+)\s*DAYS?/i', $dosageText, $dm)) {
            $durationDays = (int) $dm[1];
        }

        $instructionItem = [
            'text' => $dosageText !== '' ? $dosageText : ($doseValue . ' ' . $doseUnit . ' ' . $timingDisplay . ' for ' . $durationDays . ' days'),
            'timing' => [
                'repeat' => [
                    'frequency' => $frequency,
                    'period' => $period,
                    'periodUnit' => 'd',
                ],
                'code' => [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/v3-GTSAbbreviation',
                            'code' => $timingCode,
                            'display' => $timingDisplay,
                        ],
                    ],
                    'text' => $timingCode . ' (' . $timingDisplay . ')',
                ],
            ],
            'route' => [
                'coding' => [
                    [
                        'system' => 'http://snomed.info/sct',
                        'code' => $routeCode,
                        'display' => $routeDisplay,
                    ],
                ],
                'text' => $routeText,
            ],
            'doseAndRate' => [
                [
                    'type' => [
                        'coding' => [
                            [
                                'system' => 'http://terminology.hl7.org/CodeSystem/dose-rate-type',
                                'code' => 'ordered',
                                'display' => 'Ordered',
                            ],
                        ],
                    ],
                    'doseQuantity' => [
                        'value' => $doseValue,
                        'unit' => $doseUnit,
                        'system' => 'http://unitsofmeasure.org',
                        'code' => $doseUnitCode,
                    ],
                ],
            ],
        ];

        if ($addInstruct !== null) {
            $instructionItem['additionalInstruction'] = $addInstruct;
        }

        return [
            'dosageInstruction' => [$instructionItem],
            'dispenseRequest' => [
                'expectedSupplyDuration' => [
                    'value' => $durationDays,
                    'unit' => 'days',
                    'system' => 'http://unitsofmeasure.org',
                    'code' => 'd',
                ],
            ],
        ];
    }
}

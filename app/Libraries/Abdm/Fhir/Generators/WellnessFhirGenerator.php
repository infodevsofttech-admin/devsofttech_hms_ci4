<?php

namespace App\Libraries\Abdm\Fhir\Generators;

class WellnessFhirGenerator extends AbstractModuleFhirGenerator
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

        $careContextReference = 'WELLNESS-' . $recordId . ($sessionId !== '0' ? '-S' . $sessionId : '') . '-' . $visitDate . '-' . date('His');
        $careContextDisplay = 'Wellness & Vitals Record ' . $visitDate;

        $builder = new \App\Libraries\Abdm\Fhir\FhirDocumentBuilder();

        $composition = $this->buildBaseComposition($source, 'Wellness Record', '94500-6', 'Health and Wellness Record');
        $composition['meta']['profile'] = ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/WellnessRecord'];
        $composition['type'] = [
            'coding' => [
                [
                    'system' => 'http://snomed.info/sct',
                    'code' => '419891008',
                    'display' => 'Wellness Record',
                ],
            ],
            'text' => 'Wellness Record',
        ];

        $orgId = (string) ($source['organization']['id'] ?? $source['hfr_id'] ?? '1');
        $composition['custodian'] = ['reference' => 'urn:uuid:organization-' . ($orgId !== '' ? $orgId : '1')];

        $patientRef = 'urn:uuid:patient-' . $patientId;

        $builder
            ->buildBundleMeta('wellness-' . $recordId . '-' . strtotime($timestamp), $timestamp)
            ->addPatient($this->buildBasePatient($source));

        $encounter = $this->buildEncounter($source);
        if (is_array($encounter)) {
            $builder->addEncounter($encounter);
            $composition['encounter'] = [
                'reference' => 'urn:uuid:' . (string) ($encounter['id'] ?? ('encounter-' . $recordId)),
                'display' => 'Encounter',
            ];
        }

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

        $encounterRef = is_array($encounter) ? 'urn:uuid:' . (string) ($encounter['id'] ?? '') : null;

        $vitalObsRefs = [];
        $bodyMeasObsRefs = [];
        $generalAssessObsRefs = [];
        $activityObsRefs = [];
        $womenObsRefs = [];
        $lifestyleObsRefs = [];
        $otherObsRefs = [];

        // Body measurement LOINC codes
        $bodyMeasLoincCodes = ['8302-2', '29463-7', '39156-5', '56115-9', '56114-2', '8280-0'];
        // General assessment LOINC codes (blood sugar, lab POC, Hb)
        $generalAssessLoincCodes = ['2339-0', '1558-6', '1521-4', '4548-4', '718-7', '25428-4', '1753-3'];
        // Physical activity LOINC codes
        $activityLoincCodes = ['55423-8', '93832-4', '55411-3', '41981-2'];

        // 1. Process Vital Signs & Standalone Measurements
        $vitalsList = (array) ($source['vitals'] ?? []);
        $sysBp = null;
        $diaBp = null;
        $standaloneVitals = [];

        foreach ($vitalsList as $vital) {
            $loincCode = (string) ($vital['loinc_code'] ?? $vital['code'] ?? '');
            $display = trim((string) ($vital['display'] ?? ''));
            if ($loincCode === '8480-6' || strcasecmp($display, 'Systolic blood pressure') === 0) {
                $sysBp = $vital;
            } elseif ($loincCode === '8462-4' || strcasecmp($display, 'Diastolic blood pressure') === 0) {
                $diaBp = $vital;
            } else {
                $standaloneVitals[] = $vital;
            }
        }

        if ($sysBp !== null || $diaBp !== null) {
            $obsId = 'obs-bp-' . $recordId;
            $vitalObsRefs[] = ['reference' => 'urn:uuid:' . $obsId];

            $bpComponents = [];
            if ($sysBp !== null && is_numeric($sysBp['value'] ?? null)) {
                $bpComponents[] = [
                    'code' => [
                        'coding' => [[
                            'system' => 'http://loinc.org',
                            'code' => '8480-6',
                            'display' => 'Systolic blood pressure',
                        ]],
                    ],
                    'valueQuantity' => [
                        'value' => (float) $sysBp['value'],
                        'unit' => 'mmHg',
                        'system' => 'http://unitsofmeasure.org',
                        'code' => 'mm[Hg]',
                    ],
                ];
            }
            if ($diaBp !== null && is_numeric($diaBp['value'] ?? null)) {
                $bpComponents[] = [
                    'code' => [
                        'coding' => [[
                            'system' => 'http://loinc.org',
                            'code' => '8462-4',
                            'display' => 'Diastolic blood pressure',
                        ]],
                    ],
                    'valueQuantity' => [
                        'value' => (float) $diaBp['value'],
                        'unit' => 'mmHg',
                        'system' => 'http://unitsofmeasure.org',
                        'code' => 'mm[Hg]',
                    ],
                ];
            }

            $bpObs = [
                'resourceType' => 'Observation',
                'id' => $obsId,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/ObservationVitalSigns']],
                'status' => 'final',
                'category' => [[
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code' => 'vital-signs',
                        'display' => 'Vital Signs',
                    ]],
                ]],
                'code' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '85354-9',
                        'display' => 'Blood pressure panel',
                    ]],
                    'text' => 'Blood Pressure',
                ],
                'subject' => ['reference' => $patientRef],
                'effectiveDateTime' => $timestamp,
            ];

            if ($encounterRef) {
                $bpObs['encounter'] = ['reference' => $encounterRef];
            }
            if (! empty($bpComponents)) {
                $bpObs['component'] = $bpComponents;
            }

            $builder->addObservation($bpObs);
        }

        foreach ($standaloneVitals as $idx => $vital) {
            $display = trim((string) ($vital['display'] ?? ''));
            $val = $vital['value'] ?? null;
            if ($val === null || trim((string) $val) === '') {
                continue;
            }

            $loincCode = (string) ($vital['loinc_code'] ?? $vital['code'] ?? '');
            $unit = (string) ($vital['unit'] ?? '');
            $ucumCode = (string) ($vital['ucum_code'] ?? $vital['ucum'] ?? '');

            $loinc = $this->codingResolver->resolveLoincForLabTest($loincCode, $display);
            $ucum = $this->codingResolver->resolveUnitUcUM($ucumCode ?: $unit);

            $obsId = 'obs-vital-' . $recordId . '-' . $idx;
            $numericVal = is_numeric($val) ? (float) $val : null;
            if ($numericVal !== null) {
                if ($loincCode === '8310-5' || strcasecmp($display, 'Body temperature') === 0 || in_array($unit, ['Cel', 'degF', '[degF]'], true)) {
                    $numericVal = round($numericVal, 1);
                } elseif ($loincCode === '39156-5' || strcasecmp($display, 'Body Mass Index') === 0) {
                    $numericVal = round($numericVal, 1);
                } elseif ($loincCode === '29463-7' || strcasecmp($display, 'Body weight') === 0) {
                    $numericVal = round($numericVal, 2);
                } elseif ($loincCode === '8302-2' || strcasecmp($display, 'Body height') === 0) {
                    $numericVal = round($numericVal, 1);
                } elseif ($loincCode === '56115-9' || $loincCode === '56114-2') {
                    $numericVal = round($numericVal, 1);
                } elseif ($loincCode === '8280-0' || $loincCode === '4548-4') {
                    $numericVal = round($numericVal, 2);
                } elseif (in_array($loincCode, ['8867-4', '59408-5', '9279-1', '2339-0', '1558-6', '1521-4'], true)) {
                    $numericVal = (float) round($numericVal);
                } else {
                    $numericVal = round($numericVal, 2);
                }
            }

            // Determine target composition section & profile
            if (in_array($loincCode, $bodyMeasLoincCodes, true)) {
                $categoryCode = 'vital-signs';
                $categoryDisplay = 'Vital Signs';
                $profile = 'https://nrces.in/ndhm/fhir/r4/StructureDefinition/Observation';
                $bodyMeasObsRefs[] = ['reference' => 'urn:uuid:' . $obsId];
            } elseif (in_array($loincCode, $generalAssessLoincCodes, true)) {
                $categoryCode = 'laboratory';
                $categoryDisplay = 'Laboratory';
                $profile = 'https://nrces.in/ndhm/fhir/r4/StructureDefinition/Observation';
                $generalAssessObsRefs[] = ['reference' => 'urn:uuid:' . $obsId];
            } elseif (in_array($loincCode, $activityLoincCodes, true)) {
                $categoryCode = 'activity';
                $categoryDisplay = 'Activity';
                $profile = 'https://nrces.in/ndhm/fhir/r4/StructureDefinition/Observation';
                $activityObsRefs[] = ['reference' => 'urn:uuid:' . $obsId];
            } else {
                $categoryCode = 'vital-signs';
                $categoryDisplay = 'Vital Signs';
                $profile = 'https://nrces.in/ndhm/fhir/r4/StructureDefinition/ObservationVitalSigns';
                $vitalObsRefs[] = ['reference' => 'urn:uuid:' . $obsId];
            }

            $builder->addObservation([
                'resourceType' => 'Observation',
                'id' => $obsId,
                'meta' => ['profile' => [$profile]],
                'status' => 'final',
                'category' => [[
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code' => $categoryCode,
                        'display' => $categoryDisplay,
                    ]],
                ]],
                'code' => [
                    'coding' => $loinc['coding'] ?? [[
                        'system' => 'http://loinc.org',
                        'code' => $loincCode ?: '8716-3',
                        'display' => $display,
                    ]],
                    'text' => $display,
                ],
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'effectiveDateTime' => $timestamp,
                'valueQuantity' => [
                    'value' => $numericVal !== null ? $numericVal : (string) $val,
                    'unit' => $unit,
                    'system' => 'http://unitsofmeasure.org',
                    'code' => (string) ($ucum['code'] ?? $ucumCode ?: $unit),
                ],
            ]);
        }

        // 2. Process Physical Examination / General Assessments Findings
        $examList = (array) ($source['physical_examination'] ?? $source['findings'] ?? $source['general_assessments'] ?? []);
        foreach ($examList as $idx => $examItem) {
            $text = trim((string) (is_array($examItem) ? ($examItem['text'] ?? $examItem['display'] ?? '') : $examItem));
            if (! $this->isMeaningfulValue($text)) {
                continue;
            }

            $obsId = 'exam-' . $recordId . '-' . $idx;
            $generalAssessObsRefs[] = ['reference' => 'urn:uuid:' . $obsId];

            $builder->addObservation([
                'resourceType' => 'Observation',
                'id' => $obsId,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Observation']],
                'status' => 'final',
                'category' => [[
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code' => 'exam',
                        'display' => 'Exam',
                    ]],
                ]],
                'code' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '29545-1',
                        'display' => 'Physical findings',
                    ]],
                    'text' => 'Physical Examination',
                ],
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'effectiveDateTime' => $timestamp,
                'valueString' => $text,
            ]);
        }

        // 3. Process Women Wellness / Reproductive Health (LMP, Pregnancy, etc.)
        $womenWellness = (array) ($source['women_wellness'] ?? []);
        $lmpDate = trim((string) ($womenWellness['lmp'] ?? $source['lmp'] ?? ''));
        if ($lmpDate !== '') {
            $obsId = 'women-lmp-' . $recordId;
            $womenObsRefs[] = ['reference' => 'urn:uuid:' . $obsId];

            $builder->addObservation([
                'resourceType' => 'Observation',
                'id' => $obsId,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Observation']],
                'status' => 'final',
                'category' => [[
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code' => 'social-history',
                        'display' => 'Social History',
                    ]],
                ]],
                'code' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '8665-2',
                        'display' => 'Last menstrual period start date',
                    ]],
                    'text' => 'Last Menstrual Period (LMP)',
                ],
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'effectiveDateTime' => $timestamp,
                'valueDateTime' => date(DATE_ATOM, strtotime($lmpDate) ?: time()),
            ]);
        }

        foreach ($womenWellness as $key => $valStr) {
            if ($key === 'lmp' || ! is_string($valStr) || trim($valStr) === '') {
                continue;
            }

            $obsId = 'women-obs-' . $recordId . '-' . $key;
            $womenObsRefs[] = ['reference' => 'urn:uuid:' . $obsId];

            $builder->addObservation([
                'resourceType' => 'Observation',
                'id' => $obsId,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Observation']],
                'status' => 'final',
                'category' => [[
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code' => 'social-history',
                        'display' => 'Social History',
                    ]],
                ]],
                'code' => [
                    'text' => ucwords(str_replace('_', ' ', $key)),
                ],
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'effectiveDateTime' => $timestamp,
                'valueString' => trim($valStr),
            ]);
        }

        // 4. Process General Wellness / Lifestyle & Diet Advice
        $adviceItems = (array) ($source['advice'] ?? $source['general_advice'] ?? $source['lifestyle'] ?? []);
        foreach ($adviceItems as $idx => $adviceText) {
            $text = trim((string) (is_array($adviceText) ? ($adviceText['text'] ?? ($adviceText['value'] ?? ($adviceText['display'] ?? ''))) : $adviceText));
            if (! $this->isMeaningfulValue($text)) {
                continue;
            }

            $codeStr = is_array($adviceText) ? ($adviceText['code'] ?? '') : '';
            $displayStr = is_array($adviceText) ? ($adviceText['display'] ?? '') : '';

            $obsId = 'wellness-social-' . $recordId . '-' . $idx;

            if ($codeStr === 'general-nursing' || str_contains(strtolower($displayStr), 'nursing')) {
                $otherObsRefs[] = ['reference' => 'urn:uuid:' . $obsId];
                $obsTitle = 'General Nursing Observations';
            } else {
                $lifestyleObsRefs[] = ['reference' => 'urn:uuid:' . $obsId];
                $obsTitle = ! empty($displayStr) ? $displayStr : 'Lifestyle & General Wellness Advice';
            }

            $builder->addObservation([
                'resourceType' => 'Observation',
                'id' => $obsId,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Observation']],
                'status' => 'final',
                'category' => [[
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code' => 'social-history',
                        'display' => 'Social History',
                    ]],
                ]],
                'code' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '8670-2',
                        'display' => 'History of Social history',
                    ]],
                    'text' => $obsTitle,
                ],
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'effectiveDateTime' => $timestamp,
                'valueString' => $text,
            ]);
        }

        // Build Composition Sections strictly adhering to NRCES ABDM FHIR R4 Implementation Guide (v6.5.0)
        $sections = [];
        if (! empty($vitalObsRefs)) {
            $sections[] = [
                'title' => 'Vital Signs',
                'code' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '8716-3',
                        'display' => 'Vital signs',
                    ]],
                ],
                'entry' => $vitalObsRefs,
            ];
        }

        if (! empty($bodyMeasObsRefs)) {
            $sections[] = [
                'title' => 'Body Measurement',
                'entry' => $bodyMeasObsRefs,
            ];
        }

        if (! empty($generalAssessObsRefs)) {
            $sections[] = [
                'title' => 'General Assessment',
                'entry' => $generalAssessObsRefs,
            ];
        }

        if (! empty($activityObsRefs)) {
            $sections[] = [
                'title' => 'Physical Activity',
                'entry' => $activityObsRefs,
            ];
        }

        if (! empty($womenObsRefs)) {
            $sections[] = [
                'title' => 'Women Health',
                'entry' => $womenObsRefs,
            ];
        }

        if (! empty($lifestyleObsRefs)) {
            $sections[] = [
                'title' => 'Lifestyle',
                'entry' => $lifestyleObsRefs,
            ];
        }

        if (! empty($otherObsRefs)) {
            $sections[] = [
                'title' => 'Other Observations',
                'entry' => $otherObsRefs,
            ];
        }

        if (empty($sections)) {
            $dummyObsId = 'wellness-note-' . $recordId;
            $builder->addObservation([
                'resourceType' => 'Observation',
                'id' => $dummyObsId,
                'status' => 'final',
                'code' => ['text' => 'General Wellness Note'],
                'subject' => ['reference' => $patientRef, 'display' => (string) ($source['patient']['name'] ?? 'Patient')],
                'effectiveDateTime' => $timestamp,
                'valueString' => 'General Wellness & Vitals Assessment for ' . $visitDate,
            ]);
            $sections[] = [
                'title' => 'Vital Signs',
                'code' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '8716-3',
                        'display' => 'Vital signs',
                    ]],
                ],
                'entry' => [['reference' => 'urn:uuid:' . $dummyObsId]],
            ];
        }

        $composition['section'] = $sections;
        $builder->buildComposition($composition);

        $bundle = $builder->toBundle();
        $validation = $this->validator->validate($bundle, 'wellness', ['resolved' => 1, 'unresolved' => 0, 'fallback_used' => 0]);

        return [
            'hi_type' => 'WellnessRecord',
            'care_context_reference' => $careContextReference,
            'care_context_display' => $careContextDisplay,
            'fhir_bundle' => $bundle,
            'validation' => $validation->toArray(),
        ];
    }
}


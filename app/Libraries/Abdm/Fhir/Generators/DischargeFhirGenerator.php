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

        $careContextReference = 'DISCHARGE-' . $recordId . '-S' . $sessionId . '-' . $visitDate;
        $careContextDisplay = 'Discharge Summary ' . $visitDate;

        $builder = new \App\Libraries\Abdm\Fhir\FhirDocumentBuilder();
        $builder
            ->buildBundleMeta($bundleIdentifier, $timestamp)
            ->updateBundleMeta(['meta' => ['profile' => [
                'https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentBundle',
            ]]])
            ->buildComposition($this->buildBaseComposition($source, 'IPD Discharge Summary', '18842-5', 'Discharge summary'))
            ->updateComposition(['meta' => ['profile' => [
                'https://nrces.in/ndhm/fhir/r4/StructureDefinition/DischargeSummaryRecord',
            ]]])
            ->addPatient($this->buildBasePatient($source));

        $encounter = $this->buildEncounter($source);
        if (is_array($encounter)) {
            $builder->addEncounter($encounter);
        }

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

        foreach ((array) ($source['conditions'] ?? []) as $idx => $cond) {
            $text = trim((string) ($cond['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $resolved = $this->codingResolver->resolveSnomedForDiagnosisOrFinding((string) ($cond['code'] ?? ''), $text);
            $coding = $this->withoutPlaceholderCoding((array) ($resolved['coding'] ?? []));
            $builder->addCondition([
                'resourceType' => 'Condition',
                'id' => 'discharge-condition-' . $recordId . '-' . $idx,
                'code' => [
                    'coding' => $coding,
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
            $coding = $this->withoutPlaceholderCoding((array) ($resolved['coding'] ?? []));
            $builder->addProcedure([
                'resourceType' => 'Procedure',
                'id' => 'discharge-procedure-' . $recordId . '-' . $idx,
                'status' => 'completed',
                'code' => [
                    'coding' => $coding,
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

        foreach ((array) ($source['documents'] ?? []) as $idx => $document) {
            $data = trim((string) ($document['data'] ?? ''));
            if ($data === '') {
                continue;
            }

            $builder->addDocumentReference([
                'resourceType' => 'DocumentReference',
                'id' => 'discharge-document-' . $recordId . '-' . $idx,
                'status' => 'current',
                'type' => ['coding' => [[
                    'system' => 'http://loinc.org',
                    'code' => (string) ($document['loinc_code'] ?? '55107-7'),
                    'display' => (string) ($document['title'] ?? 'Clinical document'),
                ]]],
                'subject' => ['reference' => $patientRef],
                'date' => (string) ($document['created_at'] ?? $timestamp),
                'author' => is_array($practitioner) ? [[
                    'reference' => 'urn:uuid:' . (string) $practitioner['id'],
                ]] : null,
                'custodian' => is_array($organization) ? [
                    'reference' => 'urn:uuid:' . (string) $organization['id'],
                ] : null,
                'description' => (string) ($document['title'] ?? 'Clinical document'),
                'content' => [[
                    'attachment' => [
                        'contentType' => (string) ($document['content_type'] ?? 'application/pdf'),
                        'language' => 'en-IN',
                        'data' => $data,
                        'title' => (string) ($document['title'] ?? 'Clinical document'),
                        'creation' => (string) ($document['created_at'] ?? $timestamp),
                    ],
                ]],
            ]);
        }

        $sectionDefinitions = [
            ['title' => 'Diagnosis', 'code' => '11450-4', 'prefix' => 'discharge-condition-', 'items' => (array) ($source['conditions'] ?? [])],
            ['title' => 'Procedures', 'code' => '47519-4', 'prefix' => 'discharge-procedure-', 'items' => (array) ($source['procedures'] ?? [])],
            ['title' => 'Discharge medications', 'code' => '10183-2', 'prefix' => 'discharge-medication-', 'items' => (array) ($source['medications'] ?? [])],
            ['title' => 'Observations', 'code' => '8716-3', 'prefix' => 'discharge-observation-', 'items' => (array) ($source['observations'] ?? [])],
            ['title' => 'Investigations', 'code' => '18776-5', 'prefix' => 'discharge-investigation-', 'items' => (array) ($source['investigations'] ?? [])],
            ['title' => 'Allergies', 'code' => '48765-2', 'prefix' => 'discharge-allergy-', 'items' => (array) ($source['allergies'] ?? [])],
            ['title' => 'Discharge advice', 'code' => '8653-8', 'prefix' => 'discharge-careplan-', 'items' => (array) ($source['care_plans'] ?? [])],
            ['title' => 'Attached documents', 'code' => '55107-7', 'prefix' => 'discharge-document-', 'items' => (array) ($source['documents'] ?? [])],
        ];
        $sections = [];
        foreach ($sectionDefinitions as $definition) {
            $references = [];
            $narrativeParts = [];
            foreach ($definition['items'] as $idx => $item) {
                $references[] = ['reference' => 'urn:uuid:' . $definition['prefix'] . $recordId . '-' . $idx];
                $narrative = $this->sectionItemNarrative((array) $item);
                if ($narrative !== '') {
                    $narrativeParts[] = $narrative;
                }
            }
            if ($references === []) {
                continue;
            }
            $sections[] = [
                'title' => $definition['title'],
                'code' => ['coding' => [[
                    'system' => 'http://loinc.org',
                    'code' => $definition['code'],
                ]]],
                'entry' => $references,
                'text' => $narrativeParts !== [] ? [
                    'status' => 'generated',
                    'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><ul><li>'
                        . implode('</li><li>', array_map('htmlspecialchars', $narrativeParts))
                        . '</li></ul></div>',
                ] : null,
            ];
        }
        if ($sections !== []) {
            $builder->updateComposition(['section' => $sections]);
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
        foreach (['text', 'description', 'title', 'name', 'value'] as $field) {
            $value = trim((string) ($item[$field] ?? ''));
            if ($value !== '') {
                if ($field === 'name') {
                    $dosage = trim((string) ($item['dosage'] ?? ''));
                    return trim($value . ($dosage !== '' ? ' - ' . $dosage : ''));
                }
                return trim(strip_tags($value));
            }
        }

        return '';
    }
}

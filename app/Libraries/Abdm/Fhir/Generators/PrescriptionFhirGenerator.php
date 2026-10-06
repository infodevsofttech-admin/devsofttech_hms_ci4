<?php

namespace App\Libraries\Abdm\Fhir\Generators;

class PrescriptionFhirGenerator extends AbstractModuleFhirGenerator
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

        $careContextReference = 'PRESCRIPTION-' . $recordId . ($sessionId !== '0' ? '-S' . $sessionId : '') . '-' . $visitDate;
        $careContextDisplay = 'Prescription ' . $visitDate;

        $builder = new \App\Libraries\Abdm\Fhir\FhirDocumentBuilder();
        $builder
            ->buildBundleMeta('prescription-' . $recordId . '-' . strtotime($timestamp), $timestamp)
            ->addPatient($this->buildBasePatient($source));

        $patientRef = 'urn:uuid:patient-' . $patientId;

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

        $encounter = $this->buildEncounter($source);
        $encounterRef = null;
        if (is_array($encounter)) {
            $builder->addEncounter($encounter);
            $encounterRef = 'urn:uuid:' . (string) $encounter['id'];
        }

        $medicationRefs = [];
        $resolvedCount = 0;
        $unresolvedCount = 0;
        $fallbackCount = 0;

        foreach ((array) ($source['medications'] ?? []) as $idx => $med) {
            $drugName = trim((string) ($med['name'] ?? $med['drug_name'] ?? ''));
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

            $code = trim((string) ($med['code'] ?? $med['snomed_code'] ?? ''));
            $coding = [];
            if ($code !== '' && $code !== '371530004') {
                $coding[] = [
                    'system' => 'http://snomed.info/sct',
                    'code' => $code,
                    'display' => $drugName,
                ];
                $resolvedCount++;
            } else {
                $coding[] = [
                    'system' => 'http://snomed.info/sct',
                    'code' => '105904009',
                    'display' => $drugName,
                ];
                $fallbackCount++;
            }

            $medId = 'medication-' . $recordId . '-' . $idx;
            $medicationRefs[] = [
                'reference' => 'urn:uuid:' . $medId,
                'type' => 'MedicationRequest',
            ];

            $medResource = [
                'resourceType' => 'MedicationRequest',
                'id' => $medId,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/MedicationRequest']],
                'status' => 'active',
                'intent' => 'order',
                'subject' => ['reference' => $patientRef],
                'authoredOn' => $timestamp,
                'requester' => ['reference' => 'urn:uuid:' . (string) $practitioner['id']],
                'medicationCodeableConcept' => [
                    'coding' => $coding,
                    'text' => $drugName,
                ],
                'dosageInstruction' => $dosageInstruction,
            ];

            if ($encounterRef !== null) {
                $medResource['encounter'] = ['reference' => $encounterRef];
            }

            $builder->addMedicationRequest($medResource);
        }

        $sectionEntries = $medicationRefs;

        // Support attached scanned or digital prescription copy (Binary or DocumentReference)
        $docData = (string) ($source['prescription_pdf_base64'] ?? $source['document_data_base64'] ?? $source['pdf_base64'] ?? '');
        if ($docData !== '') {
            $docRefId = 'prescription-doc-' . $recordId;
            $builder->addDocumentReference([
                'resourceType' => 'DocumentReference',
                'id' => $docRefId,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentReference']],
                'status' => 'current',
                'docStatus' => 'final',
                'type' => [
                    'coding' => [[
                        'system' => 'http://snomed.info/sct',
                        'code' => '440545006',
                        'display' => 'Prescription record',
                    ]],
                    'text' => 'Prescription PDF',
                ],
                'subject' => ['reference' => $patientRef],
                'date' => $timestamp,
                'description' => 'Prescription Copy PDF',
                'content' => [[
                    'attachment' => [
                        'contentType' => 'application/pdf',
                        'language' => 'en-IN',
                        'data' => $docData,
                        'title' => 'Prescription.pdf',
                        'creation' => $timestamp,
                    ],
                ]],
            ]);
            $sectionEntries[] = [
                'reference' => 'urn:uuid:' . $docRefId,
                'type' => 'DocumentReference',
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
                    'https://nrces.in/ndhm/fhir/r4/StructureDefinition/PrescriptionRecord',
                ],
            ],
            'status' => 'final',
            'type' => [
                'coding' => [[
                    'system' => 'http://snomed.info/sct',
                    'code' => '440545006',
                    'display' => 'Prescription record',
                ]],
                'text' => 'Prescription record',
            ],
            'title' => 'Prescription record',
            'date' => $timestamp,
            'subject' => [
                'reference' => $patientRef,
                'display' => (string) ($source['patient']['name'] ?? 'Patient'),
            ],
            'author' => [[
                'reference' => $authorRef,
                'display' => $authorDisplay,
            ]],
            'custodian' => [
                'reference' => $custodianRef,
                'display' => (string) ($organization['name'] ?? 'Hospital'),
            ],
            'section' => [
                [
                    'title' => 'Prescription record',
                    'code' => [
                        'coding' => [[
                            'system' => 'http://snomed.info/sct',
                            'code' => '440545006',
                            'display' => 'Prescription record',
                        ]],
                    ],
                    'entry' => $sectionEntries,
                ],
            ],
        ];

        if ($encounterRef !== null) {
            $composition['encounter'] = ['reference' => $encounterRef];
        }

        $builder->buildComposition($composition);

        $bundle = $builder->toBundle();
        $validation = $this->validator->validate($bundle, 'prescription', [
            'resolved' => $resolvedCount,
            'unresolved' => $unresolvedCount,
            'fallback_used' => $fallbackCount,
        ]);

        return [
            'hi_type' => 'PrescriptionRecord',
            'care_context_reference' => $careContextReference,
            'care_context_display' => $careContextDisplay,
            'fhir_bundle' => $bundle,
            'validation' => $validation->toArray(),
        ];
    }
}

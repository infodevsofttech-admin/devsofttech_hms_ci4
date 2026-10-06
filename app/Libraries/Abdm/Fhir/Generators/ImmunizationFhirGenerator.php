<?php

namespace App\Libraries\Abdm\Fhir\Generators;

class ImmunizationFhirGenerator extends AbstractModuleFhirGenerator
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
        $visitDate = (string) ($source['visit_date'] ?? date('Y-m-d'));

        $careContextReference = 'IMMUNIZATION-' . $recordId . '-' . $visitDate;
        $careContextDisplay = 'Immunization ' . $visitDate;

        $builder = new \App\Libraries\Abdm\Fhir\FhirDocumentBuilder();
        $builder
            ->buildBundleMeta('immunization-' . $recordId . '-' . strtotime($timestamp), $timestamp)
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

        $immunizationRefs = [];
        $resolvedCount = 0;
        $unresolvedCount = 0;
        $fallbackCount = 0;

        foreach ((array) ($source['immunizations'] ?? $source['vaccines'] ?? []) as $idx => $imm) {
            $vaccineName = trim((string) ($imm['vaccine_name'] ?? $imm['name'] ?? ''));
            if (! $this->isMeaningfulValue($vaccineName)) {
                continue;
            }

            $code = trim((string) ($imm['code'] ?? $imm['snomed_code'] ?? ''));
            $coding = [];
            if ($code !== '') {
                $coding[] = [
                    'system' => 'http://snomed.info/sct',
                    'code' => $code,
                    'display' => $vaccineName,
                ];
                $resolvedCount++;
            } else {
                $coding[] = [
                    'system' => 'http://snomed.info/sct',
                    'code' => '41000179103',
                    'display' => $vaccineName,
                ];
                $fallbackCount++;
            }

            $immId = 'immunization-' . $recordId . '-' . $idx;
            $immunizationRefs[] = [
                'reference' => 'urn:uuid:' . $immId,
                'type' => 'Immunization',
            ];

            $immResource = [
                'resourceType' => 'Immunization',
                'id' => $immId,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Immunization']],
                'status' => 'completed',
                'vaccineCode' => [
                    'coding' => $coding,
                    'text' => $vaccineName,
                ],
                'patient' => ['reference' => $patientRef],
                'occurrenceDateTime' => (string) ($imm['occurrence_date'] ?? $imm['given_date'] ?? $timestamp),
                'primarySource' => true,
            ];

            $builder->addResource($immResource);
        }

        $sectionEntries = $immunizationRefs;

        // Support certificate / scan document reference
        $docData = (string) ($source['certificate_pdf_base64'] ?? $source['document_data_base64'] ?? $source['pdf_base64'] ?? '');
        if ($docData !== '') {
            $docRefId = 'immunization-doc-' . $recordId;
            $builder->addDocumentReference([
                'resourceType' => 'DocumentReference',
                'id' => $docRefId,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentReference']],
                'status' => 'current',
                'docStatus' => 'final',
                'type' => [
                    'coding' => [[
                        'system' => 'http://snomed.info/sct',
                        'code' => '41000179103',
                        'display' => 'Immunization record',
                    ]],
                    'text' => 'Immunization Certificate PDF',
                ],
                'subject' => ['reference' => $patientRef],
                'date' => $timestamp,
                'description' => 'Vaccination Certificate PDF',
                'content' => [[
                    'attachment' => [
                        'contentType' => 'application/pdf',
                        'language' => 'en-IN',
                        'data' => $docData,
                        'title' => 'Vaccine Certificate.pdf',
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
                    'https://nrces.in/ndhm/fhir/r4/StructureDefinition/ImmunizationRecord',
                ],
            ],
            'status' => 'final',
            'type' => [
                'coding' => [[
                    'system' => 'http://snomed.info/sct',
                    'code' => '41000179103',
                    'display' => 'Immunization record',
                ]],
                'text' => 'Immunization record',
            ],
            'title' => 'Immunization record',
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
                    'title' => 'Immunization record',
                    'code' => [
                        'coding' => [[
                            'system' => 'http://snomed.info/sct',
                            'code' => '41000179103',
                            'display' => 'Immunization record',
                        ]],
                    ],
                    'entry' => $sectionEntries,
                ],
            ],
        ];

        $builder->buildComposition($composition);

        $bundle = $builder->toBundle();
        $validation = $this->validator->validate($bundle, 'immunization', [
            'resolved' => $resolvedCount,
            'unresolved' => $unresolvedCount,
            'fallback_used' => $fallbackCount,
        ]);

        return [
            'hi_type' => 'ImmunizationRecord',
            'care_context_reference' => $careContextReference,
            'care_context_display' => $careContextDisplay,
            'fhir_bundle' => $bundle,
            'validation' => $validation->toArray(),
        ];
    }
}

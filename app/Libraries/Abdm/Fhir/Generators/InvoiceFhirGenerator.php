<?php

namespace App\Libraries\Abdm\Fhir\Generators;

class InvoiceFhirGenerator extends \App\Libraries\Abdm\Fhir\Generators\AbstractModuleFhirGenerator
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

        $careContextReference = 'INVOICE-' . $recordId . '-S' . $sessionId . '-' . $visitDate;
        $careContextDisplay = 'Invoice Summary ' . $visitDate;

        $builder = new \App\Libraries\Abdm\Fhir\FhirDocumentBuilder();
        $builder
            ->buildBundleMeta('invoice-' . $recordId . '-' . strtotime($timestamp), $timestamp)
            ->buildComposition($this->buildBaseComposition($source, 'Invoice Summary', '56445-0', 'Medication summary document'))
            ->addPatient($this->buildBasePatient($source));

        $encounter = $this->buildEncounter($source);
        if (is_array($encounter)) {
            $builder->addEncounter($encounter);
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

        $claimItems = [];
        foreach ((array) ($source['line_items'] ?? []) as $idx => $item) {
            $claimItems[] = [
                'sequence' => $idx + 1,
                'productOrService' => [
                    'text' => trim((string) ($item['name'] ?? '')), 
                ],
                'quantity' => [
                    'value' => (float) ($item['qty'] ?? 1),
                ],
                'unitPrice' => [
                    'value' => (float) ($item['rate'] ?? 0),
                    'currency' => (string) ($source['currency'] ?? 'INR'),
                ],
                'net' => [
                    'value' => (float) ($item['amount'] ?? 0),
                    'currency' => (string) ($source['currency'] ?? 'INR'),
                ],
            ];
        }

        $claimId = 'claim-' . $recordId;
        $builder->addClaim([
            'resourceType' => 'Claim',
            'id' => $claimId,
            'status' => 'active',
            'type' => [
                'coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/claim-type',
                    'code' => 'professional',
                ]],
            ],
            'use' => 'claim',
            'patient' => [
                'reference' => 'urn:uuid:patient-' . $patientId,
            ],
            'created' => $timestamp,
            'provider' => [
                'display' => trim((string) ($source['organization']['name'] ?? 'Hospital')),
            ],
            'priority' => [
                'coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/processpriority',
                    'code' => 'normal',
                ]],
            ],
            'item' => $claimItems,
            'total' => [
                'value' => (float) ($source['total_amount'] ?? 0),
                'currency' => (string) ($source['currency'] ?? 'INR'),
            ],
            'identifier' => [[
                'system' => 'https://hms.local/invoice-id',
                'value' => (string) ($source['invoice_no'] ?? $recordId),
            ]],
        ]);

        $sectionEntries = [
            [
                'reference' => 'urn:uuid:' . $claimId,
                'type' => 'Invoice',
            ],
        ];

        // Add DocumentReference + Binary if Invoice PDF / base64 is provided in $source
        $docData = (string) ($source['invoice_pdf_base64'] ?? $source['document_data_base64'] ?? $source['pdf_base64'] ?? '');
        if ($docData !== '') {
            $docRefId = 'invoice-doc-' . $recordId;
            $builder->addDocumentReference([
                'resourceType' => 'DocumentReference',
                'id' => $docRefId,
                'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentReference']],
                'status' => 'current',
                'docStatus' => 'final',
                'type' => [
                    'coding' => [[
                        'system' => 'http://loinc.org',
                        'code' => '56445-0',
                        'display' => 'Medication summary document',
                    ]],
                    'text' => 'Invoice Receipt PDF',
                ],
                'subject' => ['reference' => 'urn:uuid:patient-' . $patientId],
                'date' => $timestamp,
                'description' => 'Billing Invoice PDF',
                'content' => [[
                    'attachment' => [
                        'contentType' => 'application/pdf',
                        'language' => 'en-IN',
                        'data' => $docData,
                        'title' => 'Billing Invoice.pdf',
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

        $builder->updateComposition([
            'meta' => [
                'versionId' => '1',
                'lastUpdated' => $timestamp,
                'profile' => [
                    'https://nrces.in/ndhm/fhir/r4/StructureDefinition/InvoiceRecord',
                ],
            ],
            'type' => [
                'text' => 'Invoice Record',
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
                    'title' => 'Invoice Details',
                    'entry' => $sectionEntries,
                ],
            ],
        ]);

        $bundle = $builder->toBundle();
        $validation = $this->validator->validate($bundle, 'invoice', ['resolved' => 1, 'unresolved' => 0, 'fallback_used' => 0]);

        return [
            'hi_type' => 'InvoiceRecord',
            'care_context_reference' => $careContextReference,
            'care_context_display' => $careContextDisplay,
            'fhir_bundle' => $bundle,
            'validation' => $validation->toArray(),
        ];
    }
}

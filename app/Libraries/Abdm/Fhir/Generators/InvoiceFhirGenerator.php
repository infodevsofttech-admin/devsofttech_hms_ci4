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

        $builder->addClaim([
            'resourceType' => 'Claim',
            'id' => 'claim-' . $recordId,
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
                'display' => trim((string) ($source['organization']['name'] ?? '')),
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

        $organization = $this->buildOrganization($source);
        if (is_array($organization)) {
            $builder->addOrganization($organization);
        }

        $practitioner = $this->buildPractitioner($source);
        if (is_array($practitioner)) {
            $builder->addPractitioner($practitioner);
        }

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

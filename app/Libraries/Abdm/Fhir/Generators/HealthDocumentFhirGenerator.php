<?php

namespace App\Libraries\Abdm\Fhir\Generators;

class HealthDocumentFhirGenerator extends AbstractModuleFhirGenerator
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

        $docTitle = $this->cleanPlainText((string) ($source['document_title'] ?? $source['title'] ?? 'Medical Certificate'));
        if ($docTitle === '') {
            $docTitle = 'Medical Certificate';
        }

        $careContextReference = 'DOC-' . $recordId . '-' . $visitDate;
        $careContextDisplay = $docTitle . ' (' . $visitDate . ')';

        $builder = new \App\Libraries\Abdm\Fhir\FhirDocumentBuilder();
        
        // Build Composition
        $composition = $this->buildBaseComposition($source, $docTitle, '41988-7', 'Medical statement');
        
        // Add Section linking to DocumentReference
        $docRefId = 'health-document-' . $recordId;
        $composition['section'] = [[
            'title' => $docTitle,
            'code' => [
                'coding' => [[
                    'system' => 'http://loinc.org',
                    'code' => '41988-7',
                    'display' => 'Medical statement',
                ]],
            ],
            'entry' => [['reference' => 'urn:uuid:' . $docRefId]],
        ]];

        $builder
            ->buildBundleMeta('health-doc-' . $recordId . '-' . strtotime($timestamp), $timestamp)
            ->buildComposition($composition)
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

        // Attachment payload (PDF base64 or raw HTML/Text content)
        $docData = (string) ($source['document_data_base64'] ?? '');
        $contentType = 'application/pdf';

        if ($docData === '') {
            $rawContent = (string) ($source['document_content_html'] ?? $source['raw_data'] ?? '');
            if ($rawContent !== '') {
                $docData = base64_encode($rawContent);
                $contentType = 'text/html';
            } else {
                $docData = base64_encode('Medical Certificate Document #' . $recordId);
                $contentType = 'text/plain';
            }
        }

        $builder->addDocumentReference([
            'resourceType' => 'DocumentReference',
            'id' => $docRefId,
            'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentReference']],
            'status' => 'current',
            'docStatus' => 'final',
            'type' => [
                'coding' => [[
                    'system' => 'http://loinc.org',
                    'code' => '41988-7',
                    'display' => 'Medical statement',
                ]],
                'text' => $docTitle,
            ],
            'subject' => ['reference' => $patientRef],
            'date' => $timestamp,
            'description' => $docTitle,
            'content' => [[
                'attachment' => [
                    'contentType' => $contentType,
                    'language' => 'en-IN',
                    'data' => $docData,
                    'title' => $docTitle,
                    'creation' => $timestamp,
                ],
            ]],
        ]);

        $bundle = $builder->toBundle();
        $validation = $this->validator->validate($bundle, 'health_document', [
            'resolved' => 1,
            'unresolved' => 0,
            'fallback_used' => 0,
        ]);

        return [
            'hi_type' => 'HealthDocumentRecord',
            'care_context_reference' => $careContextReference,
            'care_context_display' => $careContextDisplay,
            'fhir_bundle' => $bundle,
            'validation' => $validation->toArray(),
        ];
    }
}

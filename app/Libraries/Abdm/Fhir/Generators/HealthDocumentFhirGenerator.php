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

        $careContextReference = 'DOC-' . $recordId . '-' . $visitDate . '-' . date('His');
        $careContextDisplay = $docTitle . ' (' . $visitDate . ')';

        $builder = new \App\Libraries\Abdm\Fhir\FhirDocumentBuilder();
        
        // Build Composition
        $composition = $this->buildBaseComposition($source, $docTitle, '41988-7', 'Medical statement');
        $composition['meta'] = ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/HealthDocumentRecord']];
        
        // Add Section linking to DocumentReference
        $docRefId = 'health-document-' . $recordId;
        $composition['section'] = [[
            'title' => $docTitle,
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://snomed.info/sct',
                        'code' => '419891008',
                        'display' => 'Record artifact',
                    ],
                    [
                        'system' => 'http://loinc.org',
                        'code' => '41988-7',
                        'display' => 'Medical statement',
                    ],
                ],
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
        $contentType = (string) ($source['content_type'] ?? 'application/pdf');

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

        $rawBytes = base64_decode($docData);
        if ($rawBytes === '' || ! str_starts_with($rawBytes, '%PDF')) {
            $pdfBytes = $this->convertToPdfBytes($rawBytes, $contentType, $docTitle);
            if ($pdfBytes !== '') {
                $rawBytes = $pdfBytes;
                $docData = base64_encode($pdfBytes);
                $contentType = 'application/pdf';
            }
        } else {
            $contentType = 'application/pdf';
        }

        $fileSize = is_string($rawBytes) ? strlen($rawBytes) : 0;
        $sha1Hash = is_string($rawBytes) && $rawBytes !== '' ? base64_encode(sha1($rawBytes, true)) : '';

        $orgId = (string) ($source['organization']['id'] ?? $source['hfr_id'] ?? 'IN0510000871');
        $drId = (string) ($source['practitioner']['id'] ?? $source['doctor']['id'] ?? '1');
        if ($drId === '' || $drId === '0') {
            $drId = '1';
        }
        $drName = trim((string) ($source['practitioner']['name'] ?? $source['doctor']['name'] ?? $source['doctor_name'] ?? 'Dr. Attending Medical Officer'));
        if ($drName === '' || strcasecmp($drName, 'Doctor') === 0 || strcasecmp($drName, 'NA') === 0) {
            $drName = 'Dr. Attending Medical Officer';
        } elseif (! str_starts_with(strtolower($drName), 'dr.') && ! str_starts_with(strtolower($drName), 'dr ')) {
            $drName = 'Dr. ' . $drName;
        }

        $authorRef = 'urn:uuid:practitioner-' . $drId;

        $docRef = [
            'resourceType' => 'DocumentReference',
            'id' => $docRefId,
            'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentReference']],
            'status' => 'current',
            'docStatus' => 'final',
            'type' => [
                'coding' => [
                    [
                        'system' => 'http://snomed.info/sct',
                        'code' => '419891008',
                        'display' => 'Record artifact',
                    ],
                    [
                        'system' => 'http://loinc.org',
                        'code' => '41988-7',
                        'display' => 'Medical statement',
                    ],
                ],
                'text' => $docTitle,
            ],
            'subject' => ['reference' => $patientRef],
            'author' => [[
                'reference' => $authorRef,
                'display' => $drName,
            ]],
            'custodian' => ['reference' => 'urn:uuid:organization-' . ($orgId !== '' ? $orgId : 'IN0510000871')],
            'date' => $timestamp,
            'description' => $docTitle . ' PDF',
            'content' => [[
                'attachment' => [
                    'contentType' => 'application/pdf',
                    'language' => 'en-IN',
                    'data' => $docData,
                    'size' => $fileSize,
                    'title' => $docTitle . ' PDF',
                    'creation' => $timestamp,
                ],
            ]],
        ];

        if ($sha1Hash !== '') {
            $docRef['content'][0]['attachment']['hash'] = $sha1Hash;
        }

        $builder->addDocumentReference($docRef);

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

    private function convertToPdfBytes(string $rawBytes, string $contentType, string $docTitle): string
    {
        if ($rawBytes === '') {
            $rawBytes = '<div xmlns="http://www.w3.org/1999/xhtml"><h3>' . htmlspecialchars($docTitle, ENT_QUOTES, 'UTF-8') . '</h3><p>Health Document Summary</p></div>';
        }

        if (str_starts_with($rawBytes, '%PDF')) {
            return $rawBytes;
        }

        try {
            $mpdfTempDir = defined('WRITEPATH') ? WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mpdf' : sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mpdf';
            if (! is_dir($mpdfTempDir)) {
                @mkdir($mpdfTempDir, 0755, true);
            }

            ini_set('pcre.backtrack_limit', '5000000');

            $mpdf = new \Mpdf\Mpdf([
                'format' => 'A4',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'tempDir' => $mpdfTempDir,
            ]);

            if (str_starts_with($contentType, 'image/')) {
                $ext = str_contains($contentType, 'png') ? 'png' : 'jpg';
                $tempImg = $mpdfTempDir . DIRECTORY_SEPARATOR . 'img_' . md5($rawBytes) . '.' . $ext;
                @file_put_contents($tempImg, $rawBytes);

                $html = '<!DOCTYPE html><html><body style="margin:0;padding:0;text-align:center;">'
                    . '<h3>' . htmlspecialchars($docTitle, ENT_QUOTES, 'UTF-8') . '</h3>'
                    . '<img src="' . $tempImg . '" style="max-width:100%; height:auto;" />'
                    . '</body></html>';
                $mpdf->WriteHTML($html);
                @unlink($tempImg);
            } else {
                $html = str_contains($rawBytes, '<') ? $rawBytes : ('<!DOCTYPE html><html><body><h3>' . htmlspecialchars($docTitle, ENT_QUOTES, 'UTF-8') . '</h3><p>' . nl2br(htmlspecialchars($rawBytes, ENT_QUOTES, 'UTF-8')) . '</p></body></html>');
                $mpdf->WriteHTML($html);
            }

            $pdfBytes = $mpdf->Output('', 'S');
            return is_string($pdfBytes) && str_starts_with($pdfBytes, '%PDF') ? $pdfBytes : '';
        } catch (\Throwable $e) {
            return '';
        }
    }
}

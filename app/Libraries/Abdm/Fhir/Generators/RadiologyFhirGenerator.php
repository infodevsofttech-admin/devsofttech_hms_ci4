<?php

namespace App\Libraries\Abdm\Fhir\Generators;

class RadiologyFhirGenerator extends \App\Libraries\Abdm\Fhir\Generators\AbstractModuleFhirGenerator
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

        $careContextReference = 'RAD-' . $recordId . '-S' . $sessionId . '-' . $visitDate;
        $careContextDisplay = 'Radiology Report ' . $visitDate;

        $builder = new \App\Libraries\Abdm\Fhir\FhirDocumentBuilder();
        $builder
            ->buildBundleMeta('radiology-' . $recordId . '-' . strtotime($timestamp), $timestamp)
            ->buildComposition($this->buildBaseComposition($source, 'Radiology Report', '18748-4', 'Diagnostic imaging study'))
            ->addPatient($this->buildBasePatient($source));

        $encounter = $this->buildEncounter($source);
        if (is_array($encounter)) {
            $builder->addEncounter($encounter);
        }

        $patientRef = 'urn:uuid:patient-' . $patientId;
        $encounterRef = is_array($encounter) ? 'urn:uuid:' . (string) ($encounter['id'] ?? '') : null;

        $findingResolution = $this->codingResolver->resolveSnomedForDiagnosisOrFinding(
            (string) ($source['finding_code'] ?? ''),
            (string) ($source['finding_text'] ?? '')
        );

        $bodySiteResolution = $this->codingResolver->resolveSnomedForBodySite(
            (string) ($source['body_site_code'] ?? ''),
            (string) ($source['body_site'] ?? '')
        );

        $observationIds = [];
        foreach ((array) ($source['measurements'] ?? []) as $idx => $measurement) {
            $name = trim((string) ($measurement['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $loinc = $this->codingResolver->resolveLoincForLabTest((string) ($measurement['code'] ?? ''), $name, 'radiology');
            $ucum = $this->codingResolver->resolveUnitUcUM((string) ($measurement['unit'] ?? ''));
            $obsId = 'radiology-observation-' . $recordId . '-' . $idx;
            $observationIds[] = ['reference' => 'urn:uuid:' . $obsId];

            $builder->addObservation([
                'resourceType' => 'Observation',
                'id' => $obsId,
                'status' => 'final',
                'code' => [
                    'coding' => $loinc['coding'] ?? [],
                    'text' => $name,
                ],
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'effectiveDateTime' => $timestamp,
                'valueQuantity' => [
                    'value' => (float) ($measurement['value'] ?? 0),
                    'unit' => (string) ($measurement['unit'] ?? ''),
                    'system' => 'http://unitsofmeasure.org',
                    'code' => (string) ($ucum['code'] ?? ''),
                ],
                'bodySite' => [
                    'coding' => $bodySiteResolution['coding'] ?? [],
                    'text' => (string) ($source['body_site'] ?? ''),
                ],
            ]);
        }

        $builder->addDiagnosticReport([
            'resourceType' => 'DiagnosticReport',
            'id' => 'radiology-report-' . $recordId,
            'status' => 'final',
            'code' => [
                'coding' => [[
                    'system' => 'http://loinc.org',
                    'code' => '18748-4',
                    'display' => 'Diagnostic imaging study',
                ]],
                'text' => (string) ($source['modality'] ?? 'Radiology'),
            ],
            'subject' => ['reference' => $patientRef],
            'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
            'effectiveDateTime' => $timestamp,
            'issued' => $timestamp,
            'conclusion' => (string) ($source['conclusion'] ?? ''),
            'result' => $observationIds,
        ]);

        if (trim((string) ($source['finding_text'] ?? '')) !== '') {
            $builder->addCondition([
                'resourceType' => 'Condition',
                'id' => 'radiology-finding-' . $recordId,
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'code' => [
                    'coding' => $findingResolution['coding'] ?? [],
                    'text' => (string) ($source['finding_text'] ?? ''),
                ],
            ]);
        }

        if (trim((string) ($source['study_uid'] ?? '')) !== '' || trim((string) ($source['accession_no'] ?? '')) !== '') {
            $builder->addProcedure([
                'resourceType' => 'ImagingStudy',
                'id' => 'imaging-study-' . $recordId,
                'subject' => ['reference' => $patientRef],
                'identifier' => array_values(array_filter([
                    trim((string) ($source['study_uid'] ?? '')) !== '' ? [
                        'system' => 'urn:dicom:uid',
                        'value' => (string) ($source['study_uid'] ?? ''),
                    ] : null,
                    trim((string) ($source['accession_no'] ?? '')) !== '' ? [
                        'system' => 'https://hms.local/radiology-accession',
                        'value' => (string) ($source['accession_no'] ?? ''),
                    ] : null,
                ])),
                'started' => $timestamp,
            ]);
        }

        $resolved = 0;
        $unresolved = 0;
        $fallbackUsed = 0;
        foreach ([$findingResolution, $bodySiteResolution] as $resolution) {
            if ((bool) ($resolution['unresolved'] ?? false)) {
                $unresolved++;
                $fallbackUsed++;
            } else {
                $resolved++;
            }
        }

        $bundle = $builder->toBundle();
        $validation = $this->validator->validate($bundle, 'radiology', [
            'resolved' => $resolved,
            'unresolved' => $unresolved,
            'fallback_used' => $fallbackUsed,
        ]);

        return [
            'hi_type' => 'DiagnosticReportRecord',
            'care_context_reference' => $careContextReference,
            'care_context_display' => $careContextDisplay,
            'fhir_bundle' => $bundle,
            'validation' => $validation->toArray(),
        ];
    }
}

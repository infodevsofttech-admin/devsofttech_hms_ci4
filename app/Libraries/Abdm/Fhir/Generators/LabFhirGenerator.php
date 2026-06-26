<?php

namespace App\Libraries\Abdm\Fhir\Generators;

class LabFhirGenerator extends \App\Libraries\Abdm\Fhir\Generators\AbstractModuleFhirGenerator
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

        $careContextReference = 'LAB-' . $recordId . '-S' . $sessionId . '-' . $visitDate;
        $careContextDisplay = 'Lab Report ' . $visitDate;

        $builder = new \App\Libraries\Abdm\Fhir\FhirDocumentBuilder();
        $builder
            ->buildBundleMeta('lab-' . $recordId . '-' . strtotime($timestamp), $timestamp)
            ->buildComposition($this->buildBaseComposition($source, 'Diagnostic Report Summary', '11502-2', 'Laboratory report'))
            ->addPatient($this->buildBasePatient($source));

        $encounter = $this->buildEncounter($source);
        if (is_array($encounter)) {
            $builder->addEncounter($encounter);
        }

        $patientRef = 'urn:uuid:patient-' . $patientId;
        $encounterRef = is_array($encounter) ? 'urn:uuid:' . (string) ($encounter['id'] ?? '') : null;

        $panelName = trim((string) ($source['panel_name'] ?? 'Lab Panel'));
        $panelCode = trim((string) ($source['panel_code'] ?? ''));
        $panelLoinc = $this->codingResolver->resolveLoincForLabPanel($panelCode, $panelName, (string) ($source['department'] ?? ''));

        $resultRefs = [];
        $resolvedCount = 0;
        $unresolvedCount = 0;
        $fallbackCount = 0;

        foreach ((array) ($source['observations'] ?? []) as $idx => $obs) {
            $name = trim((string) ($obs['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $resolved = $this->codingResolver->resolveLoincForLabTest((string) ($obs['code'] ?? ''), $name, (string) ($source['department'] ?? ''), (string) ($source['specimen'] ?? ''));
            if ((bool) ($resolved['unresolved'] ?? false)) {
                $unresolvedCount++;
                $fallbackCount++;
            } else {
                $resolvedCount++;
            }

            $ucum = $this->codingResolver->resolveUnitUcUM((string) ($obs['unit'] ?? ''));
            if ((bool) ($ucum['unresolved'] ?? false)) {
                $unresolvedCount++;
            } else {
                $resolvedCount++;
            }

            $interp = $this->codingResolver->resolveInterpretationCode((string) ($obs['interpretation'] ?? ''));
            if ((bool) ($interp['unresolved'] ?? false)) {
                $unresolvedCount++;
            } else {
                $resolvedCount++;
            }

            $obsId = 'lab-observation-' . $recordId . '-' . $idx;
            $resultRefs[] = ['reference' => 'urn:uuid:' . $obsId];

            $builder->addObservation([
                'resourceType' => 'Observation',
                'id' => $obsId,
                'status' => 'final',
                'category' => [[
                    'coding' => [[
                        'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                        'code' => 'laboratory',
                    ]],
                ]],
                'code' => [
                    'coding' => $resolved['coding'] ?? [],
                    'text' => $name,
                ],
                'subject' => ['reference' => $patientRef],
                'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
                'effectiveDateTime' => (string) ($obs['effective_at'] ?? $timestamp),
                'issued' => (string) ($obs['issued_at'] ?? $timestamp),
                'valueQuantity' => [
                    'value' => (float) ($obs['value'] ?? 0),
                    'unit' => (string) ($obs['unit'] ?? ''),
                    'system' => 'http://unitsofmeasure.org',
                    'code' => (string) ($ucum['code'] ?? ''),
                ],
                'interpretation' => [[
                    'coding' => [[
                        'system' => (string) ($interp['system'] ?? ''),
                        'code' => (string) ($interp['code'] ?? ''),
                        'display' => (string) ($interp['display'] ?? ''),
                    ]],
                ]],
                'referenceRange' => isset($obs['reference_range']) ? [[
                    'text' => (string) $obs['reference_range'],
                ]] : null,
            ]);
        }

        $diagReportId = 'diagnostic-report-' . $recordId;
        $builder->addDiagnosticReport([
            'resourceType' => 'DiagnosticReport',
            'id' => $diagReportId,
            'status' => 'final',
            'code' => [
                'coding' => $panelLoinc['coding'] ?? [],
                'text' => $panelName,
            ],
            'subject' => ['reference' => $patientRef],
            'encounter' => $encounterRef ? ['reference' => $encounterRef] : null,
            'effectiveDateTime' => $timestamp,
            'issued' => $timestamp,
            'result' => $resultRefs,
            'category' => [[
                'coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/v2-0074',
                    'code' => 'LAB',
                    'display' => 'Laboratory',
                ]],
            ]],
        ]);

        $bundle = $builder->toBundle();
        $validation = $this->validator->validate($bundle, 'lab', [
            'resolved' => $resolvedCount,
            'unresolved' => $unresolvedCount,
            'fallback_used' => $fallbackCount,
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

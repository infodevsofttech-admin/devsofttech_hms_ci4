<?php

namespace App\Libraries\Abdm\Fhir\Support;

class FhirBundleValidator
{
    /**
     * @param array<string,mixed> $bundle
     * @param string $sourceModule
     */
    public function validate(array $bundle, string $sourceModule = 'unknown', array $terminologySummary = []): FhirValidationResult
    {
        $errors = [];
        $warnings = [];

        if (($bundle['resourceType'] ?? '') !== 'Bundle') {
            $errors[] = ['code' => 'bundle.resourceType', 'field' => 'resourceType', 'message' => 'Bundle.resourceType must be Bundle'];
        }

        if (($bundle['type'] ?? '') !== 'document') {
            $errors[] = ['code' => 'bundle.type', 'field' => 'type', 'message' => 'Bundle.type must be document'];
        }

        $entries = $bundle['entry'] ?? [];
        if (! is_array($entries) || empty($entries)) {
            $errors[] = ['code' => 'bundle.entry', 'field' => 'entry', 'message' => 'Bundle.entry must contain resources'];
            return new FhirValidationResult(false, 0, $errors, $warnings, $terminologySummary, $this->buildAudit($sourceModule, $terminologySummary, $warnings));
        }

        $firstResourceType = (string) (($entries[0]['resource']['resourceType'] ?? ''));
        if ($firstResourceType !== 'Composition') {
            $errors[] = ['code' => 'bundle.composition.first', 'field' => 'entry[0]', 'message' => 'First entry must be Composition'];
        }

        $hasPatient = false;
        $resourceRefs = [];
        $diagnosticReportResultRefs = [];
        foreach ($entries as $idx => $entry) {
            $resource = $entry['resource'] ?? null;
            if (! is_array($resource)) {
                $errors[] = ['code' => 'bundle.entry.resource', 'field' => 'entry.' . $idx, 'message' => 'entry.resource must be object'];
                continue;
            }

            $rtype = (string) ($resource['resourceType'] ?? '');
            $rid = (string) ($resource['id'] ?? '');
            if ($rtype === 'Patient') {
                $hasPatient = true;
            }
            if ($rid !== '') {
                $resourceRefs[$rtype . '/' . $rid] = true;
                $resourceRefs['urn:uuid:' . $rid] = true;
            }
            $fullUrl = trim((string) ($entry['fullUrl'] ?? ''));
            if ($fullUrl !== '') {
                $resourceRefs[$fullUrl] = true;
            }

            if ($rtype === 'DiagnosticReport') {
                $results = $resource['result'] ?? [];
                if (is_array($results)) {
                    foreach ($results as $resRef) {
                        if (is_array($resRef) && isset($resRef['reference'])) {
                            $diagnosticReportResultRefs[] = (string) $resRef['reference'];
                        }
                    }
                }
            }

            if ($rtype === 'Observation') {
                if (isset($resource['valueQuantity']) && is_array($resource['valueQuantity'])) {
                    $vq = $resource['valueQuantity'];
                    if (! isset($vq['value']) || ! isset($vq['unit'])) {
                        $warnings[] = ['code' => 'observation.valueQuantity', 'field' => 'Observation.valueQuantity', 'message' => 'Observation valueQuantity should include value and unit'];
                    }
                }
            }
        }

        if (! $hasPatient) {
            $errors[] = ['code' => 'bundle.patient.required', 'field' => 'entry', 'message' => 'Patient resource is required'];
        }

        foreach ($diagnosticReportResultRefs as $ref) {
            if (! isset($resourceRefs[$ref])) {
                $errors[] = ['code' => 'diagnosticReport.result.link', 'field' => 'DiagnosticReport.result.reference', 'message' => 'DiagnosticReport result reference not found: ' . $ref];
            }
        }

        $score = $this->calculateScore($errors, $warnings, $terminologySummary);
        $valid = empty($errors);

        return new FhirValidationResult(
            $valid,
            $score,
            $errors,
            $warnings,
            $terminologySummary,
            $this->buildAudit($sourceModule, $terminologySummary, $warnings)
        );
    }

    /**
     * @param array<int,array<string,string>> $errors
     * @param array<int,array<string,string>> $warnings
     * @param array<string,mixed> $terminologySummary
     */
    private function calculateScore(array $errors, array $warnings, array $terminologySummary): int
    {
        $score = 100;

        $score -= min(60, count($errors) * 20);
        $score -= min(25, count($warnings) * 5);

        $resolved = (int) ($terminologySummary['resolved'] ?? 0);
        $unresolved = (int) ($terminologySummary['unresolved'] ?? 0);
        if ($resolved + $unresolved > 0) {
            $ratio = $resolved / max(1, ($resolved + $unresolved));
            $score = (int) round($score * (0.7 + ($ratio * 0.3)));
        }

        return max(0, min(100, $score));
    }

    /**
     * @param array<string,mixed> $terminologySummary
     * @param array<int,array<string,string>> $warnings
     * @return array<string,mixed>
     */
    private function buildAudit(string $sourceModule, array $terminologySummary, array $warnings): array
    {
        $resolved = (int) ($terminologySummary['resolved'] ?? 0);
        $unresolved = (int) ($terminologySummary['unresolved'] ?? 0);
        $fallbackUsed = (int) ($terminologySummary['fallback_used'] ?? 0);

        return [
            'mapping_version' => 'm2-sync-v1',
            'source_module' => $sourceModule,
            'coding_resolved_count' => $resolved,
            'coding_unresolved_count' => $unresolved,
            'fallback_used_count' => $fallbackUsed,
            'top_warnings' => array_map(static fn ($w) => (string) ($w['message'] ?? ''), array_slice($warnings, 0, 5)),
            'generated_at' => date(DATE_ATOM),
            'source_record_id' => '',
        ];
    }
}

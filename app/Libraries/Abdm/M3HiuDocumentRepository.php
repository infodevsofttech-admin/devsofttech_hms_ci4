<?php

namespace App\Libraries\Abdm;

use CodeIgniter\I18n\Time;

class M3HiuDocumentRepository
{
    private \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function persistFromDataFetch(array $payload, array $result, int $workflowId = 0): array
    {
        if (! $this->db->tableExists('abdm_hiu_documents')) {
            return ['saved' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $sessions = $this->extractSessions($result);
        if (empty($sessions)) {
            return ['saved' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $this->ensureConsentRequestIdColumn();

        $requestId = trim((string) ($result['request_id'] ?? $payload['request_id'] ?? $payload['requestId'] ?? ''));
        $abhaAddress = trim((string) ($payload['abha_address'] ?? $result['abha_address'] ?? ''));
        // The specific consent request (e.g. Method 1/2's targeted request) that
        // triggered this fetch, when known. Method 3 (fetch-all-by-abha-address)
        // spans many past consent requests at once, so this is intentionally left
        // blank for that broad fetch rather than mis-attributing every document to
        // whichever single request happened to be passed in.
        $consentRequestId = trim((string) (
            $payload['abdm_consent_request_id']
            ?? $payload['consent_request_id']
            ?? $payload['consentRequestId']
            ?? ''
        ));

        $saved = 0;
        $updated = 0;
        $skipped = 0;
        $now = Time::now('Asia/Kolkata')->toDateTimeString();

        foreach ($sessions as $session) {
            if (! is_array($session)) {
                continue;
            }

            $transactionId = trim((string) ($session['transaction_id'] ?? ''));
            $consentRef = trim((string) ($session['consent_id'] ?? ''));
            $consentArtifactId = $this->extractConsentArtifactId($consentRef);
            $records = is_array($session['decrypted_data'] ?? null) ? (array) $session['decrypted_data'] : [];

            foreach ($records as $record) {
                if (! is_array($record)) {
                    $skipped++;
                    continue;
                }

                $careContextRef = trim((string) ($record['careContextReference'] ?? ''));
                $media = trim((string) ($record['media'] ?? ''));
                $bundle = $this->decodeBundle($record['decrypted_data'] ?? null);
                if (! is_array($bundle) || empty($bundle)) {
                    $skipped++;
                    continue;
                }

                $summary = $this->buildSummary($bundle);
                $resolvedAbha = $abhaAddress !== '' ? $abhaAddress : trim((string) ($summary['abha_address'] ?? ''));
                $patientMap = $this->mapPatientByAbha($resolvedAbha, (string) ($summary['abha_number'] ?? ''));

                // NOTE: transaction_id and consent_ref are intentionally EXCLUDED
                // from the dedup key. Confirmed empirically: the SAME clinical
                // document (identical bundle_id) gets re-delivered by the bridge
                // under a DIFFERENT transaction_id/consent_ref every time it is
                // re-fetched (e.g. via the by-ABHA-address historical fetch, which
                // returns every past consent session), so including them created a
                // brand-new duplicate row per fetch instead of updating the
                // existing one. bundle_id is the FHIR Bundle resource's own `id`
                // (set once by the HIP) and is stable across re-fetches/consents,
                // so pair it with care_context_reference (the clinical encounter
                // reference) to uniquely identify a document. request_id is also
                // excluded since it is unique per polling/fetch call.
                $bundleId = trim((string) ($summary['bundle_id'] ?? ''));
                $docHash = $bundleId !== ''
                    ? sha1('bundle:' . $careContextRef . '|' . $bundleId)
                    : sha1(implode('|', [
                        $transactionId,
                        $consentRef,
                        $careContextRef,
                        (string) ($summary['bundle_id'] ?? ''),
                    ]));

                $row = [
                    'workflow_id' => $workflowId > 0 ? $workflowId : null,
                    'request_id' => $requestId !== '' ? $requestId : null,
                    'transaction_id' => $transactionId !== '' ? $transactionId : null,
                    'consent_ref' => $consentRef !== '' ? $consentRef : null,
                    'consent_artifact_id' => $consentArtifactId !== '' ? $consentArtifactId : null,
                    'abha_address' => $resolvedAbha !== '' ? $resolvedAbha : null,
                    'patient_id' => (int) ($patientMap['patient_id'] ?? 0) > 0 ? (int) $patientMap['patient_id'] : null,
                    'patient_name' => trim((string) ($summary['patient_name'] ?? '')) !== '' ? trim((string) ($summary['patient_name'] ?? '')) : null,
                    'care_context_reference' => $careContextRef !== '' ? $careContextRef : null,
                    'media' => $media !== '' ? $media : null,
                    'bundle_id' => trim((string) ($summary['bundle_id'] ?? '')) !== '' ? trim((string) ($summary['bundle_id'] ?? '')) : null,
                    'bundle_type' => trim((string) ($summary['bundle_type'] ?? '')) !== '' ? trim((string) ($summary['bundle_type'] ?? '')) : null,
                    'document_title' => trim((string) ($summary['document_title'] ?? '')) !== '' ? trim((string) ($summary['document_title'] ?? '')) : null,
                    'document_date' => $this->toDateTimeString((string) ($summary['document_date'] ?? '')),
                    'practitioner_name' => trim((string) ($summary['practitioner_name'] ?? '')) !== '' ? trim((string) ($summary['practitioner_name'] ?? '')) : null,
                    'organization_name' => trim((string) ($summary['organization_name'] ?? '')) !== '' ? trim((string) ($summary['organization_name'] ?? '')) : null,
                    'summary_json' => json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'raw_bundle' => json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => $now,
                ];

                // Only set (and never blank out on update) when this specific fetch
                // actually knew which consent request it belonged to.
                if ($consentRequestId !== '') {
                    $row['consent_request_id'] = $consentRequestId;
                }

                $existing = $this->db->table('abdm_hiu_documents')
                    ->select('id')
                    ->where('doc_hash', $docHash)
                    ->get(1)
                    ->getRowArray();

                if (! empty($existing)) {
                    $this->db->table('abdm_hiu_documents')->where('id', (int) $existing['id'])->update($row);
                    $updated++;
                    continue;
                }

                $row['doc_hash'] = $docHash;
                $row['created_at'] = $now;
                $this->db->table('abdm_hiu_documents')->insert($row);
                $saved++;
            }
        }

        return ['saved' => $saved, 'updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * Idempotent backfill so deployments that predate the Consent Request ID
     * enrichment (added 2026-07-29) don't fail with "Unknown column" on save.
     */
    private function ensureConsentRequestIdColumn(): void
    {
        $fields = $this->db->getFieldNames('abdm_hiu_documents') ?? [];
        if (in_array('consent_request_id', $fields, true)) {
            return;
        }

        try {
            $this->db->query('ALTER TABLE abdm_hiu_documents ADD COLUMN consent_request_id VARCHAR(190) NULL AFTER consent_artifact_id');
        } catch (\Throwable $e) {
            // Best-effort; if the ALTER fails (e.g. concurrent request already
            // added it, or insufficient DB privileges) just skip enrichment for
            // this call rather than breaking the fetch.
        }
    }

    public function listDocuments(array $filters, int $limit = 100): array
    {
        if (! $this->db->tableExists('abdm_hiu_documents')) {
            return [];
        }

        $builder = $this->db->table('abdm_hiu_documents')
            ->select('*')
            ->orderBy('id', 'DESC')
            ->limit($limit);

        $patientId = (int) ($filters['patient_id'] ?? 0);
        if ($patientId > 0) {
            $builder->where('patient_id', $patientId);
        }

        $abhaAddress = trim((string) ($filters['abha_address'] ?? ''));
        if ($abhaAddress !== '') {
            $builder->where('abha_address', $abhaAddress);
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $builder->groupStart()
                ->like('patient_name', $q)
                ->orLike('care_context_reference', $q)
                ->orLike('document_title', $q)
                ->orLike('practitioner_name', $q)
                ->orLike('organization_name', $q)
                ->groupEnd();
        }

        return $builder->get()->getResultArray();
    }

    public function getDocument(int $id): ?array
    {
        if ($id <= 0 || ! $this->db->tableExists('abdm_hiu_documents')) {
            return null;
        }

        $row = $this->db->table('abdm_hiu_documents')->where('id', $id)->get(1)->getRowArray();
        if (empty($row)) {
            return null;
        }

        $row['summary'] = json_decode((string) ($row['summary_json'] ?? ''), true) ?: [];
        $row['bundle'] = json_decode((string) ($row['raw_bundle'] ?? ''), true) ?: [];

        // Documents saved before attachment extraction was added won't have
        // 'attachments' in their stored summary_json; derive it live from the
        // raw bundle instead of requiring a brand-new fetch.
        if (empty($row['summary']['attachments']) && ! empty($row['bundle'])) {
            $row['summary']['attachments'] = $this->extractAttachments($row['bundle']);
        }

        return $row;
    }

    private function extractSessions(array $result): array
    {
        $sessions = $result['sessions'] ?? null;
        if (is_array($sessions)) {
            return $sessions;
        }

        $nested = $result['data']['sessions'] ?? null;
        return is_array($nested) ? $nested : [];
    }

    private function extractConsentArtifactId(string $consentRef): string
    {
        $consentRef = trim($consentRef);
        if ($consentRef === '') {
            return '';
        }
        $parts = explode(':', $consentRef);
        return trim((string) ($parts[0] ?? ''));
    }

    private function decodeBundle($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        $text = trim((string) $raw);
        if ($text === '') {
            return [];
        }

        $decoded = json_decode($text, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function buildSummary(array $bundle): array
    {
        $entries = is_array($bundle['entry'] ?? null) ? (array) $bundle['entry'] : [];
        $patient = [];
        $composition = [];
        $practitioner = [];
        $organization = [];
        $conditions = [];
        $vitals = [];
        $medications = [];
        // Indexed by "ResourceType/id" and by fullUrl, so Composition.section[].entry
        // references (e.g. {"reference": "Condition/xyz"}) can be resolved back to
        // their actual resource below.
        $resourcesByRef = [];

        foreach ($entries as $entry) {
            $resource = is_array($entry['resource'] ?? null) ? (array) $entry['resource'] : [];
            $type = trim((string) ($resource['resourceType'] ?? ''));
            if ($type === '') {
                continue;
            }

            $resourceId = trim((string) ($resource['id'] ?? ''));
            if ($resourceId !== '') {
                $resourcesByRef[$type . '/' . $resourceId] = $resource;
            }
            $fullUrl = trim((string) ($entry['fullUrl'] ?? ''));
            if ($fullUrl !== '') {
                $resourcesByRef[$fullUrl] = $resource;
            }

            if ($type === 'Patient' && empty($patient)) {
                $patient = $resource;
            } elseif ($type === 'Composition' && empty($composition)) {
                $composition = $resource;
            } elseif ($type === 'Practitioner' && empty($practitioner)) {
                $practitioner = $resource;
            } elseif ($type === 'Organization' && empty($organization)) {
                $organization = $resource;
            } elseif ($type === 'Condition') {
                $conditions[] = [
                    'text' => trim((string) (($resource['code']['text'] ?? '') ?: ($resource['code']['coding'][0]['display'] ?? ''))),
                ];
            } elseif ($type === 'Observation') {
                $codeText = trim((string) (($resource['code']['text'] ?? '') ?: ($resource['code']['coding'][0]['display'] ?? '')));
                $val = '';
                if (isset($resource['valueQuantity']) && is_array($resource['valueQuantity'])) {
                    $val = trim((string) (($resource['valueQuantity']['value'] ?? '') . ' ' . ($resource['valueQuantity']['unit'] ?? '')));
                }
                if ($codeText !== '' || $val !== '') {
                    $vitals[] = ['name' => $codeText, 'value' => $val];
                }
            } elseif ($type === 'MedicationRequest') {
                $medications[] = [
                    'name' => trim((string) (($resource['medicationCodeableConcept']['text'] ?? '') ?: ($resource['medicationCodeableConcept']['coding'][0]['display'] ?? ''))),
                    'dose' => trim((string) ($resource['dosageInstruction'][0]['text'] ?? '')),
                ];
            }
        }

        $patientName = trim((string) (($patient['name'][0]['text'] ?? '') ?: implode(' ', (array) ($patient['name'][0]['given'] ?? []))));
        $abhaNumber = '';
        foreach ((array) ($patient['identifier'] ?? []) as $identifier) {
            $system = trim((string) ($identifier['system'] ?? ''));
            if (stripos($system, 'healthid.ndhm.gov.in') !== false) {
                $abhaNumber = preg_replace('/\D+/', '', (string) ($identifier['value'] ?? '')) ?? '';
                break;
            }
        }

        $practitionerName = trim((string) (($practitioner['name'][0]['text'] ?? '') ?: implode(' ', (array) ($practitioner['name'][0]['given'] ?? []))));
        $organizationName = trim((string) ($organization['name'] ?? ''));

        return [
            'bundle_id' => trim((string) ($bundle['id'] ?? '')),
            'bundle_type' => trim((string) (($bundle['resourceType'] ?? '') . '/' . ($bundle['type'] ?? ''))),
            'document_title' => trim((string) ($composition['title'] ?? '')),
            'document_date' => trim((string) ($composition['date'] ?? $bundle['timestamp'] ?? '')),
            'patient_name' => $patientName,
            'patient_gender' => trim((string) ($patient['gender'] ?? '')),
            'patient_birth_date' => trim((string) ($patient['birthDate'] ?? '')),
            'abha_number' => $abhaNumber,
            'abha_address' => $abhaNumber !== '' ? ($abhaNumber . '@sbx') : '',
            'practitioner_name' => $practitionerName,
            'organization_name' => $organizationName,
            'conditions' => $conditions,
            'vitals' => $vitals,
            'medications' => $medications,
            'attachments' => $this->extractAttachments($bundle),
            // Mirrors the Composition's own section breakdown (Chief Complaints,
            // Physical Examination, Allergies, Investigations, Medications,
            // Procedures, Medical History, Care Plan, Documents, etc.) so the UI
            // can render an accordion per record exactly like the source document
            // is organized, instead of a fixed hard-coded set of categories.
            'sections' => $this->buildCompositionSections($composition, $resourcesByRef),
        ];
    }

    /**
     * Resolves each Composition.section[].entry[] reference to its underlying
     * resource and produces a short display label for it, falling back to the
     * section's own narrative text (section.text.div) when there are no
     * resolvable entries (some HIPs only populate the narrative).
     *
     * @return array<int, array{title: string, items: array<int, string>, narrative: string}>
     */
    private function buildCompositionSections(array $composition, array $resourcesByRef): array
    {
        $sections = [];

        foreach ((array) ($composition['section'] ?? []) as $section) {
            if (! is_array($section)) {
                continue;
            }

            $title = trim((string) (($section['title'] ?? '') ?: ($section['code']['text'] ?? '')));
            if ($title === '') {
                continue;
            }

            $items = [];
            foreach ((array) ($section['entry'] ?? []) as $ref) {
                if (! is_array($ref)) {
                    continue;
                }
                $refStr = trim((string) ($ref['reference'] ?? ''));
                if ($refStr === '' || ! isset($resourcesByRef[$refStr])) {
                    continue;
                }
                foreach ($this->describeResourceForSection((array) $resourcesByRef[$refStr], $resourcesByRef) as $label) {
                    if ($label !== '') {
                        $items[] = $label;
                    }
                }
            }

            $narrative = '';
            if ($items === []) {
                $div = trim((string) ($section['text']['div'] ?? ''));
                if ($div !== '') {
                    $div = preg_replace('/<li[^>]*>/i', "\n- ", $div) ?? $div;
                    $div = preg_replace('/<br\s*\/?>/i', "\n", $div) ?? $div;
                    $narrative = trim(html_entity_decode(strip_tags($div), ENT_QUOTES | ENT_HTML5));
                }
            }

            $sections[] = [
                'title' => $title,
                'items' => $items,
                'narrative' => $narrative,
            ];
        }

        return $sections;
    }

    /**
     * Produces one or more short, human-readable one-line labels for a FHIR
     * resource, for display inside a Composition section's item list. Most
     * resource types resolve to a single line; DiagnosticReport expands into
     * one line per linked Observation result (e.g. individual lab test
     * values) since that's where the actual pathology/lab values live.
     *
     * @return array<int, string>
     */
    private function describeResourceForSection(array $resource, array $resourcesByRef = []): array
    {
        $type = trim((string) ($resource['resourceType'] ?? ''));

        switch ($type) {
            case 'Condition':
            case 'AllergyIntolerance':
            case 'Procedure':
            case 'ServiceRequest':
                $label = trim((string) (($resource['code']['text'] ?? '') ?: ($resource['code']['coding'][0]['display'] ?? '')));
                return $label !== '' ? [$label] : [];

            case 'Observation':
                $label = $this->describeObservation($resource);
                return $label !== '' ? [$label] : [];

            case 'MedicationRequest':
            case 'MedicationStatement':
                $med = trim((string) (($resource['medicationCodeableConcept']['text'] ?? '') ?: ($resource['medicationCodeableConcept']['coding'][0]['display'] ?? '')));
                $dose = trim((string) ($resource['dosageInstruction'][0]['text'] ?? ($resource['dosage'][0]['text'] ?? '')));
                $label = $dose !== '' ? ($med . ' — ' . $dose) : $med;
                return $label !== '' ? [$label] : [];

            case 'DiagnosticReport':
                $name = trim((string) (($resource['code']['text'] ?? '') ?: ($resource['code']['coding'][0]['display'] ?? '')));
                $conclusion = trim((string) ($resource['conclusion'] ?? ''));
                $header = $conclusion !== '' ? ($name . ' — ' . $conclusion) : $name;

                $lines = $header !== '' ? [$header] : [];
                foreach ((array) ($resource['result'] ?? []) as $resultRef) {
                    if (! is_array($resultRef)) {
                        continue;
                    }
                    $refStr = trim((string) ($resultRef['reference'] ?? ''));
                    if ($refStr === '' || ! isset($resourcesByRef[$refStr])) {
                        continue;
                    }
                    $obsResource = (array) $resourcesByRef[$refStr];
                    if (trim((string) ($obsResource['resourceType'] ?? '')) !== 'Observation') {
                        continue;
                    }
                    $obsLine = $this->describeObservation($obsResource);
                    if ($obsLine !== '') {
                        $lines[] = '– ' . $obsLine;
                    }
                }

                return $lines;

            case 'CarePlan':
                $label = trim((string) (($resource['description'] ?? '') ?: ($resource['title'] ?? '')));
                return $label !== '' ? [$label] : [];

            case 'DocumentReference':
                $label = trim((string) (($resource['description'] ?? '') ?: ($resource['type']['text'] ?? '') ?: ($resource['type']['coding'][0]['display'] ?? '')));
                return $label !== '' ? [$label] : [];

            default:
                return [];
        }
    }

    /**
     * Builds a single "Test name: value unit (Flag)" style line for an
     * Observation resource, covering the common FHIR value shapes used by
     * lab/pathology reports (valueQuantity, valueString, valueCodeableConcept,
     * valueInteger/valueBoolean, and multi-component observations like Blood
     * Pressure which report systolic/diastolic as separate `component` entries
     * instead of a single top-level value).
     */
    private function describeObservation(array $resource): string
    {
        $name = trim((string) (($resource['code']['text'] ?? '') ?: ($resource['code']['coding'][0]['display'] ?? '')));

        $formatValue = static function (array $obs): string {
            if (isset($obs['valueQuantity']) && is_array($obs['valueQuantity'])) {
                return trim((string) (($obs['valueQuantity']['value'] ?? '') . ' ' . ($obs['valueQuantity']['unit'] ?? ($obs['valueQuantity']['code'] ?? ''))));
            }
            if (isset($obs['valueString'])) {
                return trim((string) $obs['valueString']);
            }
            if (isset($obs['valueCodeableConcept'])) {
                return trim((string) (($obs['valueCodeableConcept']['text'] ?? '') ?: ($obs['valueCodeableConcept']['coding'][0]['display'] ?? '')));
            }
            if (isset($obs['valueInteger'])) {
                return trim((string) $obs['valueInteger']);
            }
            if (isset($obs['valueBoolean'])) {
                return $obs['valueBoolean'] ? 'Yes' : 'No';
            }
            if (isset($obs['valueDateTime'])) {
                return trim((string) $obs['valueDateTime']);
            }
            if (isset($obs['valueRange']) && is_array($obs['valueRange'])) {
                $low  = $obs['valueRange']['low']['value'] ?? '';
                $high = $obs['valueRange']['high']['value'] ?? '';
                return trim($low . ' - ' . $high);
            }

            return '';
        };

        $val = $formatValue($resource);

        // Multi-component observations (e.g. Blood Pressure: systolic + diastolic)
        // carry no top-level value, only a `component[]` array each with its
        // own code + value.
        if ($val === '' && isset($resource['component']) && is_array($resource['component'])) {
            $parts = [];
            foreach ((array) $resource['component'] as $component) {
                if (! is_array($component)) {
                    continue;
                }
                $compName = trim((string) (($component['code']['text'] ?? '') ?: ($component['code']['coding'][0]['display'] ?? '')));
                $compVal  = $formatValue($component);
                if ($compVal === '') {
                    continue;
                }
                $parts[] = $compName !== '' ? ($compName . ': ' . $compVal) : $compVal;
            }
            $val = implode(', ', $parts);
        }

        $flag = trim((string) (($resource['interpretation'][0]['text'] ?? '') ?: ($resource['interpretation'][0]['coding'][0]['display'] ?? '')));

        $label = $val !== '' ? ($name . ': ' . $val) : $name;
        if ($flag !== '') {
            $label .= ' (' . $flag . ')';
        }

        return trim($label, ' :');
    }

    /**
     * Extracts attached scanned images/PDFs/generic documents (DocumentReference
     * resources, whose content may carry the base64 attachment data inline or
     * reference a separate Binary resource) from a FHIR bundle.
     *
     * @return array<int, array{title: string, content_type: string, data: string}>
     */
    public function extractAttachments(array $bundle): array
    {
        $entries = is_array($bundle['entry'] ?? null) ? (array) $bundle['entry'] : [];
        $documentReferences = [];

        // Binary resources are usually referenced by DocumentReference.content[].attachment.url
        // (e.g. "Binary/xyz" or a matching fullUrl) rather than carrying the base64 data inline,
        // so index them by both id and fullUrl for lookup in the second pass below.
        $binariesById = [];
        foreach ($entries as $entry) {
            $resource = is_array($entry['resource'] ?? null) ? (array) $entry['resource'] : [];
            if (trim((string) ($resource['resourceType'] ?? '')) !== 'Binary') {
                continue;
            }
            $data = trim((string) ($resource['data'] ?? ''));
            $contentType = trim((string) ($resource['contentType'] ?? ''));
            if ($data === '') {
                continue;
            }
            $binary = ['data' => $data, 'content_type' => $contentType];
            $id = trim((string) ($resource['id'] ?? ''));
            if ($id !== '') {
                $binariesById[$id] = $binary;
            }
            $fullUrl = trim((string) ($entry['fullUrl'] ?? ''));
            if ($fullUrl !== '') {
                $binariesById[$fullUrl] = $binary;
            }
        }

        foreach ($entries as $entry) {
            $resource = is_array($entry['resource'] ?? null) ? (array) $entry['resource'] : [];
            if (trim((string) ($resource['resourceType'] ?? '')) !== 'DocumentReference') {
                continue;
            }

            foreach ((array) ($resource['content'] ?? []) as $content) {
                $attachment = is_array($content['attachment'] ?? null) ? (array) $content['attachment'] : [];
                if ($attachment === []) {
                    continue;
                }

                $contentType = trim((string) ($attachment['contentType'] ?? ''));
                $data = trim((string) ($attachment['data'] ?? ''));
                if ($data === '') {
                    $refUrl = trim((string) ($attachment['url'] ?? ''));
                    $refId = str_replace('Binary/', '', $refUrl);
                    $binary = $binariesById[$refUrl] ?? $binariesById[$refId] ?? null;
                    if (is_array($binary)) {
                        $data = (string) ($binary['data'] ?? '');
                        if ($contentType === '') {
                            $contentType = (string) ($binary['content_type'] ?? '');
                        }
                    }
                }

                if ($data === '') {
                    continue;
                }

                $documentReferences[] = [
                    'title' => trim((string) (
                        ($resource['description'] ?? '')
                        ?: ($resource['type']['text'] ?? '')
                        ?: ($resource['type']['coding'][0]['display'] ?? '')
                        ?: ($attachment['title'] ?? 'Attached Document')
                    )),
                    'content_type' => $contentType !== '' ? $contentType : 'application/octet-stream',
                    'data' => $data,
                ];
            }
        }

        return $documentReferences;
    }

    private function mapPatientByAbha(string $abhaAddress, string $abhaNumber): array
    {
        if (! $this->db->tableExists('patient_master')) {
            return ['patient_id' => 0];
        }

        $fields = $this->db->getFieldNames('patient_master') ?? [];
        $idCol = $this->resolveExistingColumn($fields, ['id']);
        if ($idCol === null) {
            return ['patient_id' => 0];
        }

        $abhaAddressCol = $this->resolveExistingColumn($fields, ['abha_address', 'abha_addr']);
        $abhaNumberCol = $this->resolveExistingColumn($fields, ['abha_id', 'abha_no', 'abha_number', 'abha']);

        if ($abhaAddress !== '' && $abhaAddressCol !== null) {
            $row = $this->db->table('patient_master')
                ->select($idCol . ' AS patient_id')
                ->where($abhaAddressCol, $abhaAddress)
                ->orderBy($idCol, 'DESC')
                ->get(1)
                ->getRowArray();
            if (! empty($row)) {
                return ['patient_id' => (int) ($row['patient_id'] ?? 0)];
            }
        }

        $digits = preg_replace('/\D+/', '', $abhaNumber) ?? '';
        if ($digits !== '' && $abhaNumberCol !== null) {
            $row = $this->db->table('patient_master')
                ->select($idCol . ' AS patient_id')
                ->where($abhaNumberCol, $digits)
                ->orderBy($idCol, 'DESC')
                ->get(1)
                ->getRowArray();
            if (! empty($row)) {
                return ['patient_id' => (int) ($row['patient_id'] ?? 0)];
            }
        }

        return ['patient_id' => 0];
    }

    private function resolveExistingColumn(array $fields, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $fields, true)) {
                return $candidate;
            }
        }

        return null;
    }

    private function toDateTimeString(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            $dt = new \DateTime($value);
            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }
}

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

        $requestId = trim((string) ($result['request_id'] ?? $payload['request_id'] ?? $payload['requestId'] ?? ''));
        $abhaAddress = trim((string) ($payload['abha_address'] ?? $result['abha_address'] ?? ''));

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

                $docHash = sha1(implode('|', [
                    $requestId,
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

        foreach ($entries as $entry) {
            $resource = is_array($entry['resource'] ?? null) ? (array) $entry['resource'] : [];
            $type = trim((string) ($resource['resourceType'] ?? ''));
            if ($type === '') {
                continue;
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
        ];
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

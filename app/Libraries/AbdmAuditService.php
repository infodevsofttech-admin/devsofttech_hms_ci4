<?php

namespace App\Libraries;

use CodeIgniter\I18n\Time;

/**
 * ABDM Audit Trail Service
 *
 * Records every ABDM operation (push record, link record, consent lifecycle,
 * ABHA validation, callbacks) to the abdm_audit_logs table.
 *
 * The service is FAIL-OPEN: if the DB insert fails for any reason, the
 * exception is swallowed and the calling code continues unaffected.
 *
 * Logged fields per entry:
 *   actor_user_id, actor_name — from session or caller-supplied
 *   action                    — push_record | link_record | consent_request |
 *                               consent_grant | consent_revoke | record_linked |
 *                               consent_callback | abha_validate | push_failure
 *   entity_type, entity_id    — source module + record PK
 *   abha_id, patient_id       — subject identifiers
 *   request_json              — sanitised outbound payload
 *   response_json             — ABDM gateway response
 *   ip_address, user_agent    — requester context
 *   outcome                   — success | failure
 *   error_message             — populated on failure
 */
class AbdmAuditService
{
    private \CodeIgniter\Database\ConnectionInterface $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Write one audit entry.
     *
     * @param array{
     *   action: string,
     *   entity_type?: string,
     *   entity_id?: string|int,
     *   abha_id?: string,
     *   patient_id?: int,
     *   request?: array<mixed>,
     *   response?: array<mixed>,
     *   outcome?: string,
     *   error_message?: string,
     *   actor_user_id?: int,
     *   actor_name?: string,
     * } $data
     */
    public function log(array $data): void
    {
        try {
            $auditTable = null;
            if ($this->tableExistsSafe('audit_logs')) {
                $auditTable = 'audit_logs';
            } elseif ($this->tableExistsSafe('abdm_audit_logs')) {
                $auditTable = 'abdm_audit_logs';
            }

            if ($auditTable === null) {
                return;
            }

            $auditFields = $this->getFieldNamesSafe($auditTable);

            $httpRequest = \Config\Services::request();
            $session     = \Config\Services::session();

            $actorUserId = (int) ($data['actor_user_id'] ?? $session->get('user_id') ?? 0);
            $actorName   = trim((string) ($data['actor_name']
                ?? $session->get('full_name')
                ?? $session->get('name')
                ?? ''));

            // Serialise request / response to JSON; never store raw binary data
            $requestJson  = isset($data['request'])
                ? json_encode($data['request'],  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null;
            $responseJson = isset($data['response'])
                ? json_encode($data['response'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null;

            $entityId = (string) ($data['entity_id'] ?? '');
            $patientId = (int) ($data['patient_id'] ?? 0);

            $insert = [];

            // Preferred generic schema: audit_logs(patient_id, abha_id, action, timestamp, details)
            if (in_array('patient_id', $auditFields, true)) {
                $insert['patient_id'] = $patientId > 0 ? $patientId : 0;
            }
            if (in_array('action', $auditFields, true)) {
                $insert['action'] = $this->mapAuditAction((string) ($data['action'] ?? ''));
            }
            if (in_array('timestamp', $auditFields, true)) {
                $insert['timestamp'] = Time::now('Asia/Kolkata')->toDateTimeString();
            }
            if (in_array('user_id', $auditFields, true)) {
                $insert['user_id'] = $actorUserId > 0 ? $actorUserId : null;
            }
            if (in_array('abha_id', $auditFields, true)) {
                $insert['abha_id'] = trim((string) ($data['abha_id'] ?? '')) !== ''
                    ? trim((string) ($data['abha_id'] ?? ''))
                    : 'UNKNOWN';
            }
            if (in_array('consent_id', $auditFields, true)) {
                $insert['consent_id'] = trim((string) ($data['consent_id'] ?? '')) ?: null;
            }
            if (in_array('transaction_id', $auditFields, true)) {
                $insert['transaction_id'] = trim((string) ($data['transaction_id'] ?? '')) ?: null;
            }
            if (in_array('details', $auditFields, true)) {
                $detailPayload = [
                    'raw_action' => (string) ($data['action'] ?? ''),
                    'entity_type' => (string) ($data['entity_type'] ?? ''),
                    'entity_id' => (string) ($data['entity_id'] ?? ''),
                    'outcome' => (string) ($data['outcome'] ?? 'success'),
                    'error_message' => trim((string) ($data['error_message'] ?? '')),
                    'request' => $data['request'] ?? null,
                    'response' => $data['response'] ?? null,
                ];
                $insert['details'] = (string) json_encode($detailPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            // Extended ABDM schema fields (abdm_audit_logs)
            if (in_array('actor_user_id', $auditFields, true)) {
                $insert['actor_user_id'] = $actorUserId > 0 ? $actorUserId : null;
            }
            if (in_array('actor_name', $auditFields, true)) {
                $insert['actor_name'] = $actorName !== '' ? $actorName : null;
            }
            if (in_array('entity_type', $auditFields, true)) {
                $insert['entity_type'] = (string) ($data['entity_type'] ?? '');
            }
            if (in_array('entity_id', $auditFields, true)) {
                $insert['entity_id'] = $entityId !== '' ? $entityId : null;
            }
            if (in_array('abha_id', $auditFields, true)) {
                $insert['abha_id'] = trim((string) ($data['abha_id'] ?? '')) ?: null;
            }
            if (in_array('request_json', $auditFields, true)) {
                $insert['request_json'] = $requestJson;
            }
            if (in_array('response_json', $auditFields, true)) {
                $insert['response_json'] = $responseJson;
            }
            if (in_array('ip_address', $auditFields, true)) {
                $insert['ip_address'] = $httpRequest->getIPAddress();
            }
            if (in_array('user_agent', $auditFields, true)) {
                $insert['user_agent'] = mb_substr((string) $httpRequest->getUserAgent(), 0, 255);
            }
            if (in_array('outcome', $auditFields, true)) {
                $insert['outcome'] = (string) ($data['outcome'] ?? 'success');
            }
            if (in_array('error_message', $auditFields, true)) {
                $insert['error_message'] = trim((string) ($data['error_message'] ?? '')) ?: null;
            }
            if (in_array('created_at', $auditFields, true)) {
                $insert['created_at'] = Time::now('Asia/Kolkata')->toDateTimeString();
            }

            if (! empty($insert)) {
                $this->db->table($auditTable)->insert($insert);
            }
        } catch (\Throwable) {
            // Fail-open: audit failure must never break the main application flow
        }
    }

    /**
     * Lightweight table existence check that works without relying on
     * ConnectionInterface helper methods.
     */
    private function tableExistsSafe(string $table): bool
    {
        try {
            $result = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getRowArray();
            return ! empty($result);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Returns DB column names using SHOW COLUMNS to stay adapter-safe.
     *
     * @return array<int, string>
     */
    private function getFieldNamesSafe(string $table): array
    {
        try {
            $rows = $this->db->query('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '`')->getResultArray();
            $fields = [];
            foreach ($rows as $row) {
                $field = (string) ($row['Field'] ?? '');
                if ($field !== '') {
                    $fields[] = $field;
                }
            }
            return $fields;
        } catch (\Throwable) {
            return [];
        }
    }

    private function mapAuditAction(string $action): string
    {
        $action = strtolower(trim($action));

        if (str_contains($action, 'discover')) {
            return 'DISCOVERY';
        }
        if (str_contains($action, 'fetch')) {
            return 'FETCH';
        }

        return 'SHARE';
    }
}

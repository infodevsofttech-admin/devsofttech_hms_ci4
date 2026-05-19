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
            if (! $this->db->tableExists('abdm_audit_logs')) {
                return;
            }

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

            $this->db->table('abdm_audit_logs')->insert([
                'actor_user_id' => $actorUserId > 0 ? $actorUserId : null,
                'actor_name'    => $actorName !== '' ? $actorName : null,
                'action'        => (string) ($data['action'] ?? 'unknown'),
                'entity_type'   => (string) ($data['entity_type'] ?? ''),
                'entity_id'     => $entityId !== '' ? $entityId : null,
                'abha_id'       => trim((string) ($data['abha_id'] ?? '')) ?: null,
                'patient_id'    => $patientId > 0 ? $patientId : null,
                'request_json'  => $requestJson,
                'response_json' => $responseJson,
                'ip_address'    => $httpRequest->getIPAddress(),
                'user_agent'    => mb_substr((string) $httpRequest->getUserAgent(), 0, 255),
                'outcome'       => (string) ($data['outcome'] ?? 'success'),
                'error_message' => trim((string) ($data['error_message'] ?? '')) ?: null,
                'created_at'    => Time::now('Asia/Kolkata')->toDateTimeString(),
            ]);
        } catch (\Throwable) {
            // Fail-open: audit failure must never break the main application flow
        }
    }
}

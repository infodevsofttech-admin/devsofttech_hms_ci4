<?php

namespace App\Controllers;

use App\Libraries\Abdm\AbdmConnectorFactory;
use App\Libraries\Abdm\EAtriaBridgeConnector;

/**
 * AbdmOpdQueue
 *
 * OPD reception queue powered by the ABDM Bridge Gateway.
 * Tokens are synced from the gateway into abdm_opd_tokens (local DB) on every fetch.
 *
 * Routes:
 *   GET  AbdmOpdQueue                        → index()            Live reception queue view
 *   GET  AbdmOpdQueue/list                   → list()             HMS-local token list (separate processed list)
 *   GET  AbdmOpdQueue/fetch                  → fetchQueue()       AJAX: poll gateway + sync DB
 *   POST AbdmOpdQueue/token                  → createToken()      AJAX: create manual walk-in token
 *   POST AbdmOpdQueue/token_status/(:num)    → updateTokenStatus  AJAX: CALLED/COMPLETED/CANCELLED
 *   POST AbdmOpdQueue/process_token/(:num)   → processScannedToken AJAX: find/create patient, link token in DB
 */
class AbdmOpdQueue extends BaseController
{
    private EAtriaBridgeConnector $gw;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface  $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface            $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->gw = AbdmConnectorFactory::make();
    }

    // -------------------------------------------------------------------------
    // GET AbdmOpdQueue — Live reception queue screen
    // -------------------------------------------------------------------------
    public function index()
    {
        return view('abdm/opd_queue');
    }

    // -------------------------------------------------------------------------
    // GET AbdmOpdQueue/list — HMS-local token list (processed / unprocessed)
    // -------------------------------------------------------------------------
    public function list()
    {
        $db   = \Config\Database::connect();
        $date = trim((string) ($this->request->getGet('date') ?? date('Y-m-d')));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        // Counts for summary badges
        $counts = $db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(source='scan_share') AS scan_total,
                SUM(source='manual') AS manual_total,
                SUM(patient_id IS NOT NULL) AS processed,
                SUM(patient_id IS NULL) AS unprocessed,
                SUM(status='PENDING') AS pending,
                SUM(status='CALLED') AS called,
                SUM(status='COMPLETED') AS completed,
                SUM(status='CANCELLED') AS cancelled
            FROM abdm_opd_tokens WHERE queue_date='". $db->escapeString($date) ."'"
        )->getRowArray();

        $tokens = $db->query(
            "SELECT t.*, p.p_code, p.p_fname, p.mphone1 as p_phone
            FROM abdm_opd_tokens t
            LEFT JOIN patient_master p ON p.id = t.patient_id
            WHERE t.queue_date = '". $db->escapeString($date) ."'
            ORDER BY t.gateway_token_id ASC"
        )->getResultArray();

        return view('abdm/opd_queue_list', [
            'tokens' => $tokens,
            'counts' => $counts,
            'date'   => $date,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET AbdmOpdQueue/fetch — Poll gateway for today's (or date's) queue
    // -------------------------------------------------------------------------
    public function fetchQueue()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'AJAX only']);
        }

        $date   = trim((string) ($this->request->getGet('date') ?? date('Y-m-d')));
        $status = trim((string) ($this->request->getGet('status') ?? ''));
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit  = min(100, max(10, (int) ($this->request->getGet('limit') ?? 100)));

        // Basic date format guard
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        try {
            $result = $this->gw->opdQueueFetch($date, $status, $page, $limit);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        // Sync fetched tokens into local DB
        $tokens = $result['data'] ?? $result['tokens'] ?? [];
        if (is_array($tokens) && count($tokens) > 0) {
            $this->syncTokensToDb($tokens, $date);
            // Enrich tokens with HMS patient / OPD data from local DB
            $result['data'] = $this->enrichTokensFromDb($tokens, $date);
        }

        return $this->response->setJSON($result);
    }

    // -------------------------------------------------------------------------
    // POST AbdmOpdQueue/token — Create a manual walk-in token on gateway
    // -------------------------------------------------------------------------
    public function createToken()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'AJAX only']);
        }

        $name  = trim((string) ($this->request->getPost('patient_name') ?? ''));
        $phone = trim((string) ($this->request->getPost('phone') ?? ''));
        $abha  = preg_replace('/\D/', '', (string) ($this->request->getPost('abha_number') ?? ''));
        $gender = trim((string) ($this->request->getPost('gender') ?? ''));
        $dept  = trim((string) ($this->request->getPost('department') ?? 'General OPD'));
        $date  = trim((string) ($this->request->getPost('date') ?? date('Y-m-d')));

        if ($name === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Patient name is required']);
        }

        $payload = ['patient_name' => $name, 'department' => $dept ?: 'General OPD'];
        if ($phone !== '') { $payload['phone'] = $phone; }
        if ($abha !== '')  { $payload['abha_number'] = $abha; }
        if ($gender !== '') { $payload['gender'] = strtoupper($gender[0]); }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { $payload['date'] = $date; }

        try {
            $result = $this->gw->opdTokenCreate($payload);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        return $this->response->setJSON($result);
    }

    // -------------------------------------------------------------------------
    // POST AbdmOpdQueue/token_status/:id — Update token status
    // -------------------------------------------------------------------------
    public function updateTokenStatus(int $tokenId)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'AJAX only']);
        }

        $allowed = ['PENDING', 'CALLED', 'COMPLETED', 'CANCELLED'];
        $status  = strtoupper(trim((string) ($this->request->getPost('status') ?? '')));

        if (! in_array($status, $allowed, true)) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Invalid status. Allowed: ' . implode(', ', $allowed)]);
        }

        try {
            $result = $this->gw->opdTokenUpdateStatus($tokenId, $status);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        // Mirror status to local DB so list() stays consistent
        try {
            \Config\Database::connect()
                ->table('abdm_opd_tokens')
                ->where('gateway_token_id', $tokenId)
                ->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
        } catch (\Throwable $e) {
            // non-critical — gateway update already succeeded
        }

        return $this->response->setJSON($result);
    }

    // -------------------------------------------------------------------------
    // POST AbdmOpdQueue/process_token/:id — Find or create HMS patient from token,
    //   return OPD registration URL so reception can open the OPD form.
    // -------------------------------------------------------------------------
    public function processScannedToken(int $tokenId)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'AJAX only']);
        }

        // Fields come from the token row already rendered in the queue table
        $abhaRaw   = preg_replace('/\D/', '', (string) ($this->request->getPost('abha_number') ?? ''));
        $abhaAddr  = trim((string) ($this->request->getPost('abha_address') ?? ''));
        $name      = trim((string) ($this->request->getPost('patient_name') ?? ''));
        $phone     = trim((string) ($this->request->getPost('phone') ?? ''));
        $gender    = strtoupper(trim((string) ($this->request->getPost('gender') ?? '')));
        $dob       = trim((string) ($this->request->getPost('dob') ?? ''));      // YYYY-MM-DD from gateway

        if ($name === '' && $abhaRaw === '' && $phone === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Insufficient patient data in token']);
        }

        $db     = \Config\Database::connect();
        $fields = $db->getFieldNames('patient_master') ?? [];

        // Detect ABHA column
        $abhaField = null;
        foreach (['abha_id', 'abha_no', 'abha', 'abha_address'] as $f) {
            if (in_array($f, $fields, true)) { $abhaField = $f; break; }
        }

        $existing = null;

        // 1. Search by ABHA number (14-digit)
        if ($abhaField && $abhaRaw !== '' && strlen($abhaRaw) === 14) {
            $existing = $db->table('patient_master')
                ->where($abhaField, $abhaRaw)
                ->get()->getRowArray();
        }

        // 2. Search by ABHA address
        if (! $existing && $abhaField && $abhaAddr !== '') {
            $existing = $db->table('patient_master')
                ->where($abhaField, $abhaAddr)
                ->get()->getRowArray();
        }

        // 3. Fallback: mobile
        if (! $existing && $phone !== '') {
            $existing = $db->table('patient_master')
                ->where('mphone1', $phone)
                ->get()->getRowArray();
        }

        $isNew = false;

        if ($existing) {
            $patientId = (int) ($existing['id'] ?? 0);
            $pCode     = (string) ($existing['p_code'] ?? '');
            // Backfill ABHA if empty
            if ($abhaField && empty($existing[$abhaField]) && $abhaRaw !== '') {
                $db->table('patient_master')->where('id', $patientId)->update([$abhaField => $abhaRaw]);
            }
        } else {
            // Create new patient
            $genderDb = ($gender === 'F' || $gender === '2') ? 2 : 1;

            // Convert DOB: YYYY-MM-DD (gateway) → already correct for MySQL
            $dobDb = '';
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
                $dobDb = $dob;
            }

            $insertData = [
                'p_fname'      => strtoupper($name !== '' ? $name : 'ABHA PATIENT'),
                'mphone1'      => $phone,
                'gender'       => $genderDb,
                'blood_group'  => 'Not Define',
                'estimate_dob' => $dobDb !== '' ? 0 : 1,
            ];
            if ($dobDb !== '') {
                $insertData['dob'] = $dobDb;
            } else {
                $insertData['age'] = 0;
                $insertData['age_in_month'] = 0;
            }
            if ($abhaField && $abhaRaw !== '') {
                $insertData[$abhaField] = $abhaRaw;
            }

            // Generate p_code
            $today       = date('y') . date('m');
            $countRow    = $db->query("SELECT COUNT(*) as cnt FROM patient_master WHERE p_code LIKE 'P{$today}%'")->getRow();
            $seq         = str_pad(((int) ($countRow->cnt ?? 0)) + 1, 4, '0', STR_PAD_LEFT);
            $insertData['p_code'] = 'P' . $today . $seq;

            $db->table('patient_master')->insert($insertData);
            $patientId = (int) $db->insertID();
            $pCode     = $insertData['p_code'];
            $isNew     = true;
        }

        if ($patientId <= 0) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => 'Failed to resolve patient record']);
        }

        // Update local DB record: link patient
        $db->table('abdm_opd_tokens')
            ->where('gateway_token_id', $tokenId)
            ->update([
                'patient_id'   => $patientId,
                'status'       => 'CALLED',
                'processed_at' => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

        // Mark token as CALLED on gateway (non-blocking)
        try {
            $this->gw->opdTokenUpdateStatus($tokenId, 'CALLED');
        } catch (\Throwable $e) {
            // non-critical
        }

        return $this->response->setJSON([
            'ok'           => 1,
            'patient_id'   => $patientId,
            'p_code'       => $pCode,
            'is_new'       => $isNew,
            'redirect_url' => base_url('Opd/addopd/' . $patientId),
        ]);
    }

    // -------------------------------------------------------------------------
    // Private: merge local DB HMS data into gateway token array
    // -------------------------------------------------------------------------
    private function enrichTokensFromDb(array $tokens, string $date): array
    {
        $db = \Config\Database::connect();

        // Fetch all local rows for this date in one query
        $gids = array_filter(array_map(fn($t) => (int) ($t['id'] ?? 0), $tokens));
        if (count($gids) === 0) {
            return $tokens;
        }

        $rows = $db->query(
            "SELECT t.gateway_token_id, t.patient_id, t.opd_id, t.processed_at,
                    p.p_code, p.p_fname
             FROM abdm_opd_tokens t
             LEFT JOIN patient_master p ON p.id = t.patient_id
             WHERE t.queue_date = '" . $db->escapeString($date) . "'
               AND t.gateway_token_id IN (" . implode(',', $gids) . ")"
        )->getResultArray();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['gateway_token_id']] = $r;
        }

        foreach ($tokens as &$t) {
            $gid = (int) ($t['id'] ?? 0);
            if (isset($map[$gid])) {
                $local = $map[$gid];
                $t['hms_patient_id']   = $local['patient_id'];
                $t['hms_opd_id']       = $local['opd_id'];
                $t['hms_processed_at'] = $local['processed_at'];
                $t['hms_p_code']       = $local['p_code'];
                $t['hms_p_name']       = $local['p_fname'];
                $t['hms_opd_url']      = $local['patient_id']
                    ? base_url('Opd/addopd/' . (int) $local['patient_id'])
                    : null;
                $t['hms_profile_url']  = $local['patient_id']
                    ? base_url('Patient/person_profile/' . (int) $local['patient_id'])
                    : null;
            }
        }
        unset($t);

        return $tokens;
    }

    // -------------------------------------------------------------------------
    // Private: upsert tokens from gateway into abdm_opd_tokens
    // -------------------------------------------------------------------------
    private function syncTokensToDb(array $tokens, string $date): void
    {
        $db = \Config\Database::connect();

        foreach ($tokens as $t) {
            $gid = (int) ($t['id'] ?? 0);
            if ($gid <= 0) {
                continue;
            }

            $dob = null;
            if (! empty($t['dob']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $t['dob'])) {
                $dob = $t['dob'];
            }

            $row = [
                'token_number' => (string) ($t['token_number'] ?? ''),
                'queue_date'   => $date,
                'patient_name' => (string) ($t['patient_name'] ?? ''),
                'abha_number'  => preg_replace('/\D/', '', (string) ($t['abha_number'] ?? '')),
                'abha_address' => (string) ($t['abha_address'] ?? ''),
                'gender'       => strtoupper(substr((string) ($t['gender'] ?? ''), 0, 1)),
                'dob'          => $dob,
                'phone'        => (string) ($t['phone'] ?? ''),
                'department'   => (string) ($t['department'] ?? 'General OPD'),
                'source'       => (string) ($t['source'] ?? 'manual'),
                'status'       => strtoupper((string) ($t['status'] ?? 'PENDING')),
                'updated_at'   => date('Y-m-d H:i:s'),
            ];

            $exists = $db->table('abdm_opd_tokens')
                ->where('gateway_token_id', $gid)
                ->where('queue_date', $date)
                ->countAllResults();

            if ($exists > 0) {
                // Only update non-processed records' status from gateway
                $db->table('abdm_opd_tokens')
                    ->where('gateway_token_id', $gid)
                    ->where('queue_date', $date)
                    ->where('patient_id IS NULL', null, false)
                    ->update(['status' => $row['status'], 'updated_at' => $row['updated_at']]);
            } else {
                $row['gateway_token_id'] = $gid;
                $row['created_at']       = date('Y-m-d H:i:s');
                try {
                    $db->table('abdm_opd_tokens')->insert($row);
                } catch (\Throwable $e) {
                    // ignore duplicate on race
                }
            }
        }
    }
}

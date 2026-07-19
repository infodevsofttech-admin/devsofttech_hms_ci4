<?php

namespace App\Controllers;

use App\Libraries\Abdm\AbdmConnectorFactory;
use App\Libraries\Abdm\EAtriaBridgeConnector;
use App\Models\ClinicalAuditTrailModel;

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

    /**
     * Allowed status transitions for OPD token lifecycle.
     * PENDING -> CALLED/CANCELLED
     * CALLED -> COMPLETED/CANCELLED/PENDING (revert)
     * COMPLETED/CANCELLED -> PENDING (reopen)
     */
    private const STATUS_TRANSITIONS = [
        'PENDING' => ['CALLED', 'CANCELLED'],
        'CALLED' => ['COMPLETED', 'CANCELLED', 'PENDING'],
        'COMPLETED' => ['PENDING'],
        'CANCELLED' => ['PENDING'],
    ];

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

        // Keep token->appointment linkage up to date for history display.
        $this->linkTokensToAppointments($date);
        $this->syncCompletedTokensFromOpd($date);

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
            $this->linkTokensToAppointments($date);
            $this->syncCompletedTokensFromOpd($date);
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

        $db = \Config\Database::connect();
        $current = $db->query(
            "SELECT status
             FROM abdm_opd_tokens
             WHERE gateway_token_id = " . (int) $tokenId . "
             ORDER BY id DESC
             LIMIT 1"
        )->getRowArray();
        $currentStatus = strtoupper(trim((string) ($current['status'] ?? '')));

        if ($currentStatus !== '' && $currentStatus === $status) {
            return $this->response->setJSON([
                'ok' => 1,
                'token_id' => $tokenId,
                'status' => $status,
                'message' => 'Token is already in requested status',
            ]);
        }

        if ($currentStatus !== '' && ! $this->canTransitionStatus($currentStatus, $status)) {
            return $this->response->setStatusCode(409)->setJSON([
                'ok' => 0,
                'error_text' => 'Invalid transition: ' . $currentStatus . ' -> ' . $status,
            ]);
        }

        try {
            $result = $this->gw->opdTokenUpdateStatus($tokenId, $status);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        if (empty($result['ok']) || (int) $result['ok'] !== 1) {
            $message = (string) (
                $result['error_text']
                ?? $result['message']
                ?? $result['error']
                ?? 'Bridge rejected OPD token status update'
            );

            return $this->response
                ->setStatusCode((int) ($result['http_code'] ?? 502) ?: 502)
                ->setJSON([
                    'ok' => 0,
                    'error_text' => $message,
                    'bridge_http_code' => (int) ($result['http_code'] ?? 0),
                ]);
        }

        // Mirror status to local DB so list() stays consistent
        try {
            $db->table('abdm_opd_tokens')
                ->where('gateway_token_id', $tokenId)
                ->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);

            if ($currentStatus !== '' && $currentStatus !== $status) {
                $this->logTokenAudit(
                    'abdm_opd_queue',
                    (string) $tokenId,
                    'status',
                    $currentStatus,
                    $status,
                    [
                        'source' => 'queue_action',
                        'route' => 'AbdmOpdQueue/token_status',
                    ]
                );
            }
        } catch (\Throwable $e) {
            // non-critical — gateway update already succeeded
        }

        return $this->response->setJSON($result);
    }

    private function canTransitionStatus(string $fromStatus, string $toStatus): bool
    {
        $from = strtoupper(trim($fromStatus));
        $to = strtoupper(trim($toStatus));
        if ($from === '' || $to === '') {
            return false;
        }
        if ($from === $to) {
            return true;
        }

        return in_array($to, self::STATUS_TRANSITIONS[$from] ?? [], true);
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

        $action = strtolower(trim((string) ($this->request->getPost('action') ?? 'auto')));

        // Fields come from the token row already rendered in the queue table
        $abhaRaw   = preg_replace('/\D/', '', (string) ($this->request->getPost('abha_number') ?? ''));
        $abhaAddr  = trim((string) ($this->request->getPost('abha_address') ?? ''));
        $aadhaarRaw = preg_replace('/\D/', '', (string) (
            $this->request->getPost('aadhaar_number')
            ?? $this->request->getPost('aadhar_number')
            ?? $this->request->getPost('udai')
            ?? ''
        ));
        $name      = trim((string) ($this->request->getPost('patient_name') ?? ''));
        $phone     = trim((string) ($this->request->getPost('phone') ?? ''));
        $gender    = strtoupper(trim((string) ($this->request->getPost('gender') ?? '')));
        $dob       = trim((string) ($this->request->getPost('dob') ?? ''));      // YYYY-MM-DD from gateway
        $age       = trim((string) ($this->request->getPost('age') ?? $this->request->getPost('patient_age') ?? ''));
        $birthYear = trim((string) ($this->request->getPost('birth_year') ?? $this->request->getPost('birthYear') ?? $this->request->getPost('year_of_birth') ?? ''));
        $relationText = trim((string) ($this->request->getPost('relation_text') ?? ''));
        $relationType = trim((string) ($this->request->getPost('relation_type') ?? ''));
        $relationName = trim((string) ($this->request->getPost('relative_name') ?? ''));
        $email      = trim((string) ($this->request->getPost('email') ?? ''));
        $address    = trim((string) ($this->request->getPost('address') ?? ''));
        $city       = trim((string) ($this->request->getPost('city') ?? ''));
        $district   = trim((string) ($this->request->getPost('district') ?? ''));
        $state      = trim((string) ($this->request->getPost('state') ?? ''));
        $zip        = trim((string) ($this->request->getPost('zip') ?? ''));
        $existingPatientId = (int) ($this->request->getPost('existing_patient_id') ?? 0);

        if ($name === '' && $abhaRaw === '' && $phone === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Insufficient patient data in token']);
        }

        $db     = \Config\Database::connect();
        $fields = $db->getFieldNames('patient_master') ?? [];

        // Detect ABHA column
        $abhaField    = $this->resolveFirstExistingColumn($fields, ['abha_id', 'abha_no', 'abha', 'abha_address']);
        $aadhaarField = $this->resolveFirstExistingColumn($fields, ['udai', 'aadhar_no', 'aadhaar_no', 'aadhaar', 'adhar_no']);

        $matches = $this->findPatientMatches($db, $abhaField, $aadhaarField, $abhaRaw, $abhaAddr, $aadhaarRaw, $phone, $name, $gender, $dob, $age, $birthYear);

        if ($action === 'check') {
            return $this->response->setJSON([
                'ok'                    => 1,
                'requires_confirmation' => count($matches) > 0 ? 1 : 0,
                'matches'               => $matches,
                'token_data'            => [
                    'id'            => $tokenId,
                    'abha_number'   => $abhaRaw,
                    'abha_address'  => $abhaAddr,
                    'aadhaar_number'=> $aadhaarRaw,
                    'patient_name'  => $name,
                    'phone'         => $phone,
                    'gender'        => $gender,
                    'dob'           => $dob,
                    'age'           => $age,
                    'birth_year'    => $birthYear,
                    'relation_text' => $relationText,
                    'relation_type' => $relationType,
                    'relative_name' => $relationName,
                    'email'         => $email,
                    'address'       => $address,
                    'city'          => $city,
                    'district'      => $district,
                    'state'         => $state,
                    'zip'           => $zip,
                ],
            ]);
        }

        if (($action === '' || $action === 'auto') && count($matches) > 1) {
            return $this->response->setJSON([
                'ok'                    => 1,
                'requires_confirmation' => 1,
                'matches'               => $matches,
                'token_data'            => [
                    'id'            => $tokenId,
                    'abha_number'   => $abhaRaw,
                    'abha_address'  => $abhaAddr,
                    'aadhaar_number'=> $aadhaarRaw,
                    'patient_name'  => $name,
                    'phone'         => $phone,
                    'gender'        => $gender,
                    'dob'           => $dob,
                    'age'           => $age,
                    'birth_year'    => $birthYear,
                    'relation_text' => $relationText,
                    'relation_type' => $relationType,
                    'relative_name' => $relationName,
                    'email'         => $email,
                    'address'       => $address,
                    'city'          => $city,
                    'district'      => $district,
                    'state'         => $state,
                    'zip'           => $zip,
                ],
            ]);
        }

        $patientId = 0;
        $pCode     = '';
        $isNew = false;
        $created = ['saved_data' => null];

        if ($action === 'link_existing') {
            if ($existingPatientId <= 0) {
                return $this->response->setJSON(['ok' => 0, 'error_text' => 'Please select an existing patient']);
            }
            $existing = $db->table('patient_master')
                ->select('id,p_code,mphone1' . ($abhaField ? ',' . $abhaField . ' AS patient_abha' : '') . ($aadhaarField ? ',' . $aadhaarField . ' AS patient_aadhaar' : ''))
                ->where('id', $existingPatientId)
                ->get()->getRowArray();
            if (! $existing) {
                return $this->response->setJSON(['ok' => 0, 'error_text' => 'Selected patient record not found']);
            }

            $patientId = (int) ($existing['id'] ?? 0);
            $pCode     = (string) ($existing['p_code'] ?? '');

            $backfill = [];
            if ($abhaField && trim((string) ($existing['patient_abha'] ?? '')) === '' && $abhaRaw !== '') {
                $backfill[$abhaField] = $abhaRaw;
            }
            if ($aadhaarField && trim((string) ($existing['patient_aadhaar'] ?? '')) === '' && $aadhaarRaw !== '') {
                $backfill[$aadhaarField] = $aadhaarRaw;
            }
            if (trim((string) ($existing['mphone1'] ?? '')) === '' && $phone !== '') {
                $backfill['mphone1'] = $phone;
            }
            if (! empty($backfill)) {
                $db->table('patient_master')->where('id', $patientId)->update($backfill);
            }
        } elseif (($action === '' || $action === 'auto') && count($matches) === 1 && (int) ($matches[0]['match_score'] ?? 0) > 1) {
            $existing = $matches[0];
            $patientId = (int) ($existing['id'] ?? 0);
            $pCode     = (string) ($existing['p_code'] ?? '');
        } else {
            $created = $this->createPatientFromToken(
                $db,
                $abhaField,
                $aadhaarField,
                $name,
                $phone,
                $gender,
                $dob,
                $abhaRaw,
                $aadhaarRaw,
                [
                    'relation_text' => $relationText,
                    'relation_type' => $relationType,
                    'relative_name' => $relationName,
                    'email'         => $email,
                    'address'       => $address,
                    'city'          => $city,
                    'district'      => $district,
                    'state'         => $state,
                    'zip'           => $zip,
                ]
            );
            $patientId = (int) ($created['patient_id'] ?? 0);
            $pCode     = (string) ($created['p_code'] ?? '');
            $isNew     = true;
        }

        if ($patientId <= 0) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => 'Failed to resolve patient record']);
        }

        $tokenBefore = $db->table('abdm_opd_tokens')
            ->select('id,status,patient_id')
            ->where('gateway_token_id', $tokenId)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        $this->attachPatientToToken($db, $tokenId, $patientId);

        $oldStatus = strtoupper(trim((string) ($tokenBefore['status'] ?? '')));
        if ($oldStatus !== '') {
            $this->logTokenAudit(
                'abdm_opd_queue',
                (string) $tokenId,
                'patient_id',
                (string) ($tokenBefore['patient_id'] ?? ''),
                (string) $patientId,
                [
                    'source' => 'register_opd',
                    'action' => $action,
                    'patient_id' => $patientId,
                    'is_new_patient' => $isNew ? 1 : 0,
                ]
            );
        }

        return $this->response->setJSON([
            'ok'           => 1,
            'patient_id'   => $patientId,
            'p_code'       => $pCode,
            'is_new'       => $isNew,
            'saved_data'   => $created['saved_data'] ?? null,
            'redirect_url' => base_url('Opd/addopd/' . $patientId),
            'profile_url'  => base_url('Patient/person_record/' . $patientId),
            'edit_url'     => base_url('Patient/person_record/' . $patientId . '/1'),
        ]);
    }

    private function resolveFirstExistingColumn(array $fields, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $fields, true)) {
                return $candidate;
            }
        }
        return null;
    }

    private function findPatientMatches(
        \CodeIgniter\Database\BaseConnection $db,
        ?string $abhaField,
        ?string $aadhaarField,
        string $abhaRaw,
        string $abhaAddr,
        string $aadhaarRaw,
        string $phone,
        string $name = '',
        string $gender = '',
        string $dob = '',
        string $age = '',
        string $birthYear = ''
    ): array {
        $select = 'id,p_code,p_fname,mphone1,dob,gender';
        if ($abhaField) {
            $select .= ',' . $abhaField . ' AS patient_abha';
        }
        if ($aadhaarField) {
            $select .= ',' . $aadhaarField . ' AS patient_aadhaar';
        }

        $bucket = [];
        $append = static function (array $rows, string $reason, int $score) use (&$bucket): void {
            foreach ($rows as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                if (! isset($bucket[$id])) {
                    $row['match_reasons'] = [];
                    $row['match_score']   = 0;
                    $bucket[$id] = $row;
                }
                if (! in_array($reason, $bucket[$id]['match_reasons'], true)) {
                    $bucket[$id]['match_reasons'][] = $reason;
                }
                if ($score > ($bucket[$id]['match_score'] ?? 0)) {
                    $bucket[$id]['match_score'] = $score;
                }
            }
        };

        // Definitive identifier matches (score 4)
        if ($abhaField && $abhaRaw !== '') {
            $rows = $db->table('patient_master')->select($select)->where($abhaField, $abhaRaw)->limit(10)->get()->getResultArray();
            $append($rows, 'ABHA No', 4);
        }

        if ($abhaField && $abhaAddr !== '' && $abhaAddr !== $abhaRaw) {
            $rows = $db->table('patient_master')->select($select)->where($abhaField, $abhaAddr)->limit(10)->get()->getResultArray();
            $append($rows, 'ABHA Address', 4);
        }

        if ($aadhaarField && $aadhaarRaw !== '') {
            $rows = $db->table('patient_master')->select($select)->where($aadhaarField, $aadhaarRaw)->limit(10)->get()->getResultArray();
            $append($rows, 'Aadhaar', 4);
        }

        // Demographic matches — primary criteria
        $nameUpper = strtoupper(trim($name));
        $genderDb  = ($gender === 'F' || $gender === '2') ? 2 : ($gender !== '' ? 1 : null);
        $dobValid  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob) ? $dob : '';
        $yearOnly  = $dobValid !== '' ? (int) substr($dobValid, 0, 4) : (preg_match('/^(19|20)\d{2}$/', $birthYear) ? (int) $birthYear : 0);
        $ageYears  = preg_match('/^\d{1,3}$/', $age) ? (int) $age : null;

        if ($nameUpper !== '' && $dobValid !== '') {
            // Name + exact DOB [+ gender] — high confidence (score 3)
            $q = $db->table('patient_master')->select($select)
                ->where('UPPER(p_fname)', $nameUpper)
                ->where('dob', $dobValid);
            if ($genderDb !== null) {
                $q->where('gender', $genderDb);
                $rows = $q->limit(10)->get()->getResultArray();
                $append($rows, 'Name + DOB + Gender', 3);
            } else {
                $rows = $q->limit(10)->get()->getResultArray();
                $append($rows, 'Name + DOB', 3);
            }
        }

        if ($nameUpper !== '' && $yearOnly > 0) {
            // Name + year of birth [+ gender] — medium confidence (score 2)
            $q = $db->table('patient_master')->select($select)
                ->where('UPPER(p_fname)', $nameUpper)
                ->where('YEAR(dob)', $yearOnly);
            if ($genderDb !== null) {
                $q->where('gender', $genderDb);
                $rows = $q->limit(10)->get()->getResultArray();
                $append($rows, 'Name + Year of Birth + Gender', 2);
            } else {
                $rows = $q->limit(10)->get()->getResultArray();
                $append($rows, 'Name + Year of Birth', 2);
            }
        }

        foreach ($this->findFuzzyDemographicMatches($db, $select, $nameUpper, $genderDb, $dobValid, $yearOnly, $ageYears) as $row) {
            $reason = (string) ($row['_match_reason'] ?? 'Similar Name + Demographics');
            $score  = (int) ($row['_match_score'] ?? 2);
            unset($row['_match_reason'], $row['_match_score']);
            $append([$row], $reason, $score);
        }

        // Phone match — supplementary only (score 1), ambiguous for families
        if ($phone !== '') {
            $rows = $db->table('patient_master')->select($select)->where('mphone1', $phone)->limit(10)->get()->getResultArray();
            $append($rows, 'Phone', 1);
        }

        // Assign human-readable confidence label and sort by score descending
        $confidenceLabel = static function (int $score): string {
            if ($score >= 4) { return 'definitive'; }
            if ($score === 3) { return 'high'; }
            if ($score === 2) { return 'medium'; }
            return 'low';
        };

        $result = array_values($bucket);
        usort($result, static function ($a, $b) {
            return ($b['match_score'] ?? 0) <=> ($a['match_score'] ?? 0);
        });

        foreach ($result as &$row) {
            $row['match_confidence'] = $confidenceLabel((int) ($row['match_score'] ?? 0));
        }
        unset($row);

        return $result;
    }

    private function findFuzzyDemographicMatches(
        \CodeIgniter\Database\BaseConnection $db,
        string $select,
        string $nameUpper,
        ?int $genderDb,
        string $dobValid,
        int $yearOnly,
        ?int $ageYears
    ): array {
        if ($nameUpper === '' || strlen($this->normalizeMatchName($nameUpper)) < 4) {
            return [];
        }

        $fields = $db->getFieldNames('patient_master') ?? [];
        $queries = [];

        if ($dobValid !== '') {
            $queries[] = ['reason' => $genderDb !== null ? 'Similar Name + DOB + Gender' : 'Similar Name + DOB', 'score' => 3, 'dob' => $dobValid];
        }
        if ($yearOnly > 0) {
            $queries[] = ['reason' => $genderDb !== null ? 'Similar Name + Year of Birth + Gender' : 'Similar Name + Year of Birth', 'score' => 2, 'year' => $yearOnly];
        }
        if ($ageYears !== null && in_array('age', $fields, true)) {
            $queries[] = ['reason' => $genderDb !== null ? 'Similar Name + Age + Gender' : 'Similar Name + Age', 'score' => 2, 'age' => $ageYears];
        }

        $matches = [];
        foreach ($queries as $query) {
            $builder = $db->table('patient_master')->select($select);
            if (isset($query['dob'])) {
                $builder->where('dob', $query['dob']);
            }
            if (isset($query['year'])) {
                $builder->where('YEAR(dob)', (int) $query['year']);
            }
            if (isset($query['age'])) {
                $builder->where('age', (int) $query['age']);
            }
            if ($genderDb !== null) {
                $builder->where('gender', $genderDb);
            }

            foreach ($builder->limit(100)->get()->getResultArray() as $row) {
                if (! $this->isSimilarPatientName($nameUpper, (string) ($row['p_fname'] ?? ''))) {
                    continue;
                }
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0 || isset($matches[$id])) {
                    continue;
                }
                $row['_match_reason'] = (string) $query['reason'];
                $row['_match_score']  = (int) $query['score'];
                $matches[$id] = $row;
            }
        }

        return array_values($matches);
    }

    private function normalizeMatchName(string $name): string
    {
        return preg_replace('/[^A-Z0-9]+/', '', strtoupper($name)) ?? '';
    }

    private function isSimilarPatientName(string $incomingName, string $storedName): bool
    {
        $incoming = $this->normalizeMatchName($incomingName);
        $stored   = $this->normalizeMatchName($storedName);
        if ($incoming === '' || $stored === '' || $incoming === $stored) {
            return false;
        }

        $maxDistance = strlen($incoming) <= 8 ? 1 : (strlen($incoming) <= 14 ? 2 : 3);
        similar_text($incoming, $stored, $percent);
        if (levenshtein($incoming, $stored) <= $maxDistance || $percent >= 86.0) {
            return true;
        }

        $incomingFirst = $this->normalizeMatchName(strtok($incomingName, ' ') ?: $incomingName);
        $storedFirst   = $this->normalizeMatchName(strtok($storedName, ' ') ?: $storedName);
        if (strlen($incomingFirst) < 4 || strlen($storedFirst) < 4) {
            return false;
        }

        $firstDistance = strlen($incomingFirst) <= 8 ? 1 : 2;
        similar_text($incomingFirst, $storedFirst, $firstPercent);

        return levenshtein($incomingFirst, $storedFirst) <= $firstDistance || $firstPercent >= 84.0;
    }

    private function createPatientFromToken(
        \CodeIgniter\Database\BaseConnection $db,
        ?string $abhaField,
        ?string $aadhaarField,
        string $name,
        string $phone,
        string $gender,
        string $dob,
        string $abhaRaw,
        string $aadhaarRaw,
        array $extra
    ): array {
        $fields = $db->getFieldNames('patient_master') ?? [];
        $genderDb = ($gender === 'F' || $gender === '2') ? 2 : 1;

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
        if ($aadhaarField && $aadhaarRaw !== '') {
            $insertData[$aadhaarField] = $aadhaarRaw;
        }

        $relationType = strtoupper(trim((string) ($extra['relation_type'] ?? '')));
        $relationName = strtoupper(trim((string) ($extra['relative_name'] ?? '')));
        $relationText = trim((string) ($extra['relation_text'] ?? ''));

        if (($relationType === '' || $relationName === '') && $relationText !== '' && preg_match('/^(.+?)\s+of\s+(.+)$/i', $relationText, $m)) {
            if ($relationType === '') {
                $relationType = strtoupper(trim($m[1]));
            }
            if ($relationName === '') {
                $relationName = strtoupper(trim($m[2]));
            }
        }

        $optionalMap = [
            'p_relative' => $relationType,
            'p_rname'    => $relationName,
            'email1'     => trim((string) ($extra['email'] ?? '')),
            'add1'       => trim((string) ($extra['address'] ?? '')),
            'city'       => trim((string) ($extra['city'] ?? '')),
            'district'   => trim((string) ($extra['district'] ?? '')),
            'state'      => trim((string) ($extra['state'] ?? '')),
            'zip'        => trim((string) ($extra['zip'] ?? '')),
        ];

        foreach ($optionalMap as $col => $val) {
            if ($val !== '' && in_array($col, $fields, true)) {
                $insertData[$col] = $val;
            }
        }

        $today       = date('y') . date('m');
        $countRow    = $db->query("SELECT COUNT(*) as cnt FROM patient_master WHERE p_code LIKE 'P{$today}%'")->getRow();
        $seq         = str_pad(((int) ($countRow->cnt ?? 0)) + 1, 4, '0', STR_PAD_LEFT);
        $insertData['p_code'] = 'P' . $today . $seq;

        $db->table('patient_master')->insert($insertData);

        return [
            'patient_id' => (int) $db->insertID(),
            'p_code'     => (string) $insertData['p_code'],
            'saved_data' => [
                'patient_name'  => (string) ($insertData['p_fname'] ?? ''),
                'phone'         => (string) ($insertData['mphone1'] ?? ''),
                'gender'        => $genderDb === 2 ? 'Female' : 'Male',
                'dob'           => (string) ($insertData['dob'] ?? ''),
                'abha'          => $abhaRaw,
                'aadhaar'       => $aadhaarRaw,
                'relation'      => trim($relationType . ($relationName !== '' ? ' of ' . $relationName : '')),
                'email'         => (string) ($insertData['email1'] ?? ''),
                'address'       => (string) ($insertData['add1'] ?? ''),
                'city'          => (string) ($insertData['city'] ?? ''),
                'district'      => (string) ($insertData['district'] ?? ''),
                'state'         => (string) ($insertData['state'] ?? ''),
                'zip'           => (string) ($insertData['zip'] ?? ''),
            ],
        ];
    }

    private function attachPatientToToken(\CodeIgniter\Database\BaseConnection $db, int $tokenId, int $patientId): void
    {
        $db->table('abdm_opd_tokens')
            ->where('gateway_token_id', $tokenId)
            ->update([
                'patient_id'   => $patientId,
                'processed_at' => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
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
                  p.p_code, p.p_fname,
                  o.opd_code, o.opd_status
             FROM abdm_opd_tokens t
             LEFT JOIN patient_master p ON p.id = t.patient_id
              LEFT JOIN opd_master o ON o.opd_id = t.opd_id
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
                $t['hms_opd_code']     = $local['opd_code'] ?? null;
                $t['hms_opd_status']   = $local['opd_status'] ?? null;
                $t['hms_processed_at'] = $local['processed_at'];
                $t['hms_p_code']       = $local['p_code'];
                $t['hms_p_name']       = $local['p_fname'];
                $t['hms_opd_url']      = $local['patient_id']
                    ? base_url('Opd/addopd/' . (int) $local['patient_id'])
                    : null;
                $t['hms_profile_url']  = $local['patient_id']
                    ? base_url('Patient/person_record/' . (int) $local['patient_id'])
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

    // -------------------------------------------------------------------------
    // Private: link processed ABDM tokens with created OPD appointments
    // -------------------------------------------------------------------------
    private function linkTokensToAppointments(string $date): void
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('abdm_opd_tokens') || ! $db->tableExists('opd_master')) {
            return;
        }

        $rows = $db->query(
            "SELECT id, patient_id, processed_at
             FROM abdm_opd_tokens
             WHERE queue_date = '" . $db->escapeString($date) . "'
               AND patient_id IS NOT NULL
               AND (opd_id IS NULL OR opd_id = 0)
             ORDER BY processed_at ASC, id ASC"
        )->getResultArray();

        if (empty($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $tokenRowId = (int) ($row['id'] ?? 0);
            $patientId  = (int) ($row['patient_id'] ?? 0);
            if ($tokenRowId <= 0 || $patientId <= 0) {
                continue;
            }

            $apdWhere = "p_id = " . $patientId . " AND DATE(apointment_date) = '" . $db->escapeString($date) . "'";

            $opd = $db->query(
                "SELECT opd_id
                 FROM opd_master
                 WHERE " . $apdWhere . "
                 ORDER BY opd_id DESC
                 LIMIT 1"
            )->getRowArray();

            $opdId = (int) ($opd['opd_id'] ?? 0);
            if ($opdId <= 0) {
                continue;
            }

            $db->table('abdm_opd_tokens')
                ->where('id', $tokenRowId)
                ->update([
                    'opd_id' => $opdId,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->logTokenAudit(
                'abdm_opd_queue',
                (string) $tokenRowId,
                'opd_id',
                '',
                (string) $opdId,
                [
                    'source' => 'link_tokens_to_appointments',
                    'queue_date' => $date,
                ]
            );
        }
    }

    private function syncCompletedTokensFromOpd(string $date): void
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('abdm_opd_tokens') || ! $db->tableExists('opd_master')) {
            return;
        }

        $rows = $db->query(
            "SELECT t.id, t.gateway_token_id, t.status, t.opd_id
             FROM abdm_opd_tokens t
             JOIN opd_master o ON o.opd_id = t.opd_id
             WHERE t.queue_date = '" . $db->escapeString($date) . "'
               AND t.opd_id IS NOT NULL
               AND t.status <> 'COMPLETED'
               AND o.opd_status = 2"
        )->getResultArray();

        foreach ($rows as $row) {
            $tokenId = (int) ($row['gateway_token_id'] ?? 0);
            $localId = (int) ($row['id'] ?? 0);
            $oldStatus = strtoupper(trim((string) ($row['status'] ?? '')));
            if ($tokenId <= 0 || $localId <= 0 || $oldStatus === 'COMPLETED') {
                continue;
            }

            $gatewayError = '';
            try {
                $this->gw->opdTokenUpdateStatus($tokenId, 'COMPLETED');
            } catch (\Throwable $e) {
                $gatewayError = $e->getMessage();
            }

            $db->table('abdm_opd_tokens')
                ->where('id', $localId)
                ->update([
                    'status' => 'COMPLETED',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $this->logTokenAudit(
                'abdm_opd_queue',
                (string) $tokenId,
                'status',
                $oldStatus,
                'COMPLETED',
                [
                    'source' => 'opd_visit_done_auto',
                    'queue_date' => $date,
                    'opd_id' => (int) ($row['opd_id'] ?? 0),
                    'gateway_sync' => $gatewayError === '' ? 'ok' : 'failed',
                    'gateway_error' => $gatewayError,
                ]
            );
        }
    }

    private function logTokenAudit(
        string $module,
        string $recordId,
        string $fieldName,
        ?string $oldValue,
        ?string $newValue,
        array $actionMeta = []
    ): void {
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('clinical_audit_trail')) {
                return;
            }

            $user = auth()->user();
            $userId = (string) ($user->id ?? 0);
            $payload = [
                'module' => $module,
                'record_id' => $recordId,
                'field_name' => $fieldName,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'user_id' => $userId,
                'action_at' => date('Y-m-d H:i:s'),
            ];

            if (! empty($actionMeta)) {
                $payload['new_value'] = trim((string) $newValue) . ' | ' . json_encode($actionMeta, JSON_UNESCAPED_SLASHES);
            }

            $payload['hash'] = hash('sha256', implode('|', [
                $payload['module'],
                $payload['record_id'],
                $payload['field_name'],
                (string) $payload['old_value'],
                (string) $payload['new_value'],
                $payload['user_id'],
                $payload['action_at'],
                microtime(true),
                random_int(1000, 999999),
            ]));

            (new ClinicalAuditTrailModel())->insert($payload);
        } catch (\Throwable $e) {
            // fail-open: audit should not break workflow
        }
    }
}

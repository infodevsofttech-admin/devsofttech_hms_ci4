<?php

namespace App\Controllers;

use App\Libraries\Abdm\M3HiuWorkflowService;
use App\Libraries\Abdm\M3HiuDocumentRepository;

class AbdmHiu extends BaseController
{
    private M3HiuWorkflowService $service;
    private M3HiuDocumentRepository $documentRepository;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->service = new M3HiuWorkflowService();
        $this->documentRepository = new M3HiuDocumentRepository();
    }

    public function index()
    {
        return view('abdm/patient_request_list');
    }

    public function patientRequestListData()
    {
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $status = trim((string) ($this->request->getGet('status') ?? ''));
        $limit = (int) ($this->request->getGet('limit') ?? 300);
        if ($limit <= 0 || $limit > 1000) {
            $limit = 300;
        }

        $service = new \App\Libraries\Abdm\ConsentSessionListService();
        $result = $service->getGlobalConsentRequestsList(['q' => $q, 'status' => $status], $limit);

        return $this->response->setJSON($result);
    }

    public function documents()
    {
        return view('abdm/hiu_documents');
    }

    public function documentsList()
    {
        $filters = [
            'patient_id' => (int) ($this->request->getGet('patient_id') ?? 0),
            'abha_address' => trim((string) ($this->request->getGet('abha_address') ?? '')),
            'q' => trim((string) ($this->request->getGet('q') ?? '')),
        ];
        $limit = (int) ($this->request->getGet('limit') ?? 100);
        if ($limit <= 0 || $limit > 300) {
            $limit = 100;
        }

        $rows = $this->documentRepository->listDocuments($filters, $limit);
        return $this->response->setJSON([
            'ok' => 1,
            'count' => count($rows),
            'items' => $rows,
        ]);
    }

    public function documentDetail($id = null)
    {
        $docId = (int) ($id ?? 0);
        $row = $this->documentRepository->getDocument($docId);
        if (empty($row)) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => 0,
                'error' => 'Document not found',
            ]);
        }

        return $this->response->setJSON([
            'ok' => 1,
            'item' => $row,
        ]);
    }

    public function consentRequest()
    {
        return $this->handleOperation('consent_request', 'REQUESTED');
    }

    public function consentRequestStatus()
    {
        return $this->handleOperation('consent_status', 'STATUS_CHECKED');
    }

    public function consentReconcile()
    {
        return $this->handleOperation('consent_reconcile', 'STATUS_CHECKED');
    }

    public function dataFetch()
    {
        return $this->handleOperation('data_fetch', 'DATA_PENDING');
    }

    public function consentUpdateWebhook()
    {
        $signatureFailure = $this->validateGatewayWebhookSignature();
        if ($signatureFailure !== null) {
            return $signatureFailure;
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload) || empty($payload)) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'Invalid consent callback payload',
            ]);
        }

        $result = $this->service->ingestConsentUpdateWebhook($payload);
        $httpCode = (int) ($result['ok'] ?? 0) === 1 ? 202 : 400;

        return $this->response->setStatusCode($httpCode)->setJSON($result);
    }

    public function healthInformationOnRequestWebhook()
    {
        $signatureFailure = $this->validateGatewayWebhookSignature();
        if ($signatureFailure !== null) {
            return $signatureFailure;
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload) || empty($payload)) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'Invalid health-information callback payload',
            ]);
        }

        $result = $this->service->ingestHealthInformationCallback($payload, 'hi_on_request_callback');

        return $this->response->setStatusCode(202)->setJSON([
            'ok' => 1,
            'status' => 'accepted',
            'result' => $result,
        ]);
    }

    public function healthInformationDataPushWebhook()
    {
        $signatureFailure = $this->validateGatewayWebhookSignature();
        if ($signatureFailure !== null) {
            return $signatureFailure;
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload) || empty($payload)) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'Invalid data push payload',
            ]);
        }

        $result = $this->service->ingestHealthInformationCallback($payload, 'hi_data_push_callback');

        return $this->response->setStatusCode(202)->setJSON([
            'ok' => 1,
            'status' => 'accepted',
            'result' => $result,
        ]);
    }

    public function timeline()
    {
        $filters = [
            'hfr_id' => trim((string) ($this->request->getGet('hfr_id') ?? '')),
            'consent_id' => trim((string) ($this->request->getGet('consent_id') ?? '')),
            'transaction_id' => trim((string) ($this->request->getGet('transaction_id') ?? '')),
            'abha_address' => trim((string) ($this->request->getGet('abha_address') ?? '')),
            'date_from' => trim((string) ($this->request->getGet('date_from') ?? '')),
            'date_to' => trim((string) ($this->request->getGet('date_to') ?? '')),
        ];

        $rows = $this->service->listTimeline($filters, 300);
        return $this->response->setJSON([
            'ok' => 1,
            'count' => count($rows),
            'items' => $rows,
        ]);
    }

    public function pollSummary()
    {
        $lookback = (int) ($this->request->getGet('lookback') ?? 180);
        if ($lookback <= 0) {
            $lookback = 180;
        }

        $summary = $this->service->natPollSummary($lookback);
        return $this->response->setJSON($summary);
    }

    public function patientLookup()
    {
        if (! $this->db->tableExists('patient_master')) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error' => 'patient_master table not found',
            ]);
        }

        $q = trim((string) ($this->request->getGet('q') ?? ''));
        if ($q !== '' && mb_strlen($q) < 2 && ! ctype_digit($q)) {
            return $this->response->setJSON([
                'ok' => 1,
                'count' => 0,
                'items' => [],
                'note' => 'Type at least 2 characters to search.',
            ]);
        }

        $fields = $this->db->getFieldNames('patient_master') ?? [];
        $idCol = $this->resolveExistingColumn($fields, ['id']);
        $uhidCol = $this->resolveExistingColumn($fields, ['p_code', 'uhid', 'uhid_no', 'patient_code', 'patient_id']);
        $nameCol = $this->resolveExistingColumn($fields, ['p_fname', 'patient_name', 'name']);
        $mobileCol = $this->resolveExistingColumn($fields, ['p_mobile', 'mobile', 'phone', 'contact_no']);
        $abhaNumberCol = $this->resolveExistingColumn($fields, ['abha_id', 'abha_no', 'abha_number', 'abha']);
        $abhaAddressCol = $this->resolveExistingColumn($fields, ['abha_address', 'abha_addr']);

        if ($idCol === null) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error' => 'patient_master.id column not found',
            ]);
        }

        $builder = $this->db->table('patient_master p')
            ->select('p.' . $idCol . ' AS patient_id', false)
            ->orderBy('p.' . $idCol, 'DESC')
            ->limit(30);

        if ($uhidCol !== null) {
            $builder->select('p.' . $uhidCol . ' AS patient_code', false);
        } else {
            $builder->select("'' AS patient_code", false);
        }
        if ($nameCol !== null) {
            $builder->select('p.' . $nameCol . ' AS patient_name', false);
        } else {
            $builder->select("'' AS patient_name", false);
        }
        if ($mobileCol !== null) {
            $builder->select('p.' . $mobileCol . ' AS mobile', false);
        } else {
            $builder->select("'' AS mobile", false);
        }
        if ($abhaNumberCol !== null) {
            $builder->select('p.' . $abhaNumberCol . ' AS abha_number', false);
        } else {
            $builder->select("'' AS abha_number", false);
        }
        if ($abhaAddressCol !== null) {
            $builder->select('p.' . $abhaAddressCol . ' AS abha_address', false);
        } else {
            $builder->select("'' AS abha_address", false);
        }
        if (in_array('log', $fields, true)) {
            $builder->select('p.log AS patient_log', false);
        } else {
            $builder->select("'' AS patient_log", false);
        }

        if ($q !== '') {
            $builder->groupStart();
            if ($nameCol !== null) {
                $builder->like('p.' . $nameCol, $q);
            }
            if ($uhidCol !== null) {
                $builder->orLike('p.' . $uhidCol, $q);
            }
            if ($mobileCol !== null) {
                $builder->orLike('p.' . $mobileCol, $q);
            }
            if ($abhaNumberCol !== null) {
                $builder->orLike('p.' . $abhaNumberCol, $q);
            }
            if ($abhaAddressCol !== null) {
                $builder->orLike('p.' . $abhaAddressCol, $q);
            }
            if (ctype_digit($q)) {
                $builder->orWhere('p.' . $idCol, (int) $q);
            }
            $builder->groupEnd();
        }

        $rows = $builder->get()->getResultArray();
        $items = [];
        foreach ($rows as $row) {
            $abhaNumber = trim((string) ($row['abha_number'] ?? ''));
            $abhaAddress = trim((string) ($row['abha_address'] ?? ''));
            if ($abhaAddress === '') {
                $abhaAddress = $this->extractAbhaAddressFromLog((string) ($row['patient_log'] ?? ''));
            }
            $abhaAddress = $this->canonicalizeAbhaAddress($abhaAddress, $abhaNumber);
            $latest = $this->findLatestConsentByAbhaAddress($abhaAddress);

            $items[] = [
                'patient_id' => (int) ($row['patient_id'] ?? 0),
                'patient_code' => trim((string) ($row['patient_code'] ?? '')),
                'patient_name' => trim((string) ($row['patient_name'] ?? '')),
                'mobile' => trim((string) ($row['mobile'] ?? '')),
                'abha_number' => $abhaNumber,
                'abha_address' => $abhaAddress,
                'has_abha_number' => preg_match('/^\d{14}$/', $abhaNumber) === 1 ? 1 : 0,
                'has_abha_address' => $this->isValidAbhaAddress($abhaAddress) ? 1 : 0,
                'latest_consent' => $latest,
            ];
        }

        return $this->response->setJSON([
            'ok' => 1,
            'count' => count($items),
            'items' => $items,
        ]);
    }

    private function handleOperation(string $operation, string $successState)
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = $this->request->getPost() ?? [];
        }

        $result = $this->service->runOperation($operation, $payload);
        $ok = (int) ($result['ok'] ?? 0) === 1;
        $httpCode = (int) ($result['http_code'] ?? ($ok ? 200 : 400));
        if ($httpCode < 100 || $httpCode > 599) {
            $httpCode = $ok ? 200 : 400;
        }

        $requestId = (string) ($result['request_id'] ?? $result['requestId'] ?? $payload['request_id'] ?? $payload['requestId'] ?? '');
        if ($ok) {
            return $this->response->setStatusCode($httpCode)->setJSON([
                'ok' => 1,
                'request_id' => $requestId,
                'http_code' => $httpCode,
                'data' => $result,
                'workflow_state' => $successState,
                'duplicate' => (int) ($result['duplicate'] ?? 0),
            ]);
        }

        return $this->response->setStatusCode($httpCode)->setJSON([
            'ok' => 0,
            'error' => (string) ($result['error_text'] ?? $result['message'] ?? 'HIU operation failed'),
            'request_id' => $requestId,
            'http_code' => $httpCode,
            'retryable' => (int) ($result['retryable'] ?? 0),
            'workflow_state' => 'FAILED',
            'data' => $result,
        ]);
    }

    private function findLatestConsentByAbhaAddress(string $abhaAddress): ?array
    {
        if ($abhaAddress === '' || ! $this->db->tableExists('abdm_hiu_workflows')) {
            return null;
        }

        $fields = $this->db->getFieldNames('abdm_hiu_workflows') ?? [];
        $select = [
            'id',
            'operation',
            'workflow_state',
            'status',
            'consent_id',
            'request_id',
            'transaction_id',
            'created_at',
            'updated_at',
        ];
        if (in_array('abdm_consent_request_id', $fields, true)) {
            $select[] = 'abdm_consent_request_id';
        }
        if (in_array('abdm_consent_artifact_id', $fields, true)) {
            $select[] = 'abdm_consent_artifact_id';
        }

        $row = $this->db->table('abdm_hiu_workflows')
            ->select(implode(', ', $select))
            ->where('abha_address', $abhaAddress)
            ->whereIn('operation', ['consent_request', 'consent_status', 'consent_fetch'])
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        return ! empty($row) ? $row : null;
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

    private function extractAbhaAddressFromLog(string $logText): string
    {
        if ($logText === '') {
            return '';
        }

        if (preg_match('/abha_address\s*:\s*([A-Za-z0-9._-]+@[A-Za-z0-9.-]+)/i', $logText, $m) === 1) {
            return trim((string) ($m[1] ?? ''));
        }

        return '';
    }

    private function canonicalizeAbhaAddress(string $abhaAddress, string $abhaNumber): string
    {
        $candidate = trim($abhaAddress);
        if ($candidate !== '' && $this->isValidAbhaAddress($candidate)) {
            return $candidate;
        }

        $addressDigits = preg_replace('/\D+/', '', $candidate) ?? '';
        if (preg_match('/^\d{14}$/', $addressDigits) !== 1) {
            $addressDigits = preg_replace('/\D+/', '', trim($abhaNumber)) ?? '';
        }

        if (preg_match('/^\d{14}$/', $addressDigits) === 1) {
            $domain = trim((string) (getenv('ABDM_ABHA_ADDRESS_DEFAULT_DOMAIN') ?: 'sbx'));
            return $addressDigits . '@' . $domain;
        }

        return '';
    }

    private function isValidAbhaAddress(string $value): bool
    {
        return preg_match('/^[A-Za-z0-9._-]+@[A-Za-z0-9.-]+$/', trim($value)) === 1;
    }

    private function validateGatewayWebhookSignature(): ?\CodeIgniter\HTTP\ResponseInterface
    {
        $secret = trim((string) (
            $this->readRuntimeSetting('GATEWAY_TO_HMS_HMAC_SECRET')
            ?: $this->readRuntimeSetting('EKA_WEBHOOK_SECRET')
            ?: $this->readRuntimeSetting('HMS_WEBHOOK_SECRET')
        ));

        // If no secret is configured, accept payload to keep callback flow functional.
        if ($secret === '') {
            return null;
        }

        $rawBody = (string) $this->request->getBody();
        $provided = trim((string) (
            $this->request->getHeaderLine('X-Signature')
            ?: $this->request->getHeaderLine('X-Hub-Signature-256')
        ));

        if ($provided === '') {
            return $this->response->setStatusCode(401)->setJSON([
                'ok' => 0,
                'error' => 'Missing webhook signature',
            ]);
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);
        $normalizedProvided = preg_replace('/^sha256=/i', '', $provided) ?? '';

        if (! hash_equals($expected, $normalizedProvided)) {
            return $this->response->setStatusCode(401)->setJSON([
                'ok' => 0,
                'error' => 'Invalid webhook signature',
            ]);
        }

        return null;
    }

    private function readRuntimeSetting(string $name): string
    {
        if (! $this->db->tableExists('hospital_setting')) {
            return '';
        }

        $row = $this->db->table('hospital_setting')
            ->select('s_value')
            ->where('s_name', $name)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        return trim((string) ($row['s_value'] ?? ''));
    }
}

<?php

namespace App\Controllers;

use App\Models\PaymentModel;
use RuntimeException;
use Throwable;

class Payment extends BaseController
{
    private PaymentModel $paymentModel;

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
    }

    public function index(): string
    {
        return view('payment/payment_search');
    }

    public function paymentRecord()
    {
        $paymentId = (int) preg_replace('/\D+/', '', (string) $this->request->getPost('txtsearch'));
        if ($paymentId <= 0) {
            return $this->searchResponse('<div class="alert alert-warning">Enter a valid Payment ID.</div>', 422);
        }

        $payment = $this->paymentModel->findPayment($paymentId);
        if ($payment === null) {
            return $this->searchResponse('<div class="alert alert-warning">Payment record not found.</div>', 404);
        }

        return $this->searchResponse($this->renderPayment($payment));
    }

    public function changeToBank()
    {
        $paymentId = (int) $this->request->getPost('pay_id');
        $sourceId = (int) $this->request->getPost('cbo_pay_type');
        $transactionId = trim((string) $this->request->getPost('input_card_tran'));
        $source = $this->findBankSource($sourceId);

        if ($source === null || $transactionId === '') {
            return $this->error('Bank/online source and transaction reference are required.', 422);
        }

        $label = (string) $source['label'];
        return $this->applyCorrection($paymentId, [
            'payment_mode' => 2,
            'pay_bank_id' => $sourceId,
            'insert_code' => (string) $sourceId,
            'card_bank' => $label,
            'card_tran_id' => $transactionId,
            'update_remark' => 'Changed to Bank/Online',
        ], 2, 'Payment mode changed to BANK [' . $label . '], reference ' . $transactionId);
    }

    public function changeToCash()
    {
        $paymentId = (int) $this->request->getPost('pay_id');

        return $this->applyCorrection($paymentId, [
            'payment_mode' => 1,
            'pay_bank_id' => 0,
            'card_bank' => '',
            'card_remark' => '',
            'cust_card' => '',
            'card_tran_id' => '',
            'bankcard_machine' => '',
            'insert_code' => '',
            'update_remark' => 'Changed to Cash',
        ], 1, 'Payment mode changed to CASH');
    }

    public function changeUser()
    {
        $paymentId = (int) $this->request->getPost('pay_id');
        $userId = (int) $this->request->getPost('user_list');
        $user = $this->db->table('users')->select('id,username')->where('id', $userId)->where('active', 1)->get(1)->getRowArray();
        if ($user === null) {
            return $this->error('Select a valid active user.', 422);
        }

        $payment = $this->paymentModel->findPayment($paymentId);
        $oldUser = trim((string) ($payment['update_by'] ?? '-'));
        $newUser = (string) $user['username'] . '[' . $userId . ']';

        return $this->applyCorrection($paymentId, [
            'update_by_id' => $userId,
            'update_by' => $newUser,
            'update_remark' => 'Payment user corrected',
        ], 3, 'Payment user changed from ' . $oldUser . ' to ' . $newUser);
    }

    public function changeAmount()
    {
        $paymentId = (int) $this->request->getPost('pay_id');
        $rawAmount = trim((string) $this->request->getPost('change_value'));
        if (! is_numeric($rawAmount) || (float) $rawAmount < 0) {
            return $this->error('Enter a valid amount of zero or greater.', 422);
        }

        $payment = $this->paymentModel->findPayment($paymentId);
        $oldAmount = number_format((float) ($payment['amount'] ?? 0), 2, '.', '');
        $newAmount = number_format((float) $rawAmount, 2, '.', '');

        return $this->applyCorrection($paymentId, [
            'amount' => $newAmount,
            'update_remark' => 'Payment amount corrected',
        ], 4, 'Payment amount changed from ' . $oldAmount . ' to ' . $newAmount);
    }

    private function applyCorrection(int $paymentId, array $changes, int $updateType, string $remark)
    {
        if ($paymentId <= 0) {
            return $this->error('Payment record not found.', 404);
        }

        $reason = trim((string) $this->request->getPost('correction_reason'));
        if ($reason === '') {
            return $this->error('Correction reason is required.', 422);
        }

        try {
            $payment = $this->paymentModel->correctPayment($paymentId, $changes, $updateType, $this->actorName(), $remark . '. Reason: ' . $reason);

            return $this->response->setJSON([
                'status' => 1,
                'message' => 'Payment updated successfully.',
                'html' => $this->renderPayment($payment),
                'csrf_hash' => csrf_hash(),
            ]);
        } catch (RuntimeException $e) {
            $status = str_contains(strtolower($e->getMessage()), 'not found') ? 404 : 409;
            return $this->error($e->getMessage(), $status);
        } catch (Throwable $e) {
            log_message('error', 'Payment correction failed: {message}', ['message' => $e->getMessage()]);
            return $this->error('Unable to update the payment record.', 500);
        }
    }

    private function renderPayment(array $payment): string
    {
        $context = $this->paymentContext($payment);

        return view('payment/payment_edit', [
            'payment' => $payment,
            'context' => $context,
            'users' => $this->db->table('users')->select('id,username')->where('active', 1)->orderBy('username')->get()->getResultArray(),
            'bank_sources' => $this->bankSources(),
        ]);
    }

    private function paymentContext(array $payment): array
    {
        $type = (int) ($payment['payof_type'] ?? 0);
        $referenceId = (int) ($payment['payof_id'] ?? 0);
        $context = [
            'type' => [1 => 'OPD', 2 => 'OPD Charge', 3 => 'Organization Charge', 4 => 'IPD Payment'][$type] ?? 'Payment',
            'code' => (string) ($payment['payof_code'] ?? ''),
            'patient' => '',
        ];

        $tableMap = [
            1 => ['opd_master', 'opd_id', ['opd_code'], ['p_id'], ['p_name', 'P_name']],
            2 => ['invoice_master', 'id', ['invoice_code'], ['attach_id'], ['inv_name']],
            3 => ['organization_case_master', 'id', ['case_id_code'], ['p_id'], ['p_name', 'insurance_company_name']],
            4 => ['ipd_master', 'id', ['ipd_code'], ['p_id'], ['P_name', 'p_name']],
        ];

        if (! isset($tableMap[$type])) {
            return $context;
        }

        [$table, $key, $codeColumns, $patientColumns, $nameColumns] = $tableMap[$type];
        if (! $this->db->tableExists($table)) {
            return $context;
        }

        $row = $this->db->table($table)->where($key, $referenceId)->get(1)->getRowArray();
        if ($row === null) {
            return $context;
        }

        $context['code'] = $this->firstValue($row, $codeColumns, $context['code']);
        $context['patient'] = $this->firstValue($row, $nameColumns);
        $patientId = (int) $this->firstValue($row, $patientColumns, '0');
        if ($context['patient'] === '' && $patientId > 0) {
            foreach (['patient_master', 'patient_master_exten'] as $patientTable) {
                if (! $this->db->tableExists($patientTable)) {
                    continue;
                }
                $patient = $this->db->table($patientTable)->where('id', $patientId)->get(1)->getRowArray();
                if ($patient !== null) {
                    $context['patient'] = trim((string) ($patient['p_fname'] ?? '') . ' ' . (string) ($patient['p_lname'] ?? ''));
                    break;
                }
            }
        }

        return $context;
    }

    private function bankSources(): array
    {
        if (! $this->db->tableExists('hospital_bank_payment_source') || ! $this->db->tableExists('hospital_bank')) {
            return [];
        }

        return $this->db->table('hospital_bank_payment_source s')
            ->select("s.id, CONCAT(COALESCE(s.pay_type, ''), ' [', COALESCE(b.bank_name, ''), ']') AS label", false)
            ->join('hospital_bank b', 'b.id = s.bank_id', 'left')
            ->orderBy('s.pay_type')
            ->get()
            ->getResultArray();
    }

    private function findBankSource(int $sourceId): ?array
    {
        foreach ($this->bankSources() as $source) {
            if ((int) $source['id'] === $sourceId) {
                return $source;
            }
        }

        return null;
    }

    private function firstValue(array $row, array $columns, string $default = ''): string
    {
        foreach ($columns as $column) {
            if (isset($row[$column]) && trim((string) $row[$column]) !== '') {
                return trim((string) $row[$column]);
            }
        }

        return $default;
    }

    private function actorName(): string
    {
        $user = auth()->user();
        return $user ? ((string) ($user->username ?? 'user') . '[' . (int) $user->id . ']') : 'system';
    }

    private function searchResponse(string $body, int $status = 200)
    {
        return $this->response
            ->setStatusCode($status)
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setBody($body);
    }

    private function error(string $message, int $status)
    {
        return $this->response->setStatusCode($status)->setJSON([
            'status' => 0,
            'message' => $message,
            'csrf_hash' => csrf_hash(),
        ]);
    }
}
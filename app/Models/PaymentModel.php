<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;

class PaymentModel
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    public function insertPayment(array $data): int
    {
        $this->db->table('payment_history')->insert($data);

        return (int) $this->db->insertID();
    }

    public function updatePayment(array $data, int $id): bool
    {
        return (bool) $this->db->table('payment_history')->where('id', $id)->update($data);
    }

    public function findPayment(int $id): ?array
    {
        return $this->db->table('payment_history')->where('id', $id)->get(1)->getRowArray();
    }

    public function assertEditable(array $payment): void
    {
        $cashStatus = strtolower(trim((string) ($payment['cash_submission_status'] ?? 'open')));
        if ($cashStatus !== '' && $cashStatus !== 'open') {
            throw new RuntimeException('This payment is already included in a cash submission and cannot be edited.');
        }
        if ((int) ($payment['cash_submission_scroll_id'] ?? 0) > 0) {
            throw new RuntimeException('This payment is linked to a cash submission and cannot be edited.');
        }

        $bankStatus = strtolower(trim((string) ($payment['bank_reconcile_status'] ?? '')));
        if ($bankStatus !== '' || (int) ($payment['bank_statement_entry_id'] ?? 0) > 0 || (int) ($payment['bank_settlement_entry_id'] ?? 0) > 0) {
            throw new RuntimeException('This payment is already reconciled with the bank and cannot be edited.');
        }
    }

    public function correctPayment(int $id, array $changes, int $updateType, string $actor, string $remark): array
    {
        $payment = $this->findPayment($id);
        if ($payment === null) {
            throw new RuntimeException('Payment record not found.');
        }

        $this->assertEditable($payment);
        $fields = $this->db->getFieldNames('payment_history') ?? [];
        $changes = array_intersect_key($changes, array_flip($fields));
        if ($changes === []) {
            throw new RuntimeException('No supported payment fields were provided.');
        }
        if (! $this->db->tableExists('payment_history_log')) {
            throw new RuntimeException('Payment audit log table is unavailable.');
        }

        $this->db->transStart();
        $this->db->table('payment_history')->where('id', $id)->update($changes);
        $this->db->table('payment_history_log')->insert([
            'pay_id' => $id,
            'update_type' => $updateType,
            'update_by' => mb_substr($actor, 0, 50),
            'update_remark' => mb_substr($remark, 0, 200),
        ]);
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new RuntimeException('Unable to update the payment record.');
        }

        return $this->findPayment($id) ?? $payment;
    }
}

<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;

class InvoiceModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function createInvoice(array $data): int
    {
        if (! $this->db->table('invoice_master')->insert($data)) {
            return 0;
        }

        $insertId = (int) $this->db->insertID();
        $pid = str_pad(substr((string) $insertId, -7, 7), 7, '0', STR_PAD_LEFT);
        $invoiceCode = 'N' . date('ym') . $pid;

        $this->db->table('invoice_master')
            ->where('id', $insertId)
            ->update(['invoice_code' => $invoiceCode]);

        return $insertId;
    }

    public function addInvoiceItem(array $data): int
    {
        if (! $this->db->table('invoice_item')->insert($data)) {
            return 0;
        }

        $insertId = (int) $this->db->insertID();
        $invoiceId = (int) ($data['inv_master_id'] ?? 0);
        if ($invoiceId > 0) {
            $this->recalculateInvoice($invoiceId);
        }

        return $insertId;
    }

    public function deleteInvoiceItem(int $id): bool
    {
        $row = $this->db->table('invoice_item')->where('id', $id)->get()->getRowArray();
        if (empty($row)) {
            return false;
        }

        $user = auth()->user();
        $userId = $user->id ?? 0;
        $userName = $user->username ?? $user->email ?? 'User';

        $row['update_by_id'] = $userId;
        $row['update_by'] = $userName;
        $row['update_action'] = '1';

        if ($this->db->tableExists('invoice_item_update')) {
            $auditTableFields = $this->db->getFieldNames('invoice_item_update');
            if (! empty($auditTableFields)) {
                $row = array_intersect_key($row, array_flip($auditTableFields));
            }

            if (! $this->db->table('invoice_item_update')->insert($row)) {
                return false;
            }
        }

        $this->db->table('invoice_item')->where('id', $id)->delete();
        if ($this->db->affectedRows() < 1) {
            return false;
        }

        $this->recalculateInvoice((int) $row['inv_master_id']);

        return true;
    }

    public function updateInvoice(array $data, int $id): bool
    {
        $updated = (bool) $this->db->table('invoice_master')
            ->where('id', $id)
            ->update($data);

        $this->recalculateInvoice($id);

        return $updated;
    }

    public function updateItem(array $data, int $id): bool
    {
        $updated = (bool) $this->db->table('invoice_item')
            ->where('id', $id)
            ->update($data);

        $row = $this->db->table('invoice_item')->where('id', $id)->get()->getRowArray();
        if (! empty($row)) {
            $this->recalculateInvoice((int) $row['inv_master_id']);
        }

        return $updated;
    }

    public function updateInvoiceFinal(int $id): void
    {
        $this->recalculateInvoice($id);
    }

    private function recalculateInvoice(int $invoiceId): void
    {
        if ($invoiceId <= 0) {
            return;
        }

        $invoice = $this->db->table('invoice_master')->where('id', $invoiceId)->get()->getRowArray();
        if (empty($invoice)) {
            return;
        }

        $discountAmount = (float) ($invoice['discount_amount'] ?? 0);
        $correctionAmount = (float) ($invoice['correction_amount'] ?? 0);
        $insuranceCaseId = (int) ($invoice['insurance_case_id'] ?? 0);

        $this->db->table('invoice_item')
            ->set('item_amount', 'item_rate*item_qty', false)
            ->where('inv_master_id', $invoiceId)
            ->update();

        $gtotalRow = $this->db->table('invoice_item')
            ->selectSum('item_amount', 'gtotal')
            ->where('inv_master_id', $invoiceId)
            ->get()
            ->getRowArray();
        $grossTotal = (float) ($gtotalRow['gtotal'] ?? 0);

        $paidRow = $this->db->table('payment_history')
            ->select('sum(if(credit_debit>0,amount*-1,amount)) as paid_amount', false)
            ->where('payof_type', 2)
            ->where('payof_id', $invoiceId)
            ->get()
            ->getRowArray();
        $paidAmount = (float) ($paidRow['paid_amount'] ?? 0);

        $correctionCrdr = (int) ($invoice['correction_crdr'] ?? 1);
        $netAmount = $grossTotal - $discountAmount;
        if ($correctionAmount > 0) {
            if ($correctionCrdr === 0) {
                $netAmount += $correctionAmount;  // Add-type correction increases net
            } else {
                $netAmount -= $correctionAmount;  // Return-type correction reduces net
            }
        }
        $balanceAmount = $netAmount - $paidAmount;
        $paymentPart = ($balanceAmount > 0 && $paidAmount > 0) ? 1 : 0;
        $paymentMode = (int) ($invoice['payment_mode'] ?? 0);
        $paymentStatus = ($paymentMode === 3 || $paymentMode === 4) ? 1 : ($balanceAmount <= 0 ? 1 : 0);

        $this->db->table('invoice_master')
            ->where('id', $invoiceId)
            ->update([
                'total_amount' => $grossTotal,
                'net_amount' => $netAmount,
                'payment_part_received' => $paidAmount,
                'payment_part_balance' => $balanceAmount,
                'payment_part' => $paymentPart,
                'payment_status' => $paymentStatus,
            ]);

        if ($insuranceCaseId > 0) {
            $this->db->table('organization_case_master')
                ->where('id', $insuranceCaseId)
                ->update(['inv_opd_charge_amt' => $netAmount]);
        }
    }
}

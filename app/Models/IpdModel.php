<?php

namespace App\Models;

use CodeIgniter\Model;

class IpdModel extends Model
{
    protected $table = 'ipd_master';

    public function insertIpd(array $data): int
    {
        $master = $data['master'] ?? [];
        if (empty($master['p_id'])) {
            return 0;
        }

        $existing = $this->db->table('ipd_master')
            ->where('ipd_status', 0)
            ->where('p_id', $master['p_id'])
            ->get()
            ->getResult();

        if (! empty($existing)) {
            return (int) $existing[0]->id;
        }

        $userSignature = $this->getUserSignature();

        $inserted = $this->db->table('ipd_master')->insert($master);
        if (! $inserted) {
            return 0;
        }

        $insertId = (int) $this->db->insertID();

        $countRow = $this->db->table('ipd_master')
            ->select('count(*) as xtimes')
            ->where('id <=', $insertId)
            ->where('p_id', $master['p_id'])
            ->get()
            ->getRow();

        $times = (int) ($countRow->xtimes ?? 0);

        $pid = str_pad(substr((string) $insertId, -7, 7), 7, '0', STR_PAD_LEFT);
        $pid = 'A' . date('ym') . $pid;

        $this->db->table('ipd_master')
            ->where('id', $insertId)
            ->update([
                'ipd_code' => $pid,
                'ipd_times' => $times,
            ]);

        $docList = $data['doc_list'] ?? [];
        if (! empty($docList)) {
            foreach ($docList as $docId) {
                $this->db->table('ipd_master_doc_list')->insert([
                    'doc_id' => $docId,
                    'ipd_id' => $insertId,
                    'log' => 'Insert By :' . $userSignature,
                ]);
            }
        }

        return $insertId;
    }

    public function addIpdDoc(array $docData): int
    {
        $inserted = $this->db->table('ipd_master_doc_list')->insert($docData);

        return $inserted ? (int) $this->db->insertID() : 0;
    }

    public function removeIpdDoc(int $ipdDocId): int
    {
        $row = $this->db->table('ipd_master_doc_list')
            ->where('id', $ipdDocId)
            ->get()
            ->getRowArray();

        if (empty($row)) {
            return 0;
        }

        $user = $this->getUserIdentity();
        $row['update_by_id'] = $user['id'];
        $row['update_by'] = $user['name'] . '[' . date('Y-m-d H:i:s') . ']';

        $inserted = $this->db->table('ipd_master_doc_list_delete')->insert($row);
        if (! $inserted) {
            return 0;
        }

        $this->db->table('ipd_master_doc_list')
            ->where('id', $ipdDocId)
            ->delete();

        return 1;
    }

    public function bedAssign(array $data): int
    {
        $inserted = $this->db->table('ipd_bed_assign')->insert($data);
        if (! $inserted) {
            return 0;
        }

        $insertId = (int) $this->db->insertID();

        $this->db->table('hc_bed_master')
            ->where('bed_used_p_id', $data['ipd_id'])
            ->update(['bed_used_p_id' => 0]);

        $this->db->table('hc_bed_master')
            ->where('id', $data['bed_id'])
            ->update(['bed_used_p_id' => $data['ipd_id']]);

        return $insertId;
    }

    public function calculateIPD(int $ipdNo): void
    {
        $packageRow = $this->db->table('ipd_package')
            ->select('sum(package_Amount) as total_pakage_amt, count(*) as no_package')
            ->where('ipd_id', $ipdNo)
            ->get()
            ->getRow();
        $totalPackageAmt = (float) ($packageRow->total_pakage_amt ?? 0);
        $noPackage = (int) ($packageRow->no_package ?? 0);

        $itemsBuilder = $this->db->table('ipd_invoice_item')
            ->select('sum(item_amount) as total_items')
            ->where('ipd_id', $ipdNo);
        if ($noPackage > 0) {
            $itemsBuilder->where('package_id', 0);
        }
        $itemsRow = $itemsBuilder->get()->getRow();
        $ipdItemsTotal = (float) ($itemsRow->total_items ?? 0);

        $chargeRow = $this->db->table('invoice_master')
            ->select('sum(total_amount) as total_opd_charge')
            ->where('payment_status', 1)
            ->where('ipd_include', 1)
            ->where('invoice_status', 1)
            ->where('ipd_id', $ipdNo)
            ->get()
            ->getRow();
        $totalChargeAmount = (float) ($chargeRow->total_opd_charge ?? 0);

        $medicalRow = $this->db->table('invoice_med_master inv')
            ->select(
                'sum(if(inv.ipd_credit = 1 and inv.ipd_credit_type = 1, inv.net_amount, 0)) as tot_medical_amt,'
                . 'sum(if(inv.ipd_credit = 1 and inv.ipd_credit_type = 0, inv.net_amount, 0)) as tot_medical_package_bill,'
                . 'sum(if(inv.ipd_credit = 0, inv.net_amount, 0)) as cash_med_amount',
                false
            )
            ->where('inv.ipd_id', $ipdNo)
            ->get()
            ->getRow();
        $totalMedicalAmt = (float) ($medicalRow->tot_medical_amt ?? 0);
        $totalMedicalPackageAmt = (float) ($medicalRow->tot_medical_package_bill ?? 0);
        $cashMedicalAmt = (float) ($medicalRow->cash_med_amount ?? 0);

        $ipdRow = $this->db->table('ipd_master')
            ->select('Discount,Discount2,Discount3,chargeamount1,chargeamount2')
            ->where('id', $ipdNo)
            ->get()
            ->getRow();
        $discount1 = (float) ($ipdRow->Discount ?? 0);
        $discount2 = (float) ($ipdRow->Discount2 ?? 0);
        $discount3 = (float) ($ipdRow->Discount3 ?? 0);
        $charge1 = (float) ($ipdRow->chargeamount1 ?? 0);
        $charge2 = (float) ($ipdRow->chargeamount2 ?? 0);

        $totalCharges = $totalChargeAmount + $totalPackageAmt + $ipdItemsTotal;
        $grossAmount = $totalCharges + round($totalMedicalAmt, 0);
        $totalDiscount = $discount1 + $discount2 + $discount3;
        $totalExtraCharge = $charge1 + $charge2;
        $netAmount = ($grossAmount + $totalExtraCharge) - $totalDiscount;

        $payRow = $this->db->table('payment_history')
            ->select('sum(if(credit_debit = 0, amount, amount * -1)) as total_paid', false)
            ->where('payof_type', 4)
            ->where('payof_id', $ipdNo)
            ->get()
            ->getRow();
        $totalPaid = (float) ($payRow->total_paid ?? 0);

        $medPayRow = $this->db->table('payment_history_medical')
            ->select('sum(if(credit_debit = 0, amount, amount * -1)) as total_med_paid', false)
            ->where('Customerof_type', 2)
            ->where('ipd_id', $ipdNo)
            ->get()
            ->getRow();
        $totalMedPaid = (float) ($medPayRow->total_med_paid ?? 0);

        $balance = $netAmount - $totalPaid;

        $this->db->table('ipd_master')
            ->where('id', $ipdNo)
            ->update([
                'charge_amount' => $totalCharges,
                'gross_amount' => $grossAmount,
                'package_charge_amount' => $totalPackageAmt,
                'med_amount' => $totalMedicalAmt,
                'package_med_amount' => $totalMedicalPackageAmt,
                'cash_med_amount' => $cashMedicalAmt,
                'med_paid' => $totalMedPaid,
                'total_paid_amount' => $totalPaid,
                'net_amount' => $netAmount,
                'balance_amount' => $balance,
            ]);

        $balanceRow = $this->db->table('ipd_master')
            ->select('payable_by_tpa,discount_by_hospital,discount_by_hospital_2,discount_for_tpa')
            ->where('id', $ipdNo)
            ->get()
            ->getRow();

        $payableByTpa = (float) ($balanceRow->payable_by_tpa ?? 0);
        $discountByHospital = (float) ($balanceRow->discount_by_hospital ?? 0);
        $discountByHospital2 = (float) ($balanceRow->discount_by_hospital_2 ?? 0);
        $discountForTpa = (float) ($balanceRow->discount_for_tpa ?? 0);

        $balanceAfterDiscount = $balance - $payableByTpa - $discountByHospital - $discountByHospital2 - $discountForTpa;

        $this->db->table('ipd_master')
            ->where('id', $ipdNo)
            ->update([
                'balance_discount_after' => $balanceAfterDiscount,
            ]);
    }

    public function updateIpd(array $data, int $id): void
    {
        $row = $this->db->table('ipd_master')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (! empty($row)) {
            $userSignature = $this->getUserSignature();
            $oldLog = ($row['log'] ?? '') === '' ? ' ' : (string) $row['log'];

            $changeData = compare_arrays($row, $data);
            if (strlen($changeData) > 0) {
                $data['log'] = $oldLog . PHP_EOL . $changeData . 'Update By :' . $userSignature;
            }
        }

        $this->db->table('ipd_master')
            ->where('id', $id)
            ->update($data);
    }

    public function insertIpdItem(array $data): int
    {
        $inserted = $this->db->table('ipd_invoice_item')->insert($data);

        return $inserted ? (int) $this->db->insertID() : 0;
    }

    public function updateIpdItem(array $data, int $id): void
    {
        $row = $this->db->table('ipd_invoice_item')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (! empty($row)) {
            $userSignature = $this->getUserSignature();
            $oldLog = ($row['log'] ?? '') === '' ? ' ' : (string) $row['log'];

            $changeData = compare_arrays($row, $data);
            if (strlen($changeData) > 0) {
                $data['log'] = $oldLog . PHP_EOL . $changeData . 'Update By :' . $userSignature;
            }
        }

        $this->db->table('ipd_invoice_item')
            ->where('id', $id)
            ->update($data);
    }

    public function deleteIpdInvoiceItem(int $id): int
    {
        $row = $this->db->table('ipd_invoice_item')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (empty($row)) {
            return 0;
        }

        $user = $this->getUserIdentity();
        $row['update_by_id'] = $user['id'];
        $row['update_by'] = $user['name'];
        $row['update_action'] = '1';

        $inserted = $this->db->table('ipd_invoice_item_update')->insert($row);
        if (! $inserted) {
            return 0;
        }

        $this->db->table('ipd_invoice_item')
            ->where('id', $id)
            ->delete();

        return 1;
    }

    public function insertPackage(array $data): int
    {
        $inserted = $this->db->table('ipd_package')->insert($data);

        return $inserted ? (int) $this->db->insertID() : 0;
    }

    public function updatePackage(array $data, int $id): void
    {
        $this->db->table('ipd_package')
            ->where('id', $id)
            ->update($data);
    }

    public function deletePackage(int $id): int
    {
        $row = $this->db->table('ipd_package')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (empty($row)) {
            return 0;
        }

        $user = $this->getUserIdentity();
        $row['update_by_id'] = $user['id'];
        $row['update_by_action'] = $user['name'] . ' D:' . date('d-m-Y h:i:s');
        $row['update_action'] = '1';

        $inserted = $this->db->table('ipd_package_update')->insert($row);
        if (! $inserted) {
            return 0;
        }

        $this->db->table('ipd_package')
            ->where('id', $id)
            ->delete();

        return 1;
    }

    public function insertReferIpd(array $data): int
    {
        $inserted = $this->db->table('ipd_refer')->insert($data);

        return $inserted ? (int) $this->db->insertID() : 0;
    }

    public function removeReferIpd(int $id): int
    {
        $row = $this->db->table('ipd_refer')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (empty($row)) {
            return 0;
        }

        $user = $this->getUserIdentity();
        $row['update_by_id'] = $user['id'];
        $row['update_by_action'] = $user['name'] . ' D:' . date('d-m-Y h:i:s');
        $row['update_action'] = '1';

        $inserted = $this->db->table('ipd_refer_update')->insert($row);
        if (! $inserted) {
            return 0;
        }

        $this->db->table('ipd_refer')
            ->where('id', $id)
            ->delete();

        return 1;
    }

    private function getUserIdentity(): array
    {
        $userId = 0;
        $userName = 'system';

        if (function_exists('auth')) {
            $user = auth()->user();
            if ($user) {
                $userId = (int) ($user->id ?? 0);
                $userName = (string) ($user->username ?? $user->email ?? 'user');
            }
        }

        return [
            'id' => $userId,
            'name' => $userName,
        ];
    }

    private function getUserSignature(): string
    {
        $user = $this->getUserIdentity();

        return $user['id'] . '[' . $user['name'] . ']' . date('Y-m-d H:i:s');
    }
}

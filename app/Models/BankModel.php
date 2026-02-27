<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;

class BankModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function getBanks(): array
    {
        return $this->db->table('hospital_bank')
            ->orderBy('bank_name', 'ASC')
            ->get()
            ->getResult();
    }

    public function getBankById(int $id): ?object
    {
        return $this->db->table('hospital_bank')
            ->where('id', $id)
            ->get()
            ->getRow();
    }

    public function insertBank(string $name): int
    {
        $this->db->table('hospital_bank')->insert([
            'bank_name' => $name,
        ]);

        return (int) $this->db->insertID();
    }

    public function updateBank(int $id, string $name): bool
    {
        return (bool) $this->db->table('hospital_bank')
            ->where('id', $id)
            ->update(['bank_name' => $name]);
    }

    public function deleteBank(int $id): bool
    {
        return (bool) $this->db->table('hospital_bank')
            ->where('id', $id)
            ->delete();
    }

    public function getPaymentSources(): array
    {
        return $this->db->table('hospital_bank_payment_source s')
            ->select('s.id, s.bank_id, s.pay_type, b.bank_name')
            ->join('hospital_bank b', 'b.id = s.bank_id')
            ->orderBy('b.bank_name', 'ASC')
            ->orderBy('s.pay_type', 'ASC')
            ->get()
            ->getResult();
    }

    public function getPaymentSourcesByBank(int $bankId): array
    {
        return $this->db->table('hospital_bank_payment_source')
            ->select('id, bank_id, pay_type')
            ->where('bank_id', $bankId)
            ->orderBy('pay_type', 'ASC')
            ->get()
            ->getResult();
    }

    public function insertPaymentSource(int $bankId, string $payType): int
    {
        $this->db->table('hospital_bank_payment_source')->insert([
            'bank_id' => $bankId,
            'pay_type' => $payType,
        ]);

        return (int) $this->db->insertID();
    }

    public function updatePaymentSource(int $id, int $bankId, string $payType): bool
    {
        return (bool) $this->db->table('hospital_bank_payment_source')
            ->where('id', $id)
            ->update([
                'bank_id' => $bankId,
                'pay_type' => $payType,
            ]);
    }

    public function deletePaymentSource(int $id): bool
    {
        return (bool) $this->db->table('hospital_bank_payment_source')
            ->where('id', $id)
            ->delete();
    }

    public function countPaymentSourcesForBank(int $bankId): int
    {
        return (int) $this->db->table('hospital_bank_payment_source')
            ->where('bank_id', $bankId)
            ->countAllResults();
    }
}

<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;

class MedicalBankModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    private function sourceTable(): ?string
    {
        if ($this->db->tableExists('medical_bank_source')) {
            return 'medical_bank_source';
        }

        if ($this->db->tableExists('medical_bank_payment_source')) {
            return 'medical_bank_payment_source';
        }

        return null;
    }

    private function firstColumn(array $fields, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $fields, true)) {
                return $candidate;
            }
        }

        return null;
    }

    private function sourceConfig(): array
    {
        $table = $this->sourceTable();
        if ($table === null) {
            return ['table' => null, 'id' => null, 'bank' => null, 'payType' => null];
        }

        $fields = $this->db->getFieldNames($table) ?? [];

        return [
            'table' => $table,
            'id' => $this->firstColumn($fields, ['id', 'source_id']),
            'bank' => $this->firstColumn($fields, ['bank_id', 'medical_bank_id', 'bankid']),
            'payType' => $this->firstColumn($fields, ['pay_type', 'source_name', 'payment_source', 'name']),
        ];
    }

    public function getBanks(): array
    {
        if (! $this->db->tableExists('medical_bank')) {
            return [];
        }

        return $this->db->table('medical_bank')
            ->orderBy('bank_name', 'ASC')
            ->get()
            ->getResult();
    }

    public function insertBank(string $name): int
    {
        if (! $this->db->tableExists('medical_bank')) {
            return 0;
        }

        $this->db->table('medical_bank')->insert([
            'bank_name' => $name,
        ]);

        return (int) $this->db->insertID();
    }

    public function updateBank(int $id, string $name): bool
    {
        if (! $this->db->tableExists('medical_bank')) {
            return false;
        }

        return (bool) $this->db->table('medical_bank')
            ->where('id', $id)
            ->update(['bank_name' => $name]);
    }

    public function deleteBank(int $id): bool
    {
        if (! $this->db->tableExists('medical_bank')) {
            return false;
        }

        return (bool) $this->db->table('medical_bank')
            ->where('id', $id)
            ->delete();
    }

    public function getPaymentSourcesByBank(int $bankId): array
    {
        $cfg = $this->sourceConfig();
        if ($cfg['table'] === null || $cfg['id'] === null || $cfg['bank'] === null) {
            return [];
        }

        $select = [
            $cfg['id'] . ' as id',
            $cfg['bank'] . ' as bank_id',
        ];

        if ($cfg['payType'] !== null) {
            $select[] = $cfg['payType'] . ' as pay_type';
        } else {
            $select[] = '"" as pay_type';
        }

        $builder = $this->db->table((string) $cfg['table'])
            ->select(implode(', ', $select), false)
            ->where($cfg['bank'], $bankId);

        if ($cfg['payType'] !== null) {
            $builder->orderBy($cfg['payType'], 'ASC');
        } else {
            $builder->orderBy($cfg['id'], 'ASC');
        }

        return $builder->get()->getResult();
    }

    public function insertPaymentSource(int $bankId, string $payType): int
    {
        $cfg = $this->sourceConfig();
        if ($cfg['table'] === null || $cfg['bank'] === null || $cfg['payType'] === null) {
            return 0;
        }

        $payload = [
            $cfg['bank'] => $bankId,
            $cfg['payType'] => $payType,
        ];

        $this->db->table((string) $cfg['table'])->insert($payload);

        return (int) $this->db->insertID();
    }

    public function updatePaymentSource(int $id, int $bankId, string $payType): bool
    {
        $cfg = $this->sourceConfig();
        if ($cfg['table'] === null || $cfg['id'] === null || $cfg['bank'] === null || $cfg['payType'] === null) {
            return false;
        }

        return (bool) $this->db->table((string) $cfg['table'])
            ->where($cfg['id'], $id)
            ->update([
                $cfg['bank'] => $bankId,
                $cfg['payType'] => $payType,
            ]);
    }

    public function deletePaymentSource(int $id): bool
    {
        $cfg = $this->sourceConfig();
        if ($cfg['table'] === null || $cfg['id'] === null) {
            return false;
        }

        return (bool) $this->db->table((string) $cfg['table'])
            ->where($cfg['id'], $id)
            ->delete();
    }

    public function countPaymentSourcesForBank(int $bankId): int
    {
        $cfg = $this->sourceConfig();
        if ($cfg['table'] === null || $cfg['bank'] === null) {
            return 0;
        }

        return (int) $this->db->table((string) $cfg['table'])
            ->where($cfg['bank'], $bankId)
            ->countAllResults();
    }
}

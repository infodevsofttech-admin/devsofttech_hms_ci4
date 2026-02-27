<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;

class DoctorModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function getDoctors(): array
    {
        return $this->db->table('doctor_master')->get()->getResult();
    }

    public function getDoctorById(int $id): array
    {
        return $this->db->table('doctor_master')->where('id', $id)->get()->getResult();
    }

    public function insert(array $data): int
    {
        $this->db->table('doctor_master')->insert($data);

        return (int) $this->db->insertID();
    }

    public function updateDoctor(array $data, int $id): bool
    {
        return (bool) $this->db->table('doctor_master')->where('id', $id)->update($data);
    }

    public function insertSpec(array $data): int
    {
        $this->db->table('doc_spec')->insert($data);

        return (int) $this->db->insertID();
    }

    public function removeSpec(int $id): bool
    {
        return (bool) $this->db->table('doc_spec')->where('id', $id)->delete();
    }

    public function specExists(int $docId, int $specId): bool
    {
        return $this->db->table('doc_spec')
            ->where('doc_id', $docId)
            ->where('med_spec_id', $specId)
            ->countAllResults() > 0;
    }

    public function getDoctorSpecs(int $docId): array
    {
        return $this->db->table('med_spec m')
            ->select('m.SpecName, m.id, d.doc_id, d.med_spec_id, d.id as doc_spec_id')
            ->join('doc_spec d', 'm.id = d.med_spec_id')
            ->where('d.doc_id', $docId)
            ->get()
            ->getResult();
    }

    public function getSpecsList(): array
    {
        return $this->db->table('med_spec')->orderBy('SpecName')->get()->getResult();
    }

    public function getFeeTypes(): array
    {
        return $this->db->table('doc_fee_type')->orderBy('fee_type')->get()->getResult();
    }

    public function getDoctorFees(int $docId): array
    {
        return $this->db->table('doc_fee_type f')
            ->select('d.id, f.fee_type, d.doc_fee_desc, d.amount')
            ->join('doc_opd_fee d', 'd.doc_fee_type = f.id AND d.doc_id = ' . (int) $docId, 'left')
            ->orderBy('f.id')
            ->get()
            ->getResult();
    }

    public function getDoctorIpdFees(int $docId): array
    {
        return $this->db->table('doc_ipd_fee_type f')
            ->select('d.id, f.fee_type, d.doc_fee_desc, d.amount')
            ->join('doc_ipd_fee d', 'd.doc_fee_type = f.id AND d.doc_id = ' . (int) $docId, 'left')
            ->orderBy('f.id')
            ->get()
            ->getResult();
    }

    public function getDoctorFeeByType(int $docId, int $feeType): ?object
    {
        return $this->db->table('doc_opd_fee')
            ->where('doc_id', $docId)
            ->where('doc_fee_type', $feeType)
            ->get()
            ->getRow();
    }

    public function getIpdFeeTypes(): array
    {
        return $this->db->table('doc_ipd_fee_type')->orderBy('fee_type')->get()->getResult();
    }

    public function getDoctorIpdFeeByType(int $docId, int $feeType): ?object
    {
        return $this->db->table('doc_ipd_fee')
            ->where('doc_id', $docId)
            ->where('doc_fee_type', $feeType)
            ->get()
            ->getRow();
    }

    public function insertFee(array $data): int
    {
        $this->db->table('doc_opd_fee')->insert($data);

        return (int) $this->db->insertID();
    }

    public function insertIpdFee(array $data): int
    {
        $this->db->table('doc_ipd_fee')->insert($data);

        return (int) $this->db->insertID();
    }

    public function removeFee(int $id, array $auditData = []): bool
    {
        $row = $this->db->table('doc_opd_fee')->where('id', $id)->get()->getRowArray();
        if ($row === null) {
            return false;
        }

        $row = array_merge($row, $auditData);
        $this->db->table('doc_opd_fee_update')->insert($row);

        return (bool) $this->db->table('doc_opd_fee')->where('id', $id)->delete();
    }

    public function removeIpdFee(int $id): bool
    {
        return (bool) $this->db->table('doc_ipd_fee')->where('id', $id)->delete();
    }

    public function insertIpdFeeType(string $name): int
    {
        $this->db->table('doc_ipd_fee_type')->insert(['fee_type' => $name]);

        return (int) $this->db->insertID();
    }

    public function updateIpdFeeType(int $id, string $name): bool
    {
        return (bool) $this->db->table('doc_ipd_fee_type')->where('id', $id)->update(['fee_type' => $name]);
    }

    public function deleteIpdFeeType(int $id): bool
    {
        return (bool) $this->db->table('doc_ipd_fee_type')->where('id', $id)->delete();
    }

    public function getMedSpecs(): array
    {
        return $this->db->table('med_spec')->orderBy('SpecName')->get()->getResult();
    }

    public function insertMedSpec(string $name): int
    {
        $this->db->table('med_spec')->insert(['SpecName' => $name]);

        return (int) $this->db->insertID();
    }

    public function updateMedSpec(int $id, string $name): bool
    {
        return (bool) $this->db->table('med_spec')->where('id', $id)->update(['SpecName' => $name]);
    }

    public function deleteMedSpec(int $id): bool
    {
        return (bool) $this->db->table('med_spec')->where('id', $id)->delete();
    }
}

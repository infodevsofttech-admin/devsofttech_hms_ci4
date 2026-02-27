<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;

class OpdModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function insertOpd(array $data): int
    {
        $pId = (int) ($data['p_id'] ?? 0);
        $docId = (int) ($data['doc_id'] ?? 0);

        if ($pId <= 0 || $docId <= 0) {
            return 0;
        }

        $duplicate = $this->db->table('opd_master')
            ->where('apointment_date', date('Y-m-d'))
            ->where('p_id', $pId)
            ->where('doc_id', $docId)
            ->groupStart()
                ->where('payment_status', 0)
                ->orWhere('opd_status', 1)
            ->groupEnd()
            ->countAllResults();

        if ($duplicate > 0) {
            return 0;
        }

        $this->db->table('opd_master')->insert($data);
        $insertId = (int) $this->db->insertID();
        if ($insertId <= 0) {
            return 0;
        }

        $opdTimesRow = $this->db->table('opd_master')
            ->select('count(*) as xtimes')
            ->where('opd_id <=', $insertId)
            ->where('p_id', $pId)
            ->get()
            ->getRow();
        $opdTimes = (int) ($opdTimesRow->xtimes ?? 0);

        $opdNoRow = $this->db->table('opd_master')
            ->select('count(*) as no_max')
            ->where('apointment_date', date('Y-m-d'))
            ->where('doc_id', $docId)
            ->get()
            ->getRow();
        $opdNo = (int) ($opdNoRow->no_max ?? 1);
        if ($opdNo <= 0) {
            $opdNo = 1;
        }

        $pidSuffix = str_pad(substr((string) $insertId, -7, 7), 7, '0', STR_PAD_LEFT);
        $opdCode = 'D' . date('ym') . $pidSuffix;

        $runningOpd = 0;
        $lastOpdId = 0;

        if ((int) ($data['opd_fee_type'] ?? 0) === 3) {
            $runningOpd = 1;

            $lastOpdRow = $this->db->table('opd_master')
                ->select('max(opd_id) as last_opd_id')
                ->where('apointment_date >=', date('Y-m-d', strtotime('-4 days')))
                ->where('apointment_date <=', date('Y-m-d'))
                ->where('running_opd', 0)
                ->where('doc_id', $docId)
                ->where('p_id', $pId)
                ->get()
                ->getRow();

            $lastOpdId = (int) ($lastOpdRow->last_opd_id ?? 0);
        }

        $update = [
            'opd_code' => $opdCode,
            'no_visit' => $opdTimes,
            'running_opd' => $runningOpd,
            'running_opd_id' => $lastOpdId,
            'opd_no' => $opdNo,
        ];

        $this->db->table('opd_master')
            ->where('opd_id', $insertId)
            ->update($update);

        return $insertId;
    }
}

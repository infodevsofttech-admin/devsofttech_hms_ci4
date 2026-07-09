<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AdjustAbdmHiuWorkflowRequestOperationIndex extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('abdm_hiu_workflows')) {
            return;
        }

        $indexes = $this->db->query('SHOW INDEX FROM abdm_hiu_workflows')->getResultArray();
        $hasReqOpUnique = false;
        $hasReqOpIdx = false;

        foreach ($indexes as $idx) {
            $name = (string) ($idx['Key_name'] ?? '');
            if ($name === 'uniq_abdm_hiu_req_op') {
                $hasReqOpUnique = true;
            }
            if ($name === 'idx_abdm_hiu_req_op') {
                $hasReqOpIdx = true;
            }
        }

        if ($hasReqOpUnique) {
            $this->db->query('ALTER TABLE abdm_hiu_workflows DROP INDEX uniq_abdm_hiu_req_op');
        }

        if (! $hasReqOpIdx) {
            $this->db->query('ALTER TABLE abdm_hiu_workflows ADD INDEX idx_abdm_hiu_req_op (request_id, operation)');
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('abdm_hiu_workflows')) {
            return;
        }

        $indexes = $this->db->query('SHOW INDEX FROM abdm_hiu_workflows')->getResultArray();
        $hasReqOpUnique = false;
        $hasReqOpIdx = false;

        foreach ($indexes as $idx) {
            $name = (string) ($idx['Key_name'] ?? '');
            if ($name === 'uniq_abdm_hiu_req_op') {
                $hasReqOpUnique = true;
            }
            if ($name === 'idx_abdm_hiu_req_op') {
                $hasReqOpIdx = true;
            }
        }

        if ($hasReqOpIdx) {
            $this->db->query('ALTER TABLE abdm_hiu_workflows DROP INDEX idx_abdm_hiu_req_op');
        }

        if (! $hasReqOpUnique) {
            $this->db->query('ALTER TABLE abdm_hiu_workflows ADD UNIQUE INDEX uniq_abdm_hiu_req_op (request_id, operation)');
        }
    }
}

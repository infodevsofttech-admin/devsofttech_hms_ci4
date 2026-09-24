<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCompositeIndexesToAbdmHiuWorkflows extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('abdm_hiu_workflows')) {
            return;
        }

        if (! $this->indexExists('abdm_hiu_workflows', 'idx_abdm_hiu_op_status_id')) {
            $this->db->query('CREATE INDEX idx_abdm_hiu_op_status_id ON abdm_hiu_workflows (operation, status, id)');
        }

        if (! $this->indexExists('abdm_hiu_workflows', 'idx_abdm_hiu_op_status_created')) {
            $this->db->query('CREATE INDEX idx_abdm_hiu_op_status_created ON abdm_hiu_workflows (operation, status, created_at)');
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('abdm_hiu_workflows')) {
            return;
        }

        if ($this->indexExists('abdm_hiu_workflows', 'idx_abdm_hiu_op_status_id')) {
            $this->db->query('DROP INDEX idx_abdm_hiu_op_status_id ON abdm_hiu_workflows');
        }

        if ($this->indexExists('abdm_hiu_workflows', 'idx_abdm_hiu_op_status_created')) {
            $this->db->query('DROP INDEX idx_abdm_hiu_op_status_created ON abdm_hiu_workflows');
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $rows = $this->db->query('SHOW INDEX FROM ' . $table)->getResultArray();
        foreach ($rows as $row) {
            if (($row['Key_name'] ?? '') === $indexName) {
                return true;
            }
        }

        return false;
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCreatedAtIndexToAbdmHiuWorkflows extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('abdm_hiu_workflows')) {
            return;
        }

        // Existing composite keys all start with another column, so retention
        // pruning by age alone would table-scan.
        if (! $this->indexExists('abdm_hiu_workflows', 'idx_abdm_hiu_created_at')) {
            $this->db->query('CREATE INDEX idx_abdm_hiu_created_at ON abdm_hiu_workflows (created_at)');
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('abdm_hiu_workflows')) {
            return;
        }

        if ($this->indexExists('abdm_hiu_workflows', 'idx_abdm_hiu_created_at')) {
            $this->db->query('DROP INDEX idx_abdm_hiu_created_at ON abdm_hiu_workflows');
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

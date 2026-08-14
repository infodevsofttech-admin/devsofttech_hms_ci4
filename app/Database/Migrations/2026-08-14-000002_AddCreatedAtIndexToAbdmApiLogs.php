<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCreatedAtIndexToAbdmApiLogs extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('abdm_api_logs')) {
            return;
        }

        // Only channel/status/event_type were indexed, so retention pruning by age would table-scan.
        if (! $this->indexExists('abdm_api_logs', 'idx_abdm_api_logs_created_at')) {
            $this->db->query('CREATE INDEX idx_abdm_api_logs_created_at ON abdm_api_logs (created_at)');
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('abdm_api_logs')) {
            return;
        }

        if ($this->indexExists('abdm_api_logs', 'idx_abdm_api_logs_created_at')) {
            $this->db->query('DROP INDEX idx_abdm_api_logs_created_at ON abdm_api_logs');
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

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddConsentMappingColumnsToAbdmHiuWorkflows extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('abdm_hiu_workflows')) {
            return;
        }

        $fields = $this->db->getFieldNames('abdm_hiu_workflows') ?? [];

        $add = [];
        if (! in_array('hms_request_id', $fields, true)) {
            $add['hms_request_id'] = [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
                'after' => 'request_id',
            ];
        }
        if (! in_array('gateway_request_id', $fields, true)) {
            $add['gateway_request_id'] = [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
                'after' => 'hms_request_id',
            ];
        }
        if (! in_array('abdm_consent_request_id', $fields, true)) {
            $add['abdm_consent_request_id'] = [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
                'after' => 'consent_id',
            ];
        }
        if (! in_array('abdm_consent_artifact_id', $fields, true)) {
            $add['abdm_consent_artifact_id'] = [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
                'after' => 'abdm_consent_request_id',
            ];
        }

        if (! empty($add)) {
            $this->forge->addColumn('abdm_hiu_workflows', $add);
        }

        $fields = $this->db->getFieldNames('abdm_hiu_workflows') ?? [];
        if (in_array('abdm_consent_request_id', $fields, true)) {
            if (! $this->indexExists('abdm_hiu_workflows', 'idx_abdm_hiu_abdm_consent_req')) {
                $this->db->query('CREATE INDEX idx_abdm_hiu_abdm_consent_req ON abdm_hiu_workflows (abdm_consent_request_id)');
            }
        }
        if (in_array('hms_request_id', $fields, true)) {
            if (! $this->indexExists('abdm_hiu_workflows', 'idx_abdm_hiu_hms_req')) {
                $this->db->query('CREATE INDEX idx_abdm_hiu_hms_req ON abdm_hiu_workflows (hms_request_id)');
            }
        }
        if (in_array('gateway_request_id', $fields, true)) {
            if (! $this->indexExists('abdm_hiu_workflows', 'idx_abdm_hiu_gateway_req')) {
                $this->db->query('CREATE INDEX idx_abdm_hiu_gateway_req ON abdm_hiu_workflows (gateway_request_id)');
            }
        }
        if (in_array('abdm_consent_artifact_id', $fields, true)) {
            if (! $this->indexExists('abdm_hiu_workflows', 'idx_abdm_hiu_abdm_consent_artifact')) {
                $this->db->query('CREATE INDEX idx_abdm_hiu_abdm_consent_artifact ON abdm_hiu_workflows (abdm_consent_artifact_id)');
            }
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('abdm_hiu_workflows')) {
            return;
        }

        $fields = $this->db->getFieldNames('abdm_hiu_workflows') ?? [];

        if (in_array('hms_request_id', $fields, true)) {
            $this->forge->dropColumn('abdm_hiu_workflows', 'hms_request_id');
        }
        if (in_array('gateway_request_id', $fields, true)) {
            $this->forge->dropColumn('abdm_hiu_workflows', 'gateway_request_id');
        }
        if (in_array('abdm_consent_request_id', $fields, true)) {
            $this->forge->dropColumn('abdm_hiu_workflows', 'abdm_consent_request_id');
        }
        if (in_array('abdm_consent_artifact_id', $fields, true)) {
            $this->forge->dropColumn('abdm_hiu_workflows', 'abdm_consent_artifact_id');
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $rows = $this->db->query('SHOW INDEX FROM ' . $this->db->escapeIdentifiers($table))->getResultArray();
        foreach ($rows as $row) {
            if (strcasecmp((string) ($row['Key_name'] ?? ''), $indexName) === 0) {
                return true;
            }
        }

        return false;
    }
}

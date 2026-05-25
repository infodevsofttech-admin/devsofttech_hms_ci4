<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAbdmDrugTerminologyFieldsToMedProductMaster extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('med_product_master')) {
            return;
        }

        $fields = $this->db->getFieldNames('med_product_master') ?? [];
        $add = [];

        if (! in_array('abdm_drug_type', $fields, true)) {
            $add['abdm_drug_type'] = ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'default' => null];
        }
        if (! in_array('abdm_drug_identifier', $fields, true)) {
            $add['abdm_drug_identifier'] = ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'default' => null];
        }
        if (! in_array('abdm_drug_display', $fields, true)) {
            $add['abdm_drug_display'] = ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null];
        }
        if (! in_array('abdm_drug_generic', $fields, true)) {
            $add['abdm_drug_generic'] = ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null];
        }
        if (! in_array('abdm_drug_payload_json', $fields, true)) {
            $add['abdm_drug_payload_json'] = ['type' => 'MEDIUMTEXT', 'null' => true];
        }
        if (! in_array('abdm_drug_last_synced_at', $fields, true)) {
            $add['abdm_drug_last_synced_at'] = ['type' => 'DATETIME', 'null' => true, 'default' => null];
        }

        if ($add !== []) {
            $this->forge->addColumn('med_product_master', $add);
        }

        // Optional index for fast traceability lookup.
        $indexes = $this->db->query('SHOW INDEX FROM med_product_master')->getResultArray();
        $hasIdx = false;
        foreach ($indexes as $idx) {
            if (strcasecmp((string) ($idx['Key_name'] ?? ''), 'idx_abdm_drug_identifier') === 0) {
                $hasIdx = true;
                break;
            }
        }

        if (! $hasIdx && in_array('abdm_drug_identifier', $this->db->getFieldNames('med_product_master') ?? [], true)) {
            try {
                $this->db->query('ALTER TABLE med_product_master ADD INDEX idx_abdm_drug_identifier (abdm_drug_identifier)');
            } catch (\Throwable $e) {
                // Ignore index creation failures to keep migration non-blocking.
            }
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('med_product_master')) {
            return;
        }

        try {
            $this->db->query('ALTER TABLE med_product_master DROP INDEX idx_abdm_drug_identifier');
        } catch (\Throwable $e) {
            // no-op
        }

        $fields = $this->db->getFieldNames('med_product_master') ?? [];
        foreach ([
            'abdm_drug_type',
            'abdm_drug_identifier',
            'abdm_drug_display',
            'abdm_drug_generic',
            'abdm_drug_payload_json',
            'abdm_drug_last_synced_at',
        ] as $column) {
            if (in_array($column, $fields, true)) {
                $this->forge->dropColumn('med_product_master', $column);
            }
        }
    }
}

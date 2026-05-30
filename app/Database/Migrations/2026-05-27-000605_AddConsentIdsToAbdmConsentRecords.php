<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds consent id tracking columns to abdm_consent_records.
 *
 * gateway_consent_id captures consent id returned by the bridge/gateway so
 * downstream share/audit flows can map consent_handle <-> gateway consent id.
 */
class AddConsentIdsToAbdmConsentRecords extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('abdm_consent_records')) {
            return;
        }

        $fields = $this->db->getFieldNames('abdm_consent_records') ?? [];

        if (! in_array('consent_id', $fields, true)) {
            $this->forge->addColumn('abdm_consent_records', [
                'consent_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                    'null'       => true,
                    'after'      => 'consent_handle',
                ],
            ]);
        }

        $fields = $this->db->getFieldNames('abdm_consent_records') ?? [];
        if (! in_array('gateway_consent_id', $fields, true)) {
            $this->forge->addColumn('abdm_consent_records', [
                'gateway_consent_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                    'null'       => true,
                    'after'      => 'consent_id',
                ],
            ]);
        }

        $indexes = $this->db->getIndexData('abdm_consent_records') ?? [];
        $indexNames = array_map(static fn ($i) => (string) ($i->name ?? ''), $indexes);

        if (! in_array('idx_abdm_consent_id', $indexNames, true)) {
            $this->db->query('CREATE INDEX idx_abdm_consent_id ON abdm_consent_records (consent_id)');
        }

        if (! in_array('idx_abdm_gateway_consent_id', $indexNames, true)) {
            $this->db->query('CREATE INDEX idx_abdm_gateway_consent_id ON abdm_consent_records (gateway_consent_id)');
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('abdm_consent_records')) {
            return;
        }

        $fields = $this->db->getFieldNames('abdm_consent_records') ?? [];

        if (in_array('gateway_consent_id', $fields, true)) {
            $this->forge->dropColumn('abdm_consent_records', 'gateway_consent_id');
        }

        $fields = $this->db->getFieldNames('abdm_consent_records') ?? [];
        if (in_array('consent_id', $fields, true)) {
            $this->forge->dropColumn('abdm_consent_records', 'consent_id');
        }
    }
}

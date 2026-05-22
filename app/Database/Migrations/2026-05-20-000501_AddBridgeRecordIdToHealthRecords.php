<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds bridge_record_id (integer) to health_records.
 *
 * The bridge POST /api/v3/records/push response now returns:
 *   record_id  (int)   — bridge's internal record identifier; needed for
 *                        GET  /api/v3/records/{id}
 *                        POST /api/v3/records/{id}/share
 *   queue_id   (string) — already stored in abdm_txn_id
 *
 * This migration stores record_id in bridge_record_id so the HMS can
 * trigger manual share / status checks without re-pushing.
 */
class AddBridgeRecordIdToHealthRecords extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('health_records')) {
            return;
        }

        $fields = $this->db->getFieldNames('health_records');

        if (! in_array('bridge_record_id', $fields, true)) {
            $this->forge->addColumn('health_records', [
                'bridge_record_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'abdm_txn_id',
                ],
            ]);
        }

        // Add index for fast lookups by bridge record id
        $indexes = $this->db->getIndexData('health_records');
        $indexNames = array_map(static fn ($i) => $i->name, $indexes);
        if (! in_array('idx_hr_bridge_record_id', $indexNames, true)) {
            $this->forge->addKey('bridge_record_id');
            // forge->addKey on existing table requires processIndexes
            // Use raw query as CI4 forge does not support addKey on existing tables directly.
            $this->db->query('CREATE INDEX idx_hr_bridge_record_id ON health_records (bridge_record_id)');
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('health_records')) {
            return;
        }

        $fields = $this->db->getFieldNames('health_records');
        if (in_array('bridge_record_id', $fields, true)) {
            $this->forge->dropColumn('health_records', 'bridge_record_id');
        }
    }
}

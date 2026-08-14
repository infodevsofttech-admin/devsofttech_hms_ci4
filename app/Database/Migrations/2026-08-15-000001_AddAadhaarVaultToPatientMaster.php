<?php

namespace App\Database\Migrations;

use App\Libraries\AadhaarVaultService;
use CodeIgniter\Database\Migration;

class AddAadhaarVaultToPatientMaster extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('patient_master')) {
            return;
        }

        $fields = $this->db->getFieldNames('patient_master') ?? [];
        $add = [];

        if (! in_array('udai_enc', $fields, true)) {
            $add['udai_enc'] = ['type' => 'TEXT', 'null' => true, 'comment' => 'AES-256-GCM encrypted Aadhaar'];
        }
        if (! in_array('udai_hash', $fields, true)) {
            $add['udai_hash'] = ['type' => 'CHAR', 'constraint' => 64, 'null' => true, 'comment' => 'HMAC-SHA256 of Aadhaar for exact-match search'];
        }
        if (! in_array('udai_last4', $fields, true)) {
            $add['udai_last4'] = ['type' => 'VARCHAR', 'constraint' => 4, 'null' => true, 'comment' => 'Last 4 digits for display'];
        }

        if ($add !== []) {
            $this->forge->addColumn('patient_master', $add);
        }

        if (! $this->indexExists('patient_master', 'idx_patient_udai_hash')) {
            $this->db->query('CREATE INDEX idx_patient_udai_hash ON patient_master (udai_hash)');
        }

        $this->migrateExistingAadhaar();
    }

    public function down(): void
    {
        if (! $this->db->tableExists('patient_master')) {
            return;
        }

        if ($this->indexExists('patient_master', 'idx_patient_udai_hash')) {
            $this->db->query('DROP INDEX idx_patient_udai_hash ON patient_master');
        }

        $fields = $this->db->getFieldNames('patient_master') ?? [];
        foreach (['udai_enc', 'udai_hash', 'udai_last4'] as $column) {
            if (in_array($column, $fields, true)) {
                $this->forge->dropColumn('patient_master', $column);
            }
        }
    }

    /**
     * Move any plaintext Aadhaar already in udai into the vault and mask the column.
     */
    private function migrateExistingAadhaar(): void
    {
        $vault = new AadhaarVaultService();

        $rows = $this->db->table('patient_master')
            ->select('id, udai')
            ->where('udai IS NOT NULL', null, false)
            ->where("TRIM(udai) <> ''", null, false)
            ->where('udai_enc IS NULL', null, false)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $digits = $vault->normalize((string) ($row['udai'] ?? ''));
            if ($digits === '') {
                continue;
            }

            $this->db->table('patient_master')
                ->where('id', (int) $row['id'])
                ->update($vault->buildColumns($digits));
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        foreach ($this->db->query('SHOW INDEX FROM ' . $table)->getResultArray() as $row) {
            if (($row['Key_name'] ?? '') === $indexName) {
                return true;
            }
        }

        return false;
    }
}

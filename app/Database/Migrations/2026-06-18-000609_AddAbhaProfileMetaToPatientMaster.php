<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAbhaProfileMetaToPatientMaster extends Migration
{
    public function up(): void
    {
        if (! $this->tableExists('patient_master')) {
            return;
        }

        $fields = $this->getColumns('patient_master');
        $add = [];

        if (! in_array('abha_profile_photo_base64', $fields, true)) {
            $add['abha_profile_photo_base64'] = [
                'type' => 'LONGTEXT',
                'null' => true,
            ];
        }

        if (! in_array('abha_verified_status', $fields, true)) {
            $add['abha_verified_status'] = [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ];
        }

        if (! in_array('abha_verification_type', $fields, true)) {
            $add['abha_verification_type'] = [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ];
        }

        if (! in_array('abha_kyc_verified', $fields, true)) {
            $add['abha_kyc_verified'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => true,
                'default' => 0,
            ];
        }

        if (! in_array('abha_mobile_verified', $fields, true)) {
            $add['abha_mobile_verified'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => true,
                'default' => 0,
            ];
        }

        if ($add !== []) {
            $this->forge->addColumn('patient_master', $add);
        }
    }

    public function down(): void
    {
        if (! $this->tableExists('patient_master')) {
            return;
        }

        $fields = $this->getColumns('patient_master');
        foreach ([
            'abha_profile_photo_base64',
            'abha_verified_status',
            'abha_verification_type',
            'abha_kyc_verified',
            'abha_mobile_verified',
        ] as $column) {
            if (in_array($column, $fields, true)) {
                $this->forge->dropColumn('patient_master', $column);
            }
        }
    }

    /**
     * @return string[]
     */
    private function getColumns(string $table): array
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [];
        }

        $result = $this->db->query('SHOW COLUMNS FROM `' . $table . '`')->getResultArray();

        return array_map(static fn (array $row): string => (string) ($row['Field'] ?? ''), $result);
    }

    private function tableExists(string $table): bool
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }

        $row = $this->db->query("SHOW TABLES LIKE '" . $table . "'")->getRowArray();

        return ! empty($row);
    }
}

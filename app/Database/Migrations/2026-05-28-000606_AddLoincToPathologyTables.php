<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds LOINC coding fields to pathology master tables so that
 * lab test results can be published as structured FHIR Observations
 * with standard LOINC codes for ABDM DiagnosticReportRecord.
 *
 * lab_tests  → loinc_code, loinc_property, loinc_system, loinc_scale
 * lab_repo   → loinc_code  (LOINC panel code for the DiagnosticReport.code)
 */
class AddLoincToPathologyTables extends Migration
{
    public function up(): void
    {
        $this->addLabTestsLoincColumns();
        $this->addLabRepoLoincColumn();
    }

    private function addLabTestsLoincColumns(): void
    {
        if (! $this->db->tableExists('lab_tests')) {
            return;
        }

        $existing = $this->db->getFieldNames('lab_tests') ?? [];
        $add = [];

        if (! in_array('loinc_code', $existing, true)) {
            $add['loinc_code'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
                'after'      => 'Unit',
            ];
        }
        if (! in_array('loinc_property', $existing, true)) {
            $add['loinc_property'] = [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
                'after'      => 'loinc_code',
            ];
        }
        if (! in_array('loinc_system', $existing, true)) {
            $add['loinc_system'] = [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
                'after'      => 'loinc_property',
            ];
        }
        if (! in_array('loinc_scale', $existing, true)) {
            $add['loinc_scale'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
                'after'      => 'loinc_system',
            ];
        }
        if (! in_array('loinc_synced_at', $existing, true)) {
            $add['loinc_synced_at'] = [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'after'   => 'loinc_scale',
            ];
        }

        if ($add !== []) {
            $this->forge->addColumn('lab_tests', $add);
        }

        // Index for fast LOINC lookup
        try {
            $indexes = $this->db->query('SHOW INDEX FROM lab_tests')->getResultArray();
            $hasIdx  = false;
            foreach ($indexes as $idx) {
                if (strcasecmp((string) ($idx['Key_name'] ?? ''), 'idx_lab_tests_loinc') === 0) {
                    $hasIdx = true;
                    break;
                }
            }
            if (! $hasIdx && in_array('loinc_code', $this->db->getFieldNames('lab_tests') ?? [], true)) {
                $this->db->query('ALTER TABLE lab_tests ADD INDEX idx_lab_tests_loinc (loinc_code)');
            }
        } catch (\Throwable $e) {
            // Non-blocking index creation
        }
    }

    private function addLabRepoLoincColumn(): void
    {
        if (! $this->db->tableExists('lab_repo')) {
            return;
        }

        $existing = $this->db->getFieldNames('lab_repo') ?? [];

        if (! in_array('loinc_code', $existing, true)) {
            $this->forge->addColumn('lab_repo', [
                'loinc_code' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'Title',
                ],
            ]);
        }
        if (! in_array('loinc_synced_at', $existing, true)) {
            $this->forge->addColumn('lab_repo', [
                'loinc_synced_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                    'after'   => 'loinc_code',
                ],
            ]);
        }
    }

    public function down(): void
    {
        foreach (['loinc_code', 'loinc_property', 'loinc_system', 'loinc_scale', 'loinc_synced_at'] as $col) {
            try {
                if ($this->db->tableExists('lab_tests') &&
                    in_array($col, $this->db->getFieldNames('lab_tests') ?? [], true)) {
                    $this->forge->dropColumn('lab_tests', $col);
                }
            } catch (\Throwable $e) {
                // no-op
            }
        }

        foreach (['loinc_code', 'loinc_synced_at'] as $col) {
            try {
                if ($this->db->tableExists('lab_repo') &&
                    in_array($col, $this->db->getFieldNames('lab_repo') ?? [], true)) {
                    $this->forge->dropColumn('lab_repo', $col);
                }
            } catch (\Throwable $e) {
                // no-op
            }
        }

        try {
            $this->db->query('ALTER TABLE lab_tests DROP INDEX idx_lab_tests_loinc');
        } catch (\Throwable $e) {
            // no-op
        }
    }
}

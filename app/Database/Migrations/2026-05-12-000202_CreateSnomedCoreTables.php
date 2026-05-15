<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSnomedCoreTables extends Migration
{
    public function up(): void
    {
        $this->createSnomedReleaseLog();
        $this->createSnomedConcept();
        $this->createSnomedDescription();
        $this->createSnomedLanguageRefset();
        $this->createSnomedSimpleMap();
        $this->createSnomedExtendedMap();
        $this->addConsultSnomedColumns();
        $this->addIpdDischargeSnomedColumns();
    }

    public function down(): void
    {
        $this->dropConsultSnomedColumns();
        $this->dropIpdDischargeSnomedColumns();

        $this->forge->dropTable('snomed_map_extended', true);
        $this->forge->dropTable('snomed_map_simple', true);
        $this->forge->dropTable('snomed_language_refset', true);
        $this->forge->dropTable('snomed_description', true);
        $this->forge->dropTable('snomed_concept', true);
        $this->forge->dropTable('snomed_release_log', true);
    }

    private function createSnomedReleaseLog(): void
    {
        if ($this->hasTable('snomed_release_log')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'package_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'release_effective_time' => [
                'type' => 'CHAR',
                'constraint' => 8,
                'null' => false,
            ],
            'rf2_md5' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'release_notes_md5' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'started',
            ],
            'row_counts_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'error_text' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'imported_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('release_effective_time');
        $this->forge->addKey('status');
        $this->forge->createTable('snomed_release_log', true);
    }

    private function createSnomedConcept(): void
    {
        if ($this->hasTable('snomed_concept')) {
            return;
        }

        $this->forge->addField([
            'concept_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'effective_time' => [
                'type' => 'CHAR',
                'constraint' => 8,
                'null' => false,
            ],
            'active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'module_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'definition_status_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('concept_id', true);
        $this->forge->addKey(['active', 'effective_time']);
        $this->forge->createTable('snomed_concept', true);
    }

    private function createSnomedDescription(): void
    {
        if ($this->hasTable('snomed_description')) {
            return;
        }

        $this->forge->addField([
            'description_id' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => false,
            ],
            'effective_time' => [
                'type' => 'CHAR',
                'constraint' => 8,
                'null' => false,
            ],
            'active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'module_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'concept_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'language_code' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => false,
            ],
            'type_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'term' => [
                'type' => 'VARCHAR',
                'constraint' => 512,
                'null' => false,
            ],
            'term_normalized' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'case_significance_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('description_id', true);
        $this->forge->addKey('concept_id');
        $this->forge->addKey(['active', 'language_code']);
        $this->forge->addKey('type_id');
        $this->forge->addKey('term_normalized');
        $this->forge->createTable('snomed_description', true);
    }

    private function createSnomedLanguageRefset(): void
    {
        if ($this->hasTable('snomed_language_refset')) {
            return;
        }

        $this->forge->addField([
            'member_id' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => false,
            ],
            'effective_time' => [
                'type' => 'CHAR',
                'constraint' => 8,
                'null' => false,
            ],
            'active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'module_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'refset_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'referenced_component_id' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => false,
            ],
            'acceptability_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('member_id', true);
        $this->forge->addKey('referenced_component_id');
        $this->forge->addKey(['refset_id', 'active']);
        $this->forge->createTable('snomed_language_refset', true);
    }

    private function createSnomedSimpleMap(): void
    {
        if ($this->hasTable('snomed_map_simple')) {
            return;
        }

        $this->forge->addField([
            'member_id' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => false,
            ],
            'effective_time' => [
                'type' => 'CHAR',
                'constraint' => 8,
                'null' => false,
            ],
            'active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'module_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'refset_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'referenced_component_id' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => false,
            ],
            'map_target' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('member_id', true);
        $this->forge->addKey(['referenced_component_id', 'active']);
        $this->forge->addKey('refset_id');
        $this->forge->createTable('snomed_map_simple', true);
    }

    private function createSnomedExtendedMap(): void
    {
        if ($this->hasTable('snomed_map_extended')) {
            return;
        }

        $this->forge->addField([
            'member_id' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => false,
            ],
            'effective_time' => [
                'type' => 'CHAR',
                'constraint' => 8,
                'null' => false,
            ],
            'active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'module_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'refset_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'referenced_component_id' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => false,
            ],
            'map_group' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'map_priority' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'map_rule' => [
                'type' => 'VARCHAR',
                'constraint' => 512,
                'null' => true,
            ],
            'map_advice' => [
                'type' => 'VARCHAR',
                'constraint' => 512,
                'null' => true,
            ],
            'map_target' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
            ],
            'correlation_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'map_category_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('member_id', true);
        $this->forge->addKey(['referenced_component_id', 'active']);
        $this->forge->addKey(['refset_id', 'map_target']);
        $this->forge->createTable('snomed_map_extended', true);
    }

    private function addConsultSnomedColumns(): void
    {
        if (! $this->hasTable('opd_prescription')) {
            return;
        }

        $fields = $this->getTableFields('opd_prescription');
        $toAdd = [];

        if (! in_array('provisional_diagnosis_snomed_id', $fields, true)) {
            $toAdd['provisional_diagnosis_snomed_id'] = [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'after' => 'Provisional_diagnosis',
            ];
        }

        if (! in_array('provisional_diagnosis_snomed_term', $fields, true)) {
            $toAdd['provisional_diagnosis_snomed_term'] = [
                'type' => 'VARCHAR',
                'constraint' => 512,
                'null' => true,
                'after' => 'provisional_diagnosis_snomed_id',
            ];
        }

        if (! in_array('provisional_diagnosis_snomed_source', $fields, true)) {
            $toAdd['provisional_diagnosis_snomed_source'] = [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
                'after' => 'provisional_diagnosis_snomed_term',
            ];
        }

        if (! empty($toAdd)) {
            $this->forge->addColumn('opd_prescription', $toAdd);
        }
    }

    private function addIpdDischargeSnomedColumns(): void
    {
        if (! $this->hasTable('ipd_discharge_diagnosis')) {
            return;
        }

        $fields = $this->getTableFields('ipd_discharge_diagnosis');
        $toAdd = [];

        if (! in_array('snomed_concept_id', $fields, true)) {
            $toAdd['snomed_concept_id'] = [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ];
        }

        if (! in_array('snomed_term', $fields, true)) {
            $toAdd['snomed_term'] = [
                'type' => 'VARCHAR',
                'constraint' => 512,
                'null' => true,
            ];
        }

        if (! in_array('snomed_source', $fields, true)) {
            $toAdd['snomed_source'] = [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ];
        }

        if (! empty($toAdd)) {
            $this->forge->addColumn('ipd_discharge_diagnosis', $toAdd);
        }
    }

    private function dropConsultSnomedColumns(): void
    {
        if (! $this->hasTable('opd_prescription')) {
            return;
        }

        $fields = $this->getTableFields('opd_prescription');
        foreach (['provisional_diagnosis_snomed_id', 'provisional_diagnosis_snomed_term', 'provisional_diagnosis_snomed_source'] as $field) {
            if (in_array($field, $fields, true)) {
                $this->forge->dropColumn('opd_prescription', $field);
            }
        }
    }

    private function dropIpdDischargeSnomedColumns(): void
    {
        if (! $this->hasTable('ipd_discharge_diagnosis')) {
            return;
        }

        $fields = $this->getTableFields('ipd_discharge_diagnosis');
        foreach (['snomed_concept_id', 'snomed_term', 'snomed_source'] as $field) {
            if (in_array($field, $fields, true)) {
                $this->forge->dropColumn('ipd_discharge_diagnosis', $field);
            }
        }
    }

    private function hasTable(string $table): bool
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }

        $row = $this->db->query("SHOW TABLES LIKE '" . $table . "'")->getRowArray();
        return ! empty($row);
    }

    /**
     * @return list<string>
     */
    private function getTableFields(string $table): array
    {
        if (! $this->hasTable($table)) {
            return [];
        }

        $fields = $this->db->query('SHOW COLUMNS FROM ' . $table)->getResultArray();
        $names = [];
        foreach ($fields as $field) {
            $name = (string) ($field['Field'] ?? '');
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }
}

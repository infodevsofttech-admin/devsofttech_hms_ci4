<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSnomedColumnsToClinicalMasters extends Migration
{
    public function up(): void
    {
        // complaints_master
        if ($this->db->tableExists('complaints_master')) {
            $fields = $this->db->getFieldNames('complaints_master') ?? [];

            if (! in_array('snomed_concept_id', $fields, true)) {
                $this->forge->addColumn('complaints_master', [
                    'snomed_concept_id' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 20,
                        'null'       => true,
                        'default'    => null,
                        'after'      => 'is_active',
                    ],
                ]);
            }

            if (! in_array('snomed_term', $fields, true)) {
                $this->forge->addColumn('complaints_master', [
                    'snomed_term' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                        'null'       => true,
                        'default'    => null,
                        'after'      => 'snomed_concept_id',
                    ],
                ]);
            }
        }

        // disease_master
        if ($this->db->tableExists('disease_master')) {
            $fields = $this->db->getFieldNames('disease_master') ?? [];

            if (! in_array('snomed_concept_id', $fields, true)) {
                $this->forge->addColumn('disease_master', [
                    'snomed_concept_id' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 20,
                        'null'       => true,
                        'default'    => null,
                    ],
                ]);
            }

            if (! in_array('snomed_term', $fields, true)) {
                $this->forge->addColumn('disease_master', [
                    'snomed_term' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 255,
                        'null'       => true,
                        'default'    => null,
                    ],
                ]);
            }

            if (! in_array('is_active', $fields, true)) {
                $this->forge->addColumn('disease_master', [
                    'is_active' => [
                        'type'       => 'TINYINT',
                        'constraint' => 1,
                        'default'    => 1,
                        'after'      => 'snomed_term',
                    ],
                ]);
            }
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('complaints_master')) {
            $fields = $this->db->getFieldNames('complaints_master') ?? [];
            if (in_array('snomed_concept_id', $fields, true)) {
                $this->forge->dropColumn('complaints_master', 'snomed_concept_id');
            }
            if (in_array('snomed_term', $fields, true)) {
                $this->forge->dropColumn('complaints_master', 'snomed_term');
            }
        }

        if ($this->db->tableExists('disease_master')) {
            $fields = $this->db->getFieldNames('disease_master') ?? [];
            if (in_array('snomed_concept_id', $fields, true)) {
                $this->forge->dropColumn('disease_master', 'snomed_concept_id');
            }
            if (in_array('snomed_term', $fields, true)) {
                $this->forge->dropColumn('disease_master', 'snomed_term');
            }
            if (in_array('is_active', $fields, true)) {
                $this->forge->dropColumn('disease_master', 'is_active');
            }
        }
    }
}

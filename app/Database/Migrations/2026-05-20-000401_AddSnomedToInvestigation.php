<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSnomedToInvestigation extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('investigation')) {
            return;
        }

        $fields = $this->db->getFieldNames('investigation');
        $add    = [];

        if (! in_array('snomed_concept_id', $fields, true)) {
            $add['snomed_concept_id'] = ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'default' => null];
        }
        if (! in_array('snomed_term', $fields, true)) {
            $add['snomed_term'] = ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'default' => null];
        }
        if (! in_array('loinc_code', $fields, true)) {
            $add['loinc_code'] = ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => null];
        }

        if (! empty($add)) {
            $this->forge->addColumn('investigation', $add);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('investigation')) {
            return;
        }

        $fields = $this->db->getFieldNames('investigation');
        foreach (['snomed_concept_id', 'snomed_term', 'loinc_code'] as $col) {
            if (in_array($col, $fields, true)) {
                $this->forge->dropColumn('investigation', $col);
            }
        }
    }
}

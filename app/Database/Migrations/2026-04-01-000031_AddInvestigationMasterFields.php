<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInvestigationMasterFields extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('investigation')) {
            return;
        }

        $fields = $this->db->getFieldNames('investigation');
        $add    = [];

        if (! in_array('is_favourite', $fields, true)) {
            $add['is_favourite'] = ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0];
        }
        if (! in_array('spec_ids', $fields, true)) {
            $add['spec_ids'] = ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'default' => null];
        }
        if (! in_array('category_name', $fields, true)) {
            $add['category_name'] = ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null];
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
        foreach (['is_favourite', 'spec_ids', 'category_name'] as $col) {
            if (in_array($col, $fields, true)) {
                $this->forge->dropColumn('investigation', $col);
            }
        }
    }
}

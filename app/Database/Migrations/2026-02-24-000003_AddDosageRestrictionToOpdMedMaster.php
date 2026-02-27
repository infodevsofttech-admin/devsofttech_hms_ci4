<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDosageRestrictionToOpdMedMaster extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('opd_med_master')) {
            return;
        }

        $fields = $this->db->getFieldNames('opd_med_master');
        $toAdd = [];

        if (! in_array('salt_name', $fields, true)
            && ! in_array('sal_name', $fields, true)
            && ! in_array('salt', $fields, true)
            && ! in_array('saltname', $fields, true)) {
            $toAdd['salt_name'] = [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ];
        }

        if (! in_array('dosage_restriction', $fields, true)
            && ! in_array('dose_restriction', $fields, true)
            && ! in_array('restriction_note', $fields, true)
            && ! in_array('restriction', $fields, true)) {
            $toAdd['dosage_restriction'] = [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ];
        }

        if (! empty($toAdd)) {
            $this->forge->addColumn('opd_med_master', $toAdd);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('opd_med_master')) {
            return;
        }

        $fields = $this->db->getFieldNames('opd_med_master');
        if (in_array('dosage_restriction', $fields, true)) {
            $this->forge->dropColumn('opd_med_master', 'dosage_restriction');
        }
        if (in_array('salt_name', $fields, true)) {
            $this->forge->dropColumn('opd_med_master', 'salt_name');
        }
    }
}

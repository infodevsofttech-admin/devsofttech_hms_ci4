<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOpdDayCareTemplateToDoctorMaster extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('doctor_master')) {
            $fields = [];
            if (! $this->db->fieldExists('opd_day_care_template', 'doctor_master')) {
                $fields['opd_day_care_template'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'opd_cont_paper_print',
                ];
            }

            if (! empty($fields)) {
                $this->forge->addColumn('doctor_master', $fields);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('doctor_master') && $this->db->fieldExists('opd_day_care_template', 'doctor_master')) {
            $this->forge->dropColumn('doctor_master', 'opd_day_care_template');
        }
    }
}

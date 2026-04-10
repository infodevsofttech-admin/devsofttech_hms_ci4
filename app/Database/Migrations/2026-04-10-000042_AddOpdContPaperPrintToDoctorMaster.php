<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOpdContPaperPrintToDoctorMaster extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('doctor_master')) {
            $fields = [
                'opd_cont_paper_print' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                    'after' => 'opd_blank_print',
                ],
            ];

            $this->forge->addColumn('doctor_master', $fields);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('doctor_master') && $this->db->fieldExists('opd_cont_paper_print', 'doctor_master')) {
            $this->forge->dropColumn('doctor_master', 'opd_cont_paper_print');
        }
    }
}

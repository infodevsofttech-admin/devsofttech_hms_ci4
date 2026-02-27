<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDoctorRegistrationNoToDoctorMaster extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('doctor_master')) {
            return;
        }

        if (! $this->db->fieldExists('doctor_reg_no', 'doctor_master')) {
            $this->forge->addColumn('doctor_master', [
                'doctor_reg_no' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => true,
                    'after' => 'mphone1',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->tableExists('doctor_master') && $this->db->fieldExists('doctor_reg_no', 'doctor_master')) {
            $this->forge->dropColumn('doctor_master', 'doctor_reg_no');
        }
    }
}

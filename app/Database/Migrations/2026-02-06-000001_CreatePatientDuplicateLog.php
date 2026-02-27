<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePatientDuplicateLog extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'new_uhid' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'name_of_person' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'new_patient_code' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'date_of_registration' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'update_by' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'remark_duplicate' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('patient_duplicate_log', true);
    }

    public function down()
    {
        $this->forge->dropTable('patient_duplicate_log', true);
    }
}

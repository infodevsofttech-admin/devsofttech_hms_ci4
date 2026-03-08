<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIpdNursingEntriesTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('ipd_nursing_entries')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'ipd_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'entry_type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'recorded_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'shift_name' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'temperature_c' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'pulse_rate' => [
                'type' => 'SMALLINT',
                'constraint' => 5,
                'null' => true,
            ],
            'resp_rate' => [
                'type' => 'SMALLINT',
                'constraint' => 5,
                'null' => true,
            ],
            'bp_systolic' => [
                'type' => 'SMALLINT',
                'constraint' => 5,
                'null' => true,
            ],
            'bp_diastolic' => [
                'type' => 'SMALLINT',
                'constraint' => 5,
                'null' => true,
            ],
            'spo2' => [
                'type' => 'SMALLINT',
                'constraint' => 5,
                'null' => true,
            ],
            'weight_kg' => [
                'type' => 'DECIMAL',
                'constraint' => '6,2',
                'null' => true,
            ],
            'fluid_direction' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
            ],
            'fluid_route' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'fluid_amount_ml' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'treatment_text' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'general_note' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'recorded_by' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'recorded_by_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('ipd_id');
        $this->forge->addKey('entry_type');
        $this->forge->addKey('recorded_at');
        $this->forge->createTable('ipd_nursing_entries', true);
    }

    public function down()
    {
        $this->forge->dropTable('ipd_nursing_entries', true);
    }
}

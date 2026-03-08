<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNursingBedsideItemsInsuranceTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('nursing_bedside_items_insurance')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'bedside_item_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'hc_insurance_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'amount1' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
                'default' => '0.00',
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'isdelete' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
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
        $this->forge->addKey('bedside_item_id');
        $this->forge->addKey('hc_insurance_id');
        $this->forge->addKey(['bedside_item_id', 'hc_insurance_id']);
        $this->forge->createTable('nursing_bedside_items_insurance', true);
    }

    public function down()
    {
        $this->forge->dropTable('nursing_bedside_items_insurance', true);
    }
}

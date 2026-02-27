<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocIpdFeeType extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'fee_type' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
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
        $this->forge->createTable('doc_ipd_fee_type', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('doc_ipd_fee_type', true);
    }
}

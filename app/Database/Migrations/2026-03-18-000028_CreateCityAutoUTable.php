<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCityAutoUTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'city' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'default'    => '',
            ],
            'district' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'default'    => '',
            ],
            'state' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => '',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['city', 'district', 'state']);

        $this->forge->createTable('city_auto_u', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('city_auto_u', true);
    }
}

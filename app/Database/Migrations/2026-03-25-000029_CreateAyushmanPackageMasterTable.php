<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAyushmanPackageMasterTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'speciality_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => '',
            ],
            'speciality_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => '',
            ],
            'procedure_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => '',
            ],
            'procedure_name' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'package_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0,
            ],
            'preauth_required' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'procedure_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => '',
            ],
            'government_reserved' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'pre_investigations' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'post_investigations' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'linked_package_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'source_file' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => '',
            ],
            'source_sheet' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'Sheet1',
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
        $this->forge->addUniqueKey('procedure_code');
        $this->forge->addKey('speciality_code');
        $this->forge->addKey('linked_package_id');
        $this->forge->addKey('preauth_required');
        $this->forge->createTable('ayushman_package_master', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('ayushman_package_master', true);
    }
}
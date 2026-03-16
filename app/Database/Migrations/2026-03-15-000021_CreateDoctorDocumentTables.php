<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDoctorDocumentTables extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('doc_format_master')) {
            $this->forge->addField([
                'df_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'doc_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => false,
                ],
                'doc_desc' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => false,
                    'default' => '',
                ],
                'doc_raw_format' => [
                    'type' => 'LONGTEXT',
                    'null' => false,
                ],
                'active' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
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
            $this->forge->addKey('df_id', true);
            $this->forge->addKey('active');
            $this->forge->createTable('doc_format_master', true);
        }

        if (! $this->db->tableExists('doc_format_sub')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'doc_format_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'input_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => false,
                ],
                'input_code' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => false,
                ],
                'input_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                    'default' => 'text',
                ],
                'input_default_value' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'short_order' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 1,
                ],
                'active' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
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
            $this->forge->addKey('doc_format_id');
            $this->forge->addKey(['doc_format_id', 'input_code']);
            $this->forge->createTable('doc_format_sub', true);
        }

        if (! $this->db->tableExists('patient_doc')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'p_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'doc_format_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'dr_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'date_issue' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'raw_data' => [
                    'type' => 'LONGTEXT',
                    'null' => false,
                ],
                'update_pre_value' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
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
            $this->forge->addKey('p_id');
            $this->forge->addKey('doc_format_id');
            $this->forge->addKey('dr_id');
            $this->forge->createTable('patient_doc', true);
        }

        if (! $this->db->tableExists('patient_doc_raw')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'p_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'p_doc_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'p_doc_sub_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'p_doc_raw_value' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'update_data' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
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
            $this->forge->addKey('p_doc_id');
            $this->forge->addKey('p_doc_sub_id');
            $this->forge->addKey('p_id');
            $this->forge->createTable('patient_doc_raw', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('patient_doc_raw', true);
        $this->forge->dropTable('patient_doc', true);
        $this->forge->dropTable('doc_format_sub', true);
        $this->forge->dropTable('doc_format_master', true);
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInvestigationProfileTables extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('invprofiles')) {
            $this->forge->addField([
                'Code' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => false,
                    'auto_increment' => true,
                ],
                'Name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => false,
                ],
            ]);
            $this->forge->addKey('Code', true);
            $this->forge->addUniqueKey('Name');
            $this->forge->createTable('invprofiles', true);
        }

        if (! $this->db->tableExists('invtprofiles')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'auto_increment' => true,
                ],
                'ProfileCode' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                ],
                'InvestigationCode' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                ],
                'printOrder' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['ProfileCode', 'InvestigationCode']);
            $this->forge->createTable('invtprofiles', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('invtprofiles')) {
            $this->forge->dropTable('invtprofiles', true);
        }
        if ($this->db->tableExists('invprofiles')) {
            $this->forge->dropTable('invprofiles', true);
        }
    }
}

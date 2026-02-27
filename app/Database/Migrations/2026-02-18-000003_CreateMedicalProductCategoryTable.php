<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMedicalProductCategoryTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('med_product_cat_master')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'med_cat_desc' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('med_product_cat_master', true);
    }

    public function down()
    {
        if ($this->db->tableExists('med_product_cat_master')) {
            $this->forge->dropTable('med_product_cat_master', true);
        }
    }
}

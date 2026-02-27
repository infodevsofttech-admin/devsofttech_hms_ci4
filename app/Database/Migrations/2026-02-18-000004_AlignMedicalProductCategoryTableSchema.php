<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlignMedicalProductCategoryTableSchema extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('med_product_cat_master')) {
            return;
        }

        if (! $this->db->fieldExists('id', 'med_product_cat_master')) {
            $this->forge->addColumn('med_product_cat_master', [
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                    'auto_increment' => true,
                ],
            ]);
        }

        if (! $this->db->fieldExists('med_cat_desc', 'med_product_cat_master')) {
            $this->forge->addColumn('med_product_cat_master', [
                'med_cat_desc' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => false,
                ],
            ]);
        }

        $this->db->query("ALTER TABLE `med_product_cat_master` MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT");
        $this->db->query("ALTER TABLE `med_product_cat_master` MODIFY `med_cat_desc` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL");

        $hasPrimary = false;
        $indexRows = $this->db->query("SHOW INDEX FROM `med_product_cat_master`")->getResultArray();
        foreach ($indexRows as $indexRow) {
            if (($indexRow['Key_name'] ?? '') === 'PRIMARY') {
                $hasPrimary = true;
                break;
            }
        }
        if (! $hasPrimary) {
            $this->db->query("ALTER TABLE `med_product_cat_master` ADD PRIMARY KEY (`id`)");
        }

        $this->db->query("ALTER TABLE `med_product_cat_master` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $this->db->query("ALTER TABLE `med_product_cat_master` ENGINE=MyISAM");
    }

    public function down()
    {
        // No-op to avoid destructive schema rollback for existing production tables.
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMedShortFormulationTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('med_short_formulation')) {
            return;
        }

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `med_short_formulation` (
                `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `short_formulation` VARCHAR(100) NOT NULL,
                `gateway_identifier` VARCHAR(100) NULL DEFAULT NULL,
                `last_synced_at`    DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_short_formulation` (`short_formulation`),
                KEY `idx_gateway_identifier` (`gateway_identifier`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(): void
    {
        if ($this->db->tableExists('med_short_formulation')) {
            $this->forge->dropTable('med_short_formulation', true);
        }
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHmsGatewayCompanyMasterTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('hms_gateway_company_master')) {
            return;
        }

        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `hms_gateway_company_master` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `gateway_company_identifier` VARCHAR(100) NOT NULL,
                `company_name` VARCHAR(255) NOT NULL,
                `gateway_updated_at` DATETIME NULL DEFAULT NULL,
                `first_synced_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `last_synced_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `sync_status` ENUM('active','deleted','error') NOT NULL DEFAULT 'active',
                `name_normalized` VARCHAR(255) NULL DEFAULT NULL,
                `payload_hash` CHAR(64) NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_gateway_company_identifier` (`gateway_company_identifier`),
                KEY `idx_company_name` (`company_name`),
                KEY `idx_name_normalized` (`name_normalized`),
                KEY `idx_gateway_updated_at` (`gateway_updated_at`),
                KEY `idx_last_synced_at` (`last_synced_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(): void
    {
        if ($this->db->tableExists('hms_gateway_company_master')) {
            $this->forge->dropTable('hms_gateway_company_master', true);
        }
    }
}

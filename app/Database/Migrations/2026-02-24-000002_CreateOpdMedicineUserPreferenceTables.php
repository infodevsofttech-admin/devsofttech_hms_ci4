<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOpdMedicineUserPreferenceTables extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('opd_medicine_usage')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type' => 'INT',
                    'unsigned' => true,
                ],
                'med_id' => [
                    'type' => 'INT',
                    'unsigned' => true,
                ],
                'use_count' => [
                    'type' => 'INT',
                    'unsigned' => true,
                    'default' => 0,
                ],
                'last_used_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['user_id', 'med_id'], 'uniq_user_med_usage');
            $this->forge->addKey(['user_id', 'last_used_at'], false, false, 'idx_user_last_used');
            $this->forge->createTable('opd_medicine_usage', true);
        }

        if (! $this->db->tableExists('opd_medicine_favorites')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'BIGINT',
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type' => 'INT',
                    'unsigned' => true,
                ],
                'med_id' => [
                    'type' => 'INT',
                    'unsigned' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['user_id', 'med_id'], 'uniq_user_med_fav');
            $this->forge->addKey(['user_id', 'created_at'], false, false, 'idx_user_fav_created');
            $this->forge->createTable('opd_medicine_favorites', true);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('opd_medicine_favorites', true);
        $this->forge->dropTable('opd_medicine_usage', true);
    }
}

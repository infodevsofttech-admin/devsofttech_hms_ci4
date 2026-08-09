<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UseInnoDbForUserActiveSessions extends Migration
{
    public function up()
    {
        $table = $this->db->query("SHOW TABLES LIKE 'user_active_sessions'")->getRowArray();
        if ($table !== null && $this->db->DBDriver === 'MySQLi') {
            $this->db->query('ALTER TABLE user_active_sessions ENGINE=InnoDB');
        }
    }

    public function down()
    {
    }
}
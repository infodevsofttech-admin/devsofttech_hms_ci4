<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeOpdAppointmentDateToDATETIME extends Migration
{
    public function up()
    {
        // Change the apointment_date column from DATE to DATETIME to store both date and time
        $this->db->disableForeignKeyChecks();
        
        $this->forge->modifyColumn('opd_master', [
            'apointment_date' => [
                'type'           => 'DATETIME',
                'null'           => true,
                'default'        => null,
                'comment'        => 'Appointment date and time'
            ]
        ]);
        
        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        // Revert the column back to DATE type
        $this->db->disableForeignKeyChecks();
        
        $this->forge->modifyColumn('opd_master', [
            'apointment_date' => [
                'type'           => 'DATE',
                'null'           => true,
                'default'        => null,
                'comment'        => 'Appointment date'
            ]
        ]);
        
        $this->db->enableForeignKeyChecks();
    }
}

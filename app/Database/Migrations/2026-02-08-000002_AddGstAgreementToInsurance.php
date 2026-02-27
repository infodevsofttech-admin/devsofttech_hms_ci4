<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGstAgreementToInsurance extends Migration
{
    public function up(): void
    {
        $fields = [
            'gst_no' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'agreement_start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'agreement_end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
        ];

        $this->forge->addColumn('hc_insurance', $fields);
    }

    public function down(): void
    {
        $this->forge->dropColumn('hc_insurance', ['gst_no', 'agreement_start_date', 'agreement_end_date']);
    }
}

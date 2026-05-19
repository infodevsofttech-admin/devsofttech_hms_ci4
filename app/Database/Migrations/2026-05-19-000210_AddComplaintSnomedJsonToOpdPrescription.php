<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddComplaintSnomedJsonToOpdPrescription extends Migration
{
    public function up(): void
    {
        $fields = $this->db->getFieldNames('opd_prescription') ?? [];

        if (! in_array('complaint_snomed_json', $fields, true)) {
            $this->forge->addColumn('opd_prescription', [
                'complaint_snomed_json' => [
                    'type'    => 'TEXT',
                    'null'    => true,
                    'default' => null,
                ],
            ]);
        }
    }

    public function down(): void
    {
        $fields = $this->db->getFieldNames('opd_prescription') ?? [];
        if (in_array('complaint_snomed_json', $fields, true)) {
            $this->forge->dropColumn('opd_prescription', 'complaint_snomed_json');
        }
    }
}

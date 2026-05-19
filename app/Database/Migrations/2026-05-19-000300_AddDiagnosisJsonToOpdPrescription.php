<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDiagnosisJsonToOpdPrescription extends Migration
{
    public function up(): void
    {
        $fields = [
            'diagnosis_json' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'diagnosis',
            ],
        ];

        if ($this->db->fieldExists('diagnosis_json', 'opd_prescription')) {
            return;
        }

        $this->forge->addColumn('opd_prescription', $fields);
    }

    public function down(): void
    {
        if ($this->db->fieldExists('diagnosis_json', 'opd_prescription')) {
            $this->forge->dropColumn('opd_prescription', 'diagnosis_json');
        }
    }
}

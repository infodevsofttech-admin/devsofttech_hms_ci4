<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGstAgreementToInsurance extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('hc_insurance')) {
            return;
        }

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

        $addFields = [];
        foreach ($fields as $name => $definition) {
            if (! $this->db->fieldExists($name, 'hc_insurance')) {
                $addFields[$name] = $definition;
            }
        }

        if (! empty($addFields)) {
            $this->forge->addColumn('hc_insurance', $addFields);
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('hc_insurance')) {
            return;
        }

        foreach (['gst_no', 'agreement_start_date', 'agreement_end_date'] as $column) {
            if ($this->db->fieldExists($column, 'hc_insurance')) {
                $this->forge->dropColumn('hc_insurance', $column);
            }
        }
    }
}

<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDiagnosisHeadMpdfBlocks extends Migration
{
    private function hasDiagnosisHeadTable(): bool
    {
        $query = $this->db->query("SHOW TABLES LIKE 'diagnosis_head_name'");
        return $query->getNumRows() > 0;
    }

    private function diagnosisHeadFields(): array
    {
        $fields = [];
        $query = $this->db->query("SHOW COLUMNS FROM diagnosis_head_name");
        foreach ($query->getResultArray() as $row) {
            $fields[] = (string) ($row['Field'] ?? '');
        }

        return $fields;
    }

    public function up()
    {
        if (! $this->hasDiagnosisHeadTable()) {
            return;
        }

        $fields = $this->diagnosisHeadFields();

        if (! in_array('mpdf_prefix_html', $fields, true)) {
            $this->forge->addColumn('diagnosis_head_name', [
                'mpdf_prefix_html' => [
                    'type' => 'longtext',
                    'null' => true,
                    'after' => 'plain_background_image',
                ],
            ]);
        }

        if (! in_array('mpdf_suffix_html', $fields, true)) {
            $this->forge->addColumn('diagnosis_head_name', [
                'mpdf_suffix_html' => [
                    'type' => 'longtext',
                    'null' => true,
                    'after' => 'mpdf_prefix_html',
                ],
            ]);
        }
    }

    public function down()
    {
        if (! $this->hasDiagnosisHeadTable()) {
            return;
        }

        $fields = $this->diagnosisHeadFields();

        if (in_array('mpdf_suffix_html', $fields, true)) {
            $this->forge->dropColumn('diagnosis_head_name', 'mpdf_suffix_html');
        }

        if (in_array('mpdf_prefix_html', $fields, true)) {
            $this->forge->dropColumn('diagnosis_head_name', 'mpdf_prefix_html');
        }
    }
}

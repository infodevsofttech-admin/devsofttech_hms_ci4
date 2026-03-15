<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDiagnosisHeadPrintSettings extends Migration
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

        if (! in_array('letter_margin_top', $fields, true)) {
            $this->forge->addColumn('diagnosis_head_name', [
                'letter_margin_top' => [
                    'type' => 'decimal',
                    'constraint' => '6,2',
                    'default' => '12.00',
                    'null' => true,
                    'after' => 'print_page_direct',
                ],
            ]);
        }

        if (! in_array('letter_margin_left', $fields, true)) {
            $this->forge->addColumn('diagnosis_head_name', [
                'letter_margin_left' => [
                    'type' => 'decimal',
                    'constraint' => '6,2',
                    'default' => '10.00',
                    'null' => true,
                    'after' => 'letter_margin_top',
                ],
            ]);
        }

        if (! in_array('letter_margin_right', $fields, true)) {
            $this->forge->addColumn('diagnosis_head_name', [
                'letter_margin_right' => [
                    'type' => 'decimal',
                    'constraint' => '6,2',
                    'default' => '10.00',
                    'null' => true,
                    'after' => 'letter_margin_left',
                ],
            ]);
        }

        if (! in_array('letter_margin_bottom', $fields, true)) {
            $this->forge->addColumn('diagnosis_head_name', [
                'letter_margin_bottom' => [
                    'type' => 'decimal',
                    'constraint' => '6,2',
                    'default' => '12.00',
                    'null' => true,
                    'after' => 'letter_margin_right',
                ],
            ]);
        }

        if (! in_array('plain_header_html', $fields, true)) {
            $this->forge->addColumn('diagnosis_head_name', [
                'plain_header_html' => [
                    'type' => 'longtext',
                    'null' => true,
                    'after' => 'letter_margin_bottom',
                ],
            ]);
        }

        if (! in_array('plain_background_image', $fields, true)) {
            $this->forge->addColumn('diagnosis_head_name', [
                'plain_background_image' => [
                    'type' => 'varchar',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'plain_header_html',
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

        if (in_array('plain_background_image', $fields, true)) {
            $this->forge->dropColumn('diagnosis_head_name', 'plain_background_image');
        }
        if (in_array('plain_header_html', $fields, true)) {
            $this->forge->dropColumn('diagnosis_head_name', 'plain_header_html');
        }
        if (in_array('letter_margin_bottom', $fields, true)) {
            $this->forge->dropColumn('diagnosis_head_name', 'letter_margin_bottom');
        }
        if (in_array('letter_margin_right', $fields, true)) {
            $this->forge->dropColumn('diagnosis_head_name', 'letter_margin_right');
        }
        if (in_array('letter_margin_left', $fields, true)) {
            $this->forge->dropColumn('diagnosis_head_name', 'letter_margin_left');
        }
        if (in_array('letter_margin_top', $fields, true)) {
            $this->forge->dropColumn('diagnosis_head_name', 'letter_margin_top');
        }
    }
}

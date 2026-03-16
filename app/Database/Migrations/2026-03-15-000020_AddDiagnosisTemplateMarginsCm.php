<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDiagnosisTemplateMarginsCm extends Migration
{
    private function hasTemplateTable(): bool
    {
        $query = $this->db->query("SHOW TABLES LIKE 'diagnosis_print_templates'");
        return $query->getNumRows() > 0;
    }

    private function templateFields(): array
    {
        $fields = [];
        $query = $this->db->query("SHOW COLUMNS FROM diagnosis_print_templates");
        foreach ($query->getResultArray() as $row) {
            $fields[] = (string) ($row['Field'] ?? '');
        }

        return $fields;
    }

    public function up()
    {
        if (! $this->hasTemplateTable()) {
            return;
        }

        $fields = $this->templateFields();

        $add = [];
        if (! in_array('page_margin_top_cm', $fields, true)) {
            $add['page_margin_top_cm'] = [
                'type' => 'decimal',
                'constraint' => '6,2',
                'null' => true,
                'default' => '6.10',
                'after' => 'page_size',
            ];
        }
        if (! in_array('page_margin_bottom_cm', $fields, true)) {
            $add['page_margin_bottom_cm'] = [
                'type' => 'decimal',
                'constraint' => '6,2',
                'null' => true,
                'default' => '2.50',
                'after' => 'page_margin_top_cm',
            ];
        }
        if (! in_array('page_margin_left_cm', $fields, true)) {
            $add['page_margin_left_cm'] = [
                'type' => 'decimal',
                'constraint' => '6,2',
                'null' => true,
                'default' => '0.70',
                'after' => 'page_margin_bottom_cm',
            ];
        }
        if (! in_array('page_margin_right_cm', $fields, true)) {
            $add['page_margin_right_cm'] = [
                'type' => 'decimal',
                'constraint' => '6,2',
                'null' => true,
                'default' => '0.70',
                'after' => 'page_margin_left_cm',
            ];
        }
        if (! in_array('margin_header_cm', $fields, true)) {
            $add['margin_header_cm'] = [
                'type' => 'decimal',
                'constraint' => '6,2',
                'null' => true,
                'default' => '0.50',
                'after' => 'page_margin_right_cm',
            ];
        }
        if (! in_array('margin_footer_cm', $fields, true)) {
            $add['margin_footer_cm'] = [
                'type' => 'decimal',
                'constraint' => '6,2',
                'null' => true,
                'default' => '1.50',
                'after' => 'margin_header_cm',
            ];
        }

        if (! empty($add)) {
            $this->forge->addColumn('diagnosis_print_templates', $add);
        }
    }

    public function down()
    {
        if (! $this->hasTemplateTable()) {
            return;
        }

        $fields = $this->templateFields();
        $dropList = [
            'page_margin_top_cm',
            'page_margin_bottom_cm',
            'page_margin_left_cm',
            'page_margin_right_cm',
            'margin_header_cm',
            'margin_footer_cm',
        ];

        foreach ($dropList as $field) {
            if (in_array($field, $fields, true)) {
                $this->forge->dropColumn('diagnosis_print_templates', $field);
            }
        }
    }
}

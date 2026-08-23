<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIpdGeneralExamFieldControls extends Migration
{
    public function up()
    {
        if (! $this->tableExists('ipd_discharge_general_exam_col')) {
            return;
        }
        if (! $this->fieldExists('is_active', 'ipd_discharge_general_exam_col')) {
            $this->db->query('ALTER TABLE ipd_discharge_general_exam_col ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER cat_group');
        }
        if (! $this->fieldExists('display_order', 'ipd_discharge_general_exam_col')) {
            $this->db->query('ALTER TABLE ipd_discharge_general_exam_col ADD COLUMN display_order INT NOT NULL DEFAULT 0 AFTER is_active');
            $this->db->query('UPDATE ipd_discharge_general_exam_col SET display_order = id WHERE display_order = 0');
        }
    }

    public function down()
    {
        if ($this->tableExists('ipd_discharge_general_exam_col') && $this->fieldExists('display_order', 'ipd_discharge_general_exam_col')) {
            $this->db->query('ALTER TABLE ipd_discharge_general_exam_col DROP COLUMN display_order');
        }
        if ($this->tableExists('ipd_discharge_general_exam_col') && $this->fieldExists('is_active', 'ipd_discharge_general_exam_col')) {
            $this->db->query('ALTER TABLE ipd_discharge_general_exam_col DROP COLUMN is_active');
        }
    }

    private function tableExists(string $table): bool
    {
        return $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getRowArray() !== null;
    }

    private function fieldExists(string $field, string $table): bool
    {
        return $this->db->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $this->db->escape($field))->getRowArray() !== null;
    }
}
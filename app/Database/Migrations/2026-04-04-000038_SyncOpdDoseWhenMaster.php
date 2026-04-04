<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SyncOpdDoseWhenMaster extends Migration
{
    public function up()
    {
        if (! $this->tableExists('opd_dose_when')) {
            return;
        }

        $this->db->table('opd_dose_when')->truncate();

        $rows = [
            [
                'dose_when_id' => 1,
                'dose_sign' => 'BF',
                'dose_sign_desc' => 'Before Food',
                'dose_sign_hindi' => 'भोजन से पहले',
            ],
            [
                'dose_when_id' => 2,
                'dose_sign' => 'AF',
                'dose_sign_desc' => 'After Food',
                'dose_sign_hindi' => 'भोजन के बाद',
            ],
            [
                'dose_when_id' => 3,
                'dose_sign' => 'BB',
                'dose_sign_desc' => 'Before Breakfast',
                'dose_sign_hindi' => 'नाश्ते से पहले',
            ],
            [
                'dose_when_id' => 4,
                'dose_sign' => 'AB',
                'dose_sign_desc' => 'After Breakfast',
                'dose_sign_hindi' => 'नाश्ते के बाद',
            ],
            [
                'dose_when_id' => 5,
                'dose_sign' => 'BL',
                'dose_sign_desc' => 'Before Lunch',
                'dose_sign_hindi' => 'दोपहर के भोजन से पहले',
            ],
            [
                'dose_when_id' => 6,
                'dose_sign' => 'AL',
                'dose_sign_desc' => 'After Lunch',
                'dose_sign_hindi' => 'दोपहर के भोजन के बाद',
            ],
            [
                'dose_when_id' => 7,
                'dose_sign' => 'BD',
                'dose_sign_desc' => 'Before Dinner',
                'dose_sign_hindi' => 'रात के खाने से पहले',
            ],
            [
                'dose_when_id' => 8,
                'dose_sign' => 'AD',
                'dose_sign_desc' => 'After Dinner',
                'dose_sign_hindi' => 'रात के खाने के बाद',
            ],
            [
                'dose_when_id' => 9,
                'dose_sign' => 'WF',
                'dose_sign_desc' => 'With Food',
                'dose_sign_hindi' => 'खाने के साथ',
            ],
            [
                'dose_when_id' => 10,
                'dose_sign' => 'WT',
                'dose_sign_desc' => 'With Tea',
                'dose_sign_hindi' => 'चाय के साथ',
            ],
            [
                'dose_when_id' => 11,
                'dose_sign' => 'ES',
                'dose_sign_desc' => 'Empty Stomach',
                'dose_sign_hindi' => 'खाली पेट',
            ],
            [
                'dose_when_id' => 12,
                'dose_sign' => 'BT',
                'dose_sign_desc' => 'Bed Time',
                'dose_sign_hindi' => 'सोने का समय',
            ],
            [
                'dose_when_id' => 13,
                'dose_sign' => 'BBATH',
                'dose_sign_desc' => 'Before Bath',
                'dose_sign_hindi' => 'स्नान से पहले',
            ],
            [
                'dose_when_id' => 14,
                'dose_sign' => 'ABATH',
                'dose_sign_desc' => 'After Bath',
                'dose_sign_hindi' => 'नहाने के बाद',
            ],
            [
                'dose_when_id' => 15,
                'dose_sign' => 'BS',
                'dose_sign_desc' => 'Before Sun',
                'dose_sign_hindi' => 'सूर्य से पहले',
            ],
            [
                'dose_when_id' => 16,
                'dose_sign' => 'AS',
                'dose_sign_desc' => 'After Sun',
                'dose_sign_hindi' => 'दोपहर के बाद',
            ],
            [
                'dose_when_id' => 17,
                'dose_sign' => 'SoS',
                'dose_sign_desc' => 'SoS',
                'dose_sign_hindi' => 'आपात स्थिति में',
            ],
        ];

        $this->db->table('opd_dose_when')->insertBatch($rows);
        $this->resetAutoIncrement('opd_dose_when', 18);
    }

    public function down()
    {
        // No-op: prior dose_when master rows vary by installation.
    }

    private function tableExists(string $table): bool
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }

        $row = $this->db->query("SHOW TABLES LIKE '" . $table . "'")->getRowArray();
        return ! empty($row);
    }

    private function resetAutoIncrement(string $table, int $nextId): void
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return;
        }

        $nextId = max(1, $nextId);
        $this->db->query('ALTER TABLE `' . $table . '` AUTO_INCREMENT = ' . $nextId);
    }
}

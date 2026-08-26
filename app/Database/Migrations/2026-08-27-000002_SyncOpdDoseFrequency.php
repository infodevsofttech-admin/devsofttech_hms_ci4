<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SyncOpdDoseFrequency extends Migration
{
    public function up()
    {
        if (! $this->tableExists('opd_dose_frequency')) {
            return;
        }

        // Delete old records & reset ID counter
        $this->db->table('opd_dose_frequency')->truncate();

        $frequencies = [
            [
                'dose_freq_id'    => 1,
                'dose_sign'       => 'OD',
                'dose_sign_desc'  => 'OD (Once Daily) – 1 dose per day',
                'dose_sign_hindi' => 'दिन में एक बार (OD)',
            ],
            [
                'dose_freq_id'    => 2,
                'dose_sign'       => 'BD',
                'dose_sign_desc'  => 'BD (Twice Daily) – 2 doses per day',
                'dose_sign_hindi' => 'दिन में दो बार (BD)',
            ],
            [
                'dose_freq_id'    => 3,
                'dose_sign'       => 'TDS',
                'dose_sign_desc'  => 'TDS / TID (Thrice Daily) – 3 doses per day',
                'dose_sign_hindi' => 'दिन में तीन बार (TDS)',
            ],
            [
                'dose_freq_id'    => 4,
                'dose_sign'       => 'QID',
                'dose_sign_desc'  => 'QID (Four Times Daily) – 4 doses per day',
                'dose_sign_hindi' => 'दिन में चार बार (QID)',
            ],
            [
                'dose_freq_id'    => 5,
                'dose_sign'       => 'HS',
                'dose_sign_desc'  => 'HS (At Bedtime) – once at night',
                'dose_sign_hindi' => 'रात को सोते समय (HS)',
            ],
            [
                'dose_freq_id'    => 6,
                'dose_sign'       => 'SOS',
                'dose_sign_desc'  => 'SOS (As Needed) – only when required',
                'dose_sign_hindi' => 'आवश्यकतानुसार (SOS)',
            ],
            [
                'dose_freq_id'    => 7,
                'dose_sign'       => 'Q4H',
                'dose_sign_desc'  => 'Q4H – every 4 hours',
                'dose_sign_hindi' => 'हर 4 घंटे में (Q4H)',
            ],
            [
                'dose_freq_id'    => 8,
                'dose_sign'       => 'Q6H',
                'dose_sign_desc'  => 'Q6H – every 6 hours',
                'dose_sign_hindi' => 'हर 6 घंटे में (Q6H)',
            ],
            [
                'dose_freq_id'    => 9,
                'dose_sign'       => 'Q8H',
                'dose_sign_desc'  => 'Q8H – every 8 hours',
                'dose_sign_hindi' => 'हर 8 घंटे में (Q8H)',
            ],
            [
                'dose_freq_id'    => 10,
                'dose_sign'       => 'Alternate Day',
                'dose_sign_desc'  => 'Alternate Day – once every 2 days',
                'dose_sign_hindi' => 'एक दिन छोड़कर (Alternate Day)',
            ],
            [
                'dose_freq_id'    => 11,
                'dose_sign'       => 'Weekly',
                'dose_sign_desc'  => 'Weekly – long-interval medication',
                'dose_sign_hindi' => 'हफ्ते में एक बार (Weekly)',
            ],
            [
                'dose_freq_id'    => 12,
                'dose_sign'       => 'Monthly',
                'dose_sign_desc'  => 'Monthly – long-interval medication',
                'dose_sign_hindi' => 'महीने में एक बार (Monthly)',
            ],
            [
                'dose_freq_id'    => 13,
                'dose_sign'       => 'Continuous Infusion',
                'dose_sign_desc'  => 'Continuous Infusion – IV drip maintained continuously',
                'dose_sign_hindi' => 'लगातार ड्रिप (Continuous Infusion)',
            ],
            [
                'dose_freq_id'    => 14,
                'dose_sign'       => 'Stat',
                'dose_sign_desc'  => 'Stat – immediate single dose',
                'dose_sign_hindi' => 'तुरंत एक खुराक (Stat)',
            ],
        ];

        $this->db->table('opd_dose_frequency')->insertBatch($frequencies);
        $this->resetAutoIncrement('opd_dose_frequency', 15);
    }

    public function down()
    {
        // Intentionally left as no-op to avoid data loss on rollback.
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

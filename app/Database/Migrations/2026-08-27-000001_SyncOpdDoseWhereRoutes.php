<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SyncOpdDoseWhereRoutes extends Migration
{
    public function up()
    {
        if (! $this->tableExists('opd_dose_where')) {
            return;
        }

        // Delete old records & reset ID counter
        $this->db->table('opd_dose_where')->truncate();

        $routes = [
            [
                'dose_where_id'   => 1,
                'dose_sign'        => 'Oral (PO)',
                'dose_sign_desc'   => 'Oral (PO) – Tablets, capsules, liquids taken by mouth',
                'dose_sign_hindi'  => 'मुँह द्वारा (Oral)',
            ],
            [
                'dose_where_id'   => 2,
                'dose_sign'        => 'Sublingual (SL)',
                'dose_sign_desc'   => 'Sublingual (SL) – Placed under the tongue',
                'dose_sign_hindi'  => 'जीभ के नीचे (Sublingual)',
            ],
            [
                'dose_where_id'   => 3,
                'dose_sign'        => 'Buccal',
                'dose_sign_desc'   => 'Buccal – Placed between gum and cheek',
                'dose_sign_hindi'  => 'गाल और मसूड़े के बीच (Buccal)',
            ],
            [
                'dose_where_id'   => 4,
                'dose_sign'        => 'Intravenous (IV)',
                'dose_sign_desc'   => 'Intravenous (IV) – Directly into a vein',
                'dose_sign_hindi'  => 'नस में (IV)',
            ],
            [
                'dose_where_id'   => 5,
                'dose_sign'        => 'Intramuscular (IM)',
                'dose_sign_desc'   => 'Intramuscular (IM) – Into a muscle',
                'dose_sign_hindi'  => 'मांसपेशी में (IM)',
            ],
            [
                'dose_where_id'   => 6,
                'dose_sign'        => 'Subcutaneous (SC)',
                'dose_sign_desc'   => 'Subcutaneous (SC) – Under the skin',
                'dose_sign_hindi'  => 'त्वचा के नीचे (SC)',
            ],
            [
                'dose_where_id'   => 7,
                'dose_sign'        => 'Intradermal (ID)',
                'dose_sign_desc'   => 'Intradermal (ID) – Into the skin (dermis layer)',
                'dose_sign_hindi'  => 'त्वचा में (ID)',
            ],
            [
                'dose_where_id'   => 8,
                'dose_sign'        => 'Topical',
                'dose_sign_desc'   => 'Topical – Applied to skin surface (cream, ointment, gel)',
                'dose_sign_hindi'  => 'त्वचा की सतह पर (Topical)',
            ],
            [
                'dose_where_id'   => 9,
                'dose_sign'        => 'Transdermal',
                'dose_sign_desc'   => 'Transdermal – Patch applied to skin for systemic absorption',
                'dose_sign_hindi'  => 'ट्रांसडर्मल पैच (Transdermal)',
            ],
            [
                'dose_where_id'   => 10,
                'dose_sign'        => 'Inhalation',
                'dose_sign_desc'   => 'Inhalation – Via nebulizer, inhaler, or mask',
                'dose_sign_hindi'  => 'सांस द्वारा (Inhalation)',
            ],
            [
                'dose_where_id'   => 11,
                'dose_sign'        => 'Intranasal',
                'dose_sign_desc'   => 'Intranasal – Through the nose (spray, drops)',
                'dose_sign_hindi'  => 'नाक के द्वारा (Intranasal)',
            ],
            [
                'dose_where_id'   => 12,
                'dose_sign'        => 'Ophthalmic',
                'dose_sign_desc'   => 'Ophthalmic – Eye drops or ointments',
                'dose_sign_hindi'  => 'आंख में (Ophthalmic)',
            ],
            [
                'dose_where_id'   => 13,
                'dose_sign'        => 'Otic',
                'dose_sign_desc'   => 'Otic – Ear drops',
                'dose_sign_hindi'  => 'कान में (Otic)',
            ],
            [
                'dose_where_id'   => 14,
                'dose_sign'        => 'Rectal',
                'dose_sign_desc'   => 'Rectal – Suppositories, enemas',
                'dose_sign_hindi'  => 'गुदा मार्ग द्वारा (Rectal)',
            ],
            [
                'dose_where_id'   => 15,
                'dose_sign'        => 'Vaginal',
                'dose_sign_desc'   => 'Vaginal – Tablets, creams, pessaries',
                'dose_sign_hindi'  => 'योनि मार्ग द्वारा (Vaginal)',
            ],
            [
                'dose_where_id'   => 16,
                'dose_sign'        => 'Intrathecal',
                'dose_sign_desc'   => 'Intrathecal – Into spinal canal (CSF)',
                'dose_sign_hindi'  => 'रीढ़ की हड्डी के द्रव में (Intrathecal)',
            ],
            [
                'dose_where_id'   => 17,
                'dose_sign'        => 'Epidural',
                'dose_sign_desc'   => 'Epidural – Into epidural space around spinal cord',
                'dose_sign_hindi'  => 'एपिड्यूरल स्थान में (Epidural)',
            ],
        ];

        $this->db->table('opd_dose_where')->insertBatch($routes);
        $this->resetAutoIncrement('opd_dose_where', 18);
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

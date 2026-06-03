<?php

namespace App\Database\Migrations;

use App\Libraries\FhirEncryptionService;
use CodeIgniter\Database\Migration;

class AddRecordDataToHealthRecords extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('health_records')) {
            return;
        }

        $fields = $this->db->getFieldNames('health_records');
        if (! in_array('record_data', $fields, true)) {
            $this->forge->addColumn('health_records', [
                'record_data' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                    'after' => 'fhir_bundle_enc',
                ],
            ]);
        }

        $fields = $this->db->getFieldNames('health_records');
        if (! in_array('fhir_bundle_enc', $fields, true) || ! in_array('record_data', $fields, true)) {
            return;
        }

        if (! FhirEncryptionService::isSupported()) {
            return;
        }

        try {
            $enc = new FhirEncryptionService();
            $rows = $this->db->table('health_records')
                ->select('id, fhir_bundle_enc, record_data')
                ->where('record_data IS NULL', null, false)
                ->orWhere('record_data', '')
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $encoded = trim((string) ($row['fhir_bundle_enc'] ?? ''));
                if ($encoded === '') {
                    continue;
                }

                try {
                    $plaintext = $enc->decrypt($encoded);
                } catch (\Throwable) {
                    continue;
                }

                if ($plaintext === '') {
                    continue;
                }

                $this->db->table('health_records')
                    ->where('id', (int) ($row['id'] ?? 0))
                    ->update(['record_data' => $plaintext]);
            }
        } catch (\Throwable) {
            // Fail-open: the new column is the important part; backfill is best-effort.
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('health_records')) {
            return;
        }

        $fields = $this->db->getFieldNames('health_records');
        if (in_array('record_data', $fields, true)) {
            $this->forge->dropColumn('health_records', 'record_data');
        }
    }
}
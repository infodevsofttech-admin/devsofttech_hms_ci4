<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tracks every distinct ABDM consent ARTIFACT id discovered under a consent
 * request. Needed because a multi-facility M3 consent produces one artifact
 * per linked HIP facility, and the bridge's merged "fetch by consent_request_id"
 * call does not reliably keep returning sessions for artifacts that were
 * already individually resolved earlier (verified 2026-07-28: a previously
 * "received" artifact stopped appearing in the merged sessions list, while
 * still returning real data when queried directly by its own artifact id).
 * So HMS must remember every artifact id it has ever seen for a consent
 * request and keep polling each one individually, in addition to the merged
 * request-id call (which is what discovers brand-new sibling artifacts).
 */
class CreateAbdmHiuConsentArtifacts extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'consent_request_id' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
            ],
            'artifact_id' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
            ],
            'abha_address' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'hfr_id' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
            ],
            'last_status' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
            'last_polled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['consent_request_id', 'artifact_id'], 'uniq_request_artifact');
        $this->forge->addKey('abha_address');
        $this->forge->createTable('abdm_hiu_consent_artifacts', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('abdm_hiu_consent_artifacts', true);
    }
}

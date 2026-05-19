<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds a FULLTEXT index on snomed_description.term for fast in-DB SNOMED search.
 * This replaces the need to call the external csnotk.e-atria.in API for every search.
 * Run: php spark migrate
 */
class AddSnomedFulltextIndex extends Migration
{
    public function up(): void
    {
        if (! $this->hasTable('snomed_description')) {
            return;
        }

        // Check if FULLTEXT index already exists
        $existing = $this->db->query(
            "SHOW INDEX FROM snomed_description WHERE Index_type = 'FULLTEXT' AND Column_name = 'term'"
        )->getResultArray();

        if (! empty($existing)) {
            return; // Already present
        }

        // Add FULLTEXT index — runs as online DDL (no table lock on InnoDB MySQL 5.6+)
        $this->db->query('ALTER TABLE snomed_description ADD FULLTEXT INDEX ft_term (term)');
    }

    public function down(): void
    {
        if (! $this->hasTable('snomed_description')) {
            return;
        }

        $existing = $this->db->query(
            "SHOW INDEX FROM snomed_description WHERE Key_name = 'ft_term'"
        )->getResultArray();

        if (! empty($existing)) {
            $this->db->query('ALTER TABLE snomed_description DROP INDEX ft_term');
        }
    }

    private function hasTable(string $table): bool
    {
        return $this->db->tableExists($table);
    }
}

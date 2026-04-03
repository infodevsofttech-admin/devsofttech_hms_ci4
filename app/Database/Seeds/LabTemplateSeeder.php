<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * OEM Lab Template Seeder
 *
 * Seeds all pathology and radiology report template data for a fresh
 * hospital installation. Safe to re-run — all inserts use INSERT IGNORE.
 *
 * Tables populated (in FK dependency order):
 *   1. lab_rgroups                   — Report group names (Haematology, BioChemistry …)
 *   2. hc_items (itype 5,6 only)     — Billing charge items linked to lab reports
 *   3. lab_repo                      — Pathology report template master
 *   4. lab_tests                     — Individual test/parameter definitions
 *   5. lab_tests_option              — Dropdown options for test parameters
 *   6. lab_repotests                 — Junction: tests per report + sort order
 *   7. radiology_ultrasound_template — US / MRI / CT / Xray / Echo templates
 *
 * Usage:
 *   php spark db:seed LabTemplateSeeder
 */
class LabTemplateSeeder extends Seeder
{
    /**
     * Ordered list of SQL files to execute.
     * Each file contains CREATE TABLE IF NOT EXISTS + INSERT IGNORE statements.
     */
    private const SQL_FILES = [
        '01_lab_rgroups.sql',
        '02_hc_items_lab.sql',
        '03_lab_repo.sql',
        '04_lab_tests.sql',
        '05_lab_tests_option.sql',
        '06_lab_repotests.sql',
        '07_radiology_ultrasound_template.sql',
    ];

    public function run(): void
    {
        $sqlDir = __DIR__ . DIRECTORY_SEPARATOR . 'LabOemData' . DIRECTORY_SEPARATOR;

        $this->db->query('SET @OLD_SQL_MODE = @@SESSION.sql_mode');
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->query('SET SESSION SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"');

        foreach (self::SQL_FILES as $file) {
            $path = $sqlDir . $file;

            if (! is_file($path)) {
                CLI::write("  [SKIP] Missing SQL file: $file", 'yellow');
                continue;
            }

            $sql = file_get_contents($path);
            if ($sql === false || trim($sql) === '') {
                CLI::write("  [SKIP] Empty SQL file: $file", 'yellow');
                continue;
            }

            // Strip UTF-8 BOM if present (PowerShell 5.1 Set-Content adds it)
            if (str_starts_with($sql, "\xEF\xBB\xBF")) {
                $sql = substr($sql, 3);
            }

            // Split on statement boundaries (semicolons not inside quotes)
            $statements = $this->splitSqlStatements($sql);

            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '') {
                    continue;
                }
                $this->db->query($stmt);
            }

            CLI::write("  [OK]   $file", 'green');
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
        $this->db->query('SET SESSION SQL_MODE = @OLD_SQL_MODE');

        CLI::write('Lab template seeder complete.', 'green');
    }

    /**
     * Simple SQL splitter: splits on semicolons that are not inside
     * single-quoted or double-quoted strings.
     *
     * @return string[]
     */
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $current    = '';
        $inSingle   = false;
        $inDouble   = false;
        $len        = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];
            $prev = ($i > 0) ? $sql[$i - 1] : '';

            if ($char === "'" && ! $inDouble && $prev !== '\\') {
                $inSingle = ! $inSingle;
            } elseif ($char === '"' && ! $inSingle && $prev !== '\\') {
                $inDouble = ! $inDouble;
            }

            if ($char === ';' && ! $inSingle && ! $inDouble) {
                $statements[] = trim($current);
                $current      = '';
            } else {
                $current .= $char;
            }
        }

        if (trim($current) !== '') {
            $statements[] = trim($current);
        }

        return $statements;
    }
}

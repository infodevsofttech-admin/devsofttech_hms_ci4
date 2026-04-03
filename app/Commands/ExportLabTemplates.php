<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\BaseConnection;

/**
 * OEM Lab Template Exporter
 *
 * Reads the current database and re-generates the SQL seed files in
 * app/Database/Seeds/LabOemData/ so they can be committed and deployed
 * to new/remote installations via git + db:seed.
 *
 * Usage:
 *   php spark oem:export-lab-templates
 *
 * Workflow:
 *   1. Add/edit lab templates in the app UI
 *   2. php spark oem:export-lab-templates
 *   3. git add app/Database/Seeds/LabOemData/  &&  git commit  &&  git push
 *   4. On remote: git pull  &&  php spark db:seed LabTemplateSeeder
 */
class ExportLabTemplates extends BaseCommand
{
    protected $group       = 'OEM';
    protected $name        = 'oem:export-lab-templates';
    protected $description = 'Export current lab/radiology templates to OEM seed SQL files.';
    protected $usage       = 'oem:export-lab-templates';

    /** Directory where SQL seed files are written. */
    private string $outDir;

    /** @var BaseConnection */
    private $db;

    public function run(array $params): void
    {
        $this->db     = \Config\Database::connect();
        $this->outDir = APPPATH . 'Database' . DIRECTORY_SEPARATOR . 'Seeds'
                      . DIRECTORY_SEPARATOR . 'LabOemData' . DIRECTORY_SEPARATOR;

        if (! is_dir($this->outDir)) {
            mkdir($this->outDir, 0755, true);
        }

        CLI::write('Exporting lab/radiology templates to OEM seed files…', 'yellow');
        CLI::newLine();

        $this->exportTable(
            filename:    '01_lab_rgroups.sql',
            table:       'lab_rgroups',
            columns:     ['mstRGrpKey', 'RepoGrp', 'PreFix', 'Suffix', 'LastNo', 'sort_order', 'Notes'],
            schema:      $this->schemaLabRgroups(),
        );

        $this->exportTable(
            filename:    '02_hc_items_lab.sql',
            table:       'hc_items',
            columns:     ['id', 'itype', 'idesc', 'idesc_detail', 'amount', 'amount_r',
                          'echs_sr_no', 'update_date', 'last_update_desc'],
            schema:      $this->schemaHcItems(),
            where:       'itype IN (5,6)',
            orderBy:     'id ASC',
        );

        $this->exportTable(
            filename:    '03_lab_repo.sql',
            table:       'lab_repo',
            columns:     ['mstRepoKey', 'Title', 'RTFData', 'HTMLData', 'GrpKey',
                          'IncludeHeader', 'IncludeFooter', 'charge_id'],
            schema:      $this->schemaLabRepo(),
            orderBy:     'mstRepoKey ASC',
        );

        $this->exportTable(
            filename:    '04_lab_tests.sql',
            table:       'lab_tests',
            columns:     ['mstTestKey', 'Test', 'TestID', 'Result', 'Options', 'Formula',
                          'VRule', 'VMsg', 'Unit', 'FixedNormals', 'isGenderSpecific', 'FixedNormalsWomen'],
            schema:      $this->schemaLabTests(),
            orderBy:     'mstTestKey ASC',
        );

        $this->exportTable(
            filename:    '05_lab_tests_option.sql',
            table:       'lab_tests_option',
            columns:     ['id', 'mstTestKey', 'sort_id', 'option_value', 'option_text', 'option_bold'],
            schema:      $this->schemaLabTestsOption(),
            orderBy:     'mstTestKey ASC, sort_id ASC',
        );

        $this->exportTable(
            filename:    '06_lab_repotests.sql',
            table:       'lab_repotests',
            columns:     ['id', 'mstRepoKey', 'mstTestKey', 'EOrder'],
            schema:      $this->schemaLabRepotests(),
            orderBy:     'mstRepoKey ASC, EOrder ASC',
        );

        $this->exportTable(
            filename:    '07_radiology_ultrasound_template.sql',
            table:       'radiology_ultrasound_template',
            columns:     ['id', 'template_name', 'keywords', 'title', 'Modality', 'charge_id',
                          'Findings', 'Impression', 'impression_cat'],
            schema:      $this->schemaRadiology(),
            orderBy:     'id ASC',
        );

        CLI::newLine();
        CLI::write('All files written to:', 'green');
        CLI::write('  ' . $this->outDir, 'cyan');
        CLI::newLine();
        CLI::write('Next steps:', 'yellow');
        CLI::write('  git add app/Database/Seeds/LabOemData/');
        CLI::write('  git commit -m "OEM: update lab template seed data"');
        CLI::write('  git push');
        CLI::write('  # On remote server: git pull && php spark db:seed LabTemplateSeeder');
    }

    // ------------------------------------------------------------------
    // Core export logic
    // ------------------------------------------------------------------

    /**
     * Exports a single table to a SQL file.
     *
     * @param string   $filename  Output SQL filename (basename only)
     * @param string   $table     Source table name
     * @param string[] $columns   Columns to export (in order)
     * @param string   $schema    CREATE TABLE IF NOT EXISTS ... statement
     * @param string   $where     Optional WHERE clause (no "WHERE" keyword)
     * @param string   $orderBy   Optional ORDER BY clause (no "ORDER BY" keyword)
     */
    private function exportTable(
        string $filename,
        string $table,
        array $columns,
        string $schema,
        string $where = '',
        string $orderBy = ''
    ): void {
        $builder = $this->db->table($table)->select(implode(', ', $columns));
        if ($where !== '') {
            $builder->where($where, null, false);
        }
        if ($orderBy !== '') {
            $builder->orderBy($orderBy, '', false);
        }
        $rows = $builder->get()->getResultArray();

        $count = count($rows);
        $path  = $this->outDir . $filename;

        // Build SQL content
        $sql  = $schema . PHP_EOL;
        $sql .= PHP_EOL;

        if ($count > 0) {
            $backtickCols = implode(', ', array_map(fn($c) => "`$c`", $columns));

            // Group rows into chunks of 100 for readability
            foreach (array_chunk($rows, 100) as $chunk) {
                $valuesClauses = array_map(fn($row) => $this->buildValuesClause($row), $chunk);
                $sql .= "INSERT IGNORE INTO `$table` ($backtickCols) VALUES" . PHP_EOL;
                $sql .= implode(',' . PHP_EOL, $valuesClauses) . ';' . PHP_EOL;
                $sql .= PHP_EOL;
            }
        } else {
            $sql .= "-- No rows found in `$table`" . PHP_EOL;
        }

        file_put_contents($path, $sql, LOCK_EX);
        CLI::write(sprintf('  [OK]  %-45s %4d rows', $filename, $count), 'green');
    }

    /**
     * Converts one result-array row to a VALUES (...) clause with proper escaping.
     */
    private function buildValuesClause(array $row): string
    {
        $vals = [];
        foreach ($row as $value) {
            if ($value === null) {
                $vals[] = 'NULL';
            } else {
                // Escape backslashes first, then single quotes
                $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $value);
                $vals[]  = "'" . $escaped . "'";
            }
        }
        return '(' . implode(', ', $vals) . ')';
    }

    // ------------------------------------------------------------------
    // Schema definitions (CREATE TABLE IF NOT EXISTS)
    // ------------------------------------------------------------------

    private function schemaLabRgroups(): string
    {
        return <<<'SQL'
CREATE TABLE IF NOT EXISTS `lab_rgroups` (
  `mstRGrpKey` int NOT NULL AUTO_INCREMENT,
  `RepoGrp` varchar(100) NOT NULL,
  `PreFix` varchar(3) NOT NULL DEFAULT '0',
  `Suffix` varchar(3) NOT NULL DEFAULT '0',
  `LastNo` int NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `Notes` varchar(50) NOT NULL DEFAULT '0',
  PRIMARY KEY (`mstRGrpKey`),
  UNIQUE KEY `RepoGrp` (`RepoGrp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 ROW_FORMAT=DYNAMIC;
SQL;
    }

    private function schemaHcItems(): string
    {
        return <<<'SQL'
CREATE TABLE IF NOT EXISTS `hc_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `itype` int DEFAULT '0',
  `idesc` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `idesc_detail` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `amount` decimal(10,2) DEFAULT '0.00',
  `amount_r` decimal(10,2) DEFAULT '0.00',
  `echs_sr_no` int DEFAULT '0',
  `update_date` datetime DEFAULT NULL,
  `last_update_desc` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
SQL;
    }

    private function schemaLabRepo(): string
    {
        return <<<'SQL'
CREATE TABLE IF NOT EXISTS `lab_repo` (
  `mstRepoKey` int NOT NULL AUTO_INCREMENT,
  `Title` varchar(100) NOT NULL,
  `RTFData` longtext,
  `HTMLData` longtext,
  `GrpKey` int NOT NULL,
  `IncludeHeader` tinyint(1) DEFAULT '1',
  `IncludeFooter` tinyint(1) DEFAULT '1',
  `charge_id` int DEFAULT '0',
  PRIMARY KEY (`mstRepoKey`),
  UNIQUE KEY `Title` (`Title`),
  KEY `GrpKey` (`GrpKey`),
  KEY `charge_id` (`charge_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
SQL;
    }

    private function schemaLabTests(): string
    {
        return <<<'SQL'
CREATE TABLE IF NOT EXISTS `lab_tests` (
  `mstTestKey` int NOT NULL AUTO_INCREMENT,
  `Test` varchar(80) NOT NULL DEFAULT '0',
  `TestID` varchar(15) NOT NULL,
  `Result` varchar(180) NOT NULL DEFAULT '0',
  `Options` text,
  `Formula` varchar(50) DEFAULT NULL,
  `VRule` varchar(50) DEFAULT NULL,
  `VMsg` varchar(50) DEFAULT NULL,
  `Unit` varchar(10) NOT NULL DEFAULT '0',
  `FixedNormals` varchar(30) NOT NULL DEFAULT '0',
  `isGenderSpecific` int NOT NULL DEFAULT '0',
  `FixedNormalsWomen` varchar(30) NOT NULL,
  PRIMARY KEY (`mstTestKey`),
  UNIQUE KEY `TestID` (`TestID`),
  KEY `Test` (`Test`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
SQL;
    }

    private function schemaLabTestsOption(): string
    {
        return <<<'SQL'
CREATE TABLE IF NOT EXISTS `lab_tests_option` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mstTestKey` int DEFAULT NULL,
  `sort_id` int DEFAULT NULL,
  `option_value` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `option_text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `option_bold` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `mstTestKey_sort_id` (`mstTestKey`,`sort_id`),
  UNIQUE KEY `mstTestKey_option_value` (`mstTestKey`,`option_value`),
  KEY `option_value` (`option_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
SQL;
    }

    private function schemaLabRepotests(): string
    {
        return <<<'SQL'
CREATE TABLE IF NOT EXISTS `lab_repotests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mstRepoKey` int NOT NULL,
  `mstTestKey` int NOT NULL,
  `EOrder` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`mstRepoKey`,`mstTestKey`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `mstRepoKey_EOrder` (`mstRepoKey`,`EOrder`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
SQL;
    }

    private function schemaRadiology(): string
    {
        return <<<'SQL'
CREATE TABLE IF NOT EXISTS `radiology_ultrasound_template` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_name` varchar(200) CHARACTER SET utf32 COLLATE utf32_unicode_ci DEFAULT NULL,
  `keywords` varchar(1000) CHARACTER SET utf32 COLLATE utf32_unicode_ci DEFAULT NULL,
  `title` varchar(200) CHARACTER SET utf32 COLLATE utf32_unicode_ci DEFAULT NULL,
  `Modality` int DEFAULT NULL,
  `charge_id` int DEFAULT NULL,
  `Findings` longtext CHARACTER SET utf32 COLLATE utf32_unicode_ci,
  `Impression` longtext CHARACTER SET utf32 COLLATE utf32_unicode_ci,
  `impression_cat` int DEFAULT '0' COMMENT '0 Not Noteable,1 Noteable',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `template_name` (`template_name`) USING BTREE,
  KEY `title` (`title`) USING BTREE,
  KEY `keywords` (`keywords`(768))
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_unicode_ci;
SQL;
    }
}

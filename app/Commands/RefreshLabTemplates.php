<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class RefreshLabTemplates extends BaseCommand
{
    protected $group       = 'OEM';
    protected $name        = 'oem:refresh-lab-templates';
    protected $description = 'Rebuild pathology/radiology template tables from LabTemplateSeeder (without truncating hc_items).';
    protected $usage       = 'oem:refresh-lab-templates';

    public function run(array $params): void
    {
        $db = \Config\Database::connect();

        CLI::write('Refreshing lab template tables...', 'yellow');
        CLI::write('This will truncate: lab_repotests, lab_tests_option, lab_tests, lab_repo, lab_rgroups, radiology_ultrasound_template', 'yellow');
        CLI::write('hc_items will NOT be truncated.', 'yellow');

        try {
            $db->query('SET FOREIGN_KEY_CHECKS = 0');

            // Order matters for dependent rows
            $db->query('TRUNCATE TABLE lab_repotests');
            $db->query('TRUNCATE TABLE lab_tests_option');
            $db->query('TRUNCATE TABLE lab_tests');
            $db->query('TRUNCATE TABLE lab_repo');
            $db->query('TRUNCATE TABLE lab_rgroups');
            $db->query('TRUNCATE TABLE radiology_ultrasound_template');

            $db->query('SET FOREIGN_KEY_CHECKS = 1');

            CLI::write('Template tables truncated. Running LabTemplateSeeder...', 'green');

            $seeder = \Config\Database::seeder();
            $seeder->call('App\\Database\\Seeds\\LabTemplateSeeder');

            $cbc = $db->query("SELECT mstRepoKey, Title FROM lab_repo WHERE Title LIKE '%CBC%' LIMIT 1")->getRow();

            if ($cbc) {
                CLI::write('CBC template restored: #' . (string) $cbc->mstRepoKey . ' - ' . (string) $cbc->Title, 'green');
            } else {
                CLI::error('CBC template still not found after refresh. Check DB connection and seed files.');
            }

            CLI::write('Done.', 'green');
        } catch (\Throwable $e) {
            try {
                $db->query('SET FOREIGN_KEY_CHECKS = 1');
            } catch (\Throwable $ignored) {
            }
            CLI::error('Refresh failed: ' . $e->getMessage());
        }
    }
}

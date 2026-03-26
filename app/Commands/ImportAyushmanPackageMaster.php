<?php

namespace App\Commands;

use Config\Database;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ImportAyushmanPackageMaster extends BaseCommand
{
    protected $group = 'Import';
    protected $name = 'ayushman:import-packages';
    protected $description = 'Import Ayushman Bharat package master data from a CSV file into ayushman_package_master.';
    protected $usage = 'ayushman:import-packages [csvPath] [--truncate]';
    protected $arguments = [
        'csvPath' => 'Optional CSV path. Defaults to writable/ayushman/105_PackageMasterReport.csv',
    ];
    protected $options = [
        '--truncate' => 'Clear ayushman_package_master before importing.',
    ];

    public function run(array $params)
    {
        $csvPath = $params[0] ?? WRITEPATH . 'ayushman/105_PackageMasterReport.csv';
        if (! is_file($csvPath)) {
            CLI::error('CSV file not found: ' . $csvPath);

            return;
        }

        $db = Database::connect();
        if (! $db->tableExists('ayushman_package_master')) {
            CLI::error('Table ayushman_package_master does not exist. Run php spark migrate first.');

            return;
        }

        if (CLI::getOption('truncate')) {
            $db->table('ayushman_package_master')->truncate();
            CLI::write('Existing ayushman_package_master rows truncated.', 'yellow');
        }

        $handle = fopen($csvPath, 'rb');
        if ($handle === false) {
            CLI::error('Unable to open CSV file: ' . $csvPath);

            return;
        }

        $rowNo = 0;
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $now = date('Y-m-d H:i:s');
        $table = $db->table('ayushman_package_master');

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNo++;

                if ($rowNo <= 3) {
                    continue;
                }

                $mapped = $this->mapCsvRow($row, basename($csvPath), $now);
                if ($mapped === null) {
                    $skipped++;
                    continue;
                }

                $existing = $db->table('ayushman_package_master')
                    ->select('id')
                    ->where('procedure_code', $mapped['procedure_code'])
                    ->get(1)
                    ->getRowArray();

                if (is_array($existing) && ! empty($existing['id'])) {
                    $id = (int) $existing['id'];
                    unset($mapped['created_at']);
                    $table->where('id', $id)->update($mapped);
                    $updated++;
                    continue;
                }

                $table->insert($mapped);
                $inserted++;
            }
        } finally {
            fclose($handle);
        }

        CLI::write('Ayushman package import complete', 'green');
        CLI::write('CSV: ' . $csvPath);
        CLI::write('Inserted: ' . $inserted, 'green');
        CLI::write('Updated: ' . $updated, 'yellow');
        CLI::write('Skipped: ' . $skipped);
    }

    private function mapCsvRow(array $row, string $sourceFile, string $timestamp): ?array
    {
        $row = array_pad($row, 10, '');

        $specialityCode = trim((string) ($row[0] ?? ''));
        $specialityName = trim((string) ($row[1] ?? ''));
        $procedureCode = trim((string) ($row[2] ?? ''));
        $procedureName = trim((string) ($row[3] ?? ''));

        if ($procedureCode === '' && $procedureName === '') {
            return null;
        }

        $amountText = trim((string) ($row[4] ?? '0'));
        $amountText = str_replace(',', '', $amountText);
        $packageAmount = is_numeric($amountText) ? (float) $amountText : 0.0;

        return [
            'speciality_code' => substr($specialityCode, 0, 50),
            'speciality_name' => substr($specialityName, 0, 255),
            'procedure_code' => substr($procedureCode, 0, 50),
            'procedure_name' => $procedureName,
            'package_amount' => number_format($packageAmount, 2, '.', ''),
            'preauth_required' => strtolower(trim((string) ($row[5] ?? ''))) === 'yes' ? 1 : 0,
            'procedure_type' => substr(trim((string) ($row[6] ?? '')), 0, 50),
            'government_reserved' => strtolower(trim((string) ($row[7] ?? ''))) === 'yes' ? 1 : 0,
            'pre_investigations' => trim((string) ($row[8] ?? '')),
            'post_investigations' => trim((string) ($row[9] ?? '')),
            'source_file' => substr($sourceFile, 0, 255),
            'source_sheet' => 'Sheet1',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }
}
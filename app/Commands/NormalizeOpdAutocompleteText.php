<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class NormalizeOpdAutocompleteText extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'opd:normalize-autocomplete-text';
    protected $description = 'One-time cleanup: normalize autocomplete text in OPD prescription and keyword master tables.';
    protected $usage       = 'opd:normalize-autocomplete-text [--apply] [--batch 500]';
    protected $options     = [
        '--apply' => 'Apply updates. If omitted, command runs in dry-run mode only.',
        '--batch' => 'Batch size for scan/update loop (default: 500).',
    ];

    public function run(array $params)
    {
        $db = Database::connect();
        $apply = CLI::getOption('apply') !== null;
        $batchSize = max(100, (int) (CLI::getOption('batch') ?? 500));

        CLI::write('OPD autocomplete text normalization', 'yellow');
        CLI::write('Mode: ' . ($apply ? 'APPLY' : 'DRY-RUN'), $apply ? 'light_green' : 'light_yellow');
        CLI::write('Batch size: ' . $batchSize, 'cyan');

        $summary = [
            'scanned' => 0,
            'changed' => 0,
            'updated' => 0,
            'tables'  => 0,
        ];

        $summary = $this->processPrescriptionTable($db, $apply, $batchSize, $summary);
        $summary = $this->processKeywordTable($db, $apply, $batchSize, $summary);

        CLI::newLine();
        CLI::write('Summary', 'yellow');
        CLI::write('Tables processed: ' . $summary['tables']);
        CLI::write('Rows scanned: ' . $summary['scanned']);
        CLI::write('Rows with normalized changes: ' . $summary['changed']);
        CLI::write('Rows updated: ' . $summary['updated'], $summary['updated'] > 0 ? 'light_green' : 'white');

        if (! $apply) {
            CLI::newLine();
            CLI::write('No DB rows were modified in dry-run mode.', 'light_yellow');
            CLI::write('Run again with --apply to persist changes.', 'light_yellow');
        }
    }

    private function processPrescriptionTable($db, bool $apply, int $batchSize, array $summary): array
    {
        $table = 'opd_prescription';
        if (! $db->tableExists($table)) {
            CLI::write('Skipping ' . $table . ': table not found.', 'light_gray');
            return $summary;
        }

        $fields = $db->getFieldNames($table) ?? [];
        $idField = in_array('id', $fields, true) ? 'id' : null;
        if ($idField === null) {
            CLI::write('Skipping ' . $table . ': id column not found.', 'red');
            return $summary;
        }

        $targets = array_values(array_intersect(
            ['Finding_Examinations', 'complaints', 'Complaint', 'provisional_diagnosis', 'Provisional_diagnosis'],
            $fields
        ));

        if ($targets === []) {
            CLI::write('Skipping ' . $table . ': no target text columns found.', 'light_gray');
            return $summary;
        }

        CLI::newLine();
        CLI::write('Processing ' . $table . ' columns: ' . implode(', ', $targets), 'cyan');
        $summary['tables']++;

        $offset = 0;
        while (true) {
            $rows = $db->table($table)
                ->select(implode(', ', array_merge([$idField], $targets)))
                ->orderBy($idField, 'ASC')
                ->limit($batchSize, $offset)
                ->get()
                ->getResultArray();

            if ($rows === []) {
                break;
            }

            $offset += count($rows);
            foreach ($rows as $row) {
                $summary['scanned']++;
                $update = [];

                foreach ($targets as $column) {
                    $original = (string) ($row[$column] ?? '');
                    if ($original === '') {
                        continue;
                    }

                    $normalized = $this->normalizeAutocompleteText($original);
                    if ($normalized !== '' && $normalized !== $original) {
                        $update[$column] = $normalized;
                    }
                }

                if ($update === []) {
                    continue;
                }

                $summary['changed']++;
                if ($apply) {
                    $db->table($table)->where($idField, $row[$idField])->update($update);
                    $summary['updated']++;
                }
            }
        }

        return $summary;
    }

    private function processKeywordTable($db, bool $apply, int $batchSize, array $summary): array
    {
        $table = 'opd_autotype_keyword_master';
        if (! $db->tableExists($table)) {
            CLI::write('Skipping ' . $table . ': table not found.', 'light_gray');
            return $summary;
        }

        $fields = $db->getFieldNames($table) ?? [];
        if (! in_array('id', $fields, true) || ! in_array('keyword', $fields, true)) {
            CLI::write('Skipping ' . $table . ': required columns id/keyword not found.', 'red');
            return $summary;
        }

        $hasSection = in_array('section_key', $fields, true);
        $sections = ['complaints', 'provisional_diagnosis', 'finding_examinations'];

        CLI::newLine();
        CLI::write('Processing ' . $table . ' keyword column' . ($hasSection ? ' for selected sections only.' : '.'), 'cyan');
        $summary['tables']++;

        $offset = 0;
        while (true) {
            $builder = $db->table($table)->select($hasSection ? 'id, keyword, section_key' : 'id, keyword');
            if ($hasSection) {
                $builder->whereIn('section_key', $sections);
            }

            $rows = $builder
                ->orderBy('id', 'ASC')
                ->limit($batchSize, $offset)
                ->get()
                ->getResultArray();

            if ($rows === []) {
                break;
            }

            $offset += count($rows);
            foreach ($rows as $row) {
                $summary['scanned']++;
                $original = (string) ($row['keyword'] ?? '');
                if ($original === '') {
                    continue;
                }

                $normalized = $this->normalizeAutocompleteText($original);
                if ($normalized === '' || $normalized === $original) {
                    continue;
                }

                $summary['changed']++;
                if ($apply) {
                    $db->table($table)->where('id', $row['id'])->update(['keyword' => $normalized]);
                    $summary['updated']++;
                }
            }
        }

        return $summary;
    }

    private function normalizeAutocompleteText(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\n"], ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        $parts = preg_split('/\s*,\s*/', $text) ?: [];
        $clean = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }

            $part = preg_replace('/\s+/', ' ', $part) ?? $part;
            $part = preg_replace_callback('/\b([A-Za-z])\s*\/\s*([A-Za-z])\b/u', static function (array $m): string {
                return mb_strtoupper($m[1]) . '/' . mb_strtoupper($m[2]);
            }, $part) ?? $part;

            if (preg_match('/^[+-]{1,3}$/', $part) && $clean !== []) {
                $lastIndex = count($clean) - 1;
                $clean[$lastIndex] = rtrim($clean[$lastIndex]) . ' ' . $part;
                continue;
            }

            if (preg_match('/^(.+?)([+-]{1,3})$/u', $part, $m) === 1) {
                $base = trim((string) $m[1]);
                $sign = trim((string) $m[2]);
                if ($base !== '') {
                    $part = $base . ' ' . $sign;
                }
            }

            $clean[] = $part;
        }

        $normalized = implode(', ', $clean);
        return rtrim(trim($normalized), ', ');
    }
}

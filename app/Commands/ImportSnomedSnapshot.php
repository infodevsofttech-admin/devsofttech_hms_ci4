<?php

namespace App\Commands;

use Config\Database;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ImportSnomedSnapshot extends BaseCommand
{
    protected $group = 'Import';
    protected $name = 'snomed:import-snapshot';
    protected $description = 'Import SNOMED CT RF2 Snapshot files (Concept, Description, Language, Map) into HMS SNOMED tables.';
    protected $usage = 'snomed:import-snapshot [--package path] [--release YYYYMMDD] [--rf2-md5 hash] [--notes-md5 hash] [--batch 1000] [--keep-existing] [--skip-map]';
    protected $options = [
        '--package' => 'Path to SNOMED package root folder. Defaults to ROOTPATH/SnomedCT_InternationalRF2_PRODUCTION_YYYYMMDDT120000Z when resolvable.',
        '--release' => 'Release effectiveTime (YYYYMMDD). If omitted, taken from release_package_information.json.',
        '--rf2-md5' => 'RF2 package MD5 for audit logging.',
        '--notes-md5' => 'Release notes MD5 for audit logging.',
        '--batch' => 'Batch size for insertBatch (default: 1000).',
        '--keep-existing' => 'Do not truncate existing SNOMED tables before import.',
        '--skip-map' => 'Skip importing simple/extended map files.',
    ];

    public function run(array $params)
    {
        $db = Database::connect();
        $this->ensureRequiredTables($db);

        $batchSize = max(100, (int) (CLI::getOption('batch') ?: 1000));
        $packagePath = $this->resolvePackagePath((string) (CLI::getOption('package') ?? ''));
        if ($packagePath === '' || ! is_dir($packagePath)) {
            CLI::error('SNOMED package folder not found. Pass --package with full folder path.');
            return;
        }

        $meta = $this->readReleaseMetadata($packagePath);
        $release = trim((string) (CLI::getOption('release') ?: ($meta['effectiveTime'] ?? '')));
        if (! preg_match('/^[0-9]{8}$/', $release)) {
            CLI::error('Release effectiveTime is missing/invalid. Use --release YYYYMMDD.');
            return;
        }

        $rf2Md5 = trim((string) (CLI::getOption('rf2-md5') ?: ''));
        $notesMd5 = trim((string) (CLI::getOption('notes-md5') ?: ''));
        $keepExisting = (bool) CLI::getOption('keep-existing');
        $skipMap = (bool) CLI::getOption('skip-map');

        $packageName = basename($packagePath);
        $now = date('Y-m-d H:i:s');

        $logId = $this->startReleaseLog($db, $packageName, $release, $rf2Md5, $notesMd5, $now);

        $counts = [
            'concept' => 0,
            'description' => 0,
            'language_refset' => 0,
            'simple_map' => 0,
            'extended_map' => 0,
        ];

        try {
            if (! $keepExisting) {
                $this->truncateTargetTables($db, ! $skipMap);
            }

            $counts['concept'] = $this->importConceptSnapshot($db, $packagePath, $release, $batchSize, $now);
            $counts['description'] = $this->importDescriptionSnapshot($db, $packagePath, $release, $batchSize, $now);
            $counts['language_refset'] = $this->importLanguageSnapshot($db, $packagePath, $release, $batchSize, $now);

            if (! $skipMap) {
                $counts['simple_map'] = $this->importSimpleMapSnapshot($db, $packagePath, $release, $batchSize, $now);
                $counts['extended_map'] = $this->importExtendedMapSnapshot($db, $packagePath, $release, $batchSize, $now);
            }

            $this->finishReleaseLog($db, $logId, 'completed', $counts, '', date('Y-m-d H:i:s'));

            CLI::write('SNOMED snapshot import completed', 'green');
            CLI::write('Package: ' . $packagePath);
            CLI::write('Release: ' . $release);
            CLI::write('Concept rows: ' . $counts['concept'], 'green');
            CLI::write('Description rows: ' . $counts['description'], 'green');
            CLI::write('Language rows: ' . $counts['language_refset'], 'green');
            CLI::write('Simple map rows: ' . $counts['simple_map'], 'yellow');
            CLI::write('Extended map rows: ' . $counts['extended_map'], 'yellow');
        } catch (\Throwable $e) {
            $this->finishReleaseLog($db, $logId, 'failed', $counts, $e->getMessage(), date('Y-m-d H:i:s'));
            CLI::error('Import failed: ' . $e->getMessage());
        }
    }

    private function ensureRequiredTables($db): void
    {
        $required = [
            'snomed_release_log',
            'snomed_concept',
            'snomed_description',
            'snomed_language_refset',
            'snomed_map_simple',
            'snomed_map_extended',
        ];

        foreach ($required as $table) {
            if (! $db->tableExists($table)) {
                throw new \RuntimeException('Missing table ' . $table . '. Run php spark migrate first.');
            }
        }
    }

    private function resolvePackagePath(string $requestedPath): string
    {
        $path = trim($requestedPath);
        if ($path !== '') {
            return rtrim($path, "\\/");
        }

        $candidates = glob(ROOTPATH . 'SnomedCT_InternationalRF2_PRODUCTION_*', GLOB_ONLYDIR) ?: [];
        if (empty($candidates)) {
            return '';
        }

        rsort($candidates);
        return rtrim((string) $candidates[0], "\\/");
    }

    private function readReleaseMetadata(string $packagePath): array
    {
        $jsonPath = $packagePath . DIRECTORY_SEPARATOR . 'release_package_information.json';
        if (! is_file($jsonPath)) {
            return [];
        }

        $raw = file_get_contents($jsonPath);
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function startReleaseLog($db, string $packageName, string $release, string $rf2Md5, string $notesMd5, string $timestamp): int
    {
        $db->table('snomed_release_log')->insert([
            'package_name' => $packageName,
            'release_effective_time' => $release,
            'rf2_md5' => $rf2Md5 !== '' ? $rf2Md5 : null,
            'release_notes_md5' => $notesMd5 !== '' ? $notesMd5 : null,
            'status' => 'started',
            'row_counts_json' => null,
            'error_text' => null,
            'imported_at' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        return (int) $db->insertID();
    }

    private function finishReleaseLog($db, int $id, string $status, array $counts, string $errorText, string $timestamp): void
    {
        $db->table('snomed_release_log')
            ->where('id', $id)
            ->update([
                'status' => $status,
                'row_counts_json' => json_encode($counts, JSON_UNESCAPED_SLASHES),
                'error_text' => $errorText !== '' ? substr($errorText, 0, 1000) : null,
                'imported_at' => $status === 'completed' ? $timestamp : null,
                'updated_at' => $timestamp,
            ]);
    }

    private function truncateTargetTables($db, bool $includeMaps): void
    {
        $tables = ['snomed_concept', 'snomed_description', 'snomed_language_refset'];
        if ($includeMaps) {
            $tables[] = 'snomed_map_simple';
            $tables[] = 'snomed_map_extended';
        }

        foreach ($tables as $table) {
            $db->table($table)->truncate();
            CLI::write('Truncated ' . $table, 'yellow');
        }
    }

    private function importConceptSnapshot($db, string $packagePath, string $release, int $batchSize, string $timestamp): int
    {
        $path = $packagePath . DIRECTORY_SEPARATOR . 'Snapshot' . DIRECTORY_SEPARATOR . 'Terminology' . DIRECTORY_SEPARATOR . 'sct2_Concept_Snapshot_INT_' . $release . '.txt';
        return $this->importTsv($path, $batchSize, function (array $row) use ($timestamp) {
            return [
                'concept_id' => trim((string) ($row['id'] ?? '')),
                'effective_time' => trim((string) ($row['effectiveTime'] ?? '')),
                'active' => (int) ($row['active'] ?? 0),
                'module_id' => trim((string) ($row['moduleId'] ?? '')),
                'definition_status_id' => trim((string) ($row['definitionStatusId'] ?? '')),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }, function (array $mapped): bool {
            return $mapped['concept_id'] !== '';
        }, function (array $batch) use ($db) {
            $db->table('snomed_concept')->ignore(true)->insertBatch($batch);
        }, 'Concept');
    }

    private function importDescriptionSnapshot($db, string $packagePath, string $release, int $batchSize, string $timestamp): int
    {
        $path = $packagePath . DIRECTORY_SEPARATOR . 'Snapshot' . DIRECTORY_SEPARATOR . 'Terminology' . DIRECTORY_SEPARATOR . 'sct2_Description_Snapshot-en_INT_' . $release . '.txt';
        return $this->importTsv($path, $batchSize, function (array $row) use ($timestamp) {
            $term = trim((string) ($row['term'] ?? ''));
            $termNormalized = mb_strtolower(trim(preg_replace('/\s+/', ' ', $term) ?? $term));
            return [
                'description_id' => trim((string) ($row['id'] ?? '')),
                'effective_time' => trim((string) ($row['effectiveTime'] ?? '')),
                'active' => (int) ($row['active'] ?? 0),
                'module_id' => trim((string) ($row['moduleId'] ?? '')),
                'concept_id' => trim((string) ($row['conceptId'] ?? '')),
                'language_code' => trim((string) ($row['languageCode'] ?? '')),
                'type_id' => trim((string) ($row['typeId'] ?? '')),
                'term' => mb_substr($term, 0, 512),
                'term_normalized' => mb_substr($termNormalized, 0, 255),
                'case_significance_id' => trim((string) ($row['caseSignificanceId'] ?? '')),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }, function (array $mapped): bool {
            return $mapped['description_id'] !== '' && $mapped['term'] !== '';
        }, function (array $batch) use ($db) {
            $db->table('snomed_description')->ignore(true)->insertBatch($batch);
        }, 'Description');
    }

    private function importLanguageSnapshot($db, string $packagePath, string $release, int $batchSize, string $timestamp): int
    {
        $path = $packagePath . DIRECTORY_SEPARATOR . 'Snapshot' . DIRECTORY_SEPARATOR . 'Refset' . DIRECTORY_SEPARATOR . 'Language' . DIRECTORY_SEPARATOR . 'der2_cRefset_LanguageSnapshot-en_INT_' . $release . '.txt';
        return $this->importTsv($path, $batchSize, function (array $row) use ($timestamp) {
            return [
                'member_id' => trim((string) ($row['id'] ?? '')),
                'effective_time' => trim((string) ($row['effectiveTime'] ?? '')),
                'active' => (int) ($row['active'] ?? 0),
                'module_id' => trim((string) ($row['moduleId'] ?? '')),
                'refset_id' => trim((string) ($row['refsetId'] ?? '')),
                'referenced_component_id' => trim((string) ($row['referencedComponentId'] ?? '')),
                'acceptability_id' => trim((string) ($row['acceptabilityId'] ?? '')),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }, function (array $mapped): bool {
            return $mapped['member_id'] !== '';
        }, function (array $batch) use ($db) {
            $db->table('snomed_language_refset')->ignore(true)->insertBatch($batch);
        }, 'Language refset');
    }

    private function importSimpleMapSnapshot($db, string $packagePath, string $release, int $batchSize, string $timestamp): int
    {
        $path = $packagePath . DIRECTORY_SEPARATOR . 'Snapshot' . DIRECTORY_SEPARATOR . 'Refset' . DIRECTORY_SEPARATOR . 'Map' . DIRECTORY_SEPARATOR . 'der2_sRefset_SimpleMapSnapshot_INT_' . $release . '.txt';
        return $this->importTsv($path, $batchSize, function (array $row) use ($timestamp) {
            return [
                'member_id' => trim((string) ($row['id'] ?? '')),
                'effective_time' => trim((string) ($row['effectiveTime'] ?? '')),
                'active' => (int) ($row['active'] ?? 0),
                'module_id' => trim((string) ($row['moduleId'] ?? '')),
                'refset_id' => trim((string) ($row['refsetId'] ?? '')),
                'referenced_component_id' => trim((string) ($row['referencedComponentId'] ?? '')),
                'map_target' => trim((string) ($row['mapTarget'] ?? '')),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }, function (array $mapped): bool {
            return $mapped['member_id'] !== '';
        }, function (array $batch) use ($db) {
            $db->table('snomed_map_simple')->ignore(true)->insertBatch($batch);
        }, 'Simple map');
    }

    private function importExtendedMapSnapshot($db, string $packagePath, string $release, int $batchSize, string $timestamp): int
    {
        $path = $packagePath . DIRECTORY_SEPARATOR . 'Snapshot' . DIRECTORY_SEPARATOR . 'Refset' . DIRECTORY_SEPARATOR . 'Map' . DIRECTORY_SEPARATOR . 'der2_iisssccRefset_ExtendedMapSnapshot_INT_' . $release . '.txt';
        return $this->importTsv($path, $batchSize, function (array $row) use ($timestamp) {
            return [
                'member_id' => trim((string) ($row['id'] ?? '')),
                'effective_time' => trim((string) ($row['effectiveTime'] ?? '')),
                'active' => (int) ($row['active'] ?? 0),
                'module_id' => trim((string) ($row['moduleId'] ?? '')),
                'refset_id' => trim((string) ($row['refsetId'] ?? '')),
                'referenced_component_id' => trim((string) ($row['referencedComponentId'] ?? '')),
                'map_group' => is_numeric($row['mapGroup'] ?? null) ? (int) $row['mapGroup'] : null,
                'map_priority' => is_numeric($row['mapPriority'] ?? null) ? (int) $row['mapPriority'] : null,
                'map_rule' => mb_substr(trim((string) ($row['mapRule'] ?? '')), 0, 512),
                'map_advice' => mb_substr(trim((string) ($row['mapAdvice'] ?? '')), 0, 512),
                'map_target' => trim((string) ($row['mapTarget'] ?? '')),
                'correlation_id' => trim((string) ($row['correlationId'] ?? '')),
                'map_category_id' => trim((string) ($row['mapCategoryId'] ?? '')),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }, function (array $mapped): bool {
            return $mapped['member_id'] !== '';
        }, function (array $batch) use ($db) {
            $db->table('snomed_map_extended')->ignore(true)->insertBatch($batch);
        }, 'Extended map');
    }

    private function importTsv(string $path, int $batchSize, callable $mapRow, callable $isValid, callable $flushBatch, string $label): int
    {
        if (! is_file($path)) {
            throw new \RuntimeException($label . ' file not found: ' . $path);
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open ' . $label . ' file: ' . $path);
        }

        $header = null;
        $batch = [];
        $imported = 0;
        $line = 0;

        try {
            while (($cols = fgetcsv($handle, 0, "\t")) !== false) {
                $line++;

                if ($line === 1) {
                    $header = $cols;
                    continue;
                }

                if (! is_array($header)) {
                    continue;
                }

                $assoc = $this->combineHeaderRow($header, $cols);
                $mapped = $mapRow($assoc);
                if (! $isValid($mapped)) {
                    continue;
                }

                $batch[] = $mapped;
                if (count($batch) >= $batchSize) {
                    $flushBatch($batch);
                    $imported += count($batch);
                    $batch = [];

                    if ($imported % ($batchSize * 10) === 0) {
                        CLI::write($label . ' imported: ' . $imported, 'cyan');
                    }
                }
            }

            if (! empty($batch)) {
                $flushBatch($batch);
                $imported += count($batch);
            }
        } finally {
            fclose($handle);
        }

        CLI::write($label . ' imported total: ' . $imported, 'green');
        return $imported;
    }

    private function combineHeaderRow(array $header, array $row): array
    {
        $output = [];
        $max = min(count($header), count($row));
        for ($i = 0; $i < $max; $i++) {
            $key = trim((string) ($header[$i] ?? ''));
            if ($key === '') {
                continue;
            }
            $output[$key] = $row[$i] ?? null;
        }
        return $output;
    }
}

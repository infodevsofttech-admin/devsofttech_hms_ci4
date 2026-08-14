<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AbdmPruneLogs extends BaseCommand
{
    protected $group = 'ABDM';
    protected $name = 'abdm:prune-logs';
    protected $description = 'Delete ABDM log rows older than the retention window (default 2 days).';
    protected $usage = 'abdm:prune-logs [--days 2] [--table all] [--batch 2000] [--dry-run] [--optimize]';
    protected $arguments = [];
    protected $options = [
        '--days' => 'Retention window in days (default: 2).',
        '--table' => 'all | abdm_api_logs | abdm_hiu_workflows (default: all).',
        '--batch' => 'Rows deleted per statement (default: 2000).',
        '--dry-run' => 'Report what would be deleted without deleting.',
        '--optimize' => 'Rebuild the table afterwards to release disk space.',
    ];

    /**
     * Extra guard per table, ANDed with the age filter.
     * HIU rows with a future retry slot are still in flight for abdm:hiu-retry.
     */
    private const GUARDS = [
        'abdm_api_logs' => '',
        'abdm_hiu_workflows' => ' AND (is_retryable = 0 OR next_retry_at IS NULL OR next_retry_at <= NOW())',
    ];

    public function run(array $params)
    {
        $days = (int) (CLI::getOption('days') ?? 2);
        if ($days <= 0) {
            $days = 2;
        }

        $batch = (int) (CLI::getOption('batch') ?? 2000);
        if ($batch <= 0) {
            $batch = 2000;
        }

        $dryRun   = CLI::getOption('dry-run') !== null;
        $optimize = CLI::getOption('optimize') !== null;

        $requested = trim((string) (CLI::getOption('table') ?? 'all'));
        $tables    = $requested === 'all' || $requested === '1'
            ? array_keys(self::GUARDS)
            : [$requested];

        foreach ($tables as $table) {
            if (! array_key_exists($table, self::GUARDS)) {
                CLI::error('Unsupported table: ' . $table);

                return;
            }
        }

        $db     = \Config\Database::connect();
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));

        CLI::write('ABDM Log Prune' . ($dryRun ? ' (dry-run)' : ''), 'yellow');
        CLI::write('Retention: ' . $days . ' day(s) — cutoff ' . $cutoff);

        foreach ($tables as $table) {
            if (! $db->tableExists($table)) {
                CLI::write($table . ': table not found, skipped.');
                continue;
            }

            $hasUpdatedAt = in_array('updated_at', $db->getFieldNames($table) ?? [], true);
            $age = $hasUpdatedAt
                ? '((created_at IS NOT NULL AND created_at < ?) OR (created_at IS NULL AND updated_at IS NOT NULL AND updated_at < ?))'
                : '(created_at IS NOT NULL AND created_at < ?)';
            $binds = $hasUpdatedAt ? [$cutoff, $cutoff] : [$cutoff];
            $where = $age . self::GUARDS[$table];

            $total = (int) ($db->query('SELECT COUNT(*) AS c FROM ' . $table . ' WHERE ' . $where, $binds)
                ->getRowArray()['c'] ?? 0);

            if ($total === 0) {
                CLI::write($table . ': nothing to prune.');
                continue;
            }

            if ($dryRun) {
                CLI::write($table . ': ' . $total . ' row(s) eligible.');
                continue;
            }

            $deleted = 0;
            do {
                $db->query('DELETE FROM ' . $table . ' WHERE ' . $where . ' ORDER BY id LIMIT ' . $batch, $binds);
                $affected = (int) $db->affectedRows();
                $deleted += $affected;
            } while ($affected > 0);

            CLI::write($table . ': deleted ' . $deleted . ' row(s).', 'green');

            if ($optimize) {
                // InnoDB reports "recreate + analyze"; that rebuild is what frees the file space.
                $db->query('OPTIMIZE TABLE ' . $table);
                CLI::write($table . ': rebuilt, disk space released.', 'green');
            }
        }
    }
}

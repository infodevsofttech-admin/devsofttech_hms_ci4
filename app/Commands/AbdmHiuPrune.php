<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AbdmHiuPrune extends BaseCommand
{
    protected $group = 'ABDM';
    protected $name = 'abdm:hiu-prune';
    protected $description = 'Delete abdm_hiu_workflows rows older than the retention window (default 2 days).';
    protected $usage = 'abdm:hiu-prune [--days 2] [--batch 2000] [--dry-run] [--optimize]';
    protected $arguments = [];
    protected $options = [
        '--days' => 'Retention window in days (default: 2).',
        '--batch' => 'Rows deleted per statement (default: 2000).',
        '--dry-run' => 'Report what would be deleted without deleting.',
        '--optimize' => 'Run OPTIMIZE TABLE afterwards to release disk space.',
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

        $dryRun = CLI::getOption('dry-run') !== null;

        $db = \Config\Database::connect();
        if (! $db->tableExists('abdm_hiu_workflows')) {
            CLI::error('Table abdm_hiu_workflows not found.');

            return;
        }

        $cutoff = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));

        // Rows with a future retry slot are still in flight for abdm:hiu-retry.
        $where = '((created_at IS NOT NULL AND created_at < ?)'
            . ' OR (created_at IS NULL AND updated_at IS NOT NULL AND updated_at < ?))'
            . ' AND (is_retryable = 0 OR next_retry_at IS NULL OR next_retry_at <= NOW())';

        $total = (int) ($db->query(
            'SELECT COUNT(*) AS c FROM abdm_hiu_workflows WHERE ' . $where,
            [$cutoff, $cutoff]
        )->getRowArray()['c'] ?? 0);

        CLI::write('ABDM HIU Workflow Prune' . ($dryRun ? ' (dry-run)' : ''), 'yellow');
        CLI::write('Retention: ' . $days . ' day(s) — cutoff ' . $cutoff);
        CLI::write('Eligible rows: ' . $total);

        if ($total === 0 || $dryRun) {
            return;
        }

        $deleted = 0;
        do {
            $db->query(
                'DELETE FROM abdm_hiu_workflows WHERE ' . $where . ' ORDER BY id LIMIT ' . $batch,
                [$cutoff, $cutoff]
            );
            $affected = (int) $db->affectedRows();
            $deleted += $affected;
        } while ($affected > 0);

        CLI::write('Deleted: ' . $deleted, 'green');

        if (CLI::getOption('optimize') !== null) {
            $db->query('OPTIMIZE TABLE abdm_hiu_workflows');
            CLI::write('Table optimized (disk space released).', 'green');
        }
    }
}

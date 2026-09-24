<?php

namespace App\Commands;

use App\Libraries\Abdm\M3HiuWorkflowService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AbdmHiuRetry extends BaseCommand
{
    protected $group = 'ABDM';
    protected $name = 'abdm:hiu-retry';
    protected $description = 'Retry failed transient ABDM M3 HIU workflow items.';
    protected $usage = 'abdm:hiu-retry [--limit 20]';
    protected $arguments = [];
    protected $options = [
        '--limit' => 'Maximum failed rows to retry in this run.',
    ];

    public function run(array $params)
    {
        $lockDir = WRITEPATH . 'locks';
        if (! is_dir($lockDir)) {
            @mkdir($lockDir, 0755, true);
        }
        $lockFile = $lockDir . DIRECTORY_SEPARATOR . 'abdm_hiu_retry.lock';
        $lockFp = @fopen($lockFile, 'c+');

        if (! $lockFp || ! flock($lockFp, LOCK_EX | LOCK_NB)) {
            CLI::write('Another instance of abdm:hiu-retry is already running. Skipping execution.', 'yellow');
            if ($lockFp) {
                fclose($lockFp);
            }
            return;
        }

        try {
            $limit = (int) (CLI::getOption('limit') ?? 20);
            if ($limit <= 0) {
                $limit = 20;
            }

            $service = new M3HiuWorkflowService();
            $summary = $service->retryDue($limit);

            CLI::write('ABDM M3 HIU Retry Summary', 'yellow');
            CLI::write('Processed: ' . (int) ($summary['processed'] ?? 0));
            CLI::write('Success: ' . (int) ($summary['success'] ?? 0), 'green');
            CLI::write('Failed: ' . (int) ($summary['failed'] ?? 0), ((int) ($summary['failed'] ?? 0) > 0 ? 'red' : 'green'));
            CLI::write('Skipped: ' . (int) ($summary['skipped'] ?? 0));
        } finally {
            flock($lockFp, LOCK_UN);
            fclose($lockFp);
        }
    }
}

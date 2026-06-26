<?php

namespace App\Commands;

use App\Libraries\Abdm\Sync\AbdmSyncWorkerService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AbdmPushSync extends BaseCommand
{
    protected $group = 'ABDM';
    protected $name = 'abdm:push-sync';
    protected $description = 'Process ABDM M2 sync outbox and push records to gateway /api/v3/records/push.';
    protected $usage = 'abdm:push-sync [--limit 20] [--worker worker-name]';
    protected $arguments = [];
    protected $options = [
        '--limit' => 'Maximum outbox rows to process in this run.',
        '--worker' => 'Worker identifier for lock tracking.',
    ];

    public function run(array $params)
    {
        $limit = (int) (CLI::getOption('limit') ?? 20);
        if ($limit <= 0) {
            $limit = 20;
        }

        $worker = trim((string) (CLI::getOption('worker') ?? 'spark-abdm-push-sync'));
        if ($worker === '') {
            $worker = 'spark-abdm-push-sync';
        }

        $service = new AbdmSyncWorkerService();
        $summary = $service->process($limit, $worker);

        CLI::write('ABDM Push Sync', 'yellow');
        CLI::write('Processed: ' . (int) ($summary['processed'] ?? 0));
        CLI::write('Success: ' . (int) ($summary['success'] ?? 0), 'green');
        CLI::write('Failed: ' . (int) ($summary['failed'] ?? 0), ((int) ($summary['failed'] ?? 0) > 0 ? 'red' : 'green'));
        CLI::write('Dead: ' . (int) ($summary['dead'] ?? 0), ((int) ($summary['dead'] ?? 0) > 0 ? 'red' : 'green'));
        CLI::write('Skipped: ' . (int) ($summary['skipped'] ?? 0));
    }
}

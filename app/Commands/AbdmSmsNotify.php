<?php

namespace App\Commands;

use App\Libraries\Abdm\Sync\AbdmSmsNotifyService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AbdmSmsNotify extends BaseCommand
{
    protected $group = 'ABDM';
    protected $name = 'abdm:sms-notify';
    protected $description = 'Process ABDM deep-link SMS notification queue and retry on network recovery.';
    protected $usage = 'abdm:sms-notify [--limit 20] [--backfill] [--force] [--worker worker-name]';
    protected $arguments = [];
    protected $options = [
        '--limit'    => 'Maximum queue records to process in this run (default: 20).',
        '--backfill' => 'Scan recent patients without ABHA and queue missing SMS notifications.',
        '--force'    => 'Ignore next_retry_at backoff timers and process all pending/failed records now.',
        '--worker'   => 'Worker identifier for lock tracking (default: cron-abdm-sms).',
    ];

    public function run(array $params)
    {
        $limit = (int) (CLI::getOption('limit') ?? 20);
        if ($limit <= 0) {
            $limit = 20;
        }

        $worker = trim((string) (CLI::getOption('worker') ?? 'cron-abdm-sms'));
        if ($worker === '') {
            $worker = 'cron-abdm-sms';
        }

        $force = (bool) (CLI::getOption('force') ?? false);
        $doBackfill = (bool) (CLI::getOption('backfill') ?? false);

        $service = new AbdmSmsNotifyService();

        CLI::write('=== ABDM SMS Notification Queue Worker ===', 'yellow');

        if ($doBackfill) {
            CLI::write('Checking for un-notified recent patients...', 'cyan');
            $backfilled = $service->backfillUnnotifiedPatients($limit);
            CLI::write("Enqueued {$backfilled} new patient(s) for SMS notification.", 'green');
        }

        $summary = $service->processBatch($limit, $worker, $force);

        CLI::write('Processed: ' . (int) ($summary['processed'] ?? 0));
        CLI::write('Sent:      ' . (int) ($summary['sent'] ?? 0), 'green');
        CLI::write('Failed:    ' . (int) ($summary['failed'] ?? 0), ((int) ($summary['failed'] ?? 0) > 0 ? 'red' : 'green'));
        CLI::write('Dead:      ' . (int) ($summary['dead'] ?? 0), ((int) ($summary['dead'] ?? 0) > 0 ? 'red' : 'green'));
        CLI::write('Skipped:   ' . (int) ($summary['skipped'] ?? 0));

        $counters = $service->getCounters();
        CLI::write(sprintf(
            'Queue Totals: Pending: %d | Sent/Done: %d | Failed/Retry: %d | Dead: %d',
            $counters['pending'],
            $counters['done'],
            $counters['failed'],
            $counters['dead']
        ), 'light_gray');
    }
}

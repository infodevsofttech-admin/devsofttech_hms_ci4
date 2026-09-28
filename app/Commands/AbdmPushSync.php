<?php

namespace App\Commands;

use App\Libraries\Abdm\Sync\AbdmSyncWorkerService;
use App\Libraries\Abdm\Sync\AbdmTaskBoardSyncService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AbdmPushSync extends BaseCommand
{
    protected $group = 'ABDM';
    protected $name = 'abdm:push-sync';
    protected $description = 'Process ABDM M2 sync outbox and ABDM Work Task Board care-context linking.';
    protected $usage = 'abdm:push-sync [--limit 20] [--worker worker-name] [--taskboard-only]';
    protected $arguments = [];
    protected $options = [
        '--limit'          => 'Maximum outbox and taskboard rows to process in this run.',
        '--worker'         => 'Worker identifier for lock tracking.',
        '--taskboard-only' => 'Skip outbox queue and only sync ABDM Work Task Board records.',
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

        $taskboardOnly = CLI::getOption('taskboard-only') !== null;

        if (! $taskboardOnly) {
            $service = new AbdmSyncWorkerService();
            $summary = $service->process($limit, $worker);

            CLI::write('ABDM Push Sync (Outbox)', 'yellow');
            CLI::write('Processed: ' . (int) ($summary['processed'] ?? 0));
            CLI::write('Success: ' . (int) ($summary['success'] ?? 0), 'green');
            CLI::write('Failed: ' . (int) ($summary['failed'] ?? 0), ((int) ($summary['failed'] ?? 0) > 0 ? 'red' : 'green'));
            CLI::write('Dead: ' . (int) ($summary['dead'] ?? 0), ((int) ($summary['dead'] ?? 0) > 0 ? 'red' : 'green'));
            CLI::write('Skipped: ' . (int) ($summary['skipped'] ?? 0));
        }

        // Also sync ABDM Work Task Board records (OPD Consults & Work Queue items)
        CLI::newLine();
        CLI::write('ABDM Work Task Board Sync', 'cyan');
        $tbService = new AbdmTaskBoardSyncService();
        $tbSummary = $tbService->syncAll($limit);

        CLI::write('OPD Consults -> Eligible: ' . ($tbSummary['opd']['eligible'] ?? 0) . ' | Linked: ' . ($tbSummary['opd']['linked'] ?? 0) . ' | Cooling: ' . ($tbSummary['opd']['cooling'] ?? 0) . ' | Failed: ' . ($tbSummary['opd']['failed'] ?? 0), 'green');
        CLI::write('Work Tasks   -> Eligible: ' . ($tbSummary['tasks']['eligible'] ?? 0) . ' | Linked: ' . ($tbSummary['tasks']['linked'] ?? 0) . ' | Cooling: ' . ($tbSummary['tasks']['cooling'] ?? 0) . ' | Failed: ' . ($tbSummary['tasks']['failed'] ?? 0), 'green');
    }
}

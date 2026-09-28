<?php

namespace App\Commands;

use App\Libraries\Abdm\Sync\AbdmTaskBoardSyncService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AbdmTaskBoardSync extends BaseCommand
{
    protected $group = 'ABDM';
    protected $name = 'abdm:taskboard-sync';
    protected $description = 'Automate care-context linking and push for ABDM Work Task Board records.';
    protected $usage = 'abdm:taskboard-sync [--limit 20] [--type all|opd|tasks] [--dry-run]';
    protected $arguments = [];
    protected $options = [
        '--limit'   => 'Maximum records to process per category (default: 20).',
        '--type'    => 'Which records to process: all, opd, or tasks (default: all).',
        '--dry-run' => 'Inspect eligible records without linking/pushing.',
    ];

    public function run(array $params)
    {
        $limit = (int) (CLI::getOption('limit') ?? 20);
        if ($limit <= 0) {
            $limit = 20;
        }

        $type = strtolower(trim((string) (CLI::getOption('type') ?? 'all')));
        if (! in_array($type, ['all', 'opd', 'tasks'], true)) {
            $type = 'all';
        }

        $dryRun = CLI::getOption('dry-run') !== null;

        CLI::write('========================================================', 'cyan');
        CLI::write(' ABDM Work Task Board - Automated Care Context Sync' . ($dryRun ? ' (DRY RUN)' : ''), 'yellow');
        CLI::write('========================================================', 'cyan');
        CLI::write('Time: ' . date('Y-m-d H:i:s') . ' | Limit: ' . $limit . ' | Type: ' . $type);

        $service = new AbdmTaskBoardSyncService();

        // 1. Process OPD Consult / Prescription Records
        if ($type === 'all' || $type === 'opd') {
            CLI::newLine();
            CLI::write('--- OPD Consult / Prescription Records ---', 'white');
            $opdSummary = $service->syncOpdConsultRecords($limit, $dryRun);

            CLI::write('Eligible : ' . $opdSummary['eligible']);
            CLI::write('Linked   : ' . $opdSummary['linked'], 'green');
            CLI::write('Cooling  : ' . ($opdSummary['cooling'] ?? 0), 'cyan');
            CLI::write('Failed   : ' . $opdSummary['failed'], $opdSummary['failed'] > 0 ? 'red' : 'green');
            CLI::write('Skipped  : ' . $opdSummary['skipped']);

            foreach ($opdSummary['details'] as $item) {
                $statusColor = match ($item['status'] ?? '') {
                    'linked'  => 'green',
                    'failed'  => 'red',
                    'cooling' => 'cyan',
                    default   => 'yellow',
                };
                $coolingNote = (isset($item['status']) && $item['status'] === 'cooling')
                    ? ' [Cooling: ' . ($item['remaining_minutes'] ?? 0) . 'm left, auto-link at ' . ($item['auto_link_at'] ?? '') . ']'
                    : '';
                $extra = isset($item['queue_id']) && $item['queue_id'] !== ''
                    ? ' [Queue: ' . $item['queue_id'] . (isset($item['bridge_record_id']) ? ' Bridge: #' . $item['bridge_record_id'] : '') . ']'
                    : (isset($item['error']) ? ' [Error: ' . $item['error'] . ']' : '');
                CLI::write('  OPD #' . ($item['opd_id'] ?? 0) . ' (' . ($item['patient'] ?? '') . ') -> ' . ($item['status'] ?? '') . $coolingNote . $extra, $statusColor);
            }
        }

        // 2. Process Open Work Tasks
        if ($type === 'all' || $type === 'tasks') {
            CLI::newLine();
            CLI::write('--- ABDM Work Task Queue Items ---', 'white');
            $taskSummary = $service->syncOpenWorkTasks($limit, $dryRun);

            CLI::write('Eligible : ' . $taskSummary['eligible']);
            CLI::write('Linked   : ' . $taskSummary['linked'], 'green');
            CLI::write('Cooling  : ' . ($taskSummary['cooling'] ?? 0), 'cyan');
            CLI::write('Failed   : ' . $taskSummary['failed'], $taskSummary['failed'] > 0 ? 'red' : 'green');
            CLI::write('Skipped  : ' . $taskSummary['skipped']);

            foreach ($taskSummary['details'] as $item) {
                $statusColor = match ($item['status'] ?? '') {
                    'linked'  => 'green',
                    'failed'  => 'red',
                    'cooling' => 'cyan',
                    default   => 'yellow',
                };
                $coolingNote = (isset($item['status']) && $item['status'] === 'cooling')
                    ? ' [Cooling: ' . ($item['remaining_minutes'] ?? 0) . 'm left, auto-link at ' . ($item['auto_link_at'] ?? '') . ']'
                    : '';
                $extra = isset($item['queue_id']) && $item['queue_id'] !== ''
                    ? ' [Queue: ' . $item['queue_id'] . ']'
                    : (isset($item['error']) ? ' [Error: ' . $item['error'] . ']' : '');
                CLI::write('  Task #' . ($item['task_id'] ?? 0) . ' (' . ($item['task_type'] ?? '') . ') -> ' . ($item['status'] ?? '') . $coolingNote . $extra, $statusColor);
            }
        }

        CLI::newLine();
        CLI::write('ABDM Task Board sync run completed.', 'cyan');
    }
}

<?php

namespace App\Commands;

use App\Libraries\BridgeSyncService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class BridgeSync extends BaseCommand
{
    protected $group = 'Bridge';
    protected $name = 'bridge:sync';
    protected $description = 'Process queued bridge sync records and push to central server.';
    protected $usage = 'bridge:sync [--limit 50]';
    protected $arguments = [];
    protected $options = [
        '--limit' => 'Maximum records to process per run (default: 50).',
    ];

    public function run(array $params)
    {
        $limit = (int) (CLI::getOption('limit') ?? 50);
        if ($limit <= 0) {
            $limit = 50;
        }

        $service = new BridgeSyncService();
        $summary = $service->processPending($limit, 'spark-bridge-sync');

        CLI::write('Bridge Sync Result', 'yellow');
        CLI::write('Processed: ' . (int) ($summary['processed'] ?? 0));
        CLI::write('Sent: ' . (int) ($summary['sent'] ?? 0), 'green');
        CLI::write('Failed: ' . (int) ($summary['failed'] ?? 0), ((int) ($summary['failed'] ?? 0) > 0 ? 'red' : 'green'));
        CLI::write('Skipped: ' . (int) ($summary['skipped'] ?? 0));
        CLI::write('Message: ' . (string) ($summary['message'] ?? ''));
    }
}

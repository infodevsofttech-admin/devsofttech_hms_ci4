<?php

namespace App\Commands;

use App\Libraries\BridgeSyncService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class BridgeCleanupAbhaVerified extends BaseCommand
{
    protected $group = 'Bridge';
    protected $name = 'bridge:cleanup-abha-verified';
    protected $description = 'Mark legacy bridge queue rows as skipped when event requires ABHA-verified patient but patient is not verified.';
    protected $usage = 'bridge:cleanup-abha-verified [--limit 500] [--dry-run]';
    protected $arguments = [];
    protected $options = [
        '--limit' => 'Maximum rows to inspect (default: 500).',
        '--dry-run' => 'Show what would be skipped without updating rows.',
    ];

    public function run(array $params)
    {
        $limit = (int) (CLI::getOption('limit') ?? 500);
        if ($limit <= 0) {
            $limit = 500;
        }

        $dryRun = CLI::getOption('dry-run') !== null;

        $service = new BridgeSyncService();
        $summary = $service->cleanupAbhaVerifiedOnlyQueue($limit, $dryRun);

        CLI::write('Bridge ABHA Cleanup Summary' . ($dryRun ? ' (dry-run)' : ''), 'yellow');
        CLI::write('Scanned: ' . (int) ($summary['scanned'] ?? 0));
        CLI::write('Marked skipped: ' . (int) ($summary['marked_skipped'] ?? 0), 'green');
        CLI::write('Kept: ' . (int) ($summary['kept'] ?? 0));
        CLI::write('Decode errors: ' . (int) ($summary['decode_errors'] ?? 0), ((int) ($summary['decode_errors'] ?? 0) > 0 ? 'red' : 'green'));
    }
}

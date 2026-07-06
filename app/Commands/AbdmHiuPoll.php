<?php

namespace App\Commands;

use App\Libraries\Abdm\M3HiuWorkflowService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AbdmHiuPoll extends BaseCommand
{
    protected $group = 'ABDM';
    protected $name = 'abdm:hiu-poll';
    protected $description = 'Poll NAT-safe gateway endpoints for consent reconciliation and decrypted HI data.';
    protected $usage = 'abdm:hiu-poll [--limit 30]';
    protected $arguments = [];
    protected $options = [
        '--limit' => 'Maximum recent workflow roots to poll in one run.',
    ];

    public function run(array $params)
    {
        $limit = (int) (CLI::getOption('limit') ?? 30);
        if ($limit <= 0) {
            $limit = 30;
        }

        $service = new M3HiuWorkflowService();
        $summary = $service->pollNatGateway($limit);

        CLI::write('ABDM HIU NAT Poll Summary', 'yellow');
        CLI::write('Processed: ' . (int) ($summary['processed'] ?? 0));
        CLI::write('Consent updates: ' . (int) ($summary['consent_updates'] ?? 0), 'green');
        CLI::write('Data updates: ' . (int) ($summary['data_updates'] ?? 0), 'green');
        CLI::write('Failed: ' . (int) ($summary['failed'] ?? 0), ((int) ($summary['failed'] ?? 0) > 0 ? 'red' : 'green'));
        CLI::write('Skipped: ' . (int) ($summary['skipped'] ?? 0));
    }
}

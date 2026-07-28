<?php

namespace App\Commands;

use App\Libraries\Abdm\M3HiuWorkflowService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * One-off diagnostic: manually call consent_reconcile using an ABDM
 * consent_request_id (the umbrella id covering potentially multiple linked
 * HIP facility consent artifacts), to check whether the bridge's
 * /v1/hiu/consent/status endpoint can expose all artifacts under one
 * consent request (vs. only a single mapped artifact per local request_id).
 *
 * Usage:
 *   php spark abdm:hiu-reconcile-by-request --consent_request_id <id> --abha_address <abha>
 */
class AbdmHiuReconcileByRequest extends BaseCommand
{
    protected $group = 'ABDM';
    protected $name = 'abdm:hiu-reconcile-by-request';
    protected $description = 'Manually run consent_reconcile using an ABDM consent_request_id.';
    protected $usage = 'abdm:hiu-reconcile-by-request --consent_request_id <id> --abha_address <abha>';
    protected $options = [
        '--consent_request_id' => 'ABDM consent_request_id (umbrella id) to reconcile.',
        '--abha_address'       => 'Patient ABHA address, e.g. someone@sbx.',
        '--hfr_id'             => 'Optional HFR id override.',
    ];

    public function run(array $params)
    {
        $consentRequestId = trim((string) (CLI::getOption('consent_request_id') ?? ''));
        $abhaAddress = trim((string) (CLI::getOption('abha_address') ?? ''));
        $hfrId = trim((string) (CLI::getOption('hfr_id') ?? ''));

        if ($consentRequestId === '' || $abhaAddress === '') {
            CLI::error('Both --consent_request_id and --abha_address are required.');
            return;
        }

        $payload = [
            'abdm_consent_request_id' => $consentRequestId,
            'abha_address' => $abhaAddress,
        ];
        if ($hfrId !== '') {
            $payload['hfr_id'] = $hfrId;
        }

        $service = new M3HiuWorkflowService();
        $result = $service->runOperation('consent_reconcile', $payload);

        CLI::write('=== consent_reconcile result ===', 'yellow');
        CLI::write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}

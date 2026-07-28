<?php

namespace App\Commands;

use App\Libraries\Abdm\M3HiuWorkflowService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * One-off diagnostic/repair command: manually trigger a data_fetch for a
 * specific ABDM consent artifact id, bypassing the normal consent_request ->
 * consent_reconcile discovery chain. Useful when the bridge/gateway created
 * multiple consent artifacts under one consent request (one per linked HIP
 * facility) and HMS only captured/polled a single artifact id.
 *
 * Usage:
 *   php spark abdm:hiu-fetch-by-consent --consent_id=<artifact_id> --abha_address=<abha> [--hfr_id=<hfr>]
 */
class AbdmHiuFetchByConsent extends BaseCommand
{
    protected $group = 'ABDM';
    protected $name = 'abdm:hiu-fetch-by-consent';
    protected $description = 'Manually run data_fetch for a specific ABDM consent artifact id.';
    protected $usage = 'abdm:hiu-fetch-by-consent --consent_id=<artifact_id> --abha_address=<abha> [--hfr_id=<hfr>]';
    protected $options = [
        '--consent_id'    => 'ABDM consent artifact id to fetch decrypted data for.',
        '--abha_address'  => 'Patient ABHA address, e.g. someone@sbx.',
        '--hfr_id'        => 'Optional HFR id override (defaults to hospital_setting.ABDM_HFR_ID).',
    ];

    public function run(array $params)
    {
        $consentId = trim((string) (CLI::getOption('consent_id') ?? ''));
        $abhaAddress = trim((string) (CLI::getOption('abha_address') ?? ''));
        $hfrId = trim((string) (CLI::getOption('hfr_id') ?? ''));

        if ($consentId === '' || $abhaAddress === '') {
            CLI::error('Both --consent_id and --abha_address are required.');
            return;
        }

        $payload = [
            'consent_id' => $consentId,
            'abdm_consent_artifact_id' => $consentId,
            'abha_address' => $abhaAddress,
        ];
        if ($hfrId !== '') {
            $payload['hfr_id'] = $hfrId;
        }

        $service = new M3HiuWorkflowService();
        $result = $service->runOperation('data_fetch', $payload);

        CLI::write('=== data_fetch result ===', 'yellow');
        CLI::write(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}

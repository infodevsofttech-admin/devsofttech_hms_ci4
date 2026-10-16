<?php

namespace App\Commands;

use App\Libraries\Abdm\Sync\AbdmSyncWorkerService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ResyncDischarge extends BaseCommand
{
    protected $group = 'ABDM';
    protected $name = 'abdm:resync-discharge';
    protected $description = 'Resync IPD Discharge FHIR Bundle for ABDM';

    public function run(array $params)
    {
        $ipdId = (int) ($params[0] ?? CLI::getOption('ipd') ?? 3222);
        CLI::write("Triggering discharge sync for IPD: {$ipdId}...", 'yellow');

        $ctrl = new \App\Controllers\Ipd_discharge();
        $request = \Config\Services::request();
        $response = \Config\Services::response();
        $logger = \Config\Services::logger();
        $ctrl->initController($request, $response, $logger);

        $ctrl->enqueueIpdDischargeSync($ipdId);
        CLI::write("Discharge sync enqueued successfully.", 'green');

        CLI::write("Processing ABDM outbox...", 'yellow');
        $service = new AbdmSyncWorkerService();
        $summary = $service->process(10, 'spark-resync-discharge');
        CLI::write("Outbox processed: " . json_encode($summary), 'green');
    }
}

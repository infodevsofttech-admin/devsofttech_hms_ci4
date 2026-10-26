<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AbdmSyncDrugs extends BaseCommand
{
    protected $group       = 'ABDM';
    protected $name        = 'abdm:sync-drugs';
    protected $description = 'Sync standard essential medicines from ABDM Bridge sync-pack to local med_product_master';
    protected $usage       = 'abdm:sync-drugs [--pages=6] [--limit=500] [--dry-run]';
    protected $options     = [
        '--pages'   => 'Number of pages to sync (default: 6)',
        '--limit'   => 'Items per page (default: 500, max: 1000)',
        '--dry-run' => 'Simulate sync without writing to database',
    ];

    public function run(array $params)
    {
        $pages = (int) ($params['pages'] ?? CLI::getOption('pages') ?? 6);
        $limit = (int) ($params['limit'] ?? CLI::getOption('limit') ?? 500);
        $dryRun = (bool) (CLI::getOption('dry-run') ?? false);

        if ($pages <= 0) {
            $pages = 6;
        }
        if ($limit <= 0 || $limit > 1000) {
            $limit = 500;
        }

        CLI::write("==================================================", 'cyan');
        CLI::write(" ABDM Pharmacy Drug Master Sync (Sync-Pack)", 'yellow');
        CLI::write(" Pages: {$pages} | Limit per page: {$limit} | Mode: " . ($dryRun ? 'DRY-RUN' : 'LIVE'), 'cyan');
        CLI::write("==================================================\n", 'cyan');

        $db = \Config\Database::connect();
        if (! $db->tableExists('med_product_master')) {
            CLI::error("Table 'med_product_master' does not exist in database.");
            return;
        }

        $connector = new \App\Libraries\Abdm\EAtriaBridgeConnector();

        $totalFetched = 0;
        $totalInserted = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $now = date('Y-m-d H:i:s');

        for ($p = 1; $p <= $pages; $p++) {
            CLI::print("Fetching page {$p} of {$pages}... ", 'yellow');
            try {
                $response = $connector->drugsSyncPack($p, $limit);
            } catch (\Throwable $e) {
                CLI::write("FAILED: " . $e->getMessage(), 'red');
                break;
            }

            if (($response['ok'] ?? 0) !== 1) {
                CLI::write("FAILED: " . ($response['error_text'] ?? $response['message'] ?? 'API error'), 'red');
                break;
            }

            $items = $response['items'] ?? [];
            $count = count($items);
            CLI::write("OK ({$count} items)", 'green');

            if ($count === 0) {
                CLI::write("No more items returned. Finishing sync.", 'yellow');
                break;
            }

            $totalFetched += $count;

            if ($dryRun) {
                continue;
            }

            foreach ($items as $item) {
                $identifier = trim((string) ($item['identifier'] ?? ''));
                $displayName = trim((string) ($item['display_name'] ?? ''));
                $brandName = trim((string) ($item['brand_name'] ?? ''));
                $genericName = trim((string) ($item['generic_name'] ?? ''));
                $dosageForm = trim((string) ($item['short_formulation'] ?? $item['formulation'] ?? $item['dosage_form'] ?? ''));
                $manufacturer = trim((string) ($item['manufacturer_name'] ?? $item['company_name'] ?? ''));
                $type = trim((string) ($item['type'] ?? 'generic'));

                if ($identifier === '' && $displayName === '') {
                    $totalSkipped++;
                    continue;
                }

                // Clean Product Name
                $itemName = $this->deriveCleanItemName($displayName, $brandName, $dosageForm);
                if ($itemName === '') {
                    $itemName = $displayName ?: $genericName;
                }

                // Check if already exists by abdm_drug_identifier
                $existing = null;
                if ($identifier !== '') {
                    $existing = $db->table('med_product_master')
                        ->where('abdm_drug_identifier', $identifier)
                        ->get()
                        ->getRowArray();
                }

                // If not found by identifier, check by exact item_name
                if (! $existing && $itemName !== '') {
                    $existing = $db->table('med_product_master')
                        ->where('item_name', $itemName)
                        ->get()
                        ->getRowArray();
                }

                $recordData = [
                    'item_name'                => $itemName,
                    'genericname'              => $genericName ?: $displayName,
                    'formulation'              => $dosageForm ?: 'Tablet',
                    'company_name'             => $manufacturer,
                    'abdm_drug_type'           => $type ?: 'generic',
                    'abdm_drug_identifier'     => $identifier,
                    'abdm_drug_display'        => $displayName,
                    'abdm_drug_generic'        => $genericName,
                    'abdm_drug_payload_json'   => json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'abdm_drug_last_synced_at' => $now,
                    'is_continue'              => 1,
                    'batch_applicable'         => 1,
                    'exp_date_applicable'      => 1,
                    'high_risk'                => ! empty($item['high_risk']) ? 1 : 0,
                ];

                if (! empty($item['hsn_code'])) {
                    $recordData['HSNCODE'] = (string) $item['hsn_code'];
                }
                if (isset($item['cgst_percent']) && is_numeric($item['cgst_percent'])) {
                    $recordData['CGST_per'] = (float) $item['cgst_percent'];
                }
                if (isset($item['sgst_percent']) && is_numeric($item['sgst_percent'])) {
                    $recordData['SGST_per'] = (float) $item['sgst_percent'];
                }

                if ($existing) {
                    $db->table('med_product_master')->where('id', $existing['id'])->update($recordData);
                    $totalUpdated++;
                } else {
                    $recordData['insert_by'] = 'abdm_sync';
                    $db->table('med_product_master')->insert($recordData);
                    $totalInserted++;
                }
            }

            $pagination = $response['pagination'] ?? [];
            if (! empty($pagination) && empty($pagination['has_next'])) {
                CLI::write("Reached last page.", 'yellow');
                break;
            }
        }

        CLI::write("\n--------------------------------------------------", 'cyan');
        CLI::write(" Sync Finished Successfully!", 'green');
        CLI::write(" Total Fetched:  {$totalFetched}", 'cyan');
        CLI::write(" Total Inserted: {$totalInserted}", 'green');
        CLI::write(" Total Updated:  {$totalUpdated}", 'yellow');
        CLI::write(" Total Skipped:  {$totalSkipped}", 'white');
        CLI::write("--------------------------------------------------\n", 'cyan');
    }

    private function deriveCleanItemName(string $displayName, string $brandName, string $formulation): string
    {
        $label = trim($displayName);
        if ($label === '') {
            return $brandName;
        }

        if (preg_match('/^([^()]+?)\s*\(([^)]+)\)\s*(.*)$/i', $label, $m)) {
            $before = trim($m[1]);
            $after = trim($m[3]);
            $cleanAfter = preg_replace('/\b(oral|route|for\s+oral\s+use)\b/i', '', $after);
            $cleanAfter = preg_replace('/\s+/', ' ', trim($cleanAfter));
            if ($formulation !== '' && $cleanAfter !== '') {
                $re = '/' . preg_quote($formulation, '/') . '/i';
                $cleanAfter = preg_replace($re, ucfirst($formulation), $cleanAfter);
            }
            if ($cleanAfter !== '') {
                return trim($before . ' ' . $cleanAfter);
            }
            return $before;
        }

        return $label;
    }
}

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
        $pages = 6;
        $limit = 500;
        $dryRun = false;

        $optPages = CLI::getOption('pages');
        if ($optPages !== null && is_numeric($optPages)) {
            $pages = (int) $optPages;
        } elseif (! empty($params[0]) && is_numeric($params[0])) {
            $pages = (int) $params[0];
        }

        $optLimit = CLI::getOption('limit');
        if ($optLimit !== null && is_numeric($optLimit)) {
            $limit = (int) $optLimit;
        } elseif (! empty($params[1]) && is_numeric($params[1])) {
            $limit = (int) $params[1];
        }

        if (CLI::getOption('dry-run') !== null || in_array('--dry-run', $params, true) || in_array('dry-run', $params, true)) {
            $dryRun = true;
        }

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

        // Determine item_name column length & try to expand if needed
        $itemNameMaxLength = 64;
        try {
            $fields = $db->getFieldData('med_product_master');
            foreach ($fields as $f) {
                if ($f->name === 'item_name' && ! empty($f->max_length)) {
                    $itemNameMaxLength = (int) $f->max_length;
                    break;
                }
            }

            if ($itemNameMaxLength < 191 && ! $dryRun) {
                try {
                    $db->query("ALTER TABLE med_product_master MODIFY COLUMN item_name VARCHAR(191) NOT NULL");
                    $itemNameMaxLength = 191;
                    CLI::write("Expanded 'item_name' column to VARCHAR(191).", 'green');
                } catch (\Throwable $e) {
                    // Table alter may be restricted; continue with existing max length
                }
            }
        } catch (\Throwable $e) {
            // Ignore
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
                $itemName = $this->deriveCleanItemName($displayName, $brandName, $dosageForm, $itemNameMaxLength);
                if ($itemName === '') {
                    $itemName = mb_substr($displayName ?: $genericName, 0, $itemNameMaxLength);
                }

                // Check if already exists by abdm_drug_identifier
                $existing = null;
                if ($identifier !== '') {
                    $existing = $db->table('med_product_master')
                        ->where('abdm_drug_identifier', $identifier)
                        ->get()
                        ->getRowArray();
                }

                // If not found by identifier, check for collision or match by item_name
                if (! $existing && $itemName !== '') {
                    $candidate = $itemName;
                    $counter = 1;
                    while (true) {
                        $collision = $db->table('med_product_master')
                            ->where('item_name', $candidate)
                            ->get()
                            ->getRowArray();

                        if (! $collision) {
                            $itemName = $candidate;
                            break;
                        }

                        // Existing row matches this drug's identifier or has no identifier yet
                        if (! empty($collision['abdm_drug_identifier']) && $collision['abdm_drug_identifier'] === $identifier) {
                            $existing = $collision;
                            $itemName = $candidate;
                            break;
                        }
                        if (empty($collision['abdm_drug_identifier'])) {
                            $existing = $collision;
                            $itemName = $candidate;
                            break;
                        }

                        // Another record already has this exact name; make name distinct
                        $suffix = ' (' . substr($identifier, -4) . ($counter > 1 ? "-{$counter}" : '') . ')';
                        $allowedLen = max(10, $itemNameMaxLength - strlen($suffix));
                        $candidate = mb_substr($itemName, 0, $allowedLen) . $suffix;
                        $counter++;

                        if ($counter > 10) {
                            $itemName = $candidate;
                            break;
                        }
                    }
                }

                $recordData = [
                    'item_name'                => $itemName,
                    'genericname'              => mb_substr($genericName ?: $displayName, 0, 255),
                    'formulation'              => mb_substr($dosageForm ?: 'Tablet', 0, 50),
                    'company_name'             => mb_substr($manufacturer, 0, 100),
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

                try {
                    if ($existing) {
                        $db->table('med_product_master')->where('id', $existing['id'])->update($recordData);
                        $totalUpdated++;
                    } else {
                        $recordData['insert_by'] = 'abdm_sync';
                        $db->table('med_product_master')->insert($recordData);
                        $totalInserted++;
                    }
                } catch (\Throwable $itemErr) {
                    // Safe item-level catch: avoid halting the entire sync on duplicate entry or constraint error
                    $totalSkipped++;
                    log_message('warning', "[abdm:sync-drugs] Skipped item {$identifier} ({$itemName}): " . $itemErr->getMessage());
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

    private function deriveCleanItemName(string $displayName, string $brandName, string $formulation, int $maxLength = 191): string
    {
        $label = trim($displayName);
        if ($label === '') {
            $label = trim($brandName);
        }
        if ($label === '') {
            return '';
        }

        // Pattern 1: Brand (Generic Salts...) Strength Form
        // e.g. "Droclox CV (amoxicillin and clavulanate potassium) 500 mg + 125 mg oral tablet"
        // => "Droclox CV 500 mg + 125 mg Tablet"
        if (preg_match('/^([^(]+?)\s*\(([^)]+)\)\s*(.*)$/i', $label, $m)) {
            $before = trim($m[1]);
            $inside = trim($m[2]);
            $after  = trim($m[3]);

            if ($after !== '' && ! preg_match('/^(as|equivalent\s+to)\b/i', $before)) {
                $cleanAfter = preg_replace('/\b(oral\s+route|oral\s+use|for\s+oral\s+use|oral)\b/i', '', $after);
                $cleanAfter = preg_replace('/\s+/', ' ', trim($cleanAfter));
                if ($formulation !== '' && $cleanAfter !== '') {
                    $re = '/' . preg_quote($formulation, '/') . '/i';
                    $cleanAfter = preg_replace($re, ucfirst($formulation), $cleanAfter);
                }
                $candidate = trim($before . ' ' . $cleanAfter);
                return mb_substr($candidate, 0, $maxLength);
            }
        }

        // Pattern 2: Generic description with "(as ...)" or "(equivalent to ...)" chemical salt
        // e.g. "Amoxicillin 500 mg and Clavulanic acid (as clavulanate potassium) 125 mg ..."
        $clean = preg_replace('/\s*\((?:as|equivalent\s+to)[^)]+\)/i', '', $label);
        $clean = preg_replace('/\b(oral\s+route|oral\s+use|for\s+oral\s+use|oral)\b/i', '', $clean);
        $clean = preg_replace('/\s+/', ' ', trim($clean));

        if ($formulation !== '' && $clean !== '') {
            $re = '/' . preg_quote($formulation, '/') . '/i';
            $clean = preg_replace($re, ucfirst($formulation), $clean);
        }

        return mb_substr($clean, 0, $maxLength);
    }
}

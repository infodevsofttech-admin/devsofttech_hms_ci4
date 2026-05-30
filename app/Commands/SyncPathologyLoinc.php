<?php

namespace App\Commands;

use App\Libraries\Abdm\EAtriaBridgeConnector;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Spark command: abdm:sync-pathology-loinc
 *
 * Fetches LOINC codes from the Bridge API pathology terminology endpoints
 * and maps them to the local lab_repo (panel) and lab_tests (component) tables.
 *
 * Matching strategy:
 *   1. For lab_repo: case-insensitive exact match on Title vs bridge test_name,
 *      then normalised fuzzy match (strip spaces/punctuation, compare words).
 *   2. For lab_tests: same matching logic on Test vs component_test_name,
 *      scoped to the matched panel.
 *
 * Usage:
 *   php spark abdm:sync-pathology-loinc
 *   php spark abdm:sync-pathology-loinc --dry-run
 *   php spark abdm:sync-pathology-loinc --since=2025-01-01T00:00:00Z
 *   php spark abdm:sync-pathology-loinc --category=PATHOLOGY
 *   php spark abdm:sync-pathology-loinc --limit=500
 */
class SyncPathologyLoinc extends BaseCommand
{
    protected $group       = 'Bridge';
    protected $name        = 'abdm:sync-pathology-loinc';
    protected $description = 'Sync LOINC codes from Bridge API into lab_tests and lab_repo tables.';
    protected $usage       = 'abdm:sync-pathology-loinc [options]';
    protected $arguments   = [];
    protected $options     = [
        '--dry-run'  => 'Print matches without updating the database.',
        '--since'    => 'ISO-8601 datetime for incremental sync (e.g. 2025-01-01T00:00:00Z).',
        '--category' => 'Filter bridge masters by sub_category (PATHOLOGY|BIOPSY). Default: all.',
        '--limit'    => 'Items per API page request. Default: 200.',
    ];

    /** @var \CodeIgniter\Database\BaseConnection */
    private $db;

    /** @var bool */
    private bool $dryRun = false;

    /** @var int */
    private int $masterMatched  = 0;
    private int $masterSkipped  = 0;
    private int $compMatched    = 0;
    private int $compSkipped    = 0;

    public function run(array $params): void
    {
        $this->dryRun  = (bool) CLI::getOption('dry-run');
        $since         = (string) (CLI::getOption('since') ?? '');
        $category      = (string) (CLI::getOption('category') ?? '');
        $limit         = (int) (CLI::getOption('limit') ?? 200);

        if ($limit <= 0) {
            $limit = 200;
        }

        $this->db = \Config\Database::connect();

        if ($this->dryRun) {
            CLI::write('[DRY-RUN] No database changes will be made.', 'yellow');
        }

        CLI::write('=== Syncing Bridge Pathology LOINC Codes ===', 'cyan');

        try {
            $connector = new EAtriaBridgeConnector();
        } catch (\Throwable $e) {
            CLI::error('Failed to initialise Bridge connector: ' . $e->getMessage());

            return;
        }

        // Load local tables once
        $localRepos  = $this->loadLocalRepos();
        $localTests  = $this->loadLocalTests();

        CLI::write('Local lab_repo records : ' . count($localRepos));
        CLI::write('Local lab_tests records: ' . count($localTests));
        CLI::write('');

        // --- Step 1: Fetch all panel masters and match to lab_repo ---
        CLI::write('--- Phase 1: Panel Masters → lab_repo ---', 'yellow');
        $masters = $this->fetchAllPages(
            $connector,
            'masters',
            ['category' => $category, 'since' => $since, 'limit' => $limit]
        );
        CLI::write('Bridge masters fetched: ' . count($masters));

        // Build lookup: normalised name → master item
        $masterByName = [];
        foreach ($masters as $item) {
            $key = $this->normalise((string) ($item['test_name'] ?? ''));
            if ($key !== '') {
                $masterByName[$key] = $item;
            }
        }

        foreach ($localRepos as $repo) {
            $this->matchAndUpdateRepo($repo, $masterByName);
        }

        CLI::write("Panel matches: {$this->masterMatched}  |  skipped (no match): {$this->masterSkipped}");
        CLI::write('');

        // --- Step 2: For each matched panel, fetch components → lab_tests ---
        CLI::write('--- Phase 2: Components → lab_tests ---', 'yellow');

        // Group local tests by repo id for fast lookup
        $testsByRepo = [];
        foreach ($localTests as $test) {
            $rid = (int) ($test['mstRepoKey'] ?? 0);
            $testsByRepo[$rid][] = $test;
        }

        foreach ($localRepos as $repo) {
            $panelName = (string) ($repo['Title'] ?? '');
            if ($panelName === '') {
                continue;
            }

            $components = $this->fetchAllPages(
                $connector,
                'components',
                ['parent_test' => $panelName, 'since' => $since, 'limit' => $limit]
            );

            if (empty($components)) {
                // Try with the bridge display_name if we synced one
                $bridgeName = (string) ($repo['_bridge_test_name'] ?? '');
                if ($bridgeName !== '' && $bridgeName !== $panelName) {
                    $components = $this->fetchAllPages(
                        $connector,
                        'components',
                        ['parent_test' => $bridgeName, 'since' => $since, 'limit' => $limit]
                    );
                }
            }

            if (empty($components)) {
                continue;
            }

            $repoTests = $testsByRepo[(int) $repo['mstRepoKey']] ?? [];
            if (empty($repoTests)) {
                continue;
            }

            // Build lookup by normalised component name
            $compByName = [];
            foreach ($components as $comp) {
                $key = $this->normalise((string) ($comp['component_test_name'] ?? ''));
                if ($key !== '') {
                    $compByName[$key] = $comp;
                }
            }

            foreach ($repoTests as $test) {
                $this->matchAndUpdateTest($test, $compByName);
            }
        }

        CLI::write("Component matches: {$this->compMatched}  |  skipped (no match): {$this->compSkipped}");
        CLI::write('');
        CLI::write('Done.', 'green');
    }

    // -------------------------------------------------------------------------
    // Match + Update helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $repo */
    private function matchAndUpdateRepo(array $repo, array $masterByName): void
    {
        $title = (string) ($repo['Title'] ?? '');
        if ($title === '') {
            return;
        }

        $key  = $this->normalise($title);
        $item = $masterByName[$key] ?? null;

        // Fuzzy fallback: try partial word match
        if ($item === null) {
            $item = $this->fuzzyMatch($title, $masterByName);
        }

        if ($item === null) {
            $this->masterSkipped++;

            return;
        }

        $loincCode    = (string) ($item['code'] ?? '');
        $bridgeTestName = (string) ($item['test_name'] ?? '');

        // Stash bridge name so phase 2 can reuse it
        $repo['_bridge_test_name'] = $bridgeTestName;

        if ($this->dryRun) {
            CLI::write("  [DRY] lab_repo #{$repo['mstRepoKey']} '{$title}' → LOINC: {$loincCode} (bridge: '{$bridgeTestName}')");
        } else {
            $this->db->table('lab_repo')->where('mstRepoKey', $repo['mstRepoKey'])->update([
                'loinc_code'      => $loincCode,
                'loinc_synced_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->masterMatched++;
    }

    /** @param array<string, mixed> $test */
    private function matchAndUpdateTest(array $test, array $compByName): void
    {
        $testName = (string) ($test['Test'] ?? '');
        if ($testName === '') {
            return;
        }

        $key  = $this->normalise($testName);
        $comp = $compByName[$key] ?? null;

        if ($comp === null) {
            $comp = $this->fuzzyMatch($testName, $compByName);
        }

        if ($comp === null) {
            $this->compSkipped++;

            return;
        }

        $loincCode     = (string) ($comp['component_code'] ?? '');
        $loincProperty = (string) ($comp['component_property'] ?? '');
        $loincSystem   = (string) ($comp['component_system'] ?? '');
        $loincScale    = (string) ($comp['component_scale'] ?? '');

        if ($this->dryRun) {
            CLI::write(
                "    [DRY] lab_tests #{$test['mstTestKey']} '{$testName}' → " .
                "LOINC: {$loincCode} | prop: {$loincProperty} | sys: {$loincSystem} | scale: {$loincScale}"
            );
        } else {
            $this->db->table('lab_tests')->where('mstTestKey', $test['mstTestKey'])->update([
                'loinc_code'      => $loincCode,
                'loinc_property'  => $loincProperty,
                'loinc_system'    => $loincSystem,
                'loinc_scale'     => $loincScale,
                'loinc_synced_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->compMatched++;
    }

    // -------------------------------------------------------------------------
    // API pagination
    // -------------------------------------------------------------------------

    /**
     * Fetch all pages from the Bridge API for either 'masters' or 'components'.
     *
     * @param array<string, mixed> $opts
     * @return array<int, array<string, mixed>>
     */
    private function fetchAllPages(EAtriaBridgeConnector $connector, string $type, array $opts): array
    {
        $limit  = (int) ($opts['limit'] ?? 200);
        $since  = (string) ($opts['since'] ?? '');
        $all    = [];
        $offset = 0;

        do {
            try {
                if ($type === 'masters') {
                    $resp = $connector->pathologyMasters(
                        (string) ($opts['category'] ?? ''),
                        $limit,
                        $offset,
                        $since
                    );
                } else {
                    $resp = $connector->pathologyComponents(
                        (string) ($opts['parent_test'] ?? ''),
                        $limit,
                        $offset,
                        $since
                    );
                }
            } catch (\Throwable $e) {
                CLI::error("API error ({$type}, offset={$offset}): " . $e->getMessage());
                break;
            }

            if (! empty($resp['error']) || (isset($resp['success']) && $resp['success'] === false)) {
                CLI::write('  API error response: ' . json_encode($resp), 'red');
                break;
            }

            $items = $resp['items'] ?? $resp['data'] ?? [];
            if (! is_array($items) || $items === []) {
                break;
            }

            $all    = array_merge($all, $items);
            $offset += count($items);

            // If fewer items returned than limit, we've reached the end
            if (count($items) < $limit) {
                break;
            }
        } while (true);

        return $all;
    }

    // -------------------------------------------------------------------------
    // Local DB loaders
    // -------------------------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    private function loadLocalRepos(): array
    {
        return $this->db->table('lab_repo')
            ->select('mstRepoKey, Title, loinc_code')
            ->get()
            ->getResultArray();
    }

    /** @return array<int, array<string, mixed>> */
    private function loadLocalTests(): array
    {
        // Join via lab_repotests to know which repo each test belongs to.
        // A test may appear in multiple repos; we update it once (last-wins is fine).
        return $this->db->table('lab_tests lt')
            ->select('lt.mstTestKey, lt.Test, lt.loinc_code, lrt.mstRepoKey')
            ->join('lab_repotests lrt', 'lrt.mstTestKey = lt.mstTestKey', 'left')
            ->get()
            ->getResultArray();
    }

    // -------------------------------------------------------------------------
    // Name matching helpers
    // -------------------------------------------------------------------------

    private function normalise(string $name): string
    {
        $name = strtolower($name);
        // Remove parenthetical suffixes like "(CBC)" or "[Panel]"
        $name = preg_replace('/[\(\[\{][^\)\]\}]*[\)\]\}]/', '', $name);
        // Strip punctuation except hyphen
        $name = preg_replace('/[^a-z0-9\- ]/', '', $name);
        // Collapse multiple spaces
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    /**
     * Fuzzy match: find the entry in $candidates whose normalised key shares
     * the highest proportion of words with the $name.
     *
     * @param array<string, array<string, mixed>> $candidates  [normalisedKey => item]
     * @return array<string, mixed>|null
     */
    private function fuzzyMatch(string $name, array $candidates): ?array
    {
        $words = array_filter(explode(' ', $this->normalise($name)));
        if (empty($words)) {
            return null;
        }

        $best      = null;
        $bestScore = 0.0;

        foreach ($candidates as $key => $item) {
            $keyWords = array_filter(explode(' ', $key));
            if (empty($keyWords)) {
                continue;
            }

            $common = count(array_intersect($words, $keyWords));
            $total  = count(array_unique(array_merge($words, $keyWords)));
            $score  = $total > 0 ? $common / $total : 0.0;

            if ($score > $bestScore) {
                $bestScore = $score;
                $best      = $item;
            }
        }

        // Require at least 50% Jaccard similarity
        return $bestScore >= 0.5 ? $best : null;
    }
}

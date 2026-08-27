<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * SNOMED CT terminology service.
 * Priority: local DB (snomed_description + snomed_concept tables).
 * Falls back to external csnotk.e-atria.in API only when local tables are absent.
 */
class CsnotkTerminologyService
{
    /** SNOMED type_id for Fully Specified Names (ends with semantic tag in parentheses) */
    private const FSN_TYPE_ID = '900000000000003001';

    private string $baseUrl;
    private int $timeoutSec;
    private bool $apiEnabled;
    private ?BaseConnection $db = null;
    private bool $localAvailable = false;
    private bool $hasFtIndex     = false;

    public function __construct()
    {
        $baseUrl = trim((string) env('snomed.csnotk.baseUrl', ''));
        if ($baseUrl === '') {
            $baseUrl = trim((string) env('CSNOTK_BASE_URL', 'https://csnotk.e-atria.in'));
        }
        $this->baseUrl    = rtrim($baseUrl, '/');
        $this->timeoutSec = 1;

        // Force 100% Local DB mode (No external HTTP calls)
        $this->apiEnabled = false;

        try {
            $db = db_connect();
            if ($db->tableExists('snomed_description') && $db->tableExists('snomed_concept')) {
                $this->db             = $db;
                $this->localAvailable = true;
                $this->hasFtIndex     = $this->checkFtIndex($db);
            }
        } catch (\Throwable $e) {
            // Local DB not available
        }
    }

    public function isEnabled(): bool
    {
        return $this->localAvailable;
    }

    // ─── Public search API ───────────────────────────────────────────────────

    /**
     * General diagnosis search (findings + disorders).
     * @return array<int, array{concept_id:string,term:string,source:string}>
     */
    public function searchDiagnosis(string $query, int $limit = 12): array
    {
        $q = trim($query);
        if ($q === '') {
            return [];
        }
        $limit = max(1, min(12, $limit));

        $cacheKey = 'snomed_diag_loc_' . md5(mb_strtolower($q) . '_' . $limit);
        try {
            if ($cached = cache($cacheKey)) {
                if (is_array($cached)) {
                    return $cached;
                }
            }
        } catch (\Throwable $e) {}

        // Local DB is 100% primary (<20ms)
        $rows = $this->localSearch($q, $limit, ['finding', 'disorder', 'morphologic abnormality', 'disease']);
        $res  = array_map(fn ($r) => [
            'concept_id' => (string) ($r['concept_id'] ?? ''),
            'term'       => (string) ($r['term'] ?? ''),
            'source'     => 'snomed_local',
        ], array_slice($rows, 0, $limit));

        try {
            cache()->save($cacheKey, $res, 86400 * 30); // Cache for 30 days
        } catch (\Throwable $e) {}

        return $res;
    }

    /**
     * Search for clinical findings / symptoms / disorders (for chief complaints autocomplete).
     * @return array<int, array{concept_id:string,term:string,fsn:string,hierarchy:string,source:string}>
     */
    public function searchFinding(string $query, int $limit = 12): array
    {
        $q = trim($query);
        if ($q === '') {
            return [];
        }
        $limit = max(1, min(12, $limit));

        $cacheKey = 'snomed_find_loc_' . md5(mb_strtolower($q) . '_' . $limit);
        try {
            if ($cached = cache($cacheKey)) {
                if (is_array($cached)) {
                    return $cached;
                }
            }
        } catch (\Throwable $e) {}

        // Local DB is 100% primary (<20ms)
        $rows = $this->localSearch($q, $limit, ['finding', 'disorder']);
        $res  = array_slice($rows, 0, $limit);

        try {
            cache()->save($cacheKey, $res, 86400 * 30); // Cache for 30 days
        } catch (\Throwable $e) {}

        return $res;
    }

    /**
     * Search for SNOMED CT substance / pharmaceutical product concepts.
     * Used for medicine SNOMED CT coding on the opd_medicince page.
     *
     * Semantic tags searched: substance, product, medicinal product
     * Local DB first (snomed_description), falls back to CSNOtk API.
     *
     * @return array<int, array{concept_id:string,term:string,fsn:string,hierarchy:string,source:string}>
     */
    public function searchSubstance(string $query, int $limit = 15): array
    {
        $q = trim($query);
        if ($q === '') {
            return [];
        }
        $limit = max(1, min(50, $limit));

        $substanceTags = ['substance', 'product', 'medicinal product', 'clinical drug'];

        $rows = $this->localSearch($q, $limit, $substanceTags);

        if (count($rows) < $limit && mb_strlen($q) >= 3 && $this->apiEnabled && $this->baseUrl !== '') {
            // CSNOtk: search substance tag first, then product tag as fallback
            $apiRows = $this->apiSearchBySemTag($q, $limit, ['substance', 'product']);
            $rows = $this->mergeFallback($rows, $apiRows, $limit);
        }

        // If still empty try broader local search and filter post-hoc for drug-related terms
        if (empty($rows) && $this->localAvailable) {
            $broader = $this->localSearch($q, $limit, []);
            $rows = array_filter($broader, static function (array $r) use ($substanceTags): bool {
                $h = mb_strtolower((string) ($r['hierarchy'] ?? ''));
                foreach ($substanceTags as $tag) {
                    if ($h === $tag || str_contains($h, $tag)) {
                        return true;
                    }
                }
                return false;
            });
            $rows = array_values($rows);
        }

        return array_slice($rows, 0, $limit);
    }

    /**
     * Search for procedures / observable entities (for investigation advice autocomplete).
     * @return array<int, array{concept_id:string,term:string,fsn:string,hierarchy:string,source:string}>
     */
    public function searchProcedure(string $query, int $limit = 20): array
    {
        $q = trim($query);
        if ($q === '') {
            return [];
        }
        $limit = max(1, min(50, $limit));

        // Local DB is primary — ~17ms vs ~330ms for external API from server
        $rows = $this->localSearch($q, $limit, ['procedure', 'observable entity', 'regime/therapy']);

        if (count($rows) < $limit && mb_strlen($q) >= 3 && $this->apiEnabled && $this->baseUrl !== '') {
            $rows = $this->mergeFallback($rows, $this->apiSearchBySemTag($q, $limit, ['procedure', 'observable entity']), $limit);
        }

        return array_slice($rows, 0, $limit);
    }

    // â”€â”€â”€ Local DB search â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Search local snomed_description.
     * Uses FULLTEXT MATCH if index exists, falls back to prefix then substring LIKE.
     *
     * @param  string[] $semanticTags  e.g. ['finding','disorder'] â€” matched against FSN suffix
     * @return array<int, array{concept_id:string,term:string,fsn:string,hierarchy:string,source:string}>
     */
    private function localSearch(string $q, int $limit, array $semanticTags = []): array
    {
        if (! $this->localAvailable || $this->db === null) {
            return [];
        }

        $fetchLimit = max($limit * 5, 100);

        // 1. Try FULLTEXT
        $dbRows = $this->hasFtIndex ? $this->localFtSearch($q, $fetchLimit) : [];

        // 2. Try prefix on term_normalized (indexed)
        if (empty($dbRows)) {
            $dbRows = $this->localPrefixSearch($q, $fetchLimit);
        }

        // 3. Try substring (slower â€” full scan but catches mid-word)
        if (empty($dbRows)) {
            $dbRows = $this->localSubstringSearch($q, $fetchLimit);
        }

        return $this->filterAndFormat($dbRows, $semanticTags, $limit);
    }

    /**
     * FULLTEXT MATCH ... AGAINST (Boolean Mode) search on local 1.7M snomed_description table.
     * @return array<int, array<string, string>>
     */
    private function localFtSearch(string $q, int $limit): array
    {
        $words   = preg_split('/\s+/', mb_strtolower(trim($q))) ?: [$q];
        $ftWords = array_filter($words, fn ($w) => mb_strlen($w) >= 2);

        if (empty($ftWords)) {
            return $this->localPrefixSearch($q, $limit);
        }

        $ftQuery = implode(' ', array_map(fn ($w) => '+' . $w . '*', $ftWords));

        try {
            return (array) $this->db->query(
                "SELECT d.concept_id, d.term,
                        (SELECT d2.term FROM snomed_description d2 WHERE d2.concept_id = d.concept_id AND d2.type_id = ? AND d2.active = 1 LIMIT 1) AS fsn
                 FROM snomed_description d
                 INNER JOIN snomed_concept c ON c.concept_id = d.concept_id AND c.active = 1
                 WHERE d.active = 1
                   AND d.language_code = 'en'
                   AND MATCH(d.term) AGAINST(? IN BOOLEAN MODE)
                 ORDER BY LENGTH(d.term) ASC
                 LIMIT ?",
                [self::FSN_TYPE_ID, $ftQuery, $limit]
            )->getResultArray();
        } catch (\Throwable $e) {
            $this->hasFtIndex = false;
            return $this->localPrefixSearch($q, $limit);
        }
    }

    /**
     * Prefix search: term_normalized LIKE 'query%' (uses B-tree index).
     * @return array<int, array<string, string>>
     */
    private function localPrefixSearch(string $q, int $limit): array
    {
        $norm = mb_strtolower(trim($q));
        try {
            return (array) $this->db->query(
                "SELECT d.concept_id, d.term,
                        (SELECT d2.term FROM snomed_description d2 WHERE d2.concept_id = d.concept_id AND d2.type_id = ? AND d2.active = 1 LIMIT 1) AS fsn
                 FROM snomed_description d
                 INNER JOIN snomed_concept c ON c.concept_id = d.concept_id AND c.active = 1
                 WHERE d.active = 1
                   AND d.language_code = 'en'
                   AND d.term_normalized LIKE ?
                 ORDER BY LENGTH(d.term) ASC
                 LIMIT ?",
                [self::FSN_TYPE_ID, $norm . '%', $limit]
            )->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Substring search: term_normalized LIKE '%query%'.
     * @return array<int, array<string, string>>
     */
    private function localSubstringSearch(string $q, int $limit): array
    {
        $norm = mb_strtolower(trim($q));
        try {
            return (array) $this->db->query(
                "SELECT d.concept_id, d.term,
                        (SELECT d2.term FROM snomed_description d2 WHERE d2.concept_id = d.concept_id AND d2.type_id = ? AND d2.active = 1 LIMIT 1) AS fsn
                 FROM snomed_description d
                 INNER JOIN snomed_concept c ON c.concept_id = d.concept_id AND c.active = 1
                 WHERE d.active = 1
                   AND d.language_code = 'en'
                   AND d.term_normalized LIKE ?
                 ORDER BY LENGTH(d.term) ASC
                 LIMIT ?",
                [self::FSN_TYPE_ID, '%' . $norm . '%', $limit]
            )->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Filter raw DB rows by semantic tag and format for output.
     *
     * @param  array<int, array<string, string>> $dbRows
     * @param  string[]                          $semanticTags
     * @return array<int, array{concept_id:string,term:string,fsn:string,hierarchy:string,source:string}>
     */
    private function filterAndFormat(array $dbRows, array $semanticTags, int $limit): array
    {
        $tagsLower = array_map('mb_strtolower', $semanticTags);
        $out       = [];
        $seen      = [];

        foreach ($dbRows as $row) {
            if (count($out) >= $limit) {
                break;
            }

            $conceptId = trim((string) ($row['concept_id'] ?? ''));
            $term      = trim((string) ($row['term'] ?? ''));
            $fsn       = trim((string) ($row['fsn'] ?? ''));
            if ($conceptId === '' || $term === '') {
                continue;
            }

            if (isset($seen[$conceptId])) {
                continue;
            }

            // Extract semantic tag from FSN or term suffix
            $hierarchy = '';
            if ($fsn !== '' && preg_match('/^(.*)\(([^)]+)\)$/', $fsn, $m)) {
                $hierarchy = trim($m[2]);
            } elseif (preg_match('/^(.*)\(([^)]+)\)$/', $term, $m)) {
                $hierarchy = trim($m[2]);
                $term = trim($m[1]);
            }

            // Apply semantic tag filter if tags specified and hierarchy identified
            if (! empty($tagsLower) && $hierarchy !== '') {
                if (! in_array(mb_strtolower($hierarchy), $tagsLower, true)) {
                    continue;
                }
            }

            $seen[$conceptId] = true;
            $out[] = [
                'concept_id' => $conceptId,
                'term'       => $term,
                'fsn'        => $fsn !== '' ? $fsn : $term,
                'hierarchy'  => $hierarchy,
                'source'     => 'snomed_local',
            ];
        }

        return $out;
    }

    // ─── External API fallbacks ───────────────────────────────────────────────

    /**
     * @return array<int, array{concept_id:string,term:string,fsn:string,hierarchy:string,source:string}>
     */
    private function apiSearchDiagnosis(string $q, int $limit): array
    {
        $rows = [];
        $seen = [];

        $search = $this->requestJson('/api/search/search', [
            'term'           => $q,
            'state'          => 'active',
            'acceptability'  => 'all',
            'groupbyconcept' => 'true',
            'fullconcept'    => 'false',
            'returnlimit'    => (string) max(10, $limit * 2),
        ]);

        foreach ($this->extractItems($search['data']) as $item) {
            if (! is_array($item) || count($rows) >= $limit) {
                break;
            }
            $conceptId = $this->readString($item, ['conceptId', 'concept_id', 'id']);
            $term      = $this->readString($item, ['term', 'conceptFsn', 'name']);
            if (! preg_match('/^[0-9]{6,20}$/', $conceptId) || $term === '') {
                continue;
            }
            if (isset($seen[$conceptId])) {
                continue;
            }
            $seen[$conceptId] = true;
            $rows[] = ['concept_id' => $conceptId, 'term' => $term, 'fsn' => $term, 'hierarchy' => '', 'source' => 'csnotk'];
        }

        return $rows;
    }

    /**
     * @param  string[] $semTags
     * @return array<int, array{concept_id:string,term:string,fsn:string,hierarchy:string,source:string}>
     */
    private function apiSearchBySemTag(string $q, int $limit, array $semTags): array
    {
        $rows = [];
        $seen = [];

        foreach ($semTags as $semTag) {
            if (count($rows) >= $limit) {
                break;
            }
            $res = $this->requestJson('/api/search/suggest', [
                'term'          => $q,
                'state'         => 'active',
                'semantictag'   => $semTag,
                'acceptability' => 'preferred',
                'returnlimit'   => (string) max(10, $limit * 2),
            ]);

            foreach ($this->extractItems($res['data']) as $item) {
                if (count($rows) >= $limit) {
                    break;
                }
                $conceptId = '';
                $term      = '';
                $fsn       = '';
                $hierarchy = $semTag;

                if (is_string($item)) {
                    $term = trim($item);
                } elseif (is_array($item)) {
                    $conceptId = $this->readString($item, ['conceptId', 'concept_id', 'id']);
                    $term      = $this->readString($item, ['term', 'name']);
                    $fsn       = $this->readString($item, ['conceptFsn', 'fsn', 'fullySpecifiedName']);
                    $hierarchy = $this->readString($item, ['hierarchy', 'semanticTag', 'semantictag']) ?: $semTag;
                    $activeStatus = $item['activeStatus'] ?? $item['active_status'] ?? null;
                    if ($activeStatus !== null && (int) $activeStatus !== 1) {
                        continue;
                    }
                    if (strtolower((string) ($item['conceptState'] ?? $item['concept_state'] ?? 'active')) === 'inactive') {
                        continue;
                    }
                }

                if ($term === '') {
                    continue;
                }
                $displayTerm = $fsn !== '' ? $fsn : $term;
                $key         = strtoupper($displayTerm) . '|' . $conceptId;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $rows[] = [
                    'concept_id' => $conceptId,
                    'term'       => $displayTerm,
                    'fsn'        => $fsn,
                    'hierarchy'  => $hierarchy,
                    'source'     => 'csnotk',
                ];
            }
        }

        return $rows;
    }

    // â”€â”€â”€ Shared helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Merge primary results with fallback rows, deduplicating by concept_id.
     *
     * @param  array<int, array<string, string>> $primary
     * @param  array<int, array<string, string>> $fallback
     * @return array<int, array<string, string>>
     */
    private function mergeFallback(array $primary, array $fallback, int $limit): array
    {
        $seen = [];
        foreach ($primary as $r) {
            $k = (string) ($r['concept_id'] ?? '');
            if ($k !== '') {
                $seen[$k] = true;
            }
        }
        foreach ($fallback as $r) {
            if (count($primary) >= $limit) {
                break;
            }
            $k = (string) ($r['concept_id'] ?? '');
            if ($k !== '' && isset($seen[$k])) {
                continue;
            }
            if ($k !== '') {
                $seen[$k] = true;
            }
            $primary[] = $r;
        }

        return $primary;
    }

    private function checkFtIndex(BaseConnection $db): bool
    {
        try {
            $rows = $db->query(
                "SHOW INDEX FROM snomed_description WHERE Index_type = 'FULLTEXT' AND Column_name = 'term'"
            )->getResultArray();

            return ! empty($rows);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed> $row
     * @param  array<int, string>   $keys
     */
    private function readString(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (! isset($row[$key])) {
                continue;
            }
            $value = trim((string) $row[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param  mixed $data
     * @return array<int, mixed>
     */
    private function extractItems($data): array
    {
        if (! is_array($data)) {
            return [];
        }
        if ($this->isList($data)) {
            return $data;
        }
        foreach (['rows', 'results', 'data', 'items'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $this->isList($data[$key]) ? $data[$key] : [];
            }
        }

        return [];
    }

    private function isList(array $arr): bool
    {
        return $arr === [] || array_keys($arr) === range(0, count($arr) - 1);
    }

    /**
     * @param  array<string, string> $query
     * @return array{ok:bool,status:int,body:string,data:mixed}
     */
    private function requestJson(string $path, array $query): array
    {
        if (! $this->apiEnabled || $this->baseUrl === '') {
            return ['ok' => false, 'status' => 0, 'body' => '', 'data' => null];
        }

        $url = $this->baseUrl . $path;
        $qs  = http_build_query($query);
        if ($qs !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . $qs;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeoutSec,
            CURLOPT_TIMEOUT        => $this->timeoutSec,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);

        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($body === false || $error !== '') {
            return ['ok' => false, 'status' => $status, 'body' => '', 'data' => null];
        }

        $decoded = json_decode((string) $body, true);

        return [
            'ok'     => $status >= 200 && $status < 300,
            'status' => $status,
            'body'   => (string) $body,
            'data'   => $decoded,
        ];
    }
}

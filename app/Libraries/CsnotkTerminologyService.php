<?php

namespace App\Libraries;

class CsnotkTerminologyService
{
    private string $baseUrl;
    private int $timeoutSec;
    private bool $enabled;

    public function __construct()
    {
        $baseUrl = trim((string) env('snomed.csnotk.baseUrl', ''));
        if ($baseUrl === '') {
            $baseUrl = trim((string) env('CSNOTK_BASE_URL', 'https://csnotk.e-atria.in'));
        }
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeoutSec = max(1, (int) env('snomed.csnotk.timeoutSec', 5));

        $enabledRaw = strtolower(trim((string) env('snomed.csnotk.enabled', env('CSNOTK_ENABLED', ''))));
        $this->enabled = in_array($enabledRaw, ['1', 'true', 'yes', 'on'], true) || $this->baseUrl !== '';
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->baseUrl !== '';
    }

    /**
     * @return array<int, array{concept_id:string,term:string,source:string}>
     */
    public function searchDiagnosis(string $query, int $limit = 20): array
    {
        $q = trim($query);
        if ($q === '' || ! $this->isEnabled()) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $rows = [];
        $seen = [];

        $search = $this->requestJson('/api/search/search', [
            'term' => $q,
            'state' => 'active',
            'acceptability' => 'all',
            'groupbyconcept' => 'true',
            'fullconcept' => 'false',
            'returnlimit' => (string) max(10, $limit * 2),
        ]);

        $items = $this->extractItems($search['data']);
        $validated = 0;
        $maxValidated = min(10, $limit);

        foreach ($items as $item) {
            if (count($rows) >= $limit) {
                break;
            }

            $conceptId = $this->readString($item, ['conceptId', 'concept_id', 'id']);
            $term = $this->readString($item, ['term', 'conceptFsn', 'name']);
            if (! preg_match('/^[0-9]{6,20}$/', $conceptId) || $term === '') {
                continue;
            }

            if ($validated < $maxValidated) {
                $isValid = $this->validateConceptId($conceptId);
                $validated++;
                if ($isValid === false) {
                    continue;
                }
            }

            $key = strtoupper($term) . '|' . $conceptId;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $rows[] = [
                'concept_id' => $conceptId,
                'term' => $term,
                'source' => 'csnotk',
            ];
        }

        if (count($rows) >= $limit) {
            return $rows;
        }

        $suggest = $this->requestJson('/api/search/suggest', [
            'term' => $q,
            'state' => 'active',
            'acceptability' => 'all',
            'returnlimit' => (string) $limit,
        ]);

        $suggestItems = $this->extractItems($suggest['data']);
        foreach ($suggestItems as $item) {
            if (count($rows) >= $limit) {
                break;
            }

            $term = '';
            if (is_string($item)) {
                $term = trim($item);
            } elseif (is_array($item)) {
                $term = $this->readString($item, ['term', 'name']);
            }
            if ($term === '') {
                continue;
            }

            $key = strtoupper($term) . '|';
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $rows[] = [
                'concept_id' => '',
                'term' => $term,
                'source' => 'csnotk-suggest',
            ];
        }

        return $rows;
    }

    private function validateConceptId(string $conceptId): ?bool
    {
        $res = $this->requestJson('/api/validate/id', ['id' => $conceptId]);
        if (! $res['ok']) {
            return null;
        }

        $data = $res['data'];
        if (is_array($data)) {
            if (array_key_exists('valueBoolean', $data)) {
                return filter_var($data['valueBoolean'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }
            if (array_key_exists('valid', $data)) {
                return filter_var($data['valid'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }
            $message = trim((string) ($data['valueString'] ?? $data['message'] ?? ''));
            if ($message !== '') {
                return stripos($message, ' is valid') !== false;
            }
        }

        return null;
    }

    /**
     * @param mixed $data
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
        if ($arr === []) {
            return true;
        }

        return array_keys($arr) === range(0, count($arr) - 1);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $keys
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
     * @param array<string, string> $query
     * @return array{ok:bool,status:int,body:string,data:mixed}
     */
    private function requestJson(string $path, array $query): array
    {
        if (! $this->isEnabled()) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'data' => null];
        }

        $url = $this->baseUrl . $path;
        $qs = http_build_query($query);
        if ($qs !== '') {
            $url .= (strpos($url, '?') === false ? '?' : '&') . $qs;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeoutSec,
            CURLOPT_TIMEOUT => $this->timeoutSec,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $error !== '') {
            return ['ok' => false, 'status' => $status, 'body' => '', 'data' => null];
        }

        $decoded = json_decode((string) $body, true);

        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'body' => (string) $body,
            'data' => $decoded,
        ];
    }
}

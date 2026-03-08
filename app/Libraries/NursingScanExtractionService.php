<?php

namespace App\Libraries;

class NursingScanExtractionService
{
    public function extract(string $docType, string $manualText = '', ?array $uploaded = null): array
    {
        $warnings = [];
        $text = trim($manualText);
        $scanRef = '';
        $uploadedPath = '';

        if (is_array($uploaded) && ! empty($uploaded['path'])) {
            $scanRef = (string) ($uploaded['scan_ref'] ?? '');
            $uploadedPath = (string) $uploaded['path'];
        }

        if ($text === '' && $uploadedPath !== '') {
            $ext = strtolower(pathinfo($uploadedPath, PATHINFO_EXTENSION));
            if (in_array($ext, ['txt', 'csv', 'log'], true)) {
                $fileText = @file_get_contents($uploadedPath);
                if (is_string($fileText)) {
                    $text = trim($fileText);
                }
            }
        }

        if ($text === '' && $uploadedPath !== '') {
            $azureText = $this->extractTextUsingAzureDocumentIntelligence($uploadedPath, $uploaded['mime'] ?? '');
            if ($azureText !== '') {
                $text = $azureText;
            } else {
                $warnings[] = 'No OCR text extracted. You can paste OCR text manually and rescan.';
            }
        }

        $rows = $this->parseRows($docType, $text);

        return [
            'scan_ref' => $scanRef,
            'source_text' => $text,
            'rows' => $rows,
            'warnings' => $warnings,
        ];
    }

    private function parseRows(string $docType, string $text): array
    {
        $normalized = strtolower(trim($docType));
        if (! in_array($normalized, ['vitals', 'fluid', 'treatment', 'auto'], true)) {
            $normalized = 'auto';
        }

        if ($normalized === 'auto') {
            $hint = strtolower($text);
            if (preg_match('/\b(intake|output|i\/?o|fluid)\b/i', $hint) === 1) {
                $normalized = 'fluid';
            } elseif (preg_match('/\b(pulse|bp|spo2|temperature|temp|resp|rr)\b/i', $hint) === 1) {
                $normalized = 'vitals';
            } else {
                $normalized = 'treatment';
            }
        }

        if ($normalized === 'vitals') {
            return [$this->parseVitalsRow($text)];
        }

        if ($normalized === 'fluid') {
            return [$this->parseFluidRow($text)];
        }

        return [$this->parseTreatmentRow($text)];
    }

    private function parseVitalsRow(string $text): array
    {
        return [
            'entry_type' => 'vitals',
            'recorded_at' => $this->extractDateTime($text),
            'temperature_f' => $this->extractNumber($text, '/(?:temp|temperature)\s*[:=-]?\s*(\d{2,3}(?:\.\d+)?)/i'),
            'pulse_rate' => $this->extractNumber($text, '/(?:pulse|pr)\s*[:=-]?\s*(\d{2,3})/i'),
            'resp_rate' => $this->extractNumber($text, '/(?:resp|rr)\s*[:=-]?\s*(\d{1,3})/i'),
            'bp_systolic' => $this->extractBloodPressure($text, 0),
            'bp_diastolic' => $this->extractBloodPressure($text, 1),
            'spo2' => $this->extractNumber($text, '/(?:spo2|spo\s*2)\s*[:=-]?\s*(\d{2,3})/i'),
            'weight_kg' => $this->extractNumber($text, '/(?:weight|wt)\s*[:=-]?\s*(\d{1,3}(?:\.\d+)?)/i'),
            'general_note' => trim($text),
            'confidence' => 0.65,
        ];
    }

    private function parseFluidRow(string $text): array
    {
        $direction = 'intake';
        if (preg_match('/\b(output|urine|drain|vomit|stool)\b/i', $text) === 1) {
            $direction = 'output';
        }

        return [
            'entry_type' => 'fluid',
            'recorded_at' => $this->extractDateTime($text),
            'fluid_direction' => $direction,
            'fluid_route' => $this->extractWord($text, '/(?:route)\s*[:=-]?\s*([a-zA-Z\s\/]{2,40})/i'),
            'fluid_amount_ml' => $this->extractNumber($text, '/(\d{1,5})\s*(?:ml|mL|cc)\b/i'),
            'general_note' => trim($text),
            'confidence' => 0.65,
        ];
    }

    private function parseTreatmentRow(string $text): array
    {
        return [
            'entry_type' => 'treatment',
            'recorded_at' => $this->extractDateTime($text),
            'treatment_text' => trim($text),
            'general_note' => trim($text),
            'confidence' => 0.6,
        ];
    }

    private function extractDateTime(string $text): string
    {
        if (preg_match('/(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/', $text, $m) === 1) {
            return $m[1] . 'T' . $m[2];
        }

        if (preg_match('/(\d{2}[\/\-]\d{2}[\/\-]\d{4})\s+(\d{1,2}:\d{2})/', $text, $m) === 1) {
            $date = str_replace('/', '-', $m[1]);
            $ts = strtotime($date . ' ' . $m[2]);
            if ($ts !== false) {
                return date('Y-m-d\TH:i', $ts);
            }
        }

        return date('Y-m-d\TH:i');
    }

    private function extractNumber(string $text, string $pattern): ?string
    {
        if (preg_match($pattern, $text, $m) !== 1) {
            return null;
        }

        return trim((string) ($m[1] ?? ''));
    }

    private function extractWord(string $text, string $pattern): string
    {
        if (preg_match($pattern, $text, $m) !== 1) {
            return '';
        }

        return trim((string) ($m[1] ?? ''));
    }

    private function extractBloodPressure(string $text, int $index): ?string
    {
        if (preg_match('/(?:bp|blood\s*pressure)\s*[:=-]?\s*(\d{2,3})\s*[\/\\-]\s*(\d{2,3})/i', $text, $m) !== 1) {
            return null;
        }

        return $index === 0 ? (string) ($m[1] ?? '') : (string) ($m[2] ?? '');
    }

    private function extractTextUsingAzureDocumentIntelligence(string $path, string $mime): string
    {
        $endpoint = rtrim((string) env('azure.docintel.endpoint', ''), '/');
        $apiKey = trim((string) env('azure.docintel.api_key', ''));
        $model = trim((string) env('azure.docintel.model', 'prebuilt-read'));
        $apiVersion = trim((string) env('azure.docintel.api_version', '2024-11-30'));

        if ($endpoint === '' || $apiKey === '') {
            return '';
        }

        $content = @file_get_contents($path);
        if (! is_string($content) || $content === '') {
            return '';
        }

        $url = $endpoint . '/documentintelligence/documentModels/' . rawurlencode($model) . ':analyze?api-version=' . rawurlencode($apiVersion);

        $headers = [
            'Ocp-Apim-Subscription-Key: ' . $apiKey,
            'Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'),
        ];

        $result = $this->sendCurlRequest($url, 'POST', $headers, $content, true);
        if ($result['http'] < 200 || $result['http'] >= 300) {
            return '';
        }

        $operation = '';
        foreach ($result['headers'] as $key => $value) {
            if (strtolower($key) === 'operation-location') {
                $operation = $value;
                break;
            }
        }

        if ($operation === '') {
            return '';
        }

        $readText = '';
        for ($i = 0; $i < 10; $i++) {
            usleep(700000);
            $poll = $this->sendCurlRequest($operation, 'GET', [
                'Ocp-Apim-Subscription-Key: ' . $apiKey,
            ], null, false);

            if ($poll['http'] < 200 || $poll['http'] >= 300) {
                continue;
            }

            $body = json_decode((string) $poll['body'], true);
            if (! is_array($body)) {
                continue;
            }

            $status = strtolower((string) ($body['status'] ?? ''));
            if ($status === 'succeeded') {
                $lines = [];
                $items = $body['analyzeResult']['content'] ?? '';
                if (is_string($items) && trim($items) !== '') {
                    $readText = trim($items);
                } elseif (isset($body['analyzeResult']['pages']) && is_array($body['analyzeResult']['pages'])) {
                    foreach ($body['analyzeResult']['pages'] as $page) {
                        foreach (($page['lines'] ?? []) as $line) {
                            $txt = trim((string) ($line['content'] ?? ''));
                            if ($txt !== '') {
                                $lines[] = $txt;
                            }
                        }
                    }
                    $readText = trim(implode("\n", $lines));
                }
                break;
            }

            if ($status === 'failed') {
                break;
            }
        }

        return $readText;
    }

    private function sendCurlRequest(string $url, string $method, array $headers, ?string $body, bool $includeHeaders): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HEADER, $includeHeaders);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $responseHeaders = [];
        $responseBody = is_string($raw) ? $raw : '';
        if ($includeHeaders && is_string($raw)) {
            $headerPart = substr($raw, 0, $headerSize);
            $responseBody = substr($raw, $headerSize);
            $lines = preg_split('/\r\n|\n|\r/', $headerPart) ?: [];
            foreach ($lines as $line) {
                $parts = explode(':', $line, 2);
                if (count($parts) !== 2) {
                    continue;
                }
                $name = trim($parts[0]);
                $value = trim($parts[1]);
                if ($name !== '') {
                    $responseHeaders[$name] = $value;
                }
            }
        }

        return [
            'http' => $http,
            'headers' => $responseHeaders,
            'body' => $responseBody,
        ];
    }
}

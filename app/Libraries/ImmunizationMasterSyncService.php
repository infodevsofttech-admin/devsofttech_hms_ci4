<?php

namespace App\Libraries;

use App\Libraries\Abdm\AbdmConnectorFactory;
use CodeIgniter\Database\BaseConnection;

class ImmunizationMasterSyncService
{
    private BaseConnection $db;

    /** @var object */
    private $connector;

    public function __construct(?BaseConnection $db = null, ?object $connector = null)
    {
        $this->db = $db ?? db_connect();
        $this->connector = $connector ?? AbdmConnectorFactory::make();
    }

    /**
     * @return array<string,mixed>
     */
    public function latestSyncMeta(): array
    {
        if (! $this->db->tableExists('immunization_master_sync_log')) {
            return [];
        }

        return $this->db->table('immunization_master_sync_log')
            ->where('master_type', 'UIP_SCHEDULE')
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray() ?? [];
    }

    /**
     * @return array<string,mixed>
     */
    public function syncUipMaster(bool $force = false): array
    {
        foreach (['immunization_vaccine_master', 'immunization_schedule_master'] as $table) {
            if (! $this->db->tableExists($table)) {
                return ['ok' => 0, 'error_text' => $table . ' table not found. Run migrations first.'];
            }
        }

        if (! method_exists($this->connector, 'immunizationUipVersion') || ! method_exists($this->connector, 'immunizationUipSchedule')) {
            return ['ok' => 0, 'error_text' => 'Current ABDM connector does not support E-Atria immunization master sync.'];
        }

        $remoteVersion = $this->connector->immunizationUipVersion();
        if ((int) ($remoteVersion['ok'] ?? 0) !== 1) {
            $this->writeSyncLog([], 'failed', 0, $this->bridgeError($remoteVersion));
            return ['ok' => 0, 'error_text' => $this->bridgeError($remoteVersion), 'bridge_response' => $remoteVersion];
        }

        $local = $this->latestSyncMeta();
        $remoteVersionCode = trim((string) ($remoteVersion['version_code'] ?? ''));
        $remoteChecksum = trim((string) ($remoteVersion['checksum'] ?? ''));
        if (! $force
            && $remoteVersionCode !== ''
            && $remoteChecksum !== ''
            && $remoteVersionCode === (string) ($local['version_code'] ?? '')
            && $remoteChecksum === (string) ($local['checksum'] ?? '')
        ) {
            return [
                'ok' => 1,
                'status' => 'already_current',
                'version_code' => $remoteVersionCode,
                'checksum' => $remoteChecksum,
                'message' => 'Local UIP master already matches bridge version.',
            ];
        }

        $catalog = $this->connector->immunizationUipSchedule();
        if ((int) ($catalog['ok'] ?? 0) !== 1) {
            $this->writeSyncLog($remoteVersion, 'failed', 0, $this->bridgeError($catalog));
            return ['ok' => 0, 'error_text' => $this->bridgeError($catalog), 'bridge_response' => $catalog];
        }

        $version = is_array($catalog['version'] ?? null) ? $catalog['version'] : $remoteVersion;
        $items = $catalog['vaccines'] ?? $catalog['schedule'] ?? $catalog['items'] ?? [];
        if (! is_array($items) || $items === []) {
            $this->writeSyncLog($version, 'failed', 0, 'Bridge returned empty UIP schedule.');
            return ['ok' => 0, 'error_text' => 'Bridge returned empty UIP schedule.'];
        }

        $counts = ['inserted' => 0, 'updated' => 0, 'vaccines' => 0];
        $syncedCodes = [];

        $this->db->transStart();
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $vaccineId = $this->upsertVaccine($item);
            if ($vaccineId <= 0) {
                continue;
            }
            $counts['vaccines']++;

            $result = $this->upsertSchedule($item, $vaccineId, $version);
            if ($result['schedule_code'] !== '') {
                $syncedCodes[] = $result['schedule_code'];
            }
            if ($result['created']) {
                $counts['inserted']++;
            } else {
                $counts['updated']++;
            }
        }
        $this->deactivateMissingBridgeRows(array_values(array_unique($syncedCodes)), (string) ($version['version_code'] ?? ''));
        $this->writeSyncLog($version, 'success', count($items), 'UIP schedule synced from E-Atria bridge.');
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return ['ok' => 0, 'error_text' => 'Database transaction failed while syncing UIP schedule.'];
        }

        return [
            'ok' => 1,
            'status' => 'synced',
            'version_code' => (string) ($version['version_code'] ?? $remoteVersionCode),
            'checksum' => (string) ($version['checksum'] ?? $remoteChecksum),
            'item_count' => count($items),
            'inserted' => $counts['inserted'],
            'updated' => $counts['updated'],
            'message' => 'UIP schedule synced from E-Atria bridge.',
        ];
    }

    private function upsertVaccine(array $item): int
    {
        $now = date('Y-m-d H:i:s');
        $vaccineName = trim((string) ($item['vaccine_name'] ?? $item['vaccine_display'] ?? $item['series_name'] ?? $item['vaccine_code'] ?? ''));
        if ($vaccineName === '') {
            return 0;
        }

        $payload = $this->filterColumns('immunization_vaccine_master', [
            'vaccine_name' => $vaccineName,
            'vaccine_code' => $this->nullableString($item['vaccine_code'] ?? null),
            'vaccine_code_system' => $this->nullableString($item['vaccine_code_system'] ?? null),
            'vaccine_display' => $this->nullableString($item['vaccine_display'] ?? $vaccineName),
            'target_disease_code' => $this->nullableString($item['target_disease_code'] ?? null),
            'target_disease_name' => $this->nullableString($item['target_disease_name'] ?? null),
            'route_code' => $this->nullableString($item['route_code'] ?? null),
            'route_name' => $this->nullableString($item['route_name'] ?? null),
            'site_code' => $this->nullableString($item['site_code'] ?? null),
            'site_name' => $this->nullableString($item['site_name'] ?? null),
            'is_active' => $this->boolInt($item['is_active'] ?? 1),
            'updated_at' => $now,
        ]);

        $existing = [];
        $vaccineCode = trim((string) ($payload['vaccine_code'] ?? ''));
        if ($vaccineCode !== '') {
            $existing = $this->db->table('immunization_vaccine_master')->where('vaccine_code', $vaccineCode)->get(1)->getRowArray() ?? [];
        }
        if ($existing === []) {
            $existing = $this->db->table('immunization_vaccine_master')->where('vaccine_name', $vaccineName)->get(1)->getRowArray() ?? [];
        }

        if ($existing !== []) {
            $this->db->table('immunization_vaccine_master')->where('id', (int) $existing['id'])->update($payload);
            return (int) $existing['id'];
        }

        $payload['created_at'] = $now;
        $this->db->table('immunization_vaccine_master')->insert($payload);
        return (int) $this->db->insertID();
    }

    /**
     * @return array{created:bool,schedule_id:int,schedule_code:string}
     */
    private function upsertSchedule(array $item, int $vaccineId, array $version): array
    {
        $now = date('Y-m-d H:i:s');
        $scheduleCode = trim((string) ($item['schedule_code'] ?? ''));
        $payload = $this->filterColumns('immunization_schedule_master', [
            'schedule_code' => $this->nullableString($scheduleCode),
            'schedule_name' => $this->nullableString($item['schedule_name'] ?? 'UIP') ?? 'UIP',
            'beneficiary_category' => $this->nullableString($item['beneficiary_category'] ?? null),
            'gender_applicability' => $this->nullableString($item['gender_applicability'] ?? null),
            'age_label' => (string) ($item['age_label'] ?? ''),
            'age_value' => (int) ($item['age_value'] ?? 0),
            'age_unit' => (string) ($item['age_unit'] ?? 'days'),
            'age_offset_days' => (int) ($item['age_offset_days'] ?? 0),
            'min_age_days' => $this->nullableInt($item['min_age_days'] ?? null),
            'max_age_days' => $this->nullableInt($item['max_age_days'] ?? null),
            'gateway_schedule_id' => $this->nullableInt($item['id'] ?? null),
            'vaccine_master_id' => $vaccineId,
            'dose_number' => $this->nullableString($item['dose_number'] ?? null),
            'series_name' => $this->nullableString($item['series_name'] ?? null) ?? 'Indian Universal Immunization Programme',
            'series_doses' => $this->nullableString($item['series_doses'] ?? null),
            'notes' => $this->nullableString($item['notes'] ?? null),
            'sort_order' => (int) ($item['sort_order'] ?? 0),
            'is_uip' => 1,
            'is_active' => $this->boolInt($item['is_active'] ?? 1),
            'is_state_specific' => $this->boolInt($item['is_state_specific'] ?? 0),
            'state_code' => $this->nullableString($item['state_code'] ?? null),
            'district_code' => $this->nullableString($item['district_code'] ?? null),
            'applicability_note' => $this->nullableString($item['applicability_note'] ?? null),
            'source_version_code' => $this->nullableString($version['version_code'] ?? null),
            'source_checksum' => $this->nullableString($version['checksum'] ?? null),
            'source_name' => $this->nullableString($version['source_name'] ?? null),
            'source_url' => $this->nullableString($version['source_url'] ?? null),
            'source_document_name' => $this->nullableString($version['source_document_name'] ?? null),
            'effective_from' => $this->normalizeDate((string) ($version['effective_from'] ?? '')),
            'updated_at' => $now,
        ]);

        $existing = $this->findSchedule($scheduleCode, $vaccineId, $payload);
        if ($existing !== []) {
            $scheduleId = (int) $existing['id'];
            $this->db->table('immunization_schedule_master')->where('id', $scheduleId)->update($payload);
            $this->syncGeneratedRecords($scheduleId, $item, $payload);
            return ['created' => false, 'schedule_id' => $scheduleId, 'schedule_code' => $scheduleCode];
        }

        $payload['created_at'] = $now;
        $this->db->table('immunization_schedule_master')->insert($payload);
        return ['created' => true, 'schedule_id' => (int) $this->db->insertID(), 'schedule_code' => $scheduleCode];
    }

    private function findSchedule(string $scheduleCode, int $vaccineId, array $payload): array
    {
        $fields = $this->db->getFieldNames('immunization_schedule_master') ?? [];
        if ($scheduleCode !== '' && in_array('schedule_code', $fields, true)) {
            $row = $this->db->table('immunization_schedule_master')->where('schedule_code', $scheduleCode)->get(1)->getRowArray();
            if (! empty($row)) {
                return $row;
            }
        }

        $row = $this->db->table('immunization_schedule_master')
            ->where('vaccine_master_id', $vaccineId)
            ->where('age_offset_days', (int) ($payload['age_offset_days'] ?? 0))
            ->where('dose_number', (string) ($payload['dose_number'] ?? ''))
            ->get(1)
            ->getRowArray() ?? [];
        if (! empty($row)) {
            return $row;
        }

        return $this->db->table('immunization_schedule_master')
            ->where('vaccine_master_id', $vaccineId)
            ->where('age_offset_days', (int) ($payload['age_offset_days'] ?? 0))
            ->orderBy('id', 'ASC')
            ->get(1)
            ->getRowArray() ?? [];
    }

    private function syncGeneratedRecords(int $scheduleId, array $item, array $schedulePayload): void
    {
        if (! $this->db->tableExists('immunization_records')) {
            return;
        }

        $this->db->table('immunization_records')
            ->where('schedule_id', $scheduleId)
            ->where('status !=', 'completed')
            ->update($this->filterColumns('immunization_records', [
                'vaccine_name' => (string) ($item['vaccine_name'] ?? $item['vaccine_display'] ?? $item['series_name'] ?? $item['vaccine_code'] ?? ''),
                'vaccine_code' => $this->nullableString($item['vaccine_code'] ?? null),
                'vaccine_code_system' => $this->nullableString($item['vaccine_code_system'] ?? null),
                'dose_number' => $this->nullableString($schedulePayload['dose_number'] ?? null),
                'route_code' => $this->nullableString($item['route_code'] ?? null),
                'route_name' => $this->nullableString($item['route_name'] ?? null),
                'site_code' => $this->nullableString($item['site_code'] ?? null),
                'site_name' => $this->nullableString($item['site_name'] ?? null),
                'notes' => $this->nullableString($schedulePayload['notes'] ?? null),
                'updated_at' => date('Y-m-d H:i:s'),
            ]));
    }

    /**
     * @param array<int,string> $syncedCodes
     */
    private function deactivateMissingBridgeRows(array $syncedCodes, string $versionCode): void
    {
        if ($syncedCodes === [] || ! in_array('schedule_code', $this->db->getFieldNames('immunization_schedule_master') ?? [], true)) {
            return;
        }

        $builder = $this->db->table('immunization_schedule_master')
            ->where('is_uip', 1)
            ->where('schedule_code IS NOT NULL', null, false)
            ->where('schedule_code !=', '')
            ->whereNotIn('schedule_code', $syncedCodes);
        if ($versionCode !== '') {
            $builder->where('source_version_code !=', $versionCode);
        }
        $builder->update(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    private function writeSyncLog(array $version, string $status, int $itemCount, string $message): void
    {
        if (! $this->db->tableExists('immunization_master_sync_log')) {
            return;
        }

        $this->db->table('immunization_master_sync_log')->insert([
            'master_type' => (string) ($version['master_type'] ?? 'UIP_SCHEDULE'),
            'version_code' => $this->nullableString($version['version_code'] ?? null),
            'effective_from' => $this->normalizeDate((string) ($version['effective_from'] ?? '')),
            'checksum' => $this->nullableString($version['checksum'] ?? null),
            'source_name' => $this->nullableString($version['source_name'] ?? null),
            'source_url' => $this->nullableString($version['source_url'] ?? null),
            'source_document_name' => $this->nullableString($version['source_document_name'] ?? null),
            'status' => $status,
            'item_count' => $itemCount,
            'message' => $message,
            'synced_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function bridgeError(array $response): string
    {
        $message = trim((string) ($response['message'] ?? $response['error_text'] ?? $response['error_code'] ?? ''));
        if ($message !== '') {
            return $message;
        }
        return 'Bridge request failed.';
    }

    private function filterColumns(string $table, array $payload): array
    {
        $fields = $this->db->getFieldNames($table) ?? [];
        return array_intersect_key($payload, array_flip($fields));
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value !== '' ? $value : null;
    }

    private function nullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int) $value;
    }

    private function boolInt($value): int
    {
        return in_array($value, [1, '1', true, 'true', 'yes', 'Y'], true) ? 1 : 0;
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }
}
<?php

namespace App\Libraries;

use App\Models\ClinicalAuditTrailModel;
use CodeIgniter\I18n\Time;

class ClinicalAuditTrail
{
    private ClinicalAuditTrailModel $auditModel;

    public function __construct(?ClinicalAuditTrailModel $auditModel = null)
    {
        $this->auditModel = $auditModel ?? new ClinicalAuditTrailModel();
    }

    /**
     * @param string|int|null $recordId
     * @param string|int|null $userId
     * @param mixed $oldValue
     * @param mixed $newValue
     */
    public function logFieldUpdate(
        string $module,
        $recordId,
        string $fieldName,
        $oldValue,
        $newValue,
        $userId
    ): bool {
        $actionAt = Time::now('Asia/Kolkata')->toDateTimeString();

        $oldValueJson = $this->toJson($oldValue);
        $newValueJson = $this->toJson($newValue);
        $recordIdText = (string) $recordId;
        $userIdText = $userId === null || $userId === '' ? 'system' : (string) $userId;

        $hash = hash(
            'sha256',
            $module . '|' . $recordIdText . '|' . $fieldName . '|' . $oldValueJson . '|' . $newValueJson . '|' . $userIdText . '|' . $actionAt
        );

        return (bool) $this->auditModel->insert([
            'module' => $module,
            'record_id' => $recordIdText,
            'field_name' => $fieldName,
            'old_value' => $oldValueJson,
            'new_value' => $newValueJson,
            'user_id' => $userIdText,
            'action_at' => $actionAt,
            'hash' => $hash,
        ]);
    }

    /**
     * @param array<string, mixed> $oldData
     * @param array<string, mixed> $newData
     * @param string|int|null $recordId
     * @param string|int|null $userId
     */
    public function logChangedFields(string $module, $recordId, array $oldData, array $newData, $userId): void
    {
        foreach ($newData as $field => $newValue) {
            $oldValue = $oldData[$field] ?? null;

            if ($oldValue === $newValue) {
                continue;
            }

            $this->logFieldUpdate($module, $recordId, (string) $field, $oldValue, $newValue, $userId);
        }
    }

    /**
     * @param mixed $value
     */
    private function toJson($value): string
    {
        return (string) json_encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
}

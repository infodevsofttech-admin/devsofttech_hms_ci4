<?php

namespace App\Models;

use CodeIgniter\Model;

class UserSettings extends Model
{
    protected $table      = 'user_settings';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = ['user_id', 'setting_key', 'setting_value'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get user setting value by key.
     */
    public function getUserSetting(int $userId, string $settingKey, string $default = ''): string
    {
        if ($userId <= 0 || $settingKey === '') {
            return $default;
        }

        $row = $this->where('user_id', $userId)
            ->where('setting_key', $settingKey)
            ->first();

        if ($row && isset($row['setting_value'])) {
            return (string) $row['setting_value'];
        }

        return $default;
    }

    /**
     * Set user setting value.
     */
    public function setUserSetting(int $userId, string $settingKey, string $value): bool
    {
        if ($userId <= 0 || $settingKey === '') {
            return false;
        }

        $existing = $this->where('user_id', $userId)
            ->where('setting_key', $settingKey)
            ->first();

        if ($existing) {
            return (bool) $this->update($existing['id'], ['setting_value' => $value]);
        }

        return (bool) $this->insert([
            'user_id' => $userId,
            'setting_key' => $settingKey,
            'setting_value' => $value,
        ]);
    }

    /**
     * Get all settings for a user.
     */
    public function getUserSettings(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $rows = $this->where('user_id', $userId)->findAll();
        $result = [];

        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }

        return $result;
    }

    /**
     * Delete a user setting.
     */
    public function deleteUserSetting(int $userId, string $settingKey): bool
    {
        if ($userId <= 0 || $settingKey === '') {
            return false;
        }

        return (bool) $this->where('user_id', $userId)
            ->where('setting_key', $settingKey)
            ->delete();
    }
}

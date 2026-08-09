<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use InvalidArgumentException;
use RuntimeException;

class RoleRegistry
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    public function all(): array
    {
        $roles = $this->db->table('auth_roles r')
            ->select('r.*, COUNT(DISTINCT rp.id) AS permission_count, COUNT(DISTINCT gu.id) AS user_count')
            ->join('auth_role_permissions rp', 'rp.role_id = r.id', 'left')
            ->join('auth_groups_users gu', 'gu.group = r.alias', 'left')
            ->groupBy('r.id')
            ->orderBy('r.is_builtin', 'DESC')
            ->orderBy('r.title', 'ASC')
            ->get()
            ->getResultArray();

        return $roles;
    }

    public function find(int $roleId): ?array
    {
        $role = $this->db->table('auth_roles')->where('id', $roleId)->get(1)->getRowArray();
        if ($role === null) {
            return null;
        }

        $role['permissions'] = array_column(
            $this->db->table('auth_role_permissions')->select('permission')->where('role_id', $roleId)->orderBy('permission')->get()->getResultArray(),
            'permission'
        );

        return $role;
    }

    public function activeGroups(): array
    {
        $groups = [];
        foreach ($this->db->table('auth_roles')->where('is_active', 1)->orderBy('title')->get()->getResultArray() as $role) {
            $groups[$role['alias']] = [
                'title' => $role['title'],
                'description' => $role['description'] ?? '',
            ];
        }

        return $groups;
    }

    public function save(?int $roleId, array $input, int $userId): int
    {
        $alias = strtolower(trim((string) ($input['alias'] ?? '')));
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $permissions = $this->validatePermissions((array) ($input['permissions'] ?? []));

        if (! preg_match('/^[a-z][a-z0-9_]{2,63}$/', $alias)) {
            throw new InvalidArgumentException('Role code must start with a letter and contain only lowercase letters, numbers, and underscores.');
        }
        if ($title === '' || mb_strlen($title) > 120) {
            throw new InvalidArgumentException('Role name is required and must be at most 120 characters.');
        }
        if (mb_strlen($description) > 255) {
            throw new InvalidArgumentException('Description must be at most 255 characters.');
        }

        $existing = $this->db->table('auth_roles')->where('alias', $alias)->get(1)->getRowArray();
        if ($existing !== null && (int) $existing['id'] !== (int) $roleId) {
            throw new InvalidArgumentException('Role code is already in use.');
        }

        $current = $roleId ? $this->find($roleId) : null;
        if ($roleId && $current === null) {
            throw new InvalidArgumentException('Role not found.');
        }
        if ($current !== null && (int) $current['is_builtin'] === 1 && $alias !== $current['alias']) {
            throw new InvalidArgumentException('Built-in role codes cannot be changed.');
        }
        if (($current['alias'] ?? '') === 'superadmin') {
            $permissions = $current['permissions'];
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transStart();
        if ($current === null) {
            $this->db->table('auth_roles')->insert([
                'alias' => $alias,
                'title' => $title,
                'description' => $description !== '' ? $description : null,
                'is_active' => 1,
                'is_builtin' => 0,
                'created_by' => $userId ?: null,
                'updated_by' => $userId ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $roleId = (int) $this->db->insertID();
        } else {
            $this->db->table('auth_roles')->where('id', $roleId)->update([
                'title' => $title,
                'description' => $description !== '' ? $description : null,
                'updated_by' => $userId ?: null,
                'updated_at' => $now,
            ]);
            $this->db->table('auth_role_permissions')->where('role_id', $roleId)->delete();
        }

        foreach ($permissions as $permission) {
            $this->db->table('auth_role_permissions')->insert([
                'role_id' => $roleId,
                'permission' => $permission,
                'created_by' => $userId ?: null,
                'created_at' => $now,
            ]);
        }
        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            throw new RuntimeException('Unable to save role.');
        }

        $this->publish();
        return (int) $roleId;
    }

    public function setActive(int $roleId, bool $active, int $userId): void
    {
        $role = $this->find($roleId);
        if ($role === null) {
            throw new InvalidArgumentException('Role not found.');
        }
        if ((int) $role['is_builtin'] === 1) {
            throw new InvalidArgumentException('Built-in roles cannot be deactivated.');
        }

        $this->db->table('auth_roles')->where('id', $roleId)->update([
            'is_active' => $active ? 1 : 0,
            'updated_by' => $userId ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->publish();
    }

    public function delete(int $roleId): void
    {
        $role = $this->find($roleId);
        if ($role === null) {
            throw new InvalidArgumentException('Role not found.');
        }
        if ((int) $role['is_builtin'] === 1) {
            throw new InvalidArgumentException('Built-in roles cannot be deleted.');
        }
        if ((int) $role['is_active'] === 1) {
            throw new InvalidArgumentException('Deactivate the role before deleting it.');
        }
        $assignments = $this->db->table('auth_groups_users')->where('group', $role['alias'])->countAllResults();
        if ($assignments > 0) {
            throw new InvalidArgumentException('This role is assigned to users and cannot be deleted.');
        }

        $this->db->table('auth_roles')->where('id', $roleId)->delete();
        $this->publish();
    }

    public function publish(): void
    {
        $groups = $this->activeGroups();
        $matrix = [];
        $rows = $this->db->table('auth_roles r')
            ->select('r.alias,rp.permission')
            ->join('auth_role_permissions rp', 'rp.role_id = r.id', 'left')
            ->where('r.is_active', 1)
            ->orderBy('r.id')
            ->get()
            ->getResultArray();
        foreach ($groups as $alias => $_role) {
            $matrix[$alias] = [];
        }
        foreach ($rows as $row) {
            if (! empty($row['permission'])) {
                $matrix[$row['alias']][] = $row['permission'];
            }
        }

        setting('AuthGroups.groups', $groups);
        setting('AuthGroups.matrix', $matrix);
    }

    private function validatePermissions(array $permissions): array
    {
        $permissions = array_values(array_unique(array_filter(array_map(static fn ($value): string => strtolower(trim((string) $value)), $permissions))));
        $known = array_keys((array) setting('AuthGroups.permissions'));
        $allowedWildcards = [];
        foreach ($known as $permission) {
            $scope = strstr($permission, '.', true);
            if ($scope !== false) {
                $allowedWildcards[] = $scope . '.*';
            }
        }
        $allowed = array_merge($known, array_unique($allowedWildcards));
        foreach ($permissions as $permission) {
            if (! in_array($permission, $allowed, true)) {
                throw new InvalidArgumentException('Unknown or unsupported permission: ' . $permission);
            }
        }

        return $permissions;
    }
}

<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use App\Libraries\RoleRegistry;
use InvalidArgumentException;
use Throwable;

class RoleManagement extends BaseController
{
    public function index(): string
    {
        return $this->listView();
    }

    public function create(): string
    {
        return $this->formView();
    }

    public function store(): string
    {
        return $this->save(null);
    }

    public function edit(int $roleId): string
    {
        $role = (new RoleRegistry($this->db))->find($roleId);
        if ($role === null) {
            return $this->listView(null, ['Role not found.']);
        }

        return $this->formView($role);
    }

    public function update(int $roleId): string
    {
        return $this->save($roleId);
    }

    public function status(int $roleId)
    {
        $registry = new RoleRegistry($this->db);
        $role = $registry->find($roleId);
        try {
            if ($role === null) {
                throw new InvalidArgumentException('Role not found.');
            }
            $active = (int) $this->request->getPost('is_active') === 1;
            $registry->setActive($roleId, $active, $this->currentUserId());
            $this->auditClinicalUpdate('role_master', 'status', $roleId, ['is_active' => (int) $role['is_active']], ['is_active' => $active ? 1 : 0]);

            return $this->response->setJSON(['update' => 1, 'message' => $active ? 'Role activated.' : 'Role deactivated.', 'csrf_hash' => csrf_hash()]);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON(['update' => 0, 'error_text' => $e->getMessage(), 'csrf_hash' => csrf_hash()]);
        }
    }

    public function delete(int $roleId)
    {
        $registry = new RoleRegistry($this->db);
        $role = $registry->find($roleId);
        try {
            if ($role === null) {
                throw new InvalidArgumentException('Role not found.');
            }
            $registry->delete($roleId);
            $this->auditClinicalUpdate('role_master', 'delete', $roleId, $role, ['deleted' => true]);

            return $this->response->setJSON(['update' => 1, 'message' => 'Role deleted.', 'csrf_hash' => csrf_hash()]);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON(['update' => 0, 'error_text' => $e->getMessage(), 'csrf_hash' => csrf_hash()]);
        }
    }

    private function save(?int $roleId): string
    {
        $registry = new RoleRegistry($this->db);
        $oldRole = $roleId ? $registry->find($roleId) : null;
        $input = [
            'alias' => $oldRole['alias'] ?? $this->request->getPost('alias'),
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'permissions' => (array) $this->request->getPost('permissions'),
        ];

        try {
            $savedId = $registry->save($roleId, $input, $this->currentUserId());
            $newRole = $registry->find($savedId);
            $this->auditClinicalUpdate('role_master', $roleId ? 'update' : 'create', $savedId, $oldRole, $newRole);

            return $this->listView($roleId ? 'Role updated successfully.' : 'Role created successfully.');
        } catch (Throwable $e) {
            $role = array_merge($oldRole ?? [], $input);
            return $this->formView($role, [$e->getMessage()]);
        }
    }

    private function listView(?string $message = null, array $errors = []): string
    {
        return view('Setting/Admin/RoleManagement/index', [
            'roles' => (new RoleRegistry($this->db))->all(),
            'message' => $message,
            'errors' => $errors,
        ]);
    }

    private function formView(?array $role = null, array $errors = []): string
    {
        $permissions = (array) setting('AuthGroups.permissions');
        $wildcards = [];
        foreach (array_keys($permissions) as $permission) {
            $scope = strstr($permission, '.', true);
            if ($scope !== false) {
                $wildcards[$scope . '.*'] = 'All ' . ucwords(str_replace('_', ' ', $scope)) . ' permissions';
            }
        }

        return view('Setting/Admin/RoleManagement/form', [
            'role' => $role,
            'permissions' => $permissions,
            'wildcards' => $wildcards,
            'errors' => $errors,
        ]);
    }

    private function currentUserId(): int
    {
        return function_exists('auth') ? (int) (auth()->user()->id ?? 0) : 0;
    }
}

<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use App\Libraries\UserSessionRegistry;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

class UserManagement extends BaseController
{
    private function currentUserId(): int
    {
        return function_exists('auth') ? (int) (auth()->user()->id ?? 0) : 0;
    }

    private function canManageAdmins(): bool
    {
        return function_exists('auth') && auth()->loggedIn() && auth()->user()->can('users.manage-admins');
    }

    private function availableRoles(): array
    {
        $roles = (array) setting('AuthGroups.groups');
        if (! $this->canManageAdmins()) {
            foreach (['superadmin', 'admin', 'developer'] as $privilegedRole) {
                unset($roles[$privilegedRole]);
            }
        }

        return $roles;
    }

    private function isPrivilegedUser(User $user): bool
    {
        return $user->inGroup('superadmin', 'admin', 'developer') || $user->can('users.manage-admins');
    }

    public function index(): string
    {
        $userModel = model(UserModel::class);

        $users = $userModel
            ->withIdentities()
            ->withGroups()
            ->findAll();

        return view('Setting/Admin/UserManagement/index', [
            'users' => $users,
        ]);
    }

    public function create(): string
    {
        return view('Setting/Admin/UserManagement/create', [
            'roles' => $this->availableRoles(),
        ]);
    }

    public function store()
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[30]|regex_match[/^[a-zA-Z0-9.]+$/]',
            'email'    => 'required|valid_email|max_length[254]',
            'password' => 'required|min_length[8]',
            'role'     => 'required',
            'person_name' => 'permit_empty|max_length[120]',
            'phone_no' => 'permit_empty|regex_match[/^[0-9+()\\s-]{7,20}$/]',
        ];

        if (! $this->validate($rules)) {
            if ($this->request->isAJAX()) {
                return view('Setting/Admin/UserManagement/create', [
                    'roles' => $this->availableRoles(),
                    'errors' => $this->validator->getErrors(),
                    'formData' => $this->request->getPost(),
                ]);
            }

            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $role = strtolower((string) $this->request->getPost('role'));
        $allowedRoles = array_keys($this->availableRoles());
        if (! in_array($role, $allowedRoles, true)) {
            $errors = ['role' => 'Invalid role selected.'];
            if ($this->request->isAJAX()) {
                return view('Setting/Admin/UserManagement/create', [
                    'roles' => $this->availableRoles(),
                    'errors' => $errors,
                    'formData' => $this->request->getPost(),
                ]);
            }

            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $user = new User();
        $user->username = (string) $this->request->getPost('username');
        $user->email = (string) $this->request->getPost('email');
        $user->password = (string) $this->request->getPost('password');
        $user->active = 1;

        $userModel = model(UserModel::class);
        try {
            if (! $userModel->save($user)) {
                throw new \RuntimeException(implode(' ', $userModel->errors()));
            }
        } catch (\Throwable $e) {
            return view('Setting/Admin/UserManagement/create', [
                'roles' => $this->availableRoles(),
                'errors' => ['user' => $e->getMessage() !== '' ? $e->getMessage() : 'Unable to create user.'],
                'formData' => $this->request->getPost(),
            ]);
        }

        $savedUser = $userModel->find($userModel->getInsertID());
        if ($savedUser !== null) {
            $savedUser->addGroup($role);
            $this->syncIdentityMeta(
                (int) $savedUser->id,
                trim((string) $this->request->getPost('person_name')),
                trim((string) $this->request->getPost('phone_no'))
            );
            $this->auditClinicalUpdate('user_management', 'create', $savedUser->id, null, [
                'username' => $savedUser->username,
                'role' => $role,
                'active' => 1,
            ]);
        }

        if ($this->request->isAJAX()) {
            $users = $userModel
                ->withIdentities()
                ->withGroups()
                ->findAll();

            return view('Setting/Admin/UserManagement/index', [
                'users' => $users,
                'message' => 'User created successfully.',
            ]);
        }

        return redirect()
            ->to('setting/admin/user-management')
            ->with('message', 'User created successfully.');
    }

    public function permissions(): string
    {
        $userModel = model(UserModel::class);

        $users = $userModel
            ->withIdentities()
            ->withGroups()
            ->findAll();

        $selectedUserId = (int) $this->request->getGet('user_id');
        $selectedUser = null;
        if ($selectedUserId) {
            $selectedUser = $userModel->withPermissions()->find($selectedUserId);
        }

        $roleContext = $this->rolePermissionContext($selectedUser);

        return view('Setting/Admin/UserManagement/permissions', [
            'users' => $users,
            'permissions' => setting('AuthGroups.permissions'),
            'selectedUser' => $selectedUser,
            'selectedRoleTitles' => $roleContext['titles'],
            'inheritedPermissions' => $roleContext['permissions'],
        ]);
    }

    public function edit(int $userId): string
    {
        $userModel = model(UserModel::class);
        $user = $userModel
            ->withIdentities()
            ->withGroups()
            ->find($userId);

        if ($user === null) {
            $users = $userModel
                ->withIdentities()
                ->withGroups()
                ->findAll();

            return view('Setting/Admin/UserManagement/index', [
                'users' => $users,
                'errors' => ['User not found.'],
            ]);
        }

        if ($this->isPrivilegedUser($user) && ! $this->canManageAdmins()) {
            return view('Setting/Admin/UserManagement/index', [
                'users' => $userModel->withIdentities()->withGroups()->findAll(),
                'errors' => ['You cannot edit an administrator account.'],
            ]);
        }

        $emailIdentity = $user->getEmailIdentity();
        $email = $emailIdentity ? (string) ($emailIdentity->secret ?? '') : '';
        $meta = $this->decodeIdentityExtra($emailIdentity->extra ?? null);

        return view('Setting/Admin/UserManagement/edit', [
            'user' => $user,
            'email' => $email,
            'person_name' => trim((string) ($meta['full_name'] ?? '')),
            'phone_no' => trim((string) ($meta['phone_no'] ?? '')),
            'roles' => $this->availableRoles(),
            'current_role' => (string) (($user->getGroups() ?? [])[0] ?? ''),
        ]);
    }

    public function update(int $userId)
    {
        $userModel = model(UserModel::class);
        $user = $userModel->withGroups()->find($userId);
        if ($user === null) {
            $users = $userModel
                ->withIdentities()
                ->withGroups()
                ->findAll();

            return view('Setting/Admin/UserManagement/index', [
                'users' => $users,
                'errors' => ['User not found.'],
            ]);
        }

        if ($this->isPrivilegedUser($user) && ! $this->canManageAdmins()) {
            return view('Setting/Admin/UserManagement/index', [
                'users' => $userModel->withIdentities()->withGroups()->findAll(),
                'errors' => ['You cannot edit an administrator account.'],
            ]);
        }

        $username = trim((string) $this->request->getPost('username'));
        $email = trim((string) $this->request->getPost('email'));
        $personName = trim((string) $this->request->getPost('person_name'));
        $phoneNo = trim((string) $this->request->getPost('phone_no'));
        $password = (string) $this->request->getPost('password');
        $active = $this->request->getPost('active') ? 1 : 0;
        $role = strtolower(trim((string) $this->request->getPost('role')));
        $oldGroups = $user->getGroups() ?? [];
        $oldState = [
            'username' => (string) ($user->username ?? ''),
            'groups' => $oldGroups,
            'active' => (int) ($user->active ?? 0),
        ];

        $errors = [];
        if ($username === '' || strlen($username) < 3 || strlen($username) > 30 || preg_match('/\A[a-zA-Z0-9\.]+\z/', $username) !== 1) {
            $errors['username'] = 'Login ID must be 3-30 chars and only letters, numbers, dot.';
        }
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required.';
        }
        if ($personName !== '' && mb_strlen($personName) > 120) {
            $errors['person_name'] = 'Person name must be maximum 120 characters.';
        }
        if ($phoneNo !== '' && preg_match('/^[0-9+\-()\s]{7,20}$/', $phoneNo) !== 1) {
            $errors['phone_no'] = 'Phone format is invalid.';
        }
        if ($password !== '' && strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }
        if (! array_key_exists($role, $this->availableRoles())) {
            $errors['role'] = 'Invalid role selected.';
        }
        if ($userId === $this->currentUserId() && $active !== 1) {
            $errors['active'] = 'You cannot deactivate your own account.';
        }

        $tables = config('Auth')->tables;
        $usersTable = (string) ($tables['users'] ?? 'users');
        $identitiesTable = (string) ($tables['identities'] ?? 'auth_identities');

        if (empty($errors)) {
            $duplicateUsername = $this->db->table($usersTable)
                ->select('id')
                ->where('username', $username)
                ->where('id !=', $userId)
                ->get(1)
                ->getRowArray();
            if (! empty($duplicateUsername)) {
                $errors['username'] = 'Login ID already used by another user.';
            }

            $duplicateEmail = $this->db->table($identitiesTable)
                ->select('id')
                ->where('type', 'email_password')
                ->where('secret', $email)
                ->where('user_id !=', $userId)
                ->get(1)
                ->getRowArray();
            if (! empty($duplicateEmail)) {
                $errors['email'] = 'Email already used by another user.';
            }
        }

        if (! empty($errors)) {
            return view('Setting/Admin/UserManagement/edit', [
                'user' => $userModel->withIdentities()->find($userId),
                'email' => $email,
                'person_name' => $personName,
                'phone_no' => $phoneNo,
                'roles' => $this->availableRoles(),
                'current_role' => $role,
                'errors' => $errors,
                'formData' => [
                    'username' => $username,
                    'email' => $email,
                    'person_name' => $personName,
                    'phone_no' => $phoneNo,
                    'active' => $active,
                    'role' => $role,
                ],
            ]);
        }

        $user->username = $username;
        $user->email = $email;
        $user->active = $active;
        if ($password !== '') {
            $user->password = $password;
        }

        $userModel->save($user);
        $user->syncGroups($role);
        $this->syncIdentityMeta($userId, $personName, $phoneNo);
        $this->auditClinicalUpdate('user_management', 'update', $userId, $oldState, [
            'username' => $username,
            'groups' => [$role],
            'active' => $active,
        ]);

        if ($active !== 1) {
            (new UserSessionRegistry($this->db))->revokeUser($userId, $this->currentUserId(), 'account_deactivated');
        }

        $users = $userModel
            ->withIdentities()
            ->withGroups()
            ->findAll();

        return view('Setting/Admin/UserManagement/index', [
            'users' => $users,
            'message' => 'User updated successfully.',
        ]);
    }

    public function resetPasswordForm(int $userId): string
    {
        $userModel = model(UserModel::class);
        $user = $userModel->find($userId);

        if ($user === null) {
            $users = $userModel
                ->withIdentities()
                ->withGroups()
                ->findAll();

            return view('Setting/Admin/UserManagement/index', [
                'users' => $users,
                'errors' => ['User not found.'],
            ]);
        }

        if ($this->isPrivilegedUser($user) && ! $this->canManageAdmins()) {
            return view('Setting/Admin/UserManagement/index', [
                'users' => $userModel->withIdentities()->withGroups()->findAll(),
                'errors' => ['You cannot reset an administrator password.'],
            ]);
        }

        return view('Setting/Admin/UserManagement/reset_password', [
            'user' => $user,
        ]);
    }

    public function resetPassword(int $userId)
    {
        $userModel = model(UserModel::class);
        $user = $userModel->withGroups()->find($userId);
        if ($user === null) {
            $users = $userModel
                ->withIdentities()
                ->withGroups()
                ->findAll();

            return view('Setting/Admin/UserManagement/index', [
                'users' => $users,
                'errors' => ['User not found.'],
            ]);
        }
        if ($this->isPrivilegedUser($user) && ! $this->canManageAdmins()) {
            return view('Setting/Admin/UserManagement/index', [
                'users' => $userModel->withIdentities()->withGroups()->findAll(),
                'errors' => ['You cannot reset an administrator password.'],
            ]);
        }

        $password = trim((string) $this->request->getPost('password'));
        $passwordConfirm = trim((string) $this->request->getPost('password_confirm'));
        $errors = [];

        if (preg_match('/^\d{6}$/', $password) !== 1) {
            $errors['password'] = 'Temporary PIN must be exactly 6 digits.';
        }

        if (! hash_equals($password, $passwordConfirm)) {
            $errors['password_confirm'] = 'PIN and confirm PIN do not match.';
        }

        if (! empty($errors)) {
            return view('Setting/Admin/UserManagement/reset_password', [
                'user' => $user,
                'errors' => $errors,
            ]);
        }

        $tables = config('Auth')->tables;
        $identitiesTable = (string) ($tables['identities'] ?? 'auth_identities');
        if (! $this->db->tableExists($identitiesTable)) {
            $users = $userModel
                ->withIdentities()
                ->withGroups()
                ->findAll();

            return view('Setting/Admin/UserManagement/index', [
                'users' => $users,
                'errors' => ['Auth identity table not found.'],
            ]);
        }

        $identity = $this->db->table($identitiesTable)
            ->select('id')
            ->where('user_id', $userId)
            ->where('type', 'email_password')
            ->get(1)
            ->getRowArray();

        if (empty($identity['id'])) {
            $users = $userModel
                ->withIdentities()
                ->withGroups()
                ->findAll();

            return view('Setting/Admin/UserManagement/index', [
                'users' => $users,
                'errors' => ['User login identity not found.'],
            ]);
        }

        $this->db->table($identitiesTable)
            ->where('id', (int) $identity['id'])
            ->update([
                'secret2' => password_hash($password, PASSWORD_DEFAULT),
                'force_reset' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        (new UserSessionRegistry($this->db))->revokeUser($userId, $this->currentUserId(), 'password_reset');
        $this->auditClinicalUpdate('user_management', 'password_reset', $userId, null, ['force_reset' => 1]);

        $users = $userModel
            ->withIdentities()
            ->withGroups()
            ->findAll();

        return view('Setting/Admin/UserManagement/index', [
            'users' => $users,
            'message' => 'Temporary 6-digit PIN set successfully. User must change password on next login.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeIdentityExtra($extra): array
    {
        if (is_array($extra)) {
            return $extra;
        }
        if (is_string($extra) && trim($extra) !== '') {
            $decoded = json_decode($extra, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function syncIdentityMeta(int $userId, string $personName, string $phoneNo): void
    {
        $tables = config('Auth')->tables;
        $identitiesTable = (string) ($tables['identities'] ?? 'auth_identities');
        if (! $this->db->tableExists($identitiesTable)) {
            return;
        }

        $row = $this->db->table($identitiesTable)
            ->select('id,extra')
            ->where('user_id', $userId)
            ->where('type', 'email_password')
            ->get(1)
            ->getRowArray();

        if (empty($row) || empty($row['id'])) {
            return;
        }

        $payload = $this->decodeIdentityExtra($row['extra'] ?? null);
        if ($personName === '') {
            unset($payload['full_name']);
        } else {
            $payload['full_name'] = $personName;
        }

        if ($phoneNo === '') {
            unset($payload['phone_no']);
        } else {
            $payload['phone_no'] = $phoneNo;
        }

        $this->db->table($identitiesTable)
            ->where('id', (int) $row['id'])
            ->update([
                'extra' => empty($payload) ? null : json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
    }

    public function savePermissions()
    {
        $rules = [
            'user_id' => 'required|is_natural_no_zero',
        ];

        if (! $this->validate($rules)) {
            if ($this->request->isAJAX()) {
                $users = model(UserModel::class)
                    ->withIdentities()
                    ->withGroups()
                    ->findAll();

                return view('Setting/Admin/UserManagement/permissions', [
                    'users' => $users,
                    'permissions' => setting('AuthGroups.permissions'),
                    'selectedUser' => null,
                    'errors' => $this->validator->getErrors(),
                ]);
            }

            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userId = (int) $this->request->getPost('user_id');
        $permissions = (array) $this->request->getPost('permissions');
        $permissions = array_values(array_filter(array_map('strtolower', $permissions)));

        $allowedPermissions = array_keys(setting('AuthGroups.permissions'));
        $permissions = array_values(array_intersect($permissions, $allowedPermissions));

        $userModel = model(UserModel::class);
        $user = $userModel->find($userId);
        if ($user === null) {
            $errors = ['user_id' => 'User not found.'];
            if ($this->request->isAJAX()) {
                $users = $userModel
                    ->withIdentities()
                    ->withGroups()
                    ->findAll();

                return view('Setting/Admin/UserManagement/permissions', [
                    'users' => $users,
                    'permissions' => setting('AuthGroups.permissions'),
                    'selectedUser' => null,
                    'errors' => $errors,
                ]);
            }

            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $oldPermissions = $userModel->withPermissions()->find($userId)?->getPermissions() ?? [];
        $user->syncPermissions(...$permissions);
        $this->auditClinicalUpdate('user_management', 'permissions', $userId, $oldPermissions, $permissions);

        if ($this->request->isAJAX()) {
            $users = $userModel
                ->withIdentities()
                ->withGroups()
                ->findAll();

            $selectedUser = $userModel->withPermissions()->find($userId);
            $roleContext = $this->rolePermissionContext($selectedUser);

            return view('Setting/Admin/UserManagement/permissions', [
                'users' => $users,
                'permissions' => setting('AuthGroups.permissions'),
                'selectedUser' => $selectedUser,
                'selectedRoleTitles' => $roleContext['titles'],
                'inheritedPermissions' => $roleContext['permissions'],
                'message' => 'Permissions updated successfully.',
            ]);
        }

        return redirect()
            ->to('setting/admin/user-management/permissions?user_id=' . $userId)
            ->with('message', 'Permissions updated successfully.');
    }

    /**
     * @return array{titles: list<string>, permissions: list<string>}
     */
    private function rolePermissionContext(?User $user): array
    {
        if ($user === null) {
            return ['titles' => [], 'permissions' => []];
        }

        $availablePermissions = array_keys((array) setting('AuthGroups.permissions'));
        $groups = (array) setting('AuthGroups.groups');
        $matrix = (array) setting('AuthGroups.matrix');
        $inheritedPermissions = [];
        $roleTitles = [];

        foreach ($user->getGroups() ?? [] as $role) {
            $roleTitles[] = (string) ($groups[$role]['title'] ?? $role);
            foreach ($matrix[$role] ?? [] as $grant) {
                if (str_ends_with($grant, '.*')) {
                    $prefix = substr($grant, 0, -1);
                    foreach ($availablePermissions as $permission) {
                        if (str_starts_with($permission, $prefix)) {
                            $inheritedPermissions[] = $permission;
                        }
                    }
                } elseif (in_array($grant, $availablePermissions, true)) {
                    $inheritedPermissions[] = $grant;
                }
            }
        }

        return [
            'titles' => array_values(array_unique($roleTitles)),
            'permissions' => array_values(array_unique($inheritedPermissions)),
        ];
    }

    public function sessions(): string
    {
        $registry = new UserSessionRegistry($this->db);
        $registry->cleanup();
        $rows = $registry->onlineUsers();
        foreach ($rows as &$row) {
            $meta = $this->decodeIdentityExtra($row['extra'] ?? null);
            $row['person_name'] = trim((string) ($meta['full_name'] ?? ''));
            unset($row['extra']);
        }
        unset($row);

        return view('Setting/Admin/UserManagement/sessions', [
            'sessions' => $rows,
            'current_user_id' => $this->currentUserId(),
        ]);
    }

    public function forceLogout(int $userId)
    {
        if ($userId === $this->currentUserId()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Use Sign Out to end your own session.']);
        }

        $user = model(UserModel::class)->withGroups()->find($userId);
        if ($user === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'User not found.']);
        }
        if ($this->isPrivilegedUser($user) && ! $this->canManageAdmins()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'You cannot log out an administrator.']);
        }

        $count = (new UserSessionRegistry($this->db))->revokeUser($userId, $this->currentUserId());
        $this->auditClinicalUpdate('user_management', 'force_logout', $userId, null, ['sessions_revoked' => $count]);

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => $count > 0 ? 'User session ended.' : 'No active session found.',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function delete(int $userId)
    {
        if ($userId === $this->currentUserId()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'You cannot delete your own account.']);
        }

        $userModel = model(UserModel::class);
        $user = $userModel->withGroups()->find($userId);
        if ($user === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'User not found.']);
        }
        if ($this->isPrivilegedUser($user) && ! $this->canManageAdmins()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'You cannot delete an administrator.']);
        }

        (new UserSessionRegistry($this->db))->revokeUser($userId, $this->currentUserId(), 'account_deleted');
        $this->auditClinicalUpdate('user_management', 'delete', $userId, [
            'username' => $user->username,
            'groups' => $user->getGroups() ?? [],
        ], ['deleted' => true]);

        if (! $userModel->delete($userId)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Unable to delete user.']);
        }

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'User deleted successfully.',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }
}

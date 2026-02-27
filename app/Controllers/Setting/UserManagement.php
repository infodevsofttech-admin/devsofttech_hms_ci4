<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

class UserManagement extends BaseController
{
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
            'roles' => setting('AuthGroups.groups'),
        ]);
    }

    public function store()
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[30]',
            'email'    => 'required|valid_email|max_length[254]',
            'password' => 'required|min_length[8]',
            'role'     => 'required',
        ];

        if (! $this->validate($rules)) {
            if ($this->request->isAJAX()) {
                return view('Setting/Admin/UserManagement/create', [
                    'roles' => setting('AuthGroups.groups'),
                    'errors' => $this->validator->getErrors(),
                    'formData' => $this->request->getPost(),
                ]);
            }

            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $role = strtolower((string) $this->request->getPost('role'));
        $allowedRoles = array_keys(setting('AuthGroups.groups'));
        if (! in_array($role, $allowedRoles, true)) {
            $errors = ['role' => 'Invalid role selected.'];
            if ($this->request->isAJAX()) {
                return view('Setting/Admin/UserManagement/create', [
                    'roles' => setting('AuthGroups.groups'),
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
        $userModel->save($user);

        $savedUser = $userModel->find($userModel->getInsertID());
        if ($savedUser !== null) {
            $savedUser->addGroup($role);
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

        return view('Setting/Admin/UserManagement/permissions', [
            'users' => $users,
            'permissions' => setting('AuthGroups.permissions'),
            'selectedUser' => $selectedUser,
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

        $emailIdentity = $user->getEmailIdentity();
        $email = $emailIdentity ? (string) ($emailIdentity->secret ?? '') : '';
        $meta = $this->decodeIdentityExtra($emailIdentity->extra ?? null);

        return view('Setting/Admin/UserManagement/edit', [
            'user' => $user,
            'email' => $email,
            'person_name' => trim((string) ($meta['full_name'] ?? '')),
            'phone_no' => trim((string) ($meta['phone_no'] ?? '')),
        ]);
    }

    public function update(int $userId)
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

        $username = trim((string) $this->request->getPost('username'));
        $email = trim((string) $this->request->getPost('email'));
        $personName = trim((string) $this->request->getPost('person_name'));
        $phoneNo = trim((string) $this->request->getPost('phone_no'));
        $password = (string) $this->request->getPost('password');
        $active = $this->request->getPost('active') ? 1 : 0;

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
                'errors' => $errors,
                'formData' => [
                    'username' => $username,
                    'email' => $email,
                    'person_name' => $personName,
                    'phone_no' => $phoneNo,
                    'active' => $active,
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
        $this->syncIdentityMeta($userId, $personName, $phoneNo);

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

        return view('Setting/Admin/UserManagement/reset_password', [
            'user' => $user,
        ]);
    }

    public function resetPassword(int $userId)
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

        $user->syncPermissions(...$permissions);

        if ($this->request->isAJAX()) {
            $users = $userModel
                ->withIdentities()
                ->withGroups()
                ->findAll();

            $selectedUser = $userModel->withPermissions()->find($userId);

            return view('Setting/Admin/UserManagement/permissions', [
                'users' => $users,
                'permissions' => setting('AuthGroups.permissions'),
                'selectedUser' => $selectedUser,
                'message' => 'Permissions updated successfully.',
            ]);
        }

        return redirect()
            ->to('setting/admin/user-management/permissions?user_id=' . $userId)
            ->with('message', 'Permissions updated successfully.');
    }
}

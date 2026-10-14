<?php

namespace App\Models;

use CodeIgniter\Model;

class UserProfileModel extends Model
{
    protected $table = 'user_profiles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'full_name',
        'phone_no',
        'doctor_id',
        'nurse_id',
        'department',
        'designation',
        'employee_code',
        'avatar',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get profile for a specific user.
     */
    public function getProfile(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        return $this->where('user_id', $userId)->first();
    }

    /**
     * Save or update user profile.
     */
    public function saveProfile(int $userId, array $data): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $existing = $this->where('user_id', $userId)->first();
        $fields = [
            'full_name'     => isset($data['full_name']) ? trim((string) $data['full_name']) : null,
            'phone_no'      => isset($data['phone_no']) ? trim((string) $data['phone_no']) : null,
            'doctor_id'     => ! empty($data['doctor_id']) ? (int) $data['doctor_id'] : null,
            'nurse_id'      => ! empty($data['nurse_id']) ? (int) $data['nurse_id'] : null,
            'department'    => isset($data['department']) ? trim((string) $data['department']) : null,
            'designation'   => isset($data['designation']) ? trim((string) $data['designation']) : null,
            'employee_code' => isset($data['employee_code']) ? trim((string) $data['employee_code']) : null,
        ];

        if (isset($data['avatar'])) {
            $fields['avatar'] = trim((string) $data['avatar']);
        }

        if ($existing) {
            return (bool) $this->update($existing['id'], $fields);
        }

        $fields['user_id'] = $userId;
        return (bool) $this->insert($fields);
    }

    /**
     * Delete profile for a specific user.
     */
    public function deleteProfile(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        return (bool) $this->where('user_id', $userId)->delete();
    }

    /**
     * Get users with full profile, clinical link, and identity information.
     */
    public function getUsersList(array $filters = []): array
    {
        $builder = $this->db->table('users u')
            ->select('u.id, u.username, u.active, u.status, u.created_at, u.deleted_at')
            ->select('p.full_name, p.phone_no, p.doctor_id, p.nurse_id, p.department, p.designation, p.employee_code, p.avatar')
            ->select('ai.secret as email, ai.force_reset')
            ->select('(CASE WHEN p.doctor_id IS NOT NULL AND d.id IS NOT NULL THEN TRIM(CONCAT(COALESCE(d.p_title, "Dr."), " ", COALESCE(d.p_fname, ""), " ", COALESCE(d.p_lname, ""))) ELSE NULL END) as linked_doctor_name')
            ->select('d.doctor_reg_no as linked_doctor_reg')
            ->select('(CASE WHEN p.nurse_id IS NOT NULL AND n.id IS NOT NULL THEN n.name ELSE NULL END) as linked_nurse_name, n.nurse_code as linked_nurse_code')
            ->join('user_profiles p', 'p.user_id = u.id', 'left')
            ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = "email_password"', 'left')
            ->join('doctor_master d', 'd.id = p.doctor_id', 'left')
            ->join('nurse_master n', 'n.id = p.nurse_id', 'left');

        // By default show non-deleted users
        if (! empty($filters['include_deleted'])) {
            // Include soft-deleted
        } else {
            $builder->where('u.deleted_at IS NULL');
        }

        if (isset($filters['active']) && $filters['active'] !== '') {
            $builder->where('u.active', (int) $filters['active']);
        }

        if (! empty($filters['search'])) {
            $s = trim((string) $filters['search']);
            $builder->groupStart()
                ->like('u.username', $s)
                ->orLike('p.full_name', $s)
                ->orLike('p.phone_no', $s)
                ->orLike('ai.secret', $s)
                ->orLike('p.employee_code', $s)
                ->orLike('p.department', $s)
                ->groupEnd();
        }

        $builder->orderBy('u.id', 'ASC');
        $users = $builder->get()->getResultArray();

        // Attach groups/roles
        $groups = $this->db->table('auth_groups_users')->get()->getResultArray();
        $userGroups = [];
        foreach ($groups as $g) {
            $userGroups[(int) $g['user_id']][] = $g['group'];
        }

        foreach ($users as &$user) {
            $uid = (int) $user['id'];
            $user['groups'] = $userGroups[$uid] ?? [];
        }
        unset($user);

        // Filter by role if requested
        if (! empty($filters['role'])) {
            $roleFilter = strtolower(trim((string) $filters['role']));
            $users = array_values(array_filter($users, fn($u) => in_array($roleFilter, $u['groups'], true)));
        }

        return $users;
    }

    /**
     * Get active doctors for dropdown mapping.
     */
    public function getActiveDoctors(): array
    {
        if (! $this->db->tableExists('doctor_master')) {
            return [];
        }

        return $this->db->table('doctor_master')
            ->select('id, p_title, p_fname, p_lname, email1, mphone1, doctor_reg_no, active')
            ->where('active', '1')
            ->orWhere('active', 1)
            ->orderBy('p_fname', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get active nurses for dropdown mapping.
     */
    public function getActiveNurses(): array
    {
        if (! $this->db->tableExists('nurse_master')) {
            return [];
        }

        return $this->db->table('nurse_master')
            ->select('id, nurse_code, name, email, contact_no, designation, is_active')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get user profile by linked doctor ID.
     */
    public function getByDoctorId(int $doctorId): ?array
    {
        if ($doctorId <= 0) {
            return null;
        }

        return $this->where('doctor_id', $doctorId)->first();
    }

    /**
     * Get user profile by linked nurse ID.
     */
    public function getByNurseId(int $nurseId): ?array
    {
        if ($nurseId <= 0) {
            return null;
        }

        return $this->where('nurse_id', $nurseId)->first();
    }
}

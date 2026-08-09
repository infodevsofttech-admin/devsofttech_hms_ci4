<?php

namespace App\Database\Migrations;

use App\Libraries\RoleRegistry;
use CodeIgniter\Database\Migration;

class AddIpdNursingAndDischargePermissions extends Migration
{
    private const NEW_PERMISSIONS = [
        'ipd_nursing.view',
        'ipd_nursing.record.manage',
        'ipd_nursing.bed.transfer',
        'ipd_nursing.charge.manage',
        'ipd_discharge.view',
        'ipd_discharge.manage',
        'ipd_discharge.master.manage',
    ];

    public function up()
    {
        if (! $this->tableExists('auth_roles') || ! $this->tableExists('auth_role_permissions')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $nurse = $this->db->table('auth_roles')->where('alias', 'nurse')->get(1)->getRowArray();
        if ($nurse === null) {
            $this->db->table('auth_roles')->insert([
                'alias' => 'nurse',
                'title' => 'Nurse',
                'description' => 'In-patient nursing records, bedside care, bed transfers, and nursing charges.',
                'is_active' => 1,
                'is_builtin' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleGrants = [
            'superadmin' => self::NEW_PERMISSIONS,
            'admin' => self::NEW_PERMISSIONS,
            'developer' => self::NEW_PERMISSIONS,
            'nurse' => [
                'ipd_nursing.view',
                'ipd_nursing.record.manage',
                'ipd_nursing.bed.transfer',
                'ipd_nursing.charge.manage',
                'ipd_discharge.view',
            ],
            'doctor' => [
                'ipd_nursing.view',
                'ipd_discharge.view',
                'ipd_discharge.manage',
            ],
            'billing_cashier' => [
                'ipd_discharge.view',
            ],
        ];

        foreach ($roleGrants as $alias => $permissions) {
            $role = $this->db->table('auth_roles')->select('id')->where('alias', $alias)->get(1)->getRowArray();
            if ($role === null) {
                continue;
            }

            foreach ($permissions as $permission) {
                $this->insertRolePermission((int) $role['id'], $permission, $now);
            }
        }

        if ($this->tableExists('auth_permissions_users')) {
            $managerIds = array_column(
                $this->db->table('auth_permissions_users')->select('user_id')->where('permission', 'users.manage-admins')->get()->getResultArray(),
                'user_id'
            );
            foreach ($managerIds as $userId) {
                foreach (self::NEW_PERMISSIONS as $permission) {
                    $exists = $this->db->table('auth_permissions_users')
                        ->where('user_id', (int) $userId)
                        ->where('permission', $permission)
                        ->countAllResults();
                    if ($exists === 0) {
                        $this->db->table('auth_permissions_users')->insert([
                            'user_id' => (int) $userId,
                            'permission' => $permission,
                            'created_at' => $now,
                        ]);
                    }
                }
            }
        }

        (new RoleRegistry($this->db))->publish();
    }

    public function down()
    {
        if ($this->tableExists('auth_permissions_users')) {
            $this->db->table('auth_permissions_users')->whereIn('permission', self::NEW_PERMISSIONS)->delete();
        }
        if ($this->tableExists('auth_role_permissions')) {
            $this->db->table('auth_role_permissions')->whereIn('permission', self::NEW_PERMISSIONS)->delete();
        }
        if ($this->tableExists('auth_roles')) {
            $this->db->table('auth_roles')->where('alias', 'nurse')->where('is_builtin', 1)->delete();
            (new RoleRegistry($this->db))->publish();
        }
    }

    private function insertRolePermission(int $roleId, string $permission, string $createdAt): void
    {
        $exists = $this->db->table('auth_role_permissions')
            ->where('role_id', $roleId)
            ->where('permission', $permission)
            ->countAllResults();
        if ($exists === 0) {
            $this->db->table('auth_role_permissions')->insert([
                'role_id' => $roleId,
                'permission' => $permission,
                'created_at' => $createdAt,
            ]);
        }
    }

    private function tableExists(string $table): bool
    {
        return $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getRowArray() !== null;
    }
}
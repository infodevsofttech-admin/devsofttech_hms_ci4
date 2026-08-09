<?php

namespace App\Database\Migrations;

use App\Libraries\RoleRegistry;
use CodeIgniter\Database\Migration;

class AddDevelopedModulePermissions extends Migration
{
    private const NEW_PERMISSIONS = [
        'reports.billing_operations.view',
        'reports.document_issue.view',
        'finance.access',
        'finance.compliance.view',
        'finance.doctor_payout.manage',
        'finance.vendor.manage',
        'finance.po.manage',
        'finance.grn.manage',
        'finance.invoice.manage',
        'doctor_work.immunization.access',
        'doctor_work.immunization.schedule-manage',
        'doctor_work.immunization.record-manage',
        'abdm.abha.create',
        'billing.payment_request.view',
        'billing.payment_request.manage',
        'billing.refund.view',
        'billing.refund.manage',
        'billing.items.view',
        'billing.items.manage',
        'billing.packages.view',
        'billing.packages.manage',
    ];

    public function up()
    {
        if (! $this->tableExists('auth_roles') || ! $this->tableExists('auth_role_permissions')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $doctor = $this->db->table('auth_roles')->where('alias', 'doctor')->get(1)->getRowArray();
        if ($doctor === null) {
            $this->db->table('auth_roles')->insert([
                'alias' => 'doctor',
                'title' => 'Doctor',
                'description' => 'Clinical doctor access to OPD work, diagnosis reports, and immunization records.',
                'is_active' => 1,
                'is_builtin' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $doctor = $this->db->table('auth_roles')->where('alias', 'doctor')->get(1)->getRowArray();
        }

        $roleGrants = [
            'doctor' => [
                'opd.doctor-panel.access',
                'doctor_work.access',
                'doctor_work.immunization.access',
                'doctor_work.immunization.record-manage',
                'diagnosis.access',
                'diagnosis.report.view',
            ],
            'superadmin' => ['reports.billing_operations.view', 'reports.document_issue.view'],
            'admin' => ['reports.billing_operations.view', 'reports.document_issue.view'],
            'developer' => ['reports.billing_operations.view', 'reports.document_issue.view'],
            'billing_cashier' => [
                'abdm.abha.create',
                'billing.payment_request.view',
                'billing.payment_request.manage',
                'billing.refund.view',
                'billing.refund.manage',
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
                    $exists = $this->db->table('auth_permissions_users')->where('user_id', (int) $userId)->where('permission', $permission)->countAllResults();
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
            $this->db->table('auth_roles')->where('alias', 'doctor')->where('is_builtin', 1)->delete();
            (new RoleRegistry($this->db))->publish();
        }
    }

    private function insertRolePermission(int $roleId, string $permission, string $createdAt): void
    {
        $exists = $this->db->table('auth_role_permissions')->where('role_id', $roleId)->where('permission', $permission)->countAllResults();
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

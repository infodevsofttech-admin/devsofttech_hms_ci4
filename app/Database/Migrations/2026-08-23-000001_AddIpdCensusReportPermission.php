<?php

namespace App\Database\Migrations;

use App\Libraries\RoleRegistry;
use CodeIgniter\Database\Migration;

class AddIpdCensusReportPermission extends Migration
{
    private const PERMISSION = 'reports.ipd_census.view';

    public function up()
    {
        if (! $this->tableExists('auth_roles') || ! $this->tableExists('auth_role_permissions')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        foreach (['superadmin', 'admin', 'developer'] as $alias) {
            $role = $this->db->table('auth_roles')->select('id')->where('alias', $alias)->get(1)->getRowArray();
            if ($role === null) {
                continue;
            }

            $exists = $this->db->table('auth_role_permissions')
                ->where('role_id', (int) $role['id'])
                ->where('permission', self::PERMISSION)
                ->countAllResults();
            if ($exists === 0) {
                $this->db->table('auth_role_permissions')->insert([
                    'role_id' => (int) $role['id'],
                    'permission' => self::PERMISSION,
                    'created_at' => $now,
                ]);
            }
        }

        (new RoleRegistry($this->db))->publish();
    }

    public function down()
    {
        if ($this->tableExists('auth_role_permissions')) {
            $this->db->table('auth_role_permissions')->where('permission', self::PERMISSION)->delete();
        }

        if ($this->tableExists('auth_roles')) {
            (new RoleRegistry($this->db))->publish();
        }
    }

    private function tableExists(string $table): bool
    {
        return $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getRowArray() !== null;
    }
}

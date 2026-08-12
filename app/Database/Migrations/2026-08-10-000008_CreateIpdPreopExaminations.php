<?php

namespace App\Database\Migrations;

use App\Libraries\RoleRegistry;
use CodeIgniter\Database\Migration;

class CreateIpdPreopExaminations extends Migration
{
    private const PERMISSIONS = [
        'ipd_ot.view',
        'ipd_ot.examination.manage',
    ];

    public function up()
    {
        if (! $this->tableExists('ipd_preop_examinations')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'ipd_id' => ['type' => 'INT', 'unsigned' => true],
                'patient_id' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'department_id' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'department_name_snapshot' => ['type' => 'VARCHAR', 'constraint' => 150, 'default' => ''],
                'form_key' => ['type' => 'VARCHAR', 'constraint' => 100],
                'schema_version' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
                'episode_no' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
                'payload_json' => ['type' => 'LONGTEXT', 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
                'examined_by' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'examined_at' => ['type' => 'DATETIME', 'null' => true],
                'created_by' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'updated_by' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['ipd_id', 'form_key', 'episode_no'], 'uq_ipd_preop_episode');
            $this->forge->addKey(['department_id', 'status'], false, false, 'idx_ipd_preop_department_status');
            $this->forge->createTable('ipd_preop_examinations', true, ['ENGINE' => 'InnoDB']);
        }

        if (! $this->tableExists('ipd_preop_examination_updates')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'examination_id' => ['type' => 'INT', 'unsigned' => true],
                'revision_no' => ['type' => 'INT', 'unsigned' => true],
                'payload_json' => ['type' => 'LONGTEXT', 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
                'examined_by' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'examined_at' => ['type' => 'DATETIME', 'null' => true],
                'edit_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
                'updated_by' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'updated_at' => ['type' => 'DATETIME'],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['examination_id', 'revision_no'], 'uq_ipd_preop_revision');
            $this->forge->createTable('ipd_preop_examination_updates', true, ['ENGINE' => 'InnoDB']);
        }

        $this->grantPermissions();
    }

    public function down()
    {
        if ($this->tableExists('auth_permissions_users')) {
            $this->db->table('auth_permissions_users')->whereIn('permission', self::PERMISSIONS)->delete();
        }
        if ($this->tableExists('auth_role_permissions')) {
            $this->db->table('auth_role_permissions')->whereIn('permission', self::PERMISSIONS)->delete();
        }
        if ($this->tableExists('auth_roles')) {
            (new RoleRegistry($this->db))->publish();
        }

        $this->forge->dropTable('ipd_preop_examination_updates', true);
        $this->forge->dropTable('ipd_preop_examinations', true);
    }

    private function grantPermissions(): void
    {
        if (! $this->tableExists('auth_roles') || ! $this->tableExists('auth_role_permissions')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $roleGrants = [
            'superadmin' => self::PERMISSIONS,
            'admin' => self::PERMISSIONS,
            'developer' => self::PERMISSIONS,
            'doctor' => self::PERMISSIONS,
            'nurse' => ['ipd_ot.view'],
        ];

        foreach ($roleGrants as $alias => $permissions) {
            $role = $this->db->table('auth_roles')->select('id')->where('alias', $alias)->get(1)->getRowArray();
            if ($role === null) {
                continue;
            }
            foreach ($permissions as $permission) {
                $exists = $this->db->table('auth_role_permissions')
                    ->where('role_id', (int) $role['id'])
                    ->where('permission', $permission)
                    ->countAllResults();
                if ($exists === 0) {
                    $this->db->table('auth_role_permissions')->insert([
                        'role_id' => (int) $role['id'],
                        'permission' => $permission,
                        'created_at' => $now,
                    ]);
                }
            }
        }

        if ($this->tableExists('auth_permissions_users')) {
            $managerIds = array_column(
                $this->db->table('auth_permissions_users')->select('user_id')->where('permission', 'users.manage-admins')->get()->getResultArray(),
                'user_id'
            );
            foreach ($managerIds as $userId) {
                foreach (self::PERMISSIONS as $permission) {
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

    private function tableExists(string $table): bool
    {
        return $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getRowArray() !== null;
    }
}
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\AuthGroups;

class CreateAuthRoleMasterTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'alias' => ['type' => 'VARCHAR', 'constraint' => 64],
            'title' => ['type' => 'VARCHAR', 'constraint' => 120],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'is_builtin' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('alias', 'uk_auth_roles_alias');
        $this->forge->addKey(['is_active', 'title'], false, false, 'idx_auth_roles_active_title');
        $this->forge->createTable('auth_roles', true, ['ENGINE' => 'InnoDB']);

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'role_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'permission' => ['type' => 'VARCHAR', 'constraint' => 191],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['role_id', 'permission'], 'uk_auth_role_permission');
        $this->forge->addKey('permission', false, false, 'idx_auth_role_permissions_permission');
        $this->forge->addForeignKey('role_id', 'auth_roles', 'id', 'CASCADE', 'CASCADE', 'fk_auth_role_permissions_role');
        $this->forge->createTable('auth_role_permissions', true, ['ENGINE' => 'InnoDB']);

        $config = new AuthGroups();
        $now = date('Y-m-d H:i:s');
        foreach ($config->groups as $alias => $role) {
            $this->db->table('auth_roles')->insert([
                'alias' => $alias,
                'title' => $role['title'] ?? $alias,
                'description' => $role['description'] ?? null,
                'is_active' => 1,
                'is_builtin' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $roleId = (int) ($this->db->table('auth_roles')
                ->select('id')
                ->where('alias', $alias)
                ->get(1)
                ->getRowArray()['id'] ?? 0);
            foreach ($config->matrix[$alias] ?? [] as $permission) {
                $this->db->table('auth_role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission' => $permission,
                    'created_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('auth_role_permissions', true);
        $this->forge->dropTable('auth_roles', true);
    }
}

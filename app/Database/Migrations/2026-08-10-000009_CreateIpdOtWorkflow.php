<?php

namespace App\Database\Migrations;

use App\Libraries\RoleRegistry;
use CodeIgniter\Database\Migration;

class CreateIpdOtWorkflow extends Migration
{
    private const PERMISSIONS = [
        'ipd_ot.request.manage',
        'ipd_ot.schedule.manage',
        'ipd_ot.status.manage',
        'ipd_ot.postop.manage',
    ];

    public function up()
    {
        if (! $this->tableExists('ipd_ot_cases')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'request_no' => ['type' => 'VARCHAR', 'constraint' => 30],
                'ipd_id' => ['type' => 'INT', 'unsigned' => true],
                'patient_id' => ['type' => 'INT', 'unsigned' => true],
                'department_id' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'department_name_snapshot' => ['type' => 'VARCHAR', 'constraint' => 150, 'default' => ''],
                'procedure_name' => ['type' => 'VARCHAR', 'constraint' => 255],
                'procedure_side' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'not_applicable'],
                'priority' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'routine'],
                'requested_date' => ['type' => 'DATE'],
                'requested_time' => ['type' => 'TIME', 'null' => true],
                'requested_notes' => ['type' => 'TEXT', 'null' => true],
                'scheduled_start_at' => ['type' => 'DATETIME', 'null' => true],
                'surgeon_id' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'surgeon_name_snapshot' => ['type' => 'VARCHAR', 'constraint' => 180, 'default' => ''],
                'anesthetist_id' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'anesthetist_name_snapshot' => ['type' => 'VARCHAR', 'constraint' => 180, 'default' => ''],
                'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'requested'],
                'call_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'not_called'],
                'called_at' => ['type' => 'DATETIME', 'null' => true],
                'called_by' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'confirmed_at' => ['type' => 'DATETIME', 'null' => true],
                'confirmed_by' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'consent_verified' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'site_side_verified' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'allergy_verified' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'npo_verified' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'investigations_verified' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'anesthesia_clearance' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'blood_availability' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'not_required'],
                'who_sign_in' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'who_time_out' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'who_sign_out' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'actual_start_at' => ['type' => 'DATETIME', 'null' => true],
                'actual_end_at' => ['type' => 'DATETIME', 'null' => true],
                'lock_version' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'created_by' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'updated_by' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('request_no', 'uq_ipd_ot_request_no');
            $this->forge->addKey(['scheduled_start_at', 'department_id', 'status'], false, false, 'idx_ipd_ot_queue');
            $this->forge->addKey(['ipd_id', 'status'], false, false, 'idx_ipd_ot_admission');
            $this->forge->createTable('ipd_ot_cases', true, ['ENGINE' => 'InnoDB']);
        }

        if (! $this->tableExists('ipd_ot_case_events')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'case_id' => ['type' => 'INT', 'unsigned' => true],
                'event_type' => ['type' => 'VARCHAR', 'constraint' => 40],
                'from_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => ''],
                'to_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => ''],
                'old_scheduled_at' => ['type' => 'DATETIME', 'null' => true],
                'new_scheduled_at' => ['type' => 'DATETIME', 'null' => true],
                'reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'actor_id' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['case_id', 'created_at'], false, false, 'idx_ipd_ot_case_events');
            $this->forge->createTable('ipd_ot_case_events', true, ['ENGINE' => 'InnoDB']);
        }

        if (! $this->tableExists('ipd_ot_postop_assessments')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'case_id' => ['type' => 'INT', 'unsigned' => true],
                'supersedes_id' => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
                'observed_at' => ['type' => 'DATETIME'],
                'consciousness' => ['type' => 'VARCHAR', 'constraint' => 80, 'default' => ''],
                'airway_breathing' => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
                'bp' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => ''],
                'pulse' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => ''],
                'spo2' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => ''],
                'temperature' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => ''],
                'pain_score' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
                'bleeding_wound_drain' => ['type' => 'TEXT', 'null' => true],
                'nausea_vomiting' => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => ''],
                'complications' => ['type' => 'TEXT', 'null' => true],
                'interventions' => ['type' => 'TEXT', 'null' => true],
                'disposition' => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => ''],
                'handover_notes' => ['type' => 'TEXT', 'null' => true],
                'edit_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => ''],
                'recorded_by' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'signed_at' => ['type' => 'DATETIME'],
                'created_at' => ['type' => 'DATETIME'],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['case_id', 'observed_at'], false, false, 'idx_ipd_ot_postop_case');
            $this->forge->createTable('ipd_ot_postop_assessments', true, ['ENGINE' => 'InnoDB']);
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
        $this->forge->dropTable('ipd_ot_postop_assessments', true);
        $this->forge->dropTable('ipd_ot_case_events', true);
        $this->forge->dropTable('ipd_ot_cases', true);
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
            'nurse' => ['ipd_ot.request.manage', 'ipd_ot.status.manage', 'ipd_ot.postop.manage'],
        ];
        foreach ($roleGrants as $alias => $permissions) {
            $role = $this->db->table('auth_roles')->select('id')->where('alias', $alias)->get(1)->getRowArray();
            if ($role === null) {
                continue;
            }
            foreach ($permissions as $permission) {
                $exists = $this->db->table('auth_role_permissions')->where('role_id', (int) $role['id'])->where('permission', $permission)->countAllResults();
                if ($exists === 0) {
                    $this->db->table('auth_role_permissions')->insert(['role_id' => (int) $role['id'], 'permission' => $permission, 'created_at' => $now]);
                }
            }
        }
        if ($this->tableExists('auth_permissions_users')) {
            $managerIds = array_column($this->db->table('auth_permissions_users')->select('user_id')->where('permission', 'users.manage-admins')->get()->getResultArray(), 'user_id');
            foreach ($managerIds as $userId) {
                foreach (self::PERMISSIONS as $permission) {
                    $exists = $this->db->table('auth_permissions_users')->where('user_id', (int) $userId)->where('permission', $permission)->countAllResults();
                    if ($exists === 0) {
                        $this->db->table('auth_permissions_users')->insert(['user_id' => (int) $userId, 'permission' => $permission, 'created_at' => $now]);
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
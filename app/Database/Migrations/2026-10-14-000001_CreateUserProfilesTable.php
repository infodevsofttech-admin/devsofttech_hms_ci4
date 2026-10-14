<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserProfilesTable extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('user_profiles')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                ],
                'full_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                    'null'       => true,
                ],
                'phone_no' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                ],
                'doctor_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true,
                ],
                'nurse_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true,
                ],
                'department' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'designation' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'employee_code' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
                'avatar' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey('user_id', 'uk_user_profiles_user_id');
            $this->forge->addKey('doctor_id', false, false, 'idx_user_profiles_doctor_id');
            $this->forge->addKey('nurse_id', false, false, 'idx_user_profiles_nurse_id');
            $this->forge->addKey('phone_no', false, false, 'idx_user_profiles_phone_no');
            $this->forge->addKey('employee_code', false, false, 'idx_user_profiles_employee_code');
            $this->forge->createTable('user_profiles', true, ['ENGINE' => 'InnoDB']);
        }

        // Backfill existing users from auth_identities.extra
        $users = $this->db->table('users')->get()->getResultArray();
        $now = date('Y-m-d H:i:s');

        foreach ($users as $user) {
            $userId = (int) $user['id'];
            $existing = $this->db->table('user_profiles')->where('user_id', $userId)->get(1)->getRowArray();
            if ($existing !== null) {
                continue;
            }

            $identity = $this->db->table('auth_identities')
                ->where('user_id', $userId)
                ->where('type', 'email_password')
                ->get(1)
                ->getRowArray();

            $fullName = null;
            $phoneNo = null;
            $avatar = null;

            if (! empty($identity['extra'])) {
                $extra = $identity['extra'];
                if (is_string($extra)) {
                    $decoded = json_decode($extra, true);
                    if (is_array($decoded)) {
                        $extra = $decoded;
                    }
                }
                if (is_array($extra)) {
                    $fullName = ! empty($extra['full_name']) ? trim((string) $extra['full_name']) : null;
                    $phoneNo  = ! empty($extra['phone_no']) ? trim((string) $extra['phone_no']) : null;
                    $avatar   = ! empty($extra['profile_photo']) ? trim((string) $extra['profile_photo']) : null;
                }
            }

            $this->db->table('user_profiles')->insert([
                'user_id'    => $userId,
                'full_name'  => $fullName,
                'phone_no'   => $phoneNo,
                'avatar'     => $avatar,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('user_profiles', true);
    }
}

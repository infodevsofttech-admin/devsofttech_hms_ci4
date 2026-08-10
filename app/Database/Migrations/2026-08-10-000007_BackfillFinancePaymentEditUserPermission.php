<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BackfillFinancePaymentEditUserPermission extends Migration
{
    private const PERMISSION = 'finance.payment.edit';

    public function up()
    {
        if (! $this->tableExists('auth_permissions_users')) {
            return;
        }

        $eligiblePermissions = [
            'finance.cash.accounts.accept',
            'finance.cash.accounts.verify',
            'finance.bank.audit',
            'users.manage-admins',
        ];
        $userIds = array_unique(array_column(
            $this->db->table('auth_permissions_users')
                ->select('user_id')
                ->whereIn('permission', $eligiblePermissions)
                ->get()
                ->getResultArray(),
            'user_id'
        ));

        foreach ($userIds as $userId) {
            $exists = $this->db->table('auth_permissions_users')
                ->where('user_id', (int) $userId)
                ->where('permission', self::PERMISSION)
                ->countAllResults();
            if ($exists === 0) {
                $this->db->table('auth_permissions_users')->insert([
                    'user_id' => (int) $userId,
                    'permission' => self::PERMISSION,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->tableExists('auth_permissions_users')) {
            $this->db->table('auth_permissions_users')->where('permission', self::PERMISSION)->delete();
        }
    }

    private function tableExists(string $table): bool
    {
        return $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getRowArray() !== null;
    }
}
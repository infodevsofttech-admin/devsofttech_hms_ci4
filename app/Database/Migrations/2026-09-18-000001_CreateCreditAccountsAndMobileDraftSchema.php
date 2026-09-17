<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCreditAccountsAndMobileDraftSchema extends Migration
{
    public function up()
    {
        $forge = \Config\Database::forge();

        // 1. mst_credit_accounts
        if (!$this->db->tableExists('mst_credit_accounts')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `mst_credit_accounts` (
                `account_id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL DEFAULT 1,
                `account_type` ENUM('STAFF', 'DOCTOR', 'VIP_CUSTOMER', 'CORPORATE', 'OTHER') NOT NULL DEFAULT 'STAFF',
                `account_name` VARCHAR(150) NOT NULL,
                `phone` VARCHAR(20) DEFAULT NULL,
                `staff_id` VARCHAR(50) DEFAULT NULL,
                `department` VARCHAR(100) DEFAULT NULL,
                `credit_limit` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `current_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `remarks` TEXT DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY `idx_store_acc` (`store_id`, `account_type`),
                KEY `idx_phone` (`phone`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        // 2. mst_credit_ledger
        if (!$this->db->tableExists('mst_credit_ledger')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `mst_credit_ledger` (
                `ledger_id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL DEFAULT 1,
                `account_id` INT NOT NULL,
                `entry_type` ENUM('CREDIT_SALE', 'PAYMENT_RECEIVED', 'ADJUSTMENT') NOT NULL DEFAULT 'CREDIT_SALE',
                `sale_id` INT DEFAULT NULL,
                `invoice_no` VARCHAR(50) DEFAULT NULL,
                `debit_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `credit_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `balance_after` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `payment_mode` VARCHAR(50) DEFAULT 'Cash',
                `payment_ref` VARCHAR(100) DEFAULT NULL,
                `remarks` TEXT DEFAULT NULL,
                `recorded_by_name` VARCHAR(100) DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_acc_ledger` (`account_id`, `created_at`),
                KEY `idx_store` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        // 3. mst_draft_bills
        if (!$this->db->tableExists('mst_draft_bills')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `mst_draft_bills` (
                `draft_id` INT AUTO_INCREMENT PRIMARY KEY,
                `store_id` INT NOT NULL DEFAULT 1,
                `draft_code` VARCHAR(50) NOT NULL,
                `patient_name` VARCHAR(100) DEFAULT 'Walk-in Customer',
                `uhid` VARCHAR(50) DEFAULT NULL,
                `patient_mobile` VARCHAR(20) DEFAULT NULL,
                `bed_no` VARCHAR(50) DEFAULT NULL,
                `ward_name` VARCHAR(50) DEFAULT NULL,
                `doctor_name` VARCHAR(100) DEFAULT NULL,
                `total_items` INT NOT NULL DEFAULT 0,
                `estimated_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `items_json` LONGTEXT NOT NULL,
                `status` ENUM('DRAFT', 'CONVERTED_TO_SALE', 'CANCELLED') NOT NULL DEFAULT 'DRAFT',
                `converted_sale_id` INT DEFAULT NULL,
                `collected_by_name` VARCHAR(100) DEFAULT 'Pharmacist',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY `idx_store_status` (`store_id`, `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        // 4. Alter mst_purchases
        if ($this->db->tableExists('mst_purchases')) {
            $fields = $this->db->getFieldNames('mst_purchases');
            $addPurchCols = [];
            if (!in_array('invoice_photo', $fields, true)) {
                $addPurchCols['invoice_photo'] = [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => in_array('remarks', $fields, true) ? 'remarks' : null
                ];
            }
            if (!in_array('is_verified', $fields, true)) {
                $addPurchCols['is_verified'] = [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0
                ];
            }
            if (!in_array('verified_by_name', $fields, true)) {
                $addPurchCols['verified_by_name'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true
                ];
            }
            if (!in_array('verified_at', $fields, true)) {
                $addPurchCols['verified_at'] = [
                    'type' => 'DATETIME',
                    'null' => true
                ];
            }
            if (!empty($addPurchCols)) {
                $forge->addColumn('mst_purchases', $addPurchCols);
            }
        }

        // 5. Alter mst_sales
        if ($this->db->tableExists('mst_sales')) {
            $fields = $this->db->getFieldNames('mst_sales');
            if (!in_array('credit_account_id', $fields, true)) {
                $forge->addColumn('mst_sales', [
                    'credit_account_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'null'       => true,
                        'after'      => in_array('credit_amount', $fields, true) ? 'credit_amount' : null
                    ]
                ]);
            }
        }

        // 6. Alter mst_store_users
        if ($this->db->tableExists('mst_store_users')) {
            $fields = $this->db->getFieldNames('mst_store_users');
            if (!in_array('permissions', $fields, true)) {
                $forge->addColumn('mst_store_users', [
                    'permissions' => [
                        'type' => 'TEXT',
                        'null' => true,
                        'after' => in_array('user_role', $fields, true) ? 'user_role' : null
                    ]
                ]);
            }

            // Seed default permissions if null
            $allPerms = json_encode(['admin', 'sale', 'purchase', 'sale_edit_after_1h', 'purchase_edit_verified', 'bank_upi_audit']);
            $pharmaPerms = json_encode(['sale', 'purchase', 'sale_edit_after_1h']);
            $cashierPerms = json_encode(['sale']);
            $auditorPerms = json_encode(['purchase', 'bank_upi_audit']);

            $this->db->query("UPDATE `mst_store_users` SET `permissions` = '{$allPerms}' WHERE `user_role` = 'store_manager' AND (`permissions` IS NULL OR `permissions` = '')");
            $this->db->query("UPDATE `mst_store_users` SET `permissions` = '{$pharmaPerms}' WHERE `user_role` = 'pharmacist' AND (`permissions` IS NULL OR `permissions` = '')");
            $this->db->query("UPDATE `mst_store_users` SET `permissions` = '{$cashierPerms}' WHERE `user_role` = 'cashier' AND (`permissions` IS NULL OR `permissions` = '')");
            $this->db->query("UPDATE `mst_store_users` SET `permissions` = '{$auditorPerms}' WHERE `user_role` = 'auditor' AND (`permissions` IS NULL OR `permissions` = '')");
        }

        // 7. Seed sample credit accounts if table empty
        if ($this->db->tableExists('mst_credit_accounts')) {
            $count = $this->db->table('mst_credit_accounts')->countAllResults();
            if ($count === 0) {
                $this->db->table('mst_credit_accounts')->insertBatch([
                    [
                        'store_id'        => 1,
                        'account_type'    => 'STAFF',
                        'account_name'    => 'Dr. Rajesh Sharma',
                        'phone'           => '9811002233',
                        'staff_id'        => 'EMP-DOC-101',
                        'department'      => 'General Medicine',
                        'credit_limit'    => 10000.00,
                        'current_balance' => 0.00,
                        'remarks'         => 'Senior Consultant Staff Credit Account'
                    ],
                    [
                        'store_id'        => 1,
                        'account_type'    => 'STAFF',
                        'account_name'    => 'Sister Sunita Rawat',
                        'phone'           => '9822003344',
                        'staff_id'        => 'EMP-NUR-205',
                        'department'      => 'ICU Nursing',
                        'credit_limit'    => 5000.00,
                        'current_balance' => 0.00,
                        'remarks'         => 'Staff Nurse Credit Account'
                    ],
                    [
                        'store_id'        => 1,
                        'account_type'    => 'VIP_CUSTOMER',
                        'account_name'    => 'Amitabh Hospital Trust',
                        'phone'           => '9833004455',
                        'staff_id'        => 'CORP-001',
                        'department'      => 'Trust VIP',
                        'credit_limit'    => 50000.00,
                        'current_balance' => 0.00,
                        'remarks'         => 'Corporate VIP Credit Account'
                    ]
                ]);
            }
        }
    }

    public function down()
    {
        $forge = \Config\Database::forge();

        if ($this->db->tableExists('mst_credit_ledger')) {
            $forge->dropTable('mst_credit_ledger', true);
        }
        if ($this->db->tableExists('mst_credit_accounts')) {
            $forge->dropTable('mst_credit_accounts', true);
        }
        if ($this->db->tableExists('mst_draft_bills')) {
            $forge->dropTable('mst_draft_bills', true);
        }

        if ($this->db->tableExists('mst_purchases')) {
            $fields = $this->db->getFieldNames('mst_purchases');
            foreach (['invoice_photo', 'is_verified', 'verified_by_name', 'verified_at'] as $c) {
                if (in_array($c, $fields, true)) {
                    $forge->dropColumn('mst_purchases', $c);
                }
            }
        }

        if ($this->db->tableExists('mst_sales')) {
            if (in_array('credit_account_id', $this->db->getFieldNames('mst_sales'), true)) {
                $forge->dropColumn('mst_sales', 'credit_account_id');
            }
        }

        if ($this->db->tableExists('mst_store_users')) {
            if (in_array('permissions', $this->db->getFieldNames('mst_store_users'), true)) {
                $forge->dropColumn('mst_store_users', 'permissions');
            }
        }
    }
}

<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($db->connect_error) {
    die("DB connection failed: " . $db->connect_error . "\n");
}

echo "=== Enhancing Medical Store Schema for Multi-Link & Device Security ===\n";

// 1. Add store_slug, security_key, current_otp, otp_expiry to mst_stores if not exist
$columns = [
    'store_slug' => "ALTER TABLE `mst_stores` ADD COLUMN `store_slug` VARCHAR(80) DEFAULT NULL AFTER `store_code`",
    'security_key' => "ALTER TABLE `mst_stores` ADD COLUMN `security_key` VARCHAR(100) DEFAULT NULL AFTER `terms_conditions`",
    'current_otp' => "ALTER TABLE `mst_stores` ADD COLUMN `current_otp` VARCHAR(10) DEFAULT NULL AFTER `security_key`",
    'otp_expiry' => "ALTER TABLE `mst_stores` ADD COLUMN `otp_expiry` DATETIME DEFAULT NULL AFTER `current_otp`"
];

foreach ($columns as $col => $sql) {
    $check = $db->query("SHOW COLUMNS FROM `mst_stores` LIKE '$col'");
    if ($check->num_rows == 0) {
        if ($db->query($sql)) {
            echo "   ✓ Added column `$col` to `mst_stores`.\n";
        } else {
            echo "   ! Error adding `$col`: " . $db->error . "\n";
        }
    } else {
        echo "   ✓ Column `$col` already exists in `mst_stores`.\n";
    }
}

// Ensure unique index on store_slug
$indexCheck = $db->query("SHOW INDEX FROM `mst_stores` WHERE Key_name = 'idx_store_slug'");
if ($indexCheck->num_rows == 0) {
    $db->query("ALTER TABLE `mst_stores` ADD UNIQUE INDEX `idx_store_slug` (`store_slug`)");
}

// 2. Create mst_store_devices (Machine Authorization / Terminal Verification)
$createDevicesTable = "CREATE TABLE IF NOT EXISTS `mst_store_devices` (
    `device_id` INT AUTO_INCREMENT PRIMARY KEY,
    `store_id` INT NOT NULL,
    `machine_name` VARCHAR(150) DEFAULT 'Pharmacy Counter PC',
    `device_token` VARCHAR(128) NOT NULL UNIQUE,
    `device_fingerprint` VARCHAR(128) DEFAULT NULL,
    `ip_address` VARCHAR(50) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `verified_by_method` VARCHAR(30) DEFAULT 'SECURITY_KEY' COMMENT 'SECURITY_KEY, OTP, ADMIN_MANUAL',
    `status` VARCHAR(20) DEFAULT 'authorized' COMMENT 'authorized, revoked',
    `authorized_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `last_active_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_store` (`store_id`),
    INDEX `idx_token` (`device_token`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($db->query($createDevicesTable)) {
    echo "   ✓ `mst_store_devices` table verified/created.\n";
} else {
    echo "   ! Error creating `mst_store_devices`: " . $db->error . "\n";
}

// 3. Update existing stores with slug and default security keys
$stores = $db->query("SELECT store_id, store_code, store_slug, security_key FROM `mst_stores`");
while ($s = $stores->fetch_assoc()) {
    $slug = $s['store_slug'];
    $secKey = $s['security_key'];
    $updates = [];

    if (empty($slug)) {
        if ($s['store_id'] == 1) $slug = 'storeA';
        elseif ($s['store_id'] == 2) $slug = 'storeB';
        else $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $s['store_code']));
        $updates[] = "`store_slug` = '$slug'";
    }

    if (empty($secKey)) {
        $secKey = 'HMS-' . strtoupper(bin2hex(random_bytes(3))) . '-' . rand(100, 999);
        $updates[] = "`security_key` = '$secKey'";
    }

    if (!empty($updates)) {
        $db->query("UPDATE `mst_stores` SET " . implode(', ', $updates) . " WHERE `store_id` = {$s['store_id']}");
        echo "   ✓ Initialized store #{$s['store_id']} with slug: '$slug' | Security Key: '$secKey'\n";
    }
}

$db->close();
echo "=== Schema Enhancement Completed Successfully ===\n";

<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

class HmsInstall extends BaseCommand
{
    protected $group       = 'HMS';
    protected $name        = 'hms:install';
    protected $description = 'Initialize and setup HMS: schema, master tables, templates, admin user & hospital profile.';
    protected $usage       = 'hms:install [options]';

    protected $arguments = [];
    protected $options = [
        '--hospital-name'  => 'Name of the hospital to configure in hospital_setting (default: "Johri Hospital")',
        '--admin-user'     => 'Username for superadmin account (default: "admin")',
        '--admin-email'    => 'Email for superadmin account (default: "admin@example.com")',
        '--admin-password' => 'Password for superadmin account (default: "Admin@12345")',
        '--force'          => 'Force import even if tables already exist in the database',
        '--skip-db-import' => 'Skip importing schema/masters and only configure admin user and hospital name',
    ];

    public function run(array $params): void
    {
        CLI::write("==================================================", 'cyan');
        CLI::write("           HMS Installation & Setup Tool          ", 'cyan');
        CLI::write("==================================================", 'cyan');

        $hospitalName  = CLI::getOption('hospital-name') ?? 'Johri Hospital';
        $adminUser     = CLI::getOption('admin-user') ?? 'admin';
        $adminEmail    = CLI::getOption('admin-email') ?? 'admin@example.com';
        $adminPassword = CLI::getOption('admin-password') ?? 'Admin@12345';
        $force         = CLI::getOption('force') !== null;
        $skipDbImport  = CLI::getOption('skip-db-import') !== null;

        // 1. Ensure writable directories
        $this->ensureWritableDirectories();

        // 2. Connect to database
        try {
            $db = \Config\Database::connect();
            $dbName = $db->getDatabase();
            CLI::write("Connected to database: {$dbName}", 'green');
        } catch (\Throwable $e) {
            CLI::error("Database connection failed: " . $e->getMessage());
            return;
        }

        // 3. Check table count
        $tables = $db->listTables();
        $tableCount = count($tables);
        CLI::write("Current table count in '{$dbName}': {$tableCount}", 'yellow');

        // 4. Import Starter Schema & Masters if needed
        if (!$skipDbImport && ($tableCount === 0 || $force)) {
            CLI::write("Starting database initialization...", 'yellow');
            $importOk = $this->importStarterSql($db);
            if (!$importOk) {
                CLI::error("Failed to import starter database SQL. Aborting.");
                return;
            }
            $tables = $db->listTables();
            CLI::write("Database successfully populated with " . count($tables) . " tables.", 'green');
        } elseif ($tableCount > 0 && !$force) {
            CLI::write("Database already contains {$tableCount} tables. Skipping SQL import (use --force to overwrite).", 'yellow');
        }

        // 5. Ensure Admin User
        CLI::write("Configuring Admin User ({$adminUser})...", 'yellow');
        $this->configureAdminUser($db, $adminUser, $adminEmail, $adminPassword, $hospitalName);

        // 6. Configure Hospital Profile
        CLI::write("Setting Hospital Name: '{$hospitalName}'...", 'yellow');
        $this->configureHospitalSetting($db, 'H_Name', $hospitalName);

        // 7. Success Banner
        CLI::write("\n==================================================", 'green');
        CLI::write("         HMS Installation Completed!              ", 'green');
        CLI::write("==================================================", 'green');
        CLI::write("  Hospital Name : {$hospitalName}");
        CLI::write("  Database      : {$dbName}");
        CLI::write("  Admin User    : {$adminUser}");
        CLI::write("  Admin Email   : {$adminEmail}");
        CLI::write("  Admin Password: {$adminPassword}");
        CLI::write("--------------------------------------------------");
        CLI::write("You can now login at: " . site_url('login'), 'cyan');
        CLI::write("==================================================\n", 'green');
    }

    private function ensureWritableDirectories(): void
    {
        $dirs = [
            WRITEPATH . 'cache',
            WRITEPATH . 'logs',
            WRITEPATH . 'session',
            WRITEPATH . 'uploads',
            WRITEPATH . 'debugbar',
            FCPATH . 'assets/images',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            if (is_dir($dir) && !is_writable($dir)) {
                @chmod($dir, 0775);
            }
        }
        CLI::write("Writable directories checked and ready.", 'green');
    }

    private function importStarterSql($db): bool
    {
        $sqlPath = ROOTPATH . 'database/hms_starter_schema_and_masters.sql';
        $gzPath  = ROOTPATH . 'database/hms_starter_schema_and_masters.sql.gz';

        if (!is_file($sqlPath) && is_file($gzPath)) {
            CLI::write("Decompressing {$gzPath}...", 'yellow');
            $sqlPath = $this->decompressGz($gzPath);
        }

        if (!is_file($sqlPath)) {
            CLI::error("Starter SQL file not found at: {$sqlPath}");
            return false;
        }

        $host = (string) $db->hostname;
        $port = (string) ($db->port ?? 3306);
        $user = (string) $db->username;
        $pass = (string) $db->password;
        $name = (string) $db->database;

        // Try using mysql command-line tool first (fastest and zero-memory)
        $mysqlCmd = "mysql -h " . escapeshellarg($host) . " -P " . escapeshellarg($port) . " -u " . escapeshellarg($user);
        if ($pass !== '') {
            $mysqlCmd .= " -p" . escapeshellarg($pass);
        }
        $mysqlCmd .= " " . escapeshellarg($name) . " < " . escapeshellarg($sqlPath) . " 2>&1";

        exec($mysqlCmd, $out, $ret);
        if ($ret === 0) {
            CLI::write("SQL imported successfully via mysql command line.", 'green');
            return true;
        }

        // Fallback: Read in blocks and execute using mysqli
        CLI::write("CLI import exited with code {$ret}. Falling back to PHP mysqli execution...", 'yellow');
        return $this->importSqlViaPhp($db, $sqlPath);
    }

    private function decompressGz(string $gzPath): string
    {
        $outPath = sys_get_temp_dir() . '/hms_starter_schema_and_masters.sql';
        $gz = gzopen($gzPath, 'rb');
        $out = fopen($outPath, 'wb');
        while (!gzeof($gz)) {
            fwrite($out, gzread($gz, 1024 * 512));
        }
        gzclose($gz);
        fclose($out);
        return $outPath;
    }

    private function importSqlViaPhp($db, string $sqlPath): bool
    {
        $fp = fopen($sqlPath, 'r');
        if (!$fp) {
            CLI::error("Unable to open SQL file: {$sqlPath}");
            return false;
        }

        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->query('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"');

        $buffer = '';
        $count = 0;

        while (!feof($fp)) {
            $line = fgets($fp);
            if ($line === false) {
                break;
            }
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
                continue;
            }

            $buffer .= $line;
            if (str_ends_with($trimmed, ';')) {
                try {
                    $db->query($buffer);
                    $count++;
                } catch (\Throwable $e) {
                    // Ignore duplicate key or exists errors
                }
                $buffer = '';
            }
        }
        fclose($fp);

        $db->query('SET FOREIGN_KEY_CHECKS = 1');
        CLI::write("PHP import completed ({$count} statements executed).", 'green');
        return true;
    }

    private function configureAdminUser($db, string $username, string $email, string $password, string $hospitalName = ''): void
    {
        $userId = null;
        try {
            $userModel = model(UserModel::class);
            $user = $userModel->where('username', $username)->first();

            if ($user === null) {
                $user = new User([
                    'username' => $username,
                    'email'    => $email,
                    'password' => $password,
                    'active'   => 1,
                ]);
                $userModel->save($user);
                $userId = $userModel->getInsertID();
                $user = $userModel->findById($userId);
                CLI::write("Created new admin user: {$username} (ID: {$userId})", 'green');
            } else {
                $userId = (int) $user->id;
                $user->email = $email;
                $user->setPassword($password);
                $user->active = 1;
                $userModel->save($user);
                CLI::write("Updated existing admin user: {$username}", 'green');
            }

            if ($user && method_exists($user, 'addGroup')) {
                $user->addGroup('admin');
                $user->addGroup('superadmin');
            }
        } catch (\Throwable $e) {
            // Direct database fallback if Shield models fail
            CLI::write("Shield model notice: " . $e->getMessage() . ". Using direct auth table upsert.", 'yellow');
            $userId = $this->directAdminUserUpsert($db, $username, $email, $password);
        }

        // Set Admin Profile Name based on Hospital Name
        if ($userId !== null && $userId > 0) {
            $this->applyAdminProfileName($db, $userId, $hospitalName);
        }
    }

    private function directAdminUserUpsert($db, string $username, string $email, string $password): int
    {
        $now = date('Y-m-d H:i:s');
        $userRow = $db->table('users')->where('username', $username)->get()->getRowArray();
        if ($userRow) {
            $userId = (int) $userRow['id'];
            $db->table('users')->where('id', $userId)->update([
                'active' => 1,
                'updated_at' => $now,
            ]);
        } else {
            $db->table('users')->insert([
                'username' => $username,
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $userId = (int) $db->insertID();
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $identity = $db->table('auth_identities')
            ->where('user_id', $userId)
            ->where('type', 'email_password')
            ->get()->getRowArray();

        if ($identity) {
            $db->table('auth_identities')->where('id', $identity['id'])->update([
                'secret' => $email,
                'secret2' => $hash,
                'updated_at' => $now,
            ]);
        } else {
            $db->table('auth_identities')->insert([
                'user_id' => $userId,
                'type' => 'email_password',
                'secret' => $email,
                'secret2' => $hash,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Add to admin group
        $hasGroup = $db->table('auth_groups_users')
            ->where('user_id', $userId)
            ->where('group', 'admin')
            ->countAllResults();

        if ($hasGroup === 0) {
            $db->table('auth_groups_users')->insert([
                'user_id' => $userId,
                'group' => 'admin',
                'created_at' => $now,
            ]);
        }
        CLI::write("Admin user configured in auth tables successfully.", 'green');
        return $userId;
    }

    private function applyAdminProfileName($db, int $userId, string $hospitalName): void
    {
        $adminFullName = trim($hospitalName) !== '' ? trim($hospitalName) . ' Admin' : 'Hospital Administrator';
        $now = date('Y-m-d H:i:s');

        // 1. Update auth_identities.extra
        $extraPayload = json_encode([
            'full_name' => $adminFullName,
            'phone_no'  => '',
        ]);
        $db->table('auth_identities')
            ->where('user_id', $userId)
            ->where('type', 'email_password')
            ->update(['extra' => $extraPayload]);

        // 2. Update user_profiles if table exists
        if ($db->tableExists('user_profiles')) {
            $existing = $db->table('user_profiles')->where('user_id', $userId)->get(1)->getRowArray();
            if ($existing) {
                $db->table('user_profiles')->where('user_id', $userId)->update([
                    'full_name'  => $adminFullName,
                    'phone_no'   => '',
                    'updated_at' => $now,
                ]);
            } else {
                $db->table('user_profiles')->insert([
                    'user_id'    => $userId,
                    'full_name'  => $adminFullName,
                    'phone_no'   => '',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
        CLI::write("Admin display name set to: '{$adminFullName}'", 'green');
    }

    private function configureHospitalSetting($db, string $key, string $value): void
    {
        $existing = $db->table('hospital_setting')->where('s_name', $key)->countAllResults();
        if ($existing > 0) {
            $db->table('hospital_setting')->where('s_name', $key)->update(['s_value' => $value]);
        } else {
            $db->table('hospital_setting')->insert(['s_name' => $key, 's_value' => $value]);
        }
    }
}

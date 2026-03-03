<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

class Setup extends BaseController
{
    /** @var array<string, mixed>|null */
    private ?array $lastSyncResult = null;

    private function lockFilePath(): string
    {
        return WRITEPATH . 'setup_db_complete.lock';
    }

    private function masterSchemaFilePath(): string
    {
        $fromEnv = trim((string) env('setup.sync.master.schema_file', ''));
        if ($fromEnv !== '') {
            if (str_starts_with($fromEnv, '/') || preg_match('/^[A-Za-z]:[\\\/]/', $fromEnv) === 1) {
                return $fromEnv;
            }

            return ROOTPATH . ltrim(str_replace(['\\'], '/', $fromEnv), '/');
        }

        return APPPATH . 'Database/master_schema.json';
    }

    private function isSetupLocked(): bool
    {
        return is_file($this->lockFilePath());
    }

    private function ensureSetupAccess()
    {
        if ($this->isSetupLocked()) {
            return $this->response->setStatusCode(404)->setBody('Setup is already completed and locked.');
        }

        $keyFromEnv = trim((string) env('setup.dbToolsKey', ''));
        if ($keyFromEnv !== '') {
            $keyFromRequest = trim((string) ($this->request->getGet('key') ?? $this->request->getPost('key') ?? ''));
            if ($keyFromRequest !== '' && hash_equals($keyFromEnv, $keyFromRequest)) {
                return null;
            }
        }

        if (!function_exists('auth')) {
            return null;
        }

        $user = auth()->user();
        if (!$user) {
            return $this->response->setStatusCode(403)->setBody('Please login to access setup tools.');
        }

        $allowed = false;
        if (method_exists($user, 'can') && $user->can('billing.opd.edit')) {
            $allowed = true;
        }
        if (!$allowed && method_exists($user, 'inGroup') && $user->inGroup('admin', 'superadmin', 'developer')) {
            $allowed = true;
        }

        if (!$allowed) {
            return $this->response->setStatusCode(403)->setBody('Access denied.');
        }

        return null;
    }

    public function dbTools()
    {
        if ($deny = $this->ensureSetupAccess()) {
            return $deny;
        }

        try {
            return view('setup/db_setup', $this->buildDbToolsViewData());
        } catch (\Throwable $e) {
            log_message('error', 'Setup dbTools load failed: {message}', ['message' => $e->getMessage()]);

            return view('setup/db_setup', [
                'tables' => [],
                'msg' => 'Setup tools could not load completely: ' . $e->getMessage(),
                'lock_file' => $this->lockFilePath(),
                'setup_key' => trim((string) env('setup.dbToolsKey', '')),
                'sync_result' => null,
                'sync_master_database' => (string) env('setup.sync.master.database', ''),
                'sync_client_database' => (string) env('setup.sync.client.database', ''),
                'master_schema_file' => $this->masterSchemaFilePath(),
                'master_schema_exists' => is_file($this->masterSchemaFilePath()),
                'diagnostics' => $this->collectSetupDiagnostics($e->getMessage()),
            ]);
        }
    }

    public function schemaSync()
    {
        if ($deny = $this->ensureSetupAccess()) {
            return $deny;
        }

        @ini_set('memory_limit', (string) env('setup.sync.memory_limit', '1024M'));
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        @ignore_user_abort(true);

        $action = strtolower(trim((string) $this->request->getPost('sync_action')));
        if (!in_array($action, ['analyze', 'apply'], true)) {
            $action = 'analyze';
        }

        $this->lastSyncResult = $this->executeSchemaSyncAction($action);

        return view('setup/db_setup', $this->buildDbToolsViewData());
    }

    public function schemaSyncStep()
    {
        if ($deny = $this->ensureSetupAccess()) {
            return $deny;
        }

        @ini_set('memory_limit', (string) env('setup.sync.memory_limit', '1024M'));
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        @ignore_user_abort(true);

        $result = $this->executeSchemaSyncAction('apply');
        $this->lastSyncResult = $result;

        $sqlCount = is_array($result['sql'] ?? null) ? count($result['sql']) : 0;
        $applyErrors = is_array($result['apply_errors'] ?? null) ? count($result['apply_errors']) : 0;
        $applied = (int) ($result['applied'] ?? 0);
        $truncated = !empty($result['truncated']) || !empty($result['apply_truncated']);

        $shouldContinue = $applyErrors === 0
            && ($truncated || ($sqlCount > 0 && $applied > 0));

        return $this->response->setJSON([
            'ok' => empty($result['errors'] ?? []),
            'result' => $result,
            'sql_count' => $sqlCount,
            'applied' => $applied,
            'apply_error_count' => $applyErrors,
            'should_continue' => $shouldContinue,
            'message' => $shouldContinue
                ? 'Applied chunk, continuing...'
                : 'Schema sync auto-apply reached a stop condition.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function executeSchemaSyncAction(string $action): array
    {
        try {
            $maxRuntimeSeconds = (int) env('setup.sync.max_runtime_seconds', 40);
            if ($maxRuntimeSeconds < 5) {
                $maxRuntimeSeconds = 5;
            }

            $startedAt = microtime(true);

            $syncResult = $this->buildSchemaSyncPlan($startedAt, $maxRuntimeSeconds);
            if ($action === 'apply' && empty($syncResult['errors']) && !empty($syncResult['sql'])) {
                $maxApplyStatements = (int) env('setup.sync.max_apply_statements_per_run', 75);
                if ($maxApplyStatements <= 0) {
                    $maxApplyStatements = 75;
                }

                $applyResult = $this->applySchemaSyncSql($syncResult['sql'], $startedAt, $maxRuntimeSeconds, $maxApplyStatements);
                $syncResult['applied'] = $applyResult['applied'];
                $syncResult['apply_errors'] = $applyResult['errors'];
                $syncResult['apply_truncated'] = $applyResult['truncated'];
            }

            return $syncResult;
        } catch (\Throwable $e) {
            log_message('error', 'Setup schemaSync failed: {message}', ['message' => $e->getMessage()]);
            return [
                'errors' => ['Schema sync failed: ' . $e->getMessage()],
                'sql' => [],
                'summary' => [],
                'source' => 'runtime-exception',
            ];
        }
    }

    public function exportMasterSchema()
    {
        if ($deny = $this->ensureSetupAccess()) {
            return $deny;
        }

        $schema = $this->readSchemaFromConnection($this->db);
        $file = $this->masterSchemaFilePath();
        $dir = dirname($file);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            session()->setFlashdata('setup_msg', 'Unable to create schema file directory.');
            return redirect()->to($this->dbToolsUrlWithKey());
        }

        $payload = [
            'generated_at' => date('Y-m-d H:i:s'),
            'source' => 'local-current-db',
            'tables' => $schema,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (!is_string($json) || @file_put_contents($file, $json) === false) {
            session()->setFlashdata('setup_msg', 'Unable to write master schema file.');
            return redirect()->to($this->dbToolsUrlWithKey());
        }

        session()->setFlashdata('setup_msg', 'Master schema exported: ' . $file);
        return redirect()->to($this->dbToolsUrlWithKey());
    }

    public function generateMigrations()
    {
        if ($deny = $this->ensureSetupAccess()) {
            return $deny;
        }

        $selected = $this->request->getPost('tables');
        if (!is_array($selected)) {
            $selected = [];
        }

        $selected = array_values(array_unique(array_map(static function ($value): string {
            $name = strtolower(trim((string) $value));
            return preg_replace('/[^a-z0-9_]/', '', $name) ?? '';
        }, $selected)));
        $selected = array_values(array_filter($selected));

        if (empty($selected)) {
            session()->setFlashdata('setup_msg', 'Please select at least one table.');
            return redirect()->to($this->dbToolsUrlWithKey());
        }

        $migrationDir = APPPATH . 'Database/Migrations';
        if (!is_dir($migrationDir) && !mkdir($migrationDir, 0777, true) && !is_dir($migrationDir)) {
            session()->setFlashdata('setup_msg', 'Unable to create migration directory.');
            return redirect()->to($this->dbToolsUrlWithKey());
        }

        $createdFiles = [];
        $ts = date('Y-m-d-His');
        $counter = 0;

        foreach ($selected as $tableName) {
            $showQuery = $this->db->query('SHOW CREATE TABLE `' . $tableName . '`');
            $showRow = $showQuery->getRowArray();
            if (empty($showRow)) {
                continue;
            }

            $createSql = '';
            foreach ($showRow as $key => $value) {
                if (is_string($key) && stripos($key, 'Create Table') !== false) {
                    $createSql = (string) $value;
                    break;
                }
            }
            if ($createSql === '') {
                $vals = array_values($showRow);
                $createSql = (string) ($vals[1] ?? '');
            }
            if ($createSql === '') {
                continue;
            }

            $createSql = $this->ensureCreateTableIfNotExists((string) $createSql);
            if (!str_ends_with(trim($createSql), ';')) {
                $createSql .= ';';
            }

            $className = 'CreateExistingTable' . $this->studly($tableName) . date('His') . str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
            $fileStamp = $ts . str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
            $fileName = $fileStamp . '_' . $className . '.php';
            $counter++;

            $php = "<?php\n\nnamespace App\\Database\\Migrations;\n\nuse CodeIgniter\\Database\\Migration;\n\nclass {$className} extends Migration\n{\n    public function up()\n    {\n        \$this->db->query(<<<'SQL'\n{$createSql}\nSQL\n        );\n    }\n\n    public function down()\n    {\n        \$this->forge->dropTable('{$tableName}', true);\n    }\n}\n";

            $path = $migrationDir . DIRECTORY_SEPARATOR . $fileName;
            if (@file_put_contents($path, $php) !== false) {
                $createdFiles[] = $fileName;
            }
        }

        if (empty($createdFiles)) {
            session()->setFlashdata('setup_msg', 'No migration files generated.');
        } else {
            session()->setFlashdata('setup_msg', 'Generated ' . count($createdFiles) . ' migration file(s).');
        }

        return redirect()->to($this->dbToolsUrlWithKey());
    }

    public function complete()
    {
        if ($deny = $this->ensureSetupAccess()) {
            return $deny;
        }

        $lockFile = $this->lockFilePath();
        $userName = 'unknown';
        if (function_exists('auth') && auth()->user()) {
            $user = auth()->user();
            $userName = (string) ($user->username ?? $user->email ?? 'unknown');
        }

        $content = "Setup completed at: " . date('Y-m-d H:i:s') . "\nBy: " . $userName . "\n";
        @file_put_contents($lockFile, $content);

        return $this->response->setBody('Setup locked successfully. You can now remove setup routes/page if required.');
    }

    public function ensureAdminLogin()
    {
        if ($deny = $this->ensureSetupAccess()) {
            return $deny;
        }

        $repair = $this->repairAuthSchemaInternal();
        if (!empty($repair['errors'])) {
            session()->setFlashdata('setup_msg', 'Auth schema repair failed before admin setup: ' . implode(' | ', (array) $repair['errors']));
            return redirect()->to($this->dbToolsUrlWithKey());
        }

        $username = trim((string) env('setup.admin.username', 'admin'));
        $email = trim((string) env('setup.admin.email', 'admin@example.com'));
        $password = (string) env('setup.admin.password', 'Admin@12345');
        $group = strtolower(trim((string) env('setup.admin.group', 'admin')));

        if ($username === '') {
            $username = 'admin';
        }
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $email = 'admin@example.com';
        }
        if (mb_strlen($password) < 8) {
            $password = 'Admin@12345';
        }

        try {
            $userModel = model(UserModel::class);
            $user = $userModel->where('username', $username)->first();

            if ($user === null) {
                $tables = config('Auth')->tables;
                $identitiesTable = (string) ($tables['identities'] ?? 'auth_identities');
                $idRow = $this->db->table($identitiesTable)
                    ->select('user_id')
                    ->where('type', 'email_password')
                    ->where('secret', $email)
                    ->get(1)
                    ->getRowArray();

                if (!empty($idRow['user_id'])) {
                    $user = $userModel->find((int) $idRow['user_id']);
                }
            }

            $action = 'updated';
            if ($user === null) {
                $newUser = new User();
                $newUser->username = $username;
                $newUser->email = $email;
                $newUser->password = $password;
                $newUser->active = 1;
                $userModel->save($newUser);

                $insertId = (int) $userModel->getInsertID();
                $user = $insertId > 0 ? $userModel->find($insertId) : null;
                $action = 'created';
            } else {
                $user->username = $username;
                $user->email = $email;
                $user->password = $password;
                $user->active = 1;
                $userModel->save($user);
                $user = $userModel->find((int) ($user->id ?? 0));
            }

            if ($user !== null && method_exists($user, 'addGroup')) {
                $groupConfig = setting('AuthGroups.groups');
                if (!is_array($groupConfig) || !isset($groupConfig[$group])) {
                    $group = 'admin';
                }
                $user->addGroup($group);
            }

            $repairMsg = '';
            if (!empty($repair['applied'])) {
                $repairMsg = ' Auth schema repaired: ' . implode(', ', (array) $repair['applied']) . '.';
            }

            session()->setFlashdata('setup_msg', 'Admin login ' . $action . ': username=' . $username . ', email=' . $email . ', group=' . $group . '.' . $repairMsg);
        } catch (\Throwable $e) {
            session()->setFlashdata('setup_msg', 'Admin login setup failed: ' . $e->getMessage());
        }

        return redirect()->to($this->dbToolsUrlWithKey());
    }

    public function repairAuthSchema()
    {
        if ($deny = $this->ensureSetupAccess()) {
            return $deny;
        }

        $result = $this->repairAuthSchemaInternal();
        $parts = [];
        $parts[] = 'Auth schema check finished.';
        $parts[] = 'Applied: ' . count((array) ($result['applied'] ?? [])) . '.';

        if (!empty($result['applied'])) {
            $parts[] = 'Changes: ' . implode(', ', (array) $result['applied']) . '.';
        }
        if (!empty($result['warnings'])) {
            $parts[] = 'Warnings: ' . implode(' | ', (array) $result['warnings']) . '.';
        }
        if (!empty($result['errors'])) {
            $parts[] = 'Errors: ' . implode(' | ', (array) $result['errors']) . '.';
        }

        session()->setFlashdata('setup_msg', implode(' ', $parts));
        return redirect()->to($this->dbToolsUrlWithKey());
    }

    public function prepareFilesystem()
    {
        if ($deny = $this->ensureSetupAccess()) {
            return $deny;
        }

        $result = $this->prepareWritablePaths();

        $parts = [];
        $parts[] = 'Filesystem prep done.';
        $parts[] = 'Created: ' . count($result['created']) . '.';
        $parts[] = 'Permission-updated: ' . count($result['permission_updated']) . '.';
        $parts[] = 'Writable: ' . count($result['writable']) . '/' . count($result['checked']) . '.';

        if (!empty($result['failed'])) {
            $parts[] = 'Failed: ' . implode(', ', $result['failed']) . '.';
        }

        if (!empty($result['commands'])) {
            $parts[] = 'Run on server shell if needed: ' . implode(' | ', $result['commands']);
        }

        session()->setFlashdata('setup_msg', implode(' ', $parts));

        return redirect()->to($this->dbToolsUrlWithKey());
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDbToolsViewData(): array
    {
        $allTables = [];
        $scripted = [];
        $dbError = null;

        try {
            $allTables = $this->db->listTables();
            $scripted = $this->detectScriptedTables();
        } catch (\Throwable $e) {
            $dbError = $e->getMessage();
            log_message('error', 'Setup buildDbToolsViewData DB failure: {message}', ['message' => $dbError]);
            session()->setFlashdata('setup_msg', 'Database connection/setup error: ' . $dbError);
        }

        $tableRows = [];
        foreach ($allTables as $tableName) {
            $isScripted = isset($scripted[strtolower((string) $tableName)]);
            $tableRows[] = [
                'name' => (string) $tableName,
                'scripted' => $isScripted,
            ];
        }

        usort($tableRows, static function (array $a, array $b): int {
            if ($a['scripted'] === $b['scripted']) {
                return strcmp($a['name'], $b['name']);
            }
            return $a['scripted'] ? 1 : -1;
        });

        return [
            'tables' => $tableRows,
            'msg' => session('setup_msg'),
            'lock_file' => $this->lockFilePath(),
            'setup_key' => trim((string) env('setup.dbToolsKey', '')),
            'sync_result' => $this->lastSyncResult,
            'sync_master_database' => (string) env('setup.sync.master.database', ''),
            'sync_client_database' => (string) env('setup.sync.client.database', ''),
            'master_schema_file' => $this->masterSchemaFilePath(),
            'master_schema_exists' => is_file($this->masterSchemaFilePath()),
            'diagnostics' => $this->collectSetupDiagnostics($dbError),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function collectSetupDiagnostics(?string $dbError = null): array
    {
        $errors = [];
        $warnings = [];
        $info = [];

        $env = (string) env('CI_ENVIRONMENT', 'production');
        $info[] = 'CI_ENVIRONMENT: ' . $env;

        $setupKey = trim((string) env('setup.dbToolsKey', ''));
        if ($setupKey === '') {
            $warnings[] = 'setup.dbToolsKey is empty. Setup endpoint can only be accessed by authenticated privileged users.';
        } else {
            $info[] = 'setup.dbToolsKey is configured.';
        }

        $dbHost = trim((string) env('database.default.hostname', ''));
        $dbName = trim((string) env('database.default.database', ''));
        $dbUser = trim((string) env('database.default.username', ''));

        if ($dbHost === '' || $dbName === '' || $dbUser === '') {
            $errors[] = 'Missing database.default.* values in .env (hostname/database/username).';
        } else {
            $info[] = 'DB target: ' . $dbHost . ' / ' . $dbName . ' / user=' . $dbUser;
        }

        if ($dbError !== null && $dbError !== '') {
            $errors[] = 'Database error: ' . $dbError;
        }

        $authSchema = $this->checkAuthSchemaHealth();
        if (!empty($authSchema['errors'])) {
            foreach ((array) $authSchema['errors'] as $authErr) {
                $errors[] = (string) $authErr;
            }
        }
        if (!empty($authSchema['warnings'])) {
            foreach ((array) $authSchema['warnings'] as $authWarn) {
                $warnings[] = (string) $authWarn;
            }
        }

        foreach ($this->requiredWritablePaths() as $path) {
            $relative = $this->relativeProjectPath($path);
            if (!is_dir($path)) {
                $warnings[] = $relative . ' does not exist.';
                continue;
            }

            if (!is_writable($path)) {
                $warnings[] = $relative . ' is not writable.';
            } else {
                $info[] = $relative . ' is writable.';
            }
        }

        $info[] = 'Web SAPI/PHP: ' . PHP_SAPI . ' / ' . PHP_VERSION;
        $info[] = 'If spark fails but web works, your CLI PHP version likely differs from web PHP version.';

        if ($this->isSetupLocked()) {
            $warnings[] = 'Setup lock file exists. Remove lock file if this is a fresh installation attempt.';
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'info' => $info,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSchemaSyncPlan(float $startedAt, int $maxRuntimeSeconds): array
    {
        $errors = [];
        $sql = [];

        $masterSchema = $this->loadMasterSchemaDefinition();
        if (!empty($masterSchema['errors'])) {
            return [
                'errors' => $masterSchema['errors'],
                'sql' => [],
                'summary' => [],
                'source' => $masterSchema['source'] ?? 'unknown',
            ];
        }

        try {
            $client = $this->resolveClientConnection();
        } catch (\Throwable $e) {
            return [
                'errors' => [$e->getMessage()],
                'sql' => [],
                'summary' => [],
                'source' => (string) ($masterSchema['source'] ?? 'unknown'),
            ];
        }

        $masterTables = $masterSchema['tables'] ?? [];
        $clientTables = array_flip($client->listTables());

        $maxTables = (int) env('setup.sync.max_tables_per_run', 150);
        if ($maxTables <= 0) {
            $maxTables = 150;
        }
        $processedTables = 0;
        $truncated = false;

        $createCount = 0;
        $alterCount = 0;

        foreach ($masterTables as $table => $masterTableDef) {
            if ($processedTables >= $maxTables) {
                $truncated = true;
                break;
            }

            if ((microtime(true) - $startedAt) >= $maxRuntimeSeconds) {
                $truncated = true;
                break;
            }

            $table = (string) $table;
            $processedTables++;
            if (!isset($clientTables[$table])) {
                $createSql = (string) ($masterTableDef['create_sql'] ?? '');
                if ($createSql !== '') {
                    $createSql = $this->ensureCreateTableIfNotExists((string) $createSql);
                    if (!str_ends_with($createSql, ';')) {
                        $createSql .= ';';
                    }
                }
                if ($createSql !== '') {
                    $sql[] = $createSql;
                    $createCount++;
                }
                continue;
            }

            $tableAlterSql = $this->buildAlterSqlForTableFromSchema((array) $masterTableDef, $client, $table);
            if (!empty($tableAlterSql)) {
                foreach ($tableAlterSql as $stmt) {
                    $sql[] = $stmt;
                }
                $alterCount += count($tableAlterSql);
            }

        }

        return [
            'errors' => $errors,
            'sql' => $sql,
            'summary' => [
                'master_tables' => count((array) $masterTables),
                'processed_tables' => $processedTables,
                'max_tables_per_run' => $maxTables,
                'create_tables' => $createCount,
                'alter_statements' => $alterCount,
            ],
            'source' => (string) ($masterSchema['source'] ?? 'master-schema-file'),
            'truncated' => $truncated,
        ];
    }

    /**
     * @param array<int, string> $sqlList
     * @return array{applied:int,errors:array<int,string>,truncated:bool}
     */
    private function applySchemaSyncSql(array $sqlList, float $startedAt, int $maxRuntimeSeconds, int $maxStatements): array
    {
        $client = $this->resolveClientConnection();
        $applied = 0;
        $errors = [];
        $truncated = false;
        $attempted = 0;

        foreach ($sqlList as $stmt) {
            if ($attempted >= $maxStatements) {
                $truncated = true;
                break;
            }

            if ((microtime(true) - $startedAt) >= $maxRuntimeSeconds) {
                $truncated = true;
                break;
            }

            $stmt = $this->normalizeSyncSql((string) $stmt);
            if ($stmt === '') {
                continue;
            }
            $attempted++;
            try {
                $client->query($stmt);
                $applied++;
            } catch (\Throwable $e) {
                $message = (string) $e->getMessage();

                if ($this->isSafeToSkipSyncStatement($message, $stmt)) {
                    continue;
                }

                if ($this->isCreateTableStatement($stmt) && stripos($message, 'Cannot add foreign key constraint') !== false) {
                    $retry = $this->stripForeignKeysFromCreateTable($stmt);
                    if ($retry !== $stmt && $retry !== '') {
                        try {
                            $client->query($retry);
                            $applied++;
                            continue;
                        } catch (\Throwable $retryError) {
                            $message = (string) $retryError->getMessage();
                        }
                    }
                }

                $errors[] = $message . ' | SQL: ' . mb_substr($stmt, 0, 180);
            }
        }

        return ['applied' => $applied, 'errors' => $errors, 'truncated' => $truncated];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadMasterSchemaDefinition(): array
    {
        if ($this->hasSyncRoleConfig('master')) {
            try {
                $masterDb = $this->connectSyncDb('master');
                return [
                    'errors' => [],
                    'tables' => $this->readSchemaFromConnection($masterDb),
                    'source' => 'master-db-connection',
                ];
            } catch (\Throwable $e) {
                return [
                    'errors' => ['Master DB connection failed: ' . $e->getMessage()],
                    'tables' => [],
                    'source' => 'master-db-connection',
                ];
            }
        }

        $file = $this->masterSchemaFilePath();
        if (!is_file($file)) {
            return [
                'errors' => ['Master schema file not found: ' . $file . '. Use "Export Master Schema File" first on master system.'],
                'tables' => [],
                'source' => 'master-schema-file',
            ];
        }

        $json = @file_get_contents($file);
        $decoded = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($decoded) || !is_array($decoded['tables'] ?? null)) {
            return [
                'errors' => ['Invalid master schema file format: ' . $file],
                'tables' => [],
                'source' => 'master-schema-file',
            ];
        }

        return [
            'errors' => [],
            'tables' => $decoded['tables'],
            'source' => 'master-schema-file',
        ];
    }

    private function hasSyncRoleConfig(string $role): bool
    {
        $role = strtolower(trim($role));
        $host = trim((string) env('setup.sync.' . $role . '.hostname', ''));
        $user = trim((string) env('setup.sync.' . $role . '.username', ''));
        $db = trim((string) env('setup.sync.' . $role . '.database', ''));
        return $host !== '' && $user !== '' && $db !== '';
    }

    private function resolveClientConnection()
    {
        if ($this->hasSyncRoleConfig('client')) {
            return $this->connectSyncDb('client');
        }

        return $this->db;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function readSchemaFromConnection($db): array
    {
        $tables = $db->listTables();
        $out = [];

        foreach ($tables as $tableName) {
            $table = (string) $tableName;
            $out[$table] = [
                'create_sql' => $this->getCreateTableSql($db, $table),
                'columns' => $this->readColumns($db, $table),
                'indexes' => $this->readIndexes($db, $table),
            ];
        }

        return $out;
    }

    /**
     * @return array<int, string>
     */
    private function buildAlterSqlForTableFromSchema(array $masterTableDef, $client, string $table): array
    {
        $sql = [];
        $columnAlterClauses = [];

        $masterCols = is_array($masterTableDef['columns'] ?? null) ? $masterTableDef['columns'] : [];
        $clientCols = $this->readColumns($client, $table);

        $prevCol = '';
        foreach ($masterCols as $colName => $masterCol) {
            if (!isset($clientCols[$colName])) {
                $position = $prevCol === '' ? ' FIRST' : (' AFTER `' . $prevCol . '`');
                $columnAlterClauses[] = 'ADD COLUMN ' . $this->columnDefinition($masterCol) . $position;
                $prevCol = $colName;
                continue;
            }

            if ($this->shouldModifyExistingColumn((array) $masterCol, (array) $clientCols[$colName])) {
                $columnAlterClauses[] = 'MODIFY COLUMN ' . $this->columnDefinition($masterCol);
            }
            $prevCol = $colName;
        }

        if (!empty($columnAlterClauses)) {
            $sql[] = 'ALTER TABLE `' . $table . '` ' . implode(', ', $columnAlterClauses) . ';';
        }

        $masterIdx = is_array($masterTableDef['indexes'] ?? null) ? $masterTableDef['indexes'] : [];
        $clientIdx = $this->readIndexes($client, $table);

        foreach ($masterIdx as $keyName => $masterDef) {
            if (!isset($clientIdx[$keyName])) {
                if ($this->hasEquivalentIndexDefinition($clientIdx, (array) $masterDef)) {
                    continue;
                }

                if ($this->shouldSkipUniqueIndexSync($client, $table, (array) $masterDef, $clientCols)) {
                    continue;
                }

                $sql[] = 'ALTER TABLE `' . $table . '` ADD ' . $this->indexDefinitionSql($masterDef) . ';';
                continue;
            }

            if ($this->indexSignature($masterDef) !== $this->indexSignature($clientIdx[$keyName])) {
                if ($this->hasEquivalentIndexDefinition($clientIdx, (array) $masterDef, $keyName)) {
                    continue;
                }

                if ($this->shouldSkipUniqueIndexSync($client, $table, (array) $masterDef, $clientCols)) {
                    continue;
                }

                if ($keyName === 'PRIMARY') {
                    $sql[] = 'ALTER TABLE `' . $table . '` DROP PRIMARY KEY;';
                } else {
                    $sql[] = 'ALTER TABLE `' . $table . '` DROP INDEX `' . $keyName . '`;';
                }
                $sql[] = 'ALTER TABLE `' . $table . '` ADD ' . $this->indexDefinitionSql($masterDef) . ';';
            }
        }

        return $sql;
    }

    /**
     * @param array<string, array<string, mixed>> $clientIdx
     * @param array<string, mixed> $masterDef
     */
    private function hasEquivalentIndexDefinition(array $clientIdx, array $masterDef, ?string $ignoreKeyName = null): bool
    {
        $masterCols = array_values((array) ($masterDef['columns'] ?? []));
        $masterType = strtoupper((string) ($masterDef['type'] ?? 'BTREE'));
        $masterUnique = (bool) ($masterDef['unique'] ?? false);
        $masterName = (string) ($masterDef['name'] ?? '');
        $masterIsPrimary = strtoupper($masterName) === 'PRIMARY';

        if ($masterIsPrimary) {
            $masterUnique = true;
        }

        if (empty($masterCols)) {
            return false;
        }

        foreach ($clientIdx as $clientKeyName => $clientDef) {
            if ($ignoreKeyName !== null && strcasecmp((string) $clientKeyName, $ignoreKeyName) === 0) {
                continue;
            }

            $clientCols = array_values((array) ($clientDef['columns'] ?? []));
            $clientType = strtoupper((string) ($clientDef['type'] ?? 'BTREE'));
            $clientUnique = (bool) ($clientDef['unique'] ?? false);
            $clientName = (string) ($clientDef['name'] ?? '');
            $clientIsPrimary = strtoupper($clientName) === 'PRIMARY';

            if ($clientIsPrimary) {
                $clientUnique = true;
            }

            if ($masterCols === $clientCols && $masterType === $clientType && $masterUnique === $clientUnique) {
                return true;
            }
        }

        return false;
    }

    private function connectSyncDb(string $role)
    {
        $role = strtolower(trim($role));
        $cfg = [
            'DSN' => '',
            'hostname' => (string) env('setup.sync.' . $role . '.hostname', ''),
            'username' => (string) env('setup.sync.' . $role . '.username', ''),
            'password' => (string) env('setup.sync.' . $role . '.password', ''),
            'database' => (string) env('setup.sync.' . $role . '.database', ''),
            'DBDriver' => (string) env('setup.sync.' . $role . '.dbdriver', 'MySQLi'),
            'DBPrefix' => '',
            'pConnect' => false,
            'DBDebug' => true,
            'charset' => (string) env('setup.sync.' . $role . '.charset', 'utf8mb4'),
            'DBCollat' => (string) env('setup.sync.' . $role . '.dbcollat', 'utf8mb4_general_ci'),
            'swapPre' => '',
            'encrypt' => false,
            'compress' => false,
            'strictOn' => false,
            'failover' => [],
            'port' => (int) env('setup.sync.' . $role . '.port', 3306),
        ];

        if ($cfg['hostname'] === '' || $cfg['username'] === '' || $cfg['database'] === '') {
            throw new \RuntimeException('Missing setup.sync.' . $role . ' database config in .env');
        }

        return \Config\Database::connect($cfg, false);
    }

    private function dbToolsUrlWithKey(): string
    {
        $url = base_url('setup/db-tools');
        $key = trim((string) env('setup.dbToolsKey', ''));
        if ($key !== '') {
            $url .= '?key=' . urlencode($key);
        }
        return $url;
    }

    private function getCreateTableSql($db, string $table): string
    {
        $row = $db->query('SHOW CREATE TABLE `' . $table . '`')->getRowArray();
        if (empty($row)) {
            return '';
        }

        $create = '';
        foreach ($row as $key => $val) {
            if (is_string($key) && stripos($key, 'Create Table') !== false) {
                $create = (string) $val;
                break;
            }
        }
        if ($create === '') {
            $values = array_values($row);
            $create = (string) ($values[1] ?? '');
        }
        if ($create === '') {
            return '';
        }

        $create = $this->ensureCreateTableIfNotExists((string) $create);
        if (!str_ends_with($create, ';')) {
            $create .= ';';
        }

        return $create;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function readColumns($db, string $table): array
    {
        $rows = $db->query('SHOW FULL COLUMNS FROM `' . $table . '`')->getResultArray();
        $out = [];
        foreach ($rows as $row) {
            $name = (string) ($row['Field'] ?? '');
            if ($name !== '') {
                $out[$name] = $row;
            }
        }
        return $out;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function readIndexes($db, string $table): array
    {
        $rows = $db->query('SHOW INDEX FROM `' . $table . '`')->getResultArray();
        $out = [];

        foreach ($rows as $row) {
            $key = (string) ($row['Key_name'] ?? '');
            if ($key === '') {
                continue;
            }

            if (!isset($out[$key])) {
                $out[$key] = [
                    'name' => $key,
                    'unique' => ((int) ($row['Non_unique'] ?? 1)) === 0,
                    'type' => strtoupper((string) ($row['Index_type'] ?? 'BTREE')),
                    'columns' => [],
                ];
            }

            $seq = (int) ($row['Seq_in_index'] ?? 0);
            $out[$key]['columns'][$seq] = (string) ($row['Column_name'] ?? '');
        }

        foreach ($out as $key => $def) {
            ksort($def['columns']);
            $def['columns'] = array_values(array_filter($def['columns']));
            $out[$key] = $def;
        }

        return $out;
    }

    private function columnDefinition(array $col): string
    {
        $name = (string) ($col['Field'] ?? '');
        $type = (string) ($col['Type'] ?? 'text');
        $null = strtoupper((string) ($col['Null'] ?? 'YES')) === 'NO' ? ' NOT NULL' : ' NULL';
        $extra = trim((string) ($col['Extra'] ?? ''));
        $default = $this->columnDefaultSql($col);
        $collation = trim((string) ($col['Collation'] ?? ''));
        $comment = trim((string) ($col['Comment'] ?? ''));
        $applyCollation = strtolower(trim((string) env('setup.sync.apply_column_collation', 'false')));
        $canApplyCollation = in_array($applyCollation, ['1', 'true', 'yes', 'on'], true);

        $sql = '`' . $name . '` ' . $type;
        if ($collation !== '' && $canApplyCollation) {
            $sql .= ' COLLATE ' . $this->normalizeCollationName($collation);
        }
        $sql .= $null . $default;
        if ($extra !== '') {
            $extra = preg_replace('/\bDEFAULT_GENERATED\b/i', '', $extra) ?? $extra;
            $extra = trim(preg_replace('/\s+/', ' ', $extra) ?? $extra);
            if ($extra !== '') {
                $sql .= ' ' . strtoupper($extra);
            }
        }
        if ($comment !== '') {
            $sql .= " COMMENT '" . str_replace("'", "''", $comment) . "'";
        }

        return $sql;
    }

    private function columnDefaultSql(array $col): string
    {
        if (!array_key_exists('Default', $col)) {
            return '';
        }

        $default = $col['Default'];
        if ($default === null) {
            $isNotNull = strtoupper((string) ($col['Null'] ?? 'YES')) === 'NO';
            return $isNotNull ? '' : ' DEFAULT NULL';
        }

        $defaultStr = (string) $default;
        $upper = strtoupper($defaultStr);
        if ($upper === 'CURRENT_TIMESTAMP' || str_starts_with($upper, 'CURRENT_TIMESTAMP(')) {
            return ' DEFAULT ' . $defaultStr;
        }

        return " DEFAULT '" . str_replace("'", "''", $defaultStr) . "'";
    }

    private function columnSignature(array $col): string
    {
        $type = $this->normalizeColumnTypeForCompare((string) ($col['Type'] ?? ''));

        $nullability = $this->normalizeColumnNullabilityForCompare($col);
        $defaultNormalized = $this->normalizeColumnDefaultFromMetadata($col);

        return json_encode([
            $type,
            $nullability,
            $defaultNormalized,
        ]) ?: '';
    }

    private function normalizeColumnTypeForCompare(string $type): string
    {
        $type = strtolower(trim($type));
        $type = preg_replace('/\s+/', ' ', $type) ?? $type;

        if ($type === '') {
            return '';
        }

        // MySQL/MariaDB may report integer display width differently: int vs int(11), int unsigned vs int(10) unsigned.
        // Ignore only integer display width for comparison to avoid false-positive MODIFY statements.
        $type = preg_replace('/\b(tinyint|smallint|mediumint|int|integer|bigint)\s*\(\s*\d+\s*\)/i', '$1', $type) ?? $type;
        $type = preg_replace('/\s+/', ' ', $type) ?? $type;

        return trim($type);
    }

    /**
     * @param array<string, mixed> $masterCol
     * @param array<string, mixed> $clientCol
     */
    private function shouldModifyExistingColumn(array $masterCol, array $clientCol): bool
    {
        $masterType = $this->normalizeColumnTypeForCompare((string) ($masterCol['Type'] ?? ''));
        $clientType = $this->normalizeColumnTypeForCompare((string) ($clientCol['Type'] ?? ''));

        $typeMatches = $masterType === $clientType
            || $this->isCompatibleWiderType($clientType, $masterType);

        if (!$typeMatches) {
            return true;
        }

        if ($this->normalizeColumnNullabilityForCompare($masterCol) !== $this->normalizeColumnNullabilityForCompare($clientCol)) {
            return true;
        }

        if ($this->normalizeColumnDefaultFromMetadata($masterCol) !== $this->normalizeColumnDefaultFromMetadata($clientCol)) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $col
     */
    private function normalizeColumnNullabilityForCompare(array $col): string
    {
        $nullability = strtoupper(trim((string) ($col['Null'] ?? 'YES')));
        return $nullability === 'NO' ? 'NO' : 'YES';
    }

    /**
     * @param array<string, mixed> $col
     */
    private function normalizeColumnDefaultFromMetadata(array $col): string
    {
        $default = $col['Default'] ?? null;
        if ($default === null) {
            return '__NULL__';
        }

        return $this->normalizeColumnDefaultForCompare((string) $default);
    }

    private function isCompatibleWiderType(string $clientType, string $masterType): bool
    {
        $client = $this->parseColumnTypeSpec($clientType);
        $master = $this->parseColumnTypeSpec($masterType);

        if ($client === null || $master === null) {
            return false;
        }

        if ($client['base'] !== $master['base']) {
            return false;
        }

        if ($client['unsigned'] !== $master['unsigned']) {
            return false;
        }

        if (!in_array($client['base'], ['varchar', 'char', 'varbinary', 'binary'], true)) {
            return false;
        }

        if ($client['length'] === null || $master['length'] === null) {
            return false;
        }

        return $client['length'] >= $master['length'];
    }

    /**
     * @return array{base:string,unsigned:bool,length:int|null}|null
     */
    private function parseColumnTypeSpec(string $type): ?array
    {
        $type = trim(strtolower($type));
        if ($type === '') {
            return null;
        }

        $type = preg_replace('/\s+/', ' ', $type) ?? $type;

        if (preg_match('/^([a-z]+)\s*\(\s*(\d+)\s*\)\s*(unsigned)?$/i', $type, $m) === 1) {
            return [
                'base' => strtolower((string) $m[1]),
                'length' => (int) $m[2],
                'unsigned' => !empty($m[3]),
            ];
        }

        if (preg_match('/^([a-z]+)\s*(unsigned)?$/i', $type, $m) === 1) {
            return [
                'base' => strtolower((string) $m[1]),
                'length' => null,
                'unsigned' => !empty($m[2]),
            ];
        }

        return null;
    }

    private function normalizeColumnDefaultForCompare(string $default): string
    {
        $defaultStr = trim($default);
        if ($defaultStr === '') {
            return '';
        }

        $upper = strtoupper($defaultStr);

        // Treat CURRENT_TIMESTAMP, CURRENT_TIMESTAMP(), and CURRENT_TIMESTAMP(0) as equivalent.
        if (preg_match('/^CURRENT_TIMESTAMP\s*(?:\(\s*0?\s*\))?$/i', $upper) === 1) {
            return 'CURRENT_TIMESTAMP';
        }

        return $defaultStr;
    }

    private function ensureCreateTableIfNotExists(string $sql): string
    {
        $sql = trim($sql);
        if ($sql === '') {
            return '';
        }

        $sql = preg_replace('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?/i', 'CREATE TABLE IF NOT EXISTS ', $sql, 1) ?? $sql;
        return $this->normalizeSyncSql($sql);
    }

    private function normalizeCollationName(string $collation): string
    {
        $collation = trim($collation);
        if ($collation === '') {
            return '';
        }

        if (preg_match('/^utf8mb4_0900_/i', $collation) === 1) {
            return (string) env('setup.sync.collation_fallback', 'utf8mb4_general_ci');
        }

        return $collation;
    }

    private function normalizeSyncSql(string $sql): string
    {
        $sql = trim($sql);
        if ($sql === '') {
            return '';
        }

        $fallbackCollation = $this->normalizeCollationName('utf8mb4_0900_ai_ci');
        $sql = preg_replace('/\bDEFAULT_GENERATED\b/i', '', $sql) ?? $sql;
        $sql = preg_replace('/\butf8mb4_0900_[a-z0-9_]+\b/i', $fallbackCollation, $sql) ?? $sql;
        $sql = preg_replace('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)+/i', 'CREATE TABLE IF NOT EXISTS ', $sql, 1) ?? $sql;
        $sql = preg_replace('/\s+/', ' ', $sql) ?? $sql;
        $sql = preg_replace('/\s+;$/', ';', $sql) ?? $sql;

        return trim($sql);
    }

    private function isCreateTableStatement(string $stmt): bool
    {
        return preg_match('/^CREATE\s+TABLE\s+/i', ltrim($stmt)) === 1;
    }

    private function isSafeToSkipSyncStatement(string $message, string $stmt): bool
    {
        $messageLower = strtolower($message);
        $stmtLower = strtolower($stmt);

        if (str_contains($messageLower, 'used in a foreign key constraint') && str_contains($stmtLower, 'alter table')) {
            return true;
        }

        if (str_contains($messageLower, 'blob/text column') && str_contains($messageLower, 'key specification without a key length')) {
            return true;
        }

        if (str_contains($messageLower, 'duplicate entry') && str_contains($stmtLower, 'alter table') && (str_contains($stmtLower, 'add unique key') || str_contains($stmtLower, 'add primary key'))) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $idxDef
     * @param array<string, array<string, mixed>> $clientCols
     */
    private function shouldSkipUniqueIndexSync($db, string $table, array $idxDef, array $clientCols): bool
    {
        $isPrimary = strtoupper((string) ($idxDef['name'] ?? '')) === 'PRIMARY';
        $isUnique = $isPrimary || (bool) ($idxDef['unique'] ?? false);
        if (!$isUnique) {
            return false;
        }

        $columns = array_values(array_filter(array_map(static fn ($col) => (string) $col, (array) ($idxDef['columns'] ?? []))));
        if (empty($columns)) {
            return false;
        }

        $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table) ?? '';
        if ($safeTable === '') {
            return false;
        }

        $safeColumns = [];
        $notNullConditions = [];
        foreach ($columns as $column) {
            $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column) ?? '';
            if ($safeColumn === '') {
                return false;
            }
            $safeColumns[] = '`' . $safeColumn . '`';

            $colMeta = $clientCols[$safeColumn] ?? null;
            $isNullable = strtoupper((string) ($colMeta['Null'] ?? 'YES')) !== 'NO';
            if ($isNullable) {
                $notNullConditions[] = '`' . $safeColumn . '` IS NOT NULL';
            }
        }

        $groupBy = implode(', ', $safeColumns);
        $sql = 'SELECT 1 FROM `' . $safeTable . '`';
        if (!empty($notNullConditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $notNullConditions);
        }
        $sql .= ' GROUP BY ' . $groupBy . ' HAVING COUNT(*) > 1 LIMIT 1';

        try {
            $row = $db->query($sql)->getRowArray();
            return !empty($row);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function stripForeignKeysFromCreateTable(string $stmt): string
    {
        $out = $stmt;
        $patterns = [
            '/\s*,\s*CONSTRAINT\s+`[^`]+`\s+FOREIGN\s+KEY\s*\([^\)]*\)\s*REFERENCES\s*`[^`]+`\s*\([^\)]*\)(?:\s+ON\s+DELETE\s+[A-Z_ ]+)?(?:\s+ON\s+UPDATE\s+[A-Z_ ]+)?/i',
            '/\s*,\s*FOREIGN\s+KEY\s*\([^\)]*\)\s*REFERENCES\s*`[^`]+`\s*\([^\)]*\)(?:\s+ON\s+DELETE\s+[A-Z_ ]+)?(?:\s+ON\s+UPDATE\s+[A-Z_ ]+)?/i',
        ];

        foreach ($patterns as $pattern) {
            $out = preg_replace($pattern, '', $out) ?? $out;
        }

        $out = preg_replace('/,\s*\)/', ')', $out) ?? $out;
        $out = preg_replace('/\s+/', ' ', $out) ?? $out;
        $out = trim($out);

        if ($out !== '' && !str_ends_with($out, ';')) {
            $out .= ';';
        }

        return $this->normalizeSyncSql($out);
    }

    private function indexDefinitionSql(array $idx): string
    {
        $name = (string) ($idx['name'] ?? '');
        $columns = array_values((array) ($idx['columns'] ?? []));
        $columnSql = implode(',', array_map(static fn ($c) => '`' . $c . '`', $columns));

        if ($name === 'PRIMARY') {
            return 'PRIMARY KEY (' . $columnSql . ')';
        }

        $type = strtoupper((string) ($idx['type'] ?? 'BTREE'));
        $unique = (bool) ($idx['unique'] ?? false);

        if ($type === 'FULLTEXT') {
            return 'FULLTEXT KEY `' . $name . '` (' . $columnSql . ')';
        }
        if ($type === 'SPATIAL') {
            return 'SPATIAL KEY `' . $name . '` (' . $columnSql . ')';
        }
        if ($unique) {
            return 'UNIQUE KEY `' . $name . '` (' . $columnSql . ')';
        }

        return 'KEY `' . $name . '` (' . $columnSql . ')';
    }

    private function indexSignature(array $idx): string
    {
        return json_encode([
            (string) ($idx['name'] ?? ''),
            (bool) ($idx['unique'] ?? false),
            strtoupper((string) ($idx['type'] ?? 'BTREE')),
            array_values((array) ($idx['columns'] ?? [])),
        ]) ?: '';
    }

    /**
     * @return array<string, bool>
     */
    private function detectScriptedTables(): array
    {
        $result = [];
        $files = glob(APPPATH . 'Database/Migrations/*.php') ?: [];

        foreach ($files as $filePath) {
            $content = @file_get_contents($filePath);
            if (!is_string($content) || $content === '') {
                continue;
            }

            if (preg_match_all('/createTable\(\s*[\'\"]([a-zA-Z0-9_]+)[\'\"]/i', $content, $m1) === 1 || !empty($m1[1])) {
                foreach (($m1[1] ?? []) as $table) {
                    $result[strtolower((string) $table)] = true;
                }
            }

            if (preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $content, $m2) === 1 || !empty($m2[1])) {
                foreach (($m2[1] ?? []) as $table) {
                    $result[strtolower((string) $table)] = true;
                }
            }
        }

        return $result;
    }

    private function studly(string $value): string
    {
        $value = preg_replace('/[^a-zA-Z0-9]+/', ' ', $value) ?? $value;
        $value = ucwords(strtolower(trim($value)));
        return str_replace(' ', '', $value);
    }

    /**
     * @return array<int, string>
     */
    private function requiredWritablePaths(): array
    {
        return [
            WRITEPATH,
            WRITEPATH . 'cache',
            WRITEPATH . 'logs',
            WRITEPATH . 'session',
            WRITEPATH . 'uploads',
            WRITEPATH . 'debugbar',
            WRITEPATH . 'tmp',
            FCPATH . 'uploads',
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function prepareWritablePaths(): array
    {
        $checked = [];
        $created = [];
        $permissionUpdated = [];
        $writable = [];
        $failed = [];

        $paths = $this->requiredWritablePaths();
        foreach ($paths as $path) {
            $checked[] = $this->relativeProjectPath($path);

            if (!is_dir($path)) {
                if (@mkdir($path, 0775, true)) {
                    $created[] = $this->relativeProjectPath($path);
                }
            }

            if (is_dir($path) && !is_writable($path)) {
                if (@chmod($path, 0775)) {
                    $permissionUpdated[] = $this->relativeProjectPath($path);
                }
            }

            if (is_dir($path) && is_writable($path)) {
                $writable[] = $this->relativeProjectPath($path);
            } else {
                $failed[] = $this->relativeProjectPath($path);
            }
        }

        $commands = [];
        if (!empty($failed)) {
            $projectPath = rtrim(str_replace('\\', '/', ROOTPATH), '/');
            $commands[] = 'sudo chown -R www-data:www-data "' . $projectPath . '/writable" "' . $projectPath . '/public/uploads"';
            $commands[] = 'sudo find "' . $projectPath . '/writable" -type d -exec chmod 775 {} \;';
            $commands[] = 'sudo find "' . $projectPath . '/writable" -type f -exec chmod 664 {} \;';
            $commands[] = 'sudo chmod -R 775 "' . $projectPath . '/public/uploads"';
        }

        return [
            'checked' => $checked,
            'created' => $created,
            'permission_updated' => $permissionUpdated,
            'writable' => $writable,
            'failed' => array_values(array_unique($failed)),
            'commands' => $commands,
        ];
    }

    private function relativeProjectPath(string $absolutePath): string
    {
        $normalizedPath = str_replace('\\', '/', $absolutePath);
        $normalizedRoot = str_replace('\\', '/', ROOTPATH);

        if (str_starts_with($normalizedPath, $normalizedRoot)) {
            return ltrim(substr($normalizedPath, strlen($normalizedRoot)), '/');
        }

        return $absolutePath;
    }

    /**
     * @return array{applied:array<int,string>,warnings:array<int,string>,errors:array<int,string>}
     */
    private function repairAuthSchemaInternal(): array
    {
        $applied = [];
        $warnings = [];
        $errors = [];

        try {
            $tables = config('Auth')->tables;
            $usersTable = (string) ($tables['users'] ?? 'users');

            if (!$this->tableExists($usersTable)) {
                $errors[] = 'Auth users table not found: ' . $usersTable . '. Run schema sync first.';
                return ['applied' => $applied, 'warnings' => $warnings, 'errors' => $errors];
            }

            $requiredColumns = [
                'deleted_at' => 'ALTER TABLE `' . $usersTable . '` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `updated_at`',
                'updated_at' => 'ALTER TABLE `' . $usersTable . '` ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL AFTER `created_at`',
                'created_at' => 'ALTER TABLE `' . $usersTable . '` ADD COLUMN `created_at` DATETIME NULL DEFAULT NULL AFTER `last_active`',
                'last_active' => 'ALTER TABLE `' . $usersTable . '` ADD COLUMN `last_active` DATETIME NULL DEFAULT NULL AFTER `active`',
                'active' => 'ALTER TABLE `' . $usersTable . '` ADD COLUMN `active` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status_message`',
                'status_message' => 'ALTER TABLE `' . $usersTable . '` ADD COLUMN `status_message` VARCHAR(255) NULL DEFAULT NULL AFTER `status`',
                'status' => 'ALTER TABLE `' . $usersTable . '` ADD COLUMN `status` VARCHAR(255) NULL DEFAULT NULL AFTER `username`',
                'username' => 'ALTER TABLE `' . $usersTable . '` ADD COLUMN `username` VARCHAR(30) NULL DEFAULT NULL AFTER `id`',
            ];

            foreach ($requiredColumns as $column => $sql) {
                if ($this->columnExists($usersTable, $column)) {
                    continue;
                }

                try {
                    $this->db->query($sql);
                    $applied[] = $usersTable . '.' . $column;
                } catch (\Throwable $e) {
                    $errors[] = 'Failed to add ' . $usersTable . '.' . $column . ': ' . $e->getMessage();
                }
            }

            if (!$this->columnExists($usersTable, 'deleted_at')) {
                $errors[] = 'Column still missing after repair: ' . $usersTable . '.deleted_at';
            }
        } catch (\Throwable $e) {
            $errors[] = 'Auth schema repair exception: ' . $e->getMessage();
        }

        return ['applied' => $applied, 'warnings' => $warnings, 'errors' => $errors];
    }

    /**
     * @return array{warnings:array<int,string>,errors:array<int,string>}
     */
    private function checkAuthSchemaHealth(): array
    {
        $warnings = [];
        $errors = [];

        try {
            $tables = config('Auth')->tables;
            $usersTable = (string) ($tables['users'] ?? 'users');

            if (!$this->tableExists($usersTable)) {
                $errors[] = 'Auth users table missing: ' . $usersTable . '.';
                return ['warnings' => $warnings, 'errors' => $errors];
            }

            if (!$this->columnExists($usersTable, 'deleted_at')) {
                $errors[] = 'Missing required auth column: ' . $usersTable . '.deleted_at (causes login failure).';
            }
        } catch (\Throwable $e) {
            $warnings[] = 'Auth schema diagnostics skipped: ' . $e->getMessage();
        }

        return ['warnings' => $warnings, 'errors' => $errors];
    }

    private function tableExists(string $tableName): bool
    {
        $tableName = trim($tableName);
        if ($tableName === '') {
            return false;
        }

        try {
            $tables = $this->db->listTables();
            return in_array($tableName, $tables, true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $tableName = trim($tableName);
        $columnName = trim($columnName);
        if ($tableName === '' || $columnName === '') {
            return false;
        }

        try {
            $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName) ?? '';
            $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $columnName) ?? '';
            if ($safeTable === '' || $safeColumn === '') {
                return false;
            }

            $row = $this->db->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'")->getRowArray();
            return !empty($row);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

<?php

namespace App\Controllers;

use App\Controllers\BaseController;

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

        return view('setup/db_setup', $this->buildDbToolsViewData());
    }

    public function schemaSync()
    {
        if ($deny = $this->ensureSetupAccess()) {
            return $deny;
        }

        $action = strtolower(trim((string) $this->request->getPost('sync_action')));
        if (!in_array($action, ['analyze', 'apply'], true)) {
            $action = 'analyze';
        }

        $syncResult = $this->buildSchemaSyncPlan();
        if ($action === 'apply' && empty($syncResult['errors']) && !empty($syncResult['sql'])) {
            $applyResult = $this->applySchemaSyncSql($syncResult['sql']);
            $syncResult['applied'] = $applyResult['applied'];
            $syncResult['apply_errors'] = $applyResult['errors'];
        }

        $this->lastSyncResult = $syncResult;

        return view('setup/db_setup', $this->buildDbToolsViewData());
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

            $createSql = preg_replace('/^CREATE\s+TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', trim($createSql), 1) ?? $createSql;
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

    /**
     * @return array<string, mixed>
     */
    private function buildDbToolsViewData(): array
    {
        $allTables = $this->db->listTables();
        $scripted = $this->detectScriptedTables();

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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSchemaSyncPlan(): array
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

        $createCount = 0;
        $alterCount = 0;

        foreach ($masterTables as $table => $masterTableDef) {
            $table = (string) $table;
            if (!isset($clientTables[$table])) {
                $createSql = (string) ($masterTableDef['create_sql'] ?? '');
                if ($createSql !== '') {
                    $createSql = preg_replace('/^CREATE\s+TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', trim($createSql), 1) ?? $createSql;
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
                'create_tables' => $createCount,
                'alter_statements' => $alterCount,
            ],
            'source' => (string) ($masterSchema['source'] ?? 'master-schema-file'),
        ];
    }

    /**
     * @param array<int, string> $sqlList
     * @return array{applied:int,errors:array<int,string>}
     */
    private function applySchemaSyncSql(array $sqlList): array
    {
        $client = $this->resolveClientConnection();
        $applied = 0;
        $errors = [];

        foreach ($sqlList as $stmt) {
            $stmt = trim((string) $stmt);
            if ($stmt === '') {
                continue;
            }
            try {
                $client->query($stmt);
                $applied++;
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage() . ' | SQL: ' . mb_substr($stmt, 0, 180);
            }
        }

        return ['applied' => $applied, 'errors' => $errors];
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

        $masterCols = is_array($masterTableDef['columns'] ?? null) ? $masterTableDef['columns'] : [];
        $clientCols = $this->readColumns($client, $table);

        $prevCol = '';
        foreach ($masterCols as $colName => $masterCol) {
            if (!isset($clientCols[$colName])) {
                $position = $prevCol === '' ? ' FIRST' : (' AFTER `' . $prevCol . '`');
                $sql[] = 'ALTER TABLE `' . $table . '` ADD COLUMN ' . $this->columnDefinition($masterCol) . $position . ';';
                $prevCol = $colName;
                continue;
            }

            if ($this->columnSignature($masterCol) !== $this->columnSignature($clientCols[$colName])) {
                $sql[] = 'ALTER TABLE `' . $table . '` MODIFY COLUMN ' . $this->columnDefinition($masterCol) . ';';
            }
            $prevCol = $colName;
        }

        $masterIdx = is_array($masterTableDef['indexes'] ?? null) ? $masterTableDef['indexes'] : [];
        $clientIdx = $this->readIndexes($client, $table);

        foreach ($masterIdx as $keyName => $masterDef) {
            if (!isset($clientIdx[$keyName])) {
                $sql[] = 'ALTER TABLE `' . $table . '` ADD ' . $this->indexDefinitionSql($masterDef) . ';';
                continue;
            }

            if ($this->indexSignature($masterDef) !== $this->indexSignature($clientIdx[$keyName])) {
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

        $create = preg_replace('/^CREATE\s+TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', trim($create), 1) ?? $create;
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

        $sql = '`' . $name . '` ' . $type;
        if ($collation !== '') {
            $sql .= ' COLLATE ' . $collation;
        }
        $sql .= $null . $default;
        if ($extra !== '') {
            $sql .= ' ' . strtoupper($extra);
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
        return json_encode([
            strtolower((string) ($col['Type'] ?? '')),
            strtoupper((string) ($col['Null'] ?? 'YES')),
            (string) ($col['Default'] ?? ''),
            strtolower((string) ($col['Extra'] ?? '')),
            strtolower((string) ($col['Collation'] ?? '')),
            (string) ($col['Comment'] ?? ''),
        ]) ?: '';
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
}

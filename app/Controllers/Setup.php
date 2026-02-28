<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Setup extends BaseController
{
    private function lockFilePath(): string
    {
        return WRITEPATH . 'setup_db_complete.lock';
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

        $flash = session('setup_msg');

        return view('setup/db_setup', [
            'tables' => $tableRows,
            'msg' => $flash,
            'lock_file' => $this->lockFilePath(),
            'setup_key' => trim((string) env('setup.dbToolsKey', '')),
        ]);
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
            return redirect()->to(base_url('setup/db-tools'));
        }

        $migrationDir = APPPATH . 'Database/Migrations';
        if (!is_dir($migrationDir) && !mkdir($migrationDir, 0777, true) && !is_dir($migrationDir)) {
            session()->setFlashdata('setup_msg', 'Unable to create migration directory.');
            return redirect()->to(base_url('setup/db-tools'));
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

        return redirect()->to(base_url('setup/db-tools'));
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

<?php

namespace App\Libraries;

use RuntimeException;

class SystemOperations
{
    private string $historyPath;

    public function __construct()
    {
        $this->historyPath = WRITEPATH . 'system_update_history.json';
    }

    public function collectStatus(): array
    {
        $status = [
            'hostname' => $this->getHostname(),
            'os' => $this->getOsInfo(),
            'uptime' => $this->getUptime(),
            'cpu' => ['used_percent' => null],
            'memory' => ['total_mb' => null, 'used_mb' => null, 'free_mb' => null],
            'disk' => ['total_gb' => null, 'used_gb' => null, 'free_gb' => null],
            'network' => 'Unavailable',
            'internet' => 'Unavailable',
            'services' => [],
            'raid' => 'Unavailable',
            'last_update' => null,
        ];

        $status['cpu']['used_percent'] = $this->readCpuUsage();
        $status['memory'] = $this->readMemoryUsage();
        $status['disk'] = $this->readDiskUsage();
        $status['network'] = $this->readNetworkStatus();
        $status['internet'] = $this->readInternetStatus();
        $status['services'] = $this->readServiceStatus();
        $status['raid'] = $this->readRaidStatus();
        $status['last_update'] = $this->getLatestHistoryEntry();

        return $status;
    }

    public function runUpdateDirect(): array
    {
        $repoPath = ROOTPATH;
        $logFile = WRITEPATH . 'system_update.log';
        $startedAt = date('Y-m-d H:i:s');
        
        $entry = [
            'timestamp' => $startedAt,
            'type' => 'update',
            'status' => 'running',
            'message' => 'HMS update started (direct deployment from GitHub)',
        ];
        $this->appendHistory($entry);

        try {
            // GitHub repo details - PUBLIC REPO, no auth needed
            $githubOwner = 'infodevsofttech-admin';
            $githubRepo = 'devsofttech_hms_ci4';
            $branch = 'main';
            
            // Download latest ZIP from GitHub
            $zipUrl = "https://github.com/{$githubOwner}/{$githubRepo}/archive/refs/heads/{$branch}.zip";
            $tempDir = sys_get_temp_dir();
            $zipFile = $tempDir . '/hms_update_' . time() . '.zip';
            
            file_put_contents($logFile, "Downloading from: {$zipUrl}\n", FILE_APPEND);
            
            $zipContent = @file_get_contents($zipUrl);
            if ($zipContent === false) {
                throw new \Exception('Failed to download from GitHub. Check network connectivity and GitHub availability.');
            }
            
            if (!file_put_contents($zipFile, $zipContent)) {
                throw new \Exception('Failed to write downloaded ZIP to temporary directory.');
            }
            
            file_put_contents($logFile, "Downloaded ZIP: {$zipFile} (" . filesize($zipFile) . " bytes)\n", FILE_APPEND);
            
            // Extract ZIP
            $zip = new \ZipArchive();
            if ($zip->open($zipFile) !== true) {
                throw new \Exception('Failed to open downloaded ZIP file.');
            }
            
            $extractDir = $tempDir . '/hms_extract_' . time();
            if (!mkdir($extractDir)) {
                throw new \Exception('Failed to create extraction directory.');
            }
            
            $zip->extractTo($extractDir);
            $zip->close();
            
            // Find the extracted directory (GitHub creates a folder like "repo-main")
            $dirs = array_diff(scandir($extractDir), ['.', '..']);
            if (empty($dirs)) {
                throw new \Exception('Downloaded ZIP appears to be empty.');
            }
            
            $extractedRepoDir = $extractDir . '/' . reset($dirs);
            file_put_contents($logFile, "Extracted to: {$extractedRepoDir}\n", FILE_APPEND);
            
            // Sync files (skip git, vendor, and certain local files)
            $this->syncFiles($extractedRepoDir, $repoPath, $logFile);
            
            // Cleanup
            $this->deleteDirectory($extractDir);
            @unlink($zipFile);
            
            $successMsg = 'HMS updated successfully from GitHub (direct deployment)';
            $this->appendHistory([
                'timestamp' => date('Y-m-d H:i:s'),
                'type' => 'update',
                'status' => 'success',
                'message' => $successMsg,
                'detail' => 'Files synced from ' . $zipUrl,
            ]);
            file_put_contents($logFile, "SUCCESS: {$successMsg}\n", FILE_APPEND);
            
            return ['ok' => true, 'message' => $successMsg];
        } catch (\Throwable $e) {
            $errorMsg = 'Update failed: ' . $e->getMessage();
            $this->appendHistory([
                'timestamp' => date('Y-m-d H:i:s'),
                'type' => 'update',
                'status' => 'failed',
                'message' => 'HMS update failed',
                'detail' => $errorMsg,
            ]);
            file_put_contents($logFile, "FAILED: {$errorMsg}\n", FILE_APPEND);
            return ['ok' => false, 'message' => $errorMsg];
        }
    }

    private function syncFiles(string $sourceDir, string $destDir, string $logFile): void
    {
        $skip = ['.git', '.gitignore', 'vendor', 'node_modules', 'writable', '.env', '.env.example'];
        
        $this->copyRecursive($sourceDir, $destDir, $skip, $logFile);
        file_put_contents($logFile, "File sync completed\n", FILE_APPEND);
    }

    private function copyRecursive(string $src, string $dest, array $skip, string $logFile): void
    {
        $dir = opendir($src);
        if (!$dir) {
            throw new \Exception("Cannot open directory: {$src}");
        }
        
        while (false !== ($file = readdir($dir))) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            if (in_array($file, $skip)) {
                continue;
            }
            
            $srcPath = $src . '/' . $file;
            $destPath = $dest . '/' . $file;
            
            if (is_dir($srcPath)) {
                if (!is_dir($destPath)) {
                    @mkdir($destPath, 0755, true);
                }
                $this->copyRecursive($srcPath, $destPath, $skip, $logFile);
            } else {
                if (@copy($srcPath, $destPath)) {
                    file_put_contents($logFile, "  ✓ {$file}\n", FILE_APPEND);
                } else {
                    file_put_contents($logFile, "  ✗ {$file} (copy failed)\n", FILE_APPEND);
                }
            }
        }
        closedir($dir);
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    private function findGitCommand(): ?string
    {
        // Try common git installation paths
        $gitPaths = [
            '/usr/bin/git',
            '/bin/git',
            '/usr/local/bin/git',
        ];

        foreach ($gitPaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // Try to find git in PATH using shell
        $result = $this->runCommand('which git 2>/dev/null || command -v git 2>/dev/null');
        if ($result['exit_code'] === 0 && trim($result['output']) !== '') {
            $gitPath = trim($result['output']);
            if (file_exists($gitPath)) {
                return $gitPath;
            }
        }

        return null;
    }

    private function getGitDiagnostics(string $gitPath, string $repoPath): array
    {
        $diagnostics = [
            'git_version' => 'Unknown',
            'git_config_user_name' => 'Not set',
            'git_config_user_email' => 'Not set',
            'remote_url' => 'Not configured',
            'current_branch' => 'Unknown',
            'git_dir_exists' => 'No',
            'git_config_exists' => 'No',
            'repo_is_dirty' => 'Unknown',
        ];

        // Check if .git directory exists
        if (is_dir($repoPath . '/.git')) {
            $diagnostics['git_dir_exists'] = 'Yes';
        }

        // Check if .git/config exists
        if (file_exists($repoPath . '/.git/config')) {
            $diagnostics['git_config_exists'] = 'Yes';
        }

        // Get git version
        $result = $this->runCommand(escapeshellarg($gitPath) . ' --version 2>&1');
        if ($result['exit_code'] === 0 && trim($result['output']) !== '') {
            $diagnostics['git_version'] = trim($result['output']);
        }

        // Get git config using --local flag (repository-specific)
        $result = $this->runCommand('cd ' . escapeshellarg($repoPath) . ' && ' . escapeshellarg($gitPath) . ' config --local user.name 2>&1');
        if ($result['exit_code'] === 0 && trim($result['output']) !== '') {
            $diagnostics['git_config_user_name'] = trim($result['output']);
        } else {
            // Try global config
            $result = $this->runCommand(escapeshellarg($gitPath) . ' config --global user.name 2>&1');
            if ($result['exit_code'] === 0 && trim($result['output']) !== '') {
                $diagnostics['git_config_user_name'] = trim($result['output']) . ' (global)';
            }
        }

        $result = $this->runCommand('cd ' . escapeshellarg($repoPath) . ' && ' . escapeshellarg($gitPath) . ' config --local user.email 2>&1');
        if ($result['exit_code'] === 0 && trim($result['output']) !== '') {
            $diagnostics['git_config_user_email'] = trim($result['output']);
        } else {
            // Try global config
            $result = $this->runCommand(escapeshellarg($gitPath) . ' config --global user.email 2>&1');
            if ($result['exit_code'] === 0 && trim($result['output']) !== '') {
                $diagnostics['git_config_user_email'] = trim($result['output']) . ' (global)';
            }
        }

        // Get remote URL
        $result = $this->runCommand('cd ' . escapeshellarg($repoPath) . ' && ' . escapeshellarg($gitPath) . ' config --get remote.origin.url 2>&1');
        if ($result['exit_code'] === 0 && trim($result['output']) !== '') {
            $diagnostics['remote_url'] = trim($result['output']);
        }

        // Get current branch
        $result = $this->runCommand('cd ' . escapeshellarg($repoPath) . ' && ' . escapeshellarg($gitPath) . ' rev-parse --abbrev-ref HEAD 2>&1');
        if ($result['exit_code'] === 0 && trim($result['output']) !== '') {
            $diagnostics['current_branch'] = trim($result['output']);
        }

        // Check if repo is dirty (has uncommitted changes)
        $result = $this->runCommand('cd ' . escapeshellarg($repoPath) . ' && ' . escapeshellarg($gitPath) . ' status --porcelain 2>&1');
        if ($result['exit_code'] === 0) {
            $diagnostics['repo_is_dirty'] = (trim($result['output']) === '') ? 'No' : 'Yes (has changes)';
        }

        return $diagnostics;
    }

    public function getGitDiagnosticsForWeb(): array
    {
        $repoPath = ROOTPATH;
        $gitPath = $this->findGitCommand();
        
        $info = [
            'timestamp' => date('Y-m-d H:i:s'),
            'php_user' => get_current_user() ?: 'unknown',
            'repo_path' => $repoPath,
            'git_path' => $gitPath ?: 'NOT FOUND',
            'git_version' => 'N/A',
            'directory_exists' => [
                '.git' => is_dir($repoPath . '/.git'),
                '.git/config' => file_exists($repoPath . '/.git/config'),
            ],
            'file_permissions' => [
                '.git_readable' => is_readable($repoPath . '/.git'),
                '.git_writable' => is_writable($repoPath . '/.git'),
                'config_readable' => is_readable($repoPath . '/.git/config'),
                'config_writable' => is_writable($repoPath . '/.git/config'),
            ],
            'git_config_values' => [
                'user.name' => 'NOT SET',
                'user.email' => 'NOT SET',
                'remote.origin.url' => 'NOT SET',
                'current_branch' => 'UNKNOWN',
            ],
            'git_commands_output' => [],
            'troubleshooting_commands' => [],
        ];

        if (!$gitPath) {
            $info['error'] = 'Git binary not found. Tried: /usr/bin/git, /bin/git, /usr/local/bin/git';
            return $info;
        }

        // Get git version
        $result = $this->runCommand(escapeshellarg($gitPath) . ' --version 2>&1');
        $info['git_version'] = trim($result['output']) ?: 'Failed to get version';
        $info['git_commands_output']['--version'] = [
            'exit_code' => $result['exit_code'],
            'output' => $result['output'],
        ];

        // Get user.name
        $result = $this->runCommand('cd ' . escapeshellarg($repoPath) . ' && ' . escapeshellarg($gitPath) . ' config user.name 2>&1');
        if ($result['exit_code'] === 0 && trim($result['output']) !== '') {
            $info['git_config_values']['user.name'] = trim($result['output']);
        }
        $info['git_commands_output']['config_user.name'] = [
            'exit_code' => $result['exit_code'],
            'output' => $result['output'] ?: '(empty)',
        ];

        // Get user.email
        $result = $this->runCommand('cd ' . escapeshellarg($repoPath) . ' && ' . escapeshellarg($gitPath) . ' config user.email 2>&1');
        if ($result['exit_code'] === 0 && trim($result['output']) !== '') {
            $info['git_config_values']['user.email'] = trim($result['output']);
        }
        $info['git_commands_output']['config_user.email'] = [
            'exit_code' => $result['exit_code'],
            'output' => $result['output'] ?: '(empty)',
        ];

        // Get remote origin URL
        $result = $this->runCommand('cd ' . escapeshellarg($repoPath) . ' && ' . escapeshellarg($gitPath) . ' config remote.origin.url 2>&1');
        if ($result['exit_code'] === 0 && trim($result['output']) !== '') {
            $info['git_config_values']['remote.origin.url'] = trim($result['output']);
        }
        $info['git_commands_output']['config_remote.origin.url'] = [
            'exit_code' => $result['exit_code'],
            'output' => $result['output'] ?: '(empty)',
        ];

        // Get current branch
        $result = $this->runCommand('cd ' . escapeshellarg($repoPath) . ' && ' . escapeshellarg($gitPath) . ' rev-parse --abbrev-ref HEAD 2>&1');
        if ($result['exit_code'] === 0 && trim($result['output']) !== '') {
            $info['git_config_values']['current_branch'] = trim($result['output']);
        }
        $info['git_commands_output']['current_branch'] = [
            'exit_code' => $result['exit_code'],
            'output' => $result['output'] ?: '(empty)',
        ];

        // Test git status
        $result = $this->runCommand('cd ' . escapeshellarg($repoPath) . ' && ' . escapeshellarg($gitPath) . ' status 2>&1');
        $info['git_commands_output']['status'] = [
            'exit_code' => $result['exit_code'],
            'output_preview' => substr(trim($result['output']), 0, 200) . (strlen($result['output']) > 200 ? '...' : ''),
        ];

        // Provide troubleshooting commands
        if ($info['git_config_values']['user.name'] === 'NOT SET' || $info['git_config_values']['user.email'] === 'NOT SET') {
            $info['troubleshooting_commands'] = [
                'Run these commands on your server as root:',
                "cd {$repoPath}",
                "sudo -u www-data {$gitPath} config user.name 'www-data'",
                "sudo -u www-data {$gitPath} config user.email 'deploy@localhost'",
                "sudo -u www-data {$gitPath} status",
            ];
        }

        return $info;
    }

    public function runServerAction(string $action): array
    {
        $commands = [
            'restart_web' => ['systemctl restart nginx', 'systemctl restart apache2', 'service nginx restart', 'service apache2 restart'],
            'restart_php' => ['systemctl restart php-fpm', 'systemctl restart php8.3-fpm', 'service php-fpm restart', 'service php8.3-fpm restart'],
            'reboot' => ['systemctl reboot'],
            'shutdown' => ['systemctl poweroff'],
        ];

        $options = $commands[$action] ?? [];
        if ($options === []) {
            return ['ok' => false, 'message' => 'Unknown action'];
        }

        $command = $this->findWorkingCommand($options);
        if ($command === null) {
            return ['ok' => false, 'message' => 'System control commands are not available on this server.'];
        }

        $result = $this->runCommand($command);
        $status = $result['exit_code'] === 0 ? 'success' : 'failed';
        $this->appendHistory([
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $action,
            'status' => $status,
            'message' => ucfirst(str_replace('_', ' ', $action)) . ' completed',
            'detail' => $result['output'],
        ]);

        return [
            'ok' => $result['exit_code'] === 0,
            'message' => $result['exit_code'] === 0 ? ucfirst(str_replace('_', ' ', $action)) . ' executed.' : 'Action failed.',
            'output' => $result['output'],
        ];
    }

    public function getHistory(): array
    {
        if (! file_exists($this->historyPath)) {
            return [];
        }

        $json = file_get_contents($this->historyPath);
        if ($json === false || trim($json) === '') {
            return [];
        }

        $data = json_decode($json, true);
        return is_array($data) ? array_slice($data, -12) : [];
    }

    private function appendHistory(array $entry): void
    {
        $history = $this->getHistory();
        $history[] = $entry;
        file_put_contents($this->historyPath, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function safeCommand(string $command): string
    {
        $result = $this->runCommand($command);
        return $result['output'] ?: 'Unavailable';
    }

    private function runCommand(string $command): array
    {
        if (! function_exists('shell_exec')) {
            return ['exit_code' => 1, 'output' => ''];
        }

        $output = shell_exec($command) ?: '';
        $exitCode = 0;
        if ($output === '' && ! $this->commandExists($command)) {
            $exitCode = 127;
        }

        return [
            'exit_code' => $exitCode,
            'output' => trim((string) $output),
        ];
    }

    private function getHostname(): string
    {
        if (function_exists('gethostname')) {
            $hostname = gethostname();
            if ($hostname !== false) {
                return $hostname;
            }
        }

        $result = $this->safeCommand('hostname');
        return $result !== 'Unavailable' ? $result : 'Unknown';
    }

    private function getOsInfo(): string
    {
        if (function_exists('php_uname')) {
            return php_uname();
        }

        $result = $this->safeCommand('uname -a');
        return $result !== 'Unavailable' ? $result : 'Unknown';
    }

    private function getUptime(): string
    {
        $result = $this->safeCommand('uptime');
        if ($result !== 'Unavailable') {
            return $result;
        }

        // Fallback: read from /proc/uptime if available via PHP
        if (file_exists('/proc/uptime')) {
            $uptimeStr = @file_get_contents('/proc/uptime');
            if ($uptimeStr !== false) {
                $uptimeSeconds = (int) explode(' ', $uptimeStr)[0];
                $days = intdiv($uptimeSeconds, 86400);
                $hours = intdiv($uptimeSeconds % 86400, 3600);
                $minutes = intdiv($uptimeSeconds % 3600, 60);
                return "{$days} days, {$hours} hours, {$minutes} minutes";
            }
        }

        return 'Unavailable';
    }

    private function commandExists(string $command): bool
    {
        if (stripos($command, 'uname') !== false || stripos($command, 'hostname') !== false || stripos($command, 'uptime') !== false) {
            return true;
        }

        return false;
    }

    private function readCpuUsage(): ?float
    {
        // Try shell command first (if available)
        $result = $this->safeCommand('top -bn1 | grep "Cpu(s)" | tail -n 1');
        if ($result !== 'Unavailable' && trim($result) !== '') {
            if (preg_match('/(\d+(?:\.\d+)?)%us/', $result, $matches)) {
                return (float) $matches[1];
            }
        }

        // Fallback: read from /proc/stat if on Linux
        if (file_exists('/proc/stat')) {
            return $this->readCpuUsageFromProcStat();
        }

        // Fallback: use PHP getrusage if available
        if (function_exists('getrusage')) {
            return $this->readCpuUsageFromRusage();
        }

        return null;
    }

    private function readCpuUsageFromProcStat(): ?float
    {
        static $lastStat = null;
        static $lastTime = null;

        $statFile = @file_get_contents('/proc/stat');
        if ($statFile === false) {
            return null;
        }

        $lines = explode("\n", $statFile);
        $cpuLine = $lines[0];
        if (! str_starts_with($cpuLine, 'cpu ')) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($cpuLine));
        if (count($parts) < 5) {
            return null;
        }

        $user = (int) $parts[1];
        $nice = (int) $parts[2];
        $system = (int) $parts[3];
        $idle = (int) $parts[4];
        $iowait = isset($parts[5]) ? (int) $parts[5] : 0;

        $totalTime = microtime(true);
        $total = $user + $nice + $system + $idle + $iowait;
        $work = $user + $nice + $system;

        if ($lastStat === null || $lastTime === null) {
            $lastStat = ['total' => $total, 'work' => $work];
            $lastTime = $totalTime;
            return 0.0;
        }

        $diffTotal = $total - $lastStat['total'];
        $diffWork = $work - $lastStat['work'];

        $lastStat = ['total' => $total, 'work' => $work];
        $lastTime = $totalTime;

        if ($diffTotal <= 0) {
            return 0.0;
        }

        return min(100.0, round(($diffWork / $diffTotal) * 100, 2));
    }

    private function readCpuUsageFromRusage(): ?float
    {
        $rusage = getrusage();
        if (! is_array($rusage)) {
            return null;
        }

        $userTime = ($rusage['ru_utime.tv_sec'] ?? 0) + (($rusage['ru_utime.tv_usec'] ?? 0) / 1000000);
        $systemTime = ($rusage['ru_stime.tv_sec'] ?? 0) + (($rusage['ru_stime.tv_usec'] ?? 0) / 1000000);
        $totalTime = $userTime + $systemTime;

        return min(100.0, (float) ($totalTime * 100));
    }

    private function readMemoryUsage(): array
    {
        $result = $this->safeCommand('free -m');
        if ($result !== 'Unavailable' && trim($result) !== '') {
            $lines = preg_split('/\r\n|\n|\r/', $result);
            if (count($lines) >= 2) {
                $parts = preg_split('/\s+/', trim($lines[1]));
                if (count($parts) >= 3) {
                    return [
                        'total_mb' => (int) ($parts[1] ?? 0),
                        'used_mb' => (int) ($parts[2] ?? 0),
                        'free_mb' => (int) ($parts[3] ?? 0),
                    ];
                }
            }
        }

        // Fallback: use PHP memory functions
        if (function_exists('memory_get_usage') && function_exists('memory_get_peak_usage')) {
            $usage = memory_get_usage(true);
            $peak = memory_get_peak_usage(true);
            $limit = $this->parsePhpMemoryLimit();
            return [
                'total_mb' => (int) ($limit / 1024 / 1024),
                'used_mb' => (int) ($usage / 1024 / 1024),
                'free_mb' => (int) (($limit - $usage) / 1024 / 1024),
            ];
        }

        return ['total_mb' => null, 'used_mb' => null, 'free_mb' => null];
    }

    private function parsePhpMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return 2147483648; // Default 2GB
        }

        $unit = strtoupper(substr($limit, -1));
        $numeric = (int) rtrim($limit, 'KMGTP');

        return match ($unit) {
            'G' => $numeric * 1024 * 1024 * 1024,
            'M' => $numeric * 1024 * 1024,
            'K' => $numeric * 1024,
            default => $numeric,
        };
    }

    private function readDiskUsage(): array
    {
        $result = $this->safeCommand('df -h /');
        if ($result !== 'Unavailable' && trim($result) !== '') {
            $lines = preg_split('/\r\n|\n|\r/', $result);
            if (count($lines) >= 2) {
                $parts = preg_split('/\s+/', trim($lines[1]));
                if (count($parts) >= 6) {
                    return [
                        'total_gb' => $this->humanToGb((string) ($parts[1] ?? '0')),
                        'used_gb' => $this->humanToGb((string) ($parts[2] ?? '0')),
                        'free_gb' => $this->humanToGb((string) ($parts[3] ?? '0')),
                    ];
                }
            }
        }

        // Fallback: use PHP disk functions
        $rootPath = ROOTPATH;
        if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
            $total = disk_total_space($rootPath);
            $free = disk_free_space($rootPath);
            if ($total !== false && $free !== false) {
                return [
                    'total_gb' => round($total / 1024 / 1024 / 1024, 2),
                    'used_gb' => round(($total - $free) / 1024 / 1024 / 1024, 2),
                    'free_gb' => round($free / 1024 / 1024 / 1024, 2),
                ];
            }
        }

        return ['total_gb' => null, 'used_gb' => null, 'free_gb' => null];
    }

    private function humanToGb(string $value): ?float
    {
        if ($value === '') {
            return null;
        }

        $unit = strtolower(substr($value, -1));
        $numeric = (float) rtrim($value, 'KMGTP');

        return match ($unit) {
            't' => $numeric * 1024,
            'g' => $numeric,
            'm' => $numeric / 1024,
            'k' => $numeric / 1024 / 1024,
            default => $numeric,
        };
    }

    private function readNetworkStatus(): string
    {
        // Method 1: $_SERVER['SERVER_ADDR'] — set by Apache/Nginx when available
        if (!empty($_SERVER['SERVER_ADDR'])) {
            $ip = $_SERVER['SERVER_ADDR'];
            if (filter_var($ip, FILTER_VALIDATE_IP) && !$this->isLoopbackIp($ip)) {
                return $ip;
            }
        }

        // Method 2: Try /sbin/ip with absolute path (may not be in PATH)
        $result = $this->safeCommand('/sbin/ip -4 addr show scope global 2>/dev/null | grep -oP "inet \K[0-9.]+"');
        if ($result !== 'Unavailable' && trim($result) !== '') {
            $ips = preg_split('/\s+/', trim($result));
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP) && !$this->isLoopbackIp($ip)) {
                    return $ip;
                }
            }
        }

        // Method 3: Parse /proc/net/route to get gateway interface and its IP
        if (file_exists('/proc/net/route')) {
            $defaultIp = $this->getIpFromProcRoute();
            if ($defaultIp && filter_var($defaultIp, FILTER_VALIDATE_IP) && !$this->isLoopbackIp($defaultIp)) {
                return $defaultIp;
            }
        }

        // Method 4: hostname -I (simple space-separated list)
        $result = $this->safeCommand('hostname -I');
        if ($result !== 'Unavailable' && trim($result) !== '') {
            $ips = preg_split('/\s+/', trim($result));
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP) && !$this->isLoopbackIp($ip)) {
                    return $ip;
                }
            }
        }

        // Method 5: Try ifconfig output (older systems)
        $result = $this->safeCommand('ifconfig 2>/dev/null | grep -oP "inet \K[0-9.]+" | grep -v "^127\."');
        if ($result !== 'Unavailable' && trim($result) !== '') {
            $ips = preg_split('/\s+/', trim($result));
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        // Method 6: Socket-based approach — connect to external host to get local IP
        $localIp = $this->getLocalIpViaSocket();
        if ($localIp && filter_var($localIp, FILTER_VALIDATE_IP) && !$this->isLoopbackIp($localIp)) {
            return $localIp;
        }

        return 'Unavailable';
    }

    private function getIpFromProcRoute(): ?string
    {
        $lines = file('/proc/net/route', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return null;
        }

        // Find line with destination 00000000 (default route)
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 3 && $parts[1] === '00000000') {
                // parts[0] is interface name
                $ifname = $parts[0];
                // Get IP for this interface from /proc/net/arp or /proc/net/dev_snmp6
                $ip = $this->getIpForInterface($ifname);
                if ($ip) {
                    return $ip;
                }
            }
        }
        return null;
    }

    private function getIpForInterface(string $ifname): ?string
    {
        // Try to get IP by parsing /proc/net/dev output combined with IP resolution
        $result = $this->safeCommand("ip -4 addr show dev " . escapeshellarg($ifname) . " 2>/dev/null | grep -oP 'inet \K[0-9.]+'");
        if ($result !== 'Unavailable' && trim($result) !== '') {
            return trim(preg_split('/\s+/', trim($result))[0] ?? null);
        }
        return null;
    }

    private function getLocalIpViaSocket(): ?string
    {
        try {
            // Create socket and connect to public DNS to determine local IP
            $sock = @fsockopen('8.8.8.8', 53, $errno, $errstr, 2);
            if (!$sock) {
                return null;
            }

            $localName = @getsockname($sock);
            @fclose($sock);

            if ($localName) {
                // getsockname returns array like ['127.0.0.1', 53]
                return is_array($localName) ? ($localName[0] ?? null) : null;
            }
        } catch (\Throwable $e) {
            // Socket operation failed, ignore
        }
        return null;
    }

    private function isLoopbackIp(string $ip): bool
    {
        return strpos($ip, '127.') === 0 || $ip === '::1' || strpos($ip, '::ffff:127.') === 0;
    }

    private function readInternetStatus(): string
    {
        // Method 1: Try ping to Google DNS
        $result = $this->safeCommand('ping -c 1 -W 2 8.8.8.8 2>/dev/null');
        if ($result !== 'Unavailable' && trim($result) !== '' && strpos($result, '100% packet loss') === false && strpos($result, '100.0%') === false) {
            return 'Online';
        }

        // Method 2: Try DNS resolution using nslookup
        $result = $this->safeCommand('nslookup google.com 8.8.8.8 2>/dev/null | grep -i "name:"');
        if ($result !== 'Unavailable' && trim($result) !== '') {
            return 'Online';
        }

        // Method 3: Try dig (DNS query)
        $result = $this->safeCommand('dig @8.8.8.8 google.com +short 2>/dev/null');
        if ($result !== 'Unavailable' && trim($result) !== '') {
            return 'Online';
        }

        // Method 4: Try curl/wget to external site with timeout
        $result = $this->safeCommand('curl -s -m 3 -o /dev/null -w "%{http_code}" https://www.google.com 2>/dev/null');
        if ($result !== 'Unavailable' && trim($result) !== '' && intval($result) >= 200 && intval($result) < 400) {
            return 'Online';
        }

        // Method 5: Try wget HEAD request
        $result = $this->safeCommand('wget --spider -q -T 2 https://www.google.com 2>&1');
        if ($result !== 'Unavailable' && strpos($result, 'HTTP request sent, awaiting response') !== false) {
            return 'Online';
        }

        // Method 6: PHP fsockopen to Google DNS port 53
        $onlineViaSocket = $this->checkInternetViaSocket();
        if ($onlineViaSocket) {
            return 'Online';
        }

        // Method 7: PHP fsockopen to Cloudflare DNS
        $result = $this->safeCommand('curl -s -m 3 -o /dev/null -w "%{http_code}" https://1.1.1.1 2>/dev/null');
        if ($result !== 'Unavailable' && trim($result) !== '' && intval($result) >= 200 && intval($result) < 400) {
            return 'Online';
        }

        // Method 8: Try to resolve google.com using PHP's gethostbyname
        try {
            if (function_exists('gethostbyname')) {
                $ip = @gethostbyname('google.com');
                if ($ip !== 'google.com' && filter_var($ip, FILTER_VALIDATE_IP)) {
                    return 'Online';
                }
            }
        } catch (\Throwable $e) {
            // Ignore errors
        }

        return 'Offline';
    }

    private function checkInternetViaSocket(): bool
    {
        try {
            // Try connecting to Google DNS (8.8.8.8:53)
            $sock = @fsockopen('8.8.8.8', 53, $errno, $errstr, 3);
            if ($sock) {
                @fclose($sock);
                return true;
            }

            // Try Cloudflare DNS (1.1.1.1:53)
            $sock = @fsockopen('1.1.1.1', 53, $errno, $errstr, 3);
            if ($sock) {
                @fclose($sock);
                return true;
            }

            // Try Quad9 DNS (9.9.9.9:53)
            $sock = @fsockopen('9.9.9.9', 53, $errno, $errstr, 3);
            if ($sock) {
                @fclose($sock);
                return true;
            }
        } catch (\Throwable $e) {
            // Ignore socket errors
        }

        return false;
    }

    private function readServiceStatus(): array
    {
        $services = ['nginx', 'apache2', 'php-fpm', 'mariadb', 'mysql', 'sshd'];
        $result = [];
        foreach ($services as $service) {
            $command = 'systemctl is-active ' . escapeshellarg($service) . ' 2>/dev/null';
            $output = $this->safeCommand($command);
            if ($output === 'Unavailable' || trim($output) === '') {
                continue;
            }

            $result[$service] = $output;
        }

        return $result;
    }

    private function readRaidStatus(): string
    {
        if (file_exists('/proc/mdstat')) {
            $content = file_get_contents('/proc/mdstat');
            if (is_string($content) && trim($content) !== '') {
                return trim($content);
            }
        }

        return 'Not detected';
    }

    private function findWorkingCommand(array $options): ?string
    {
        foreach ($options as $command) {
            if ($this->commandAvailable($command)) {
                return $command;
            }
        }

        return null;
    }

    private function commandAvailable(string $command): bool
    {
        if (! function_exists('shell_exec')) {
            return false;
        }

        if (str_contains($command, 'systemctl') || str_contains($command, 'service') || str_contains($command, 'reboot') || str_contains($command, 'poweroff')) {
            return true;
        }

        $binary = trim((string) preg_split('/\s+/', trim($command))[0]);
        $test = shell_exec('command -v ' . escapeshellarg($binary) . ' 2>/dev/null');
        return trim((string) $test) !== '';
    }

    private function getLatestHistoryEntry(): ?array
    {
        $history = $this->getHistory();
        return $history === [] ? null : end($history);
    }
}

<?php

namespace App\Libraries;

use RuntimeException;

class SystemOperations
{
    private string $historyPath;
    private string $deployedShaPath;
    private string $updateCachePath;

    private const GITHUB_OWNER  = 'infodevsofttech-admin';
    private const GITHUB_REPO   = 'devsofttech_hms_ci4';
    private const GITHUB_BRANCH = 'main';
    private const UPDATE_CACHE_TTL = 600; // 10 minutes

    public function __construct()
    {
        $this->historyPath    = WRITEPATH . 'system_update_history.json';
        $this->deployedShaPath = WRITEPATH . 'deployed_commit.txt';
        $this->updateCachePath = WRITEPATH . 'github_update_cache.json';
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
        $status['raid']        = $this->readRaidStatus();
        $status['last_update']  = $this->getLatestHistoryEntry();
        $status['update_check'] = $this->checkForUpdates();

        return $status;
    }

    public function runUpdateDirect(): array
    {
        $repoPath = ROOTPATH;
        $logFile  = WRITEPATH . 'system_update.log';

        // Extend PHP time limit for large ZIP download + file sync
        @set_time_limit(300);

        // Remove any stale 'running' entries from previous failed attempts
        $this->purgeStaleRunning();

        try {
            $zipUrl  = "https://github.com/" . self::GITHUB_OWNER . "/" . self::GITHUB_REPO . "/archive/refs/heads/" . self::GITHUB_BRANCH . ".zip";
            $tempDir = sys_get_temp_dir();
            $zipFile = $tempDir . '/hms_update_' . time() . '.zip';

            // Fetch latest commit info for tracking (fail-safe, non-blocking)
            $latestInfo = $this->fetchLatestCommitInfo();

            file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] Downloading: {$zipUrl}\n", FILE_APPEND);

            $zipContent = @file_get_contents($zipUrl);
            if ($zipContent === false) {
                throw new \Exception('Failed to download from GitHub. Check network connectivity.');
            }
            if (!file_put_contents($zipFile, $zipContent)) {
                throw new \Exception('Failed to write ZIP to temp directory.');
            }

            $zipSize = number_format(filesize($zipFile) / 1024, 1) . ' KB';
            file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] Downloaded ZIP: {$zipSize}\n", FILE_APPEND);

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

            $dirs = array_diff(scandir($extractDir), ['.', '..']);
            if (empty($dirs)) {
                throw new \Exception('Downloaded ZIP appears to be empty.');
            }
            $extractedRepoDir = $extractDir . '/' . reset($dirs);

            $this->syncFiles($extractedRepoDir, $repoPath, $logFile);
            $this->deleteDirectory($extractDir);
            @unlink($zipFile);

            $successMsg = 'HMS updated successfully from GitHub (direct deployment)';
            $shaLine = !empty($latestInfo['sha']) ? "\nCommit: " . substr($latestInfo['sha'], 0, 7) . ($latestInfo['message'] ? ' — ' . $latestInfo['message'] : '') : '';
            $this->appendHistory([
                'timestamp' => date('Y-m-d H:i:s'),
                'type'      => 'update',
                'status'    => 'success',
                'message'   => $successMsg,
                'detail'    => "Downloaded: {$zipSize}\nSource: {$zipUrl}{$shaLine}\nCompleted: " . date('Y-m-d H:i:s'),
            ]);
            file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] SUCCESS: {$successMsg}\n", FILE_APPEND);

            // Save deployed SHA so update checker knows what version is live
            if (!empty($latestInfo['sha'])) {
                file_put_contents($this->deployedShaPath, $latestInfo['sha']);
            }
            @unlink($this->updateCachePath); // invalidate cache so next check is fresh

            return ['ok' => true, 'message' => $successMsg];

        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            $this->appendHistory([
                'timestamp' => date('Y-m-d H:i:s'),
                'type'      => 'update',
                'status'    => 'failed',
                'message'   => 'HMS update failed',
                'detail'    => $errorMsg,
            ]);
            file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] FAILED: {$errorMsg}\n", FILE_APPEND);
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

    public function checkForUpdates(): array
    {
        $result = [
            'has_update'      => false,
            'deployed_sha'    => '',
            'latest_sha'      => '',
            'latest_message'  => '',
            'latest_date'     => '',
            'error'           => '',
        ];

        // Read cached result if fresh enough
        if (file_exists($this->updateCachePath)) {
            $cached = @json_decode((string) file_get_contents($this->updateCachePath), true);
            if (is_array($cached) && isset($cached['fetched_at']) && (time() - (int)$cached['fetched_at']) < self::UPDATE_CACHE_TTL) {
                $result['latest_sha']     = $cached['sha'] ?? '';
                $result['latest_message'] = $cached['message'] ?? '';
                $result['latest_date']    = $cached['date'] ?? '';
                $result['deployed_sha']   = $this->getDeployedSha();
                $result['has_update']     = $result['latest_sha'] !== '' && $result['latest_sha'] !== $result['deployed_sha'];
                return $result;
            }
        }

        $info = $this->fetchLatestCommitInfo();
        if (!empty($info['error'])) {
            $result['error'] = $info['error'];
            return $result;
        }

        // Cache the result
        file_put_contents($this->updateCachePath, json_encode([
            'fetched_at' => time(),
            'sha'        => $info['sha'],
            'message'    => $info['message'],
            'date'       => $info['date'],
        ]));

        $result['latest_sha']     = $info['sha'];
        $result['latest_message'] = $info['message'];
        $result['latest_date']    = $info['date'];
        $result['deployed_sha']   = $this->getDeployedSha();
        $result['has_update']     = $result['latest_sha'] !== '' && $result['latest_sha'] !== $result['deployed_sha'];

        return $result;
    }

    private function fetchLatestCommitInfo(): array
    {
        $apiUrl = "https://api.github.com/repos/" . self::GITHUB_OWNER . "/" . self::GITHUB_REPO . "/commits/" . self::GITHUB_BRANCH;
        $ctx = stream_context_create(['http' => [
            'timeout' => 8,
            'header'  => "User-Agent: HMS-CI4-Updater/1.0\r\nAccept: application/vnd.github.v3+json\r\n",
            'ignore_errors' => true,
        ]]);
        $body = @file_get_contents($apiUrl, false, $ctx);
        if ($body === false) {
            return ['sha' => '', 'message' => '', 'date' => '', 'error' => 'Could not reach GitHub API'];
        }
        $data = json_decode($body, true);
        if (!is_array($data) || empty($data['sha'])) {
            return ['sha' => '', 'message' => '', 'date' => '', 'error' => 'Unexpected GitHub API response'];
        }
        $message = substr((string)($data['commit']['message'] ?? ''), 0, 72);
        $message = strtok($message, "\n"); // first line only
        return [
            'sha'     => (string)$data['sha'],
            'message' => (string)$message,
            'date'    => (string)($data['commit']['committer']['date'] ?? ''),
            'error'   => '',
        ];
    }

    private function getDeployedSha(): string
    {
        if (!file_exists($this->deployedShaPath)) return '';
        return trim((string)@file_get_contents($this->deployedShaPath));
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
        return is_array($data) ? array_slice($data, -20) : [];
    }

    private function purgeStaleRunning(): void
    {
        if (!file_exists($this->historyPath)) return;
        $json = @file_get_contents($this->historyPath);
        $data = is_string($json) ? json_decode($json, true) : [];
        if (!is_array($data)) return;
        // Mark any existing 'running' entries as 'timeout' so they don't pile up
        $changed = false;
        foreach ($data as &$entry) {
            if (($entry['status'] ?? '') === 'running') {
                $entry['status'] = 'timeout';
                $entry['detail'] = 'Process did not complete (PHP timeout or server error).';
                $changed = true;
            }
        }
        unset($entry);
        if ($changed) {
            file_put_contents($this->historyPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
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

    private function readNetworkStatus(): array
    {
        $interfaces = [];

        // Parse /proc/net/fib_trie (most reliable on Linux — no shell needed)
        if (file_exists('/proc/net/fib_trie')) {
            $this->parseInterfacesFromIp($interfaces);
        }

        // Fallback: ifconfig
        if (empty($interfaces)) {
            $this->parseInterfacesFromIfconfig($interfaces);
        }

        // Fallback: hostname -I (no interface names available)
        if (empty($interfaces)) {
            $result = $this->safeCommand('hostname -I');
            if ($result !== 'Unavailable') {
                foreach (preg_split('/\s+/', trim($result)) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP) && !$this->isLoopbackIp($ip)) {
                        $interfaces[] = ['name' => '?', 'ip' => $ip, 'type' => $this->guessIfaceType('?', $ip)];
                    }
                }
            }
        }

        // Ultimate fallback: SERVER_ADDR
        if (empty($interfaces) && !empty($_SERVER['SERVER_ADDR'])) {
            $ip = $_SERVER['SERVER_ADDR'];
            if (filter_var($ip, FILTER_VALIDATE_IP) && !$this->isLoopbackIp($ip)) {
                $interfaces[] = ['name' => 'server', 'ip' => $ip, 'type' => 'lan'];
            }
        }

        return $interfaces;
    }

    private function parseInterfacesFromIp(array &$interfaces): void
    {
        $result = $this->safeCommand('/sbin/ip -4 addr show 2>/dev/null');
        if ($result === 'Unavailable' || trim($result) === '') {
            $result = $this->safeCommand('ip -4 addr show 2>/dev/null');
        }
        if ($result === 'Unavailable') return;

        $currentIface = null;
        foreach (explode("\n", $result) as $line) {
            if (preg_match('/^\d+:\s+(\S+):/', $line, $m)) {
                $currentIface = rtrim($m[1], ':');
            } elseif ($currentIface && preg_match('/inet\s+(\d+\.\d+\.\d+\.\d+)/', $line, $m)) {
                $ip = $m[1];
                if (!filter_var($ip, FILTER_VALIDATE_IP) || $this->isLoopbackIp($ip)) continue;
                $interfaces[] = [
                    'name' => $currentIface,
                    'ip'   => $ip,
                    'type' => $this->guessIfaceType($currentIface, $ip),
                ];
            }
        }
    }

    private function parseInterfacesFromIfconfig(array &$interfaces): void
    {
        $result = $this->safeCommand('ifconfig 2>/dev/null');
        if ($result === 'Unavailable') return;

        $currentIface = null;
        foreach (explode("\n", $result) as $line) {
            if (preg_match('/^(\S+):?\s/', $line, $m)) {
                $currentIface = rtrim($m[1], ':');
            } elseif ($currentIface && preg_match('/inet\s+(?:addr:)?(\d+\.\d+\.\d+\.\d+)/', $line, $m)) {
                $ip = $m[1];
                if (!filter_var($ip, FILTER_VALIDATE_IP) || $this->isLoopbackIp($ip)) continue;
                $interfaces[] = [
                    'name' => $currentIface,
                    'ip'   => $ip,
                    'type' => $this->guessIfaceType($currentIface, $ip),
                ];
            }
        }
    }

    // Classify interface as lan/vpn/public based on name and IP range
    private function guessIfaceType(string $name, string $ip): string
    {
        $name = strtolower($name);
        if (str_starts_with($name, 'wg') || str_starts_with($name, 'tun') || str_starts_with($name, 'tap') || str_starts_with($name, 'vpn')) {
            return 'vpn';
        }
        // RFC1918 private ranges
        if (str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.') || preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $ip)) {
            return 'lan';
        }
        return 'public';
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

    private function readRaidStatus(): array
    {
        if (!file_exists('/proc/mdstat')) {
            return [];
        }

        $content = @file_get_contents('/proc/mdstat');
        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        $arrays = [];
        $lines = explode("\n", $content);
        $i = 0;

        while ($i < count($lines)) {
            $line = trim($lines[$i]);

            // Match array definition: "md0 : active raid1 sda1[0] sdb1[1]"
            if (preg_match('/^(md\d+)\s*:\s*(\w+)\s+(\w+)\s+(.+)$/', $line, $m)) {
                $name   = $m[1];
                $state  = $m[2]; // active / inactive
                $level  = $m[3]; // raid1, raid5, etc.
                $devStr = $m[4];

                // Parse devices: sda1[0], sdb1[1], sdc1[2](F) etc.
                preg_match_all('/(\w+)\[(\d+)\](\(F\)|\(S\))?/', $devStr, $dm);
                $devices = [];
                foreach ($dm[1] as $k => $dev) {
                    $flag = $dm[3][$k] ?? '';
                    $devices[] = [
                        'name'   => $dev,
                        'index'  => (int) $dm[2][$k],
                        'failed' => $flag === '(F)',
                        'spare'  => $flag === '(S)',
                    ];
                }

                // Next line has block count and health: "      1953382400 blocks super 1.2 [2/2] [UU]"
                $health    = 'Unknown';
                $total     = 0;
                $active    = 0;
                $healthStr = '';
                if (isset($lines[$i + 1])) {
                    $next = trim($lines[$i + 1]);
                    // [total/active]
                    if (preg_match('/\[(\d+)\/(\d+)\]/', $next, $nm)) {
                        $total  = (int) $nm[1];
                        $active = (int) $nm[2];
                    }
                    // [UU] or [U_UU] style
                    if (preg_match('/\[([U_]+)\]/', $next, $hm)) {
                        $healthStr = $hm[1];
                    }
                    // Rebuild progress line (line after next)
                    $rebuildProgress = null;
                    if (isset($lines[$i + 2]) && preg_match('/(\d+\.\d+)%/', $lines[$i + 2], $pm)) {
                        $rebuildProgress = (float) $pm[1];
                    }
                }

                $failedCount = substr_count($healthStr, '_');
                if ($state !== 'active') {
                    $health = 'Inactive';
                } elseif ($failedCount === 0 && $active === $total && $total > 0) {
                    $health = 'Healthy';
                } elseif ($failedCount > 0) {
                    $health = 'Degraded';
                } elseif ($total === 0) {
                    $health = 'Unknown';
                } else {
                    $health = 'Rebuilding';
                }

                $arrays[] = [
                    'name'             => $name,
                    'state'            => $state,
                    'level'            => strtoupper($level),
                    'health'           => $health,
                    'health_str'       => $healthStr,
                    'total_drives'     => $total,
                    'active_drives'    => $active,
                    'failed_drives'    => $failedCount,
                    'devices'          => $devices,
                    'rebuild_progress' => $rebuildProgress ?? null,
                ];
            }
            $i++;
        }

        return $arrays;
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

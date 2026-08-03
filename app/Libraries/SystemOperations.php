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

    public function runUpdate(): array
    {
        $repoPath = ROOTPATH;
        $logFile = WRITEPATH . 'system_update.log';
        $startedAt = date('Y-m-d H:i:s');
        
        $entry = [
            'timestamp' => $startedAt,
            'type' => 'update',
            'status' => 'running',
            'message' => 'HMS update started (git pull)',
        ];
        $this->appendHistory($entry);

        // Run git pull with timeout protection
        $command = 'cd ' . escapeshellarg($repoPath) . ' && timeout 60 git pull origin main 2>&1';
        $result = $this->runCommand($command);
        $output = $result['output'];

        // Check for timeout
        if (str_contains($output, 'Terminated') || $result['exit_code'] === 124) {
            $entry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'type' => 'update',
                'status' => 'timeout',
                'message' => 'HMS update timed out (60 seconds)',
                'detail' => 'The git pull operation took too long. Check network connectivity and try again.',
            ];
            $this->appendHistory($entry);
            file_put_contents($logFile, 'TIMEOUT: ' . $output . PHP_EOL, FILE_APPEND);

            return ['ok' => false, 'message' => 'Update timed out after 60 seconds. Please check network and try again.', 'output' => $output];
        }

        if ($result['exit_code'] !== 0) {
            $entry = [
                'timestamp' => date('Y-m-d H:i:s'),
                'type' => 'update',
                'status' => 'failed',
                'message' => 'HMS update failed',
                'detail' => $output,
            ];
            $this->appendHistory($entry);
            file_put_contents($logFile, 'FAILED: ' . $output . PHP_EOL, FILE_APPEND);

            return ['ok' => false, 'message' => 'Update failed. Check logs for details.', 'output' => $output];
        }

        // Success
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'update',
            'status' => 'success',
            'message' => 'HMS update completed',
            'detail' => $output,
        ];
        $this->appendHistory($entry);
        file_put_contents($logFile, 'SUCCESS: ' . $output . PHP_EOL, FILE_APPEND);

        return ['ok' => true, 'message' => 'HMS update completed successfully', 'output' => $output];
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
        $result = $this->safeCommand('ping -c 2 8.8.8.8 2>/dev/null | tail -n 1');
        if ($result === 'Unavailable' || trim($result) === '') {
            return 'Unavailable';
        }

        return strpos($result, '100% packet loss') !== false ? 'Offline' : 'Online';
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

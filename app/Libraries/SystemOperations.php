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
            'hostname' => $this->safeCommand('hostname'),
            'os' => $this->safeCommand('uname -a'),
            'uptime' => $this->safeCommand('uptime'),
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
            'message' => 'Update started',
        ];
        $this->appendHistory($entry);

        $commands = [
            'cd ' . escapeshellarg($repoPath) . ' && git pull origin main 2>&1',
            'cd ' . escapeshellarg($repoPath) . ' && php spark migrate --namespace App 2>&1',
        ];

        $output = [];
        foreach ($commands as $command) {
            $result = $this->runCommand($command);
            $output[] = $result['output'];
            if ($result['exit_code'] !== 0) {
                $entry = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'type' => 'update',
                    'status' => 'failed',
                    'message' => 'Update failed',
                    'detail' => trim(implode("\n", $output)),
                ];
                $this->appendHistory($entry);
                file_put_contents($logFile, trim(implode("\n", $output)) . PHP_EOL, FILE_APPEND);

                return ['ok' => false, 'message' => 'Update failed', 'output' => trim(implode("\n", $output))];
            }
        }

        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => 'update',
            'status' => 'success',
            'message' => 'Update completed',
            'detail' => trim(implode("\n", $output)),
        ];
        $this->appendHistory($entry);
        file_put_contents($logFile, trim(implode("\n", $output)) . PHP_EOL, FILE_APPEND);

        return ['ok' => true, 'message' => 'Update completed successfully', 'output' => trim(implode("\n", $output))];
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
            return ['exit_code' => 1, 'output' => 'shell_exec is not available'];
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

    private function commandExists(string $command): bool
    {
        if (stripos($command, 'uname') !== false || stripos($command, 'hostname') !== false || stripos($command, 'uptime') !== false) {
            return true;
        }

        return false;
    }

    private function readCpuUsage(): ?float
    {
        $result = $this->safeCommand('top -bn1 | grep "Cpu(s)" | tail -n 1');
        if ($result === 'Unavailable') {
            return null;
        }

        if (preg_match('/(\d+(?:\.\d+)?)%us/', $result, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    private function readMemoryUsage(): array
    {
        $result = $this->safeCommand('free -m');
        if ($result === 'Unavailable') {
            return ['total_mb' => null, 'used_mb' => null, 'free_mb' => null];
        }

        $lines = preg_split('/\r\n|\n|\r/', $result);
        if (count($lines) < 2) {
            return ['total_mb' => null, 'used_mb' => null, 'free_mb' => null];
        }

        $parts = preg_split('/\s+/', trim($lines[1]));
        if (count($parts) < 3) {
            return ['total_mb' => null, 'used_mb' => null, 'free_mb' => null];
        }

        return [
            'total_mb' => (int) ($parts[1] ?? 0),
            'used_mb' => (int) ($parts[2] ?? 0),
            'free_mb' => (int) ($parts[3] ?? 0),
        ];
    }

    private function readDiskUsage(): array
    {
        $result = $this->safeCommand('df -h /');
        if ($result === 'Unavailable') {
            return ['total_gb' => null, 'used_gb' => null, 'free_gb' => null];
        }

        $lines = preg_split('/\r\n|\n|\r/', $result);
        if (count($lines) < 2) {
            return ['total_gb' => null, 'used_gb' => null, 'free_gb' => null];
        }

        $parts = preg_split('/\s+/', trim($lines[1]));
        if (count($parts) < 6) {
            return ['total_gb' => null, 'used_gb' => null, 'free_gb' => null];
        }

        return [
            'total_gb' => $this->humanToGb((string) ($parts[1] ?? '0')),
            'used_gb' => $this->humanToGb((string) ($parts[2] ?? '0')),
            'free_gb' => $this->humanToGb((string) ($parts[3] ?? '0')),
        ];
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
        $result = $this->safeCommand('ip addr 2>/dev/null | head -n 5');
        if ($result === 'Unavailable' || trim($result) === '') {
            return 'Unavailable';
        }

        return 'Detected';
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

<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\IncomingRequest;

class UserSessionRegistry
{
    public const SESSION_TOKEN_KEY = 'hms_active_session_token';
    private const TOUCH_KEY = 'hms_active_session_touched_at';
    private const ONLINE_SECONDS = 120;

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    public function activate(int $userId): string
    {
        $this->ensureTable();
        $token = bin2hex(random_bytes(32));
        $now = date('Y-m-d H:i:s');

        $this->db->transStart();
        $this->db->table('user_active_sessions')
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->update([
                'is_active' => 0,
                'logout_at' => $now,
                'revoked_reason' => 'signed_in_elsewhere',
            ]);

        $request = service('request');
        $this->db->table('user_active_sessions')->insert([
            'user_id' => $userId,
            'session_token' => $token,
            'session_id_hash' => hash('sha256', session_id()),
            'ip_address' => $request instanceof IncomingRequest ? $request->getIPAddress() : null,
            'user_agent' => $request instanceof IncomingRequest ? substr($request->getUserAgent()->getAgentString(), 0, 255) : null,
            'login_at' => $now,
            'last_activity' => $now,
            'is_active' => 1,
        ]);
        $this->db->transComplete();

        session()->set(self::SESSION_TOKEN_KEY, $token);
        session()->set(self::TOUCH_KEY, time());

        return $token;
    }

    public function ensureCurrent(int $userId): array
    {
        $this->ensureTable();
        $token = trim((string) session()->get(self::SESSION_TOKEN_KEY));
        if ($token === '') {
            $this->activate($userId);
            return ['active' => true, 'reason' => null];
        }

        $row = $this->db->table('user_active_sessions')
            ->select('is_active,revoked_reason')
            ->where('user_id', $userId)
            ->where('session_token', $token)
            ->get(1)
            ->getRowArray();

        if (empty($row) || (int) ($row['is_active'] ?? 0) !== 1) {
            return [
                'active' => false,
                'reason' => (string) ($row['revoked_reason'] ?? 'session_expired'),
            ];
        }

        $lastTouch = (int) session()->get(self::TOUCH_KEY);
        if ($lastTouch <= time() - 30) {
            $this->db->table('user_active_sessions')
                ->where('user_id', $userId)
                ->where('session_token', $token)
                ->where('is_active', 1)
                ->update(['last_activity' => date('Y-m-d H:i:s')]);
            session()->set(self::TOUCH_KEY, time());
        }

        return ['active' => true, 'reason' => null];
    }

    public function deactivateCurrent(string $reason = 'logout'): void
    {
        if (! $this->db->tableExists('user_active_sessions')) {
            return;
        }

        $token = trim((string) session()->get(self::SESSION_TOKEN_KEY));
        if ($token !== '') {
            $this->db->table('user_active_sessions')
                ->where('session_token', $token)
                ->where('is_active', 1)
                ->update([
                    'is_active' => 0,
                    'logout_at' => date('Y-m-d H:i:s'),
                    'revoked_reason' => $reason,
                ]);
        }
    }

    public function revokeUser(int $userId, int $revokedBy, string $reason = 'admin_force_logout'): int
    {
        $this->ensureTable();
        $this->db->table('user_active_sessions')
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->update([
                'is_active' => 0,
                'logout_at' => date('Y-m-d H:i:s'),
                'revoked_reason' => $reason,
                'revoked_by' => $revokedBy,
            ]);

        return $this->db->affectedRows();
    }

    public function onlineUsers(): array
    {
        $this->ensureTable();
        $cutoff = date('Y-m-d H:i:s', time() - self::ONLINE_SECONDS);

        return $this->db->table('user_active_sessions s')
            ->select('s.id,s.user_id,s.ip_address,s.user_agent,s.login_at,s.last_activity,u.username,i.extra')
            ->join('users u', 'u.id = s.user_id', 'inner')
            ->join('auth_identities i', "i.user_id = u.id AND i.type = 'email_password'", 'left')
            ->where('s.is_active', 1)
            ->where('s.last_activity >=', $cutoff)
            ->orderBy('s.last_activity', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function cleanup(): void
    {
        if (! $this->db->tableExists('user_active_sessions')) {
            return;
        }

        $this->db->table('user_active_sessions')
            ->where('is_active', 1)
            ->where('last_activity <', date('Y-m-d H:i:s', time() - 7200))
            ->update([
                'is_active' => 0,
                'logout_at' => date('Y-m-d H:i:s'),
                'revoked_reason' => 'expired',
            ]);
    }

    private function ensureTable(): void
    {
        if ($this->db->tableExists('user_active_sessions')) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS user_active_sessions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            session_token CHAR(64) NOT NULL,
            session_id_hash CHAR(64) NOT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            login_at DATETIME NOT NULL,
            last_activity DATETIME NOT NULL,
            logout_at DATETIME NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            revoked_reason VARCHAR(60) NULL,
            revoked_by INT UNSIGNED NULL,
            UNIQUE KEY uk_user_active_session_token (session_token),
            KEY idx_user_active_sessions_user (user_id,is_active),
            KEY idx_user_active_sessions_online (is_active,last_activity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
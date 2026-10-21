<?php

declare(strict_types=1);

namespace App\Authentication\Authenticators;

use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Authentication\Passwords;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Result;

class LegacySafeSession extends Session
{
    /**
     * Dummy bcrypt hash with cost 10 used to equalize execution timing
     * when a username is not found in the database.
     */
    private const DUMMY_HASH = '$2y$10$cfqH24k.mHkYnkyj5jT4k.UvY6oQzU/80pBf1K5k6v78d78f99e4K';

    public function completeLogin(User $user): void
    {
        parent::completeLogin($user);
        (new \App\Libraries\UserSessionRegistry())->activate((int) $user->id);
    }

    public function logout(): void
    {
        (new \App\Libraries\UserSessionRegistry())->deactivateCurrent();
        parent::logout();
    }

    /**
     * @phpstan-param array{email?: string, username?: string, password?: string} $credentials
     */
    public function check(array $credentials): Result
    {
        if (empty($credentials['password']) || count($credentials) < 2) {
            return new Result([
                'success' => false,
                'reason'  => lang('Auth.badAttempt'),
            ]);
        }

        $givenPassword = (string) $credentials['password'];
        unset($credentials['password']);

        /** @var Passwords $passwords */
        $passwords = service('passwords');

        $user = $this->provider->findByCredentials($credentials);

        if ($user === null) {
            // Mitigate user enumeration timing attack by performing a dummy hash verification
            $passwords->verify($givenPassword, self::DUMMY_HASH);

            return new Result([
                'success' => false,
                'reason'  => lang('Auth.badAttempt'),
            ]);
        }

        $hash = $user->password_hash ?? null;
        if (!is_string($hash) || trim($hash) === '') {
            $passwords->verify($givenPassword, self::DUMMY_HASH);

            return new Result([
                'success' => false,
                'reason'  => lang('Auth.badAttempt'),
            ]);
        }

        if (!$passwords->verify($givenPassword, $hash)) {
            return new Result([
                'success' => false,
                'reason'  => lang('Auth.badAttempt'),
            ]);
        }

        if ($passwords->needsRehash($hash)) {
            $user->password_hash = $passwords->hash($givenPassword);
            $this->provider->save($user);
        }

        return new Result([
            'success'   => true,
            'extraInfo' => $user,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Authentication\Authenticators;

use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Authentication\Passwords;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Result;

class LegacySafeSession extends Session
{
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

        $user = $this->provider->findByCredentials($credentials);

        if ($user === null) {
            return new Result([
                'success' => false,
                'reason'  => lang('Auth.badAttempt'),
            ]);
        }

        $hash = $user->password_hash ?? null;
        if (!is_string($hash) || trim($hash) === '') {
            return new Result([
                'success' => false,
                'reason'  => lang('Auth.invalidPassword'),
            ]);
        }

        /** @var Passwords $passwords */
        $passwords = service('passwords');

        if (!$passwords->verify($givenPassword, $hash)) {
            return new Result([
                'success' => false,
                'reason'  => lang('Auth.invalidPassword'),
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

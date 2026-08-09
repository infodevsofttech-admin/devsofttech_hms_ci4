<?php

namespace App\Controllers;

class AuthSession extends BaseController
{
    public function status()
    {
        if (! function_exists('auth') || ! auth()->loggedIn()) {
            return $this->response->setStatusCode(401)->setJSON([
                'active' => false,
                'login_url' => base_url('login'),
            ]);
        }

        return $this->response->setJSON([
            'active' => true,
            'checked_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
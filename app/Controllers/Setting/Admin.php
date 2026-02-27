<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;

class Admin extends BaseController
{
    public function index(): string
    {
        return view('Setting/Admin/index');
    }
}

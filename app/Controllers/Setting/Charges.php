<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;

class Charges extends BaseController
{
    public function index(): string
    {
        return view('Setting/Charges/index');
    }
}

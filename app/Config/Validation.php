<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;
use App\Validation\AbdmRules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
        AbdmRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    /**
     * Login validation rules for Shield.
     * Overrides the default email-based login rules.
     *
     * @var array<string, array<string, array<int, string>|string>>
     */
    public array $login = [
        'username' => [
            'label' => 'Auth.username',
            'rules' => [
                'required',
                'max_length[30]',
                'min_length[3]',
                'regex_match[/\A[a-zA-Z0-9\.]+\z/]',
            ],
        ],
        'password' => [
            'label'  => 'Auth.password',
            'rules'  => ['required', 'max_byte[255]'],
            'errors' => [
                'max_byte' => 'Auth.errorPasswordTooLongBytes',
            ],
        ],
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    /**
     * ABDM ABHA-related validation profiles.
     *
     * @var array<string, array<string, string>>
     */
    public array $abhaAddress = [
        'abha_address' => [
            'label' => 'ABHA Address',
            'rules' => 'required|valid_abha_address',
        ],
    ];
}

<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter Shield.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * --------------------------------------------------------------------
     * Default Group
     * --------------------------------------------------------------------
     * The group that a newly registered user is added to.
     */
    public string $defaultGroup = 'user';

    /**
     * --------------------------------------------------------------------
     * Groups
     * --------------------------------------------------------------------
     * An associative array of the available groups in the system, where the keys
     * are the group names and the values are arrays of the group info.
     *
     * Whatever value you assign as the key will be used to refer to the group
     * when using functions such as:
     *      $user->addGroup('superadmin');
     *
     * @var array<string, array<string, string>>
     *
     * @see https://codeigniter4.github.io/shield/quick_start_guide/using_authorization/#change-available-groups for more info
     */
    public array $groups = [
        'superadmin' => [
            'title'       => 'Super Admin',
            'description' => 'Complete control of the site.',
        ],
        'admin' => [
            'title'       => 'Admin',
            'description' => 'Day to day administrators of the site.',
        ],
        'developer' => [
            'title'       => 'Developer',
            'description' => 'Site programmers.',
        ],
        'user' => [
            'title'       => 'User',
            'description' => 'General users of the site. Often customers.',
        ],
        'beta' => [
            'title'       => 'Beta User',
            'description' => 'Has access to beta-level features.',
        ],
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions
     * --------------------------------------------------------------------
     * The available permissions in the system.
     *
     * If a permission is not listed here it cannot be used.
     */
    public array $permissions = [
        'admin.access'        => 'Can access the sites admin area',
        'admin.settings'      => 'Can access the main site settings',
        'users.manage-admins' => 'Can manage other admins',
        'users.create'        => 'Can create new non-admin users',
        'users.edit'          => 'Can edit existing non-admin users',
        'users.delete'        => 'Can delete existing non-admin users',
        'beta.access'         => 'Can access beta-level features',
        'billing.access'      => 'Can access billing module',
        'opd.doctor-panel.access' => 'Can access OPD doctor work panel (doctor-only)',
        
        'billing.opd.edit'    => 'Can edit OPD registrations',
        'billing.opd.pay'     => 'Can confirm OPD payments',
        'billing.charges.view' => 'Can view charges invoices',
        'billing.charges.edit' => 'Can edit charges invoices',
        'billing.charges.date-edit' => 'Can edit charges invoice date',
        'billing.charges.pay' => 'Can confirm charges payments',
        'billing.charges.cancel' => 'Can cancel charges invoices',
        'billing.charges.correct' => 'Can apply charges corrections',
        'billing.ipd.access'  => 'Can access IPD billing module',
        'billing.ipd.current-admission' => 'Can view IPD current admissions',
        'billing.ipd.invoice' => 'Can view IPD invoice list',
        'billing.ipd.cash-balance' => 'Can view IPD cash balance report',
        'billing.ipd.export'  => 'Can export IPD cash balance report',
        'finance.access'      => 'Can access finance module',
        'finance.vendor.manage' => 'Can manage finance vendors',
        'finance.po.manage' => 'Can manage purchase orders',
        'finance.grn.manage' => 'Can manage GRN entries',
        'finance.invoice.manage' => 'Can manage vendor invoices',
        'finance.cash.manage' => 'Can manage cash collection and disbursement',
        'finance.doctor_payout.manage' => 'Can manage doctor payout workflow',
        'finance.bank_deposit.manage' => 'Can manage bank deposit register',
        'finance.compliance.view' => 'Can view finance compliance report',
        'diagnosis.access'    => 'Can access diagnosis module',
        'diagnosis.report.view' => 'Can view diagnosis reports',
        'doctor_work.access'  => 'Can access doctor work module',
        'doctor_work.appointment.view' => 'Can view OPD appointment list',
        'doctor_work.rx_group.manage' => 'Can manage Rx group panel',
        'doctor_work.medicine.manage' => 'Can manage OPD medicine',
        'doctor_work.advice.manage' => 'Can manage OPD advice master',
        'doctor_work.template_workspace.access' => 'Can access clinical templates workspace',
        'pharmacy.access'     => 'Can access pharmacy module',
        'pharmacy.purchase.status-update' => 'Can update pharmacy purchase invoice status',
        'template.pathology'  => 'Can access pathology templates',
        'template.ultrasound' => 'Can access ultrasound templates',
        'template.xray'       => 'Can access x-ray templates',
        'template.ct'         => 'Can access CT templates',
        'template.mri'        => 'Can access MRI templates',
        'template.echo'       => 'Can access ECHO templates',
        'template.discharge'  => 'Can access IPD discharge templates',
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions Matrix
     * --------------------------------------------------------------------
     * Maps permissions to groups.
     *
     * This defines group-level permissions.
     */
    public array $matrix = [
        'superadmin' => [
            'admin.*',
            'users.*',
            'beta.*',
            'billing.*',
            'finance.*',
            'diagnosis.*',
            'doctor_work.*',
            'pharmacy.*',
            'template.*',
        ],
        'admin' => [
            'admin.access',
            'users.create',
            'users.edit',
            'users.delete',
            'beta.access',
            'billing.*',
            'finance.*',
            'diagnosis.*',
            'doctor_work.*',
            'pharmacy.*',
            'template.*',
        ],
        'developer' => [
            'admin.access',
            'admin.settings',
            'users.create',
            'users.edit',
            'beta.access',
            'billing.*',
            'finance.*',
            'diagnosis.*',
            'doctor_work.*',
            'pharmacy.*',
            'template.*',
        ],
        'user' => [],
        'beta' => [
            'beta.access',
        ],
    ];
}

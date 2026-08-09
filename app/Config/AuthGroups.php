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
        'doctor' => [
            'title'       => 'Doctor',
            'description' => 'Clinical doctor access to OPD work, diagnosis reports, and immunization records.',
        ],
        'nurse' => [
            'title'       => 'Nurse',
            'description' => 'In-patient nursing records, bedside care, bed transfers, and nursing charges.',
        ],
        'pharmacy_admin' => [
            'title'       => 'Pharmacy Admin',
            'description' => 'Full pharmacy controls including old bill edits and discount overrides.',
        ],
        'stock_manager' => [
            'title'       => 'Stock Manager',
            'description' => 'Manages stock masters, approvals, and procurement.',
        ],
        'stock_requester' => [
            'title'       => 'Stock Requester',
            'description' => 'Compatibility role for users who create stock indents.',
        ],
        'stock_issuer' => [
            'title'       => 'Stock Issuer',
            'description' => 'Compatibility role for users who issue stock and receive purchase stock.',
        ],
        'department_head' => [
            'title'       => 'Department Head',
            'description' => 'Creates and tracks departmental stock indents.',
        ],
        'storekeeper' => [
            'title'       => 'Storekeeper',
            'description' => 'Handles item issuance and stock receiving operations.',
        ],
        'billing_cashier' => [
            'title'       => 'Billing Cashier',
            'description' => 'Submits billing cash statements to accounts.',
        ],
        'accounts_officer' => [
            'title'       => 'Accounts Officer',
            'description' => 'Accepts/verifies billing statements and audits bank records.',
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
        'media.image.preupload-edit' => 'Can rotate/crop images before upload (requires hospital setting ON)',
        
        'billing.patient.edit-name-anytime' => 'Can edit patient name even after 24-hour restriction',
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
        'billing.ipd.admission.edit' => 'Can edit IPD admission details (date, mode, department, refer, doctors, contact, clinical notes)',
        'billing.ipd.bill.print' => 'Can print IPD bill variants',
        'billing.ipd.discharge.edit' => 'Can edit IPD discharge process details (discount, extra charges, status, date, time)',
        'billing.ipd.status.manage' => 'Can toggle IPD status between Active and Discharged (controls bill edit lock)',
        'settings.bed_status.view' => 'Can view bed status and bed management screens',
        'settings.charges.access' => 'Can access charges settings',
        'reports.access' => 'Can access reports section',
        'reports.collection.view' => 'Can view collection reports',
        'reports.insurance_credit.view' => 'Can view insurance credit reports',
        'reports.nabh_audit.view' => 'Can view NABH audit report',
        'reports.billing_operations.view' => 'Can view billing operations reports',
        'reports.document_issue.view' => 'Can view document issue reports',
        'finance.workflow.view' => 'Can access Accounts and Finance workflow dashboard',
        'finance.access' => 'Can access finance procurement and payable workflows',
        'finance.cash.billing.submit' => 'Can create billing cash entries and submit cash statements',
        'finance.cash.accounts.accept' => 'Can accept submitted billing cash statements',
        'finance.cash.accounts.verify' => 'Can verify accepted billing cash statements',
        'finance.bank.deposit.create' => 'Can create bank deposit register entries',
        'finance.bank.audit' => 'Can mark bank transaction audit status',
        'finance.bank.statement.update' => 'Can mark updated in bank statement status',
        'finance.compliance.view' => 'Can view finance compliance reports',
        'finance.doctor_payout.manage' => 'Can manage doctor payouts and agreements',
        'finance.vendor.manage' => 'Can manage finance vendors',
        'finance.po.manage' => 'Can manage finance purchase orders',
        'finance.grn.manage' => 'Can manage goods receipt notes',
        'finance.invoice.manage' => 'Can manage vendor and payable invoices',
        'diagnosis.access'    => 'Can access diagnosis module',
        'diagnosis.report.view' => 'Can view diagnosis reports',
        'doctor_work.access'  => 'Can access doctor work module',
        'doctor_work.appointment.view' => 'Can view OPD appointment list',
        'doctor_work.rx_group.manage' => 'Can manage Rx group panel',
        'doctor_work.medicine.manage' => 'Can manage OPD medicine',
        'doctor_work.advice.manage' => 'Can manage OPD advice master',
        'doctor_work.template_workspace.access' => 'Can access clinical templates workspace',
        'doctor_work.immunization.access' => 'Can access immunization schedules and patient records',
        'doctor_work.immunization.schedule-manage' => 'Can synchronize and modify immunization schedule masters',
        'doctor_work.immunization.record-manage' => 'Can generate and complete patient immunization records',
        'pharmacy.access'     => 'Can access pharmacy module',
        'pharmacy.invoice.admin' => 'Can fully manage pharmacy invoice controls (old bills and discount overrides)',
        'pharmacy.invoice.discount.10' => 'Can apply pharmacy invoice discount up to 10%',
        'pharmacy.invoice.discount.20' => 'Can apply pharmacy invoice discount up to 20%',
        'pharmacy.invoice.discount.30' => 'Can apply pharmacy invoice discount up to 30%',
        'pharmacy.invoice.edit-old' => 'Can edit past Walk-in/Registered pharmacy invoices',
        'pharmacy.purchase.status-update' => 'Can update pharmacy purchase invoice status',
        'template.pathology'  => 'Can access pathology templates',
        'template.ultrasound' => 'Can access ultrasound templates',
        'template.xray'       => 'Can access x-ray templates',
        'template.ct'         => 'Can access CT templates',
        'template.mri'        => 'Can access MRI templates',
        'template.echo'       => 'Can access ECHO templates',
        'template.discharge'  => 'Can access IPD discharge templates',
        'template.opd_print'  => 'Can access OPD print templates',
        'template.pathology_print' => 'Can access pathology print templates',
        'template.diagnosis_print' => 'Can access diagnosis print templates',
        'template.document_print' => 'Can access document print templates',
        'template.ipd_document' => 'Can access IPD document master templates',
        'hospital_stock.access' => 'Can access hospital stock module',
        'hospital_stock.master.manage' => 'Can manage hospital stock masters (category/item/supplier)',
        'hospital_stock.indent.create' => 'Can create stock indents',
        'hospital_stock.indent.approve' => 'Can approve stock indents',
        'hospital_stock.issue' => 'Can issue stock against approved indents',
        'hospital_stock.purchase.manage' => 'Can create/receive purchase orders',
        'hospital_stock.report.view' => 'Can view stock reports and alerts',
        'abdm.access' => 'Can access ABDM module',
        'abdm.taskboard.access' => 'Can access ABDM task board',
        'abdm.gateway.use' => 'Can run ABDM gateway actions from UI',
        'abdm.bridge_log.view' => 'Can view ABDM Bridge communication log',
        'abdm.abha.create' => 'Can create, verify, and link ABHA records for patients',
        'billing.payment_request.view' => 'Can view organization payment requests',
        'billing.payment_request.manage' => 'Can process organization payment requests',
        'billing.refund.view' => 'Can view billing refund requests',
        'billing.refund.manage' => 'Can process billing refunds',
        'billing.items.view' => 'Can view billing item masters',
        'billing.items.manage' => 'Can manage billing item masters',
        'billing.packages.view' => 'Can view billing package masters',
        'billing.packages.manage' => 'Can manage billing package masters',
        'ipd_nursing.view' => 'Can view the IPD patient list, nursing workspace, and nursing charts',
        'ipd_nursing.record.manage' => 'Can create and update nursing records, admission history, and scanned nursing entries',
        'ipd_nursing.bed.transfer' => 'Can transfer admitted patients between beds',
        'ipd_nursing.charge.manage' => 'Can add, update, and remove nursing and bedside charges',
        'ipd_discharge.view' => 'Can search, preview, and print IPD discharge summaries',
        'ipd_discharge.manage' => 'Can create and update IPD discharge summaries and personal section templates',
        'ipd_discharge.master.manage' => 'Can manage shared discharge surgery, course, dietary, and ICD masters',
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
            'media.image.preupload-edit',
            'opd.doctor-panel.access',
            'settings.bed_status.view',
            'settings.charges.access',
            'reports.access',
            'reports.collection.view',
            'reports.insurance_credit.view',
            'reports.nabh_audit.view',
            'reports.billing_operations.view',
            'reports.document_issue.view',
            'finance.*',
            'diagnosis.*',
            'doctor_work.*',
            'pharmacy.*',
            'template.*',
            'hospital_stock.*',
            'abdm.*',
            'ipd_nursing.*',
            'ipd_discharge.*',
        ],
        'admin' => [
            'admin.access',
            'users.create',
            'users.edit',
            'users.delete',
            'beta.access',
            'billing.*',
            'media.image.preupload-edit',
            'billing.patient.edit-name-anytime',
            'opd.doctor-panel.access',
            'settings.bed_status.view',
            'settings.charges.access',
            'reports.access',
            'reports.collection.view',
            'reports.insurance_credit.view',
            'reports.nabh_audit.view',
            'reports.billing_operations.view',
            'reports.document_issue.view',
            'finance.*',
            'diagnosis.*',
            'doctor_work.*',
            'pharmacy.*',
            'template.*',
            'hospital_stock.*',
            'abdm.*',
            'ipd_nursing.*',
            'ipd_discharge.*',
        ],
        'developer' => [
            'admin.access',
            'admin.settings',
            'users.create',
            'users.edit',
            'beta.access',
            'billing.*',
            'media.image.preupload-edit',
            'opd.doctor-panel.access',
            'settings.bed_status.view',
            'settings.charges.access',
            'reports.access',
            'reports.collection.view',
            'reports.insurance_credit.view',
            'reports.nabh_audit.view',
            'reports.billing_operations.view',
            'reports.document_issue.view',
            'finance.*',
            'diagnosis.*',
            'doctor_work.*',
            'pharmacy.*',
            'template.*',
            'hospital_stock.*',
            'abdm.*',
            'ipd_nursing.*',
            'ipd_discharge.*',
        ],
        'doctor' => [
            'opd.doctor-panel.access',
            'doctor_work.access',
            'doctor_work.immunization.access',
            'doctor_work.immunization.record-manage',
            'diagnosis.access',
            'diagnosis.report.view',
            'ipd_nursing.view',
            'ipd_discharge.view',
            'ipd_discharge.manage',
        ],
        'nurse' => [
            'ipd_nursing.view',
            'ipd_nursing.record.manage',
            'ipd_nursing.bed.transfer',
            'ipd_nursing.charge.manage',
            'ipd_discharge.view',
        ],
        'pharmacy_admin' => [
            'pharmacy.*',
        ],
        'stock_manager' => [
            'hospital_stock.access',
            'hospital_stock.master.manage',
            'hospital_stock.indent.approve',
            'hospital_stock.purchase.manage',
            'hospital_stock.report.view',
        ],
        'stock_requester' => [
            'hospital_stock.access',
            'hospital_stock.indent.create',
            'hospital_stock.report.view',
        ],
        'stock_issuer' => [
            'hospital_stock.access',
            'hospital_stock.issue',
            'hospital_stock.purchase.manage',
            'hospital_stock.report.view',
        ],
        'department_head' => [
            'hospital_stock.access',
            'hospital_stock.indent.create',
            'hospital_stock.report.view',
        ],
        'storekeeper' => [
            'hospital_stock.access',
            'hospital_stock.issue',
            'hospital_stock.purchase.manage',
            'hospital_stock.report.view',
        ],
        'billing_cashier' => [
            'finance.workflow.view',
            'finance.cash.billing.submit',
            'finance.bank.deposit.create',
            'abdm.abha.create',
            'billing.payment_request.view',
            'billing.payment_request.manage',
            'billing.refund.view',
            'billing.refund.manage',
            'ipd_discharge.view',
        ],
        'accounts_officer' => [
            'finance.workflow.view',
            'finance.cash.accounts.accept',
            'finance.cash.accounts.verify',
            'finance.bank.audit',
            'finance.bank.statement.update',
        ],
        'user' => [],
        'beta' => [
            'beta.access',
        ],
    ];
}

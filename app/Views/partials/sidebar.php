<?php
$user = function_exists('auth') ? auth()->user() : null;
$canTemplate = false;
if ($user && method_exists($user, 'can')) {
    $canTemplate = $user->can('template.pathology')
        || $user->can('template.ultrasound')
        || $user->can('template.xray')
        || $user->can('template.ct')
        || $user->can('template.mri')
        || $user->can('template.echo')
        || $user->can('template.*');
}

if (! $canTemplate && $user && method_exists($user, 'inGroup')) {
    $canTemplate = $user->inGroup('superadmin', 'admin', 'developer');
}

$canIpdBilling = $user && method_exists($user, 'can')
    ? ($user->can('billing.ipd.access')
        || $user->can('billing.ipd.current-admission')
        || $user->can('billing.ipd.invoice')
        || $user->can('billing.ipd.cash-balance')
        || $user->can('billing.access'))
    : false;

$canBilling = false;
if ($user && method_exists($user, 'can')) {
    $canBilling = $user->can('billing.access')
        || $user->can('billing.opd.edit')
        || $user->can('billing.opd.pay')
        || $user->can('billing.charges.view')
        || $user->can('billing.charges.edit')
        || $user->can('billing.charges.date-edit')
        || $user->can('billing.charges.pay')
        || $user->can('billing.charges.cancel')
        || $user->can('billing.charges.correct')
        || $user->can('billing.ipd.access')
        || $user->can('billing.ipd.current-admission')
        || $user->can('billing.ipd.invoice')
        || $user->can('billing.ipd.cash-balance')
        || $user->can('billing.ipd.export')
        || $user->can('billing.*');
}

if (! $canBilling && $user && method_exists($user, 'inGroup')) {
    $canBilling = $user->inGroup('superadmin', 'admin', 'developer');
}

$canPharmacy = true;
if ($user && method_exists($user, 'can')) {
    $canPharmacy = $user->can('pharmacy.access')
        || $user->can('billing.access');
}

if (! $canPharmacy && $user && method_exists($user, 'inGroup')) {
    $canPharmacy = $user->inGroup('superadmin', 'admin', 'developer');
}

$canHospitalStock = false;
if ($user && method_exists($user, 'can')) {
    $canHospitalStock = $user->can('hospital_stock.access')
        || $user->can('hospital_stock.master.manage')
        || $user->can('hospital_stock.indent.create')
        || $user->can('hospital_stock.indent.approve')
        || $user->can('hospital_stock.issue')
        || $user->can('hospital_stock.purchase.manage')
        || $user->can('hospital_stock.report.view')
        || $user->can('hospital_stock.*');
}

if (! $canHospitalStock && $user && method_exists($user, 'inGroup')) {
    $canHospitalStock = $user->inGroup('superadmin', 'admin', 'developer', 'stock_manager', 'stock_requester', 'stock_issuer');
}

$canFinance = false;
if ($user && method_exists($user, 'can')) {
    $canFinance = $user->can('finance.workflow.view')
        || $user->can('finance.cash.billing.submit')
        || $user->can('finance.cash.accounts.accept')
        || $user->can('finance.cash.accounts.verify')
        || $user->can('finance.bank.deposit.create')
        || $user->can('finance.bank.audit')
        || $user->can('finance.bank.statement.update')
        || $user->can('finance.*');
}

$canFinanceBilling = false;
$canFinanceAccounts = false;
$canFinanceBank = false;
if ($user && method_exists($user, 'can')) {
    $canFinanceBilling = $user->can('finance.cash.billing.submit') || $user->can('finance.*');
    $canFinanceAccounts = $user->can('finance.cash.accounts.accept') || $user->can('finance.cash.accounts.verify') || $user->can('finance.*');
    $canFinanceBank = $user->can('finance.bank.deposit.create') || $user->can('finance.bank.audit') || $user->can('finance.bank.statement.update') || $user->can('finance.*');
}

$canDoctorWork = false;
if ($user && method_exists($user, 'can')) {
    $canDoctorWork = $user->can('doctor_work.access')
        || $user->can('doctor_work.appointment.view')
        || $user->can('doctor_work.rx_group.manage')
        || $user->can('doctor_work.medicine.manage')
        || $user->can('doctor_work.advice.manage')
        || $user->can('doctor_work.template_workspace.access')
        || $user->can('doctor_work.*');
}

if (! $canDoctorWork && $user && method_exists($user, 'inGroup')) {
    $canDoctorWork = $user->inGroup('superadmin', 'admin', 'developer');
}

$canDiagnosis = false;
if ($user && method_exists($user, 'can')) {
    $canDiagnosis = $user->can('diagnosis.access')
        || $user->can('diagnosis.report.view')
        || $user->can('diagnosis.*');
}

if (! $canDiagnosis && $user && method_exists($user, 'inGroup')) {
    $canDiagnosis = $user->inGroup('superadmin', 'admin', 'developer');
}

$canAbdm = false;
if ($user && method_exists($user, 'can')) {
    $canAbdm = $user->can('abdm.access')
        || $user->can('abdm.taskboard.access')
        || $user->can('abdm.gateway.use')
        || $user->can('abdm.*');
}

if (! $canAbdm && $user && method_exists($user, 'inGroup')) {
    $canAbdm = $user->inGroup('superadmin', 'admin', 'developer');
}

$canBedStatus = false;
if ($user && method_exists($user, 'can')) {
    $canBedStatus = $user->can('settings.bed_status.view')
        || $user->can('admin.settings')
        || $user->can('admin.access');
}

if (! $canBedStatus && $user && method_exists($user, 'inGroup')) {
    $canBedStatus = $user->inGroup('superadmin', 'admin', 'developer');
}

$canReports = false;
if ($user && method_exists($user, 'can')) {
    $canReports = $user->can('reports.access')
        || $user->can('reports.collection.view')
        || $user->can('reports.insurance_credit.view')
        || $user->can('reports.nabh_audit.view')
        || $user->can('diagnosis.report.view')
        || $user->can('diagnosis.access');
}

if (! $canReports && $user && method_exists($user, 'inGroup')) {
    $canReports = $user->inGroup('superadmin', 'admin', 'developer');
}

$canAdminPanel = false;
if ($user && method_exists($user, 'can')) {
    $canAdminPanel = $user->can('admin.access')
        || $user->can('admin.settings');
}

if (! $canAdminPanel && $user && method_exists($user, 'inGroup')) {
    $canAdminPanel = $user->inGroup('superadmin', 'admin', 'developer');
}

$canChargesSettings = false;
if ($user && method_exists($user, 'can')) {
    $canChargesSettings = $user->can('settings.charges.access')
        || $user->can('admin.settings')
        || $user->can('admin.access');
}

if (! $canChargesSettings && $user && method_exists($user, 'inGroup')) {
    $canChargesSettings = $user->inGroup('superadmin', 'admin', 'developer');
}
?>
<ul class="sidebar-nav" id="sidebar-nav">
    <li class="nav-item">
        <a class="nav-link" href="javascript:load_form('<?= base_url('dashboard') ?>','Dashboard');">
            <i class="bi bi-grid"></i>
            <span>Dashboard</span>
        </a>
    </li>
    <?php if ($canBedStatus) { ?>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('setting/admin/bed-status') ?>','Bed Status');">
                <i class="bi bi-geo-alt"></i>
                <span>Bed Status</span>
            </a>
        </li>
    <?php } ?>
    <?php if ($canBilling) { ?>
        
    <li class="nav-heading">Billing</li>
    <li class="nav-item">
        <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('/billing/patient') ?>','Patient List')">
            <i class="bi bi-person-lines-fill"></i>
            <span>Patient List</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Invoice/opdlist') ?>','OPD Invoice')">
            <i class="bi bi-receipt"></i>
            <span>OPD Invoice</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Invoice/chargeslist') ?>','Charges Invoice')">
            <i class="bi bi-journal-text"></i>
            <span>Charges Invoice</span>
        </a>
    </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('/billing/ipd') ?>','IPD List')">
                <i class="bi bi-hospital"></i>
                <span>IPD Billing </span>
            </a>
        </li>
    <li class="nav-item">
        <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Orgcase/search_all') ?>','Org. OPD\'s Invoice')">
            <i class="bi bi-receipt-cutoff"></i>
            <span>Org. OPD's Invoice</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Invoice/list_req_payment') ?>','Payment Request')">
            <i class="bi bi-cash-coin"></i>
            <span>Payment Request</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Invoice/list_refund') ?>','Refund Amount Request')">
            <i class="bi bi-arrow-counterclockwise"></i>
            <span>Refund Amount Request</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Report/index') ?>','Report Panel')">
            <i class="bi bi-grid-1x2"></i>
            <span>Report Panel</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('org-packing') ?>','Organization Packing')">
            <i class="bi bi-box-seam"></i>
            <span>Org Packing </span>
        </a>
    </li>
    <?php if ($canFinanceBilling) { ?>
    <li class="nav-item">
        <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('billing/cash-submission/create') ?>','Cash Submission')">
            <i class="bi bi-cash-stack"></i>
            <span>Cash Submission</span>
        </a>
    </li>
    <?php } ?>
    <?php } ?>
    <?php if ($canFinance) { ?>
        <li class="nav-heading">Accounts And Finance</li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Finance/index') ?>','Finance & Accounting')">
                <i class="bi bi-bank"></i>
                <span>Accounts And Finance</span>
            </a>
        </li>
        <?php if ($canFinanceBilling) { ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('billing/cash-submission/create') ?>','Billing Cash Statement Submission')">
                    <i class="bi bi-journal-arrow-up"></i>
                    <span>Billing Cash Statement</span>
                </a>
            </li>
        <?php } ?>
        <?php if ($canFinanceAccounts) { ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Finance/cashbook/accounts') ?>','Accounts Accept and Verify')">
                    <i class="bi bi-patch-check"></i>
                    <span>Accounts Verify Payment</span>
                </a>
            </li>
        <?php } ?>
        <?php if ($canFinanceBank) { ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Finance/bank_audit') ?>','Bank Payment Audit')">
                    <i class="bi bi-building"></i>
                    <span>Bank Audit And Statement</span>
                </a>
            </li>
        <?php } ?>
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#finance-payout-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-cash-stack"></i>
                <span>Payout</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="finance-payout-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <li>
                    <a href="javascript:load_form('<?= base_url('Finance/payout/opd-consult') ?>','OPD Consult Payout')">
                        <i class="bi bi-circle"></i>
                        <span>OPD Consult Payout</span>
                    </a>
                </li>
            </ul>
        </li>
    <?php } ?>
    <?php if ($canIpdBilling) { ?>
        <li class="nav-heading">In-Patient & Nursing Care</li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('/ipd/patient') ?>','IPD Patient List')">
                <i class="bi bi-person-vcard"></i>
                <span>IPD Patient List</span>
            </a>
        </li>
    <?php } ?>
    
    <?php if ($canDoctorWork) { ?>
        <li class="nav-heading">Doctor's Work</li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('/opd/appointment') ?>','OPD Appointment List')">
                <i class="bi bi-calendar2-check"></i>
                <span>OPD Appointment List</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Opd_prescription/rx_group_panel') ?>','Rx-Group')">
                <i class="bi bi-collection"></i>
                <span>Rx-Group</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Opd_prescription/opd_medicince') ?>','OPD Medicine')">
                <i class="bi bi-capsule"></i>
                <span>OPD Medicine</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Opd_prescription/opd_invest_master') ?>','Investigation Master')">
                <i class="bi bi-clipboard2-pulse"></i>
                <span>Investigation Master</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Opd_prescription/opd_invest_profile_master') ?>','Investigation Profile Master')">
                <i class="bi bi-collection"></i>
                <span>Investigation Profile Master</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Opd_prescription/opd_advice') ?>','OPD Advice Master')">
                <i class="bi bi-chat-left-text"></i>
                <span>OPD Advice Master</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Opd_prescription/template_workspace') ?>','Clinical Templates')">
                <i class="bi bi-journal-text"></i>
                <span>Clinical Templates</span>
            </a>
        </li>
        <li class="nav-item">
        <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('doctor_work/document_workspace') ?>','Doctor Documents Workspace')">
            <i class="bi bi-file-earmark-text"></i>
            <span>Document Issue </span>
        </a>
    </li>
    <?php } ?>
   
    <?php if ($canPharmacy) { ?>
        <li class="nav-heading">Pharmacy</li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Medical') ?>','Medical Store')">
                <i class="bi bi-capsule-pill"></i>
                <span>Pharmacy</span>
            </a>
        </li>
    <?php } ?>
    <?php if ($canHospitalStock) { ?>
        <li class="nav-heading">Hospital Stock</li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Storestock') ?>','Hospital Stock');">
                <i class="bi bi-box-seam"></i>
                <span>Hospital Stock</span>
            </a>
        </li>
    <?php } ?>

    <?php if ($canAbdm) { ?>
        <li class="nav-heading">ABDM</li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('AbdmTaskBoard') ?>','ABDM Task Board')">
                <i class="bi bi-shield-check"></i>
                <span>ABDM Task Board</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('AbdmOpdQueue') ?>','ABDM OPD Queue')">
                <i class="bi bi-people"></i>
                <span>ABDM OPD Queue</span>
            </a>
        </li>
    <?php } ?>

    <?php if ($canReports) { ?>
        <li class="nav-heading">Reports</li>

        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Report/collection_report') ?>','Collection Report')">
                <i class="bi bi-graph-up"></i>
                <span>Collection Report</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Report/report_opd_total') ?>','OPD Total Report')">
                <i class="bi bi-bar-chart"></i>
                <span>OPD Total Report</span>
            </a>
        </li>
        <?php if ($canDiagnosis) { ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Report/diagnosis_report') ?>','Diagnosis Report')">
                    <i class="bi bi-activity"></i>
                    <span>Diagnosis Report</span>
                </a>
            </li>
        <?php } ?>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Report/insurance_credit_main') ?>','Insurance Credit')">
                <i class="bi bi-file-medical"></i>
                <span>Insurance Credit </span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Report/nabh_audit_report') ?>','NABH Audit Report')">
                <i class="bi bi-clipboard2-check"></i>
                <span>NABH Audit Report</span>
            </a>
        </li>
    <?php } ?>
    
    
    <?php if ($canDiagnosis) { ?>
        <li class="nav-heading">Diagnosis</li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('diagnosis/pathology') ?>','Pathology')">
                <i class="bi bi-eyedropper"></i>
                <span>Pathology</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('diagnosis/biopsy') ?>','Biopsy')">
                <i class="bi bi-capsule"></i>
                <span>Biopsy</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('diagnosis/xray') ?>','X-Ray')">
                <i class="bi bi-file-medical"></i>
                <span>X-Ray</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('diagnosis/mri') ?>','MRI')">
                <i class="bi bi-circle-square"></i>
                <span>MRI</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('diagnosis/ctscan') ?>','CT-Scan')">
                <i class="bi bi-disc"></i>
                <span>CT-Scan</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('diagnosis/ultrasound') ?>','Ultrasound')">
                <i class="bi bi-soundwave"></i>
                <span>Ultrasound</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('diagnosis/echo') ?>','Echo')">
                <i class="bi bi-heart-pulse"></i>
                <span>Echo</span>
            </a>
        </li>
    <?php } ?>
    <?php if ($canAdminPanel || $canTemplate || $canChargesSettings) { ?>
        <li class="nav-heading">Admin & Settings</li>
        <?php if ($canAdminPanel) { ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('setting/admin') ?>','Admin Panel')">
                    <i class="bi bi-gear"></i>
                    <span>Admin</span>
                </a>
            </li>
        <?php } ?>
        <?php if ($canTemplate) { ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('setting/template') ?>','Template')">
                    <i class="bi bi-palette"></i>
                    <span>Template</span>
                </a>
            </li>
        <?php } ?>
        <?php if ($canChargesSettings) { ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('setting/charges') ?>','Charges')">
                    <i class="bi bi-tags"></i>
                    <span>Charges</span>
                </a>
            </li>
        <?php } ?>
    <?php } ?>

</ul>
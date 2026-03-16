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

$canFinance = false;
if ($user && method_exists($user, 'can')) {
    $canFinance = $user->can('finance.access')
        || $user->can('finance.vendor.manage')
        || $user->can('finance.po.manage')
        || $user->can('finance.grn.manage')
        || $user->can('finance.invoice.manage')
        || $user->can('finance.cash.manage')
        || $user->can('finance.doctor_payout.manage')
        || $user->can('finance.bank_deposit.manage')
        || $user->can('finance.compliance.view')
        || $user->can('finance.*');
}

if (! $canFinance && $user && method_exists($user, 'inGroup')) {
    $canFinance = $user->inGroup('superadmin', 'admin', 'developer');
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
?>
<ul class="sidebar-nav" id="sidebar-nav">
    <li class="nav-item">
        <a class="nav-link" href="javascript:load_form('<?= base_url('dashboard') ?>','Dashboard');">
            <i class="bi bi-grid"></i>
            <span>Dashboard</span>
        </a>
    </li>
    <?php if ($canBilling) { ?>
        
    <li class="nav-heading">Billing</li>
    <li class="nav-item">
        <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('/billing/patient') ?>','Patient List')">
            <i class="bi bi-person-lines-fill"></i>
            <span>Patient List</span>
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
    <?php } ?>
    <?php if ($canFinance) { ?>
        <li class="nav-heading">Accounts And Finance</li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Finance/index') ?>','Finance & Accounting')">
                <i class="bi bi-bank"></i>
                <span>Finance & Accounting</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Finance/phase2') ?>','Finance & Accounting - Phase 2')">
                <i class="bi bi-diagram-3"></i>
                <span>Finance Phase 2</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Finance/cashbook') ?>','Cash Collection & Disbursement SOP')">
                <i class="bi bi-cash-stack"></i>
                <span>Cash & Disbursement SOP</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Finance/doctor_payout') ?>','Doctor Payout Workflow')">
                <i class="bi bi-person-badge"></i>
                <span>Doctor Payout Workflow</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Finance/bank_deposits') ?>','Bank Deposit Register')">
                <i class="bi bi-building"></i>
                <span>Bank Deposit Register</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Finance/compliance_report') ?>','Finance Compliance Report')">
                <i class="bi bi-shield-exclamation"></i>
                <span>Finance Compliance Report</span>
            </a>
        </li>
    <?php } ?>
    <li class="nav-heading">In-Patient & Nursing Care</li>
    <li class="nav-item">
        <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('/ipd/patient') ?>','IPD Patient List')">
            <i class="bi bi-person-vcard"></i>
            <span>IPD Patient List</span>
        </a>
    </li>
    
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

    <li class="nav-heading">Reports</li>
    
    <li class="nav-item">
        <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('Report/collection_report') ?>','Collection Report')">
            <i class="bi bi-graph-up"></i>
            <span>Collection Report</span>
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
    <?php } ?>
    <li class="nav-heading">Admin & Settings</li>
    <li class="nav-item">
        <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('setting/admin') ?>','Admin Panel')">
            <i class="bi bi-gear"></i>
            <span>Admin</span>
        </a>
    </li>
    <?php if ($canTemplate) { ?>
        <li class="nav-item">
            <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('setting/template') ?>','Template')">
                <i class="bi bi-palette"></i>
                <span>Template</span>
            </a>
        </li>
    <?php } ?>
    <li class="nav-item">
        <a class="nav-link collapsed" href="javascript:load_form('<?= base_url('setting/charges') ?>','Charges')">
            <i class="bi bi-tags"></i>
            <span>Charges</span>
        </a>
    </li>

</ul>
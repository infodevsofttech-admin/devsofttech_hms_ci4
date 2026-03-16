<?php
$user = function_exists('auth') ? auth()->user() : null;
$canPathology = $user && method_exists($user, 'can') ? $user->can('template.pathology') : false;
$canUltrasound = $user && method_exists($user, 'can') ? $user->can('template.ultrasound') : false;
$canXray = $user && method_exists($user, 'can') ? $user->can('template.xray') : false;
$canCt = $user && method_exists($user, 'can') ? $user->can('template.ct') : false;
$canMri = $user && method_exists($user, 'can') ? $user->can('template.mri') : false;
$canEcho = $user && method_exists($user, 'can') ? $user->can('template.echo') : false;
$canDischarge = $user && method_exists($user, 'can') ? $user->can('template.discharge') : false;
$canOpdPrintTemplate = $user && method_exists($user, 'can') ? $user->can('billing.opd.edit') : false;
$canDiagnosisPrintTemplate = $canPathology || $canUltrasound || $canXray || $canCt || $canMri || $canEcho;
$canDoctorDocumentPrintTemplate = $user && method_exists($user, 'can')
    ? ($user->can('doctor_work.template_workspace.access') || $user->can('doctor_work.access') || $user->can('template.pathology'))
    : false;

if (! $canOpdPrintTemplate && $user && method_exists($user, 'inGroup')) {
    $canOpdPrintTemplate = $user->inGroup('OPDEdit', 'admin', 'superadmin', 'developer');
}

$hasTemplateAccess = $canPathology || $canUltrasound || $canXray || $canCt || $canMri || $canEcho || $canDischarge || $canDoctorDocumentPrintTemplate;
?>
<section class="content">
    <div class="pagetitle">
        <h1>Templates Library</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Dashboard</li>
                <li class="breadcrumb-item active">Template</li>
            </ol>
        </nav>
    </div>

    <style>
        .admin-hero {
            background: linear-gradient(120deg, #f4f7fb 0%, #eef3ff 100%);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 16px;
        }
        .admin-hero h2 {
            font-family: "Poppins", "Nunito", sans-serif;
            font-size: 22px;
            margin: 0;
            color: #0f172a;
        }
        .admin-tiles {
            margin-top: 12px;
        }

        .admin-tile {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 20px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            color: #1f2937;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            min-height: 110px;
        }

        .admin-tile:hover {
            border-color: #cfd6de;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
            transform: translateY(-2px);
        }

        .admin-tile .tile-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #eef2ff;
            color: #1d4ed8;
            font-size: 20px;
        }

        .admin-tile span {
            font-weight: 600;
            font-size: 14px;
            text-align: center;
        }

        .admin-panel {
            margin-top: 8px;
            padding: 10px 12px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            min-height: 120px;
        }

        .admin-panel section.content {
            padding: 0;
            margin: 0;
        }

        .admin-panel .row {
            margin-left: -6px;
            margin-right: -6px;
        }

        .admin-panel .row > [class*="col-"] {
            padding-left: 6px;
            padding-right: 6px;
        }

        .admin-panel .card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
            margin-bottom: 12px;
        }

        .admin-panel .card-header {
            padding: 10px 14px;
        }

        .admin-panel .card-body {
            padding: 12px 14px;
        }

        .admin-panel .table {
            margin-bottom: 0;
        }
    </style>

    <?php if (! $hasTemplateAccess) { ?>
        <div class="alert alert-warning mt-3 mb-0">
            You do not have access to any templates. Please contact your administrator.
        </div>
    <?php } ?>

    <div class="row g-3 admin-tiles">
        <?php if ($canPathology) { ?>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:load_form_div('<?= base_url('Lab_Admin/report_list') ?>','maindiv','Pathology Template');">
                    <span class="tile-icon"><i class="bi bi-heart-pulse"></i></span>
                    <span>Pathology Template</span>
                </a>
            </div>
        <?php } ?>
        <?php if ($canUltrasound) { ?>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:load_form_div('<?= base_url('Lab_Admin/report_ultrasound_list/1') ?>','maindiv','Ultra Sound Template');">
                    <span class="tile-icon"><i class="bi bi-broadcast"></i></span>
                    <span>Ultra Sound Template</span>
                </a>
            </div>
        <?php } ?>
        <?php if ($canXray) { ?>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:load_form_div('<?= base_url('Lab_Admin/report_ultrasound_list/3') ?>','maindiv','X-Ray Template');">
                    <span class="tile-icon"><i class="bi bi-camera"></i></span>
                    <span>X-Ray Template</span>
                </a>
            </div>
        <?php } ?>
        <?php if ($canCt) { ?>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:load_form_div('<?= base_url('Lab_Admin/report_ultrasound_list/4') ?>','maindiv','CT-Scan Template');">
                    <span class="tile-icon"><i class="bi bi-cpu"></i></span>
                    <span>CT-Scan Template</span>
                </a>
            </div>
        <?php } ?>
        <?php if ($canMri) { ?>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:load_form_div('<?= base_url('Lab_Admin/report_ultrasound_list/2') ?>','maindiv','MRI Template');">
                    <span class="tile-icon"><i class="bi bi-activity"></i></span>
                    <span>MRI Template</span>
                </a>
            </div>
        <?php } ?>
        <?php if ($canEcho) { ?>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:load_form_div('<?= base_url('Lab_Admin/report_ultrasound_list/6') ?>','maindiv','ECHO Template');">
                    <span class="tile-icon"><i class="bi bi-soundwave"></i></span>
                    <span>ECHO Template</span>
                </a>
            </div>
        <?php } ?>

        <?php if ($canOpdPrintTemplate) { ?>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:load_form_div('<?= base_url('Opd/print_template_builder') ?>','maindiv','OPD Print Template');">
                    <span class="tile-icon"><i class="bi bi-printer"></i></span>
                    <span>OPD Print Template</span>
                </a>
            </div>
        <?php } ?>

        <?php if ($canDischarge) { ?>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/template/discharge_templates') ?>','maindiv','IPD Discharge Template');">
                    <span class="tile-icon"><i class="bi bi-file-earmark-medical"></i></span>
                    <span>IPD Discharge Template</span>
                </a>
            </div>
        <?php } ?>

        <?php if ($canDiagnosisPrintTemplate) { ?>
            <?php if ($canPathology) { ?>
                <div class="col-6 col-md-2 col-lg-2">
                    <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/template/diagnosis_print_settings/5') ?>','maindiv','Pathology Print Template');">
                        <span class="tile-icon"><i class="bi bi-printer"></i></span>
                        <span>Pathology Print Template</span>
                    </a>
                </div>
            <?php } ?>

            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/template/diagnosis_print_settings/3') ?>','maindiv','Diagnosis Print Template');">
                    <span class="tile-icon"><i class="bi bi-printer-fill"></i></span>
                    <span>Diagnosis Print Template</span>
                </a>
            </div>
        <?php } ?>

        <?php if ($canDoctorDocumentPrintTemplate) { ?>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/template/document_print_settings') ?>','maindiv','Document Print Template');">
                    <span class="tile-icon"><i class="bi bi-file-earmark-pdf"></i></span>
                    <span>Document Print Template</span>
                </a>
            </div>
        <?php } ?>

    </div>

    <div class="row mt-4">
        <div class="col-12 admin-panel" id="maindiv"></div>
    </div>
</section>

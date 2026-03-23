<section class="content">
    <div class="pagetitle">
        <h1>Bed Management</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Dashboard</li>
                <li class="breadcrumb-item active">Bed Management</li>
            </ol>
        </nav>
    </div>

    <div class="row g-3">
        <div class="col-6 col-md-3">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/bed-status') ?>','maindiv','Bed Status');">
                <i class="bi bi-geo-alt"></i>
                <span>Bed Status</span>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/departments') ?>','maindiv','Departments');">
                <i class="bi bi-diagram-2"></i>
                <span>Departments</span>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/wards') ?>','maindiv','Wards');">
                <i class="bi bi-diagram-3"></i>
                <span>Wards</span>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/bed-categories') ?>','maindiv','Bed Categories');">
                <i class="bi bi-layers"></i>
                <span>Bed Categories</span>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/beds') ?>','maindiv','Beds');">
                <i class="bi bi-hospital"></i>
                <span>Bed Master</span>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/bed-maintenance') ?>','maindiv','Bed Maintenance');">
                <i class="bi bi-tools"></i>
                <span>Maintenance Log</span>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/bed-assignments') ?>','maindiv','Bed Assignment History');">
                <i class="bi bi-clock-history"></i>
                <span>Assignment History</span>
            </a>
        </div>
    </div>

    <hr/>
    <div class="row mt-3">
        <div class="col-12" id="maindiv"></div>
    </div>
</section>

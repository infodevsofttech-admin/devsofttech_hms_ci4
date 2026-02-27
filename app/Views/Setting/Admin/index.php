<section class="content">
    <div class="pagetitle">
        <h1>Admin Panel</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Dashboard</li>
                <li class="breadcrumb-item active">Admin</li>
            </ol>
        </nav>
    </div>

    <style>
        .admin-tiles {
            margin-top: 12px;
        }

        .admin-tile {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 18px 12px;
            border: 1px solid #e3e6ea;
            border-radius: 8px;
            background: #ffffff;
            color: #374151;
            text-decoration: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            min-height: 90px;
        }

        .admin-tile:hover {
            border-color: #cfd6de;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .admin-tile i {
            font-size: 22px;
            color: #0d6efd;
        }

        .admin-tile span {
            font-weight: 600;
            font-size: 13px;
            text-align: center;
        }
    </style>

    <div class="row g-3 admin-tiles">
        <div class="col-6 col-md-2 col-lg-2">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/doctor') ?>','maindiv','Doctor Master');">
                <i class="bi bi-person-badge"></i>
                <span>Doctor Master</span>
            </a>
        </div>
        <div class="col-6 col-md-2 col-lg-2">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/user-management') ?>','maindiv','User Management');">
                <i class="bi bi-people"></i>
                <span>User Management</span>
            </a>
        </div>
        <div class="col-6 col-md-2 col-lg-2">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/ai-settings') ?>','maindiv','AI Settings');">
                <i class="bi bi-cpu"></i>
                <span>AI Settings</span>
            </a>
        </div>
        <div class="col-6 col-md-2 col-lg-2">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/insurance') ?>','maindiv','Insurance Master');">
                <i class="bi bi-shield-check"></i>
                <span>Insurance Master</span>
            </a>
        </div>
        <div class="col-6 col-md-2 col-lg-2">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/reffer') ?>','maindiv','Referral Admin');">
                <i class="bi bi-share"></i>
                <span>Referral Admin</span>
            </a>
        </div>
        <div class="col-6 col-md-2 col-lg-2">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/bank') ?>','maindiv','Bank & Payment Sources');">
                <i class="bi bi-bank"></i>
                <span>Bank & Payment</span>
            </a>
        </div>
        <div class="col-6 col-md-2 col-lg-2">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/medical-bank') ?>','maindiv','Medical Bank & Payment Sources');">
                <i class="bi bi-credit-card-2-front"></i>
                <span>Medical Bank</span>
            </a>
        </div>
        <div class="col-6 col-md-2 col-lg-2">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('setting/admin/bed-management') ?>','maindiv','Bed Management');">
                <i class="bi bi-hospital"></i>
                <span>Bed Management</span>
            </a>
        </div>
    </div>
    <hr/>
    <div class="row mt-4">
        <div class="col-12" id="maindiv"></div>
    </div>
</section>

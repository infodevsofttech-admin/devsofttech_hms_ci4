<section class="content">
    <div class="pagetitle mb-3">
        <h1 class="mb-0">Admin Panel</h1>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="javascript:showAdminTiles()">Dashboard Admin</a></li>
                <li class="breadcrumb-item active d-none" id="admin_breadcrumb_sub">Sub Page</li>
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

    <!-- Tiles Grid View -->
    <div id="admin_tiles_view">
        <div class="row g-3 admin-tiles">
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('setting/admin/doctor') ?>','Doctor Master');">
                    <i class="bi bi-person-badge"></i>
                    <span>Doctor Master</span>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('setting/admin/nurse') ?>','Nursing Staff Master');">
                    <i class="bi bi-person-heart" style="color:#0d6efd"></i>
                    <span>Nursing Staff Master</span>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('setting/admin/nursing-station') ?>','Nursing Station Master');">
                    <i class="bi bi-hospital" style="color:#0d6efd"></i>
                    <span>Nursing Station Master</span>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('setting/admin/user-management') ?>','User Management');">
                    <i class="bi bi-people"></i>
                    <span>User Management</span>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('setting/admin/ai-settings') ?>','AI Settings');">
                    <i class="bi bi-cpu"></i>
                    <span>AI Settings</span>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('setting/admin/healthplix-settings') ?>','HealthPlix Integration');">
                    <i class="bi bi-link-45deg"></i>
                    <span>HealthPlix Integration</span>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('setting/admin/abdm-gateway') ?>','ABDM Gateway Config');">
                    <i class="bi bi-hdd-network" style="color:#0d6efd"></i>
                    <span>ABDM Gateway</span>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('setting/admin/abdm-report-doctors') ?>','ABDM Report Doctors');">
                    <i class="bi bi-person-vcard" style="color:#0d6efd"></i>
                    <span>ABDM Report Doctors</span>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('setting/admin/hospital-profile') ?>','Hospital Profile');">
                    <i class="bi bi-building"></i>
                    <span>Hospital Profile</span>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('setting/admin/insurance') ?>','Insurance Master');">
                    <i class="bi bi-shield-check"></i>
                    <span>Insurance Master</span>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('setting/admin/reffer') ?>','Referral Admin');">
                    <i class="bi bi-share"></i>
                    <span>Referral Admin</span>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('setting/admin/bank') ?>','Bank & Payment Sources');">
                    <i class="bi bi-bank"></i>
                    <span>Bank & Payment</span>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('setting/admin/medical-bank') ?>','Medical Bank & Payment Sources');">
                    <i class="bi bi-credit-card-2-front"></i>
                    <span>Medical Bank</span>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('setting/admin/bed-management') ?>','Bed Management');">
                    <i class="bi bi-hospital"></i>
                    <span>Bed Management</span>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('setting/admin/ipd-examination-fields') ?>','IPD Examination Fields');">
                    <i class="bi bi-clipboard2-pulse"></i>
                    <span>IPD Examination Fields</span>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('Storestock') ?>','Hospital Stock');">
                    <i class="bi bi-box-seam"></i>
                    <span>Hospital Stock</span>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a class="admin-tile" href="javascript:openAdminSubPage('<?= base_url('setting/admin/system-ops') ?>','System Operations');">
                    <i class="bi bi-server"></i>
                    <span>System Operations</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Admin Sub-page View Container -->
    <div id="admin_subpage_view" class="d-none">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                <strong class="text-primary fs-6 mb-0" id="admin_subpage_title"><i class="bi bi-gear me-1"></i> Admin Module</strong>
                <button type="button" class="btn btn-outline-primary btn-sm py-1 px-3" onclick="showAdminTiles()">
                    <i class="bi bi-arrow-left me-1"></i> Back to Admin Panel
                </button>
            </div>
            <div class="card-body p-3" id="maindiv"></div>
        </div>
    </div>
</section>

<script>
function openAdminSubPage(url, title) {
    $('#admin_tiles_view').addClass('d-none');
    $('#admin_subpage_view').removeClass('d-none');
    if (title) {
        $('#admin_subpage_title').html('<i class="bi bi-gear me-1"></i> ' + title);
        $('#admin_breadcrumb_sub').removeClass('d-none').text(title);
    }
    load_form_div(url, 'maindiv', title);
}

function showAdminTiles() {
    $('#maindiv').empty();
    $('#admin_subpage_view').addClass('d-none');
    $('#admin_tiles_view').removeClass('d-none');
    $('#admin_breadcrumb_sub').addClass('d-none').text('');
}

(function() {
    var rawLoadFormDiv = window.load_form_div;
    window.load_form_div = function(ourl, xdiv, top_title) {
        if (xdiv === 'maindiv' && $('#admin_tiles_view').length > 0) {
            $('#admin_tiles_view').addClass('d-none');
            $('#admin_subpage_view').removeClass('d-none');
            if (top_title) {
                $('#admin_subpage_title').html('<i class="bi bi-gear me-1"></i> ' + top_title);
                $('#admin_breadcrumb_sub').removeClass('d-none').text(top_title);
            }
        }
        if (typeof rawLoadFormDiv === 'function') {
            rawLoadFormDiv(ourl, xdiv, top_title);
        }
    };
})();
</script>

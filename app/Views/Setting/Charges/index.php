<section class="content">
    <div class="pagetitle">
        <h1>Charges</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Dashboard</li>
                <li class="breadcrumb-item active">Charges</li>
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
        <div class="col-6 col-md-4 col-lg-3">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('item/search') ?>','maindiv','OPD Charge Master');">
                <i class="bi bi-capsule"></i>
                <span>OPD Charges</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('item-ipd/search') ?>','maindiv','IPD Charges');">
                <i class="bi bi-hospital"></i>
                <span>IPD Charges</span>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a class="admin-tile" href="javascript:load_form_div('<?= base_url('package/search') ?>','maindiv','Package IPD');">
                <i class="bi bi-collection"></i>
                <span>Package</span>
            </a>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12" id="maindiv"></div>
    </div>
</section>

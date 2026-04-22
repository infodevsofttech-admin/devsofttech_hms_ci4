<?= $this->include('storestock/_niceadmin_theme') ?>
<div class="storestock-ui">
<?php
$stats = $stats ?? [];
$lists = $lists ?? [];
$stockValue = (float) ($stats['stock_value'] ?? 0);
?>
<section class="content-header">
    <h1>Store Stock <small>Dashboard</small></h1>
</section>
<section class="content">
    <div class="module-hero">
        <h4>Hospital Stock Control Center</h4>
        <p>Track indents, monitor stock movement, and jump to master controls from one screen.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="label">Indents Today</span>
            <span class="value"><?= (int) ($stats['today_indents'] ?? 0) ?></span>
        </div>
        <div class="stat-card">
            <span class="label">Indents This Month</span>
            <span class="value"><?= (int) ($stats['month_indents'] ?? 0) ?></span>
        </div>
        <div class="stat-card">
            <span class="label">Low Stock Items</span>
            <span class="value"><?= (int) ($stats['low_stock_count'] ?? 0) ?></span>
        </div>
        <div class="stat-card">
            <span class="label">Near Expiry (60 Days)</span>
            <span class="value"><?= (int) ($stats['near_expiry_count'] ?? 0) ?></span>
        </div>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(2, minmax(180px, 1fr));">
        <div class="stat-card">
            <span class="label">Pending Purchase Invoices</span>
            <span class="value"><?= (int) ($stats['pending_purchase'] ?? 0) ?></span>
        </div>
        <div class="stat-card">
            <span class="label">Approx. Current Stock Value</span>
            <span class="value">Rs. <?= number_format($stockValue, 2) ?></span>
        </div>
    </div>

    <div class="action-grid">
        <a class="action-card" href="javascript:load_form_div('/Storestock/Indent_List','maindiv','Store : Indent');">
            <span class="icon"><i class="fa fa-shopping-cart"></i></span>
            <h5>Indent Desk</h5>
            <p>Create and process departmental item requests.</p>
        </a>
        <a class="action-card" href="javascript:load_form_div('/Storestock/Report_2','maindiv','Day Report :Store');">
            <span class="icon"><i class="fa fa-line-chart"></i></span>
            <h5>Day Report</h5>
            <p>Review daily issue trend and transaction summary.</p>
        </a>
        <a class="action-card" href="javascript:load_form_div('/Storestock/store_stock','maindiv','Store Stock : Store');">
            <span class="icon"><i class="fa fa-barcode"></i></span>
            <h5>Stock Search</h5>
            <p>Find real-time availability with reorder controls.</p>
        </a>
        <a class="action-card" href="javascript:load_form_div('/Storestock/main_store','maindiv','Store Main : Store');">
            <span class="icon"><i class="fa fa-cogs"></i></span>
            <h5>Store Controls</h5>
            <p>Open masters, inventory, and procurement tools.</p>
        </a>
    </div>

    <div class="box" style="margin-top:1rem;">
        <div class="box-header">
            <h3 class="box-title">Workspace</h3>
        </div>
        <div class="box-body">
            <div id="maindiv" class="row"></div>
        </div>
    </div>

    <div class="insight-grid">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Low Stock Alerts</h3>
            </div>
            <div class="box-body">
                <div class="table-wrap">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style="width:120px;">Current Unit</th>
                                <th style="width:140px;">Reorder Threshold</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $lowStockItems = $lists['low_stock_items'] ?? []; ?>
                            <?php if (empty($lowStockItems)) { ?>
                                <tr><td colspan="3" class="text-center text-muted">No low-stock items right now.</td></tr>
                            <?php } ?>
                            <?php foreach ($lowStockItems as $row) { ?>
                                <tr>
                                    <td><?= esc($row['item_name'] ?? '') ?></td>
                                    <td><?= esc($row['cur_units'] ?? 0) ?></td>
                                    <td><?= esc($row['re_order_qty'] ?? 0) ?> x <?= esc($row['packing'] ?? 1) ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Near Expiry Alerts</h3>
            </div>
            <div class="box-body">
                <div class="table-wrap">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Item / Batch</th>
                                <th style="width:100px;">Expiry</th>
                                <th style="width:90px;">Days Left</th>
                                <th style="width:95px;">Cur Packs</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $nearExpiryItems = $lists['near_expiry_items'] ?? []; ?>
                            <?php if (empty($nearExpiryItems)) { ?>
                                <tr><td colspan="4" class="text-center text-muted">No near-expiry batches in next 60 days.</td></tr>
                            <?php } ?>
                            <?php foreach ($nearExpiryItems as $row) { ?>
                                <tr>
                                    <td><?= esc($row['item_name'] ?? '') ?> <span class="text-muted">(<?= esc($row['batch_no'] ?? '-') ?>)</span></td>
                                    <td><?= esc($row['expiry_str'] ?? '-') ?></td>
                                    <td>
                                        <span class="mini-badge <?= ((int) ($row['days_left'] ?? 0)) <= 15 ? 'danger' : 'warn' ?>">
                                            <?= (int) ($row['days_left'] ?? 0) ?>
                                        </span>
                                    </td>
                                    <td><?= esc($row['cur_packs'] ?? 0) ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
</div>

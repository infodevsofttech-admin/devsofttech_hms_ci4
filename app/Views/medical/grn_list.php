<div class="pagetitle d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 text-primary"><i class="bi bi-box-seam"></i> Goods Received Notes (GRN)</h4>
        <small class="text-muted">Inward Stock Inspection &amp; Physical Receipt Verification</small>
    </div>
    <div class="d-flex gap-2">
        <a href="javascript:load_form_div('<?= base_url('Medical/main_store') ?>','medical-main','Store Main :Pharmacy');" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Store Main
        </a>
        <a href="javascript:load_form_div('<?= base_url('Medical/purchase_orders') ?>','medical-main','Purchase Orders :Pharmacy');" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-text"></i> Purchase Orders
        </a>
        <a href="javascript:load_form_div('<?= base_url('Medical/grn_new') ?>','medical-main','Create GRN :Pharmacy');" class="btn btn-success btn-sm">
            <i class="bi bi-plus-circle"></i> Create Inward GRN
        </a>
    </div>
</div>

<div class="card mb-3 shadow-sm border-0">
    <div class="card-body p-3">
        <form method="get" id="grn-filter-form" class="row g-2 align-items-center" onsubmit="event.preventDefault(); applyGrnFilter();">
            <div class="col-md-3">
                <input type="text" id="grn_search_text" class="form-control form-control-sm" placeholder="Search GRN #, Bill #, PO #..." value="<?= esc($search ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select id="grn_filter_supplier" class="form-select form-select-sm">
                    <option value="0">All Suppliers</option>
                    <?php if (!empty($suppliers)): ?>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= (int)$s['sid'] ?>" <?= ($supplierId == $s['sid']) ? 'selected' : '' ?>>
                                <?= esc($s['name_supplier']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select id="grn_filter_status" class="form-select form-select-sm">
                    <option value="all">All Statuses</option>
                    <option value="draft" <?= ($status === 'draft') ? 'selected' : '' ?>>Draft</option>
                    <option value="verified" <?= ($status === 'verified') ? 'selected' : '' ?>>Verified</option>
                    <option value="posted_to_inventory" <?= ($status === 'posted_to_inventory') ? 'selected' : '' ?>>Posted to Live Stock</option>
                    <option value="cancelled" <?= ($status === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" id="grn_filter_from" class="form-control form-control-sm" value="<?= esc($fromDate ?? '') ?>" title="From Date">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-dark btn-sm w-100"><i class="bi bi-funnel"></i> Filter</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetGrnFilter()"><i class="bi bi-arrow-counterclockwise"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0" id="grn-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>GRN Number</th>
                        <th>Supplier / Vendor</th>
                        <th>Supplier Bill / Challan</th>
                        <th>GRN Date</th>
                        <th>Linked PO</th>
                        <th class="text-end">Net Amount</th>
                        <th class="text-center">Status</th>
                        <th class="text-end" style="min-width: 170px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($grnList)): ?>
                        <?php foreach ($grnList as $idx => $row): ?>
                            <?php
                                $statusBadge = 'bg-secondary';
                                if ($row['status'] === 'draft') $statusBadge = 'bg-warning text-dark';
                                elseif ($row['status'] === 'verified') $statusBadge = 'bg-info text-dark';
                                elseif ($row['status'] === 'posted_to_inventory') $statusBadge = 'bg-success';
                                elseif ($row['status'] === 'cancelled') $statusBadge = 'bg-danger';
                            ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <strong class="text-primary font-monospace"><?= esc($row['grn_number']) ?></strong>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= esc($row['name_supplier'] ?: 'Unknown Vendor') ?></div>
                                </td>
                                <td>
                                    <div><strong>Inv:</strong> <?= esc($row['supplier_invoice_no']) ?></div>
                                    <?php if (!empty($row['challan_no'])): ?>
                                        <small class="text-muted">Challan: <?= esc($row['challan_no']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($row['grn_date']) ? date('d-M-Y', strtotime($row['grn_date'])) : '-' ?></td>
                                <td>
                                    <?php if (!empty($row['po_number'])): ?>
                                        <span class="badge bg-light text-primary border"><?= esc($row['po_number']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Direct Intake</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold">₹<?= number_format((float)$row['net_amount'], 2) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $statusBadge ?> px-2 py-1 text-uppercase" style="font-size: 11px;">
                                        <?= str_replace('_', ' ', esc($row['status'])) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('Medical/grn_print/' . $row['grn_id']) ?>" target="_blank" class="btn btn-outline-secondary" title="Print GRN Slip">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-primary" onclick="load_form_div('<?= base_url('Medical/grn_detail/' . $row['grn_id']) ?>','medical-main','GRN Inspection Detail');" title="View Inspection Details">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <button type="button" class="btn btn-outline-warning" onclick="load_form_div('<?= base_url('Medical/grn_edit/' . $row['grn_id']) ?>','medical-main','Edit GRN Inspection');" title="Edit Inspection Details">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <?php if ($row['status'] === 'verified'): ?>
                                            <button type="button" class="btn btn-success" onclick="postGrnToStock(<?= (int)$row['grn_id'] ?>)" title="Post to Live Hospital Stock">
                                                <i class="bi bi-box-arrow-in-down"></i> Post Stock
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                No Goods Received Notes found. Click <strong>Create Inward GRN</strong> to record incoming material.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function applyGrnFilter() {
    var search = document.getElementById('grn_search_text').value;
    var supplier = document.getElementById('grn_filter_supplier').value;
    var status = document.getElementById('grn_filter_status').value;
    var from = document.getElementById('grn_filter_from').value;

    var url = '<?= base_url('Medical/grn_list') ?>?q=' + encodeURIComponent(search)
            + '&supplier_id=' + encodeURIComponent(supplier)
            + '&status=' + encodeURIComponent(status)
            + '&from=' + encodeURIComponent(from);

    load_form_div(url, 'medical-main', 'GRN Register :Pharmacy');
}

function resetGrnFilter() {
    load_form_div('<?= base_url('Medical/grn_list') ?>', 'medical-main', 'GRN Register :Pharmacy');
}

function postGrnToStock(grnId) {
    if (!confirm('Are you sure you want to verify and post this GRN into live pharmacy inventory? This will update physical stock quantities and supplier purchase ledgers.')) return;

    $.post('<?= base_url('Medical/grn_post_inventory/') ?>' + grnId, {}, function(data) {
        alert(data.message || 'Stock posted');
        if (data.status) applyGrnFilter();
    }, 'json').fail(function() {
        alert('Error posting GRN to inventory.');
    });
}
</script>

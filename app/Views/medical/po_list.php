<div class="pagetitle d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 text-primary"><i class="bi bi-file-earmark-text"></i> Pharmacy Purchase Orders (PO)</h4>
        <small class="text-muted">Procurement &amp; Supplier Requisitions</small>
    </div>
    <div class="d-flex gap-2">
        <a href="javascript:load_form_div('<?= base_url('Medical/main_store') ?>','medical-main','Store Main :Pharmacy');" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Store Main
        </a>
        <a href="javascript:load_form_div('<?= base_url('Medical/grn_list') ?>','medical-main','GRN Register :Pharmacy');" class="btn btn-outline-info btn-sm">
            <i class="bi bi-box-seam"></i> GRN Register
        </a>
        <a href="javascript:load_form_div('<?= base_url('Medical/po_new') ?>','medical-main','New Purchase Order :Pharmacy');" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Create New PO
        </a>
    </div>
</div>

<div class="card mb-3 shadow-sm border-0">
    <div class="card-body p-3">
        <form method="get" id="po-filter-form" class="row g-2 align-items-center" onsubmit="event.preventDefault(); applyPoFilter();">
            <div class="col-md-3">
                <input type="text" id="po_search_text" class="form-control form-control-sm" placeholder="Search PO # or Supplier..." value="<?= esc($search ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select id="po_filter_supplier" class="form-select form-select-sm">
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
                <select id="po_filter_status" class="form-select form-select-sm">
                    <option value="all">All Statuses</option>
                    <option value="draft" <?= ($status === 'draft') ? 'selected' : '' ?>>Draft</option>
                    <option value="ordered" <?= ($status === 'ordered') ? 'selected' : '' ?>>Ordered / Approved</option>
                    <option value="partially_received" <?= ($status === 'partially_received') ? 'selected' : '' ?>>Partially Received</option>
                    <option value="received" <?= ($status === 'received') ? 'selected' : '' ?>>Received / Completed</option>
                    <option value="cancelled" <?= ($status === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" id="po_filter_from" class="form-control form-control-sm" value="<?= esc($fromDate ?? '') ?>" title="From Date">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-dark btn-sm w-100"><i class="bi bi-funnel"></i> Filter</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetPoFilter()"><i class="bi bi-arrow-counterclockwise"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0" id="po-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>PO Number</th>
                        <th>Supplier</th>
                        <th>PO Date</th>
                        <th>Delivery Due</th>
                        <th class="text-center">Items</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-center">Status</th>
                        <th class="text-end" style="min-width: 170px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $idx => $row): ?>
                            <?php
                                $statusBadge = 'bg-secondary';
                                if ($row['status'] === 'draft') $statusBadge = 'bg-warning text-dark';
                                elseif ($row['status'] === 'ordered' || $row['status'] === 'approved') $statusBadge = 'bg-primary';
                                elseif ($row['status'] === 'partially_received') $statusBadge = 'bg-info text-dark';
                                elseif ($row['status'] === 'received') $statusBadge = 'bg-success';
                                elseif ($row['status'] === 'cancelled') $statusBadge = 'bg-danger';
                            ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <strong class="text-primary font-monospace"><?= esc($row['po_number']) ?></strong>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= esc($row['name_supplier'] ?: 'Unknown Vendor') ?></div>
                                    <small class="text-muted"><?= esc($row['supplier_phone'] ?: '') ?></small>
                                </td>
                                <td><?= !empty($row['po_date']) ? date('d-M-Y', strtotime($row['po_date'])) : '-' ?></td>
                                <td><?= !empty($row['expected_delivery_date']) ? date('d-M-Y', strtotime($row['expected_delivery_date'])) : '<span class="text-muted">-</span>' ?></td>
                                <td class="text-center"><span class="badge bg-light text-dark border"><?= (int)$row['total_items'] ?></span></td>
                                <td class="text-end fw-bold">₹<?= number_format((float)$row['grand_total'] ?: (float)$row['total_estimated_amount'], 2) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $statusBadge ?> px-2 py-1 text-uppercase" style="font-size: 11px;">
                                        <?= str_replace('_', ' ', esc($row['status'])) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('Medical/po_print/' . $row['po_id']) ?>" target="_blank" class="btn btn-outline-secondary" title="Print PO PDF">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <?php if ($row['status'] === 'draft'): ?>
                                            <button type="button" class="btn btn-outline-primary" onclick="load_form_div('<?= base_url('Medical/po_edit/' . $row['po_id']) ?>','medical-main','Edit PO');" title="Edit Draft">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-success" onclick="updatePoStatus(<?= (int)$row['po_id'] ?>, 'ordered')" title="Approve &amp; Issue PO">
                                                <i class="bi bi-check2-circle"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($row['status'] === 'ordered' || $row['status'] === 'partially_received'): ?>
                                            <button type="button" class="btn btn-success" onclick="load_form_div('<?= base_url('Medical/grn_new/' . $row['po_id']) ?>','medical-main','Create GRN for PO');" title="Receive Goods (GRN)">
                                                <i class="bi bi-box-arrow-in-down"></i> Receive
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($row['status'] !== 'cancelled' && $row['status'] !== 'received'): ?>
                                            <button type="button" class="btn btn-outline-danger" onclick="cancelPo(<?= (int)$row['po_id'] ?>)" title="Cancel PO">
                                                <i class="bi bi-x-circle"></i>
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
                                No Purchase Orders found. Click <strong>Create New PO</strong> to draft your first procurement order.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function applyPoFilter() {
    var search = document.getElementById('po_search_text').value;
    var supplier = document.getElementById('po_filter_supplier').value;
    var status = document.getElementById('po_filter_status').value;
    var from = document.getElementById('po_filter_from').value;

    var url = '<?= base_url('Medical/purchase_orders') ?>?q=' + encodeURIComponent(search)
            + '&supplier_id=' + encodeURIComponent(supplier)
            + '&status=' + encodeURIComponent(status)
            + '&from=' + encodeURIComponent(from);

    load_form_div(url, 'medical-main', 'Purchase Orders :Pharmacy');
}

function resetPoFilter() {
    load_form_div('<?= base_url('Medical/purchase_orders') ?>', 'medical-main', 'Purchase Orders :Pharmacy');
}

function updatePoStatus(poId, newStatus) {
    if (!confirm('Are you sure you want to approve & issue this purchase order to the vendor?')) return;

    $.post('<?= base_url('Medical/po_status/') ?>' + poId, {
        status: newStatus
    }, function(data) {
        alert(data.message || 'Updated');
        if (data.status) applyPoFilter();
    }, 'json').fail(function() {
        alert('Error updating PO status.');
    });
}

function cancelPo(poId) {
    var reason = prompt('Please enter cancellation reason:');
    if (reason === null) return;

    $.post('<?= base_url('Medical/po_status/') ?>' + poId, {
        status: 'cancelled',
        reason: reason
    }, function(data) {
        alert(data.message || 'Cancelled');
        if (data.status) applyPoFilter();
    }, 'json').fail(function() {
        alert('Error cancelling PO.');
    });
}
</script>

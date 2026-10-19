<div class="pagetitle d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 text-success">
            <i class="bi bi-card-checklist"></i> GRN Inspection Details
            <span class="badge bg-light text-dark border ms-2 font-monospace"><?= esc($grn['grn_number']) ?></span>
        </h4>
        <small class="text-muted">Inward Goods Inspection &amp; Live Stock Posting</small>
    </div>
    <div class="d-flex gap-2">
        <a href="javascript:load_form_div('<?= base_url('Medical/grn_list') ?>','medical-main','GRN Register :Pharmacy');" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to GRN List
        </a>
        <a href="<?= base_url('Medical/grn_print/' . $grn['grn_id']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer"></i> Print Slip
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body p-3">
        <div class="row g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">Supplier / Vendor</small>
                <strong><?= esc($grn['name_supplier']) ?></strong>
            </div>
            <div class="col-md-2">
                <small class="text-muted d-block">Vendor Invoice #</small>
                <strong><?= esc($grn['supplier_invoice_no']) ?></strong>
            </div>
            <div class="col-md-2">
                <small class="text-muted d-block">Invoice Date</small>
                <span><?= date('d-M-Y', strtotime($grn['invoice_date'])) ?></span>
            </div>
            <div class="col-md-2">
                <small class="text-muted d-block">GRN Date</small>
                <span><?= date('d-M-Y', strtotime($grn['grn_date'])) ?></span>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Linked PO</small>
                <?php if (!empty($grn['po_number'])): ?>
                    <span class="badge bg-light text-primary border font-monospace"><?= esc($grn['po_number']) ?></span>
                <?php else: ?>
                    <span class="text-muted">Direct Intake</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($grn['remarks'])): ?>
            <div class="alert alert-light border mt-3 mb-0 py-2">
                <small><strong>Inspection Remarks:</strong> <?= esc($grn['remarks']) ?></small>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
        <strong class="text-secondary"><i class="bi bi-capsule"></i> Verified Medicine Items</strong>
        <span class="badge bg-<?= ($grn['status'] === 'posted_to_inventory') ? 'success' : 'info text-dark' ?> text-uppercase">
            <?= str_replace('_', ' ', esc($grn['status'])) ?>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Product Name</th>
                        <th>Batch No</th>
                        <th>Expiry Date</th>
                        <th class="text-center">Pack</th>
                        <th class="text-center">Rec. Packs</th>
                        <th class="text-center">Free</th>
                        <th class="text-center">Rejected</th>
                        <th class="text-end">Rate/Pack</th>
                        <th class="text-end">MRP</th>
                        <th class="text-center">GST %</th>
                        <th class="text-end">Line Total</th>
                        <th class="text-center">Live Stock ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($grnItems)): ?>
                        <?php foreach ($grnItems as $idx => $it): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><strong><?= esc($it['item_name']) ?></strong></td>
                                <td><span class="badge bg-light text-dark font-monospace border"><?= esc($it['batch_no']) ?></span></td>
                                <td><?= date('d-M-Y', strtotime($it['expiry_date'])) ?></td>
                                <td class="text-center"><?= (int)$it['packing'] ?>s</td>
                                <td class="text-center fw-bold"><?= (float)$it['received_packs'] ?></td>
                                <td class="text-center text-success"><?= (float)$it['free_packs'] ?></td>
                                <td class="text-center <?= ((float)$it['rejected_packs'] > 0) ? 'text-danger fw-bold' : 'text-muted' ?>">
                                    <?= (float)$it['rejected_packs'] ?>
                                    <?php if (!empty($it['rejection_reason'])): ?>
                                        <small class="d-block text-danger">(<?= esc($it['rejection_reason']) ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">₹<?= number_format((float)$it['unit_purchase_rate'], 2) ?></td>
                                <td class="text-end">₹<?= number_format((float)$it['mrp'], 2) ?></td>
                                <td class="text-center"><?= number_format((float)$it['cgst_percent'] * 2, 0) ?>%</td>
                                <td class="text-end fw-semibold">₹<?= number_format((float)$it['total_amount'], 2) ?></td>
                                <td class="text-center">
                                    <?php if (!empty($it['purchase_item_id'])): ?>
                                        <span class="badge bg-success font-monospace">Item #<?= (int)$it['purchase_item_id'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Pending Post</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="11" class="text-end">Taxable Amount:</th>
                        <th class="text-end">₹<?= number_format((float)$grn['total_received_amount'], 2) ?></th>
                        <th></th>
                    </tr>
                    <tr>
                        <th colspan="11" class="text-end">Total GST:</th>
                        <th class="text-end">₹<?= number_format((float)$grn['total_tax_amount'], 2) ?></th>
                        <th></th>
                    </tr>
                    <tr class="table-success">
                        <th colspan="11" class="text-end fs-6">Net Inward Bill Total:</th>
                        <th class="text-end fs-6 text-success fw-bold">₹<?= number_format((float)$grn['net_amount'], 2) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="card p-3 border-0 bg-light">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <?php if ($grn['status'] === 'posted_to_inventory'): ?>
                <span class="text-success fw-bold">
                    <i class="bi bi-check-circle-fill"></i> Posted to Live Store Inventory (Purchase Bill Ref #<?= (int)$grn['purchase_id'] ?>)
                </span>
            <?php else: ?>
                <span class="text-warning fw-bold">
                    <i class="bi bi-clock-history"></i> Verified by Pharmacist &bull; Ready to Post to Live Hospital Inventory
                </span>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2">
            <?php if ($grn['status'] !== 'posted_to_inventory'): ?>
                <button type="button" class="btn btn-success px-4" onclick="postGrnToLiveInventory(<?= (int)$grn['grn_id'] ?>)">
                    <i class="bi bi-box-arrow-in-down"></i> Post to Live Inventory
                </button>
            <?php else: ?>
                <a href="javascript:load_form_div('<?= base_url('Medical/PurchaseInvoiceEdit/' . (int)$grn['purchase_id']) ?>','medical-main');" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-receipt"></i> View Purchase Invoice
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function postGrnToLiveInventory(grnId) {
    if (!confirm('Confirm posting verified items into live pharmacy store inventory? This will generate live batches and update available stock.')) return;

    $.post('<?= base_url('Medical/grn_post_inventory/') ?>' + grnId, {}, function(data) {
        alert(data.message || 'Done');
        if (data.status) {
            load_form_div('<?= base_url('Medical/grn_edit/') ?>' + grnId, 'medical-main', 'GRN Inspection Detail');
        }
    }, 'json').fail(function() {
        alert('Error posting GRN.');
    });
}
</script>

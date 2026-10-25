<?php
$records = $records ?? [];
$fromDate = $from_date ?? date('Y-m-01');
$toDate = $to_date ?? date('Y-m-d');
$search = $search ?? '';
$totalUnits = (float) ($total_units ?? 0);
$totalLoss = (float) ($total_loss ?? 0);
?>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="card-title mb-0"><i class="fa fa-archive text-danger me-2"></i>Expired &amp; Damaged Medicine Disposal Register</h5>
            <small class="text-muted">Permanent Loss &amp; Destruction Audit Record (NABH / BMW Compliant)</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-sm btn-outline-danger" target="_blank" href="<?= base_url('Medical/disposal_register_pdf?from_date=' . urlencode($fromDate) . '&to_date=' . urlencode($toDate) . '&search=' . urlencode($search)) ?>">
                <i class="fa fa-print me-1"></i> Print Register (PDF)
            </a>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="load_form_div('<?= base_url('Medical/expire_stock') ?>','medical-main','Nearest Expiry Stock :Pharmacy');">
                <i class="fa fa-arrow-left me-1"></i> Back to Expiry Stock
            </button>
        </div>
    </div>

    <div class="card-body pt-3">
        <!-- Date & Keyword Filter -->
        <form id="disposal_filter_form" onsubmit="event.preventDefault(); applyDisposalFilter();" class="row g-2 align-items-end mb-3">
            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-semibold">From Date</label>
                <input type="date" class="form-control form-control-sm" id="disp_from_date" value="<?= esc($fromDate) ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-semibold">To Date</label>
                <input type="date" class="form-control form-control-sm" id="disp_to_date" value="<?= esc($toDate) ?>">
            </div>
            <div class="col-md-4 col-sm-8">
                <label class="form-label small fw-semibold">Search Medicine / Batch / Voucher</label>
                <input type="text" class="form-control form-control-sm" id="disp_search" placeholder="Type name, batch, voucher, supplier..." value="<?= esc($search) ?>">
            </div>
            <div class="col-md-2 col-sm-4 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa fa-filter me-1"></i> Filter</button>
                <button type="button" class="btn btn-light btn-sm border" onclick="resetDisposalFilter()" title="Reset"><i class="fa fa-refresh"></i></button>
            </div>
        </form>

        <!-- KPI Summary Cards -->
        <div class="row g-2 mb-3">
            <div class="col-md-4 col-12">
                <div class="border rounded p-2 bg-light">
                    <div class="small text-muted">Total Disposed Items</div>
                    <div class="h5 mb-0 text-dark fw-bold"><?= count($records) ?> <small class="text-muted fs-6 font-monospace">batches</small></div>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="border rounded p-2 bg-light">
                    <div class="small text-muted">Total Quantity Disposed / Lost</div>
                    <div class="h5 mb-0 text-danger fw-bold"><?= number_format($totalUnits, 2) ?> <small class="text-muted fs-6">units</small></div>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="border rounded p-2 bg-light">
                    <div class="small text-muted">Total Purchase Cost (Financial Loss)</div>
                    <div class="h5 mb-0 text-primary fw-bold">₹ <?= number_format($totalLoss, 2) ?></div>
                </div>
            </div>
        </div>

        <!-- Records Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-sm align-middle" style="font-size: 0.85rem;">
                <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Voucher No.</th>
                    <th>Date</th>
                    <th>Medicine Name</th>
                    <th>Batch</th>
                    <th>Exp. Date</th>
                    <th>Supplier</th>
                    <th class="text-end">Disposed Qty</th>
                    <th class="text-end">P. Rate</th>
                    <th class="text-end">Loss Value</th>
                    <th>Reason</th>
                    <th>Method</th>
                    <th>Disposed By</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="13" class="text-center py-4 text-muted">
                            <i class="fa fa-info-circle fa-2x mb-2 d-block text-secondary"></i>
                            No medicine disposal records found for the selected period.
                        </td>
                    </tr>
                <?php endif; ?>
                <?php $sr = 1; foreach ($records as $r): ?>
                    <tr>
                        <td><?= $sr++ ?></td>
                        <td>
                            <strong class="text-primary font-monospace"><?= esc($r->disposal_no ?? ('EXP#' . $r->id)) ?></strong>
                        </td>
                        <td><?= esc(date('d-m-Y', strtotime($r->disposal_date))) ?></td>
                        <td><strong><?= esc($r->item_name ?? '-') ?></strong></td>
                        <td><span class="badge bg-secondary font-monospace"><?= esc($r->batch_no ?? '-') ?></span></td>
                        <td><?= esc($r->expiry_date ? date('d-m-Y', strtotime($r->expiry_date)) : '-') ?></td>
                        <td><?= esc($r->supplier_name ?? '-') ?></td>
                        <td class="text-end fw-bold text-danger"><?= esc(number_format((float) ($r->qty ?? 0), 2)) ?></td>
                        <td class="text-end">₹<?= esc(number_format((float) ($r->purchase_unit_rate ?? 0), 2)) ?></td>
                        <td class="text-end fw-bold text-danger">₹<?= esc(number_format((float) ($r->total_cost_amount ?? 0), 2)) ?></td>
                        <td>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?= esc($r->disposal_reason ?? 'Expired') ?></span>
                        </td>
                        <td class="small text-muted"><?= esc($r->disposal_method ?? '-') ?></td>
                        <td class="small"><i class="fa fa-user-circle text-muted me-1"></i><?= esc($r->disposed_by_name ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <?php if (! empty($records)): ?>
                <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="7" class="text-end">Total Summary:</td>
                    <td class="text-end text-danger"><?= number_format($totalUnits, 2) ?></td>
                    <td></td>
                    <td class="text-end text-danger">₹<?= number_format($totalLoss, 2) ?></td>
                    <td colspan="3"></td>
                </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
function applyDisposalFilter() {
    var fDate = $('#disp_from_date').val();
    var tDate = $('#disp_to_date').val();
    var q = encodeURIComponent($('#disp_search').val() || '');
    var url = '<?= base_url('Medical/disposal_register') ?>?from_date=' + fDate + '&to_date=' + tDate + '&search=' + q;
    load_form_div(url, 'medical-main', 'Expired Medicine Disposal Register :Pharmacy');
}

function resetDisposalFilter() {
    var url = '<?= base_url('Medical/disposal_register') ?>';
    load_form_div(url, 'medical-main', 'Expired Medicine Disposal Register :Pharmacy');
}
</script>

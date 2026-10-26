<div class="card shadow-sm border-0">
    <div class="card-body pt-3">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 pb-2 border-bottom">
            <div>
                <h5 class="card-title mb-0 text-primary fw-bold">
                    <i class="bi bi-clipboard2-check-fill me-1"></i> Physical Stock Audit &amp; Reconciliation
                </h5>
                <small class="text-muted">Compare System Book Stock vs Floor Physical Count from Main Pharmacy Mobile App</small>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('Medical/mobile') ?>" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm fw-bold">
                    <i class="bi bi-phone me-1"></i> Launch Mobile Audit &amp; Picker
                </a>
                <button type="button" class="btn btn-sm btn-primary shadow-sm" onclick="window.print();">
                    <i class="bi bi-printer me-1"></i> Print Report
                </button>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="p-2 border rounded bg-light text-center">
                    <div class="text-muted small">Total Audits</div>
                    <div class="fs-5 fw-bold text-dark"><?= number_format($totalAudited ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 border rounded bg-success-subtle text-center text-success">
                    <div class="small">100% Matched</div>
                    <div class="fs-5 fw-bold"><?= number_format($totalMatched ?? 0) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 border rounded bg-danger-subtle text-center text-danger">
                    <div class="small">Shortage / Damaged</div>
                    <div class="fs-5 fw-bold"><?= number_format($totalShortageUnits ?? 0) ?> <small style="font-size:11px;">units</small></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 border rounded bg-warning-subtle text-center text-dark">
                    <div class="small">Pending Reconciliation</div>
                    <div class="fs-5 fw-bold text-warning-emphasis"><?= number_format($pendingCount ?? 0) ?></div>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="get" action="<?= base_url('Medical/stock_audit_report') ?>" class="row g-2 mb-3 align-items-end bg-light p-2 rounded border">
            <div class="col-6 col-md-3">
                <label class="form-label form-label-sm mb-1 small fw-bold">From Date</label>
                <input type="date" name="from_date" value="<?= esc($fromDate ?? date('Y-m-01')) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label form-label-sm mb-1 small fw-bold">To Date</label>
                <input type="date" name="to_date" value="<?= esc($toDate ?? date('Y-m-d')) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1 small fw-bold">Audit Type</label>
                <select name="audit_type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="PHYSICAL_VARIATION" <?= ($auditType ?? '') === 'PHYSICAL_VARIATION' ? 'selected' : '' ?>>Count Difference</option>
                    <option value="DAMAGED" <?= ($auditType ?? '') === 'DAMAGED' ? 'selected' : '' ?>>Damaged / Broken</option>
                    <option value="EXPIRED" <?= ($auditType ?? '') === 'EXPIRED' ? 'selected' : '' ?>>Expired on Shelf</option>
                    <option value="SHORTAGE" <?= ($auditType ?? '') === 'SHORTAGE' ? 'selected' : '' ?>>Shortage / Missing</option>
                    <option value="SURPLUS" <?= ($auditType ?? '') === 'SURPLUS' ? 'selected' : '' ?>>Found Surplus</option>
                    <option value="OK" <?= ($auditType ?? '') === 'OK' ? 'selected' : '' ?>>Matched (OK)</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label form-label-sm mb-1 small fw-bold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="PENDING" <?= ($status ?? '') === 'PENDING' ? 'selected' : '' ?>>Pending Approval</option>
                    <option value="RECONCILED" <?= ($status ?? '') === 'RECONCILED' ? 'selected' : '' ?>>Reconciled</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
                <a href="<?= base_url('Medical/stock_audit_report') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>

        <!-- Audit Table -->
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover align-middle" id="stock-audit-table" style="font-size: 13px;">
                <thead class="table-dark">
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Medicine Name</th>
                        <th>Batch / Exp</th>
                        <th>Rack &amp; Shelf</th>
                        <th class="text-center">System Qty</th>
                        <th class="text-center">Physical Qty</th>
                        <th class="text-center">Variance (+/-)</th>
                        <th>Audit Reason &amp; Remarks</th>
                        <th>Audited By</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($audits)): ?>
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">
                                <i class="bi bi-clipboard-x fs-3 d-block mb-1"></i>
                                No stock audits recorded for this date range. Launch the Mobile App to audit shelves.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($audits as $a): 
                            $var = (int)($a['variation_qty'] ?? 0);
                            $isReconciled = ($a['reconciled_status'] ?? '') === 'RECONCILED';
                        ?>
                            <tr id="audit_row_<?= (int)$a['audit_id'] ?>">
                                <td>
                                    <strong><?= !empty($a['created_at']) ? date('d-m-Y', strtotime($a['created_at'])) : '-' ?></strong>
                                    <div class="text-muted" style="font-size:10px;"><?= !empty($a['created_at']) ? date('h:i A', strtotime($a['created_at'])) : '' ?></div>
                                </td>
                                <td>
                                    <strong><?= esc($a['item_name'] ?? 'Item #' . ($a['item_id'] ?? '')) ?></strong>
                                    <?php if (!empty($a['formulation'])): ?>
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size:10px;"><?= esc($a['formulation']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code><?= esc($a['batch_no'] ?? '-') ?></code>
                                    <?php if (!empty($a['expiry_date'])): ?>
                                        <div class="text-muted" style="font-size:11px;"><?= date('m/Y', strtotime($a['expiry_date'])) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($a['rack_no']) || !empty($a['shelf_no'])): ?>
                                        <span class="badge bg-info-subtle text-info-emphasis">
                                            R: <?= esc($a['rack_no'] ?: '-') ?> | S: <?= esc($a['shelf_no'] ?: '-') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold"><?= (int)($a['system_qty'] ?? 0) ?></td>
                                <td class="text-center fw-bold text-primary"><?= (int)($a['physical_qty'] ?? 0) ?></td>
                                <td class="text-center">
                                    <?php if ($var === 0): ?>
                                        <span class="badge bg-success">0 (Match)</span>
                                    <?php elseif ($var < 0): ?>
                                        <span class="badge bg-danger"><?= $var ?> Shortage</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">+<?= $var ?> Surplus</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= esc($a['audit_type'] ?? 'AUDIT') ?></span>
                                    <?php if (!empty($a['remarks'])): ?>
                                        <div class="text-muted small mt-1"><?= esc($a['remarks']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= esc($a['conducted_by_name'] ?? 'Staff') ?></div>
                                </td>
                                <td class="text-center" id="status_cell_<?= (int)$a['audit_id'] ?>">
                                    <?php if ($isReconciled): ?>
                                        <span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check-circle-fill"></i> Reconciled</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning">Pending Approval</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center" id="action_cell_<?= (int)$a['audit_id'] ?>">
                                    <?php if (!$isReconciled && $var !== 0): ?>
                                        <button type="button" class="btn btn-xs btn-outline-success py-0 px-2 fw-bold" onclick="reconcileStockAudit(<?= (int)$a['audit_id'] ?>, <?= $var ?>);" style="font-size: 11px;">
                                            <i class="bi bi-check2-all"></i> Adjust Stock
                                        </button>
                                    <?php elseif (!$isReconciled && $var === 0): ?>
                                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="reconcileStockAudit(<?= (int)$a['audit_id'] ?>, 0);" style="font-size: 11px;">
                                            Approve
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">Done</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function reconcileStockAudit(auditId, variation) {
    var msg = (variation !== 0) 
        ? "Are you sure you want to adjust central stock inventory by " + (variation > 0 ? ("+" + variation) : variation) + " units?"
        : "Approve this matched physical audit?";
        
    if (!confirm(msg)) {
        return;
    }

    jQuery.ajax({
        url: '<?= base_url('Medical/stock_audit_reconcile') ?>',
        type: 'POST',
        data: { audit_id: auditId },
        dataType: 'json',
        success: function(res) {
            if (res.status === 1) {
                jQuery('#status_cell_' + auditId).html('<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check-circle-fill"></i> Reconciled</span>');
                jQuery('#action_cell_' + auditId).html('<span class="text-muted small">Done</span>');
                alert(res.message);
            } else {
                alert(res.message || 'Error reconciling audit');
            }
        },
        error: function() {
            alert('Failed to connect to server. Please try again.');
        }
    });
}
</script>

<?php
$rows = $purchase_list ?? [];
$todayTs = strtotime(date('Y-m-d'));
$expirySummary = [
    'expired' => 0,
    'd30' => 0,
    'd60' => 0,
    'later' => 0,
];

foreach ($rows as $summaryRow) {
    $expiryTs = strtotime((string) ($summaryRow->expiry_date ?? ''));
    if ($expiryTs === false) {
        $expirySummary['later']++;
        continue;
    }

    $daysToExpiry = (int) floor(($expiryTs - $todayTs) / 86400);
    if ($daysToExpiry < 0) {
        $expirySummary['expired']++;
    } elseif ($daysToExpiry <= 30) {
        $expirySummary['d30']++;
    } elseif ($daysToExpiry <= 60) {
        $expirySummary['d60']++;
    } else {
        $expirySummary['later']++;
    }
}
?>

<div class="card">
    <div class="card-body pt-3">
        <!-- Top Header & Action Buttons -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
            <div>
                <h5 class="card-title mb-0">Nearest Expiry / Expired Medicine Stock</h5>
                <small class="text-muted">Monitor, Dispose or Return Expired &amp; Near-Expiry Medicine Batches</small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="load_form_div('<?= base_url('Medical/disposal_register') ?>','medical-main','Disposal Register :Pharmacy');">
                    <i class="fa fa-archive me-1"></i> Disposal Register
                </button>
                <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= base_url('Medical/expire_stock_pdf') ?>">
                    <i class="fa fa-print me-1"></i> Print (PDF)
                </a>
                <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('Medical/master') ?>','medical-main','Master :Pharmacy');">
                    <i class="fa fa-arrow-left me-1"></i> Back to Master
                </a>
            </div>
        </div>

        <!-- Interactive Category Filters & Search -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="btn-group btn-group-sm" role="group" id="expiry_filter_buttons">
                <button type="button" class="btn btn-dark active" onclick="filterExpiryTable('all', this)">
                    All <span class="badge bg-light text-dark ms-1" id="badge_count_all"><?= count($rows) ?></span>
                </button>
                <button type="button" class="btn btn-outline-danger" onclick="filterExpiryTable('expired', this)">
                    Expired <span class="badge bg-danger ms-1" id="badge_count_expired"><?= esc((string) ($expirySummary['expired'] ?? 0)) ?></span>
                </button>
                <button type="button" class="btn btn-outline-warning text-dark" onclick="filterExpiryTable('d30', this)">
                    Expiring in 30 days <span class="badge bg-warning text-dark ms-1" id="badge_count_d30"><?= esc((string) ($expirySummary['d30'] ?? 0)) ?></span>
                </button>
                <button type="button" class="btn btn-outline-info text-dark" onclick="filterExpiryTable('d60', this)">
                    Expiring in 2 months <span class="badge bg-info text-dark ms-1" id="badge_count_d60"><?= esc((string) ($expirySummary['d60'] ?? 0)) ?></span>
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="filterExpiryTable('later', this)">
                    Above 2 months <span class="badge bg-secondary ms-1" id="badge_count_later"><?= esc((string) ($expirySummary['later'] ?? 0)) ?></span>
                </button>
            </div>
            <div style="min-width: 260px;">
                <input type="text" id="expiry_table_search" class="form-control form-control-sm" placeholder="🔍 Quick search item, supplier, batch..." onkeyup="searchExpiryTable()">
            </div>
        </div>

        <!-- 4 KPI Summary Cards (Clickable) -->
        <div class="row g-2 mb-3">
            <div class="col-md-3 col-6">
                <div class="border rounded p-2 h-100 bg-light" style="cursor: pointer;" onclick="triggerCardFilter('expired')" title="Click to view Expired medicines">
                    <div class="small text-muted d-flex justify-content-between">
                        <span>Expired</span>
                        <i class="fa fa-filter text-danger"></i>
                    </div>
                    <div class="h6 mb-0 text-danger fw-bold" id="card_count_expired"><?= esc((string) ($expirySummary['expired'] ?? 0)) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="border rounded p-2 h-100 bg-light" style="cursor: pointer;" onclick="triggerCardFilter('d30')" title="Click to view Expiring in 30 days">
                    <div class="small text-muted d-flex justify-content-between">
                        <span>Expiring in 30 days</span>
                        <i class="fa fa-filter text-warning"></i>
                    </div>
                    <div class="h6 mb-0 text-warning fw-bold" id="card_count_d30"><?= esc((string) ($expirySummary['d30'] ?? 0)) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="border rounded p-2 h-100 bg-light" style="cursor: pointer;" onclick="triggerCardFilter('d60')" title="Click to view Expiring in 2 months">
                    <div class="small text-muted d-flex justify-content-between">
                        <span>Expiring in 2 months</span>
                        <i class="fa fa-filter text-info"></i>
                    </div>
                    <div class="h6 mb-0 text-info fw-bold" id="card_count_d60"><?= esc((string) ($expirySummary['d60'] ?? 0)) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="border rounded p-2 h-100 bg-light" style="cursor: pointer;" onclick="triggerCardFilter('later')" title="Click to view Above 2 months">
                    <div class="small text-muted d-flex justify-content-between">
                        <span>Above 2 months</span>
                        <i class="fa fa-filter text-secondary"></i>
                    </div>
                    <div class="h6 mb-0 text-dark fw-bold" id="card_count_later"><?= esc((string) ($expirySummary['later'] ?? 0)) ?></div>
                </div>
            </div>
        </div>

        <!-- Expiry Stock Table -->
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-sm align-middle" id="table_expiry_stock">
                <thead>
                <tr class="table-light">
                    <th>#</th>
                    <th>Item</th>
                    <th>Supplier</th>
                    <th>Pur. Date</th>
                    <th>Batch</th>
                    <th>Exp. Dt</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">C.Qty</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 170px;">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr id="row_empty_state"><td colspan="11" class="text-center text-muted py-4">No near-expiry or expired medicines found in stock.</td></tr>
                <?php endif; ?>
                <?php
                $srno = 1;
                foreach ($rows as $row):
                    $statusLabel = 'Above 2 months';
                    $statusClass = '';
                    $statusCat = 'later';

                    $expiryDateRaw = (string) ($row->expiry_date ?? '');
                    $expiryTs = strtotime($expiryDateRaw);
                    $daysToExpiry = null;

                    if ($expiryTs !== false) {
                        $daysToExpiry = (int) floor(($expiryTs - $todayTs) / 86400);

                        if ($daysToExpiry < 0) {
                            $statusLabel = 'Expired';
                            $statusClass = 'table-danger';
                            $statusCat = 'expired';
                        } elseif ($daysToExpiry <= 30) {
                            $statusLabel = 'Expiring in 30 days';
                            $statusClass = 'table-warning';
                            $statusCat = 'd30';
                        } elseif ($daysToExpiry <= 60) {
                            $statusLabel = 'Expiring in 2 months';
                            $statusClass = 'table-info';
                            $statusCat = 'd60';
                        }
                    }

                    $itemId = (int) ($row->purchase_item_id ?? $row->id ?? 0);
                    $itemName = (string) ($row->item_name ?? $row->Item_name ?? '');
                    $supplierName = (string) ($row->name_supplier ?? '-');
                    $batchNo = (string) ($row->batch_no ?? '');
                    $expDisplay = (string) ($row->exp_date ?? $row->exp_date_full ?? '');
                    $curQty = (float) ($row->cur_qty ?? 0);
                    $rate = (float) ($row->mrp ?? 0);
                    $purRate = (float) ($row->purchase_unit_rate ?? 0);

                    $itemMeta = [
                        'item_id' => $itemId,
                        'item_name' => $itemName,
                        'supplier_name' => $supplierName,
                        'sid' => (int) ($row->sid ?? 0),
                        'batch_no' => $batchNo,
                        'expiry_date' => $expiryDateRaw,
                        'exp_date_display' => $expDisplay,
                        'cur_qty' => $curQty,
                        'rate' => $rate,
                        'pur_rate' => $purRate,
                        'days_to_expiry' => $daysToExpiry,
                        'status_cat' => $statusCat,
                    ];

                    $searchText = strtolower($itemName . ' ' . $supplierName . ' ' . $batchNo . ' ' . $statusLabel);
                ?>
                    <tr class="expiry-data-row <?= esc($statusClass) ?>" 
                        id="row_exp_<?= $itemId ?>" 
                        data-status-category="<?= esc($statusCat) ?>" 
                        data-search-text="<?= esc($searchText) ?>"
                        data-cur-qty="<?= $curQty ?>">
                        <td><?= esc((string) $srno++) ?></td>
                        <td><strong><?= esc($itemName) ?></strong></td>
                        <td><?= esc($supplierName) ?></td>
                        <td><?= esc($row->str_date_of_invoice ?? '') ?></td>
                        <td><span class="badge bg-secondary font-monospace"><?= esc($batchNo) ?></span></td>
                        <td><?= esc($expDisplay) ?></td>
                        <td class="text-end"><?= esc(number_format($rate, 2)) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($row->tqty ?? 0), 2)) ?></td>
                        <td class="text-end fw-bold cur-qty-cell"><?= esc(number_format($curQty, 2)) ?></td>
                        <td>
                            <?= esc($statusLabel) ?>
                            <?php if ($daysToExpiry !== null): ?>
                                <span class="text-muted small">(<?= esc((string) $daysToExpiry) ?> days)</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 fw-semibold" 
                                onclick='openDisposeModal(<?= htmlspecialchars(json_encode($itemMeta), ENT_QUOTES, 'UTF-8') ?>)' 
                                title="Dispose / Write-Off Expired Medicine">
                                <i class="fa fa-trash-o me-1"></i> Dispose
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 ms-1 fw-semibold" 
                                onclick='openReturnModal(<?= htmlspecialchars(json_encode($itemMeta), ENT_QUOTES, 'UTF-8') ?>)' 
                                title="Return to Supplier (Create Return Invoice)">
                                <i class="fa fa-reply me-1"></i> Return
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- Modal 1: Dispose / Write-Off Expired Stock -->
<!-- ========================================== -->
<div class="modal fade" id="modal_dispose_stock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white py-2">
                <h6 class="modal-title mb-0"><i class="fa fa-trash-o me-2"></i>Dispose / Write-Off Expired Medicine</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border mb-3 p-2 small">
                    <div><strong>Medicine:</strong> <span id="disp_m_item_name" class="text-dark fw-bold"></span></div>
                    <div><strong>Batch:</strong> <span id="disp_m_batch" class="font-monospace"></span> | <strong>Exp. Date:</strong> <span id="disp_m_exp" class="text-danger fw-bold"></span></div>
                    <div><strong>Supplier:</strong> <span id="disp_m_supplier" class="text-muted"></span></div>
                    <div><strong>Available Stock:</strong> <span id="disp_m_avail" class="badge bg-secondary"></span> units</div>
                </div>

                <form id="form_dispose_stock" onsubmit="event.preventDefault(); submitStockDisposal();">
                    <input type="hidden" id="disp_item_id" value="0">
                    <input type="hidden" id="disp_max_qty" value="0">
                    <input type="hidden" id="disp_row_id" value="">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Disposal Quantity <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="any" min="0.01" class="form-control" id="disp_qty" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="$('#disp_qty').val($('#disp_max_qty').val())">Max (All)</button>
                        </div>
                        <div class="form-text small">Quantity to permanently remove from stock inventory (recorded under loss/disposal).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Reason for Disposal <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="disp_reason" required>
                            <option value="Expired / Outdated" selected>Expired / Outdated</option>
                            <option value="Damaged / Broken Ampoule or Vial">Damaged / Broken Ampoule or Vial</option>
                            <option value="Cold Chain Failure / Spoilage">Cold Chain Failure / Spoilage</option>
                            <option value="Opened / Contaminated">Opened / Contaminated</option>
                            <option value="Batch Quality Recall">Batch Quality Recall</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Destruction / Disposal Method</label>
                        <select class="form-select form-select-sm" id="disp_method">
                            <option value="Bio-Medical Waste Handover (Incineration)" selected>Bio-Medical Waste Handover (Incineration)</option>
                            <option value="Autoclave / Shredding">Autoclave / Shredding</option>
                            <option value="Chemical Treatment / Neutralization">Chemical Treatment / Neutralization</option>
                            <option value="Handover to Authorized Waste Agency">Handover to Authorized Waste Agency</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Remarks / Audit Note</label>
                        <input type="text" class="form-control form-control-sm" id="disp_remarks" placeholder="e.g. Expired on shelf, authorized by MS">
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="btn_confirm_dispose" onclick="submitStockDisposal()">
                    <i class="fa fa-trash me-1"></i> Confirm &amp; Remove from Stock
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- Modal 2: Return Medicine to Supplier       -->
<!-- ========================================== -->
<div class="modal fade" id="modal_return_stock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title mb-0"><i class="fa fa-reply me-2"></i>Return Medicine to Supplier</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border mb-3 p-2 small">
                    <div><strong>Medicine:</strong> <span id="ret_m_item_name" class="text-dark fw-bold"></span></div>
                    <div><strong>Batch:</strong> <span id="ret_m_batch" class="font-monospace"></span> | <strong>Exp. Date:</strong> <span id="ret_m_exp" class="text-primary fw-bold"></span></div>
                    <div><strong>Supplier:</strong> <span id="ret_m_supplier" class="badge bg-primary-subtle text-primary border border-primary-subtle"></span></div>
                    <div><strong>Available Stock:</strong> <span id="ret_m_avail" class="badge bg-secondary"></span> units</div>
                </div>

                <form id="form_return_stock" onsubmit="event.preventDefault(); submitStockReturn();">
                    <input type="hidden" id="ret_item_id" value="0">
                    <input type="hidden" id="ret_max_qty" value="0">

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Return Quantity <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="any" min="0.01" class="form-control" id="ret_qty" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="$('#ret_qty').val($('#ret_max_qty').val())">Max (All)</button>
                        </div>
                        <div class="form-text small">This will create or add to today's draft Purchase Return Invoice for this supplier.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="btn_confirm_return" onclick="submitStockReturn()">
                    <i class="fa fa-plus me-1"></i> Add &amp; Open Return Invoice
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var currentCategoryFilter = 'all';

function filterExpiryTable(category, btnElement) {
    currentCategoryFilter = category;
    $('#expiry_filter_buttons button').removeClass('active').addClass('btn-outline-danger btn-outline-warning btn-outline-info btn-outline-secondary');
    if (category === 'all') {
        $(btnElement).removeClass('btn-outline-secondary').addClass('btn-dark active');
    } else {
        $(btnElement).removeClass('btn-outline-danger btn-outline-warning btn-outline-info btn-outline-secondary').addClass('active');
    }

    applyCombinedFilters();
}

function triggerCardFilter(category) {
    var btn = $('#expiry_filter_buttons button[onclick*="' + category + '"]');
    if (btn.length) {
        filterExpiryTable(category, btn[0]);
    }
}

function searchExpiryTable() {
    applyCombinedFilters();
}

function applyCombinedFilters() {
    var search = ($('#expiry_table_search').val() || '').toLowerCase().trim();
    var visibleCount = 0;

    $('.expiry-data-row').each(function () {
        var $row = $(this);
        var rowCat = $row.data('status-category') || '';
        var rowText = $row.data('search-text') || '';

        var matchesCategory = (currentCategoryFilter === 'all' || rowCat === currentCategoryFilter);
        var matchesSearch = (search === '' || rowText.indexOf(search) !== -1);

        if (matchesCategory && matchesSearch) {
            $row.show();
            visibleCount++;
        } else {
            $row.hide();
        }
    });

    if (visibleCount === 0) {
        if ($('#row_no_matches').length === 0) {
            $('#table_expiry_stock tbody').append('<tr id="row_no_matches"><td colspan="11" class="text-center text-muted py-4">No matching medicines found for the selected filter.</td></tr>');
        }
        $('#row_no_matches').show();
    } else {
        $('#row_no_matches').hide();
    }
}

function openDisposeModal(item) {
    $('#disp_item_id').val(item.item_id);
    $('#disp_max_qty').val(item.cur_qty);
    $('#disp_row_id').val('row_exp_' + item.item_id);

    $('#disp_m_item_name').text(item.item_name);
    $('#disp_m_batch').text(item.batch_no || '-');
    $('#disp_m_exp').text(item.exp_date_display || item.expiry_date || '-');
    $('#disp_m_supplier').text(item.supplier_name || '-');
    $('#disp_m_avail').text(item.cur_qty);

    $('#disp_qty').val(item.cur_qty).attr('max', item.cur_qty);
    $('#disp_remarks').val('');
    $('#disp_reason').val('Expired / Outdated');

    var modal = new bootstrap.Modal(document.getElementById('modal_dispose_stock'));
    modal.show();
}

function submitStockDisposal() {
    var itemId = parseInt($('#disp_item_id').val() || '0', 10);
    var qty = parseFloat($('#disp_qty').val() || 0);
    var maxQty = parseFloat($('#disp_max_qty').val() || 0);
    var reason = $('#disp_reason').val();
    var method = $('#disp_method').val();
    var remarks = $('#disp_remarks').val();

    if (itemId <= 0 || qty <= 0) {
        notify('error', 'Please Attention', 'Please enter a valid disposal quantity');
        return;
    }
    if (qty > maxQty) {
        notify('error', 'Please Attention', 'Disposal quantity cannot exceed available stock (' + maxQty + ')');
        return;
    }

    var payload = {
        item_id: itemId,
        qty: qty,
        reason: reason,
        method: method,
        remarks: remarks
    };

    var csrfName = $('input[name="<?= csrf_token() ?>"]').attr('name');
    var csrfValue = $('input[name="<?= csrf_token() ?>"]').val();
    if (csrfName && csrfValue) {
        payload[csrfName] = csrfValue;
    }

    $('#btn_confirm_dispose').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Disposing...');

    $.post('<?= base_url('Medical/dispose_expired_stock') ?>', payload, function (data) {
        $('#btn_confirm_dispose').prop('disabled', false).html('<i class="fa fa-trash me-1"></i> Confirm &amp; Remove from Stock');

        if (!data || data.status !== 1) {
            notify('error', 'Please Attention', data && data.msg ? data.msg : 'Unable to dispose stock');
            return;
        }

        notify('success', 'Disposal Successful', data.msg);

        // Hide modal
        var modalEl = document.getElementById('modal_dispose_stock');
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }

        // Update row in table
        var rowId = $('#disp_row_id').val();
        var remainingQty = parseFloat(data.remaining_qty || 0);

        if (remainingQty <= 0) {
            $('#' + rowId).fadeOut(400, function () {
                $(this).remove();
                applyCombinedFilters();
            });
        } else {
            $('#' + rowId).find('.cur-qty-cell').text(remainingQty.toFixed(2));
            $('#' + rowId).data('cur-qty', remainingQty);
        }
    }, 'json').fail(function () {
        $('#btn_confirm_dispose').prop('disabled', false).html('<i class="fa fa-trash me-1"></i> Confirm &amp; Remove from Stock');
        notify('error', 'Error', 'Failed to communicate with server');
    });
}

function openReturnModal(item) {
    if (!item.sid || parseInt(item.sid, 10) <= 0) {
        notify('warning', 'Notice', 'No supplier linked with this batch. Cannot create supplier return invoice.');
        return;
    }

    $('#ret_item_id').val(item.item_id);
    $('#ret_max_qty').val(item.cur_qty);

    $('#ret_m_item_name').text(item.item_name);
    $('#ret_m_batch').text(item.batch_no || '-');
    $('#ret_m_exp').text(item.exp_date_display || item.expiry_date || '-');
    $('#ret_m_supplier').text(item.supplier_name || '-');
    $('#ret_m_avail').text(item.cur_qty);

    $('#ret_qty').val(item.cur_qty).attr('max', item.cur_qty);

    var modal = new bootstrap.Modal(document.getElementById('modal_return_stock'));
    modal.show();
}

function submitStockReturn() {
    var itemId = parseInt($('#ret_item_id').val() || '0', 10);
    var qty = parseFloat($('#ret_qty').val() || 0);
    var maxQty = parseFloat($('#ret_max_qty').val() || 0);

    if (itemId <= 0 || qty <= 0) {
        notify('error', 'Please Attention', 'Please enter a valid return quantity');
        return;
    }
    if (qty > maxQty) {
        notify('error', 'Please Attention', 'Return quantity cannot exceed available stock (' + maxQty + ')');
        return;
    }

    var payload = {
        item_id: itemId,
        qty: qty
    };

    var csrfName = $('input[name="<?= csrf_token() ?>"]').attr('name');
    var csrfValue = $('input[name="<?= csrf_token() ?>"]').val();
    if (csrfName && csrfValue) {
        payload[csrfName] = csrfValue;
    }

    $('#btn_confirm_return').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Creating Return...');

    $.post('<?= base_url('Medical/quick_create_purchase_return') ?>', payload, function (data) {
        $('#btn_confirm_return').prop('disabled', false).html('<i class="fa fa-plus me-1"></i> Add &amp; Open Return Invoice');

        if (!data || data.status !== 1) {
            notify('error', 'Please Attention', data && data.msg ? data.msg : 'Unable to create purchase return');
            return;
        }

        notify('success', 'Return Invoice Created', data.msg);

        // Hide modal
        var modalEl = document.getElementById('modal_return_stock');
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }

        // Open the purchase return invoice edit screen directly!
        if (data.inv_id) {
            load_form_div('<?= base_url('Medical_backpanel/PurchaseReturnInvoiceEdit') ?>/' + data.inv_id, 'medical-main', 'Purchase Return Invoice Edit');
        }
    }, 'json').fail(function () {
        $('#btn_confirm_return').prop('disabled', false).html('<i class="fa fa-plus me-1"></i> Add &amp; Open Return Invoice');
        notify('error', 'Error', 'Failed to communicate with server');
    });
}
</script>

<?php
    $isEdit = !empty($po);
    $formAction = $isEdit ? base_url('Medical/po_create') : base_url('Medical/po_create'); // we handle create / update
    $poNumber = $isEdit ? ($po['po_number'] ?? '') : ($nextPoNumber ?? 'PO-NEW');
    $supplierId = (int)($po['supplier_id'] ?? 0);
    $storeId = (int)($po['store_id'] ?? 1);
    $poDate = !empty($po['po_date']) ? $po['po_date'] : date('Y-m-d');
    $expDelivery = !empty($po['expected_delivery_date']) ? $po['expected_delivery_date'] : date('Y-m-d', strtotime('+7 days'));
    $paymentTerms = $po['payment_terms'] ?? 'Net 30 Days';
    $remarks = $po['remarks'] ?? '';
    $status = $po['status'] ?? 'ordered';
?>

<div class="pagetitle d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 text-primary">
            <i class="bi bi-file-earmark-plus"></i> <?= $isEdit ? 'Edit Purchase Order' : 'New Purchase Order' ?>
            <span class="badge bg-light text-dark border ms-2 font-monospace"><?= esc($poNumber) ?></span>
        </h4>
        <small class="text-muted">Procurement Requisition &amp; Supplier Order</small>
    </div>
    <div>
        <a href="javascript:load_form_div('<?= base_url('Medical/purchase_orders') ?>','medical-main','Purchase Orders :Pharmacy');" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to PO List
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-light py-2">
        <strong class="text-secondary"><i class="bi bi-info-circle"></i> Purchase Order Details</strong>
    </div>
    <div class="card-body p-3">
        <form id="po-create-form" onsubmit="event.preventDefault(); submitPoForm();">
            <?= csrf_field() ?>
            <?php if ($isEdit): ?>
                <input type="hidden" name="po_id" id="po_id" value="<?= (int)($po['po_id'] ?? 0) ?>">
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label form-label-sm fw-semibold">Supplier / Pharmaceutical Vendor <span class="text-danger">*</span></label>
                    <select name="supplier_id" id="po_supplier_id" class="form-select form-select-sm" required onchange="handleSupplierChange(this.value);">
                        <option value="">-- Select Supplier --</option>
                        <?php if (!empty($suppliers)): ?>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= (int)$s['sid'] ?>" <?= ($supplierId == $s['sid']) ? 'selected' : '' ?>>
                                    <?= esc($s['name_supplier']) ?> <?= !empty($s['contact_no']) ? '(' . esc($s['contact_no']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm fw-semibold">PO Date</label>
                    <input type="date" name="po_date" id="po_date" class="form-control form-control-sm" value="<?= esc($poDate) ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm fw-semibold">Expected Delivery</label>
                    <input type="date" name="expected_delivery_date" id="expected_delivery_date" class="form-control form-control-sm" value="<?= esc($expDelivery) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm fw-semibold">Payment Terms</label>
                    <select name="payment_terms" id="payment_terms" class="form-select form-select-sm">
                        <option value="Net 30 Days" <?= ($paymentTerms === 'Net 30 Days') ? 'selected' : '' ?>>Net 30 Days</option>
                        <option value="Net 15 Days" <?= ($paymentTerms === 'Net 15 Days') ? 'selected' : '' ?>>Net 15 Days</option>
                        <option value="Net 45 Days" <?= ($paymentTerms === 'Net 45 Days') ? 'selected' : '' ?>>Net 45 Days</option>
                        <option value="Cash On Delivery (COD)" <?= ($paymentTerms === 'Cash On Delivery (COD)') ? 'selected' : '' ?>>Cash On Delivery (COD)</option>
                        <option value="Advance" <?= ($paymentTerms === 'Advance') ? 'selected' : '' ?>>Advance</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm fw-semibold">Initial Status</label>
                    <select name="status" id="po_status" class="form-select form-select-sm">
                        <option value="ordered" <?= ($status === 'ordered') ? 'selected' : '' ?>>Approved &amp; Issued</option>
                        <option value="draft" <?= ($status === 'draft') ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label form-label-sm fw-semibold">Notes / Special Instructions</label>
                    <input type="text" name="remarks" id="po_remarks" class="form-control form-control-sm" placeholder="e.g., Deliver to Main Pharmacy Warehouse before 5 PM" value="<?= esc($remarks) ?>">
                </div>
            </div>

            <!-- Item Entry Panel -->
            <div class="card border-primary mt-3">
                <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-capsule"></i> Add Items to Purchase Order</span>
                    <button type="button" class="btn btn-warning btn-sm py-0" id="btn-reorder-fetch" onclick="fetchSupplierReorderItems();" style="display:none;">
                        <i class="bi bi-lightning-charge"></i> Auto-Add Low Stock Items for this Vendor
                    </button>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 align-items-end mb-2">
                        <div class="col-md-5">
                            <label class="form-label form-label-sm mb-1 fw-semibold">Search Medicine / Drug Master</label>
                            <input type="text" id="quick_drug_search" class="form-control form-control-sm" placeholder="Type medicine brand name or generic salt..." autocomplete="off">
                            <input type="hidden" id="selected_item_id" value="">
                            <input type="hidden" id="selected_item_name" value="">
                            <input type="hidden" id="selected_generic_name" value="">
                            <input type="hidden" id="selected_category" value="">
                            <input type="hidden" id="selected_gst_rate" value="12">
                            <input type="hidden" id="selected_discount_percent" value="0">
                            <input type="hidden" id="selected_sch_disc_per" value="0">
                            <input type="hidden" id="selected_scheme_info" value="">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm mb-1 fw-semibold">Pack Size (Units)</label>
                            <input type="number" id="quick_units_per_pack" class="form-control form-control-sm text-center" value="10" min="1" step="1">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm mb-1 fw-semibold">Order Packs</label>
                            <input type="number" id="quick_order_packs" class="form-control form-control-sm text-center" value="10" min="1" step="1">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm mb-1 fw-semibold">Est. Rate / Pack (₹)</label>
                            <input type="number" id="quick_ptr" class="form-control form-control-sm text-end" value="0.00" min="0" step="0.01">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-primary btn-sm w-100" id="btn-add-line" onclick="addItemToPoTable();" disabled>
                                <i class="bi bi-plus-lg"></i> Add
                            </button>
                        </div>
                    </div>

                    <!-- Selected Drug History Pill -->
                    <div id="selected_drug_info_pill" class="mb-3" style="display: none;"></div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0" id="po-items-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 35px;">#</th>
                                    <th>Product Name</th>
                                    <th>Generic / Salt</th>
                                    <th style="width: 85px;">Formulation</th>
                                    <th class="text-center" style="width: 85px;">Units/Pack</th>
                                    <th class="text-center" style="width: 95px;">Order Packs</th>
                                    <th class="text-end" style="width: 105px;">Est. Rate (₹)</th>
                                    <th class="text-center" style="width: 130px;">Scheme / Disc</th>
                                    <th class="text-center" style="width: 95px;">GST %</th>
                                    <th class="text-end" style="width: 115px;">Line Total (₹)</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="po-items-body">
                                <?php if (!empty($poItems)): ?>
                                    <?php foreach ($poItems as $idx => $it): ?>
                                        <?php
                                            $itGst = (float)($it['gst_rate'] ?? 12);
                                            $discPer = (float)($it['discount_percent'] ?? 0);
                                            $schDiscPer = (float)($it['sch_disc_per'] ?? 0);
                                            $schInfo = (string)($it['scheme_info'] ?? '');
                                        ?>
                                        <tr data-item-id="<?= (int)$it['item_id'] ?>">
                                            <td class="row-num"><?= $idx + 1 ?></td>
                                            <td>
                                                <strong><?= esc($it['item_name']) ?></strong>
                                                <input type="hidden" name="items[<?= $idx ?>][item_id]" value="<?= (int)$it['item_id'] ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][item_name]" value="<?= esc($it['item_name']) ?>">
                                            </td>
                                            <td>
                                                <?= esc($it['generic_name'] ?: '-') ?>
                                                <input type="hidden" name="items[<?= $idx ?>][generic_name]" value="<?= esc($it['generic_name']) ?>">
                                            </td>
                                            <td>
                                                <?= esc($it['category'] ?: 'Tablet') ?>
                                                <input type="hidden" name="items[<?= $idx ?>][category]" value="<?= esc($it['category']) ?>">
                                            </td>
                                            <td class="text-center">
                                                <input type="number" name="items[<?= $idx ?>][units_per_pack]" class="form-control form-control-sm text-center line-units px-1" value="<?= (int)$it['units_per_pack'] ?>" min="1">
                                            </td>
                                            <td class="text-center">
                                                <input type="number" name="items[<?= $idx ?>][order_packs]" class="form-control form-control-sm text-center line-packs px-1" value="<?= (float)$it['order_packs'] ?>" min="1" onchange="recalculatePoTotals();">
                                            </td>
                                            <td class="text-end">
                                                <input type="number" name="items[<?= $idx ?>][estimated_ptr]" class="form-control form-control-sm text-end line-rate px-1" value="<?= number_format((float)$it['estimated_ptr'], 2, '.', '') ?>" min="0" step="0.01" onchange="recalculatePoTotals();">
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex flex-column gap-1">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text py-0 px-1 bg-light text-muted" style="font-size:10px;">Disc%</span>
                                                        <input type="number" name="items[<?= $idx ?>][discount_percent]" class="form-control form-control-sm text-end line-disc px-1" value="<?= $discPer > 0 ? number_format($discPer, 2, '.', '') : '0' ?>" min="0" max="100" step="0.01" onchange="recalculatePoTotals();" style="font-size:11px;">
                                                    </div>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text py-0 px-1 bg-light text-muted" style="font-size:10px;">Sch</span>
                                                        <input type="text" name="items[<?= $idx ?>][scheme_info]" class="form-control form-control-sm line-scheme px-1" value="<?= esc($schInfo) ?>" placeholder="e.g. 10+1" style="font-size:11px;">
                                                    </div>
                                                    <input type="hidden" name="items[<?= $idx ?>][sch_disc_per]" class="line-sch-per" value="<?= $schDiscPer ?>">
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <select name="items[<?= $idx ?>][gst_rate]" class="form-select form-select-sm text-center line-gst px-1" onchange="recalculatePoTotals();">
                                                    <option value="0" <?= ((float)$itGst == 0) ? 'selected' : '' ?>>0%</option>
                                                    <option value="5" <?= (abs((float)$itGst - 5) < 0.1) ? 'selected' : '' ?>>5%</option>
                                                    <option value="12" <?= (abs((float)$itGst - 12) < 0.1) ? 'selected' : '' ?>>12%</option>
                                                    <option value="18" <?= (abs((float)$itGst - 18) < 0.1) ? 'selected' : '' ?>>18%</option>
                                                    <option value="28" <?= (abs((float)$itGst - 28) < 0.1) ? 'selected' : '' ?>>28%</option>
                                                    <?php if (!in_array((float)$itGst, [0, 5, 12, 18, 28])): ?>
                                                        <option value="<?= (float)$itGst ?>" selected><?= (float)$itGst ?>%</option>
                                                    <?php endif; ?>
                                                </select>
                                            </td>
                                            <td class="text-end fw-semibold line-total-display">
                                                ₹<?= number_format((float)$it['estimated_amount'], 2) ?>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-outline-danger btn-sm py-0" onclick="removePoRow(this);">✖</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr id="no-items-row">
                                        <td colspan="11" class="text-center py-4 text-muted">
                                            No items added yet. Search above to add items or auto-load low stock items.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="9" class="text-end">Estimated Subtotal (Taxable):</th>
                                    <th class="text-end font-monospace" id="po-subtotal-disp">₹0.00</th>
                                    <th></th>
                                </tr>
                                <tr id="po-disc-row" style="display:none;">
                                    <th colspan="9" class="text-end text-success">Total Discount Saved:</th>
                                    <th class="text-end text-success font-monospace" id="po-discount-disp">-₹0.00</th>
                                    <th></th>
                                </tr>
                                <tr>
                                    <th colspan="9" class="text-end">Estimated GST Tax:</th>
                                    <th class="text-end font-monospace" id="po-tax-disp">₹0.00</th>
                                    <th></th>
                                </tr>
                                <tr class="table-primary">
                                    <th colspan="9" class="text-end fs-6">Grand Total Commitment:</th>
                                    <th class="text-end fs-6 text-primary fw-bold font-monospace" id="po-grand-total-disp">₹0.00</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-outline-secondary" onclick="load_form_div('<?= base_url('Medical/purchase_orders') ?>','medical-main');">Cancel</button>
                <button type="submit" class="btn btn-primary px-4" id="btn-save-po">
                    <i class="bi bi-check-circle"></i> Save &amp; Generate Purchase Order
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    // Ensure jQuery UI for drug autocomplete
    if (window.jQuery && window.jQuery.ui && window.jQuery.ui.autocomplete) {
        initPoDrugAutocomplete();
    } else {
        var s = document.createElement('script');
        s.src = '<?= base_url('assets/vendor/jquery-ui/jquery-ui.min.js') ?>';
        s.onload = initPoDrugAutocomplete;
        document.body.appendChild(s);
    }

    recalculatePoTotals();
})();

function initPoDrugAutocomplete() {
    if (!window.jQuery || !window.jQuery.ui) return;
    var $ = window.jQuery;

    $("#quick_drug_search").autocomplete({
        source: function(request, response) {
            var suppId = $('#po_supplier_id').val() || 0;
            $.getJSON("<?= base_url('Medical/po_search_drug') ?>", {
                term: request.term,
                supplier_id: suppId
            }, function(data) {
                response(data || []);
            });
        },
        minLength: 1,
        select: function(event, ui) {
            var item = ui.item || {};
            document.getElementById('selected_item_id').value = item.item_code || '';
            document.getElementById('selected_item_name').value = item.value || '';
            document.getElementById('selected_generic_name').value = item.generic_name || '';
            document.getElementById('selected_category').value = item.formulation || 'Tablet';
            document.getElementById('quick_units_per_pack').value = item.packing || 10;
            document.getElementById('quick_ptr').value = item.estimated_ptr || '0.00';
            document.getElementById('selected_gst_rate').value = item.gst_rate !== undefined ? item.gst_rate : 12;
            document.getElementById('selected_discount_percent').value = item.discount_percent || 0;
            document.getElementById('selected_sch_disc_per').value = item.sch_disc_per || 0;
            document.getElementById('selected_scheme_info').value = item.scheme_info || '';
            document.getElementById('btn-add-line').disabled = false;

            // Render Drug History / Scheme info pill
            var pill = document.getElementById('selected_drug_info_pill');
            if (pill) {
                var h = '<div class="alert alert-light border py-1 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-sm" style="font-size:12px;">';
                h += '<div>';
                h += '<strong class="text-primary me-2"><i class="bi bi-capsule"></i> ' + escapeHtml(item.value) + '</strong>';
                if (item.has_last_purchase) {
                    h += '<span class="badge bg-secondary me-1">Last Rate: ₹' + item.estimated_ptr + '</span>';
                    h += '<span class="badge bg-info text-dark me-1">GST: ' + item.gst_rate + '%</span>';
                    if (item.scheme_summary) {
                        h += '<span class="badge bg-success me-1"><i class="bi bi-tag-fill"></i> ' + escapeHtml(item.scheme_summary) + '</span>';
                    }
                    if (item.is_vendor_match) {
                        h += '<span class="badge bg-success-subtle text-success border border-success-subtle ms-1"><i class="bi bi-check-circle"></i> From selected vendor</span>';
                    } else if (item.last_supplier_name) {
                        h += '<span class="text-muted ms-1 small">(Last: ' + escapeHtml(item.last_supplier_name) + (item.last_purchase_date ? ' ' + item.last_purchase_date : '') + ')</span>';
                    }
                } else {
                    h += '<span class="badge bg-light text-muted border">New Item &bull; Master GST: ' + item.gst_rate + '%</span>';
                }
                h += '</div>';
                h += '<span class="text-muted fst-italic small">Auto-filled rate &amp; GST &bull; Click + Add</span>';
                h += '</div>';
                pill.innerHTML = h;
                pill.style.display = 'block';
            }
        }
    });
}

function handleSupplierChange(suppId) {
    var reorderBtn = document.getElementById('btn-reorder-fetch');
    if (suppId && parseInt(suppId) > 0) {
        reorderBtn.style.display = 'inline-block';
    } else {
        reorderBtn.style.display = 'none';
    }
}

function addItemToPoTable(prefill) {
    var item = prefill || {
        item_id: document.getElementById('selected_item_id').value,
        item_name: document.getElementById('selected_item_name').value,
        generic_name: document.getElementById('selected_generic_name').value,
        category: document.getElementById('selected_category').value || 'Tablet',
        units_per_pack: document.getElementById('quick_units_per_pack').value || 10,
        order_packs: document.getElementById('quick_order_packs').value || 10,
        estimated_ptr: document.getElementById('quick_ptr').value || 0,
        gst_rate: document.getElementById('selected_gst_rate').value || 12,
        discount_percent: document.getElementById('selected_discount_percent').value || 0,
        sch_disc_per: document.getElementById('selected_sch_disc_per').value || 0,
        scheme_info: document.getElementById('selected_scheme_info').value || ''
    };

    if (!item.item_id || !item.item_name) {
        alert('Please search and select a medicine from master first.');
        return;
    }

    var tbody = document.getElementById('po-items-body');
    var noRow = document.getElementById('no-items-row');
    if (noRow) noRow.remove();

    var existingRow = tbody.querySelector('tr[data-item-id="' + item.item_id + '"]');
    if (existingRow) {
        var packsInput = existingRow.querySelector('.line-packs');
        packsInput.value = parseFloat(packsInput.value || 0) + parseFloat(item.order_packs);
        recalculatePoTotals();
        resetQuickAddInputs();
        return;
    }

    var rowIdx = tbody.querySelectorAll('tr').length;
    var tr = document.createElement('tr');
    tr.setAttribute('data-item-id', item.item_id);

    var itemGst = parseFloat(item.gst_rate !== undefined ? item.gst_rate : 12);
    var discVal = parseFloat(item.discount_percent || 0);
    var schVal = parseFloat(item.sch_disc_per || 0);
    var schInfo = item.scheme_info || '';

    // Standard GST options
    var standardGsts = [0, 5, 12, 18, 28];
    var gstOptionsHtml = '';
    standardGsts.forEach(function(g) {
        gstOptionsHtml += `<option value="${g}" ${Math.abs(itemGst - g) < 0.1 ? 'selected' : ''}>${g}%</option>`;
    });
    if (!standardGsts.some(g => Math.abs(itemGst - g) < 0.1)) {
        gstOptionsHtml += `<option value="${itemGst}" selected>${itemGst}%</option>`;
    }

    tr.innerHTML = `
        <td class="row-num">${rowIdx + 1}</td>
        <td>
            <strong>${escapeHtml(item.item_name)}</strong>
            <input type="hidden" name="items[${rowIdx}][item_id]" value="${item.item_id}">
            <input type="hidden" name="items[${rowIdx}][item_name]" value="${escapeHtml(item.item_name)}">
        </td>
        <td>
            ${escapeHtml(item.generic_name || '-')}
            <input type="hidden" name="items[${rowIdx}][generic_name]" value="${escapeHtml(item.generic_name || '')}">
        </td>
        <td>
            ${escapeHtml(item.category || 'Tablet')}
            <input type="hidden" name="items[${rowIdx}][category]" value="${escapeHtml(item.category || '')}">
        </td>
        <td class="text-center">
            <input type="number" name="items[${rowIdx}][units_per_pack]" class="form-control form-control-sm text-center line-units px-1" value="${item.units_per_pack}" min="1">
        </td>
        <td class="text-center">
            <input type="number" name="items[${rowIdx}][order_packs]" class="form-control form-control-sm text-center line-packs px-1" value="${item.order_packs}" min="1" onchange="recalculatePoTotals();">
        </td>
        <td class="text-end">
            <input type="number" name="items[${rowIdx}][estimated_ptr]" class="form-control form-control-sm text-end line-rate px-1" value="${parseFloat(item.estimated_ptr).toFixed(2)}" min="0" step="0.01" onchange="recalculatePoTotals();">
        </td>
        <td class="text-center">
            <div class="d-flex flex-column gap-1">
                <div class="input-group input-group-sm">
                    <span class="input-group-text py-0 px-1 bg-light text-muted" style="font-size:10px;">Disc%</span>
                    <input type="number" name="items[${rowIdx}][discount_percent]" class="form-control form-control-sm text-end line-disc px-1" value="${discVal > 0 ? discVal.toFixed(2) : '0'}" min="0" max="100" step="0.01" onchange="recalculatePoTotals();" style="font-size:11px;">
                </div>
                <div class="input-group input-group-sm">
                    <span class="input-group-text py-0 px-1 bg-light text-muted" style="font-size:10px;">Sch</span>
                    <input type="text" name="items[${rowIdx}][scheme_info]" class="form-control form-control-sm line-scheme px-1" value="${escapeHtml(schInfo)}" placeholder="e.g. 10+1" style="font-size:11px;">
                </div>
                <input type="hidden" name="items[${rowIdx}][sch_disc_per]" class="line-sch-per" value="${schVal}">
            </div>
        </td>
        <td class="text-center">
            <select name="items[${rowIdx}][gst_rate]" class="form-select form-select-sm text-center line-gst px-1" onchange="recalculatePoTotals();">
                ${gstOptionsHtml}
            </select>
        </td>
        <td class="text-end fw-semibold line-total-display">₹0.00</td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm py-0" onclick="removePoRow(this);">✖</button>
        </td>
    `;

    tbody.appendChild(tr);
    recalculatePoTotals();
    resetQuickAddInputs();
}

function resetQuickAddInputs() {
    document.getElementById('quick_drug_search').value = '';
    document.getElementById('selected_item_id').value = '';
    document.getElementById('selected_item_name').value = '';
    document.getElementById('selected_generic_name').value = '';
    document.getElementById('selected_gst_rate').value = '12';
    document.getElementById('selected_discount_percent').value = '0';
    document.getElementById('selected_sch_disc_per').value = '0';
    document.getElementById('selected_scheme_info').value = '';
    document.getElementById('quick_ptr').value = '0.00';
    document.getElementById('btn-add-line').disabled = true;

    var pill = document.getElementById('selected_drug_info_pill');
    if (pill) {
        pill.innerHTML = '';
        pill.style.display = 'none';
    }

    document.getElementById('quick_drug_search').focus();
}

function removePoRow(btn) {
    btn.closest('tr').remove();
    var tbody = document.getElementById('po-items-body');
    var rows = tbody.querySelectorAll('tr');
    if (rows.length === 0) {
        tbody.innerHTML = '<tr id="no-items-row"><td colspan="11" class="text-center py-4 text-muted">No items added yet. Search above to add items or auto-load low stock items.</td></tr>';
    } else {
        rows.forEach(function(r, idx) {
            var numTd = r.querySelector('.row-num');
            if (numTd) numTd.innerText = idx + 1;
        });
    }
    recalculatePoTotals();
}

function recalculatePoTotals() {
    var rows = document.querySelectorAll('#po-items-body tr:not(#no-items-row)');
    var totalGross = 0.0;
    var totalDiscount = 0.0;
    var totalTaxable = 0.0;
    var totalTax = 0.0;

    rows.forEach(function(r) {
        var packs = parseFloat(r.querySelector('.line-packs')?.value || 0);
        var rate = parseFloat(r.querySelector('.line-rate')?.value || 0);
        var discPer = parseFloat(r.querySelector('.line-disc')?.value || 0);
        var schPer = parseFloat(r.querySelector('.line-sch-per')?.value || 0);
        var gst = parseFloat(r.querySelector('.line-gst')?.value || 0);

        var lineGross = packs * rate;
        var schAmt = (schPer > 0) ? (lineGross * (schPer / 100)) : 0.0;
        var afterSch = Math.max(0.0, lineGross - schAmt);
        var tradeDiscAmt = (discPer > 0) ? (afterSch * (discPer / 100)) : 0.0;
        var lineTaxable = Math.max(0.0, afterSch - tradeDiscAmt);
        var lineTax = lineTaxable * (gst / 100);
        var lineTotal = lineTaxable + lineTax;

        totalGross += lineGross;
        totalDiscount += (schAmt + tradeDiscAmt);
        totalTaxable += lineTaxable;
        totalTax += lineTax;

        var totalDisp = r.querySelector('.line-total-display');
        if (totalDisp) totalDisp.innerText = '₹' + lineTotal.toFixed(2);
    });

    document.getElementById('po-subtotal-disp').innerText = '₹' + totalTaxable.toFixed(2);
    document.getElementById('po-tax-disp').innerText = '₹' + totalTax.toFixed(2);
    document.getElementById('po-grand-total-disp').innerText = '₹' + (totalTaxable + totalTax).toFixed(2);

    var discRow = document.getElementById('po-disc-row');
    if (discRow) {
        if (totalDiscount > 0) {
            discRow.style.display = '';
            document.getElementById('po-discount-disp').innerText = '-₹' + totalDiscount.toFixed(2);
        } else {
            discRow.style.display = 'none';
        }
    }
}

function fetchSupplierReorderItems() {
    var suppId = document.getElementById('po_supplier_id').value;
    if (!suppId) return;

    fetch('<?= base_url('Medical/po_reorder_items/') ?>' + suppId)
        .then(r => r.json())
        .then(data => {
            if (data.status && data.items && data.items.length > 0) {
                var added = 0;
                data.items.forEach(function(it) {
                    addItemToPoTable({
                        item_id: it.item_id,
                        item_name: it.item_name,
                        generic_name: it.generic_name,
                        category: it.category || 'Tablet',
                        units_per_pack: it.units_per_pack || 10,
                        order_packs: 10,
                        estimated_ptr: it.estimated_ptr || 0,
                        gst_rate: it.gst_rate !== undefined ? it.gst_rate : 12,
                        discount_percent: it.discount_percent || 0,
                        sch_disc_per: it.sch_disc_per || 0,
                        scheme_info: it.scheme_info || ''
                    });
                    added++;
                });
                alert('Added ' + added + ' low stock items to PO.');
            } else {
                alert('No low-stock items detected for this supplier.');
            }
        })
        .catch(e => alert('Error fetching supplier items.'));
}

function submitPoForm() {
    var form = document.getElementById('po-create-form');
    var formData = new FormData(form);

    var rows = document.querySelectorAll('#po-items-body tr:not(#no-items-row)');
    if (rows.length === 0) {
        alert('Please add at least one medicine line to the purchase order.');
        return;
    }

    var saveBtn = document.getElementById('btn-save-po');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

    fetch('<?= base_url('Medical/po_create') ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="bi bi-check-circle"></i> Save &amp; Generate Purchase Order';

        if (data.status) {
            alert(data.message);
            load_form_div('<?= base_url('Medical/purchase_orders') ?>', 'medical-main', 'Purchase Orders :Pharmacy');
        } else {
            alert('Error: ' + (data.message || 'Unable to save Purchase Order.'));
        }
    })
    .catch(e => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="bi bi-check-circle"></i> Save &amp; Generate Purchase Order';
        alert('Server communication error.');
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
</script>

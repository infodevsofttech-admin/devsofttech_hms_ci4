<?php
    $poId = !empty($selectedPo) ? (int)$selectedPo['po_id'] : 0;
    $supplierId = !empty($selectedPo) ? (int)$selectedPo['supplier_id'] : 0;
    $nextGrnNumber = $nextGrnNumber ?? 'GRN-NEW';
?>

<div class="pagetitle d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 text-success">
            <i class="bi bi-box-arrow-in-down"></i> Create Goods Received Note (GRN)
            <span class="badge bg-light text-dark border ms-2 font-monospace"><?= esc($nextGrnNumber) ?></span>
        </h4>
        <small class="text-muted">Inward Stock Physical Inspection &amp; Batch Entry</small>
    </div>
    <div>
        <a href="javascript:load_form_div('<?= base_url('Medical/grn_list') ?>','medical-main','GRN Register :Pharmacy');" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to GRN List
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-light py-2">
        <strong class="text-secondary"><i class="bi bi-truck"></i> Inward Delivery Metadata</strong>
    </div>
    <div class="card-body p-3">
        <form id="grn-create-form" onsubmit="event.preventDefault(); submitGrnForm();">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label form-label-sm fw-semibold">Linked Purchase Order (PO)</label>
                    <select name="po_id" id="grn_po_id" class="form-select form-select-sm" onchange="handlePoSelection(this.value);">
                        <option value="0">-- Direct Intake (No PO) --</option>
                        <?php if (!empty($approvedPos)): ?>
                            <?php foreach ($approvedPos as $p): ?>
                                <option value="<?= (int)$p['po_id'] ?>" <?= ($poId == $p['po_id']) ? 'selected' : '' ?>>
                                    <?= esc($p['po_number']) ?> (<?= date('d/m/y', strtotime($p['po_date'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm fw-semibold">Supplier / Pharmaceutical Vendor <span class="text-danger">*</span></label>
                    <select name="supplier_id" id="grn_supplier_id" class="form-select form-select-sm" required>
                        <option value="">-- Select Supplier --</option>
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
                    <label class="form-label form-label-sm fw-semibold">Vendor Bill / Invoice # <span class="text-danger">*</span></label>
                    <input type="text" name="supplier_invoice_no" id="supplier_invoice_no" class="form-control form-control-sm" placeholder="e.g., INV-88421" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm fw-semibold">Bill Date</label>
                    <input type="date" name="invoice_date" id="invoice_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm fw-semibold">GRN Received Date</label>
                    <input type="date" name="grn_date" id="grn_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm fw-semibold">Delivery Challan / Gate Pass #</label>
                    <input type="text" name="challan_no" id="challan_no" class="form-control form-control-sm" placeholder="Optional Challan No.">
                </div>
                <div class="col-md-9">
                    <label class="form-label form-label-sm fw-semibold">Receiving Inspection Remarks</label>
                    <input type="text" name="remarks" id="grn_remarks" class="form-control form-control-sm" placeholder="Package integrity verified, cold-chain temperature logged, etc.">
                </div>
            </div>

            <!-- Manual Drug Search (for direct items or extra items) -->
            <div class="card border-success mt-3">
                <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-shield-check"></i> Physical Inspection &amp; Batch Intake Table</span>
                    <small class="text-white-50">Enter Batch, Expiry, Received Packs &amp; Pricing</small>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-4">
                            <label class="form-label form-label-sm mb-1 fw-semibold">Add Medicine / Drug Master</label>
                            <input type="text" id="grn_drug_search" class="form-control form-control-sm" placeholder="Search medicine to add row..." autocomplete="off">
                            <input type="hidden" id="grn_sel_item_code" value="">
                            <input type="hidden" id="grn_sel_item_name" value="">
                            <input type="hidden" id="grn_sel_packing" value="10">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm mb-1 fw-semibold">Batch No.</label>
                            <input type="text" id="grn_sel_batch" class="form-control form-control-sm" placeholder="e.g. BATCH-01">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm mb-1 fw-semibold">Expiry Date</label>
                            <input type="date" id="grn_sel_expiry" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm mb-1 fw-semibold">Received Packs</label>
                            <input type="number" id="grn_sel_rec_packs" class="form-control form-control-sm" value="10" min="1">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-success btn-sm w-100" id="btn-add-grn-row" onclick="addCustomItemToGrn();" disabled>
                                <i class="bi bi-plus-lg"></i> Add Row
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0" id="grn-items-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 30px;">#</th>
                                    <th>Medicine Name</th>
                                    <th style="width: 110px;">Batch No <span class="text-danger">*</span></th>
                                    <th style="width: 120px;">Expiry <span class="text-danger">*</span></th>
                                    <th class="text-center" style="width: 70px;">Pack</th>
                                    <th class="text-center" style="width: 80px;">Ordered</th>
                                    <th class="text-center" style="width: 85px;">Rec. Packs <span class="text-danger">*</span></th>
                                    <th class="text-center" style="width: 75px;">Free</th>
                                    <th class="text-center" style="width: 75px;">Damaged</th>
                                    <th class="text-end" style="width: 100px;">Rate/Pack</th>
                                    <th class="text-end" style="width: 90px;">MRP</th>
                                    <th class="text-center" style="width: 70px;">GST %</th>
                                    <th class="text-end" style="width: 110px;">Line Total</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="grn-items-body">
                                <?php if (!empty($poItems)): ?>
                                    <?php foreach ($poItems as $idx => $it): ?>
                                        <tr>
                                            <td class="row-num"><?= $idx + 1 ?></td>
                                            <td>
                                                <strong><?= esc($it['item_name']) ?></strong>
                                                <input type="hidden" name="items[<?= $idx ?>][item_code]" value="<?= (int)$it['item_id'] ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][item_name]" value="<?= esc($it['item_name']) ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][po_item_id]" value="<?= (int)$it['po_item_id'] ?>">
                                            </td>
                                            <td>
                                                <input type="text" name="items[<?= $idx ?>][batch_no]" class="form-control form-control-sm font-monospace line-batch" placeholder="Batch #" required>
                                            </td>
                                            <td>
                                                <input type="date" name="items[<?= $idx ?>][expiry_date]" class="form-control form-control-sm line-expiry" required>
                                            </td>
                                            <td class="text-center">
                                                <input type="number" name="items[<?= $idx ?>][packing]" class="form-control form-control-sm text-center line-pack" value="<?= (int)$it['units_per_pack'] ?>" min="1">
                                            </td>
                                            <td class="text-center text-muted">
                                                <?= (float)$it['remaining_packs'] ?>
                                                <input type="hidden" name="items[<?= $idx ?>][order_packs]" value="<?= (float)$it['remaining_packs'] ?>">
                                            </td>
                                            <td class="text-center">
                                                <input type="number" name="items[<?= $idx ?>][received_packs]" class="form-control form-control-sm text-center line-rec" value="<?= (float)$it['remaining_packs'] ?>" min="0" step="1" onchange="recalculateGrnTotals();" required>
                                            </td>
                                            <td class="text-center">
                                                <input type="number" name="items[<?= $idx ?>][free_packs]" class="form-control form-control-sm text-center line-free" value="0" min="0" step="1">
                                            </td>
                                            <td class="text-center">
                                                <input type="number" name="items[<?= $idx ?>][rejected_packs]" class="form-control form-control-sm text-center line-rej" value="0" min="0" step="1">
                                            </td>
                                            <td class="text-end">
                                                <input type="number" name="items[<?= $idx ?>][unit_purchase_rate]" class="form-control form-control-sm text-end line-rate" value="<?= number_format((float)$it['estimated_ptr'], 2, '.', '') ?>" min="0" step="0.01" onchange="recalculateGrnTotals();">
                                            </td>
                                            <td class="text-end">
                                                <input type="number" name="items[<?= $idx ?>][mrp]" class="form-control form-control-sm text-end line-mrp" value="<?= number_format((float)$it['estimated_ptr'] * 1.3, 2, '.', '') ?>" min="0" step="0.01">
                                            </td>
                                            <td class="text-center">
                                                <select name="items[<?= $idx ?>][cgst_percent]" class="form-select form-select-sm text-center line-gst" onchange="recalculateGrnTotals();">
                                                    <option value="6" <?= ((float)$it['gst_rate'] == 12) ? 'selected' : '' ?>>12%</option>
                                                    <option value="2.5" <?= ((float)$it['gst_rate'] == 5) ? 'selected' : '' ?>>5%</option>
                                                    <option value="9" <?= ((float)$it['gst_rate'] == 18) ? 'selected' : '' ?>>18%</option>
                                                    <option value="0" <?= ((float)$it['gst_rate'] == 0) ? 'selected' : '' ?>>0%</option>
                                                </select>
                                                <input type="hidden" name="items[<?= $idx ?>][sgst_percent]" class="line-sgst" value="6">
                                            </td>
                                            <td class="text-end fw-semibold line-total-disp">₹0.00</td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-outline-danger btn-sm py-0" onclick="removeGrnRow(this);">✖</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr id="grn-no-items">
                                        <td colspan="14" class="text-center py-4 text-muted">
                                            No items loaded. Select a PO above or use the Search box to add items.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="12" class="text-end">Taxable Amount:</th>
                                    <th class="text-end" id="grn-taxable-disp">₹0.00</th>
                                    <th></th>
                                </tr>
                                <tr>
                                    <th colspan="12" class="text-end">GST Tax Amount:</th>
                                    <th class="text-end" id="grn-tax-disp">₹0.00</th>
                                    <th></th>
                                </tr>
                                <tr class="table-success">
                                    <th colspan="12" class="text-end fs-6">Net Inward Bill Total:</th>
                                    <th class="text-end fs-6 text-success fw-bold" id="grn-net-disp">₹0.00</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card p-3 mt-3 bg-light border-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="post_to_inventory" id="post_to_inventory" value="1" checked>
                        <label class="form-check-label fw-bold text-dark" for="post_to_inventory">
                            Immediately Post Verified Quantities to Live Pharmacy Store Stock &amp; Purchase Ledgers
                        </label>
                        <small class="d-block text-muted">Calls stored procedure <code>p_stock_update_purchase_id</code> to make batches immediately sellable.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="load_form_div('<?= base_url('Medical/grn_list') ?>','medical-main');">Cancel</button>
                        <button type="submit" class="btn btn-success px-4" id="btn-save-grn">
                            <i class="bi bi-check-all"></i> Save &amp; Complete Inward GRN
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    if (window.jQuery && window.jQuery.ui && window.jQuery.ui.autocomplete) {
        initGrnDrugAutocomplete();
    } else {
        var s = document.createElement('script');
        s.src = '<?= base_url('assets/vendor/jquery-ui/jquery-ui.min.js') ?>';
        s.onload = initGrnDrugAutocomplete;
        document.body.appendChild(s);
    }

    recalculateGrnTotals();
})();

function initGrnDrugAutocomplete() {
    if (!window.jQuery || !window.jQuery.ui) return;
    var $ = window.jQuery;

    $("#grn_drug_search").autocomplete({
        source: function(request, response) {
            $.getJSON("<?= base_url('Medical/get_drug') ?>", request, function(data) {
                response(data || []);
            });
        },
        minLength: 1,
        select: function(event, ui) {
            var item = ui.item || {};
            document.getElementById('grn_sel_item_code').value = item.item_code || item.l_item_code || '';
            document.getElementById('grn_sel_item_name').value = item.value || '';
            document.getElementById('grn_sel_packing').value = item.l_packing || 10;
            document.getElementById('btn-add-grn-row').disabled = false;
        }
    });
}

function handlePoSelection(poId) {
    if (poId && parseInt(poId) > 0) {
        load_form_div('<?= base_url('Medical/grn_new/') ?>' + poId, 'medical-main', 'Create GRN for PO');
    }
}

function addCustomItemToGrn() {
    var code = document.getElementById('grn_sel_item_code').value;
    var name = document.getElementById('grn_sel_item_name').value;
    var batch = document.getElementById('grn_sel_batch').value.trim();
    var expiry = document.getElementById('grn_sel_expiry').value;
    var packs = parseFloat(document.getElementById('grn_sel_rec_packs').value || 1);
    var packing = parseInt(document.getElementById('grn_sel_packing').value || 10);

    if (!code || !name) {
        alert('Please search and select a medicine.');
        return;
    }
    if (!batch) {
        alert('Please enter a valid batch number for physical tracking.');
        document.getElementById('grn_sel_batch').focus();
        return;
    }
    if (!expiry) {
        alert('Please enter medicine expiry date.');
        document.getElementById('grn_sel_expiry').focus();
        return;
    }

    var tbody = document.getElementById('grn-items-body');
    var noRow = document.getElementById('grn-no-items');
    if (noRow) noRow.remove();

    var rowIdx = tbody.querySelectorAll('tr').length;
    var tr = document.createElement('tr');

    tr.innerHTML = `
        <td class="row-num">${rowIdx + 1}</td>
        <td>
            <strong>${escapeHtml(name)}</strong>
            <input type="hidden" name="items[${rowIdx}][item_code]" value="${code}">
            <input type="hidden" name="items[${rowIdx}][item_name]" value="${escapeHtml(name)}">
            <input type="hidden" name="items[${rowIdx}][po_item_id]" value="">
        </td>
        <td>
            <input type="text" name="items[${rowIdx}][batch_no]" class="form-control form-control-sm font-monospace line-batch" value="${escapeHtml(batch)}" required>
        </td>
        <td>
            <input type="date" name="items[${rowIdx}][expiry_date]" class="form-control form-control-sm line-expiry" value="${expiry}" required>
        </td>
        <td class="text-center">
            <input type="number" name="items[${rowIdx}][packing]" class="form-control form-control-sm text-center line-pack" value="${packing}" min="1">
        </td>
        <td class="text-center text-muted">0</td>
        <td class="text-center">
            <input type="number" name="items[${rowIdx}][received_packs]" class="form-control form-control-sm text-center line-rec" value="${packs}" min="1" onchange="recalculateGrnTotals();" required>
        </td>
        <td class="text-center">
            <input type="number" name="items[${rowIdx}][free_packs]" class="form-control form-control-sm text-center line-free" value="0" min="0">
        </td>
        <td class="text-center">
            <input type="number" name="items[${rowIdx}][rejected_packs]" class="form-control form-control-sm text-center line-rej" value="0" min="0">
        </td>
        <td class="text-end">
            <input type="number" name="items[${rowIdx}][unit_purchase_rate]" class="form-control form-control-sm text-end line-rate" value="100.00" min="0" step="0.01" onchange="recalculateGrnTotals();">
        </td>
        <td class="text-end">
            <input type="number" name="items[${rowIdx}][mrp]" class="form-control form-control-sm text-end line-mrp" value="130.00" min="0" step="0.01">
        </td>
        <td class="text-center">
            <select name="items[${rowIdx}][cgst_percent]" class="form-select form-select-sm text-center line-gst" onchange="recalculateGrnTotals();">
                <option value="6" selected>12%</option>
                <option value="2.5">5%</option>
                <option value="9">18%</option>
                <option value="0">0%</option>
            </select>
            <input type="hidden" name="items[${rowIdx}][sgst_percent]" class="line-sgst" value="6">
        </td>
        <td class="text-end fw-semibold line-total-disp">₹0.00</td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm py-0" onclick="removeGrnRow(this);">✖</button>
        </td>
    `;

    tbody.appendChild(tr);
    recalculateGrnTotals();

    document.getElementById('grn_drug_search').value = '';
    document.getElementById('grn_sel_batch').value = '';
    document.getElementById('grn_sel_expiry').value = '';
    document.getElementById('btn-add-grn-row').disabled = true;
    document.getElementById('grn_drug_search').focus();
}

function removeGrnRow(btn) {
    btn.closest('tr').remove();
    var tbody = document.getElementById('grn-items-body');
    var rows = tbody.querySelectorAll('tr');
    if (rows.length === 0) {
        tbody.innerHTML = '<tr id="grn-no-items"><td colspan="14" class="text-center py-4 text-muted">No items loaded.</td></tr>';
    } else {
        rows.forEach(function(r, idx) {
            var numTd = r.querySelector('.row-num');
            if (numTd) numTd.innerText = idx + 1;
        });
    }
    recalculateGrnTotals();
}

function recalculateGrnTotals() {
    var rows = document.querySelectorAll('#grn-items-body tr:not(#grn-no-items)');
    var taxable = 0.0;
    var totalTax = 0.0;

    rows.forEach(function(r) {
        var recPacks = parseFloat(r.querySelector('.line-rec')?.value || 0);
        var rate = parseFloat(r.querySelector('.line-rate')?.value || 0);
        var gstVal = parseFloat(r.querySelector('.line-gst')?.value || 0) * 2; // CGST + SGST

        var lineSub = recPacks * rate;
        var lineTax = lineSub * (gstVal / 100);
        var lineTotal = lineSub + lineTax;

        taxable += lineSub;
        totalTax += lineTax;

        var totalDisp = r.querySelector('.line-total-disp');
        if (totalDisp) totalDisp.innerText = '₹' + lineTotal.toFixed(2);
    });

    document.getElementById('grn-taxable-disp').innerText = '₹' + taxable.toFixed(2);
    document.getElementById('grn-tax-disp').innerText = '₹' + totalTax.toFixed(2);
    document.getElementById('grn-net-disp').innerText = '₹' + (taxable + totalTax).toFixed(2);
}

function submitGrnForm() {
    var rows = document.querySelectorAll('#grn-items-body tr:not(#grn-no-items)');
    if (rows.length === 0) {
        alert('Please add at least one received item line.');
        return;
    }

    // Validate that all batch and expiry dates are populated
    var missingBatch = false;
    rows.forEach(function(r) {
        var batch = r.querySelector('.line-batch')?.value.trim();
        var exp = r.querySelector('.line-expiry')?.value.trim();
        if (!batch || !exp) missingBatch = true;
    });

    if (missingBatch) {
        alert('Please ensure every medicine row has a valid Batch No. and Expiration Date.');
        return;
    }

    var form = document.getElementById('grn-create-form');
    var formData = new FormData(form);

    var saveBtn = document.getElementById('btn-save-grn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing Inward Stock...';

    fetch('<?= base_url('Medical/grn_create') ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="bi bi-check-all"></i> Save &amp; Complete Inward GRN';

        if (data.status) {
            alert(data.message);
            load_form_div('<?= base_url('Medical/grn_list') ?>', 'medical-main', 'GRN Register :Pharmacy');
        } else {
            alert('Error: ' + (data.message || 'Failed to save GRN.'));
        }
    })
    .catch(e => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="bi bi-check-all"></i> Save &amp; Complete Inward GRN';
        alert('Server communication error.');
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
</script>

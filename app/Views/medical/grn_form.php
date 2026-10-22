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
        <small class="text-muted">Inward Stock Physical Inspection &amp; Vendor Invoice Reconciliation</small>
    </div>
    <div>
        <a href="javascript:load_form_div('<?= base_url('Medical/grn_list') ?>','medical-main','GRN Register :Pharmacy');" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to GRN List
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-light py-2">
        <strong class="text-secondary"><i class="bi bi-truck"></i> Inward Delivery &amp; Invoice Metadata</strong>
    </div>
    <div class="card-body p-3">
        <form id="grn-create-form" onsubmit="event.preventDefault(); submitGrnForm();">
            <?= csrf_field() ?>
            <div class="row g-2 mb-2">
                <div class="col-md-3">
                    <label class="form-label form-label-sm fw-semibold mb-1">Linked Purchase Order (PO)</label>
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
                    <label class="form-label form-label-sm fw-semibold mb-1">Supplier / Pharmaceutical Vendor <span class="text-danger">*</span></label>
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
                    <label class="form-label form-label-sm fw-semibold mb-1">Vendor Invoice / Bill # <span class="text-danger">*</span></label>
                    <input type="text" name="supplier_invoice_no" id="supplier_invoice_no" class="form-control form-control-sm" placeholder="e.g. INV-88421" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm fw-semibold mb-1">Invoice Date <span class="text-danger">*</span></label>
                    <input type="date" name="invoice_date" id="invoice_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm fw-semibold mb-1">GRN Received Date <span class="text-danger">*</span></label>
                    <input type="date" name="grn_date" id="grn_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <label class="form-label form-label-sm fw-semibold mb-1">GST Supply Type</label>
                    <select name="gst_type" id="grn_gst_type" class="form-select form-select-sm" onchange="recalculateGrnTotals();">
                        <option value="intra_state" selected>Intra-State (CGST + SGST)</option>
                        <option value="inter_state">Inter-State (IGST)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm fw-semibold mb-1">Delivery Challan / Gate Pass #</label>
                    <input type="text" name="challan_no" id="challan_no" class="form-control form-control-sm" placeholder="Optional Challan No.">
                </div>
                <div class="col-md-6">
                    <label class="form-label form-label-sm fw-semibold mb-1">Receiving Inspection Remarks</label>
                    <input type="text" name="remarks" id="grn_remarks" class="form-control form-control-sm" placeholder="Package integrity verified, cold chain maintained, etc.">
                </div>
            </div>

            <!-- Manual Medicine Search Bar -->
            <div class="card border-success mt-2">
                <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-shield-check"></i> Physical Inspection &amp; Batch Intake Table</span>
                    <small class="text-white-50">Match Vendor Invoice Columns (Gross, Discounts, Taxable, GST &amp; Net Total)</small>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 align-items-end mb-3 bg-light p-2 rounded border">
                        <div class="col-md-3">
                            <label class="form-label form-label-sm mb-1 fw-semibold">Search Medicine Master</label>
                            <input type="text" id="grn_drug_search" class="form-control form-control-sm" placeholder="Search medicine to add..." autocomplete="off">
                            <input type="hidden" id="grn_sel_item_code" value="">
                            <input type="hidden" id="grn_sel_item_name" value="">
                            <input type="hidden" id="grn_sel_packing" value="10">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm mb-1 fw-semibold">Batch No.</label>
                            <input type="text" id="grn_sel_batch" class="form-control form-control-sm font-monospace" placeholder="e.g. 26DH220">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm mb-1 fw-semibold">Expiry Date</label>
                            <input type="date" id="grn_sel_expiry" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label form-label-sm mb-1 fw-semibold">Rec. Qty</label>
                            <input type="number" id="grn_sel_rec_packs" class="form-control form-control-sm text-center" value="10" min="1">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label form-label-sm mb-1 fw-semibold">Free</label>
                            <input type="number" id="grn_sel_free_packs" class="form-control form-control-sm text-center" value="0" min="0">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label form-label-sm mb-1 fw-semibold">Rate/Pack</label>
                            <input type="number" id="grn_sel_rate" class="form-control form-control-sm text-end" value="100.00" min="0" step="0.01">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label form-label-sm mb-1 fw-semibold">MRP</label>
                            <input type="number" id="grn_sel_mrp" class="form-control form-control-sm text-end" value="130.00" min="0" step="0.01">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-success btn-sm w-100" id="btn-add-grn-row" onclick="addCustomItemToGrn();" disabled>
                                <i class="bi bi-plus-lg"></i> Add
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0" id="grn-items-table" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr class="text-center align-middle">
                                    <th style="width: 25px;">#</th>
                                    <th style="min-width: 160px;" class="text-start">Medicine Name</th>
                                    <th style="width: 95px;">Batch No <span class="text-danger">*</span></th>
                                    <th style="width: 105px;">Expiry <span class="text-danger">*</span></th>
                                    <th style="width: 50px;">Pack</th>
                                    <th style="width: 65px;">Rec. Qty <span class="text-danger">*</span></th>
                                    <th style="width: 50px;">Free</th>
                                    <th style="width: 75px;" class="text-end">Rate/Pack</th>
                                    <th style="width: 70px;" class="text-end">MRP</th>
                                    <th style="width: 80px;" class="text-end bg-light">Gross Amt</th>
                                    <th style="width: 60px;">Disc %</th>
                                    <th style="width: 60px;">Sch %</th>
                                    <th style="width: 65px;">Sch Amt</th>
                                    <th style="width: 85px;" class="text-end bg-light">Taxable Amt</th>
                                    <th style="width: 75px;">GST %</th>
                                    <th style="width: 90px;" class="text-end bg-light">Line Total</th>
                                    <th style="width: 35px;"></th>
                                </tr>
                            </thead>
                            <tbody id="grn-items-body">
                                <?php if (!empty($poItems)): ?>
                                    <?php foreach ($poItems as $idx => $it): ?>
                                        <?php 
                                            $defGst = (float)($it['gst_rate'] ?? 5);
                                            $defRate = (float)($it['estimated_ptr'] ?? 0);
                                            $defQty = (float)($it['remaining_packs'] ?? 0);
                                            $defGross = $defQty * $defRate;
                                            $defTaxable = $defGross;
                                            $defTax = $defTaxable * ($defGst / 100);
                                            $defLineTotal = $defTaxable + $defTax;
                                        ?>
                                        <tr>
                                            <td class="row-num text-center"><?= $idx + 1 ?></td>
                                            <td>
                                                <strong class="text-dark"><?= esc($it['item_name']) ?></strong>
                                                <input type="hidden" name="items[<?= $idx ?>][item_code]" value="<?= (int)$it['item_id'] ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][item_name]" value="<?= esc($it['item_name']) ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][po_item_id]" value="<?= (int)$it['po_item_id'] ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][order_packs]" value="<?= (float)$it['remaining_packs'] ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][rejected_packs]" value="0">
                                                <input type="hidden" name="items[<?= $idx ?>][hsn_code]" value="3004">
                                                <input type="hidden" name="items[<?= $idx ?>][cgst_percent]" class="line-cgst" value="<?= $defGst / 2 ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][sgst_percent]" class="line-sgst" value="<?= $defGst / 2 ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][igst_percent]" class="line-igst" value="0">
                                                <input type="hidden" name="items[<?= $idx ?>][gross_amount]" class="line-gross-hid" value="<?= number_format($defGross, 2, '.', '') ?>">
                                                <input type="hidden" name="items[<?= $idx ?>][taxable_amount]" class="line-taxable-hid" value="<?= number_format($defTaxable, 2, '.', '') ?>">
                                            </td>
                                            <td>
                                                <input type="text" name="items[<?= $idx ?>][batch_no]" class="form-control form-control-sm font-monospace line-batch" placeholder="Batch #" required>
                                            </td>
                                            <td>
                                                <input type="date" name="items[<?= $idx ?>][expiry_date]" class="form-control form-control-sm line-expiry" required>
                                            </td>
                                            <td class="text-center">
                                                <input type="number" name="items[<?= $idx ?>][packing]" class="form-control form-control-sm text-center line-pack px-1" value="<?= (int)$it['units_per_pack'] ?>" min="1">
                                            </td>
                                            <td class="text-center">
                                                <input type="number" name="items[<?= $idx ?>][received_packs]" class="form-control form-control-sm text-center fw-bold line-rec px-1" value="<?= $defQty ?>" min="0" step="1" oninput="recalculateGrnTotals();" required>
                                            </td>
                                            <td class="text-center">
                                                <input type="number" name="items[<?= $idx ?>][free_packs]" class="form-control form-control-sm text-center text-success line-free px-1" value="0" min="0" step="1" oninput="recalculateGrnTotals();">
                                            </td>
                                            <td class="text-end">
                                                <input type="number" name="items[<?= $idx ?>][unit_purchase_rate]" class="form-control form-control-sm text-end line-rate px-1" value="<?= number_format($defRate, 2, '.', '') ?>" min="0" step="0.01" oninput="recalculateGrnTotals();">
                                            </td>
                                            <td class="text-end">
                                                <input type="number" name="items[<?= $idx ?>][mrp]" class="form-control form-control-sm text-end line-mrp px-1" value="<?= number_format($defRate * 1.3, 2, '.', '') ?>" min="0" step="0.01">
                                            </td>
                                            <td class="text-end font-monospace line-gross-disp bg-light">₹<?= number_format($defGross, 2) ?></td>
                                            <td class="text-center">
                                                <input type="number" name="items[<?= $idx ?>][discount_percent]" class="form-control form-control-sm text-center line-disc px-1" value="0.00" min="0" max="100" step="0.01" oninput="recalculateGrnTotals();">
                                            </td>
                                            <td class="text-center">
                                                <input type="number" name="items[<?= $idx ?>][sch_disc_per]" class="form-control form-control-sm text-center line-sch-per px-1" value="0.00" min="0" max="100" step="0.01" oninput="recalculateGrnTotals();">
                                            </td>
                                            <td class="text-center">
                                                <input type="number" name="items[<?= $idx ?>][sch_disc_amt]" class="form-control form-control-sm text-center line-sch-amt px-1" value="0.00" min="0" step="0.01" oninput="recalculateGrnTotals();">
                                            </td>
                                            <td class="text-end font-monospace line-taxable-disp bg-light">₹<?= number_format($defTaxable, 2) ?></td>
                                            <td class="text-center">
                                                <select name="items[<?= $idx ?>][gst_rate]" class="form-select form-select-sm text-center line-gst px-1" onchange="recalculateGrnTotals();">
                                                    <option value="5" <?= ($defGst == 5) ? 'selected' : '' ?>>5%</option>
                                                    <option value="12" <?= ($defGst == 12) ? 'selected' : '' ?>>12%</option>
                                                    <option value="18" <?= ($defGst == 18) ? 'selected' : '' ?>>18%</option>
                                                    <option value="28" <?= ($defGst == 28) ? 'selected' : '' ?>>28%</option>
                                                    <option value="0" <?= ($defGst == 0) ? 'selected' : '' ?>>0%</option>
                                                </select>
                                            </td>
                                            <td class="text-end font-monospace fw-semibold line-total-disp bg-light">₹<?= number_format($defLineTotal, 2) ?></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1" onclick="removeGrnRow(this);">✖</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr id="grn-no-items">
                                        <td colspan="17" class="text-center py-4 text-muted">
                                            No items loaded. Select an approved PO above or use the Search box to add medicines.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="13" class="text-end text-muted">Sub Total (Gross Amount):</th>
                                    <th colspan="3" class="text-end font-monospace" id="grn-gross-disp">₹0.00</th>
                                    <th></th>
                                </tr>
                                <tr>
                                    <th colspan="13" class="text-end text-muted">Total Discount (Trade &amp; Scheme):</th>
                                    <th colspan="3" class="text-end font-monospace text-danger" id="grn-discount-disp">₹0.00</th>
                                    <th></th>
                                </tr>
                                <tr>
                                    <th colspan="13" class="text-end text-dark">Taxable Amount:</th>
                                    <th colspan="3" class="text-end font-monospace fw-bold" id="grn-taxable-disp">₹0.00</th>
                                    <th></th>
                                </tr>
                                <tr id="row-cgst">
                                    <th colspan="13" class="text-end text-muted">CGST Amount:</th>
                                    <th colspan="3" class="text-end font-monospace text-primary" id="grn-cgst-disp">₹0.00</th>
                                    <th></th>
                                </tr>
                                <tr id="row-sgst">
                                    <th colspan="13" class="text-end text-muted">SGST Amount:</th>
                                    <th colspan="3" class="text-end font-monospace text-primary" id="grn-sgst-disp">₹0.00</th>
                                    <th></th>
                                </tr>
                                <tr id="row-igst" style="display:none;">
                                    <th colspan="13" class="text-end text-muted">IGST Amount:</th>
                                    <th colspan="3" class="text-end font-monospace text-primary" id="grn-igst-disp">₹0.00</th>
                                    <th></th>
                                </tr>
                                <tr>
                                    <th colspan="13" class="text-end text-dark">Total GST:</th>
                                    <th colspan="3" class="text-end font-monospace fw-bold" id="grn-tax-disp">₹0.00</th>
                                    <th></th>
                                </tr>
                                <tr>
                                    <th colspan="13" class="text-end text-muted">Round Off (+ / -):</th>
                                    <th colspan="3" class="text-end">
                                        <input type="number" step="0.01" name="round_off" id="grn_round_off" class="form-control form-control-sm text-end font-monospace d-inline-block" style="width: 105px;" value="0.00" oninput="recalculateGrnTotals();">
                                    </th>
                                    <th></th>
                                </tr>
                                <tr class="table-success">
                                    <th colspan="13" class="text-end fs-6">Net Inward Bill Total (Grand Total):</th>
                                    <th colspan="3" class="text-end fs-6 font-monospace text-success fw-bold" id="grn-net-disp">₹0.00</th>
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
                        <small class="d-block text-muted">Creates verified purchase invoice records and updates stock ledgers.</small>
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
            if (item.mrp) document.getElementById('grn_sel_mrp').value = parseFloat(item.mrp).toFixed(2);
            if (item.purchase_price) document.getElementById('grn_sel_rate').value = parseFloat(item.purchase_price).toFixed(2);
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
    var freePacks = parseFloat(document.getElementById('grn_sel_free_packs').value || 0);
    var packing = parseInt(document.getElementById('grn_sel_packing').value || 10);
    var rate = parseFloat(document.getElementById('grn_sel_rate').value || 100.00);
    var mrp = parseFloat(document.getElementById('grn_sel_mrp').value || 130.00);

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

    var gross = packs * rate;

    tr.innerHTML = `
        <td class="row-num text-center">${rowIdx + 1}</td>
        <td>
            <strong class="text-dark">${escapeHtml(name)}</strong>
            <input type="hidden" name="items[${rowIdx}][item_code]" value="${code}">
            <input type="hidden" name="items[${rowIdx}][item_name]" value="${escapeHtml(name)}">
            <input type="hidden" name="items[${rowIdx}][po_item_id]" value="">
            <input type="hidden" name="items[${rowIdx}][order_packs]" value="0">
            <input type="hidden" name="items[${rowIdx}][rejected_packs]" value="0">
            <input type="hidden" name="items[${rowIdx}][hsn_code]" value="3004">
            <input type="hidden" name="items[${rowIdx}][cgst_percent]" class="line-cgst" value="2.5">
            <input type="hidden" name="items[${rowIdx}][sgst_percent]" class="line-sgst" value="2.5">
            <input type="hidden" name="items[${rowIdx}][igst_percent]" class="line-igst" value="0">
            <input type="hidden" name="items[${rowIdx}][gross_amount]" class="line-gross-hid" value="${gross.toFixed(2)}">
            <input type="hidden" name="items[${rowIdx}][taxable_amount]" class="line-taxable-hid" value="${gross.toFixed(2)}">
        </td>
        <td>
            <input type="text" name="items[${rowIdx}][batch_no]" class="form-control form-control-sm font-monospace line-batch" value="${escapeHtml(batch)}" required>
        </td>
        <td>
            <input type="date" name="items[${rowIdx}][expiry_date]" class="form-control form-control-sm line-expiry" value="${expiry}" required>
        </td>
        <td class="text-center">
            <input type="number" name="items[${rowIdx}][packing]" class="form-control form-control-sm text-center line-pack px-1" value="${packing}" min="1">
        </td>
        <td class="text-center">
            <input type="number" name="items[${rowIdx}][received_packs]" class="form-control form-control-sm text-center fw-bold line-rec px-1" value="${packs}" min="1" oninput="recalculateGrnTotals();" required>
        </td>
        <td class="text-center">
            <input type="number" name="items[${rowIdx}][free_packs]" class="form-control form-control-sm text-center text-success line-free px-1" value="${freePacks}" min="0" oninput="recalculateGrnTotals();">
        </td>
        <td class="text-end">
            <input type="number" name="items[${rowIdx}][unit_purchase_rate]" class="form-control form-control-sm text-end line-rate px-1" value="${rate.toFixed(2)}" min="0" step="0.01" oninput="recalculateGrnTotals();">
        </td>
        <td class="text-end">
            <input type="number" name="items[${rowIdx}][mrp]" class="form-control form-control-sm text-end line-mrp px-1" value="${mrp.toFixed(2)}" min="0" step="0.01">
        </td>
        <td class="text-end font-monospace line-gross-disp bg-light">₹${gross.toFixed(2)}</td>
        <td class="text-center">
            <input type="number" name="items[${rowIdx}][discount_percent]" class="form-control form-control-sm text-center line-disc px-1" value="0.00" min="0" max="100" step="0.01" oninput="recalculateGrnTotals();">
        </td>
        <td class="text-center">
            <input type="number" name="items[${rowIdx}][sch_disc_per]" class="form-control form-control-sm text-center line-sch-per px-1" value="0.00" min="0" max="100" step="0.01" oninput="recalculateGrnTotals();">
        </td>
        <td class="text-center">
            <input type="number" name="items[${rowIdx}][sch_disc_amt]" class="form-control form-control-sm text-center line-sch-amt px-1" value="0.00" min="0" step="0.01" oninput="recalculateGrnTotals();">
        </td>
        <td class="text-end font-monospace line-taxable-disp bg-light">₹${gross.toFixed(2)}</td>
        <td class="text-center">
            <select name="items[${rowIdx}][gst_rate]" class="form-select form-select-sm text-center line-gst px-1" onchange="recalculateGrnTotals();">
                <option value="5" selected>5%</option>
                <option value="12">12%</option>
                <option value="18">18%</option>
                <option value="28">28%</option>
                <option value="0">0%</option>
            </select>
        </td>
        <td class="text-end font-monospace fw-semibold line-total-disp bg-light">₹0.00</td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1" onclick="removeGrnRow(this);">✖</button>
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
        tbody.innerHTML = '<tr id="grn-no-items"><td colspan="17" class="text-center py-4 text-muted">No items loaded.</td></tr>';
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
    var isInterState = (document.getElementById('grn_gst_type')?.value === 'inter_state');

    var totalGross = 0.0;
    var totalDisc = 0.0;
    var totalTaxable = 0.0;
    var totalCgst = 0.0;
    var totalSgst = 0.0;
    var totalIgst = 0.0;
    var totalTax = 0.0;

    rows.forEach(function(r) {
        var recPacks = parseFloat(r.querySelector('.line-rec')?.value || 0);
        var rate = parseFloat(r.querySelector('.line-rate')?.value || 0);
        var gross = recPacks * rate;

        var discPer = parseFloat(r.querySelector('.line-disc')?.value || 0);
        var schPer = parseFloat(r.querySelector('.line-sch-per')?.value || 0);
        var schAmt = parseFloat(r.querySelector('.line-sch-amt')?.value || 0);

        // Scheme discount calculation
        var schPerAmt = (schPer > 0) ? (gross * (schPer / 100)) : 0.0;
        var lineSch = schPerAmt + schAmt;
        var afterSch = Math.max(0.0, gross - lineSch);

        // Trade discount calculation
        var tradeDiscAmt = (discPer > 0) ? (afterSch * (discPer / 100)) : 0.0;
        var taxable = Math.max(0.0, afterSch - tradeDiscAmt);

        // GST calculation
        var gstRate = parseFloat(r.querySelector('.line-gst')?.value || 0);
        var cgstPer = 0.0;
        var sgstPer = 0.0;
        var igstPer = 0.0;
        var cgstAmt = 0.0;
        var sgstAmt = 0.0;
        var igstAmt = 0.0;

        if (isInterState) {
            igstPer = gstRate;
            igstAmt = taxable * (igstPer / 100);
        } else {
            cgstPer = gstRate / 2;
            sgstPer = gstRate / 2;
            cgstAmt = taxable * (cgstPer / 100);
            sgstAmt = taxable * (sgstPer / 100);
        }

        var lineTax = cgstAmt + sgstAmt + igstAmt;
        var lineTotal = taxable + lineTax;

        // Update displays
        var grossDisp = r.querySelector('.line-gross-disp');
        if (grossDisp) grossDisp.innerText = '₹' + gross.toFixed(2);

        var taxableDisp = r.querySelector('.line-taxable-disp');
        if (taxableDisp) taxableDisp.innerText = '₹' + taxable.toFixed(2);

        var totalDisp = r.querySelector('.line-total-disp');
        if (totalDisp) totalDisp.innerText = '₹' + lineTotal.toFixed(2);

        // Update hidden inputs
        var cgstInp = r.querySelector('.line-cgst');
        if (cgstInp) cgstInp.value = cgstPer;

        var sgstInp = r.querySelector('.line-sgst');
        if (sgstInp) sgstInp.value = sgstPer;

        var igstInp = r.querySelector('.line-igst');
        if (igstInp) igstInp.value = igstPer;

        var grossInp = r.querySelector('.line-gross-hid');
        if (grossInp) grossInp.value = gross.toFixed(2);

        var taxableInp = r.querySelector('.line-taxable-hid');
        if (taxableInp) taxableInp.value = taxable.toFixed(2);

        // Accumulate
        totalGross += gross;
        totalDisc += (lineSch + tradeDiscAmt);
        totalTaxable += taxable;
        totalCgst += cgstAmt;
        totalSgst += sgstAmt;
        totalIgst += igstAmt;
        totalTax += lineTax;
    });

    document.getElementById('grn-gross-disp').innerText = '₹' + totalGross.toFixed(2);
    document.getElementById('grn-discount-disp').innerText = '₹' + totalDisc.toFixed(2);
    document.getElementById('grn-taxable-disp').innerText = '₹' + totalTaxable.toFixed(2);

    if (isInterState) {
        document.getElementById('row-cgst').style.display = 'none';
        document.getElementById('row-sgst').style.display = 'none';
        document.getElementById('row-igst').style.display = '';
        document.getElementById('grn-igst-disp').innerText = '₹' + totalIgst.toFixed(2);
    } else {
        document.getElementById('row-cgst').style.display = '';
        document.getElementById('row-sgst').style.display = '';
        document.getElementById('row-igst').style.display = 'none';
        document.getElementById('grn-cgst-disp').innerText = '₹' + totalCgst.toFixed(2);
        document.getElementById('grn-sgst-disp').innerText = '₹' + totalSgst.toFixed(2);
    }

    document.getElementById('grn-tax-disp').innerText = '₹' + totalTax.toFixed(2);

    var roundOff = parseFloat(document.getElementById('grn_round_off')?.value || 0);
    var netPayable = totalTaxable + totalTax + roundOff;
    document.getElementById('grn-net-disp').innerText = '₹' + netPayable.toFixed(2);
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

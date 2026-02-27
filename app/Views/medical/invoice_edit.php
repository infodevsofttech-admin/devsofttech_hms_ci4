<?php $isIpdInvoice = ((int)($invoice->ipd_id ?? 0) > 0); ?>

<div class="row g-3">
    <div class="<?= $isIpdInvoice ? 'col-lg-12' : 'col-lg-8' ?>">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Medical Invoice No. <?= esc($invoice->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) ((int)($invoice->id ?? 0)), -7, 7), 7, '0', STR_PAD_LEFT))) ?></h5>
                <div class="d-flex gap-1 ms-auto">
                    <?php if ((int)($invoice->ipd_id ?? 0) > 0): ?>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="javascript:load_form_div('<?= base_url('Medical/list_med_inv/' . (int)($invoice->ipd_id ?? 0)) ?>','medical-main');">Back to IPD Invoice List</button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-warning btn-sm" onclick="javascript:load_form_div('<?= base_url('Medical/edit_invoice_edit/' . (int)($invoice->id ?? 0)) ?>','medical-main');">Edit Invoice Header</button>
                    <button type="button" class="btn btn-success btn-sm" onclick="javascript:load_form_div('<?= base_url('Medical/invoice_edit/' . (int)($invoice->id ?? 0)) ?>','medical-main');">Refresh Invoice</button>
                </div>
            </div>
            <div class="card-body pt-3">

                <?php if (!empty($message)): ?>
                    <div class="alert alert-info py-2"><?= esc($message) ?></div>
                <?php endif; ?>

                <div id="invoice-ajax-notice" class="alert py-2 d-none" role="alert"></div>

                <?php if (!empty($isFinalized)): ?>
                    <div class="alert alert-warning py-2">Invoice is finalized. Edit actions are locked.</div>
                <?php endif; ?>

                <div class="small mb-2">
                    <strong>Name :</strong> <?= esc($invoice->inv_name ?? '-') ?> /
                    <strong>P Code :</strong> <?= esc($invoice->patient_code ?? '-') ?> /
                    <?php if ((int)($invoice->ipd_id ?? 0) > 0): ?>
                        <strong>IPD ID :</strong> <?= esc((string) ((int)($invoice->ipd_id ?? 0))) ?> /
                    <?php endif; ?>
                    <strong>Invoice No. :</strong> <?= esc($invoice->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) ((int)($invoice->id ?? 0)), -7, 7), 7, '0', STR_PAD_LEFT))) ?> /
                    <strong>Date :</strong> <?= esc(!empty($invoice->inv_date) ? date('d/m/Y', strtotime((string)$invoice->inv_date)) : '-') ?>
                </div>

                <?php if (empty($isFinalized)): ?>
                    <form method="post" action="<?= base_url('Medical/go_final') ?>" class="row g-2 mb-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="inv_id" value="<?= (int)($invoice->id ?? 0) ?>">
                        <div class="col-md-2">
                            <label class="form-label form-label-sm mb-1">Doctor ID</label>
                            <input type="number" name="doc_id" value="<?= esc($invoice->doc_id ?? 0) ?>" class="form-control form-control-sm" min="0" step="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm mb-1">Doctor Name</label>
                            <input type="text" name="doc_name" value="<?= esc($invoice->doc_name ?? '') ?>" class="form-control form-control-sm">
                        </div>
                    </form>
                <?php endif; ?>

                <div id="show_item_list" class="table-responsive mb-3">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th>Batch No</th>
                                <th>Exp.</th>
                                <th class="text-end">Rate</th>
                                <th class="text-end">S.Qty.</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Disc.</th>
                                <th class="text-end">Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $itemCodeCounts = [];
                                if (!empty($items)) {
                                    foreach ($items as $tmpItem) {
                                        $codeKey = (string) ($tmpItem->item_code ?? '');
                                        if ($codeKey !== '') {
                                            $itemCodeCounts[$codeKey] = ($itemCodeCounts[$codeKey] ?? 0) + 1;
                                        }
                                    }
                                }
                            ?>
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $i => $item): ?>
                                    <?php
                                        $rowStyle = '';
                                        $itemNameStyle = '';

                                        $isReturnRow = ((int) ($item->sale_return ?? 0) === 1)
                                            || ((float) ($item->amount ?? 0) < 0)
                                            || ((float) ($item->tamount ?? 0) < 0)
                                            || ((float) ($item->twdisc_amount ?? 0) < 0);

                                        if ($isReturnRow) {
                                            $rowStyle = 'background:orange;';
                                        }

                                        $expiryDays = 1000;
                                        if (!empty($item->expiry)) {
                                            $expiryTs = strtotime((string) $item->expiry);
                                            if ($expiryTs !== false) {
                                                $expiryDays = (int) floor(($expiryTs - strtotime(date('Y-m-d'))) / 86400);
                                            }
                                        }

                                        if ($expiryDays < 90) {
                                            $rowStyle = 'color:red;';
                                        }

                                        $sQty = (float) ($item->s_qty ?? 0);
                                        if ($sQty < 6) {
                                            $rowStyle = 'color:red;';
                                        }

                                        $itemCode = (string) ($item->item_code ?? '');
                                        if ($itemCode !== '' && (int) ($itemCodeCounts[$itemCode] ?? 0) > 1) {
                                            $itemNameStyle = 'color:green;';
                                        }
                                    ?>
                                    <tr<?= $rowStyle !== '' ? ' style="' . esc($rowStyle, 'attr') . '"' : '' ?>>
                                        <td><?= $i + 1 ?></td>
                                        <td<?= $itemNameStyle !== '' ? ' style="' . esc($itemNameStyle, 'attr') . '"' : '' ?>><?= esc($item->item_Name ?? '-') ?></td>
                                        <td><?= esc($item->batch_no ?? '-') ?></td>
                                        <td><?= esc(!empty($item->expiry) ? date('Y-m-d', strtotime((string)$item->expiry)) : '-') ?></td>
                                        <td class="text-end"><?= esc(number_format((float)($item->price ?? 0), 2)) ?></td>
                                        <td class="text-end"><?= esc(number_format((float)($item->qty ?? 0), 0, '.', '')) ?></td>
                                        <td class="text-end">
                                            <?php if (empty($isFinalized) && (int)($item->sale_return ?? 0) === 0): ?>
                                                <form method="post" action="<?= base_url('Medical/update_item_qty') ?>" class="d-inline-flex gap-1 align-items-center quick-qty-form m-0">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="inv_id" value="<?= (int)($invoice->id ?? 0) ?>">
                                                    <input type="hidden" name="item_id" value="<?= (int)($item->id ?? 0) ?>">
                                                    <input type="number" name="u_qty" value="<?= esc(number_format((float)($item->qty ?? 0), 0, '.', '')) ?>" min="1" step="1" class="form-control form-control-sm" style="width:65px;">
                                                    <button class="btn btn-sm btn-outline-info" type="submit">✎</button>
                                                </form>
                                            <?php else: ?>
                                                <?= esc(number_format((float)($item->qty ?? 0), 2)) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end"><?= esc(number_format((float)($item->disc_amount ?? 0), 2)) ?></td>
                                        <td class="text-end"><?= esc(number_format((float)($item->tamount ?? 0), 2)) ?></td>
                                        <td>
                                            <?php if (empty($isFinalized)): ?>
                                                <form method="post" action="<?= base_url('Medical/remove_item') ?>" class="m-0 quick-remove-form">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="inv_id" value="<?= (int)($invoice->id ?? 0) ?>">
                                                    <input type="hidden" name="item_id" value="<?= (int)($item->id ?? 0) ?>">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">✖</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small">Locked</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted">No items in invoice.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="8" class="text-end">Gross Total</th>
                                <th class="text-end"><?= esc(number_format((float)($totals->gross ?? 0), 2)) ?></th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="8" class="text-end">Net Total</th>
                                <th class="text-end"><?= esc(number_format((float)($totals->net ?? 0), 2)) ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <?php if (empty($isFinalized)): ?>
                    <div class="border-top pt-3">
                        <h6 class="mb-2">Product Search</h6>
                        <div class="card border-secondary mb-3">
                            <div class="card-body py-2">
                                <div class="row g-2 align-items-center mb-2">
                                    <div class="col-md-8">
                                        <label class="form-label form-label-sm mb-1">Product Search:</label>
                                        <input class="form-control form-control-sm" name="input_drug" id="input_drug" placeholder="Like Item Code , Item Name" type="text" autocomplete="off">
                                    </div>
                                    <div class="col-md-4">
                                        <span class="text-muted" id="input_product_code"></span>
                                    </div>
                                </div>

                                <input type="hidden" id="l_ssno" name="l_ssno" value="0">
                                <input type="hidden" id="item_code" name="item_code" value="">
                                <input type="hidden" id="hid_expiry_alert" name="hid_expiry_alert" value="">
                                <input type="hidden" id="hid_c_qty" name="hid_c_qty" value="0">

                                <p class="mb-2 small">
                                    <span class="text-success" id="input_product_name"></span>
                                    <span class="text-info" id="input_batch"></span>
                                    <span class="text-danger" id="input_product_mrp"></span>
                                    <span class="text-warning" id="stock_product_qty"></span>
                                </p>

                                <form method="post" action="<?= base_url('Medical/add_item') ?>" class="row g-2 align-items-end" id="auto-add-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="inv_id" value="<?= (int)($invoice->id ?? 0) ?>">
                                    <input type="hidden" name="stock_id" id="stock_id_input" value="0">

                                    <div class="col-md-3">
                                        <label class="form-label form-label-sm mb-1">Unit Rate</label>
                                        <input class="form-control form-control-sm" name="input_product_unit_rate" id="input_product_unit_rate" placeholder="Unit Rate" autocomplete="off" readonly>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label form-label-sm mb-1">Qty</label>
                                        <input class="form-control form-control-sm" name="qty" id="input_product_qty" placeholder="Qty Like No. of Tab." type="number" min="0" step="1" value="0" autocomplete="off">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label form-label-sm mb-1">Disc %</label>
                                        <input class="form-control form-control-sm" name="disc_per" id="input_disc" placeholder="Discount %" type="number" min="0" max="100" step="0.01" value="0" autocomplete="off">
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-sm btn-primary w-100" type="submit" id="additem" disabled>Add</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <?php if (!empty($stockRows)): ?>
                            <div class="table-responsive mb-2">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Batch</th>
                                            <th>Exp.</th>
                                            <th class="text-end">S.Qty</th>
                                            <th class="text-end">Rate</th>
                                            <th>Add</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stockRows as $row): ?>
                                            <tr>
                                                <td><?= esc($row->item_name ?? '-') ?></td>
                                                <td><?= esc($row->batch_no ?? '-') ?></td>
                                                <td><?= esc($row->expiry_date ?? '-') ?></td>
                                                <td class="text-end"><?= esc($row->current_qty ?? 0) ?></td>
                                                <td class="text-end"><?= esc(number_format((float)($row->selling_unit_rate ?? 0), 2)) ?></td>
                                                <td>
                                                    <form method="post" action="<?= base_url('Medical/add_item') ?>" class="d-flex gap-1 align-items-center quick-add-form">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="inv_id" value="<?= (int)($invoice->id ?? 0) ?>">
                                                        <input type="hidden" name="stock_id" value="<?= (int)($row->stock_id ?? 0) ?>">
                                                        <input type="number" name="qty" value="1" min="1" step="1" class="form-control form-control-sm" style="width:80px;">
                                                        <input type="number" name="disc_per" value="0" min="0" max="100" step="0.01" class="form-control form-control-sm" style="width:90px;" title="Disc %">
                                                        <button class="btn btn-sm btn-primary" type="submit">Add</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif (!empty($query)): ?>
                            <div class="text-muted small">No stock items found for search.</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-3">
                    <?php if (empty($isFinalized)): ?>
                        <a class="btn btn-success btn-sm" href="javascript:load_form_div('<?= base_url('Medical/final_invoice/' . (int)($invoice->id ?? 0)) ?>','medical-main');">Final Invoice</a>
                    <?php endif; ?>
                    <a class="btn btn-outline-dark btn-sm" target="_blank" href="<?= base_url('Medical/invoice_print/' . (int)($invoice->id ?? 0)) ?>">PDF Bill</a>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$isIpdInvoice): ?>
    <div class="col-lg-4">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Sale Medicine</h5>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-warning btn-sm" onclick="window.scrollTo({top:0,behavior:'smooth'})">Search OLD</button>
                        <button type="button" class="btn btn-success btn-sm" onclick="window.scrollTo({top:0,behavior:'smooth'})">Search Panel</button>
                    </div>
                </div>
            </div>
            <div class="card-body pt-2">
                

                <?php if (!empty($invoice->patient_id) && !empty($oldInvoices)): ?>
                    <div style="max-height: 78vh; overflow:auto;">
                        <?php foreach ($oldInvoices as $old): ?>
                            <?php $oldInvId = (int)($old->id ?? 0); ?>
                            <table class="table table-sm table-bordered mb-2">
                                <thead>
                                    <tr class="table-warning">
                                        <th>#</th>
                                        <th><?= esc($old->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) $oldInvId, -7, 7), 7, '0', STR_PAD_LEFT))) ?></th>
                                        <th><?= esc(!empty($old->inv_date) ? date('Y-m-d', strtotime((string)$old->inv_date)) : '-') ?></th>
                                        <th colspan="3" class="text-end">
                                            <a class="btn btn-sm btn-outline-dark" href="javascript:load_form('<?= base_url('Medical/invoice_edit/' . $oldInvId) ?>','Medical Invoice');">Show Bill</a>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th>#</th>
                                        <th>Item</th>
                                        <th>Bat.</th>
                                        <th>Exp.Dt</th>
                                        <th>Qty</th>
                                        <th>R.Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($old->_items)): ?>
                                        <?php foreach ($old->_items as $n => $oldItem): ?>
                                            <tr>
                                                <td><?= (int)$n + 1 ?></td>
                                                <td><?= esc($oldItem->item_Name ?? '-') ?></td>
                                                <td><?= esc($oldItem->batch_no ?? '-') ?></td>
                                                <td><?= esc(!empty($oldItem->expiry) ? date('m-Y', strtotime((string)$oldItem->expiry)) : '-') ?></td>
                                                <td><?= esc(number_format((float)($oldItem->qty ?? 0), 0)) ?></td>
                                                <td>
                                                    <?php if (empty($isFinalized) && !empty($oldItem->id)): ?>
                                                        <form method="post" action="<?= base_url('Medical/add_remove_item') ?>" class="d-flex gap-1 align-items-center quick-return-form m-0">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="inv_id" value="<?= (int)($invoice->id ?? 0) ?>">
                                                            <input type="hidden" name="itemid" value="<?= (int)($oldItem->id ?? 0) ?>">
                                                            <input type="number" name="rqty" value="1" min="1" step="1" class="form-control form-control-sm" style="width:60px;">
                                                            <button class="btn btn-sm btn-outline-danger" type="submit">⊖</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No item rows.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (!empty($invoice->patient_id)): ?>
                    <div class="text-muted">No old invoices for this patient.</div>
                <?php else: ?>
                    <div class="text-muted">Walk-in invoice. Previous patient history is unavailable.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    (function () {
        var invoiceId = <?= (int)($invoice->id ?? 0) ?>;

        function refreshInvoicePanel() {
            if (typeof load_form_div === 'function') {
                load_form_div('<?= base_url('Medical/invoice_edit/' . (int)($invoice->id ?? 0)) ?>', 'medical-main');
                return;
            }
            window.location.href = '<?= base_url('Medical/invoice_edit/' . (int)($invoice->id ?? 0)) ?>';
        }

        function markFocusAfterReload() {
            try {
                window.sessionStorage.setItem('medical_invoice_focus_after_add', '1');
            } catch (e) {}
        }

        function storeNoticeAfterReload(type, text) {
            try {
                window.sessionStorage.setItem('medical_invoice_notice_type', type || 'info');
                window.sessionStorage.setItem('medical_invoice_notice_text', text || '');
            } catch (e) {}
        }

        function showInlineNotice(type, text) {
            var box = document.getElementById('invoice-ajax-notice');
            if (!box) {
                return;
            }

            box.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-info', 'alert-warning');
            if (type === 'success') {
                box.classList.add('alert-success');
            } else if (type === 'danger') {
                box.classList.add('alert-danger');
            } else if (type === 'warning') {
                box.classList.add('alert-warning');
            } else {
                box.classList.add('alert-info');
            }

            box.textContent = text || '';
        }

        function consumeNoticeAfterReload() {
            var text = '';
            var type = 'info';
            try {
                text = window.sessionStorage.getItem('medical_invoice_notice_text') || '';
                type = window.sessionStorage.getItem('medical_invoice_notice_type') || 'info';
                window.sessionStorage.removeItem('medical_invoice_notice_text');
                window.sessionStorage.removeItem('medical_invoice_notice_type');
            } catch (e) {}

            if (text) {
                showInlineNotice(type, text);
            }
        }

        function focusProductSearchIfMarked() {
            var shouldFocus = false;
            try {
                shouldFocus = window.sessionStorage.getItem('medical_invoice_focus_after_add') === '1';
                if (shouldFocus) {
                    window.sessionStorage.removeItem('medical_invoice_focus_after_add');
                }
            } catch (e) {}

            if (!shouldFocus) {
                return;
            }

            setTimeout(function () {
                var inputDrug = document.getElementById('input_drug');
                if (!inputDrug) {
                    return;
                }
                inputDrug.focus();
                if (typeof inputDrug.select === 'function') {
                    inputDrug.select();
                }
            }, 120);
        }

        function ajaxSubmitForm(form, done) {
            var formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function () {
                if (typeof done === 'function') {
                    done(true);
                }
            }).catch(function () {
                if (typeof done === 'function') {
                    done(false);
                }
            });
        }

        function ajaxSubmitReturnForm(form, done) {
            var formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                if (typeof done === 'function') {
                    done(true, data || {});
                }
            }).catch(function () {
                if (typeof done === 'function') {
                    done(false, null);
                }
            });
        }

        function ensureJqueryUi(callback) {
            if (!window.jQuery) {
                return;
            }

            if (window.jQuery.ui && window.jQuery.ui.autocomplete) {
                callback();
                return;
            }

            if (!document.getElementById('jq-ui-css')) {
                var css = document.createElement('link');
                css.id = 'jq-ui-css';
                css.rel = 'stylesheet';
                css.href = '<?= base_url('assets/vendor/jquery-ui/jquery-ui.min.css') ?>';
                document.head.appendChild(css);
            }

            if (document.getElementById('jq-ui-js')) {
                setTimeout(function () { ensureJqueryUi(callback); }, 100);
                return;
            }

            var script = document.createElement('script');
            script.id = 'jq-ui-js';
            script.src = '<?= base_url('assets/vendor/jquery-ui/jquery-ui.min.js') ?>';
            script.onload = callback;
            document.body.appendChild(script);
        }

        function setAutoItem(item) {
            var stockInput = document.getElementById('l_ssno');
            var stockIdInput = document.getElementById('stock_id_input');
            var addBtn = document.getElementById('additem');
            var qtyInput = document.getElementById('input_product_qty');
            var unitRateInput = document.getElementById('input_product_unit_rate');
            var itemCodeInput = document.getElementById('item_code');

            document.getElementById('input_product_code').innerHTML = '| Product Code: ' + (item.l_item_code || '-');
            document.getElementById('input_product_name').innerHTML = 'Name: ' + (item.value || '-');
            document.getElementById('input_batch').innerHTML = ' | Batch No.: ' + (item.l_Batch || '-') + ' | Exp.Dt: ' + (item.l_Expiry || '-');
            document.getElementById('input_product_mrp').innerHTML = ' | MRP: ' + (item.l_mrp || '-') + ' | Unit Rate: ' + (item.l_unit_rate || '-');
            document.getElementById('stock_product_qty').innerHTML = ' |Qty : ' + (item.l_c_qty || '-') + ' |Pak : ' + (item.l_packing || '-');

            document.getElementById('hid_expiry_alert').value = item.expiry_alert || '';
            document.getElementById('hid_c_qty').value = item.l_c_qty || '';

            if (stockInput) {
                stockInput.value = item.l_ss_no || 0;
            }
            if (stockIdInput) {
                stockIdInput.value = item.l_ss_no || 0;
            }
            if (unitRateInput) {
                unitRateInput.value = item.l_unit_rate || '';
            }
            if (itemCodeInput) {
                itemCodeInput.value = item.item_code || '';
            }
            if (addBtn) {
                addBtn.disabled = !item.l_ss_no;
            }
            if (qtyInput) {
                qtyInput.focus();
            }
        }

        function initDrugAutocomplete() {
            if (!window.jQuery || !window.jQuery.ui || !window.jQuery.ui.autocomplete) {
                return;
            }
            var $ = window.jQuery;
            $("#input_drug").autocomplete({
                source: function (request, response) {
                    $.getJSON("<?= base_url('Medical/get_drug') ?>", request, function (data) {
                        response(data || []);
                    });
                },
                minLength: 1,
                autofocus: true,
                select: function (event, ui) {
                    setAutoItem(ui.item || {});
                }
            }).autocomplete("instance")._renderItem = function (ul, item) {
                var label = item.label || '';
                if ((item.l_new_stock || '0') > 0) {
                    label = ' 💡 ' + label;
                }

                if (parseFloat(item.expiry_alert || '999') < 2) {
                    return $("<li class='ui-state-disabled'>")
                        .append("<div>" + label + "<br>" + (item.desc || '') + "</div>")
                        .appendTo(ul);
                }

                return $("<li>")
                    .append("<div>" + label + "<br>" + (item.desc || '') + "</div>")
                    .appendTo(ul);
            };
        }

            ensureJqueryUi(initDrugAutocomplete);
            focusProductSearchIfMarked();
            consumeNoticeAfterReload();

        var forms = document.querySelectorAll('.quick-add-form');
        forms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                }

                ajaxSubmitForm(form, function (ok) {
                    if (!ok) {
                        if (button) {
                            button.disabled = false;
                        }
                        alert('Unable to add item. Please try again.');
                        return;
                    }
                    markFocusAfterReload();
                    refreshInvoicePanel();
                });
            });
        });

        var removeForms = document.querySelectorAll('.quick-remove-form');
        removeForms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                }

                ajaxSubmitForm(form, function (ok) {
                    if (!ok) {
                        if (button) {
                            button.disabled = false;
                        }
                        alert('Unable to remove item. Please try again.');
                        return;
                    }
                    refreshInvoicePanel();
                });
            });
        });

        var qtyForms = document.querySelectorAll('.quick-qty-form');
        qtyForms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                var qtyInput = form.querySelector('input[name="u_qty"]');
                var qtyValue = parseFloat((qtyInput && qtyInput.value) ? qtyInput.value : '0');
                if (!qtyValue || qtyValue <= 0) {
                    showInlineNotice('danger', 'Enter valid Qty greater than 0.');
                    if (qtyInput) {
                        qtyInput.focus();
                    }
                    return;
                }

                var button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                }

                ajaxSubmitReturnForm(form, function (ok, data) {
                    if (!ok) {
                        if (button) {
                            button.disabled = false;
                        }
                        showInlineNotice('danger', 'Unable to update qty. Please try again.');
                        return;
                    }

                    var updateValue = parseInt((data && data.update) ? data.update : '0', 10);
                    if (!updateValue) {
                        if (button) {
                            button.disabled = false;
                        }
                        showInlineNotice('danger', (data && data.msg_text) ? data.msg_text : 'Qty update failed.');
                        return;
                    }

                    storeNoticeAfterReload('success', (data && data.msg_text) ? data.msg_text : 'Qty updated.');
                    refreshInvoicePanel();
                });
            });
        });

        var returnForms = document.querySelectorAll('.quick-return-form');
        returnForms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                var qtyInput = form.querySelector('input[name="rqty"]');
                var qtyValue = parseFloat((qtyInput && qtyInput.value) ? qtyInput.value : '0');
                if (!qtyValue || qtyValue <= 0) {
                    alert('Enter valid return Qty greater than 0.');
                    if (qtyInput) {
                        qtyInput.focus();
                    }
                    return;
                }

                var button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                }

                ajaxSubmitReturnForm(form, function (ok, data) {
                    if (!ok) {
                        if (button) {
                            button.disabled = false;
                        }
                        showInlineNotice('danger', 'Unable to process return item. Please try again.');
                        return;
                    }

                    var updateValue = parseInt((data && data.update) ? data.update : '0', 10);
                    if (!updateValue) {
                        if (button) {
                            button.disabled = false;
                        }
                        showInlineNotice('danger', (data && data.msg_text) ? data.msg_text : 'Return item was not added.');
                        return;
                    }

                    storeNoticeAfterReload('success', (data && data.msg_text) ? data.msg_text : 'Return item updated.');
                    refreshInvoicePanel();
                });
            });
        });

        var autoAddForm = document.getElementById('auto-add-form');
        if (autoAddForm) {
            autoAddForm.addEventListener('submit', function (event) {
                var stockInput = document.getElementById('l_ssno');
                var stockIdInput = document.getElementById('stock_id_input');
                var addBtn = document.getElementById('additem');
                var stockId = parseInt((stockInput && stockInput.value) ? stockInput.value : '0', 10);
                if (!stockId && stockIdInput) {
                    stockId = parseInt(stockIdInput.value || '0', 10);
                }
                if (!stockId) {
                    event.preventDefault();
                    alert('Select medicine from autocomplete list.');
                    return;
                }

                var qtyInput = document.getElementById('input_product_qty');
                var qtyValue = parseFloat((qtyInput && qtyInput.value) ? qtyInput.value : '0');
                if (!qtyValue || qtyValue <= 0) {
                    event.preventDefault();
                    alert('Enter valid Qty greater than 0.');
                    if (qtyInput) {
                        qtyInput.focus();
                    }
                    return;
                }

                if (addBtn) {
                    addBtn.disabled = true;
                }

                event.preventDefault();
                ajaxSubmitForm(autoAddForm, function (ok) {
                    if (!ok) {
                        if (addBtn) {
                            addBtn.disabled = false;
                        }
                        alert('Unable to add item. Please try again.');
                        return;
                    }
                    markFocusAfterReload();
                    refreshInvoicePanel();
                });
            });
        }

        var inputDrug = document.getElementById('input_drug');
        if (inputDrug) {
            inputDrug.accessKey = 's';
        }
        var addBtn = document.getElementById('additem');
        if (addBtn) {
            addBtn.accessKey = 'i';
        }
    })();
</script>

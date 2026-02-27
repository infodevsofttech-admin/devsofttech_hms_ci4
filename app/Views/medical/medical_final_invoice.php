<?php
    $invoiceId = (int) ($invoice->id ?? 0);
    $balance = (float) ($paymentSummary['balance'] ?? 0);
    $net = (float) ($paymentSummary['net'] ?? ($invoice->net_amount ?? 0));
    $paid = (float) ($paymentSummary['paid'] ?? ($invoice->payment_received ?? 0));
    $deduction = (float) ($paymentSummary['extra_discount'] ?? ($invoice->discount_amount ?? 0));
    $deductionRemark = (string) ($paymentSummary['discount_remark'] ?? ($invoice->discount_remark ?? ''));
    $allowPaymentMode = ((int) ($invoice->ipd_credit ?? 0) === 0) && ((int) ($invoice->case_credit ?? 0) === 0) && ((int) ($invoice->group_invoice_id ?? 0) === 0);
?>

<div class="card border-0" id="medical-final-invoice">
    <div class="card-header bg-light text-dark d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
        <h5 class="mb-0">Medical Invoice <small class="text-muted">No. : <?= esc($invoice->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) $invoiceId, -7, 7), 7, '0', STR_PAD_LEFT))) ?></small></h5>
        <div class="d-flex gap-1">
            <?php if (!empty($ipd) && !empty($ipd->id)): ?>
                <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('Medical/list_med_inv/' . (int)($ipd->id ?? 0)) ?>','medical-main');">IPD Bills</a>
            <?php elseif (!empty($orgCase) && !empty($orgCase->id)): ?>
                <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('Medical/list_med_orginv/' . (int)($orgCase->id ?? 0)) ?>','medical-main');">Org Bills</a>
            <?php else: ?>
                <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('Medical/Invoice_Med_Draft?status=all') ?>','medical-main');">All Bills</a>
            <?php endif; ?>
            <?php if (!empty($canEditInvoice)): ?>
                <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= base_url('Medical/invoice_print/' . $invoiceId) ?>">Print</a>
            <?php else: ?>
                <?php $printAlertText = (string) ($editBlockReason ?? 'No permission to print this invoice.'); ?>
                <a class="btn btn-sm btn-outline-secondary" href="javascript:void(0);" title="<?= esc($printAlertText) ?>" onclick='return showMedicalPermissionAlert(this, <?= json_encode($printAlertText) ?>);'>Print</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body border-top border-danger" style="border-top-width:2px !important;">
        <?php if (!empty($message)): ?>
            <div class="alert alert-warning py-2 mb-3"><?= esc($message) ?></div>
        <?php endif; ?>
        <div class="js-permission-alert"></div>

        <div class="small mb-3 fw-semibold">
            <strong>Name :</strong> <?= esc($invoice->inv_name ?? '-') ?> /
            <strong>P Code :</strong> <?= esc($invoice->patient_code ?? '-') ?> /
            <strong>Invoice No. :</strong> <?= esc($invoice->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) $invoiceId, -7, 7), 7, '0', STR_PAD_LEFT))) ?> /
            <strong>Date :</strong> <?= esc(!empty($invoice->inv_date) ? date('d/m/Y', strtotime((string)$invoice->inv_date)) : '-') ?>
            <?php if (!empty($ipd) && !empty($ipd->ipd_code)): ?> / <strong>IPD :</strong> <?= esc($ipd->ipd_code) ?><?php endif; ?>
            <?php if (!empty($orgCase) && !empty($orgCase->case_id_code)): ?> / <strong>Org Case :</strong> <?= esc($orgCase->case_id_code) ?><?php endif; ?>
            <?php if (!empty($canEditInvoice)): ?>
                <a class="btn btn-warning btn-sm ms-2" href="javascript:load_form_div('<?= base_url('Medical/open_invoice_edit/' . $invoiceId) ?>','medical-main');">Open Bill For Edit</a>
            <?php else: ?>
                <?php $editAlertText = (string) ($editBlockReason ?? 'No permission to edit this invoice.'); ?>
                <a class="btn btn-warning btn-sm ms-2" href="javascript:void(0);" title="<?= esc($editAlertText) ?>" onclick='return showMedicalPermissionAlert(this, <?= json_encode($editAlertText) ?>);'>Open Bill For Edit</a>
            <?php endif; ?>
        </div>

        <div class="jsError"></div>

        <div class="table-responsive mb-3">
            <table class="table table-sm table-striped table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item code</th>
                        <th>Item Name</th>
                        <th>Formulation</th>
                        <th>Batch No</th>
                        <th>Exp.</th>
                        <th class="text-end">Rate</th>
                        <th class="text-end">Qty.</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Disc.</th>
                        <th>HSNCODE/GST%</th>
                        <th class="text-end">Inc. GST</th>
                        <th class="text-end">Net Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $rowCgstTotal = 0.0;
                        $rowSgstTotal = 0.0;
                    ?>
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $i => $item): ?>
                            <?php
                                $lineNet = (float) ($item->twdisc_amount ?? $item->tamount ?? $item->amount ?? 0);
                                $lineBase = (float) ($item->amount ?? 0);
                                $cgstPer = (float) ($item->CGST_per ?? 0);
                                $sgstPer = (float) ($item->SGST_per ?? 0);

                                $cgstValue = (float) ($item->CGST ?? ($item->c_gst_amt ?? 0));
                                $sgstValue = (float) ($item->SGST ?? ($item->s_gst_amt ?? 0));

                                $gstPerTotal = $cgstPer + $sgstPer;
                                if (($cgstValue <= 0 || $sgstValue <= 0) && $gstPerTotal > 0 && $lineNet > 0) {
                                    $taxableFromInclusive = $lineNet * 100 / (100 + $gstPerTotal);
                                    $taxTotal = $lineNet - $taxableFromInclusive;
                                    $cgstValue = $taxTotal * ($cgstPer / $gstPerTotal);
                                    $sgstValue = $taxTotal * ($sgstPer / $gstPerTotal);
                                } elseif (($cgstValue <= 0 || $sgstValue <= 0) && $gstPerTotal > 0 && $lineBase > 0) {
                                    $cgstValue = $lineBase * $cgstPer / 100;
                                    $sgstValue = $lineBase * $sgstPer / 100;
                                }

                                $rowCgstTotal += (float) $cgstValue;
                                $rowSgstTotal += (float) $sgstValue;
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= esc($item->item_code ?? '-') ?></td>
                                <td><?= esc($item->item_Name ?? '-') ?></td>
                                <td><?= esc($item->formulation ?? '-') ?></td>
                                <td><?= esc($item->batch_no ?? '-') ?></td>
                                <td><?= esc(!empty($item->expiry) ? date('Y-m-d', strtotime((string)$item->expiry)) : '-') ?></td>
                                <td class="text-end"><?= esc(number_format((float)($item->price ?? 0), 2)) ?></td>
                                <td class="text-end"><?= esc(number_format((float)($item->qty ?? 0), 2)) ?></td>
                                <td class="text-end"><?= esc(number_format((float)($item->amount ?? 0), 2)) ?></td>
                                <td class="text-end"><?= esc(number_format((float)($item->disc_whole ?? $item->disc_amount ?? 0), 2)) ?></td>
                                <td><?= esc(($item->HSNCODE ?? '-') . '/' . number_format((float) (($item->CGST_per ?? 0) + ($item->SGST_per ?? 0)), 2)) ?></td>
                                <td class="text-end"><?= esc(number_format($cgstValue + $sgstValue, 2)) ?></td>
                                <td class="text-end"><?= esc(number_format((float)($item->twdisc_amount ?? $item->tamount ?? 0), 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="13" class="text-center text-muted">No item rows.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <?php
                        $invoiceCgstTotal = (float) ($invoice->CGST_Tamount ?? 0);
                        $invoiceSgstTotal = (float) ($invoice->SGST_Tamount ?? 0);
                        if ($invoiceCgstTotal <= 0 && $rowCgstTotal > 0) {
                            $invoiceCgstTotal = $rowCgstTotal;
                        }
                        if ($invoiceSgstTotal <= 0 && $rowSgstTotal > 0) {
                            $invoiceSgstTotal = $rowSgstTotal;
                        }
                    ?>
                    <tr>
                        <th>#</th><th colspan="6"></th>
                        <th>Gross Total</th>
                        <th class="text-end"><?= esc(number_format((float)($invoice->gross_amount ?? 0), 2)) ?></th>
                        <th class="text-end"><?= esc(number_format((float)($invoice->disc_amount ?? 0), 2)) ?></th>
                        <th></th>
                        <th class="text-end\"><?= esc(number_format($invoiceCgstTotal + $invoiceSgstTotal, 2)) ?></th>
                        <th class="text-end"><?= esc(number_format($net, 2)) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="table-responsive mb-3">
            <table class="table table-sm table-striped table-bordered align-middle mb-0">
                <tr>
                    <th style="width: 18px">#</th>
                    <th style="width: 140px">Deduction</th>
                    <th><input class="form-control form-control-sm" id="input_dis_desc" value="<?= esc($deductionRemark) ?>" placeholder="Ded. Desc." type="text"></th>
                    <th style="width: 140px"><input class="form-control form-control-sm" id="input_dis_amt" value="<?= esc(number_format($deduction, 2, '.', '')) ?>" placeholder="Amount" type="number" step="0.01"></th>
                    <th style="width: 110px"><button type="button" class="btn btn-primary btn-sm" id="btn_update_ded">Update</button></th>
                </tr>
            </table>
        </div>

        <div class="table-responsive mb-3">
            <table class="table table-sm table-striped table-bordered align-middle mb-0">
                <tr>
                    <th style="width: 18px">#</th>
                    <th>Amount received : <?= esc(number_format($paid, 2)) ?><br>
                        <?php if (!empty($paymentHistory)): ?>
                            <?php foreach ($paymentHistory as $pay): ?>
                                [<?= esc($pay->id ?? '-') ?>:<?= esc($pay->Payment_type_str ?? '-') ?>:<?= esc(number_format((float)($pay->paid_amount ?? 0), 2)) ?>:<?= esc($pay->payment_date ?? '-') ?>]<br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </th>
                    <th>Balance Amount : <?= esc(number_format($balance, 2)) ?></th>
                    <th>Net Amount : <?= esc(number_format($net, 2)) ?></th>
                </tr>
            </table>
        </div>

        <?php if (abs($balance) > 0.0001): ?>
            <div class="mb-3" style="max-width:520px;">
                <label class="form-label form-label-sm">Received Amount</label>
                <input class="form-control form-control-sm" id="input_amount_paid" type="number" step="0.01" value="<?= esc(number_format($balance, 2, '.', '')) ?>">
            </div>

            <?php if ($allowPaymentMode): ?>
                <div class="mb-2">
                    <label class="form-label form-label-sm">Payment Mode</label>
                    <?php if ($balance > 0): ?>
                        <div class="card mb-2">
                            <div class="card-header py-1">Cash</div>
                            <div class="card-body py-2">
                                <button type="button" class="btn btn-primary btn-sm" id="btn_update1">Confirm Cash Received and Print Receipt</button>
                            </div>
                        </div>

                        <div class="card mb-2">
                            <div class="card-header py-1">Credit / Debit Card</div>
                            <div class="card-body py-2">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label form-label-sm">Payment By</label>
                                        <select class="form-select form-select-sm" id="cbo_pay_type">
                                            <?php if (!empty($bankSources)): ?>
                                                <?php foreach ($bankSources as $source): ?>
                                                    <option value="<?= (int)($source->id ?? 0) ?>"><?= esc(($source->pay_type ?? 'Bank') . ' [' . ($source->bank_name ?? '-') . ']') ?></option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="0">Bank</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label form-label-sm">Tran. ID/Ref.</label>
                                        <input class="form-control form-control-sm" id="input_card_tran" type="text" autocomplete="off">
                                    </div>
                                    <div class="col-md-4">
                                        <button type="button" class="btn btn-primary btn-sm w-100" id="btn_update2">Confirm Payment</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card mb-2">
                            <div class="card-header py-1">Cash Return</div>
                            <div class="card-body py-2">
                                <button type="button" class="btn btn-primary btn-sm" id="btn_update_return">Confirm Cash Return and Print Receipt</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="d-flex gap-2 mt-3">
            <a href="<?= base_url('Medical/invoice_print/' . $invoiceId) ?>" target="_blank" class="btn btn-secondary btn-sm">PDF</a>
            <a href="<?= base_url('Medical/invoice_print/' . $invoiceId . '/1') ?>" target="_blank" class="btn btn-secondary btn-sm">PDF A6</a>
            <a href="<?= base_url('Medical/invoice_print/' . $invoiceId . '/2') ?>" target="_blank" class="btn btn-secondary btn-sm">PDF A5</a>
            <a href="<?= base_url('Medical/invoice_print/' . $invoiceId . '/3') ?>" target="_blank" class="btn btn-secondary btn-sm">PDF A5-2</a>
        </div>
    </div>
</div>

<div class="d-none" id="final-hidden-csrf"><?= csrf_field() ?></div>

<script>
    if (typeof window.showMedicalPermissionAlert !== 'function') {
        window.showMedicalPermissionAlert = function (trigger, message) {
            var text = String(message || 'No permission to edit this invoice.');
            var root = trigger && trigger.closest ? trigger.closest('.card') : null;
            var host = root && root.querySelector ? root.querySelector('.js-permission-alert') : null;

            if (!host) {
                alert(text);
                return false;
            }

            host.innerHTML = '';

            var box = document.createElement('div');
            box.className = 'alert alert-warning alert-dismissible fade show py-2 mb-3';
            box.setAttribute('role', 'alert');

            var textNode = document.createElement('span');
            textNode.textContent = text;
            box.appendChild(textNode);

            var closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close';
            closeBtn.setAttribute('aria-label', 'Close');
            closeBtn.addEventListener('click', function () {
                if (box.parentNode) {
                    box.parentNode.removeChild(box);
                }
            });
            box.appendChild(closeBtn);

            host.appendChild(box);
            if (typeof host.scrollIntoView === 'function') {
                host.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            return false;
        };
    }

    (function () {
        var invoiceId = <?= $invoiceId ?>;
        var finalUrl = '<?= base_url('Medical/final_invoice/' . $invoiceId) ?>';

        function refreshFinal() {
            if (typeof load_form_div === 'function') {
                load_form_div(finalUrl, 'medical-main');
                return;
            }
            window.location.href = finalUrl;
        }

        function showError(message) {
            var box = document.querySelector('#medical-final-invoice .jsError');
            if (!box) {
                return;
            }
            box.innerHTML = '<div class="alert alert-danger py-2">' + (message || 'Unable to process request') + '</div>';
        }

        function postAction(url, payload, done) {
            var csrfInput = document.querySelector('#final-hidden-csrf input[type="hidden"]');
            var formData = new FormData();

            Object.keys(payload || {}).forEach(function (key) {
                formData.append(key, payload[key]);
            });

            if (csrfInput && csrfInput.name) {
                formData.append(csrfInput.name, csrfInput.value || '');
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            }).then(function (response) {
                return response.json();
            }).then(function (data) {
                if (typeof done === 'function') {
                    done(data || {});
                }
            }).catch(function () {
                showError('Unable to process request. Please try again.');
            });
        }

        var btnDed = document.getElementById('btn_update_ded');
        if (btnDed) {
            btnDed.addEventListener('click', function () {
                var grossAmount = parseFloat('<?= (float)($invoice->gross_amount ?? 0) ?>');
                var discountAmount = parseFloat(document.getElementById('input_dis_amt').value || '0');
                var maxDiscount = grossAmount * 0.12;
                if (discountAmount > maxDiscount) {
                    showError('Discount Amount is greater than allowed limit. Max : ' + maxDiscount.toFixed(2));
                    return;
                }

                postAction('<?= base_url('Medical/update_discount') ?>', {
                    med_invoice_id: invoiceId,
                    input_dis_desc: document.getElementById('input_dis_desc').value || '',
                    input_dis_amt: discountAmount
                }, function (data) {
                    if (!parseInt((data && data.update) ? data.update : 0, 10)) {
                        showError((data && data.error_text) ? data.error_text : 'Unable to update deduction.');
                        return;
                    }
                    refreshFinal();
                });
            });
        }

        var btnCash = document.getElementById('btn_update1');
        if (btnCash) {
            btnCash.addEventListener('click', function () {
                if (!window.confirm('Are you sure process this invoice?')) {
                    return;
                }
                postAction('<?= base_url('Medical/confirm_payment') ?>', {
                    mode: 1,
                    med_invoice_id: invoiceId,
                    input_amount_paid: document.getElementById('input_amount_paid').value || '0'
                }, function (data) {
                    if (!parseInt((data && data.update) ? data.update : 0, 10)) {
                        showError((data && data.error_text) ? data.error_text : 'Unable to confirm payment.');
                        return;
                    }
                    window.open('<?= base_url('Medical/invoice_print/' . $invoiceId) ?>', '_blank');
                    refreshFinal();
                });
            });
        }

        var btnCard = document.getElementById('btn_update2');
        if (btnCard) {
            btnCard.addEventListener('click', function () {
                postAction('<?= base_url('Medical/confirm_payment') ?>', {
                    mode: 2,
                    med_invoice_id: invoiceId,
                    cbo_pay_type: document.getElementById('cbo_pay_type') ? document.getElementById('cbo_pay_type').value : '0',
                    input_card_tran: document.getElementById('input_card_tran') ? document.getElementById('input_card_tran').value : '',
                    input_amount_paid: document.getElementById('input_amount_paid').value || '0'
                }, function (data) {
                    if (!parseInt((data && data.update) ? data.update : 0, 10)) {
                        showError((data && data.error_text) ? data.error_text : 'Unable to confirm card payment.');
                        return;
                    }
                    refreshFinal();
                });
            });
        }

        var btnReturn = document.getElementById('btn_update_return');
        if (btnReturn) {
            btnReturn.addEventListener('click', function () {
                if (!window.confirm('Are you sure process this invoice?')) {
                    return;
                }
                postAction('<?= base_url('Medical/confirm_payment') ?>', {
                    mode: 5,
                    med_invoice_id: invoiceId,
                    input_amount_paid: document.getElementById('input_amount_paid').value || '0'
                }, function (data) {
                    if (!parseInt((data && data.update) ? data.update : 0, 10)) {
                        showError((data && data.error_text) ? data.error_text : 'Unable to confirm cash return.');
                        return;
                    }
                    window.open('<?= base_url('Medical/invoice_print/' . $invoiceId) ?>', '_blank');
                    refreshFinal();
                });
            });
        }
    })();
</script>

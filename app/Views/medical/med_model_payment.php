<?php
    if (!function_exists('get_age_1')) {
        helper('age');
    }

    $ipdId = (int) ($ipd->id ?? 0);
    $patientName = (string) ($patient->p_fname ?? '-');
    $patientRelative = (string) ($patient->p_rname ?? '');
    $patientCode = (string) ($patient->p_code ?? '-');
    $genderValue = (string) ($patient->xgender ?? ($patient->gender ?? ''));
    if ($genderValue === '1') {
        $genderValue = 'Male';
    } elseif ($genderValue === '0' || $genderValue === '2') {
        $genderValue = 'Female';
    }
    $patientAge = '';
    if (!empty($patient)) {
        $patientAge = get_age_1(
            $patient->dob ?? null,
            $patient->age ?? '',
            $patient->age_in_month ?? '',
            $patient->estimate_dob ?? '',
            $ipd->register_date ?? null
        );
    }
    $ipdCode = (string) ($ipd->ipd_code ?? ('IPD-' . $ipdId));
    $groupId = (int) ($group->med_group_id ?? 0);
    $netAmount = (float) ($paymentSummary['net_amount'] ?? ($group->net_amount ?? 0));
    $paidAmount = (float) ($paymentSummary['paid_amount'] ?? ($group->payment_received ?? 0));
    $balanceAmount = (float) ($paymentSummary['balance_amount'] ?? ($group->payment_balance ?? ($netAmount - $paidAmount)));
?>

<div class="card border-0">
    <div class="card-header bg-light border-bottom border-danger border-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">IPD Payment : <small class="text-muted"><strong>IPD Code :</strong> <?= esc($ipdCode) ?></small></h5>
        <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('Medical/list_med_inv/' . $ipdId) ?>','medical-main');">Back to Invoice List</a>
    </div>

    <div class="card-body">
        <div class="mb-3">
            <strong>Name :</strong> <?= esc($patientName) ?><?= $patientRelative !== '' ? ' {' . esc($patientRelative) . '}' : '' ?> /
            <strong>Age :</strong> <?= esc($patientAge !== '' ? $patientAge : '-') ?> /
            <strong>Gender :</strong> <?= esc($genderValue !== '' ? $genderValue : '-') ?> /
            <strong>Patient Code / UHID :</strong> <?= esc($patientCode) ?> /
            <strong>IPD Code :</strong> <?= esc($ipdCode) ?> 
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card border-secondary">
                    <div class="card-header py-2"><strong>Add Payment</strong></div>
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label form-label-sm">Date of Payment</label>
                                <input type="date" id="date_payment" class="form-control form-control-sm" value="<?= esc(date('Y-m-d')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label form-label-sm">Type</label>
                                <select id="cbo_cr_dr" class="form-select form-select-sm">
                                    <option value="0" selected>Credit Amount</option>
                                    <option value="1">Debit / Return Amount</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label form-label-sm">Amount</label>
                                <input type="number" min="0" step="0.01" id="input_Amount" class="form-control form-control-sm" placeholder="0.00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label form-label-sm">Remark</label>
                                <input type="text" id="input_cash_remark" class="form-control form-control-sm" placeholder="Any remark">
                            </div>
                        </div>

                        <div class="border rounded p-2 mt-3">
                            <div class="fw-semibold mb-2">Cash</div>
                            <button type="button" class="btn btn-primary btn-sm" id="btn_update1">Confirm Cash Payment</button>
                        </div>

                        <div class="border rounded p-2 mt-3">
                            <div class="fw-semibold mb-2">Credit / Debit Card</div>
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label form-label-sm">Payment By</label>
                                    <select class="form-select form-select-sm" id="cbo_pay_type">
                                        <?php if (!empty($bankSources)): ?>
                                            <?php foreach ($bankSources as $row): ?>
                                                <option value="<?= (int) ($row->id ?? 0) ?>"><?= esc(($row->pay_type ?? 'Source') . (!empty($row->bank_name) ? ' [' . $row->bank_name . ']' : '')) ?></option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <option value="0">Default</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label form-label-sm">Tran. ID / Ref.</label>
                                    <input type="text" id="input_card_tran" class="form-control form-control-sm" placeholder="Card Tran. ID">
                                </div>
                                <div class="col-md-3">
                                    <button type="button" class="btn btn-primary btn-sm w-100" id="btn_update2">Confirm Card Payment</button>
                                </div>
                            </div>
                        </div>

                        <div class="small text-muted mt-2">Use Debit/Return when refunding amount.</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-secondary mb-2">
                    <div class="card-header py-2"><strong>Summary</strong></div>
                    <div class="card-body py-2 small">
                        <div><strong>Pharmacy Bill Amount:</strong> Rs. <?= esc(number_format($netAmount, 2)) ?></div>
                        <div><strong>Total Amount Paid:</strong> Rs. <?= esc(number_format($paidAmount, 2)) ?></div>
                        <div><strong>Balance Amount:</strong> Rs. <?= esc(number_format($balanceAmount, 2)) ?></div>
                    </div>
                </div>

                <div class="card border-secondary">
                    <div class="card-header py-2"><strong>Payment Details</strong></div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 380px; overflow:auto;">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Pay.No.</th>
                                        <th>Mode</th>
                                        <th>Date</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($paymentHistory)): ?>
                                        <?php foreach ($paymentHistory as $row): ?>
                                            <tr>
                                                <td><?= esc((string) ($row->id ?? '')) ?></td>
                                                <td><?= esc($row->Payment_type_str ?? '-') ?></td>
                                                <td><?= esc(!empty($row->payment_date) ? date('d-m-Y', strtotime((string) $row->payment_date)) : '-') ?></td>
                                                <td class="text-end"><?= esc(number_format((float) ($row->paid_amount ?? 0), 2)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center text-muted">No payment entries.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var tokenName = '<?= esc(csrf_token()) ?>';
    var tokenValue = '<?= esc(csrf_hash()) ?>';
    var ipdId = <?= (int) $ipdId ?>;
    var medGroupId = <?= (int) $groupId ?>;

    function submitPayment(mode) {
        var amount = parseFloat(document.getElementById('input_Amount').value || '0');
        if (!(amount > 0)) {
            alert('Amount should be greater than 0');
            return;
        }

        var payload = {
            mode: mode,
            amount: amount,
            Med_Group_id: medGroupId,
            ipd_id: ipdId,
            cr_dr: parseInt(document.getElementById('cbo_cr_dr').value || '0', 10),
            date_payment: document.getElementById('date_payment').value || '',
            cash_remark: document.getElementById('input_cash_remark').value || '',
            cbo_pay_type: parseInt(document.getElementById('cbo_pay_type').value || '0', 10),
            input_card_tran: document.getElementById('input_card_tran').value || ''
        };

        payload[tokenName] = tokenValue;

        fetch('<?= base_url('Medical/group_confirm_payment') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: new URLSearchParams(payload).toString()
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || parseInt(data.update || 0, 10) !== 1) {
                    alert((data && data.error_text) ? data.error_text : 'Something went wrong');
                    return;
                }
                alert('Payment updated');
                if (typeof load_form_div === 'function') {
                    load_form_div('<?= base_url('Medical/med_cash_payment/') ?>' + ipdId, 'medical-main');
                } else {
                    window.location.href = '<?= base_url('Medical/med_cash_payment/') ?>' + ipdId;
                }
            })
            .catch(function () {
                alert('Network error while updating payment');
            });
    }

    var btnCash = document.getElementById('btn_update1');
    var btnCard = document.getElementById('btn_update2');

    if (btnCash) {
        btnCash.addEventListener('click', function () { submitPayment(1); });
    }
    if (btnCard) {
        btnCard.addEventListener('click', function () { submitPayment(2); });
    }
})();
</script>

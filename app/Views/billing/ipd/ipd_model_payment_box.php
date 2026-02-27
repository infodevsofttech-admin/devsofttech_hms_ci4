<?php
$ipd = $ipd_info ?? null;
$person = $person_info ?? null;
$age = '';
if ($person) {
    $age = get_age_1($person->dob ?? null, $person->age ?? '', $person->age_in_month ?? '', $person->estimate_dob ?? '');
}
?>
<div class="card">
    <div class="card-body">
        <p class="mb-2">
            <strong>Name :</strong> <?= esc($person->p_fname ?? '') ?> {<i><?= esc($person->p_rname ?? '') ?></i>}
            <strong>/ Age :</strong> <?= esc($age) ?>
            <strong>/ Gender :</strong> <?= esc($person->xgender ?? '') ?>
            <strong>/ P Code :</strong> <?= esc($person->p_code ?? '') ?>
            <strong>/ IPD Code :</strong> <?= esc($ipd->ipd_code ?? '') ?>
            <strong>/ No of Days :</strong> <?= esc($ipd->no_days ?? '') ?>
        </p>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <strong>Amount</strong>
    </div>
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Date of Payment</label>
                <input class="form-control" name="datepicker_payment" id="datepicker_payment" type="date" value="<?= esc(date('Y-m-d')) ?>" />
            </div>
            <div class="col-md-3">
                <label class="form-label">Amount</label>
                <input class="form-control" id="input_Amount" placeholder="0.00" type="number" step="0.01">
            </div>
        </div>
    </div>
</div>

<div class="accordion mt-3" id="accordionPayment">
    <div class="accordion-item">
        <h2 class="accordion-header" id="payCashHeading">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#payCashBody" aria-expanded="true">
                Cash
            </button>
        </h2>
        <div id="payCashBody" class="accordion-collapse collapse show" aria-labelledby="payCashHeading" data-bs-parent="#accordionPayment">
            <div class="accordion-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Remark</label>
                        <input class="form-control" id="input_cash_remark" placeholder="Any remark" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-primary" id="btn_update1">Confirm Cash Received and Print Receipt</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header" id="payCardHeading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#payCardBody" aria-expanded="false">
                Credit / Debit Card
            </button>
        </h2>
        <div id="payCardBody" class="accordion-collapse collapse" aria-labelledby="payCardHeading" data-bs-parent="#accordionPayment">
            <div class="accordion-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Payment By</label>
                        <select class="form-select" name="cbo_pay_type" id="cbo_pay_type">
                            <?php foreach (($bank_data ?? []) as $row) : ?>
                                <option value="<?= esc($row->id ?? '') ?>"><?= esc(($row->pay_type ?? '') . ' [' . ($row->bank_name ?? '') . ']') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tran. ID/Ref.</label>
                        <input class="form-control" id="input_card_tran" placeholder="Card Tran.ID." type="text" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary w-100" id="btn_update2">Confirm Payment</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="Ipd_ID" value="<?= (int) ($ipd->id ?? 0) ?>" />
<script>
    (function() {
        var csrfName = '<?= esc(csrf_token()) ?>';
        var csrfHash = '<?= esc(csrf_hash()) ?>';

        function enableButtons() {
            $('#btn_update1').attr('disabled', false);
            $('#btn_update2').attr('disabled', false);
        }

        $('#btn_update1').on('click', function() {
            var amount = parseFloat($('#input_Amount').val() || '0');
            if (amount <= 0) {
                alert('Amount should be greater than zero.');
                return;
            }

            var payload = {};
            payload.mode = 1;
            payload.amount = amount;
            payload.ipd_id = $('#Ipd_ID').val();
            payload.cash_remark = $('#input_cash_remark').val();
            payload.date_payment = $('#datepicker_payment').val();
            payload[csrfName] = csrfHash;

            $('#btn_update1').attr('disabled', true);
            $('#btn_update2').attr('disabled', true);

            $.post('<?= site_url('billing/ipd/payment/confirm') ?>', payload, function(data) {
                if (data.update === 0) {
                    $('#payModal-bodyc').html(data.error_text || 'Payment failed.');
                    setTimeout(enableButtons, 1000);
                    return;
                }

                if (data.ipd_id && data.payid) {
                    load_report_div('<?= site_url('billing/ipd/payment/receipt') ?>/' + data.ipd_id + '/' + data.payid, 'payModal-bodyc');
                }
            }, 'json');
        });

        $('#btn_update2').on('click', function() {
            var amount = parseFloat($('#input_Amount').val() || '0');
            if (amount <= 0) {
                alert('Amount should be greater than zero.');
                return;
            }

            var payload = {};
            payload.mode = 2;
            payload.amount = amount;
            payload.ipd_id = $('#Ipd_ID').val();
            payload.cbo_pay_type = $('#cbo_pay_type').val();
            payload.input_card_tran = $('#input_card_tran').val();
            payload.date_payment = $('#datepicker_payment').val();
            payload[csrfName] = csrfHash;

            $('#btn_update1').attr('disabled', true);
            $('#btn_update2').attr('disabled', true);

            $.post('<?= site_url('billing/ipd/payment/confirm') ?>', payload, function(data) {
                if (data.update === 0) {
                    $('#payModal-bodyc').html(data.error_text || 'Payment failed.');
                    setTimeout(enableButtons, 1000);
                    return;
                }

                if (data.ipd_id && data.payid) {
                    load_report_div('<?= site_url('billing/ipd/payment/receipt') ?>/' + data.ipd_id + '/' + data.payid, 'payModal-bodyc');
                }
            }, 'json');
        });
    })();
</script>

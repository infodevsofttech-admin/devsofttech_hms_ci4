<div class="card">
    <div class="card-header">
        <strong>Payment Deduction</strong>
    </div>
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Amount</label>
                <input class="form-control" id="input_Amount" placeholder="0.00" type="number" step="0.01">
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="Ipd_ID" value="<?= esc($ipd_id ?? '') ?>" />

<div class="accordion mt-3" id="accordionDeduction">
    <div class="accordion-item">
        <h2 class="accordion-header" id="dedCashHeading">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#dedCashBody" aria-expanded="true">
                Cash
            </button>
        </h2>
        <div id="dedCashBody" class="accordion-collapse collapse show" aria-labelledby="dedCashHeading" data-bs-parent="#accordionDeduction">
            <div class="accordion-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label">Remark</label>
                        <input class="form-control" id="input_cash_remark" placeholder="Any remark" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-primary" id="btn_update_return">Confirm Cash Received and Print Receipt</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="accordion-item">
        <h2 class="accordion-header" id="dedBankHeading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dedBankBody" aria-expanded="false">
                Bank Deposit or NEFT or Cheque No.
            </button>
        </h2>
        <div id="dedBankBody" class="accordion-collapse collapse" aria-labelledby="dedBankHeading" data-bs-parent="#accordionDeduction">
            <div class="accordion-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Name of Person</label>
                        <input class="form-control" id="input_person_name" placeholder="Person Name" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">To Bank: Customer Bank Name</label>
                        <input class="form-control" id="input_bank_name" placeholder="Customer Bank Name" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">From Bank: Hospital Bank Name</label>
                        <input class="form-control" id="input_bank_hospital" placeholder="Hospital Bank Name" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tran. ID / UTR / Cheque</label>
                        <input class="form-control" id="input_bank_tran" placeholder="Tran. ID or UTR No." type="text" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date of Tran.</label>
                        <input class="form-control" id="datepicker_dot" type="date" value="<?= esc(date('Y-m-d')) ?>" />
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary" id="btn_update_return_bank">Confirm Payment</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        var csrfName = '<?= esc(csrf_token()) ?>';
        var csrfHash = '<?= esc(csrf_hash()) ?>';

        $('#btn_update_return').on('click', function() {
            var amount = parseFloat($('#input_Amount').val() || '0');
            if (amount <= 0) {
                alert('Amount should be greater than zero.');
                return;
            }

            var payload = {};
            payload.mode = 3;
            payload.amount = amount;
            payload.ipd_id = $('#Ipd_ID').val();
            payload.cash_remark = $('#input_cash_remark').val();
            payload[csrfName] = csrfHash;

            $.post('<?= site_url('billing/ipd/payment/deduct') ?>', payload, function(data) {
                if (data.update === 0) {
                    $('#payModal_ded-bodyc').html(data.error_text || 'Refund failed.');
                    return;
                }

                if (data.ipd_id && data.payid) {
                    load_report_div('<?= site_url('billing/ipd/payment/receipt') ?>/' + data.ipd_id + '/' + data.payid, 'payModal_ded-bodyc');
                }
            }, 'json');
        });

        $('#btn_update_return_bank').on('click', function() {
            var amount = parseFloat($('#input_Amount').val() || '0');
            if (amount <= 0) {
                alert('Amount should be greater than zero.');
                return;
            }

            var payload = {};
            payload.mode = 4;
            payload.amount = amount;
            payload.ipd_id = $('#Ipd_ID').val();
            payload.cash_remark = $('#input_cash_remark').val();
            payload[csrfName] = csrfHash;

            $.post('<?= site_url('billing/ipd/payment/deduct') ?>', payload, function(data) {
                if (data.update === 0) {
                    $('#payModal_ded-bodyc').html(data.error_text || 'Refund failed.');
                    return;
                }

                if (data.ipd_id && data.payid) {
                    load_report_div('<?= site_url('billing/ipd/payment/receipt') ?>/' + data.ipd_id + '/' + data.payid, 'payModal_ded-bodyc');
                }
            }, 'json');
        });
    })();
</script>

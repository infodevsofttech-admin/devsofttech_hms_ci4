<?= form_open() ?>
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <div><strong>Name:</strong> <?= esc($person_info[0]->p_fname ?? '') ?> <span class="text-muted">{<?= esc($person_info[0]->p_rname ?? '') ?>}</span></div>
            <div><strong>Age:</strong> <?= esc($person_info[0]->age ?? '') ?></div>
            <div><strong>Gender:</strong> <?= esc($person_info[0]->xgender ?? '') ?></div>
            <div><strong>P Code:</strong> <?= esc($person_info[0]->p_code ?? '') ?></div>
            <div><strong>ORG. Code:</strong> <?= esc($org_info[0]->case_id_code ?? '') ?></div>
        </div>
    </div>
</div>

<div id="sub_panel" class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="fw-semibold">Payment Amount</span>
        <input type="hidden" id="req_payment_id" name="req_payment_id" value="<?= esc($req_payment_order[0]->id ?? 0) ?>">
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-md-3">
                <label for="input_Amount" class="form-label">Amount</label>
                <input class="form-control number" id="input_Amount" value="<?= esc($req_payment_order[0]->payment_amount ?? '') ?>" type="text" readonly>
            </div>
        </div>

        <div class="accordion" id="paymentAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingCash">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCash" aria-expanded="true" aria-controls="collapseCash">
                        Cash
                    </button>
                </h2>
                <div id="collapseCash" class="accordion-collapse collapse show" aria-labelledby="headingCash" data-bs-parent="#paymentAccordion">
                    <div class="accordion-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label">Remark</label>
                                <input class="form-control" id="input_cash_remark" placeholder="Any remark" type="text" autocomplete="off">
                            </div>
                            <div class="col-md-6 text-md-end">
                                <button type="button" class="btn btn-primary" id="btn_update1">Confirm Cash Received and Print Receipt</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingCard">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCard" aria-expanded="false" aria-controls="collapseCard">
                        Credit / Debit Card / UPI / Online
                    </button>
                </h2>
                <div id="collapseCard" class="accordion-collapse collapse" aria-labelledby="headingCard" data-bs-parent="#paymentAccordion">
                    <div class="accordion-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Payment By</label>
                                <select class="form-select" name="cbo_pay_type" id="cbo_pay_type">
                                    <?php foreach (($bank_data ?? []) as $row) : ?>
                                        <option value="<?= esc($row->id ?? '') ?>"><?= esc($row->pay_type ?? '') ?> [<?= esc($row->bank_name ?? '') ?>]</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tran. ID/Ref.</label>
                                <input class="form-control" id="input_card_tran" placeholder="Card Tran.ID." type="text" autocomplete="off">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Payment Confirm By Bank/Online</label>
                                <button type="button" class="btn btn-primary" id="btn_update2">Confirm Payment</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>
<script>
$(document).ready(function(){
    function enable_btn(){
        $('#btn_update1').attr('disabled', false);
        $('#btn_update2').attr('disabled', false);
    }

    $('#btn_update1').click(function(){
        var amount = $('#input_Amount').val();
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

        $('#btn_update1').attr('disabled', true);
        $('#btn_update2').attr('disabled', true);

        if(amount > 0){
            $.post('<?= base_url('Invoice/req_payment_process') ?>', {
                "mode":"1",
                "amount": amount,
                "req_payment_id": $('#req_payment_id').val(),
                "cash_remark": $('#input_cash_remark').val(),
                "<?= csrf_token() ?>": csrf_value
            }, function(data){
                if(data.update==0){
                    $('#sub_panel').html(data.showcontent);
                    setTimeout(enable_btn,5000);
                }else{
                    $('#sub_panel').html(data.showcontent);
                }
            }, 'json');
        }else{
            setTimeout(enable_btn,20000);
            alert('Amount Should be greater then Zero (0)');
        }
    });

    $('#btn_update2').click(function(){
        var amount = $('#input_Amount').val();
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

        $('#btn_update1').attr('disabled', true);
        $('#btn_update2').attr('disabled', true);

        if(amount > 0){
            $.post('<?= base_url('Invoice/req_payment_process') ?>', {
                "mode":"2",
                "req_payment_id": $('#req_payment_id').val(),
                "amount": amount,
                "cbo_pay_type": $('#cbo_pay_type').val(),
                "input_card_tran": $('#input_card_tran').val(),
                "<?= csrf_token() ?>": csrf_value
            }, function(data){
                if(data.update==0){
                    $('#sub_panel').html(data.showcontent);
                    setTimeout(enable_btn,5000);
                }else{
                    $('#sub_panel').html(data.showcontent);
                }
            }, 'json');
        }else{
            setTimeout(enable_btn,5000);
            alert('Amount Should be greater then Zero (0)');
        }
    });
});
</script>

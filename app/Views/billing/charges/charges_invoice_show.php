<?php
  $user = auth()->user();
  $canChargeView = is_object($user) && method_exists($user, 'can') ? $user->can('billing.charges.view') : false;
  $canChargeEdit = is_object($user) && method_exists($user, 'can') ? $user->can('billing.charges.edit') : false;
  $canChargePay = is_object($user) && method_exists($user, 'can') ? $user->can('billing.charges.pay') : false;
  $canChargeCancel = is_object($user) && method_exists($user, 'can') ? $user->can('billing.charges.cancel') : false;
  $canChargeCorrect = is_object($user) && method_exists($user, 'can') ? $user->can('billing.charges.correct') : false;
?>
<form role="form" class="form1">
<?= csrf_field() ?>
<section class="content-header">
  <div class="row align-items-center">
    <div class="col-md-6">
      <h1>
        Invoice
        <small>#<?= esc($invoice_master[0]->invoice_code ?? '') ?></small>
      </h1>
    </div>
    <div class="col-md-6 text-end">
      <div class="d-inline-flex gap-2">
        <?php if (($invoice_master[0]->ipd_id ?? 0) > 0) : ?>
          <a class="btn btn-outline-secondary btn-sm" href="javascript:load_form('<?= base_url('IpdNew/ipd_panel') ?>/<?= esc($invoice_master[0]->ipd_id) ?>');">IPD Panel</a>
        <?php endif; ?>
        <?php if (($invoice_master[0]->payment_status ?? 0) == 0 && $canChargeEdit) : ?>
          <a class="btn btn-outline-warning btn-sm" href="javascript:load_form('<?= base_url('billing/charges/edit') ?>/<?= esc($invoice_master[0]->id ?? 0) ?>');">Edit Invoice</a>
        <?php endif; ?>
        <a class="btn btn-outline-primary btn-sm" href="javascript:load_form('<?= base_url('billing/patient/person_record') ?>/<?= esc($invoice_master[0]->attach_id ?? 0) ?>');">Person</a>
      </div>
    </div>
  </div>
</section>
<section class="invoice">
  <div class="card">
    <div class="card-body p-3">
  <div class="row invoice-info mb-3">
  <div class="col-sm-6 invoice-col">
    To
    <address>
    <strong><?= esc($patient_master[0]->p_fname ?? '') ?></strong><br>
    <?= esc($patient_master[0]->p_relative ?? '') ?>  : <?= esc($patient_master[0]->p_rname ?? '') ?><br>
    Gender : <?= esc($patient_master[0]->xgender ?? '') ?><br>
    Age : <?= esc($patient_master[0]->age ?? '') ?><br>
    Phone No : <?= esc($patient_master[0]->mphone1 ?? '') ?>
    </address>
  </div>
  <div class="col-sm-6 invoice-col">
    <b>Invoice #<?= esc($invoice_master[0]->invoice_code ?? '') ?></b><br>
    <?php if (($invoice_master[0]->insurance_id ?? 0) > 1) : ?>
      <strong> Ins. Comp. :</strong><?= esc($insurance[0]->ins_company_name ?? '') ?><br>
      <?php if (($invoice_master[0]->insurance_case_id ?? 0) > 0 && count($case_master) > 0) : ?>
        <strong> Org.Case No. :</strong><?= esc($case_master[0]->case_id_code ?? '') ?><br>
      <?php endif; ?>
    <?php endif; ?>
    <b>Date :</b> <?= esc($invoice_master[0]->inv_date ?? '') ?><br>
    <b>Patient ID :</b> <?= esc($patient_master[0]->p_code ?? '') ?><br>
    <b>Refer By :</b> <?= esc($invoice_master[0]->refer_by_other ?? '') ?><br>
    <input type="hidden" value="<?= esc($invoice_master[0]->id ?? 0) ?>" id="invoice_id" name="invoice_id" />
  </div>
  </div>

  <div class="row">
  <div class="col-md-12">
    <table class="table table-striped table-responsive mb-0">
    <tr>
      <th style="width: 10px">#</th>
      <th>Charges Group</th>
      <th>Charge Name</th>
      <th>Rate</th>
      <th>Qty</th>
      <th>Amount</th>
      <th></th>
    </tr>
    <?php $srno = 1; foreach ($invoiceDetails as $row) : ?>
      <tr>
        <td><?= $srno ?></td>
        <td><?= esc($row->group_desc ?? '') ?></td>
        <td><?= esc($row->item_name ?? '') ?></td>
        <td><?= esc($row->item_rate ?? '') ?></td>
        <td><?= esc($row->item_qty ?? '') ?></td>
        <td><?= esc($row->item_amount ?? '') ?></td>
        <td></td>
      </tr>
    <?php $srno++; endforeach; ?>
    <tr>
      <th style="width: 10px">#</th>
      <th></th>
      <th></th>
      <th></th>
      <th>Gross Total</th>
      <th><?= esc($invoice_master[0]->total_amount ?? '') ?></th>
      <th></th>
    </tr>
    <?php if (($invoice_master[0]->payment_status ?? 0) == 0) : ?>
      <tr>
        <th style="width: 10px">#</th>
        <th>Deduction</th>
        <th colspan=3><input class="form-control" name="input_dis_desc" id="input_dis_desc" placeholder="Ded. Desc." value="<?= esc($invoice_master[0]->discount_desc ?? '') ?>" type="text" /></th>
        <th><input style="width: 100px" class="form-control" name="input_dis_amt" id="input_dis_amt" placeholder="Amount" value="<?= esc($invoice_master[0]->discount_amount ?? '') ?>" type="text" /></th>
        <th><button type="button" class="btn btn-primary" id="btn_update_ded">Update</button></th>
      </tr>
    <?php else : ?>
      <tr>
        <th style="width: 10px">#</th>
        <th>Deduction</th>
        <th colspan=3><?= esc($invoice_master[0]->discount_desc ?? '') ?></th>
        <th><?= esc($invoice_master[0]->discount_amount ?? '') ?></th>
        <th></th>
      </tr>
    <?php endif; ?>
    <tr>
      <th style="width: 10px">#</th>
      <th colspan="2"></th>
      <th></th>
      <th>Net Amount</th>
      <th><?= esc($invoice_master[0]->net_amount ?? '') ?></th>
      <th></th>
    </tr>
    </table>
  </div>
  </div>
    </div>
  </div>
</section>
</form>

<?php if (($invoice_master[0]->payment_status ?? 0) == 0 && $canChargePay) : ?>
  <div class="card">
    <div class="card-body p-3">
  <div class="row mt-2">
    <div class="col-md-12">
      <table class="table table-striped table-responsive mb-0">
        <tr>
          <th style="width: 10px">#</th>
          <th>Amount received : <?= esc(number_format((float) ($paid_amount ?? 0), 2)) ?></th>
          <th>Balance Amount : <?= esc(number_format((float) ($pending_amount ?? 0), 2)) ?></th>
          <th></th>
          <th></th>
          <th></th>
          <th></th>
        </tr>
      </table>
    </div>
  </div>
  <div class="row mt-2 mb-3">
    <div class="col-md-6">
      <label>Received Amount</label>
      <input class="form-control" type="number" step="0.01" id="input_received_amount" value="<?= esc(number_format((float) ($pending_amount ?? 0), 2, '.', '')) ?>" />
    </div>
  </div>
  <div class="payment_type">
    <div class="jsError text-danger"></div>
    <div id="payment_type" class="row">
      <div class="col-md-12">
        <div class="form-group">
          <label>Payment Mode</label>
          <div class="accordion" id="accordionPayment">
            <?php if (($pending_amount ?? 0) > 0) : ?>
              <div class="accordion-item">
                <h2 class="accordion-header" id="headingCash">
                  <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCash" aria-expanded="true" aria-controls="collapseCash">
                    Cash
                  </button>
                </h2>
                <div id="collapseCash" class="accordion-collapse collapse show" aria-labelledby="headingCash" data-bs-parent="#accordionPayment">
                  <div class="accordion-body">
                    <button type="button" class="btn btn-primary" id="btn_update1">Confirm Cash Received and Print Receipt</button>
                  </div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header" id="headingCard">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCard" aria-expanded="false" aria-controls="collapseCard">
                    Credit / Debit Card / UPI / Online
                  </button>
                </h2>
                <div id="collapseCard" class="accordion-collapse collapse" aria-labelledby="headingCard" data-bs-parent="#accordionPayment">
                  <div class="accordion-body">
                    <div class="row">
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Payment By</label>
                          <select class="form-select" name="cbo_pay_type" id="cbo_pay_type">
                            <?php foreach ($bank_data as $row) : ?>
                              <option value="<?= esc($row->id ?? '') ?>"><?= esc($row->pay_type ?? '') ?> [<?= esc($row->bank_name ?? '') ?>]</option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Tran. ID/Ref.</label>
                          <input class="form-control" id="input_card_tran" placeholder="Card Tran.ID." type="text" autocomplete="off">
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="form-group">
                          <label>Payment Confirm By Bank/Online</label>
                          <button type="button" class="btn btn-primary" id="btn_update2">Confirm Payment</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php else : ?>
              <div class="accordion-item">
                <h2 class="accordion-header" id="headingZero">
                  <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseZero" aria-expanded="true" aria-controls="collapseZero">
                    Zero Amount
                  </button>
                </h2>
                <div id="collapseZero" class="accordion-collapse collapse show" aria-labelledby="headingZero" data-bs-parent="#accordionPayment">
                  <div class="accordion-body">
                    <button id="btn_update0" type="button" class="btn btn-primary">Confirm With Zero Amount</button>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <?php if (! empty($show_ipd_credit)) : ?>
              <div class="accordion-item">
                <h2 class="accordion-header" id="headingIpd">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseIpd" aria-expanded="false" aria-controls="collapseIpd">
                    Credit to IPD<?= ! empty($ipd_master) ? ' [' . esc($ipd_master[0]->ipd_code ?? '') . ']' : '' ?>
                  </button>
                </h2>
                <div id="collapseIpd" class="accordion-collapse collapse" aria-labelledby="headingIpd" data-bs-parent="#accordionPayment">
                  <div class="accordion-body">
                    <input type="hidden" id="hidden_ipd_id" value="<?= esc($ipd_master[0]->id ?? 0) ?>">
                    <button type="button" class="btn btn-primary" id="btn_update3">Click here to Confirm</button>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <?php if (($invoice_master[0]->insurance_id ?? 0) > 0) : ?>
              <div class="accordion-item">
                <h2 class="accordion-header" id="headingOrg">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOrg" aria-expanded="false" aria-controls="collapseOrg">
                    Credit to Organization
                  </button>
                </h2>
                <div id="collapseOrg" class="accordion-collapse collapse" aria-labelledby="headingOrg" data-bs-parent="#accordionPayment">
                  <div class="accordion-body">
                    <?php foreach ($case_master as $row) : ?>
                      <div class="row">
                        <div class="col-md-3">
                          <div class="form-group">
                            <label>Case ID</label>
                            <input class="form-control" id="input_case_id" placeholder="Claim ID" type="text"
                              value="<?= esc($row->case_id_code ?? '') ?>" autocomplete="off" readonly>
                            <input type="hidden" id="hidden_case_id" value="<?= esc($row->id ?? '') ?>">
                          </div>
                        </div>
                        <div class="col-md-3">
                          <div class="form-group">
                            <label>Credit Confirm</label>
                            <button type="button" class="btn btn-primary" id="btn_update4">Click here to Confirm</button>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
    </div>
  </div>
<?php endif; ?>

<div class="clearfix"></div>
<input type="hidden" id="spid" value="<?= date('dmyHis') . rand(10000, 99999) ?>" />

<?php if (($invoice_master[0]->payment_status ?? 0) == 1 && $canChargeView) : ?>
  <div class="card mt-3">
    <div class="card-body p-3">
      <div class="row g-2">
        <?php if ($canChargeEdit) : ?>
          <div class="col-md-4">
            <button type="button" class="btn btn-primary" onclick="load_form('<?= base_url('billing/charges/edit') ?>/<?= esc($invoice_master[0]->id ?? 0) ?>')">Edit Invoice Items</button>
          </div>
        <?php endif; ?>
        <?php if ($canChargeCancel) : ?>
          <div class="col-md-4">
            <button type="button" class="btn btn-success" id="btn_cancel_inv">Cancel Invoice</button>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($canChargeCorrect && ((int) ($invoice_master[0]->payment_mode ?? 0) === 1 || (int) ($invoice_master[0]->payment_mode ?? 0) === 2)) : ?>
        <hr />
        <div class="row">
          <table class="table">
            <tr>
              <th style="width: 10px">#</th>
              <th>Deduction</th>
              <th colspan=3>
                <input class="form-control" name="input_corr_desc" id="input_corr_desc" placeholder="Correction Desc." value="<?= esc($invoice_master[0]->correction_remark ?? '') ?>" type="text" />
              </th>
              <th>
                <input style="width: 100px" class="form-control" name="input_corr_amt" id="input_corr_amt" placeholder="Amount" value="<?= esc($invoice_master[0]->correction_amount ?? '') ?>" type="text" />
              </th>
              <th>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="optionsRadios_crdr" id="corr_return" value="1" checked>
                  <label class="form-check-label" for="corr_return">Return</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="optionsRadios_crdr" id="corr_add" value="0">
                  <label class="form-check-label" for="corr_add">Add</label>
                </div>
              </th>
              <th><button type="button" class="btn btn-primary" id="btn_update_corr">Update Correction</button></th>
            </tr>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
<script>
$(document).ready(function(){
  $('#btn_update_ded').click(function(){
    var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
    $.post('<?= base_url('billing/charges/update-discount') ?>', {
      "inv_id": $('#invoice_id').val(),
      "discount_desc": $('#input_dis_desc').val(),
      "discount_amount": $('#input_dis_amt').val(),
      "<?= csrf_token() ?>": csrf_value
    }, function(data){
      if (data && data.update) {
        load_form('<?= base_url('billing/charges/show') ?>/' + $('#invoice_id').val());
      }
    });
  });
});

$(document).ready(function(){
  $('#btn_cancel_inv').click(function(){
    var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
    if (confirm('Are you sure cancel this invoice')) {
      $.post('<?= base_url('billing/charges/cancel-invoice') ?>/' + $('#invoice_id').val(), {
        "<?= csrf_token() ?>": csrf_value
      }, function(data){
        if (data && data.update) {
          load_form('<?= base_url('billing/charges/show') ?>/' + $('#invoice_id').val());
        }
      }, 'json');
    }
  });

  $('#btn_update_corr').click(function(){
    var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
    if (confirm('Are you sure process this correction')) {
      $.post('<?= base_url('billing/charges/update-correction') ?>', {
        "invoice_id": $('#invoice_id').val(),
        "input_corr_desc": $('#input_corr_desc').val(),
        "input_corr_amt": $('#input_corr_amt').val(),
        "optionsRadios_crdr": $('input[name="optionsRadios_crdr"]:checked').val(),
        "<?= csrf_token() ?>": csrf_value
      }, function(data){
        if (data && data.update) {
          load_form('<?= base_url('billing/charges/show') ?>/' + $('#invoice_id').val());
        } else if (data && data.error_text) {
          $('div.jsError').html(data.error_text);
        }
      }, 'json');
    }
  });
});

(function() {
  function enable_btn() {
    $('#btn_update1').prop('disabled', false);
    $('#btn_update2').prop('disabled', false);
  }

  function getCsrfPair() {
    var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
    if (!input) {
      return { name: '<?= csrf_token() ?>', value: '<?= csrf_hash() ?>' };
    }
    return { name: input.getAttribute('name'), value: input.value };
  }

  function updateCsrf(data) {
    if (!data || !data.csrfName || !data.csrfHash) {
      return;
    }
    var input = document.querySelector('input[name="' + data.csrfName + '"]');
    if (input) {
      input.value = data.csrfHash;
    }
  }

  $('#btn_update0').click(function() {
    $('#btn_update1').prop('disabled', true);
    $('#btn_update2').prop('disabled', true);
    var csrf = getCsrfPair();
    var spid = $('#spid').val();

    if (confirm('Are you sure process this invoice')) {
      $.post('<?= base_url('billing/charges/confirm-payment') ?>', {
        "mode": "0",
        "inv_id": $('#invoice_id').val(),
        "spid": spid,
        "received_amount": $('#input_received_amount').val(),
        [csrf.name]: csrf.value
      }, function(data) {
        updateCsrf(data);
        if (data.update == 0) {
          $('div.jsError').html(data.error_text);
        } else {
          load_form('<?= base_url('billing/charges/show') ?>/' + $('#invoice_id').val());
        }
      }, 'json');
    } else {
      setTimeout(enable_btn, 5000);
    }
  });

  $('#btn_update1').click(function() {
    $('#btn_update1').prop('disabled', true);
    $('#btn_update2').prop('disabled', true);
    var csrf = getCsrfPair();
    var spid = $('#spid').val();

    if (confirm('Are you sure process this invoice')) {
      $.post('<?= base_url('billing/charges/confirm-payment') ?>', {
        "mode": "1",
        "inv_id": $('#invoice_id').val(),
        "spid": spid,
        "received_amount": $('#input_received_amount').val(),
        [csrf.name]: csrf.value
      }, function(data) {
        updateCsrf(data);
        if (data.update == 0) {
          $('div.jsError').html(data.error_text);
        } else {
          load_form('<?= base_url('billing/charges/show') ?>/' + $('#invoice_id').val());
        }
      }, 'json');
    } else {
      setTimeout(enable_btn, 5000);
    }
  });

  $('#btn_update2').click(function() {
    $('#btn_update1').prop('disabled', true);
    $('#btn_update2').prop('disabled', true);
    var csrf = getCsrfPair();
    var spid = $('#spid').val();

    if (confirm('Are you sure process this invoice')) {
      $.post('<?= base_url('billing/charges/confirm-payment') ?>', {
        "mode": "2",
        "inv_id": $('#invoice_id').val(),
        "cbo_pay_type": $('#cbo_pay_type').val(),
        "input_card_tran": $('#input_card_tran').val(),
        "spid": spid,
        "received_amount": $('#input_received_amount').val(),
        [csrf.name]: csrf.value
      }, function(data) {
        updateCsrf(data);
        if (data.update == 0) {
          if (typeof notify === 'function') {
            notify('error', 'Please Attention', data.error_text);
          } else {
            $('div.jsError').html(data.error_text);
          }
          setTimeout(enable_btn, 5000);
        } else {
          load_form('<?= base_url('billing/charges/show') ?>/' + $('#invoice_id').val());
        }
      }, 'json');
    } else {
      setTimeout(enable_btn, 5000);
    }
  });

  $('#btn_update3').click(function() {
    var csrf = getCsrfPair();
    $.post('<?= base_url('billing/charges/confirm-payment') ?>', {
      "mode": "3",
      "inv_id": $('#invoice_id').val(),
      "ipd_id": $('#hidden_ipd_id').val(),
      [csrf.name]: csrf.value
    }, function(data) {
      updateCsrf(data);
      if (data.update == 0) {
        $('div.jsError').html(data.error_text);
      } else {
        load_form('<?= base_url('billing/charges/show') ?>/' + $('#invoice_id').val());
      }
    }, 'json');
  });

  $('#btn_update4').click(function() {
    var csrf = getCsrfPair();
    $.post('<?= base_url('billing/charges/confirm-payment') ?>', {
      "mode": "4",
      "inv_id": $('#invoice_id').val(),
      "case_id": $('#hidden_case_id').val(),
      [csrf.name]: csrf.value
    }, function(data) {
      updateCsrf(data);
      if (data.update == 0) {
        $('div.jsError').html(data.error_text);
      } else {
        load_form('<?= base_url('billing/charges/show') ?>/' + $('#invoice_id').val());
      }
    }, 'json');
  });
})();
</script>

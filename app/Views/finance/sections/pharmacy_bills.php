<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <strong>Pharmacy Bills &mdash; Payable (Cr. to Hospital)</strong>
        <span class="badge bg-info text-dark">Pharmacy is a separate entity</span>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Register bills received from the Pharmacy entity. These are amounts <strong>owed by the Hospital to the Pharmacy</strong>
            (Credit to Hospital). Record each bill on receipt and settle when payment is made.
        </p>

        <div id="pharm_bill_alert"></div>

        <form id="pharm_bill_form" class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="text" class="form-control form-control-sm" name="bill_no"
                       placeholder="Bill / Invoice No *" required>
            </div>
            <div class="col-md-3">
                <input type="date" class="form-control form-control-sm" name="bill_date" required>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control form-control-sm" name="pharmacy_name"
                       placeholder="Pharmacy / Supplier Name *" required>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control form-control-sm" name="description"
                       placeholder="Description (medicines / items)">
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="bill_amount"
                       id="pb_bill_amount" placeholder="Bill Amount" oninput="pbCalcNet()">
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="tax_amount"
                       id="pb_tax_amount" placeholder="Tax / GST" value="0" oninput="pbCalcNet()">
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="net_amount"
                       id="pb_net_amount" placeholder="Net Payable" readonly style="background:#f8f9fa;">
            </div>
            <div class="col-md-6">
                <textarea class="form-control form-control-sm" name="remarks" rows="1"
                          placeholder="Remarks / Notes (optional)"></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary btn-sm" type="submit">Register Pharmacy Bill</button>
                <button class="btn btn-outline-secondary btn-sm ms-1" type="reset" onclick="pbCalcNet()">Clear</button>
            </div>
        </form>

        <hr>

        <!-- Settle Payment Modal -->
        <div class="modal fade" id="pharmSettleModal" tabindex="-1" aria-labelledby="pharmSettleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h6 class="modal-title" id="pharmSettleModalLabel">Record Payment to Pharmacy</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="pharm_settle_alert"></div>
                        <form id="pharm_settle_form" class="row g-2">
                            <input type="hidden" name="bill_id" id="ps_bill_id">
                            <div class="col-12">
                                <small class="text-muted" id="ps_bill_info"></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-label-sm mb-0">Amount Paid (Rs.)</label>
                                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm"
                                       name="paid_amount" id="ps_paid_amount" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-label-sm mb-0">Payment Date</label>
                                <input type="date" class="form-control form-control-sm" name="payment_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-label-sm mb-0">Payment Mode</label>
                                <select class="form-select form-select-sm" name="payment_mode">
                                    <option value="">-- Select --</option>
                                    <option value="cash">Cash</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="neft">NEFT / RTGS</option>
                                    <option value="upi">UPI</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-label-sm mb-0">Cheque / Ref No</label>
                                <input type="text" class="form-control form-control-sm" name="payment_ref"
                                       placeholder="Cheque / NEFT / UTR">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success btn-sm" id="ps_submit_btn">Record Payment</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="pharm_bills_table_wrap">
            <?= view('finance/partials/pharmacy_bills_table', ['pharmacy_bills' => $pharmacy_bills ?? []]) ?>
        </div>
    </div>
</div>

<script>
(function () {
    function pbCalcNet() {
        var bill = parseFloat(document.getElementById('pb_bill_amount').value) || 0;
        var tax  = parseFloat(document.getElementById('pb_tax_amount').value) || 0;
        document.getElementById('pb_net_amount').value = (bill + tax).toFixed(2);
    }
    window.pbCalcNet = pbCalcNet;

    function showAlert(boxId, message, ok) {
        var cls  = ok ? 'alert-success' : 'alert-danger';
        var html = '<div class="alert ' + cls + ' alert-dismissible fade show py-2" role="alert">'
            + message
            + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        var box = document.getElementById(boxId);
        if (box) { box.innerHTML = html; }
    }

    function reloadTable() {
        load_form_div('<?= base_url('Finance/pharmacy_bills_table') ?>', 'pharm_bills_table_wrap');
    }

    // Register new bill
    var form = document.getElementById('pharm_bill_form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new window.FormData(form);
            fetch('<?= base_url('Finance/pharmacy_bill_create') ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                if (!res.ok || !res.d || res.d.status !== 1) {
                    showAlert('pharm_bill_alert', (res.d && res.d.message) ? res.d.message : 'Request failed.', false);
                    return;
                }
                showAlert('pharm_bill_alert', res.d.message, true);
                form.reset();
                pbCalcNet();
                reloadTable();
            })
            .catch(function () { showAlert('pharm_bill_alert', 'Network or server error.', false); });
        });
    }

    // Settle / Record Payment
    var settleModal = document.getElementById('pharmSettleModal');
    var settleForm  = document.getElementById('pharm_settle_form');

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-pharm-settle');
        if (!btn) { return; }
        var billId   = btn.getAttribute('data-id') || '';
        var billNo   = btn.getAttribute('data-bill-no') || '';
        var netAmt   = btn.getAttribute('data-net') || '0';
        var paidAmt  = btn.getAttribute('data-paid') || '0';
        var balance  = (parseFloat(netAmt) - parseFloat(paidAmt)).toFixed(2);

        document.getElementById('ps_bill_id').value = billId;
        document.getElementById('ps_paid_amount').value = balance;
        document.getElementById('ps_bill_info').textContent =
            'Bill: ' + billNo + ' | Net: Rs. ' + parseFloat(netAmt).toFixed(2)
            + ' | Already Paid: Rs. ' + parseFloat(paidAmt).toFixed(2)
            + ' | Balance: Rs. ' + balance;

        document.getElementById('pharm_settle_alert').innerHTML = '';
        var modal = new bootstrap.Modal(settleModal);
        modal.show();
    });

    var submitBtn = document.getElementById('ps_submit_btn');
    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            if (!settleForm.checkValidity()) { settleForm.reportValidity(); return; }
            var fd = new window.FormData(settleForm);
            fetch('<?= base_url('Finance/pharmacy_bill_settle') ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                if (!res.ok || !res.d || res.d.status !== 1) {
                    showAlert('pharm_settle_alert', (res.d && res.d.message) ? res.d.message : 'Request failed.', false);
                    return;
                }
                showAlert('pharm_settle_alert', res.d.message, true);
                reloadTable();
                setTimeout(function () {
                    bootstrap.Modal.getInstance(settleModal).hide();
                    settleForm.reset();
                }, 1400);
            })
            .catch(function () { showAlert('pharm_settle_alert', 'Network or server error.', false); });
        });
    }
})();
</script>

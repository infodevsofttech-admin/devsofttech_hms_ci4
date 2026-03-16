<section class="content finance-cashbook">
    <div class="mb-3">
        <h2 class="mb-1">Cash Collection & Disbursement SOP</h2>
        <p class="text-muted mb-0">Capture receipts/disbursements, scroll submission, and compliance checks for Section 269ST and 40A(3).</p>
    </div>

    <div id="cash_alert"></div>

    <div class="row g-2 mb-2">
        <div class="col-md-3 col-6">
            <div class="card border-primary"><div class="card-body py-2"><div class="small text-muted">Today Receipts</div><div class="h5 mb-0 text-primary"><?= number_format((float) ($summary['today_receipts'] ?? 0), 2) ?></div></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-info"><div class="card-body py-2"><div class="small text-muted">Today Disbursements</div><div class="h5 mb-0 text-info"><?= number_format((float) ($summary['today_disbursements'] ?? 0), 2) ?></div></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-danger"><div class="card-body py-2"><div class="small text-muted">Compliance Hold</div><div class="h5 mb-0 text-danger"><?= (int) ($summary['hold_count'] ?? 0) ?></div></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-dark"><div class="card-body py-2"><div class="small text-muted">Pending Scrolls</div><div class="h5 mb-0"><?= (int) ($summary['pending_scroll'] ?? 0) ?></div></div></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header"><strong>1) Cash Entry</strong></div>
                <div class="card-body">
                    <form id="cash_txn_form" class="row g-2">
                        <div class="col-md-4"><input type="date" name="txn_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-md-4">
                            <select class="form-select" name="txn_type" required>
                                <option value="receipt">Receipt</option>
                                <option value="disbursement">Disbursement</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="flow_type" required>
                                <option value="patient">Patient</option>
                                <option value="pharmacy">Pharmacy</option>
                                <option value="vendor">Vendor</option>
                                <option value="salary">Salary</option>
                                <option value="reimbursement">Reimbursement</option>
                                <option value="doctor_payout">Doctor Payout</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4"><input type="text" class="form-control" name="department" placeholder="Department (OPD/IPD/Pharmacy)"></div>
                        <div class="col-md-4"><input type="text" class="form-control" name="reference_no" placeholder="Reference No"></div>
                        <div class="col-md-4"><input type="number" step="0.01" class="form-control" name="amount" placeholder="Amount" required></div>
                        <div class="col-md-4">
                            <select class="form-select" name="mode" required>
                                <option value="cash">Cash</option>
                                <option value="bank">Bank</option>
                                <option value="upi">UPI</option>
                                <option value="card">Card</option>
                            </select>
                        </div>
                        <div class="col-md-8"><input type="text" class="form-control" name="party_name" placeholder="Party / Patient / Vendor"></div>
                        <div class="col-12"><textarea class="form-control" name="narration" rows="2" placeholder="Narration"></textarea></div>
                        <div class="col-12"><button type="submit" class="btn btn-primary btn-sm">Save Cash Entry</button></div>
                    </form>
                    <hr>
                    <div id="cash_txn_table_wrap"><?= view('finance/partials/cash_transactions_table', ['transactions' => $transactions ?? []]) ?></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header"><strong>2) Scroll Submission & Reconciliation</strong></div>
                <div class="card-body">
                    <form id="scroll_form" class="row g-2">
                        <div class="col-md-4"><input type="date" name="scroll_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-md-4"><input type="text" name="department" class="form-control" placeholder="Department" required></div>
                        <div class="col-md-4"><input type="number" step="0.01" name="submitted_amount" class="form-control" placeholder="Submitted Amount" required></div>
                        <div class="col-md-6"><input type="text" name="submitted_by" class="form-control" placeholder="Submitted By"></div>
                        <div class="col-md-6"><input type="text" name="remarks" class="form-control" placeholder="Remarks"></div>
                        <div class="col-12"><button type="submit" class="btn btn-primary btn-sm">Submit Scroll</button></div>
                    </form>
                    <hr>
                    <div id="scroll_table_wrap"><?= view('finance/partials/scroll_table', ['scrolls' => $scrolls ?? []]) ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    function showAlert(message, ok) {
        var cls = ok ? 'alert-success' : 'alert-danger';
        var html = '<div class="alert ' + cls + ' alert-dismissible fade show" role="alert">'
            + message
            + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            + '</div>';
        var box = document.getElementById('cash_alert');
        if (box) {
            box.innerHTML = html;
        }
    }

    function refreshTables() {
        load_form_div('<?= base_url('Finance/cash_transactions_table') ?>', 'cash_txn_table_wrap');
        load_form_div('<?= base_url('Finance/scroll_table') ?>', 'scroll_table_wrap');
    }

    function wireForm(formId, endpoint) {
        var form = document.getElementById(formId);
        if (!form) {
            return;
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new window.FormData(form);

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(function(res) {
                return res.json().then(function(data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function(result) {
                if (!result.ok || !result.data || result.data.status !== 1) {
                    showAlert((result.data && result.data.message) ? result.data.message : 'Request failed', false);
                    return;
                }

                showAlert(result.data.message || 'Saved successfully', true);
                form.reset();
                refreshTables();
            })
            .catch(function() {
                showAlert('Network or server error.', false);
            });
        });
    }

    wireForm('cash_txn_form', '<?= base_url('Finance/cash_transaction_create') ?>');
    wireForm('scroll_form', '<?= base_url('Finance/scroll_create') ?>');
})();
</script>

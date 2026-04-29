<section class="content finance-medical-store-credit">
    <div class="mb-3">
        <h2 class="mb-1">Medical Store Credit Account</h2>
        <p class="text-muted mb-0">Review medical store payout requests, approve them, and post settlement payments.</p>
    </div>

    <div id="ms_credit_alert"></div>

    <div class="row g-2 mb-3">
        <div class="col-md-3 col-6"><div class="card border-secondary"><div class="card-body py-2"><div class="small text-muted">Total Requested</div><div class="h5 mb-0" id="ms_total_requested"><?= esc(number_format((float) ($summary['total_requested'] ?? 0), 2)) ?></div></div></div></div>
        <div class="col-md-3 col-6"><div class="card border-success"><div class="card-body py-2"><div class="small text-muted">Total Paid</div><div class="h5 mb-0 text-success" id="ms_total_paid"><?= esc(number_format((float) ($summary['total_paid'] ?? 0), 2)) ?></div></div></div></div>
        <div class="col-md-3 col-6"><div class="card border-warning"><div class="card-body py-2"><div class="small text-muted">Total Pending</div><div class="h5 mb-0 text-warning" id="ms_total_pending"><?= esc(number_format((float) ($summary['total_pending'] ?? 0), 2)) ?></div></div></div></div>
        <div class="col-md-3 col-6"><div class="card border-info"><div class="card-body py-2"><div class="small text-muted">Open Requests</div><div class="h5 mb-0 text-info" id="ms_open_requests"><?= (int) ($summary['open_requests'] ?? 0) ?></div></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header"><strong>Request Actions</strong></div>
                <div class="card-body">
                    <form id="ms_request_action_form" class="row g-2">
                        <div class="col-12">
                            <label class="form-label mb-1">Request ID</label>
                            <input type="number" class="form-control" name="request_id" id="ms_request_id" min="1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-1">Remark</label>
                            <textarea class="form-control" name="remark" id="ms_action_remark" rows="2" placeholder="Optional review/approval remark"></textarea>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn_ms_review">Mark Finance Review</button>
                            <button type="button" class="btn btn-success btn-sm" id="btn_ms_approve">Approve</button>
                            <button type="button" class="btn btn-danger btn-sm" id="btn_ms_reject">Reject</button>
                        </div>
                    </form>

                    <hr>

                    <form id="ms_payment_form" class="row g-2">
                        <div class="col-12"><strong>Settlement Payment</strong></div>
                        <div class="col-12">
                            <label class="form-label mb-1">Request ID</label>
                            <input type="number" class="form-control" name="request_id" id="ms_payment_request_id" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1">Amount</label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1">Mode</label>
                            <select class="form-select" name="payment_mode">
                                <option value="1">Cash</option>
                                <option value="2">Bank</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1">Bank Source ID</label>
                            <input type="number" class="form-control" name="insert_code" placeholder="Optional">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1">Bank ID</label>
                            <input type="number" class="form-control" name="pay_bank_id" placeholder="Optional">
                        </div>
                        <div class="col-md-6"><input type="text" class="form-control" name="card_tran_id" placeholder="Transaction ID"></div>
                        <div class="col-md-6"><input type="text" class="form-control" name="card_bank" placeholder="Bank/Card Name"></div>
                        <div class="col-12"><textarea class="form-control" name="remark" rows="2" placeholder="Payment remark"></textarea></div>
                        <div class="col-12"><button type="submit" class="btn btn-primary btn-sm">Post Payment</button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Request List</strong>
                    <div class="d-flex gap-2">
                        <select id="ms_status_filter" class="form-select form-select-sm" style="min-width:180px;">
                            <option value="">All Status</option>
                            <option value="submitted">Submitted</option>
                            <option value="finance_review">Finance Review</option>
                            <option value="approved">Approved</option>
                            <option value="partially_paid">Partially Paid</option>
                            <option value="paid">Paid</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_ms_refresh">Refresh</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="ms_requests_table_wrap"><?= view('finance/partials/medical_store_requests_table', ['rows' => $rows ?? []]) ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="msRequestDetailModal" tabindex="-1" aria-labelledby="msRequestDetailModalLabel">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="msRequestDetailModalLabel">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="ms_request_detail_modal_body">
                <div class="p-3 text-muted">Loading...</div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var BASE = '<?= base_url() ?>';

    function showAlert(msg, ok) {
        var cls = ok ? 'alert-success' : 'alert-danger';
        var box = document.getElementById('ms_credit_alert');
        if (!box) return;
        box.innerHTML = '<div class="alert ' + cls + ' alert-dismissible fade show" role="alert">'
            + msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }

    function postForm(url, formData, cb) {
        fetch(url, {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: formData
        }).then(function (res) {
            return res.json().then(function (data) { return { ok: res.ok, data: data }; });
        }).then(cb).catch(function () {
            showAlert('Network or server error.', false);
        });
    }

    function refreshRequestsTable() {
        var status = document.getElementById('ms_status_filter') ? document.getElementById('ms_status_filter').value : '';
        load_form_div(BASE + 'Finance/medical_store_requests_table?status=' + encodeURIComponent(status), 'ms_requests_table_wrap');
    }

    function refreshSummary() {
        fetch(BASE + 'Finance/medical_store_dashboard_card', {
            method: 'GET',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function (res) {
            return res.json().then(function (data) { return { ok: res.ok, data: data }; });
        }).then(function (result) {
            if (!result.ok || !result.data || result.data.status !== 1) {
                return;
            }
            var s = result.data.summary || {};
            var tr = document.getElementById('ms_total_requested');
            var tp = document.getElementById('ms_total_paid');
            var tn = document.getElementById('ms_total_pending');
            var orq = document.getElementById('ms_open_requests');
            if (tr) tr.textContent = Number(s.total_requested || 0).toFixed(2);
            if (tp) tp.textContent = Number(s.total_paid || 0).toFixed(2);
            if (tn) tn.textContent = Number(s.total_pending || 0).toFixed(2);
            if (orq) orq.textContent = String(parseInt(s.open_requests || 0, 10));
        });
    }

    function doStatusAction(endpoint) {
        var requestId = document.getElementById('ms_request_id') ? document.getElementById('ms_request_id').value : '';
        var remark = document.getElementById('ms_action_remark') ? document.getElementById('ms_action_remark').value : '';
        if (!requestId) {
            showAlert('Request ID is required.', false);
            return;
        }
        var fd = new window.FormData();
        fd.append('request_id', requestId);
        fd.append('remark', remark);

        postForm(BASE + endpoint, fd, function (result) {
            if (!result.ok || !result.data || result.data.status !== 1) {
                showAlert((result.data && result.data.message) ? result.data.message : 'Action failed.', false);
                return;
            }
            showAlert(result.data.message || 'Updated successfully.', true);
            refreshRequestsTable();
            refreshSummary();
        });
    }

    var paymentForm = document.getElementById('ms_payment_form');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new window.FormData(paymentForm);
            postForm(BASE + 'Finance/medical_store_payment_create', fd, function (result) {
                if (!result.ok || !result.data || result.data.status !== 1) {
                    showAlert((result.data && result.data.message) ? result.data.message : 'Payment failed.', false);
                    return;
                }
                showAlert(result.data.message || 'Payment posted successfully.', true);
                paymentForm.reset();
                refreshRequestsTable();
                refreshSummary();
            });
        });
    }

    window.openMedicalStoreRequestDetail = function (requestId) {
        if (!requestId) return;
        var body = document.getElementById('ms_request_detail_modal_body');
        if (body) body.innerHTML = '<div class="p-3 text-muted">Loading...</div>';

        fetch(BASE + 'Finance/medical_store_request_lines_table?request_id=' + encodeURIComponent(String(requestId)), {
            method: 'GET',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function (r) {
            return r.text().then(function (html) { return { ok: r.ok, html: html }; });
        }).then(function (result) {
            if (!body) return;
            body.innerHTML = result.ok ? result.html : '<div class="p-3 text-danger">Unable to load request details.</div>';
        }).catch(function () {
            if (body) body.innerHTML = '<div class="p-3 text-danger">Network error while loading details.</div>';
        });

        var modalEl = document.getElementById('msRequestDetailModal');
        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    };

    var btnRefresh = document.getElementById('btn_ms_refresh');
    if (btnRefresh) btnRefresh.addEventListener('click', refreshRequestsTable);

    var statusFilter = document.getElementById('ms_status_filter');
    if (statusFilter) statusFilter.addEventListener('change', refreshRequestsTable);

    var btnReview = document.getElementById('btn_ms_review');
    if (btnReview) btnReview.addEventListener('click', function () { doStatusAction('Finance/medical_store_request_review'); });

    var btnApprove = document.getElementById('btn_ms_approve');
    if (btnApprove) btnApprove.addEventListener('click', function () { doStatusAction('Finance/medical_store_request_approve'); });

    var btnReject = document.getElementById('btn_ms_reject');
    if (btnReject) btnReject.addEventListener('click', function () { doStatusAction('Finance/medical_store_request_reject'); });
})();
</script>

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
        <div class="col-12">
            <div class="card">
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

<div class="modal fade" id="msRequestActionModal" tabindex="-1" aria-labelledby="msRequestActionModalLabel">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="msRequestActionModalLabel">Request Action</h5>
                    <div class="small text-muted" id="ms_request_action_modal_subtitle">Review request details and update its workflow status.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-lg-4">
                        <form id="ms_request_action_form" class="row g-2">
                            <div class="col-12">
                                <label class="form-label mb-1">Request ID</label>
                                <input type="number" class="form-control" name="request_id" id="ms_request_id" min="1" required readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label mb-1">Request No</label>
                                <input type="text" class="form-control" id="ms_request_no" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label mb-1">Status</label>
                                <input type="text" class="form-control" id="ms_request_status" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label mb-1">Requested</label>
                                <input type="text" class="form-control" id="ms_request_requested_amount" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label mb-1">Pending</label>
                                <input type="text" class="form-control" id="ms_request_pending_amount" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label mb-1">Remark</label>
                                <textarea class="form-control" name="remark" id="ms_action_remark" rows="3" placeholder="Optional review/approval remark"></textarea>
                            </div>
                            <div class="col-12 d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btn_ms_review">Mark Finance Review</button>
                                <button type="button" class="btn btn-success btn-sm" id="btn_ms_approve">Approve</button>
                                <button type="button" class="btn btn-danger btn-sm" id="btn_ms_reject">Reject</button>
                                <button type="button" class="btn btn-primary btn-sm d-none" id="btn_ms_to_settlement">Settlement Payment</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-8">
                        <div id="ms_request_action_detail_wrap" class="border rounded overflow-hidden">
                            <div class="p-3 text-muted">Loading...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="msSettlementModal" tabindex="-1" aria-labelledby="msSettlementModalLabel">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="msSettlementModalLabel">Settlement Payment</h5>
                    <div class="small text-muted" id="ms_settlement_modal_subtitle">Post settlement for the approved medical store credit request.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="ms_payment_form" class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label mb-1">Request ID</label>
                        <input type="number" class="form-control" name="request_id" id="ms_payment_request_id" min="1" required readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label mb-1">Request No</label>
                        <input type="text" class="form-control" id="ms_payment_request_no" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label mb-1">Amount</label>
                        <input type="number" class="form-control" name="amount" id="ms_payment_amount" step="0.01" min="0.01" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label mb-1">Mode</label>
                        <select class="form-select" name="payment_mode" id="ms_payment_mode">
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
                    <div class="col-md-6">
                        <label class="form-label mb-1">Transaction ID</label>
                        <input type="text" class="form-control" name="card_tran_id" placeholder="Transaction ID">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label mb-1">Bank/Card Name</label>
                        <input type="text" class="form-control" name="card_bank" placeholder="Bank/Card Name">
                    </div>
                    <div class="col-12">
                        <label class="form-label mb-1">Payment remark</label>
                        <textarea class="form-control" name="remark" rows="3" placeholder="Payment remark"></textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-between align-items-center pt-2">
                        <div class="small text-muted" id="ms_payment_pending_hint">Pending amount will be loaded from the selected request.</div>
                        <button type="submit" class="btn btn-primary btn-sm">Post Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var BASE = '<?= base_url() ?>';
    var currentRequest = null;

    function formatAmount(value) {
        return Number(value || 0).toFixed(2);
    }

    function getActionModal() {
        var modalEl = document.getElementById('msRequestActionModal');
        if (!modalEl || !window.bootstrap || !window.bootstrap.Modal) {
            return null;
        }

        return window.bootstrap.Modal.getOrCreateInstance(modalEl);
    }

    function getSettlementModal() {
        var modalEl = document.getElementById('msSettlementModal');
        if (!modalEl || !window.bootstrap || !window.bootstrap.Modal) {
            return null;
        }

        return window.bootstrap.Modal.getOrCreateInstance(modalEl);
    }

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

    function updateActionButtons(request) {
        var status = request && request.status ? String(request.status) : '';
        var pending = Number(request && request.pending_amount ? request.pending_amount : 0);
        var btnReview = document.getElementById('btn_ms_review');
        var btnApprove = document.getElementById('btn_ms_approve');
        var btnReject = document.getElementById('btn_ms_reject');
        var btnSettlement = document.getElementById('btn_ms_to_settlement');

        if (btnReview) btnReview.disabled = status !== 'submitted';
        if (btnApprove) btnApprove.disabled = status !== 'finance_review';
        if (btnReject) btnReject.disabled = !(status === 'submitted' || status === 'finance_review');
        if (btnSettlement) {
            var canSettle = (status === 'approved' || status === 'partially_paid') && pending > 0;
            btnSettlement.classList.toggle('d-none', !canSettle);
            btnSettlement.disabled = !canSettle;
        }
    }

    function fillActionModal(request) {
        currentRequest = request || null;
        var requestId = document.getElementById('ms_request_id');
        var requestNo = document.getElementById('ms_request_no');
        var requestStatus = document.getElementById('ms_request_status');
        var requestedAmount = document.getElementById('ms_request_requested_amount');
        var pendingAmount = document.getElementById('ms_request_pending_amount');
        var subtitle = document.getElementById('ms_request_action_modal_subtitle');

        if (requestId) requestId.value = request && request.id ? String(request.id) : '';
        if (requestNo) requestNo.value = request && request.request_no ? String(request.request_no) : '';
        if (requestStatus) requestStatus.value = request && request.status ? String(request.status) : '';
        if (requestedAmount) requestedAmount.value = formatAmount(request && request.requested_amount ? request.requested_amount : 0);
        if (pendingAmount) pendingAmount.value = formatAmount(request && request.pending_amount ? request.pending_amount : 0);
        if (subtitle) {
            subtitle.textContent = request
                ? 'Request #' + String(request.id || '') + ' is currently in ' + String(request.status || 'draft') + ' status.'
                : 'Review request details and update its workflow status.';
        }

        updateActionButtons(request || {});
    }

    function fillSettlementForm(request) {
        var requestId = document.getElementById('ms_payment_request_id');
        var requestNo = document.getElementById('ms_payment_request_no');
        var amount = document.getElementById('ms_payment_amount');
        var hint = document.getElementById('ms_payment_pending_hint');

        if (requestId) requestId.value = request && request.id ? String(request.id) : '';
        if (requestNo) requestNo.value = request && request.request_no ? String(request.request_no) : '';
        if (amount) amount.value = formatAmount(request && request.pending_amount ? request.pending_amount : 0);
        if (hint) hint.textContent = 'Pending amount: ' + formatAmount(request && request.pending_amount ? request.pending_amount : 0);
    }

    function loadRequestActionModal(requestId) {
        var detailWrap = document.getElementById('ms_request_action_detail_wrap');
        if (detailWrap) {
            detailWrap.innerHTML = '<div class="p-3 text-muted">Loading...</div>';
        }

        fetch(BASE + 'Finance/medical_store_request_detail/' + encodeURIComponent(String(requestId)), {
            method: 'GET',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function (res) {
            return res.json().then(function (data) { return { ok: res.ok, data: data }; });
        }).then(function (result) {
            if (!result.ok || !result.data || result.data.status !== 1 || !result.data.request) {
                showAlert((result.data && result.data.message) ? result.data.message : 'Unable to load request.', false);
                return;
            }

            fillActionModal(result.data.request);
            fetch(BASE + 'Finance/medical_store_request_lines_table?request_id=' + encodeURIComponent(String(requestId)), {
                method: 'GET',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(function (r) {
                return r.text().then(function (html) { return { ok: r.ok, html: html }; });
            }).then(function (htmlResult) {
                if (!detailWrap) return;
                detailWrap.innerHTML = htmlResult.ok ? htmlResult.html : '<div class="p-3 text-danger">Unable to load request details.</div>';
            }).catch(function () {
                if (detailWrap) detailWrap.innerHTML = '<div class="p-3 text-danger">Network error while loading details.</div>';
            });

            var actionModal = getActionModal();
            if (actionModal) {
                actionModal.show();
            }
        }).catch(function () {
            showAlert('Network or server error.', false);
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

            if (requestId) {
                loadRequestActionModal(requestId);
            }

            if (endpoint === 'Finance/medical_store_request_approve' && currentRequest) {
                currentRequest.status = 'approved';
                fillSettlementForm(currentRequest);
                var actionModal = getActionModal();
                var settlementModal = getSettlementModal();
                if (actionModal) actionModal.hide();
                if (settlementModal) settlementModal.show();
            }
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

                var settlementModal = getSettlementModal();
                if (settlementModal) settlementModal.hide();

                if (currentRequest && currentRequest.id) {
                    loadRequestActionModal(currentRequest.id);
                }
            });
        });
    }

    window.openMedicalStoreRequestAction = function (requestId) {
        if (!requestId) {
            return;
        }

        loadRequestActionModal(requestId);
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

    var btnSettlement = document.getElementById('btn_ms_to_settlement');
    if (btnSettlement) {
        btnSettlement.addEventListener('click', function () {
            if (!currentRequest) {
                showAlert('Select a request first.', false);
                return;
            }

            fillSettlementForm(currentRequest);
            var settlementModal = getSettlementModal();
            if (settlementModal) {
                settlementModal.show();
            }
        });
    }
})();
</script>

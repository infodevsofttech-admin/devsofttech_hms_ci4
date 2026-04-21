<section class="content finance-bank-audit">
    <div class="mb-3">
        <h2 class="mb-1">Bank Payment Audit</h2>
        <p class="text-muted mb-0">Reconcile bank payments and track settlement matching against your bank statement.</p>
    </div>

    <div id="bank_audit_alert"></div>

    <!-- Summary Cards -->
    <div class="row g-2 mb-3">
        <div class="col-lg-3 col-md-6 col-6">
            <div class="card border-warning">
                <div class="card-body py-2">
                    <div class="small text-muted">System Bank Payments Pending Match</div>
                    <div class="h5 mb-0 text-warning" id="card_direct_unmatched"><?= (int) ($direct_unmatched ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-6">
            <div class="card border-success">
                <div class="card-body py-2">
                    <div class="small text-muted">System Bank Payments Matched</div>
                    <div class="h5 mb-0 text-success" id="card_direct_matched"><?= (int) ($direct_matched ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-6">
            <div class="card border-info">
                <div class="card-body py-2">
                    <div class="small text-muted">Settlement Entries Unmatched</div>
                    <div class="h5 mb-0 text-info" id="card_sett_unmatched"><?= (int) ($sett_unmatched ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-6">
            <div class="card border-primary">
                <div class="card-body py-2">
                    <div class="small text-muted">Settlement Entries Matched</div>
                    <div class="h5 mb-0 text-primary" id="card_sett_matched"><?= (int) ($sett_matched ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" id="bankAuditTabs">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab_direct" type="button">
                <i class="bi bi-bank me-1"></i>System Bank Payments
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_settlement" type="button">
                <i class="bi bi-collection me-1"></i>Settlement Entries
            </button>
        </li>
    </ul>

    <div class="tab-content">

        <!-- ══ TAB 1: DIRECT TRANSACTIONS ══════════════════════════════════════ -->
        <div class="tab-pane fade show active" id="tab_direct">
            <div class="row g-3">

                <!-- System Bank Payments (shown first on open) -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>System Bank Payments</strong>
                            <div class="d-flex gap-2 flex-nowrap align-items-center overflow-auto">
                                <input type="date" id="direct_from" class="form-control form-control-sm" style="width:130px" value="<?= date('Y-m-01') ?>">
                                <input type="date" id="direct_to" class="form-control form-control-sm" style="width:130px" value="<?= date('Y-m-d') ?>">
                                <select id="direct_status_filter" class="form-select form-select-sm" style="width:120px">
                                    <option value="unmatched">Unmatched</option>
                                    <option value="matched">Matched</option>
                                    <option value="all">All</option>
                                </select>
                                <select id="direct_bank_name_filter" class="form-select form-select-sm" style="width:150px">
                                    <?php foreach (($bank_options ?? []) as $b): ?>
                                        <option value="<?= (int) ($b['id'] ?? 0) ?>"><?= esc((string) ($b['bank_name'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select id="direct_accepted_by_filter" class="form-select form-select-sm" style="width:220px">
                                    <option value="">All Users</option>
                                    <?php foreach (($accepted_by_users ?? []) as $u): ?>
                                        <option value="<?= (int) ($u['user_id'] ?? 0) ?>"><?= esc((string) ($u['user_name'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="refreshDirectTable()">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div id="direct_payments_table_wrap"></div>
                        </div>
                    </div>
                    <div class="card border-0 bg-light mt-2">
                        <div class="card-body py-2 px-2">
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <input type="text" class="form-control form-control-sm" id="batch_match_remarks" style="min-width:220px;max-width:360px;" placeholder="Match remarks (optional)">
                                <button type="button" class="btn btn-primary btn-sm" onclick="batchMatchSelectedPayments()">
                                    <i class="bi bi-check2-square me-1"></i>Match Selected
                                </button>
                                <input type="text" class="form-control form-control-sm" id="settlement_remarks" style="min-width:220px;max-width:360px;" placeholder="Settlement remarks (optional)">
                                <button type="button" class="btn btn-warning btn-sm" onclick="createSettlementForSelectedPayments()">
                                    <i class="bi bi-stack me-1"></i>Settlement Selected
                                </button>
                                <span class="text-muted small">Use checkboxes in first column for batch confirmation.</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ══ TAB 2: SETTLEMENT ENTRIES ═══════════════════════════════════════ -->
        <div class="tab-pane fade" id="tab_settlement">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong>Settlement Entries</strong>
                            <div class="d-flex gap-2 flex-nowrap align-items-center overflow-auto">
                                <input type="date" id="sett_from" class="form-control form-control-sm" style="width:130px" value="<?= date('Y-m-01') ?>">
                                <input type="date" id="sett_to" class="form-control form-control-sm" style="width:130px" value="<?= date('Y-m-d') ?>">
                                <select id="sett_status_filter" class="form-select form-select-sm" style="width:120px">
                                    <option value="unmatched">Unmatched</option>
                                    <option value="matched">Matched</option>
                                    <option value="all">All</option>
                                </select>
                                <select id="sett_bank_filter" class="form-select form-select-sm" style="width:150px">
                                    <?php foreach (($bank_options ?? []) as $b): ?>
                                        <option value="<?= (int) ($b['id'] ?? 0) ?>"><?= esc((string) ($b['bank_name'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="refreshSettlementTable()">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div id="settlement_entries_table_wrap"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /tab-content -->
</section>

<div class="modal fade" id="settlementPaymentsModal" tabindex="-1" aria-labelledby="settlementPaymentsModalLabel">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="settlementPaymentsModalLabel">Linked Settlement Payments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="settlement_payments_modal_body">
                <div class="p-3 text-muted">Loading...</div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var BASE = '<?= base_url() ?>';

    function showAlert(msg, ok) {
        var box = document.getElementById('bank_audit_alert');
        if (!box) return;
        box.innerHTML = '<div class="alert ' + (ok ? 'alert-success' : 'alert-danger') + ' alert-dismissible fade show" role="alert">'
            + msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }

    // ─── Direct / Settlement tables ─────────────────────────────────────────────

    window.refreshDirectTable = function () {
        var from   = document.getElementById('direct_from')?.value || '';
        var to     = document.getElementById('direct_to')?.value || '';
        var status = document.getElementById('direct_status_filter')?.value || 'unmatched';
        var bankId   = document.getElementById('direct_bank_name_filter')?.value || '';
        var acceptedBy = document.getElementById('direct_accepted_by_filter')?.value || '';
        load_form_div(BASE + 'Finance/bank_audit_direct_payments_table?from_date=' + encodeURIComponent(from) + '&to_date=' + encodeURIComponent(to) + '&status=' + encodeURIComponent(status) + '&bank_id=' + encodeURIComponent(bankId) + '&accepted_by=' + encodeURIComponent(acceptedBy), 'direct_payments_table_wrap');
    };

    window.refreshSettlementTable = function () {
        var from = document.getElementById('sett_from')?.value || '';
        var to   = document.getElementById('sett_to')?.value || '';
        var status = document.getElementById('sett_status_filter')?.value || 'unmatched';
        var bankId = document.getElementById('sett_bank_filter')?.value || '';
        load_form_div(BASE + 'Finance/bank_settlement_entries_table?from_date=' + encodeURIComponent(from) + '&to_date=' + encodeURIComponent(to) + '&status=' + encodeURIComponent(status) + '&bank_id=' + encodeURIComponent(bankId), 'settlement_entries_table_wrap');
    };

    window.matchSettlementWithBankStatement = function (settlementId) {
        if (!settlementId) return;
        var remarks = window.prompt('Bank statement match remarks (optional):', '') || '';
        var fd = new window.FormData();
        fd.append('settlement_id', String(settlementId));
        fd.append('remarks', remarks);

        fetch(BASE + 'Finance/bank_settlement_match_statement', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: fd
        }).then(function (r) { return r.json().then(function (d) { return {ok: r.ok, d: d}; }); })
        .then(function (result) {
            showAlert(result.d.message || (result.ok ? 'Updated.' : 'Error.'), result.ok && result.d.status === 1);
            if (result.ok && result.d.status === 1) {
                refreshSettlementTable();
            }
        }).catch(function () { showAlert('Network error.', false); });
    };

    window.openSettlementLinkedPayments = function (settlementId, settlementRef) {
        if (!settlementId) return;

        var title = document.getElementById('settlementPaymentsModalLabel');
        if (title) {
            title.textContent = 'Linked Payments - ' + (settlementRef || ('Settlement #' + settlementId));
        }

        var body = document.getElementById('settlement_payments_modal_body');
        if (body) {
            body.innerHTML = '<div class="p-3 text-muted">Loading...</div>';
        }

        fetch(BASE + 'Finance/bank_settlement_linked_payments_table?settlement_id=' + encodeURIComponent(String(settlementId)), {
            method: 'GET',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function (r) { return r.text().then(function (html) { return {ok: r.ok, html: html}; }); })
        .then(function (result) {
            if (!body) return;
            if (!result.ok) {
                body.innerHTML = '<div class="p-3 text-danger">Unable to load linked payments.</div>';
                return;
            }
            body.innerHTML = result.html;
        }).catch(function () {
            if (body) {
                body.innerHTML = '<div class="p-3 text-danger">Network error while loading linked payments.</div>';
            }
        });

        var modalEl = document.getElementById('settlementPaymentsModal');
        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
            var modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    };

    // ─── Direct match actions ─────────────────────────────────────────────────

    window.toggleSelectAllBankPayments = function (checkbox) {
        var checks = document.querySelectorAll('.bank-payment-check');
        checks.forEach(function (item) { item.checked = !!checkbox.checked; });
    };

    window.matchSinglePayment = function (paymentId) {
        if (!paymentId) return;
        var remarks = window.prompt('Bank statement remarks (optional):', '') || '';
        var fd = new window.FormData();
        fd.append('payment_id', String(paymentId));
        fd.append('remarks', remarks);
        fetch(BASE + 'Finance/bank_reconcile_match', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: fd
        }).then(function (r) { return r.json().then(function (d) { return {ok: r.ok, d: d}; }); })
        .then(function (result) {
            showAlert(result.d.message || (result.ok ? 'Matched.' : 'Error.'), result.ok && result.d.status === 1);
            if (result.ok && result.d.status === 1) {
                refreshDirectTable();
            }
        }).catch(function () { showAlert('Network error.', false); });
    };

    window.batchMatchSelectedPayments = function () {
        var checks = document.querySelectorAll('.bank-payment-check:checked');
        if (!checks.length) {
            showAlert('Select at least one payment from first-column checkboxes.', false);
            return;
        }

        var fd = new window.FormData();
        checks.forEach(function (cb) { fd.append('payment_ids[]', cb.value); });
        fd.append('remarks', document.getElementById('batch_match_remarks')?.value || '');

        fetch(BASE + 'Finance/bank_reconcile_batch_match', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: fd
        }).then(function (r) { return r.json().then(function (d) { return {ok: r.ok, d: d}; }); })
        .then(function (result) {
            showAlert(result.d.message || (result.ok ? 'Batch matched.' : 'Error.'), result.ok && result.d.status === 1);
            if (result.ok && result.d.status === 1) {
                var remarksBox = document.getElementById('batch_match_remarks');
                if (remarksBox) { remarksBox.value = ''; }
                refreshDirectTable();
            }
        }).catch(function () { showAlert('Network error.', false); });
    };

    window.createSettlementForSelectedPayments = function () {
        var checks = document.querySelectorAll('.bank-payment-check:checked');
        if (!checks.length) {
            showAlert('Select at least one payment from first-column checkboxes.', false);
            return;
        }

        var fd = new window.FormData();
        checks.forEach(function (cb) { fd.append('payment_ids[]', cb.value); });
        fd.append('remarks', document.getElementById('settlement_remarks')?.value || '');

        fetch(BASE + 'Finance/bank_settlement_create', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: fd
        }).then(function (r) { return r.json().then(function (d) { return {ok: r.ok, d: d}; }); })
        .then(function (result) {
            showAlert(result.d.message || (result.ok ? 'Settlement entry created.' : 'Error.'), result.ok && result.d.status === 1);
            if (result.ok && result.d.status === 1) {
                var remarksBox = document.getElementById('settlement_remarks');
                if (remarksBox) { remarksBox.value = ''; }
                refreshDirectTable();
                refreshSettlementTable();
            }
        }).catch(function () { showAlert('Network error.', false); });
    };

    window.unmatchPayment = function (paymentId) {
        if (!confirm('Reverse this match?')) return;
        var fd = new window.FormData();
        fd.append('payment_id', paymentId);
        fetch(BASE + 'Finance/bank_reconcile_unmatch', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: fd
        }).then(function (r) { return r.json().then(function (d) { return {ok: r.ok, d: d}; }); })
        .then(function (result) {
            showAlert(result.d.message || (result.ok ? 'Done.' : 'Error.'), result.ok && result.d.status === 1);
            if (result.ok && result.d.status === 1) { refreshDirectTable(); refreshSettlementTable(); }
        }).catch(function () { showAlert('Network error.', false); });
    };

    // ─── Auto-load on page ready ─────────────────────────────────────────────────
    refreshDirectTable();
    refreshSettlementTable();

    // Re-load tables when switching tabs
    document.querySelectorAll('#bankAuditTabs [data-bs-toggle="tab"]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function (e) {
            if (e.target.dataset.bsTarget === '#tab_direct') { refreshDirectTable(); }
            if (e.target.dataset.bsTarget === '#tab_settlement') { refreshSettlementTable(); }
        });
    });
})();
</script>

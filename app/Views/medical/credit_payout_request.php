<div class="pagetitle">
    <h1>Medical Store Credit Payout Request</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Medical Store</li>
            <li class="breadcrumb-item active">Credit Payout Request</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div id="med_credit_req_alert"></div>
    <style>
        #med_req_status_dashboard_strip {
            display: grid;
            grid-template-columns: repeat(7, minmax(110px, 1fr));
            gap: 8px;
        }
        #med_req_status_dashboard_strip .med-stat {
            border: 1px solid #d9dee6;
            border-radius: 6px;
            padding: 8px 10px;
            background: #fff;
            min-height: 58px;
        }
        #med_req_status_dashboard_strip .med-stat-label {
            color: #6c757d;
            font-size: 12px;
            line-height: 1.1;
        }
        #med_req_status_dashboard_strip .med-stat-value {
            font-size: 28px;
            font-weight: 600;
            line-height: 1;
            margin-top: 4px;
        }
        #med_request_history_table {
            width: 100% !important;
        }
        @media (max-width: 1200px) {
            #med_req_status_dashboard_strip {
                grid-template-columns: repeat(4, minmax(110px, 1fr));
            }
        }
        @media (max-width: 768px) {
            #med_req_status_dashboard_strip {
                grid-template-columns: repeat(2, minmax(110px, 1fr));
            }
        }
    </style>

    <?php
        $historyRows = $rows ?? [];
        $statusCounts = [
            'submitted' => 0,
            'finance_review' => 0,
            'approved' => 0,
            'partially_paid' => 0,
            'paid' => 0,
            'rejected' => 0,
        ];
        foreach ($historyRows as $historyRow) {
            $st = strtolower(trim((string) ($historyRow['status'] ?? '')));
            if (array_key_exists($st, $statusCounts)) {
                $statusCounts[$st]++;
            }
        }
    ?>

    <div class="card mb-3" id="med_req_status_dashboard">
        <div class="card-body p-2">
            <div id="med_req_status_dashboard_strip">
                <div class="med-stat"><div class="med-stat-label">Total</div><div class="med-stat-value text-dark" id="med_dash_total"><?= (int) count($historyRows) ?></div></div>
                <div class="med-stat"><div class="med-stat-label">Submitted</div><div class="med-stat-value text-primary" id="med_dash_submitted"><?= (int) ($statusCounts['submitted'] ?? 0) ?></div></div>
                <div class="med-stat"><div class="med-stat-label">Finance Review</div><div class="med-stat-value text-warning" id="med_dash_finance_review"><?= (int) ($statusCounts['finance_review'] ?? 0) ?></div></div>
                <div class="med-stat"><div class="med-stat-label">Approved</div><div class="med-stat-value text-success" id="med_dash_approved"><?= (int) ($statusCounts['approved'] ?? 0) ?></div></div>
                <div class="med-stat"><div class="med-stat-label">Partial</div><div class="med-stat-value text-info" id="med_dash_partially_paid"><?= (int) ($statusCounts['partially_paid'] ?? 0) ?></div></div>
                <div class="med-stat"><div class="med-stat-label">Paid</div><div class="med-stat-value text-secondary" id="med_dash_paid"><?= (int) ($statusCounts['paid'] ?? 0) ?></div></div>
                <div class="med-stat"><div class="med-stat-label">Rejected</div><div class="med-stat-value text-danger" id="med_dash_rejected"><?= (int) ($statusCounts['rejected'] ?? 0) ?></div></div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs" id="medCreditTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="med-credit-select-tab" data-bs-toggle="tab" data-bs-target="#med-credit-select-pane" type="button" role="tab" aria-controls="med-credit-select-pane" aria-selected="true">1) Select Credit Entries</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="med-credit-history-tab" data-bs-toggle="tab" data-bs-target="#med-credit-history-pane" type="button" role="tab" aria-controls="med-credit-history-pane" aria-selected="false">Request History</button>
        </li>
    </ul>

    <div class="tab-content border border-top-0 p-3 bg-white" id="medCreditTabsContent">
        <div class="tab-pane fade show active" id="med-credit-select-pane" role="tabpanel" aria-labelledby="med-credit-select-tab" tabindex="0">

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>1) Select Credit Entries</strong>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_med_pool_refresh">Refresh Pool</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn_med_pool_select_all">Select All</button>
                    </div>
                </div>
                <div class="card-body border-bottom pb-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label mb-1">Scope</label>
                            <select class="form-select form-select-sm" id="med_pool_scope">
                                <option value="all">All Eligible</option>
                                <option value="ipd">IPD Discharged Only</option>
                                <option value="org">Org OPD Submitted Only</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1">From</label>
                            <input type="date" class="form-control form-control-sm" id="med_pool_from_date">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1">To</label>
                            <input type="date" class="form-control form-control-sm" id="med_pool_to_date">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1">Group By</label>
                            <select class="form-select form-select-sm" id="med_pool_group_by">
                                <option value="none">None</option>
                                <option value="ipd">IPD No.</option>
                                <option value="case">Org Case ID</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-outline-dark w-100" id="btn_med_pool_apply_filter">Apply</button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 420px; overflow:auto;">
                        <table class="table table-sm table-bordered align-middle mb-0" id="med_credit_pool_table" style="width:100%;">
                            <thead class="table-light" style="position:sticky;top:0;z-index:2;">
                                <tr>
                                    <th style="width:42px;"><input type="checkbox" id="med_pool_master"></th>
                                    <th>Source</th>
                                    <th>IPD</th>
                                    <th>Case</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header"><strong>2) Submit Payout Request</strong></div>
                <div class="card-body">
                    <form id="med_credit_request_form" class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1">Request Date</label>
                            <input type="date" class="form-control" name="request_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1">No. of Invoices</label>
                            <input type="text" class="form-control" id="med_selected_count" value="0" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-1">Total Amount</label>
                            <input type="text" class="form-control" id="med_selected_total" value="0.00" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-1">Selected Invoices</label>
                            <div class="table-responsive border rounded" style="max-height: 180px; overflow:auto;">
                                <table class="table table-sm table-bordered mb-0" id="med_selected_preview_table">
                                    <thead class="table-light" style="position:sticky;top:0;z-index:1;">
                                        <tr>
                                            <th>Source</th>
                                            <th>IPD / Case</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="3" class="text-center text-muted py-2">No invoices selected.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="small text-muted mt-1">Each invoice appears once in this list.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-1">Remarks</label>
                            <textarea class="form-control" name="remarks" rows="3" placeholder="Optional remarks for finance"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-sm">Submit Request</button>
                        </div>
                    </form>
                    <hr>
                    <div class="small text-muted">Use Request History tab to track status updates. Use Finance panel for approval and payment settlement.</div>
                </div>
            </div>
        </div>
    </div>

        </div>
        <div class="tab-pane fade" id="med-credit-history-pane" role="tabpanel" aria-labelledby="med-credit-history-tab" tabindex="0">

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Request History</strong>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_med_history_refresh">Refresh</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0" id="med_request_history_table">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Request No</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Requested</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Pending</th>
                        </tr>
                    </thead>
                    <tbody><tr><td colspan="7" class="text-center text-muted py-3">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
        </div>
    </div>
</section>

<script>
(function () {
    var BASE = '<?= base_url() ?>';
    var poolTable = null;
    var historyTable = null;
    var selectedMap = {};
    var poolRowCache = {};

    function showAlert(msg, ok) {
        var box = document.getElementById('med_credit_req_alert');
        if (!box) return;
        box.innerHTML = '<div class="alert ' + (ok ? 'alert-success' : 'alert-danger') + ' alert-dismissible fade show" role="alert">'
            + msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }

    function getStatusBadgeClass(status) {
        var s = String(status || '').toLowerCase();
        if (s === 'submitted') return 'bg-primary';
        if (s === 'finance_review') return 'bg-warning text-dark';
        if (s === 'approved') return 'bg-success';
        if (s === 'partially_paid') return 'bg-info text-dark';
        if (s === 'paid') return 'bg-dark';
        if (s === 'rejected') return 'bg-danger';
        return 'bg-secondary';
    }

    function refreshStatusDashboard(summary) {
        var s = summary || {};
        var counts = {
            total: Number(s.total || 0),
            submitted: Number(s.submitted || 0),
            finance_review: Number(s.finance_review || 0),
            approved: Number(s.approved || 0),
            partially_paid: Number(s.partially_paid || 0),
            paid: Number(s.paid || 0),
            rejected: Number(s.rejected || 0)
        };

        var totalEl = document.getElementById('med_dash_total');
        var submittedEl = document.getElementById('med_dash_submitted');
        var reviewEl = document.getElementById('med_dash_finance_review');
        var approvedEl = document.getElementById('med_dash_approved');
        var partialEl = document.getElementById('med_dash_partially_paid');
        var paidEl = document.getElementById('med_dash_paid');
        var rejectedEl = document.getElementById('med_dash_rejected');

        if (totalEl) totalEl.textContent = String(counts.total);
        if (submittedEl) submittedEl.textContent = String(counts.submitted);
        if (reviewEl) reviewEl.textContent = String(counts.finance_review);
        if (approvedEl) approvedEl.textContent = String(counts.approved);
        if (partialEl) partialEl.textContent = String(counts.partially_paid);
        if (paidEl) paidEl.textContent = String(counts.paid);
        if (rejectedEl) rejectedEl.textContent = String(counts.rejected);
    }

    function getRowKey(row) {
        return String(row.source_type || '') + ':' + String(row.source_ref_id || 0);
    }

    function selectedRows() {
        return Object.keys(selectedMap).map(function (key) {
            return selectedMap[key];
        });
    }

    function clearSelectedRows() {
        selectedMap = {};
        refreshSelectionSummary();
        var master = document.getElementById('med_pool_master');
        if (master) {
            master.checked = false;
        }
    }

    function refreshSelectionSummary() {
        var rows = selectedRows();
        var total = 0;
        rows.forEach(function (row) { total += Number(row.line_amount || 0); });
        var c = document.getElementById('med_selected_count');
        var t = document.getElementById('med_selected_total');
        if (c) c.value = String(rows.length);
        if (t) t.value = total.toFixed(2);

        var previewBody = document.querySelector('#med_selected_preview_table tbody');
        if (previewBody) {
            if (!rows.length) {
                previewBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-2">No invoices selected.</td></tr>';
            } else {
                var html = '';
                rows.forEach(function (row) {
                    var ref = String(row.source_type || '') + '#' + Number(row.source_ref_id || 0);
                    var ipd = String(row.ipd_code || '').trim();
                    var cse = String(row.case_code || '').trim();
                    var label = ipd !== '' ? ('IPD: ' + ipd) : (cse !== '' ? ('Case: ' + cse) : '-');
                    html += '<tr>'
                        + '<td>' + ref + '</td>'
                        + '<td>' + label + '</td>'
                        + '<td class="text-end">' + Number(row.line_amount || 0).toFixed(2) + '</td>'
                        + '</tr>';
                });
                previewBody.innerHTML = html;
            }
        }
    }

    function getGroupMode() {
        var el = document.getElementById('med_pool_group_by');
        return el ? String(el.value || 'none') : 'none';
    }

    function updateMasterCheckboxForPage() {
        var master = document.getElementById('med_pool_master');
        if (!master || !poolTable) {
            return;
        }
        var pageRows = poolTable.rows({ page: 'current' }).data().toArray();
        if (!pageRows.length) {
            master.checked = false;
            return;
        }
        var allSelected = pageRows.every(function (row) {
            return !!selectedMap[getRowKey(row)];
        });
        master.checked = allSelected;
    }

    function loadPool() {
        if (!poolTable) {
            return;
        }

        if (poolTable.ajax && typeof poolTable.ajax.reload === 'function') {
            poolTable.ajax.reload(null, false);
            return;
        }

        if (typeof poolTable.api === 'function') {
            var api = poolTable.api();
            if (api && api.ajax && typeof api.ajax.reload === 'function') {
                api.ajax.reload(null, false);
                return;
            }
            if (api && typeof api.draw === 'function') {
                api.draw(false);
                return;
            }
        }

        if (typeof poolTable.draw === 'function') {
            poolTable.draw(false);
            return;
        }

        if (typeof poolTable.fnDraw === 'function') {
            poolTable.fnDraw(false);
            return;
        }

        showAlert('Unable to refresh credit entry table on this DataTable version.', false);
    }

    function applyPoolFilter() {
        clearSelectedRows();
        loadPool();
    }

    function syncScopeWithGroupMode() {
        var scopeEl = document.getElementById('med_pool_scope');
        var groupEl = document.getElementById('med_pool_group_by');
        if (!scopeEl || !groupEl) {
            return;
        }

        if (groupEl.value === 'ipd') {
            scopeEl.value = 'ipd';
            scopeEl.setAttribute('disabled', 'disabled');
        } else if (groupEl.value === 'case') {
            scopeEl.value = 'org';
            scopeEl.setAttribute('disabled', 'disabled');
        } else {
            scopeEl.removeAttribute('disabled');
        }
    }

    function initPoolTable() {
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.DataTable === 'undefined') {
            showAlert('DataTable dependency missing. Unable to load credit entries.', false);
            return;
        }

        poolTable = window.jQuery('#med_credit_pool_table').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            pageLength: 25,
            order: [[5, 'desc']],
            ajax: {
                url: BASE + 'Medical/credit_payout_pool_datatable',
                type: 'POST',
                data: function (d) {
                    d.scope = document.getElementById('med_pool_scope') ? document.getElementById('med_pool_scope').value : 'all';
                    d.from_date = document.getElementById('med_pool_from_date') ? document.getElementById('med_pool_from_date').value : '';
                    d.to_date = document.getElementById('med_pool_to_date') ? document.getElementById('med_pool_to_date').value : '';
                    d.group_by = document.getElementById('med_pool_group_by') ? document.getElementById('med_pool_group_by').value : 'none';
                },
                error: function () {
                    showAlert('Unable to load credit entry pool.', false);
                }
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: function (data, type, row) {
                        if (getGroupMode() === 'ipd') {
                            return '';
                        }
                        var key = getRowKey(row);
                        poolRowCache[key] = row;
                        var checked = selectedMap[key] ? ' checked' : '';
                        return '<input type="checkbox" class="med-pool-check" data-key="' + key + '"' + checked + '>';
                    }
                },
                {
                    data: null,
                    render: function (data, type, row) {
                        return String(row.source_type || '') + '#' + Number(row.source_ref_id || 0);
                    }
                },
                { data: 'ipd_code', defaultContent: '' },
                { data: 'case_code', defaultContent: '' },
                { data: 'credit_category', defaultContent: '' },
                { data: 'inv_date', defaultContent: '' },
                {
                    data: 'line_amount',
                    className: 'text-end',
                    render: function (v) {
                        return Number(v || 0).toFixed(2);
                    }
                }
            ],
            drawCallback: function () {
                renderGroupRows();
                updateMasterCheckboxForPage();
                refreshSelectionSummary();
            },
            language: {
                emptyTable: 'No eligible credit entries found.'
            }
        });

        window.jQuery('#med_credit_pool_table tbody').on('change', '.med-pool-check', function () {
            var key = this.getAttribute('data-key') || '';
            if (!key) {
                return;
            }
            if (this.checked) {
                selectedMap[key] = poolRowCache[key] || selectedMap[key] || {};
            } else {
                delete selectedMap[key];
            }
            updateMasterCheckboxForPage();
            refreshSelectionSummary();
        });

        refreshSelectionSummary();
    }

    function renderGroupRows() {
        if (!poolTable || typeof window.jQuery === 'undefined') {
            return;
        }
        var groupMode = document.getElementById('med_pool_group_by') ? document.getElementById('med_pool_group_by').value : 'none';
        var body = window.jQuery('#med_credit_pool_table tbody');
        body.find('tr.med-group-row').remove();
        if (groupMode === 'none') {
            return;
        }

        var rows = poolTable.rows({ page: 'current' }).data().toArray();
        var domRows = body.find('tr');
        var lastGroup = null;
        rows.forEach(function (row, i) {
            var val = groupMode === 'ipd' ? String(row.ipd_code || '').trim() : String(row.case_code || '').trim();
            var groupValue = val !== '' ? val : 'Unmapped';
            if (groupValue !== lastGroup) {
                var groupRows = rows.filter(function (r) {
                    var rv = groupMode === 'ipd' ? String(r.ipd_code || '').trim() : String(r.case_code || '').trim();
                    return (rv !== '' ? rv : 'Unmapped') === groupValue;
                });
                var groupTotal = 0;
                groupRows.forEach(function (r) { groupTotal += Number(r.line_amount || 0); });

                var title = (groupMode === 'ipd' ? 'IPD: ' : 'Case: ') + groupValue + ' | Total: ' + groupTotal.toFixed(2);
                if (groupMode === 'ipd') {
                    var allSelected = groupRows.length > 0 && groupRows.every(function (r) {
                        return !!selectedMap[getRowKey(r)];
                    });
                    var checked = allSelected ? ' checked' : '';
                    title = '<input type="checkbox" class="form-check-input me-2 med-ipd-group-check" data-ipd="' + groupValue + '"' + checked + '> ' + title;
                }
                var header = window.jQuery('<tr class="med-group-row table-secondary"><td colspan="7" class="fw-semibold small">' + title + '</td></tr>');
                window.jQuery(domRows[i]).before(header);
                lastGroup = groupValue;
            }
        });
    }

    function loadHistory() {
        if (!historyTable) {
            return;
        }
        historyTable.ajax.reload(null, false);
    }

    function initHistoryTable() {
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.DataTable === 'undefined') {
            showAlert('DataTable dependency missing. Unable to load request history.', false);
            return;
        }

        historyTable = window.jQuery('#med_request_history_table').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            autoWidth: false,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            pageLength: 25,
            order: [[0, 'desc']],
            ajax: {
                url: BASE + 'Medical/credit_payout_requests_datatable',
                type: 'POST',
                dataSrc: function (json) {
                    refreshStatusDashboard(json && json.status_summary ? json.status_summary : {});
                    return (json && Array.isArray(json.data)) ? json.data : [];
                },
                error: function () {
                    refreshStatusDashboard({});
                    showAlert('Unable to load request history.', false);
                }
            },
            columns: [
                { data: 'row_no', orderable: false, searchable: false },
                {
                    data: null,
                    render: function (data, type, row) {
                        return '<strong>' + String(row.request_no || '') + '</strong><br><small class="text-muted">ID: ' + Number(row.id || 0) + '</small>';
                    }
                },
                { data: 'request_date', defaultContent: '' },
                {
                    data: 'status',
                    render: function (v) {
                        var status = String(v || '');
                        return '<span class="badge ' + getStatusBadgeClass(status) + '">' + status + '</span>';
                    }
                },
                {
                    data: 'requested_amount',
                    className: 'text-end',
                    render: function (v) {
                        return Number(v || 0).toFixed(2);
                    }
                },
                {
                    data: 'paid_amount',
                    className: 'text-end',
                    render: function (v) {
                        return Number(v || 0).toFixed(2);
                    }
                },
                {
                    data: 'pending_amount',
                    className: 'text-end',
                    render: function (v) {
                        return Number(v || 0).toFixed(2);
                    }
                }
            ],
            language: {
                emptyTable: 'No payout requests yet.'
            }
        });

        var historyTab = document.getElementById('med-credit-history-tab');
        if (historyTab) {
            historyTab.addEventListener('shown.bs.tab', function () {
                if (historyTable && historyTable.columns) {
                    historyTable.columns.adjust().draw(false);
                }
            });
        }
    }

    var form = document.getElementById('med_credit_request_form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var rows = selectedRows();
            if (!rows.length) {
                showAlert('Select at least one credit entry.', false);
                return;
            }
            var fd = new window.FormData(form);
            fd.append('line_items', JSON.stringify(rows));

            fetch(BASE + 'Medical/credit_payout_request_create', {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: fd
            }).then(function (res) {
                return res.json().then(function (data) { return { ok: res.ok, data: data }; });
            }).then(function (result) {
                if (!result.ok || !result.data || result.data.status !== 1) {
                    showAlert((result.data && result.data.message) ? result.data.message : 'Request submission failed.', false);
                    return;
                }
                showAlert(result.data.message || 'Request created successfully.', true);
                form.reset();
                clearSelectedRows();
                loadPool();
                loadHistory();
            }).catch(function () {
                showAlert('Network or server error.', false);
            });
        });
    }

    var btnPoolRefresh = document.getElementById('btn_med_pool_refresh');
    if (btnPoolRefresh) btnPoolRefresh.addEventListener('click', loadPool);

    var btnHistRefresh = document.getElementById('btn_med_history_refresh');
    if (btnHistRefresh) btnHistRefresh.addEventListener('click', loadHistory);

    var masterCb = document.getElementById('med_pool_master');
    if (masterCb) {
        masterCb.addEventListener('change', function () {
            if (!poolTable) {
                return;
            }
            var pageRows = poolTable.rows({ page: 'current' }).data().toArray();
            pageRows.forEach(function (row) {
                var key = getRowKey(row);
                if (masterCb.checked) {
                    selectedMap[key] = row;
                } else {
                    delete selectedMap[key];
                }
            });
            if (getGroupMode() === 'ipd') {
                window.jQuery('#med_credit_pool_table tbody .med-ipd-group-check').prop('checked', masterCb.checked);
            } else {
                window.jQuery('#med_credit_pool_table tbody .med-pool-check').prop('checked', masterCb.checked);
            }
            refreshSelectionSummary();
        });
    }

    var btnSelectAll = document.getElementById('btn_med_pool_select_all');
    if (btnSelectAll) {
        btnSelectAll.addEventListener('click', function () {
            if (!poolTable) {
                return;
            }
            var pageRows = poolTable.rows({ page: 'current' }).data().toArray();
            var anyUnchecked = pageRows.some(function (row) {
                return !selectedMap[getRowKey(row)];
            });
            pageRows.forEach(function (row) {
                var key = getRowKey(row);
                if (anyUnchecked) {
                    selectedMap[key] = row;
                } else {
                    delete selectedMap[key];
                }
            });
            if (getGroupMode() === 'ipd') {
                window.jQuery('#med_credit_pool_table tbody .med-ipd-group-check').prop('checked', anyUnchecked);
            } else {
                window.jQuery('#med_credit_pool_table tbody .med-pool-check').prop('checked', anyUnchecked);
            }
            if (masterCb) masterCb.checked = anyUnchecked;
            refreshSelectionSummary();
        });
    }

    if (typeof window.jQuery !== 'undefined') {
        window.jQuery('#med_credit_pool_table tbody').on('change', '.med-ipd-group-check', function () {
            if (!poolTable) {
                return;
            }
            var ipd = String(this.getAttribute('data-ipd') || '').trim();
            if (!ipd) {
                return;
            }

            var pageRows = poolTable.rows({ page: 'current' }).data().toArray();
            pageRows.forEach(function (row) {
                var rowIpd = String(row.ipd_code || '').trim();
                var rowGroup = rowIpd !== '' ? rowIpd : 'Unmapped';
                if (rowGroup !== ipd) {
                    return;
                }
                var key = getRowKey(row);
                if (this.checked) {
                    selectedMap[key] = row;
                } else {
                    delete selectedMap[key];
                }
            }, this);

            updateMasterCheckboxForPage();
            refreshSelectionSummary();
        });
    }

    var btnApplyFilter = document.getElementById('btn_med_pool_apply_filter');
    if (btnApplyFilter) {
        btnApplyFilter.addEventListener('click', applyPoolFilter);
    }

    var groupModeEl = document.getElementById('med_pool_group_by');
    if (groupModeEl) {
        groupModeEl.addEventListener('change', function () {
            syncScopeWithGroupMode();
            applyPoolFilter();
        });
    }

    var filterIds = ['med_pool_scope', 'med_pool_from_date', 'med_pool_to_date'];
    filterIds.forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('change', applyPoolFilter);
    });

    syncScopeWithGroupMode();
    initPoolTable();
    initHistoryTable();
    loadHistory();
})();
</script>

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
                            <label class="form-label mb-1">Selected Lines</label>
                            <input type="text" class="form-control" id="med_selected_count" value="0" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-1">Total Amount</label>
                            <input type="text" class="form-control" id="med_selected_total" value="0.00" readonly>
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
                    <div class="small text-muted">Request history is shown below. Use Finance panel for approval and payment settlement.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
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
                    <tbody>
                        <?php if (empty($rows ?? [])): ?>
                            <tr><td colspan="7" class="text-center text-muted py-3">No payout requests yet.</td></tr>
                        <?php else: ?>
                            <?php foreach (($rows ?? []) as $i => $row): ?>
                                <tr>
                                    <td><?= (int) $i + 1 ?></td>
                                    <td><strong><?= esc((string) ($row['request_no'] ?? '')) ?></strong><br><small class="text-muted">ID: <?= (int) ($row['id'] ?? 0) ?></small></td>
                                    <td><?= esc((string) ($row['request_date'] ?? '')) ?></td>
                                    <td><span class="badge bg-secondary"><?= esc((string) ($row['status'] ?? '')) ?></span></td>
                                    <td class="text-end"><?= esc(number_format((float) ($row['requested_amount'] ?? 0), 2)) ?></td>
                                    <td class="text-end"><?= esc(number_format((float) ($row['paid_amount'] ?? 0), 2)) ?></td>
                                    <td class="text-end"><?= esc(number_format((float) ($row['pending_amount'] ?? 0), 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var BASE = '<?= base_url() ?>';
    var poolTable = null;
    var selectedMap = {};
    var poolRowCache = {};

    function showAlert(msg, ok) {
        var box = document.getElementById('med_credit_req_alert');
        if (!box) return;
        box.innerHTML = '<div class="alert ' + (ok ? 'alert-success' : 'alert-danger') + ' alert-dismissible fade show" role="alert">'
            + msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
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
        fetch(BASE + 'Medical/credit_payout_requests', {
            method: 'GET',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(function (res) {
            return res.json().then(function (data) { return { ok: res.ok, data: data }; });
        }).then(function (result) {
            var table = document.querySelector('#med_request_history_table tbody');
            if (!table) return;
            if (!result.ok || !result.data || result.data.status !== 1) {
                table.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">Unable to load request history.</td></tr>';
                return;
            }
            var rows = result.data.rows || [];
            if (!rows.length) {
                table.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">No payout requests yet.</td></tr>';
                return;
            }
            var html = '';
            rows.forEach(function (row, i) {
                html += '<tr>'
                    + '<td>' + (i + 1) + '</td>'
                    + '<td><strong>' + (row.request_no || '') + '</strong><br><small class="text-muted">ID: ' + Number(row.id || 0) + '</small></td>'
                    + '<td>' + (row.request_date || '') + '</td>'
                    + '<td><span class="badge bg-secondary">' + (row.status || '') + '</span></td>'
                    + '<td class="text-end">' + Number(row.requested_amount || 0).toFixed(2) + '</td>'
                    + '<td class="text-end">' + Number(row.paid_amount || 0).toFixed(2) + '</td>'
                    + '<td class="text-end">' + Number(row.pending_amount || 0).toFixed(2) + '</td>'
                    + '</tr>';
            });
            table.innerHTML = html;
        });
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
    loadHistory();
})();
</script>

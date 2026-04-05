<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ABDM Work Task Board</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .container-wrap { max-width: 1280px; margin: 24px auto; }
        .table td, .table th { vertical-align: middle; }
        .status-pill { text-transform: uppercase; font-size: 11px; letter-spacing: .4px; }
    </style>
</head>
<body>
<div class="container-wrap px-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">ABDM Work Task Board</h4>
            <div class="small text-muted">Unupdated ABHA Patient List + Separate ABDM Task Queues</div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnRefresh">Refresh</button>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3" id="taskFilters">
        <button class="btn btn-sm btn-primary filter-btn" data-filter="all">All</button>
        <button class="btn btn-sm btn-outline-primary filter-btn" data-filter="patient_abha_create">Unupdated ABHA</button>
        <button class="btn btn-sm btn-outline-primary filter-btn" data-filter="patient_abha_update">ABHA Update</button>
        <button class="btn btn-sm btn-outline-primary filter-btn" data-filter="opd_prescription_publish">OPD Publish</button>
        <button class="btn btn-sm btn-outline-primary filter-btn" data-filter="lab_report_publish">Lab Reports</button>
        <button class="btn btn-sm btn-outline-primary filter-btn" data-filter="radiology_report_publish">Radiology Reports</button>
        <button class="btn btn-sm btn-outline-primary filter-btn" data-filter="ipd_admission_publish">IPD Admission</button>
        <button class="btn btn-sm btn-outline-primary filter-btn" data-filter="ipd_discharge_publish">IPD Discharge</button>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0" id="taskTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Task</th>
                            <th>Patient</th>
                            <th>ABHA</th>
                            <th>Entity</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (($tasks ?? []) as $t): ?>
                        <tr data-task-id="<?= (int) ($t['id'] ?? 0) ?>" data-task-type="<?= esc((string) ($t['task_type'] ?? '')) ?>" data-entity-id="<?= esc((string) ($t['entity_id'] ?? '')) ?>">
                            <td><?= (int) ($t['id'] ?? 0) ?></td>
                            <td>
                                <div><strong><?= esc((string) ($t['task_code'] ?? '')) ?></strong></div>
                                <div class="text-muted small"><?= esc((string) ($t['task_type'] ?? '')) ?></div>
                            </td>
                            <td>
                                <div><?= esc((string) ($t['patient_name'] ?? '')) ?></div>
                                <div class="text-muted small">#<?= (int) ($t['patient_id'] ?? 0) ?></div>
                            </td>
                            <td><input type="text" class="form-control form-control-sm abha-input" value="<?= esc((string) ($t['abha_id'] ?? '')) ?>" placeholder="14-digit ABHA"></td>
                            <td>
                                <div><?= esc((string) ($t['entity_type'] ?? '')) ?></div>
                                <div class="text-muted small"><?= esc((string) ($t['entity_id'] ?? '')) ?></div>
                            </td>
                            <td><span class="badge bg-secondary status-pill"><?= esc((string) ($t['status'] ?? 'pending')) ?></span></td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <?php $type = strtolower((string) ($t['task_type'] ?? '')); ?>
                                    <?php if ($type === 'patient_abha_create' || $type === 'patient_abha_link'): ?>
                                        <button class="btn btn-sm btn-outline-success action-btn" data-action="create_abha">Create ABHA</button>
                                    <?php elseif ($type === 'patient_abha_update'): ?>
                                        <button class="btn btn-sm btn-outline-primary action-btn" data-action="update_abha">Update ABHA</button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-warning action-btn" data-action="submit">Submit</button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-dark close-btn">Close</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="small text-muted mt-2" id="statusBox">Ready</div>
</div>

<div class="modal fade" id="taskActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ABDM Guided Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2 small text-muted" id="modalTaskSummary"></div>
                <label class="form-label">ABHA Number</label>
                <input type="text" class="form-control" id="modalAbhaInput" maxlength="14" placeholder="Enter 14-digit ABHA">
                <div class="form-text">ABHA validation is required before queueing the ABDM API action.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="modalConfirmBtn">Queue Action</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    var csrfName = '<?= csrf_token() ?>';
    var csrfHash = '<?= csrf_hash() ?>';
    var statusBox = document.getElementById('statusBox');
    var modalEl = document.getElementById('taskActionModal');
    var actionModal = new bootstrap.Modal(modalEl);
    var modalAbhaInput = document.getElementById('modalAbhaInput');
    var modalTaskSummary = document.getElementById('modalTaskSummary');
    var modalConfirmBtn = document.getElementById('modalConfirmBtn');
    var selectedAction = '';
    var selectedRow = null;

    function applyFilter(filter) {
        var rows = document.querySelectorAll('#taskTable tbody tr');
        rows.forEach(function (row) {
            var type = (row.getAttribute('data-task-type') || '').toLowerCase();
            row.style.display = (filter === 'all' || type === filter.toLowerCase()) ? '' : 'none';
        });

        document.querySelectorAll('.filter-btn').forEach(function (btn) {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        });
        var active = document.querySelector('.filter-btn[data-filter="' + filter + '"]');
        if (active) {
            active.classList.remove('btn-outline-primary');
            active.classList.add('btn-primary');
        }
    }

    function setStatus(msg, isError) {
        statusBox.textContent = msg;
        statusBox.style.color = isError ? '#dc3545' : '#6c757d';
    }

    function post(url, payload, cb) {
        payload[csrfName] = csrfHash;
        var body = Object.keys(payload).map(function (key) {
            return encodeURIComponent(key) + '=' + encodeURIComponent(payload[key]);
        }).join('&');
        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: body
        }).then(function (r) {
            return r.json();
        }).then(function (json) {
            if (json && json.csrfHash) {
                csrfHash = json.csrfHash;
            }
            cb(json || {});
        }).catch(function (e) {
            setStatus('Request failed: ' + e.message, true);
        });
    }

    function runAction(row, action) {
        var taskId = row.getAttribute('data-task-id');
        var abha = modalAbhaInput.value.trim();

        setStatus('Submitting action...');
        post('<?= base_url('AbdmTaskBoard/perform_action') ?>', {
            task_id: taskId,
            action: action,
            abha_id: abha,
            opd_session_id: ''
        }, function (res) {
            if (res.ok !== 1) {
                setStatus(res.error_text || 'Action failed', true);
                return;
            }

            var badge = row.querySelector('.status-pill');
            if (badge) {
                badge.textContent = 'IN_PROGRESS';
                badge.className = 'badge bg-info status-pill';
            }
            setStatus('Action queued: ' + (res.event_type || '-'));
            actionModal.hide();
        });
    }

    document.querySelectorAll('.action-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            selectedRow = btn.closest('tr');
            if (!selectedRow) {
                return;
            }
            selectedAction = btn.getAttribute('data-action') || 'submit';

            var abhaInput = selectedRow.querySelector('.abha-input');
            var taskType = selectedRow.getAttribute('data-task-type') || '';
            modalAbhaInput.value = abhaInput ? abhaInput.value.trim() : '';
            modalTaskSummary.textContent = 'Task: ' + taskType + ' | Action: ' + selectedAction.toUpperCase();
            actionModal.show();
        });
    });

    document.querySelectorAll('.filter-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyFilter(btn.getAttribute('data-filter') || 'all');
        });
    });

    applyFilter('patient_abha_create');

    modalConfirmBtn.addEventListener('click', function () {
        if (!selectedRow || !selectedAction) {
            return;
        }
        var abha = modalAbhaInput.value.trim();
        if (!/^\d{14}$/.test(abha)) {
            setStatus('ABHA must be a 14-digit number.', true);
            return;
        }
        var rowInput = selectedRow.querySelector('.abha-input');
        if (rowInput) {
            rowInput.value = abha;
        }
        runAction(selectedRow, selectedAction);
    });

    document.querySelectorAll('.close-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.closest('tr');
            if (!row) {
                return;
            }
            var taskId = row.getAttribute('data-task-id');
            post('<?= base_url('AbdmTaskBoard/mark_status') ?>', {
                task_id: taskId,
                status: 'completed',
                note: 'Closed from task board'
            }, function (res) {
                if (res.ok !== 1) {
                    setStatus(res.error_text || 'Close failed', true);
                    return;
                }
                row.remove();
                setStatus('Task closed');
            });
        });
    });

    document.getElementById('btnRefresh').addEventListener('click', function () {
        window.location.reload();
    });
})();
</script>
</body>
</html>

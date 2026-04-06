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
                        <?php
                            $type = strtolower((string) ($t['task_type'] ?? ''));
                            $abhaId = trim((string) ($t['abha_id'] ?? ''));
                            $hasValidAbha = preg_match('/^\d{14}$/', $abhaId) === 1;

                            $payload = json_decode((string) ($t['payload_json'] ?? ''), true);
                            if (! is_array($payload)) {
                                $payload = [];
                            }
                            $meta = (isset($payload['meta']) && is_array($payload['meta'])) ? $payload['meta'] : [];
                            $opdId = (int) ($meta['opd_id'] ?? 0);
                            $opdSessionId = (int) ($meta['opd_session_id'] ?? 0);
                            $showSandbox = ($type === 'opd_prescription_publish' && $hasValidAbha && $opdId > 0);
                        ?>
                        <tr
                            data-task-id="<?= (int) ($t['id'] ?? 0) ?>"
                            data-task-type="<?= esc((string) ($t['task_type'] ?? '')) ?>"
                            data-entity-id="<?= esc((string) ($t['entity_id'] ?? '')) ?>"
                            data-patient-id="<?= (int) ($t['patient_id'] ?? 0) ?>"
                            data-abha-id="<?= esc($abhaId) ?>"
                            data-opd-id="<?= $opdId ?>"
                            data-opd-session-id="<?= $opdSessionId ?>"
                            data-sandbox-eligible="<?= $showSandbox ? '1' : '0' ?>"
                        >
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
                                    <?php if ($type === 'patient_abha_create' || $type === 'patient_abha_link'): ?>
                                        <button class="btn btn-sm btn-outline-success action-btn" data-action="create_abha">Create ABHA</button>
                                    <?php elseif ($type === 'patient_abha_update'): ?>
                                        <button class="btn btn-sm btn-outline-primary action-btn" data-action="update_abha">Update ABHA</button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-warning action-btn" data-action="submit">Submit</button>
                                    <?php endif; ?>
                                    <?php if ($showSandbox): ?>
                                        <button class="btn btn-sm btn-outline-info sandbox-btn">Sandbox</button>
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

    <div class="card shadow-sm mt-3 d-none" id="abdmSandboxCard">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong>ABDM Sandbox Actions</strong>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSandboxHide">Hide</button>
        </div>
        <div class="card-body p-3">
            <div class="small text-muted mb-2" id="abdmSandboxContext">Select eligible OPD Publish task to use sandbox actions.</div>

            <div class="row g-2 mb-2">
                <div class="col-md-6">
                    <label class="form-label mb-1 small">ABHA Number</label>
                    <input type="text" class="form-control form-control-sm" id="tb_abha_id" maxlength="14" placeholder="14-digit ABHA">
                </div>
                <div class="col-md-6">
                    <label class="form-label mb-1 small">QR Payload (Scan &amp; Share)</label>
                    <input type="text" class="form-control form-control-sm" id="tb_abdm_qr_payload" placeholder="Paste scanned QR payload">
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-2">
                <button type="button" class="btn btn-outline-primary btn-sm" id="tb_btn_abdm_scan_lookup">Scan Lookup</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="tb_btn_abdm_validate_abha">Validate ABHA</button>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-md-6">
                    <input type="text" class="form-control form-control-sm" id="tb_abdm_purpose_code" placeholder="Purpose (ex: CAREMGT)">
                </div>
                <div class="col-md-6">
                    <input type="datetime-local" class="form-control form-control-sm" id="tb_abdm_consent_expires_at">
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-2">
                <button type="button" class="btn btn-outline-success btn-sm" id="tb_btn_abdm_consent_request">Request Consent</button>
                <button type="button" class="btn btn-outline-warning btn-sm" id="tb_btn_abdm_share_fhir">Share FHIR</button>
            </div>

            <div class="mb-2">
                <label class="form-label mb-1 small">Consent Handle</label>
                <input type="text" class="form-control form-control-sm" id="tb_abdm_consent_handle" placeholder="Auto-filled after consent request">
            </div>

            <div class="row g-2 mb-2">
                <div class="col-md-7">
                    <input type="number" min="1" class="form-control form-control-sm" id="tb_abdm_claim_document_id" placeholder="Claim Document ID">
                </div>
                <div class="col-md-5">
                    <button type="button" class="btn btn-outline-dark btn-sm w-100" id="tb_btn_abdm_claim_status">Claim Status</button>
                </div>
            </div>

            <div class="small text-muted" id="abdmSandboxStatus">Ready</div>
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
    var sandboxCard = document.getElementById('abdmSandboxCard');
    var sandboxContext = document.getElementById('abdmSandboxContext');
    var sandboxStatus = document.getElementById('abdmSandboxStatus');
    var sandboxAbhaInput = document.getElementById('tb_abha_id');
    var sandboxQrPayloadInput = document.getElementById('tb_abdm_qr_payload');
    var sandboxPurposeCodeInput = document.getElementById('tb_abdm_purpose_code');
    var sandboxConsentExpiresInput = document.getElementById('tb_abdm_consent_expires_at');
    var sandboxConsentHandleInput = document.getElementById('tb_abdm_consent_handle');
    var sandboxClaimDocumentInput = document.getElementById('tb_abdm_claim_document_id');
    var selectedAction = '';
    var selectedRow = null;
    var sandboxRow = null;

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

    function setSandboxStatus(msg, level) {
        if (!sandboxStatus) {
            return;
        }
        sandboxStatus.classList.remove('text-muted', 'text-success', 'text-danger', 'text-warning');
        if (level === 'success') {
            sandboxStatus.classList.add('text-success');
        } else if (level === 'danger') {
            sandboxStatus.classList.add('text-danger');
        } else if (level === 'warning') {
            sandboxStatus.classList.add('text-warning');
        } else {
            sandboxStatus.classList.add('text-muted');
        }
        sandboxStatus.textContent = msg || 'Ready';
    }

    function currentSandboxContext() {
        if (!sandboxRow) {
            return null;
        }

        return {
            taskId: parseInt(sandboxRow.getAttribute('data-task-id') || '0', 10) || 0,
            patientId: parseInt(sandboxRow.getAttribute('data-patient-id') || '0', 10) || 0,
            opdId: parseInt(sandboxRow.getAttribute('data-opd-id') || '0', 10) || 0,
            opdSessionId: parseInt(sandboxRow.getAttribute('data-opd-session-id') || '0', 10) || 0,
            abhaId: (sandboxAbhaInput.value || '').trim()
        };
    }

    function openSandboxForRow(row) {
        if (!row || !sandboxCard) {
            return;
        }
        if ((row.getAttribute('data-sandbox-eligible') || '0') !== '1') {
            setSandboxStatus('Sandbox actions are available only for OPD Publish rows with valid ABHA and printed consult.', 'warning');
            return;
        }

        sandboxRow = row;

        var taskCode = '';
        var patientName = '';
        var taskCodeEl = row.querySelector('td:nth-child(2) strong');
        var patientNameEl = row.querySelector('td:nth-child(3) div');
        if (taskCodeEl) {
            taskCode = taskCodeEl.textContent.trim();
        }
        if (patientNameEl) {
            patientName = patientNameEl.textContent.trim();
        }

        var rowAbha = (row.getAttribute('data-abha-id') || '').trim();
        sandboxAbhaInput.value = rowAbha;
        sandboxQrPayloadInput.value = '';
        sandboxPurposeCodeInput.value = sandboxPurposeCodeInput.value || 'CAREMGT';
        sandboxConsentHandleInput.value = '';
        sandboxClaimDocumentInput.value = '';

        var opdId = row.getAttribute('data-opd-id') || '0';
        var opdSessionId = row.getAttribute('data-opd-session-id') || '0';
        sandboxContext.textContent = 'Task: ' + taskCode + ' | Patient: ' + patientName + ' | OPD: ' + opdId + ' | Session: ' + opdSessionId;
        sandboxCard.classList.remove('d-none');
        setSandboxStatus('Ready');
        sandboxCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
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

    document.querySelectorAll('.sandbox-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openSandboxForRow(btn.closest('tr'));
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
                if (sandboxRow === row && sandboxCard) {
                    sandboxCard.classList.add('d-none');
                    sandboxRow = null;
                }
                row.remove();
                setStatus('Task closed');
            });
        });
    });

    document.getElementById('btnSandboxHide').addEventListener('click', function () {
        if (sandboxCard) {
            sandboxCard.classList.add('d-none');
        }
        sandboxRow = null;
    });

    document.getElementById('tb_btn_abdm_scan_lookup').addEventListener('click', function () {
        var qrPayload = (sandboxQrPayloadInput.value || '').trim();
        if (!qrPayload) {
            setSandboxStatus('Paste QR payload first.', 'warning');
            return;
        }
        setSandboxStatus('Queuing Scan & Share lookup...');
        post('<?= base_url('AbdmGateway/scan_share_lookup') ?>', {
            qr_payload: qrPayload
        }, function (res) {
            if (res.ok !== 1) {
                setSandboxStatus(res.error_text || 'Scan lookup failed.', 'danger');
                return;
            }
            if (res.abha_id_hint) {
                sandboxAbhaInput.value = (res.abha_id_hint || '').toString().trim();
            }
            setSandboxStatus('Scan lookup queued. Queue ID: ' + (res.queue_id || '-'), 'success');
        });
    });

    document.getElementById('tb_btn_abdm_validate_abha').addEventListener('click', function () {
        var abha = (sandboxAbhaInput.value || '').trim();
        if (!/^\d{14}$/.test(abha)) {
            setSandboxStatus('ABHA must be a 14-digit number.', 'warning');
            return;
        }
        setSandboxStatus('Queuing ABHA validation...');
        post('<?= base_url('AbdmGateway/abha_validate') ?>', {
            abha_id: abha
        }, function (res) {
            if (res.ok !== 1) {
                setSandboxStatus(res.error_text || 'ABHA validation queue failed.', 'danger');
                return;
            }
            setSandboxStatus('ABHA validation queued. Queue ID: ' + (res.queue_id || '-'), 'success');
        });
    });

    document.getElementById('tb_btn_abdm_consent_request').addEventListener('click', function () {
        var ctx = currentSandboxContext();
        if (!ctx || ctx.patientId <= 0) {
            setSandboxStatus('Select an eligible OPD Publish row first.', 'warning');
            return;
        }
        if (!/^\d{14}$/.test(ctx.abhaId)) {
            setSandboxStatus('ABHA must be a 14-digit number.', 'warning');
            return;
        }

        setSandboxStatus('Requesting consent...');
        post('<?= base_url('AbdmGateway/consent_request') ?>', {
            patient_id: ctx.patientId,
            abha_id: ctx.abhaId,
            purpose_code: (sandboxPurposeCodeInput.value || '').trim(),
            expires_at: (sandboxConsentExpiresInput.value || '').trim().replace('T', ' ')
        }, function (res) {
            if (res.ok !== 1) {
                setSandboxStatus(res.error_text || 'Consent request failed.', 'danger');
                return;
            }
            if (res.consent_handle) {
                sandboxConsentHandleInput.value = (res.consent_handle || '').toString();
            }
            setSandboxStatus('Consent requested. Handle: ' + (res.consent_handle || '-'), 'success');
        });
    });

    document.getElementById('tb_btn_abdm_share_fhir').addEventListener('click', function () {
        var ctx = currentSandboxContext();
        if (!ctx || ctx.patientId <= 0 || ctx.opdId <= 0) {
            setSandboxStatus('Select an eligible OPD Publish row first.', 'warning');
            return;
        }
        if (!/^\d{14}$/.test(ctx.abhaId)) {
            setSandboxStatus('ABHA must be a 14-digit number.', 'warning');
            return;
        }

        setSandboxStatus('Checking consent and queuing FHIR share...');
        post('<?= base_url('AbdmGateway/share_prescription_bundle') ?>', {
            opd_id: ctx.opdId,
            opd_session_id: ctx.opdSessionId,
            patient_id: ctx.patientId,
            abha_id: ctx.abhaId,
            consent_handle: (sandboxConsentHandleInput.value || '').trim()
        }, function (res) {
            if (res.ok !== 1) {
                setSandboxStatus(res.error_text || 'FHIR share queue failed.', 'danger');
                return;
            }
            if (res.consent_handle) {
                sandboxConsentHandleInput.value = (res.consent_handle || '').toString();
            }
            setSandboxStatus('FHIR share queued. Queue ID: ' + (res.queue_id || '-'), 'success');
        });
    });

    document.getElementById('tb_btn_abdm_claim_status').addEventListener('click', function () {
        var docId = parseInt((sandboxClaimDocumentInput.value || '0').toString(), 10) || 0;
        if (docId <= 0) {
            setSandboxStatus('Enter Claim Document ID first.', 'warning');
            return;
        }

        setSandboxStatus('Queuing claim status request...');
        post('<?= base_url('AbdmGateway/nhcx_claim_status_request') ?>', {
            document_id: docId
        }, function (res) {
            if (res.ok !== 1) {
                setSandboxStatus(res.error_text || 'Claim status queue failed.', 'danger');
                return;
            }
            setSandboxStatus('Claim status queued. Queue ID: ' + (res.queue_id || '-'), 'success');
        });
    });

    document.getElementById('btnRefresh').addEventListener('click', function () {
        window.location.reload();
    });
})();
</script>
</body>
</html>

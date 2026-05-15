<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OPD Queue — ABDM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .wrap { max-width: 1280px; margin: 20px auto; }
        .badge-source-scan  { background:#0d6efd; }
        .badge-source-manual{ background:#6c757d; }
        .token-num { font-size: 1.3rem; font-weight: 700; color: #0d6efd; }
        .status-PENDING   { background:#ffc107; color:#000; }
        .status-CALLED    { background:#0d6efd; color:#fff; }
        .status-COMPLETED { background:#198754; color:#fff; }
        .status-CANCELLED { background:#dc3545; color:#fff; }
        .auto-refresh-bar { height:3px; background:#0d6efd; transition: width linear 30s; }
    </style>
</head>
<body>
<div class="wrap px-3">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 pt-3">
        <div>
            <h4 class="mb-0">OPD Queue <span class="badge bg-primary ms-2">ABDM</span></h4>
            <div class="small text-muted">Scan &amp; Share arrivals + walk-in tokens — auto-refresh every 30 s</div>
        </div>
        <div class="d-flex gap-2">
            <input type="date" id="queueDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            <select id="queueStatus" class="form-select form-select-sm" style="width:140px">
                <option value="">All Status</option>
                <option value="PENDING" selected>Pending</option>
                <option value="CALLED">Called</option>
                <option value="COMPLETED">Completed</option>
                <option value="CANCELLED">Cancelled</option>
            </select>
            <button class="btn btn-sm btn-outline-primary" id="btnRefresh"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalAddToken">
                + Add Walk-in
            </button>
        </div>
    </div>

    <!-- Auto-refresh bar -->
    <div class="auto-refresh-bar w-100 mb-2 rounded" id="refreshBar"></div>

    <!-- Summary badges -->
    <div class="d-flex gap-3 mb-3" id="queueSummary">
        <span class="badge rounded-pill bg-secondary px-3 py-2">Loading…</span>
    </div>

    <!-- Queue table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Token</th>
                            <th>Name</th>
                            <th>ABHA</th>
                            <th>Dept</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>HMS Patient</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="queueBody">
                        <tr><td colspan="8" class="text-center py-4 text-muted">Loading queue…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- /wrap -->

<!-- ===== Add Walk-in Modal ===== -->
<div class="modal fade" id="modalAddToken" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Walk-in Token</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="addTokenAlert" class="alert alert-danger d-none py-2"></div>
                <div class="mb-3">
                    <label class="form-label">Patient Name <span class="text-danger">*</span></label>
                    <input type="text" id="at_name" class="form-control" placeholder="Full name">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Phone</label>
                        <input type="tel" id="at_phone" class="form-control" placeholder="10-digit mobile">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Gender</label>
                        <select id="at_gender" class="form-select">
                            <option value="M">Male</option>
                            <option value="F">Female</option>
                            <option value="O">Other</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">ABHA No (optional)</label>
                        <input type="text" id="at_abha" class="form-control" placeholder="14-digit">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Department</label>
                        <input type="text" id="at_dept" class="form-control" value="General OPD">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Date</label>
                    <input type="date" id="at_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success" id="btnAddTokenSubmit">Create Token</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== Process Scan&Share patient modal (auto-opened) ===== -->
<div class="modal fade" id="modalProcessToken" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Register Patient for OPD</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="processTokenBody">
                <p class="text-muted">Processing…</p>
            </div>
            <div class="modal-footer" id="processTokenFooter" style="display:none!important">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ===== OPD QUEUE BOARD ===== */
(function () {
    'use strict';

    const BASE      = '<?= base_url() ?>';
    const csrfName  = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    let autoRefreshTimer  = null;
    const REFRESH_SECS    = 30;

    // ---- helpers ----
    function post(url, data) {
        const fd = new FormData();
        Object.entries(data).forEach(([k, v]) => fd.append(k, v));
        const csrf = '<?= csrf_token() ?>';
        const hash = '<?= csrf_hash() ?>';
        fd.append(csrf, hash);
        return fetch(url, { method: 'POST', body: fd })
            .then(r => r.json());
    }

    function statusBadge(s) {
        return `<span class="badge status-${s} px-2 py-1 rounded-pill">${s}</span>`;
    }

    function sourceBadge(src) {
        if (src === 'scan_share') return '<span class="badge badge-source-scan px-2">ABHA Scan</span>';
        return '<span class="badge badge-source-manual px-2">Walk-in</span>';
    }

    function fmtTime(ts) {
        if (!ts) return '—';
        const d = new Date(ts);
        return d.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true });
    }

    // ---- fetch queue from HMS controller ----
    function loadQueue() {
        const date   = document.getElementById('queueDate').value;
        const status = document.getElementById('queueStatus').value;
        const url    = BASE + 'AbdmOpdQueue/fetch?date=' + encodeURIComponent(date)
                       + '&status=' + encodeURIComponent(status);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(renderQueue)
            .catch(err => {
                document.getElementById('queueBody').innerHTML =
                    `<tr><td colspan="8" class="text-danger text-center py-3">Failed to load queue: ${err.message}</td></tr>`;
            });

        resetRefreshBar();
    }

    function renderQueue(data) {
        const tokens = data.data ?? data.tokens ?? [];
        const body   = document.getElementById('queueBody');
        const summary= document.getElementById('queueSummary');

        // Summary counts
        const counts    = { PENDING: 0, CALLED: 0, COMPLETED: 0, CANCELLED: 0 };
        let opdDoneCount = 0;
        tokens.forEach(t => {
            if (counts[t.status] !== undefined) counts[t.status]++;
            if (t.hms_patient_id) opdDoneCount++;
        });
        summary.innerHTML = [
            `<span class="badge rounded-pill status-PENDING px-3 py-2">${counts.PENDING} Pending</span>`,
            `<span class="badge rounded-pill status-CALLED px-3 py-2">${counts.CALLED} Called</span>`,
            `<span class="badge rounded-pill status-COMPLETED px-3 py-2">${counts.COMPLETED} Completed</span>`,
            `<span class="badge rounded-pill status-CANCELLED px-3 py-2">${counts.CANCELLED} Cancelled</span>`,
            `<span class="badge rounded-pill bg-success px-3 py-2">✓ ${opdDoneCount} OPD Registered</span>`,
            `<span class="badge rounded-pill bg-secondary px-3 py-2">${tokens.length} Total</span>`,
        ].join('');

        if (tokens.length === 0) {
            body.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No tokens found for this filter.</td></tr>';
            return;
        }

        body.innerHTML = tokens.map(t => {
            const id       = t.id ?? 0;
            const isPend   = t.status === 'PENDING';
            const isCalled = t.status === 'CALLED';
            const isScan   = t.source === 'scan_share';
            const isLinked = !! t.hms_patient_id;   // patient already registered in HMS

            const abhaDisplay = t.abha_number
                ? `<div class="small text-primary">${fmtAbha(t.abha_number)}</div>`
                : (t.abha_address ? `<div class="small text-muted">${esc(t.abha_address)}</div>` : '<span class="text-muted">—</span>');

            // HMS patient column
            let hmsCell = '<span class="text-muted small">—</span>';
            if (isLinked) {
                const pLabel = esc((t.hms_p_code ?? '') + ' ' + (t.hms_p_name ?? ''));
                hmsCell = `<a href="javascript:load_form('${esc(t.hms_profile_url ?? '')}','Patient Profile')"
                              class="text-decoration-none">
                              <span class="badge bg-success-subtle text-success border border-success-subtle">✓ ${pLabel}</span>
                           </a>`;
                if (t.hms_opd_url && ! t.hms_opd_id) {
                    hmsCell += `<br><a href="${esc(t.hms_opd_url)}" class="small text-primary">+ Add OPD Visit</a>`;
                }
            }

            let actions = '';
            if (isPend) {
                actions += `<button class="btn btn-xs btn-sm btn-outline-primary me-1" onclick="callToken(${id})">Call</button>`;
            }
            if (isPend || isCalled) {
                actions += `<button class="btn btn-xs btn-sm btn-outline-success me-1" onclick="completeToken(${id})">Complete</button>`;
                actions += `<button class="btn btn-xs btn-sm btn-outline-danger me-1" onclick="cancelToken(${id})">Cancel</button>`;
            }
            // Show "Register OPD" for SCAN tokens not yet linked, OR any PENDING token without a patient
            if (! isLinked && (isPend || isCalled)) {
                const payload = JSON.stringify({
                    id: t.id, abha_number: t.abha_number ?? '', abha_address: t.abha_address ?? '',
                    patient_name: t.patient_name ?? '', phone: t.phone ?? '',
                    gender: t.gender ?? '', dob: t.dob ?? ''
                }).replace(/'/g, '&#39;');
                actions += `<button class="btn btn-xs btn-sm btn-primary" onclick="openRegisterModal('${payload}')">Register OPD</button>`;
            }

            return `<tr id="row-${id}" class="${isLinked ? 'table-success' : ''}">
                <td><span class="token-num">#${t.token_number ?? id}</span></td>
                <td>
                    <div>${esc(t.patient_name ?? '—')}</div>
                    <div class="small text-muted">${esc(t.phone ?? '')}</div>
                </td>
                <td>${abhaDisplay}</td>
                <td>${esc(t.department ?? 'General OPD')}</td>
                <td>${sourceBadge(t.source ?? 'manual')}</td>
                <td>${statusBadge(t.status ?? 'PENDING')}</td>
                <td>${hmsCell}</td>
                <td>${fmtTime(t.created_at)}</td>
                <td style="min-width:180px">${actions}</td>
            </tr>`;
        }).join('');
    }

    function fmtAbha(n) {
        const d = String(n).replace(/\D/g, '');
        if (d.length === 14) return `${d.slice(0,2)}-${d.slice(2,6)}-${d.slice(6,10)}-${d.slice(10)}`;
        return n;
    }

    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ---- token status actions ----
    function setTokenStatus(tokenId, status) {
        post(BASE + 'AbdmOpdQueue/token_status/' + tokenId, { status })
            .then(r => {
                if (r.ok) {
                    loadQueue();
                } else {
                    alert('Error: ' + (r.error_text ?? r.message ?? 'Unknown'));
                }
            })
            .catch(err => alert('Request failed: ' + err.message));
    }

    window.callToken     = id => setTokenStatus(id, 'CALLED');
    window.completeToken = id => setTokenStatus(id, 'COMPLETED');
    window.cancelToken   = id => { if (confirm('Cancel token #' + id + '?')) setTokenStatus(id, 'CANCELLED'); };

    // ---- Register OPD from scan_share token ----
    window.openRegisterModal = function (payloadJson) {
        const t = JSON.parse(payloadJson);
        const body = document.getElementById('processTokenBody');
        body.innerHTML = `
            <p class="mb-2">Processing ABHA Scan token for:</p>
            <div class="card border-primary mb-3">
                <div class="card-body py-2">
                    <div class="fw-bold">${esc(t.patient_name || 'Unknown')}</div>
                    <div class="small">${t.abha_number ? 'ABHA: ' + fmtAbha(t.abha_number) : ''} ${t.phone ? ' | Ph: ' + t.phone : ''}</div>
                </div>
            </div>
            <div class="d-flex justify-content-center">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Processing…</span></div>
            </div>
            <p class="text-center text-muted small mt-2">Finding / creating patient record…</p>`;

        const modal = new bootstrap.Modal(document.getElementById('modalProcessToken'));
        modal.show();

        post(BASE + 'AbdmOpdQueue/process_token/' + t.id, {
            abha_number: t.abha_number ?? '',
            abha_address: t.abha_address ?? '',
            patient_name: t.patient_name ?? '',
            phone: t.phone ?? '',
            gender: t.gender ?? '',
            dob: t.dob ?? '',
        }).then(r => {
            if (r.ok) {
                body.innerHTML = `
                    <div class="alert alert-success py-2 mb-2">
                        ${r.is_new ? '<strong>New patient created.</strong>' : '<strong>Existing patient found.</strong>'}
                    </div>
                    <div class="card mb-3">
                        <div class="card-body py-2">
                            <div class="fw-bold">${esc(t.patient_name || '—')}</div>
                            <div class="small text-muted">HMS ID: <strong>${esc(r.p_code)}</strong></div>
                        </div>
                    </div>
                    <a href="${r.redirect_url}" class="btn btn-primary w-100">Open OPD Registration →</a>
                    <button class="btn btn-outline-secondary w-100 mt-2" data-bs-dismiss="modal" onclick="loadQueue()">Back to Queue</button>`;
                loadQueue();
            } else {
                body.innerHTML = `<div class="alert alert-danger">${esc(r.error_text ?? 'Failed to process token')}</div>
                    <button class="btn btn-secondary w-100" data-bs-dismiss="modal">Close</button>`;
            }
        }).catch(err => {
            body.innerHTML = `<div class="alert alert-danger">Request error: ${esc(err.message)}</div>
                <button class="btn btn-secondary w-100" data-bs-dismiss="modal">Close</button>`;
        });
    };

    // ---- Add walk-in token ----
    document.getElementById('btnAddTokenSubmit').addEventListener('click', function () {
        const name = document.getElementById('at_name').value.trim();
        if (!name) {
            document.getElementById('addTokenAlert').textContent = 'Patient name is required.';
            document.getElementById('addTokenAlert').classList.remove('d-none');
            return;
        }
        document.getElementById('addTokenAlert').classList.add('d-none');

        post(BASE + 'AbdmOpdQueue/token', {
            patient_name: name,
            phone:  document.getElementById('at_phone').value.trim(),
            gender: document.getElementById('at_gender').value,
            abha_number: document.getElementById('at_abha').value.replace(/\D/g,''),
            department: document.getElementById('at_dept').value.trim() || 'General OPD',
            date: document.getElementById('at_date').value,
        }).then(r => {
            if (r.ok) {
                bootstrap.Modal.getInstance(document.getElementById('modalAddToken')).hide();
                document.getElementById('at_name').value = '';
                document.getElementById('at_phone').value = '';
                document.getElementById('at_abha').value = '';
                loadQueue();
            } else {
                document.getElementById('addTokenAlert').textContent = r.error_text ?? r.message ?? 'Error creating token.';
                document.getElementById('addTokenAlert').classList.remove('d-none');
            }
        }).catch(err => {
            document.getElementById('addTokenAlert').textContent = err.message;
            document.getElementById('addTokenAlert').classList.remove('d-none');
        });
    });

    // ---- auto-refresh ----
    function resetRefreshBar() {
        const bar = document.getElementById('refreshBar');
        bar.style.transition = 'none';
        bar.style.width = '100%';
        // trigger reflow
        void bar.offsetWidth;
        bar.style.transition = `width ${REFRESH_SECS}s linear`;
        bar.style.width = '0%';

        clearTimeout(autoRefreshTimer);
        autoRefreshTimer = setTimeout(loadQueue, REFRESH_SECS * 1000);
    }

    document.getElementById('btnRefresh').addEventListener('click', loadQueue);
    document.getElementById('queueDate').addEventListener('change', loadQueue);
    document.getElementById('queueStatus').addEventListener('change', loadQueue);

    // Initial load
    loadQueue();

})();
</script>
</body>
</html>

<style>
    .abdm-q-token-num  { font-size:1.1rem; font-weight:700; color:#0d6efd; }
    .badge-src-scan    { background:#0d6efd; }
    .badge-src-manual  { background:#6c757d; }
    .status-PENDING    { background:#ffc107; color:#000; }
    .status-CALLED     { background:#0d6efd; color:#fff; }
    .status-COMPLETED  { background:#198754; color:#fff; }
    .status-CANCELLED  { background:#dc3545; color:#fff; }
    .abdm-q-refresh-bar{ height:3px; background:#0d6efd; transition: width linear 30s; border-radius:2px; }
</style>

<!-- Page title -->
<div class="pagetitle">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="mb-0 fs-5">OPD Queue <span class="badge bg-primary ms-2">ABDM</span></h1>
            <div class="small text-muted">Scan &amp; Share arrivals + walk-in tokens — auto-refresh every 30 s</div>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <input type="date" id="queueDate" class="form-control form-control-sm" style="width:145px" value="<?= date('Y-m-d') ?>">
            <select id="queueStatus" class="form-select form-select-sm" style="width:130px">
                <option value="">All Status</option>
                <option value="PENDING" selected>Pending</option>
                <option value="CALLED">Called</option>
                <option value="COMPLETED">Completed</option>
                <option value="CANCELLED">Cancelled</option>
            </select>
            <button class="btn btn-sm btn-outline-primary" id="btnRefresh">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#abdmQueueModalAddToken">
                <i class="bi bi-plus-lg"></i> Add Walk-in
            </button>
            <a href="javascript:load_form('<?= base_url('AbdmOpdQueue/list') ?>','ABDM OPD List')"
               class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-list-ul"></i> History
            </a>
        </div>
    </div>
</div>

<section class="section">

    <!-- Auto-refresh progress bar -->
    <div class="abdm-q-refresh-bar w-100 mb-2" id="abdmQRefreshBar"></div>

    <!-- Summary pills -->
    <div class="d-flex flex-wrap gap-2 mb-3" id="abdmQSummary">
        <span class="badge rounded-pill bg-secondary px-3 py-2">Loading…</span>
    </div>

    <!-- Queue card -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px">Token</th>
                            <th>Name</th>
                            <th>ABHA</th>
                            <th>Dept</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>HMS Patient</th>
                            <th style="width:70px">Time</th>
                            <th style="min-width:190px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="abdmQueueBody">
                        <tr><td colspan="9" class="text-center py-4 text-muted">Loading queue…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</section>

<!-- ===== Add Walk-in Modal ===== -->
<div class="modal fade" id="abdmQueueModalAddToken" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0">Add Walk-in Token</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="abdmAddTokenAlert" class="alert alert-danger d-none py-2 small"></div>
                <div class="mb-2">
                    <label class="form-label form-label-sm">Patient Name <span class="text-danger">*</span></label>
                    <input type="text" id="at_name" class="form-control form-control-sm" placeholder="Full name">
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label form-label-sm">Phone</label>
                        <input type="tel" id="at_phone" class="form-control form-control-sm" placeholder="10-digit mobile">
                    </div>
                    <div class="col-6">
                        <label class="form-label form-label-sm">Gender</label>
                        <select id="at_gender" class="form-select form-select-sm">
                            <option value="M">Male</option>
                            <option value="F">Female</option>
                            <option value="O">Other</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label form-label-sm">ABHA No (optional)</label>
                        <input type="text" id="at_abha" class="form-control form-control-sm" placeholder="14-digit">
                    </div>
                    <div class="col-6">
                        <label class="form-label form-label-sm">Department</label>
                        <input type="text" id="at_dept" class="form-control form-control-sm" value="General OPD">
                    </div>
                </div>
                <div class="mb-1">
                    <label class="form-label form-label-sm">Date</label>
                    <input type="date" id="at_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-sm btn-success" id="btnAddTokenSubmit">Create Token</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== Register patient from Scan&Share token ===== -->
<div class="modal fade" id="abdmQueueModalProcess" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title mb-0">Register Patient for OPD</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="abdmProcessTokenBody">
                <p class="text-muted small">Processing…</p>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const BASE         = '<?= base_url() ?>';
    const CSRF_NAME    = '<?= csrf_token() ?>';
    const REFRESH_SECS = 30;
    let autoTimer      = null;

    function post(url, data) {
        const fd = new FormData();
        Object.entries(data).forEach(([k, v]) => fd.append(k, v));
        fd.append(CSRF_NAME, '<?= csrf_hash() ?>');
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        }).then(r => r.json());
    }

    function statusBadge(s) {
        return `<span class="badge status-${s} px-2 py-1 rounded-pill small">${s}</span>`;
    }
    function sourceBadge(src) {
        return src === 'scan_share'
            ? '<span class="badge badge-src-scan px-2 small">ABHA Scan</span>'
            : '<span class="badge badge-src-manual px-2 small">Walk-in</span>';
    }
    function fmtTime(ts) {
        if (!ts) return '—';
        return new Date(ts).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true });
    }
    function fmtAbha(n) {
        const d = String(n).replace(/\D/g, '');
        return d.length === 14 ? `${d.slice(0,2)}-${d.slice(2,6)}-${d.slice(6,10)}-${d.slice(10)}` : n;
    }
    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function loadQueue() {
        const date   = document.getElementById('queueDate').value;
        const status = document.getElementById('queueStatus').value;
        const url    = BASE + 'AbdmOpdQueue/fetch?date=' + encodeURIComponent(date)
                             + '&status=' + encodeURIComponent(status);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(renderQueue)
            .catch(err => {
                document.getElementById('abdmQueueBody').innerHTML =
                    `<tr><td colspan="9" class="text-danger text-center py-3 small">Failed to load: ${esc(err.message)}</td></tr>`;
            });
        resetBar();
    }

    function renderQueue(data) {
        const tokens  = data.data ?? data.tokens ?? [];
        const body    = document.getElementById('abdmQueueBody');
        const summary = document.getElementById('abdmQSummary');

        const counts = { PENDING:0, CALLED:0, COMPLETED:0, CANCELLED:0 };
        let opdDone  = 0;
        tokens.forEach(t => {
            if (counts[t.status] !== undefined) counts[t.status]++;
            if (t.hms_patient_id) opdDone++;
        });

        summary.innerHTML = [
            `<span class="badge rounded-pill status-PENDING px-3 py-2">${counts.PENDING} Pending</span>`,
            `<span class="badge rounded-pill status-CALLED px-3 py-2">${counts.CALLED} Called</span>`,
            `<span class="badge rounded-pill status-COMPLETED px-3 py-2">${counts.COMPLETED} Completed</span>`,
            `<span class="badge rounded-pill status-CANCELLED px-3 py-2">${counts.CANCELLED} Cancelled</span>`,
            `<span class="badge rounded-pill bg-success px-3 py-2">✓ ${opdDone} OPD Registered</span>`,
            `<span class="badge rounded-pill bg-secondary px-3 py-2">${tokens.length} Total</span>`,
        ].join('');

        if (!tokens.length) {
            body.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted small">No tokens found for this filter.</td></tr>';
            return;
        }

        body.innerHTML = tokens.map(t => {
            const id       = t.id ?? 0;
            const isPend   = t.status === 'PENDING';
            const isCalled = t.status === 'CALLED';
            const isLinked = !!t.hms_patient_id;

            const abhaCell = t.abha_number
                ? `<div class="small text-primary font-monospace">${fmtAbha(t.abha_number)}</div>`
                : (t.abha_address ? `<div class="small text-muted">${esc(t.abha_address)}</div>` : '<span class="text-muted">—</span>');

            let hmsCell = '<span class="text-muted small">—</span>';
            if (isLinked) {
                const lbl = esc((t.hms_p_code ?? '') + ' ' + (t.hms_p_name ?? ''));
                hmsCell = `<a href="javascript:load_form('${esc(t.hms_profile_url ?? '')}','Patient Profile')" class="text-decoration-none">
                               <span class="badge bg-success-subtle text-success border border-success-subtle">✓ ${lbl}</span>
                           </a>`;
                if (t.hms_opd_url && !t.hms_opd_id) {
                    hmsCell += `<br><a href="${esc(t.hms_opd_url)}" class="small text-primary">+ Add OPD Visit</a>`;
                }
            }

            let actions = '';
            if (isPend) {
                actions += `<button class="btn btn-xs btn-sm btn-outline-primary me-1" onclick="abdmQCall(${id})">Call</button>`;
            }
            if (isPend || isCalled) {
                actions += `<button class="btn btn-xs btn-sm btn-outline-success me-1" onclick="abdmQComplete(${id})">Complete</button>`;
                actions += `<button class="btn btn-xs btn-sm btn-outline-danger me-1" onclick="abdmQCancel(${id})">Cancel</button>`;
            }
            if (!isLinked && (isPend || isCalled)) {
                const pl = JSON.stringify({
                    id: t.id, abha_number: t.abha_number ?? '', abha_address: t.abha_address ?? '',
                    patient_name: t.patient_name ?? '', phone: t.phone ?? '',
                    gender: t.gender ?? '', dob: t.dob ?? ''
                }).replace(/'/g, '&#39;');
                actions += `<button class="btn btn-xs btn-sm btn-primary" onclick="abdmQRegister('${pl}')">Register OPD</button>`;
            }

            return `<tr id="abdmqrow-${id}" class="${isLinked ? 'table-success' : ''}">
                <td><span class="abdm-q-token-num">#${t.token_number ?? id}</span></td>
                <td>
                    <div class="fw-semibold small">${esc(t.patient_name ?? '—')}</div>
                    <div class="small text-muted">${esc(t.phone ?? '')}</div>
                </td>
                <td>${abhaCell}</td>
                <td class="small">${esc(t.department ?? 'General OPD')}</td>
                <td>${sourceBadge(t.source ?? 'manual')}</td>
                <td>${statusBadge(t.status ?? 'PENDING')}</td>
                <td>${hmsCell}</td>
                <td class="small">${fmtTime(t.created_at)}</td>
                <td>${actions}</td>
            </tr>`;
        }).join('');
    }

    function setStatus(tokenId, status) {
        post(BASE + 'AbdmOpdQueue/token_status/' + tokenId, { status })
            .then(r => {
                if (r.ok) { loadQueue(); }
                else      { alert('Error: ' + (r.error_text ?? r.message ?? 'Unknown error')); }
            })
            .catch(err => alert('Request failed: ' + err.message));
    }

    window.abdmQCall     = id => setStatus(id, 'CALLED');
    window.abdmQComplete = id => setStatus(id, 'COMPLETED');
    window.abdmQCancel   = id => { if (confirm('Cancel token #' + id + '?')) setStatus(id, 'CANCELLED'); };

    window.abdmQRegister = function (payloadJson) {
        const t    = JSON.parse(payloadJson);
        const body = document.getElementById('abdmProcessTokenBody');
        body.innerHTML = `
            <p class="mb-2 small">Processing ABHA Scan token for:</p>
            <div class="card border-primary mb-3">
                <div class="card-body py-2">
                    <div class="fw-bold">${esc(t.patient_name || 'Unknown')}</div>
                    <div class="small">${t.abha_number ? 'ABHA: ' + fmtAbha(t.abha_number) : ''}${t.phone ? ' | Ph: ' + t.phone : ''}</div>
                </div>
            </div>
            <div class="d-flex justify-content-center">
                <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Processing…</span></div>
            </div>
            <p class="text-center text-muted small mt-2">Finding / creating patient record…</p>`;

        new bootstrap.Modal(document.getElementById('abdmQueueModalProcess')).show();

        post(BASE + 'AbdmOpdQueue/process_token/' + t.id, {
            abha_number: t.abha_number ?? '', abha_address: t.abha_address ?? '',
            patient_name: t.patient_name ?? '', phone: t.phone ?? '',
            gender: t.gender ?? '', dob: t.dob ?? '',
        }).then(r => {
            if (r.ok) {
                body.innerHTML = `
                    <div class="alert alert-success py-2 small mb-2">
                        ${r.is_new ? '<strong>New patient created.</strong>' : '<strong>Existing patient found.</strong>'}
                    </div>
                    <div class="card mb-3"><div class="card-body py-2 small">
                        <div class="fw-bold">${esc(t.patient_name || '—')}</div>
                        <div class="text-muted">HMS ID: <strong>${esc(r.p_code)}</strong></div>
                    </div></div>
                    <a href="${r.redirect_url}" class="btn btn-sm btn-primary w-100">Open OPD Registration →</a>
                    <button class="btn btn-sm btn-outline-secondary w-100 mt-2" data-bs-dismiss="modal" onclick="loadQueue()">Back to Queue</button>`;
                loadQueue();
            } else {
                body.innerHTML = `<div class="alert alert-danger small">${esc(r.error_text ?? 'Failed to process token')}</div>
                    <button class="btn btn-sm btn-secondary w-100" data-bs-dismiss="modal">Close</button>`;
            }
        }).catch(err => {
            body.innerHTML = `<div class="alert alert-danger small">Request error: ${esc(err.message)}</div>
                <button class="btn btn-sm btn-secondary w-100" data-bs-dismiss="modal">Close</button>`;
        });
    };

    document.getElementById('btnAddTokenSubmit').addEventListener('click', function () {
        const name      = document.getElementById('at_name').value.trim();
        const alertBox  = document.getElementById('abdmAddTokenAlert');
        if (!name) {
            alertBox.textContent = 'Patient name is required.';
            alertBox.classList.remove('d-none');
            return;
        }
        alertBox.classList.add('d-none');

        post(BASE + 'AbdmOpdQueue/token', {
            patient_name : name,
            phone        : document.getElementById('at_phone').value.trim(),
            gender       : document.getElementById('at_gender').value,
            abha_number  : document.getElementById('at_abha').value.replace(/\D/g, ''),
            department   : document.getElementById('at_dept').value.trim() || 'General OPD',
            date         : document.getElementById('at_date').value,
        }).then(r => {
            if (r.ok) {
                bootstrap.Modal.getInstance(document.getElementById('abdmQueueModalAddToken')).hide();
                document.getElementById('at_name').value  = '';
                document.getElementById('at_phone').value = '';
                document.getElementById('at_abha').value  = '';
                loadQueue();
            } else {
                alertBox.textContent = r.error_text ?? r.message ?? 'Error creating token.';
                alertBox.classList.remove('d-none');
            }
        }).catch(err => {
            alertBox.textContent = err.message;
            alertBox.classList.remove('d-none');
        });
    });

    function resetBar() {
        const bar = document.getElementById('abdmQRefreshBar');
        bar.style.transition = 'none';
        bar.style.width = '100%';
        void bar.offsetWidth;
        bar.style.transition = `width ${REFRESH_SECS}s linear`;
        bar.style.width = '0%';
        clearTimeout(autoTimer);
        autoTimer = setTimeout(loadQueue, REFRESH_SECS * 1000);
    }

    document.getElementById('btnRefresh').addEventListener('click', loadQueue);
    document.getElementById('queueDate').addEventListener('change', loadQueue);
    document.getElementById('queueStatus').addEventListener('change', loadQueue);

    window.pageCleanup = function () { clearTimeout(autoTimer); };

    loadQueue();
})();
</script>

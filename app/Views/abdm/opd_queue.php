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
                <option value="PENDING">Pending</option>
                <option value="CALLED">Called</option>
                <option value="COMPLETED">Completed</option>
                <option value="CANCELLED">Cancelled</option>
            </select>
            <button class="btn btn-sm btn-outline-primary" id="btnRefresh">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <a href="javascript:load_form('<?= base_url('billing/patient') ?>','Patient Management')"
               class="btn btn-sm btn-outline-success">
                <i class="bi bi-arrow-left"></i> Back to Patient Management
            </a>
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

<!-- ===== OPD Registration in modal (iframe) ===== -->
<div class="modal fade" id="abdmOpdRegisterModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title mb-0">OPD Registration</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2" style="min-height:70vh;max-height:80vh;overflow:auto;" id="abdmOpdRegisterBody">
                <div class="text-muted small">Loading OPD form…</div>
            </div>
            <div class="modal-footer py-2">
                <a id="abdmOpdRegisterOpenTab" href="#" target="_blank" class="btn btn-sm btn-outline-primary">Open in New Tab</a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
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
    const STATUS_TRANSITIONS = {
        PENDING: ['CALLED', 'CANCELLED'],
        CALLED: ['COMPLETED', 'CANCELLED', 'PENDING'],
        COMPLETED: ['PENDING'],
        CANCELLED: ['PENDING']
    };
    let autoTimer      = null;
    let opdModalCurrentUrl = '';
    let originalLoadFormRef = null;

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
    function encodePayload(obj) {
        return btoa(unescape(encodeURIComponent(JSON.stringify(obj))));
    }
    function decodePayload(payload) {
        return JSON.parse(decodeURIComponent(escape(atob(String(payload || '')))));
    }
    function normalizeOpdUrl(url) {
        if (!url) return '';
        if (/^https?:\/\//i.test(url)) return url;
        if (url.startsWith('/')) return BASE.replace(/\/$/, '') + url;
        return BASE.replace(/\/$/, '') + '/' + String(url).replace(/^\/+/, '');
    }
    function isOpdModalOpen() {
        const el = document.getElementById('abdmOpdRegisterModal');
        return !!(el && el.classList.contains('show'));
    }
    function loadOpdModalContent(url, method = 'GET', data = null) {
        const targetUrl = normalizeOpdUrl(url);
        opdModalCurrentUrl = targetUrl;
        document.getElementById('abdmOpdRegisterOpenTab').href = targetUrl || '#';

        if (!window.jQuery) {
            document.getElementById('abdmOpdRegisterBody').innerHTML = '<div class="alert alert-danger small mb-0">jQuery is required to load OPD form.</div>';
            return;
        }

        $.ajax({
            url: targetUrl,
            method: method,
            data: data,
            dataType: 'html',
            beforeSend: function () {
                document.getElementById('abdmOpdRegisterBody').innerHTML = '<div class="text-muted small">Loading OPD form…</div>';
            }
        }).done(function (html) {
            $('#abdmOpdRegisterBody').html(html);
        }).fail(function (jqXHR) {
            const msg = jqXHR && jqXHR.status ? ('HTTP ' + jqXHR.status) : 'Failed to load OPD form';
            document.getElementById('abdmOpdRegisterBody').innerHTML = '<div class="alert alert-danger small mb-0">' + esc(msg) + '</div>';
        });
    }
    function openOpdRegistrationModal(url) {
        const modalEl = document.getElementById('abdmOpdRegisterModal');
        if (!modalEl) return;

        if (originalLoadFormRef === null && typeof window.load_form === 'function') {
            originalLoadFormRef = window.load_form;
        }

        if (typeof window.load_form === 'function') {
            window.load_form = function (ourl, top_title = '') {
                if (isOpdModalOpen()) {
                    loadOpdModalContent(ourl, 'GET', null);
                    if (top_title) {
                        const titleEl = modalEl.querySelector('.modal-title');
                        if (titleEl) titleEl.textContent = top_title;
                    }
                    return;
                }
                if (typeof originalLoadFormRef === 'function') {
                    originalLoadFormRef(ourl, top_title);
                }
            };
        }

        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        loadOpdModalContent(url, 'GET', null);
    }

    if (window.jQuery) {
        $(document).on('submit', '#abdmOpdRegisterBody form', function (e) {
            e.preventDefault();
            const $form = $(this);
            const action = $form.attr('action') || opdModalCurrentUrl || window.location.href;
            const method = ($form.attr('method') || 'POST').toUpperCase();
            loadOpdModalContent(action, method, $form.serialize());
        });

        $(document).on('click', '#abdmOpdRegisterBody a[href]', function (e) {
            const href = $(this).attr('href') || '';
            if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
                return;
            }
            const normalizedHref = normalizeOpdUrl(href);
            if (/\.pdf(\?|$)/i.test(normalizedHref) || normalizedHref.indexOf('/opd_print/') !== -1) {
                e.preventDefault();
                window.open(normalizedHref, '_blank');
                return;
            }
            e.preventDefault();
            loadOpdModalContent(href, 'GET', null);
        });
    }

    const opdModalEl = document.getElementById('abdmOpdRegisterModal');
    if (opdModalEl) {
        opdModalEl.addEventListener('hidden.bs.modal', function () {
            if (typeof originalLoadFormRef === 'function') {
                window.load_form = originalLoadFormRef;
            }
            originalLoadFormRef = null;
            opdModalCurrentUrl = '';
            const body = document.getElementById('abdmOpdRegisterBody');
            if (body) {
                body.innerHTML = '<div class="text-muted small">Loading OPD form…</div>';
            }
        });
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
        const statusFilter = String(document.getElementById('queueStatus')?.value || '').toUpperCase();
        const viewTokens = statusFilter
            ? tokens
            : tokens.filter(t => ['PENDING', 'CALLED'].includes(String(t.status || '').toUpperCase()));
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

        if (!viewTokens.length) {
            body.innerHTML = statusFilter
                ? '<tr><td colspan="9" class="text-center py-4 text-muted small">No tokens found for this filter.</td></tr>'
                : '<tr><td colspan="9" class="text-center py-4 text-muted small">No active queue tokens. Completed/Cancelled are hidden from active queue.</td></tr>';
            return;
        }

        body.innerHTML = viewTokens.map(t => {
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
                if (t.hms_opd_id && t.hms_opd_code) {
                    hmsCell += `<br><span class="badge bg-primary-subtle text-primary border border-primary-subtle">Booked OPD: ${esc(t.hms_opd_code)}</span>`;
                } else if (t.hms_opd_url) {
                    hmsCell += `<br><a href="javascript:void(0)" onclick="abdmQOpenOpdInModal('${esc(t.hms_opd_url)}')" class="small text-primary">+ Add OPD Visit</a>`;
                }
            }

            let actions = '';
            if (isPend) {
                actions += `<button class="btn btn-xs btn-sm btn-outline-primary me-1" onclick="abdmQCall(${id})">Call</button>`;
                actions += `<button class="btn btn-xs btn-sm btn-outline-danger me-1" onclick="abdmQCancel(${id})">Cancel</button>`;
            }
            if (isCalled) {
                actions += `<button class="btn btn-xs btn-sm btn-outline-success me-1" onclick="abdmQComplete(${id})">Complete</button>`;
                actions += `<button class="btn btn-xs btn-sm btn-outline-secondary me-1" onclick="abdmQReopen(${id})">Mark Pending</button>`;
            }
            if (t.status === 'COMPLETED' || t.status === 'CANCELLED') {
                actions += `<button class="btn btn-xs btn-sm btn-outline-secondary me-1" onclick="abdmQReopen(${id})">Reopen</button>`;
            }
            if (!isLinked && (isPend || isCalled)) {
                const pl = encodePayload({
                    id: t.id, abha_number: t.abha_number ?? '', abha_address: t.abha_address ?? '',
                    aadhaar_number: t.aadhaar_number ?? t.aadhar_number ?? t.udai ?? '',
                    patient_name: t.patient_name ?? '', phone: t.phone ?? '',
                    gender: t.gender ?? '', dob: t.dob ?? '',
                    age: t.age ?? t.patient_age ?? '', birth_year: t.birth_year ?? t.birthYear ?? t.year_of_birth ?? '',
                    relation_text: t.relation_text ?? '', relation_type: t.relation_type ?? '', relative_name: t.relative_name ?? '',
                    email: t.email ?? t.email1 ?? '',
                    address: t.address ?? t.add1 ?? '',
                    city: t.city ?? '', district: t.district ?? '', state: t.state ?? '', zip: t.zip ?? ''
                });
                actions += `<button class="btn btn-xs btn-sm btn-primary" onclick="abdmQRegister('${pl}')">Register OPD</button>`;
            }

            return `<tr id="abdmqrow-${id}" data-status="${esc(String(t.status || 'PENDING').toUpperCase())}" class="${isLinked ? 'table-success' : ''}">
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
        const row = document.getElementById('abdmqrow-' + tokenId);
        const currentStatus = row ? String(row.getAttribute('data-status') || '').trim().toUpperCase() : '';
        if (currentStatus && currentStatus !== status) {
            const next = STATUS_TRANSITIONS[currentStatus] || [];
            if (next.indexOf(status) === -1) {
                alert('Invalid transition: ' + currentStatus + ' -> ' + status);
                return;
            }
        }

        post(BASE + 'AbdmOpdQueue/token_status/' + tokenId, { status })
            .then(r => {
                if (r.ok) {
                    const statusFilter = document.getElementById('queueStatus');
                    // Keep newly called token visible instead of vanishing under Pending-only filter.
                    if (status === 'CALLED' && statusFilter.value === 'PENDING') {
                        statusFilter.value = '';
                    }
                    loadQueue();
                }
                else      { alert('Error: ' + (r.error_text ?? r.message ?? 'Unknown error')); }
            })
            .catch(err => alert('Request failed: ' + err.message));
    }

    window.abdmQCall     = id => setStatus(id, 'CALLED');
    window.abdmQComplete = id => setStatus(id, 'COMPLETED');
    window.abdmQReopen   = id => setStatus(id, 'PENDING');
    window.abdmQCancel   = id => { if (confirm('Cancel token #' + id + '?')) setStatus(id, 'CANCELLED'); };

    window.abdmQRegister = function (payloadEncoded) {
        const t    = decodePayload(payloadEncoded);
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
            <p class="text-center text-muted small mt-2">Checking existing HMS patient records…</p>`;

        new bootstrap.Modal(document.getElementById('abdmQueueModalProcess')).show();

        post(BASE + 'AbdmOpdQueue/process_token/' + t.id, {
            action: 'check',
            abha_number: t.abha_number ?? '', abha_address: t.abha_address ?? '',
            aadhaar_number: t.aadhaar_number ?? '',
            patient_name: t.patient_name ?? '', phone: t.phone ?? '',
            gender: t.gender ?? '', dob: t.dob ?? '',
            age: t.age ?? t.patient_age ?? '', birth_year: t.birth_year ?? t.birthYear ?? t.year_of_birth ?? '',
        }).then(r => {
            if (!r.ok) {
                body.innerHTML = `<div class="alert alert-danger small">${esc(r.error_text ?? 'Failed to process token')}</div>
                    <button class="btn btn-sm btn-secondary w-100" data-bs-dismiss="modal">Close</button>`;
                return;
            }

            if (r.requires_confirmation) {
                const matches = Array.isArray(r.matches) ? r.matches : [];
                const confidenceBadge = conf => {
                    const map = { definitive: ['success','Definitive'], high: ['primary','High'], medium: ['warning','Medium'], low: ['secondary','Low'] };
                    const [cls, lbl] = map[conf] || ['secondary', 'Low'];
                    return `<span class="badge bg-${cls} ms-1">${lbl}</span>`;
                };
                const genderLabel = g => g == 2 ? 'F' : g == 1 ? 'M' : '—';
                const cards = matches.map(m => {
                    const reasons = Array.isArray(m.match_reasons) ? m.match_reasons.join(', ') : '';
                    const conf = m.match_confidence || 'low';
                    const borderCls = conf === 'definitive' ? 'border-success' : conf === 'high' ? 'border-primary' : conf === 'medium' ? 'border-warning' : 'border-secondary';
                    return `<div class="card mb-2 ${borderCls}">
                        <div class="card-body py-2 px-3 small">
                            <div class="fw-bold">${esc(m.p_code || 'UHID N/A')} - ${esc(m.p_fname || 'Unnamed')} ${confidenceBadge(conf)}</div>
                            <div class="text-muted">DOB: ${esc(m.dob || '—')} | Gender: ${genderLabel(m.gender)} | Phone: ${esc(m.mphone1 || '—')}</div>
                            <div class="text-muted">ABHA: ${esc(m.patient_abha || '—')} | Aadhaar: ${esc(m.patient_aadhaar || '—')}</div>
                            <div class="text-muted fst-italic">Matched by: ${esc(reasons || 'Data match')}</div>
                            <button class="btn btn-sm btn-outline-primary mt-2" onclick="abdmQResolveExisting(${Number(t.id) || 0}, ${Number(m.id) || 0}, '${payloadEncoded}')">Link to This Patient</button>
                        </div>
                    </div>`;
                }).join('');

                const createNewCard = `<div class="card mb-2 border-dashed border-2 border-secondary">
                    <div class="card-body py-2 px-3 small">
                        <div class="fw-bold text-secondary"><i class="bi bi-person-plus me-1"></i>None of the above — Register as New Patient</div>
                        <div class="text-muted">This ABHA does not belong to any listed record. A new HMS patient will be created.</div>
                        <button class="btn btn-sm btn-success mt-2" onclick="abdmQCreateNewPatient(${Number(t.id) || 0}, '${payloadEncoded}')">Create New Patient</button>
                    </div>
                </div>`;

                body.innerHTML = `
                    <div class="alert alert-warning py-2 small mb-2">
                        Possible matching records found in HMS. Select the correct patient to link, or register as new.
                    </div>
                    ${cards}${createNewCard}
                    <a href="${BASE}billing/patient" class="btn btn-sm btn-outline-secondary w-100 mt-1" target="_blank">Open Manual Patient Registration</a>
                    <button class="btn btn-sm btn-outline-secondary w-100 mt-1" data-bs-dismiss="modal">Close</button>`;
            } else {
                abdmQCreateNewPatient(t.id, payloadEncoded, true);
            }
        }).catch(err => {
            body.innerHTML = `<div class="alert alert-danger small">Request error: ${esc(err.message)}</div>
                <button class="btn btn-sm btn-secondary w-100" data-bs-dismiss="modal">Close</button>`;
        });
    };

    window.abdmQResolveExisting = function (tokenId, patientId, encodedPayload) {
        const t = decodePayload(encodedPayload);
        const body = document.getElementById('abdmProcessTokenBody');
        body.innerHTML = '<p class="text-muted small mb-0">Linking token to selected patient…</p>';

        post(BASE + 'AbdmOpdQueue/process_token/' + tokenId, {
            action: 'link_existing',
            existing_patient_id: patientId,
            abha_number: t.abha_number ?? '', abha_address: t.abha_address ?? '',
            aadhaar_number: t.aadhaar_number ?? '',
            patient_name: t.patient_name ?? '', phone: t.phone ?? '',
            gender: t.gender ?? '', dob: t.dob ?? '',
            age: t.age ?? t.patient_age ?? '', birth_year: t.birth_year ?? t.birthYear ?? t.year_of_birth ?? '',
        }).then(handleProcessResult).catch(err => {
            body.innerHTML = `<div class="alert alert-danger small">Request error: ${esc(err.message)}</div>`;
        });
    };

    window.abdmQCreateNewPatient = function (tokenId, encodedPayload, skipConfirm) {
        const t = decodePayload(encodedPayload);
        if (!skipConfirm && !confirm('Create a new patient record for this token?')) {
            return;
        }
        renderCreatePatientForm(tokenId, t);
    };

    window.abdmQSubmitCreateNewPatient = function (tokenId) {
        const body = document.getElementById('abdmProcessTokenBody');
        const payload = {
            action: 'create_new',
            abha_number: document.getElementById('np_abha_number')?.value?.replace(/\D/g, '') || '',
            abha_address: document.getElementById('np_abha_address')?.value?.trim() || '',
            aadhaar_number: document.getElementById('np_aadhaar_number')?.value?.replace(/\D/g, '') || '',
            patient_name: document.getElementById('np_patient_name')?.value?.trim() || '',
            phone: document.getElementById('np_phone')?.value?.trim() || '',
            gender: document.getElementById('np_gender')?.value || 'M',
            dob: document.getElementById('np_dob')?.value || '',
            relation_type: document.getElementById('np_relation_type')?.value?.trim() || '',
            relative_name: document.getElementById('np_relative_name')?.value?.trim() || '',
            relation_text: document.getElementById('np_relation_text')?.value?.trim() || '',
            email: document.getElementById('np_email')?.value?.trim() || '',
            address: document.getElementById('np_address')?.value?.trim() || '',
            city: document.getElementById('np_city')?.value?.trim() || '',
            district: document.getElementById('np_district')?.value?.trim() || '',
            state: document.getElementById('np_state')?.value?.trim() || '',
            zip: document.getElementById('np_zip')?.value?.trim() || '',
        };

        if (!payload.patient_name) {
            alert('Patient name is required.');
            return;
        }

        body.innerHTML = '<p class="text-muted small mb-0">Creating new patient record with provided details…</p>';
        post(BASE + 'AbdmOpdQueue/process_token/' + tokenId, payload)
            .then(handleProcessResult)
            .catch(err => {
                body.innerHTML = `<div class="alert alert-danger small">Request error: ${esc(err.message)}</div>`;
            });
    };

    function renderCreatePatientForm(tokenId, t) {
        const body = document.getElementById('abdmProcessTokenBody');
        const relationType = (t.relation_type || '').toUpperCase();
        const relationText = t.relation_text || '';
        const relativeName = t.relative_name || '';

        body.innerHTML = `
            <div class="alert alert-info py-2 small mb-2">
                Review and edit patient data before creating new record in patient_master.
            </div>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label form-label-sm">Patient Name *</label>
                    <input id="np_patient_name" class="form-control form-control-sm" value="${esc(t.patient_name || '')}">
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm">Gender</label>
                    <select id="np_gender" class="form-select form-select-sm">
                        <option value="M" ${(String(t.gender || 'M').toUpperCase().startsWith('M')) ? 'selected' : ''}>Male</option>
                        <option value="F" ${(String(t.gender || '').toUpperCase().startsWith('F')) ? 'selected' : ''}>Female</option>
                        <option value="O" ${(String(t.gender || '').toUpperCase().startsWith('O')) ? 'selected' : ''}>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm">DOB</label>
                    <input id="np_dob" type="date" class="form-control form-control-sm" value="${esc(t.dob || '')}">
                </div>

                <div class="col-md-6">
                    <label class="form-label form-label-sm">Phone</label>
                    <input id="np_phone" class="form-control form-control-sm" value="${esc(t.phone || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label form-label-sm">Email</label>
                    <input id="np_email" class="form-control form-control-sm" value="${esc(t.email || '')}">
                </div>

                <div class="col-md-6">
                    <label class="form-label form-label-sm">ABHA Number</label>
                    <input id="np_abha_number" class="form-control form-control-sm" value="${esc(t.abha_number || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label form-label-sm">ABHA Address</label>
                    <input id="np_abha_address" class="form-control form-control-sm" value="${esc(t.abha_address || '')}">
                </div>

                <div class="col-md-6">
                    <label class="form-label form-label-sm">Aadhaar No</label>
                    <input id="np_aadhaar_number" class="form-control form-control-sm" value="${esc(t.aadhaar_number || '')}">
                </div>
                <div class="col-md-6">
                    <label class="form-label form-label-sm">Relation (Text)</label>
                    <input id="np_relation_text" class="form-control form-control-sm" placeholder="Wife of XXXX / Son of YYYY" value="${esc(relationText)}">
                </div>

                <div class="col-md-4">
                    <label class="form-label form-label-sm">Relation Type</label>
                    <select id="np_relation_type" class="form-select form-select-sm">
                        <option value="" ${relationType === '' ? 'selected' : ''}>-- Select --</option>
                        <option value="S/O" ${relationType === 'S/O' ? 'selected' : ''}>S/O</option>
                        <option value="D/O" ${relationType === 'D/O' ? 'selected' : ''}>D/O</option>
                        <option value="W/O" ${relationType === 'W/O' ? 'selected' : ''}>W/O</option>
                        <option value="C/O" ${relationType === 'C/O' ? 'selected' : ''}>C/O</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label form-label-sm">Relative Name</label>
                    <input id="np_relative_name" class="form-control form-control-sm" value="${esc(relativeName)}">
                </div>

                <div class="col-12">
                    <label class="form-label form-label-sm">Address</label>
                    <input id="np_address" class="form-control form-control-sm" value="${esc(t.address || '')}">
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm">City</label>
                    <input id="np_city" class="form-control form-control-sm" value="${esc(t.city || '')}">
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm">District</label>
                    <input id="np_district" class="form-control form-control-sm" value="${esc(t.district || '')}">
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm">State</label>
                    <input id="np_state" class="form-control form-control-sm" value="${esc(t.state || '')}">
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm">Pin/Zip</label>
                    <input id="np_zip" class="form-control form-control-sm" value="${esc(t.zip || '')}">
                </div>
            </div>
            <div class="small text-muted mt-2">Profile picture upload is supported after creation from Edit Profile.</div>
            <button class="btn btn-sm btn-success w-100 mt-3" onclick="abdmQSubmitCreateNewPatient(${Number(tokenId) || 0})">Create Patient Now</button>
            <a href="${BASE}billing/patient" class="btn btn-sm btn-outline-secondary w-100 mt-2" target="_blank">Open Manual Patient Registration (/billing/patient)</a>
            <button class="btn btn-sm btn-outline-secondary w-100 mt-2" data-bs-dismiss="modal">Close</button>`;
    }

    function handleProcessResult(r) {
        const body = document.getElementById('abdmProcessTokenBody');
        if (r.ok) {
            body.innerHTML = `
                <div class="alert alert-success py-2 small mb-2">
                    ${r.is_new ? '<strong>New patient created.</strong>' : '<strong>Existing patient linked.</strong>'}
                </div>
                <div class="card mb-3"><div class="card-body py-2 small">
                    <div class="text-muted">HMS ID: <strong>${esc(r.p_code || '—')}</strong></div>
                    ${r.saved_data ? `<div class="mt-2 text-muted">Saved: ${esc([r.saved_data.relation, r.saved_data.email, r.saved_data.address].filter(Boolean).join(' | ') || 'Basic fields')}</div>` : ''}
                </div></div>
                <button class="btn btn-sm btn-primary w-100" onclick="abdmQOpenOpdInModal('${esc(r.redirect_url || '')}')">Open OPD Registration →</button>
                <a href="${r.profile_url || ''}" class="btn btn-sm btn-outline-primary w-100 mt-2">Open Patient Profile</a>
                <a href="${r.edit_url || ''}" class="btn btn-sm btn-outline-primary w-100 mt-2">Edit Person Profile (for photo/details)</a>
                <button class="btn btn-sm btn-outline-secondary w-100 mt-2" data-bs-dismiss="modal" onclick="loadQueue()">Back to Queue</button>`;
            loadQueue();
            return;
        }
        body.innerHTML = `<div class="alert alert-danger small">${esc(r.error_text ?? r.message ?? 'Failed to process token')}</div>
            <button class="btn btn-sm btn-secondary w-100" data-bs-dismiss="modal">Close</button>`;
    }

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

    window.abdmQOpenOpdInModal = function (url) {
        openOpdRegistrationModal(url);
    };

    window.pageCleanup = function () { clearTimeout(autoTimer); };

    loadQueue();
})();
</script>

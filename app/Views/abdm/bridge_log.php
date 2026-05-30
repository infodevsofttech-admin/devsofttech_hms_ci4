<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ABDM Bridge Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f5f7fb; }
        .container-wrap { width: 100%; margin: 0; padding: 20px 24px; box-sizing: border-box; }
        .table td, .table th { vertical-align: middle; font-size: 13px; }
        pre.json-block {
            background: #0b1020; color: #d9e2ff;
            padding: 12px; border-radius: 8px;
            max-height: 380px; overflow: auto;
            font-size: 12px; white-space: pre-wrap; word-break: break-all;
        }
        .badge-channel-bridge    { background:#0d6efd; }
        .badge-channel-eatria    { background:#198754; }
        .badge-channel-csnotk    { background:#6f42c1; }
        .badge-channel-healthplix{ background:#fd7e14; }
        .status-success { color:#198754; font-weight:600; }
        .status-error   { color:#dc3545; font-weight:600; }
        .status-pending { color:#fd7e14; font-weight:600; }
        .http-post { color:#0d6efd; font-weight:600; }
        .http-get  { color:#198754; font-weight:600; }
        .trunc     { max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .filter-form input, .filter-form select { font-size: 13px; }
    </style>
</head>
<body>
<div class="container-wrap">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-0"><i class="bi bi-journal-text me-2 text-primary"></i>ABDM Bridge Log</h4>
            <div class="small text-muted">
                All API calls from HMS → <a href="https://abdm-bridge.e-atria.in" target="_blank">abdm-bridge.e-atria.in</a>
                — use <strong>Share</strong> on any row to copy debug info for the bridge team.
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <span class="badge bg-secondary" id="totalBadge">Loading…</span>
            <button class="btn btn-sm btn-outline-primary" id="btnRefresh">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <a href="<?= site_url('AbdmTaskBoard') ?>" class="btn btn-sm btn-outline-secondary">
                ← Task Board
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form class="row g-2 align-items-end filter-form" id="filterForm">
                <div class="col-auto">
                    <label class="form-label mb-0 small">Channel</label>
                    <select class="form-select form-select-sm" id="filterChannel" name="channel">
                        <option value="">All</option>
                        <option value="eatria_bridge">eatria_bridge</option>
                        <option value="bridge">bridge</option>
                        <option value="csnotk">csnotk</option>
                        <option value="abdm">abdm</option>
                        <option value="healthplix">healthplix</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0 small">Status</label>
                    <select class="form-select form-select-sm" id="filterStatus" name="status">
                        <option value="">All</option>
                        <option value="success">success</option>
                        <option value="error">error</option>
                        <option value="pending">pending</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0 small">Date From</label>
                    <input type="date" class="form-control form-control-sm" id="filterDateFrom" name="date_from">
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0 small">Date To</label>
                    <input type="date" class="form-control form-control-sm" id="filterDateTo" name="date_to">
                </div>
                <div class="col">
                    <label class="form-label mb-0 small">Search (event / entity / endpoint)</label>
                    <input type="text" class="form-control form-control-sm" id="filterSearch" name="search" placeholder="e.g. abdm.abha.validate or patient_id">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearFilter">Clear</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover mb-0" id="logTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px">ID</th>
                            <th style="width:140px">Time</th>
                            <th style="width:120px">Channel</th>
                            <th>Event Type</th>
                            <th style="width:50px">M</th>
                            <th>Endpoint</th>
                            <th style="width:80px">HTTP</th>
                            <th style="width:60px">Entity</th>
                            <th style="width:110px">Status</th>
                            <th>Error</th>
                            <th style="width:100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="logBody">
                        <tr><td colspan="11" class="text-center text-muted py-4">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted" id="pageInfo">—</div>
            <div class="d-flex gap-1" id="paginationWrap"></div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="logDetailModalLabel">Log Detail</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Meta row -->
                <div class="row g-2 mb-3" id="detailMeta"></div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="small">Request JSON</strong>
                            <button class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2" id="btnCopyReq">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                        <pre class="json-block" id="detailReqJson">{}</pre>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="small">Response JSON</strong>
                            <button class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2" id="btnCopyRes">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                        <pre class="json-block" id="detailResJson">{}</pre>
                    </div>
                </div>

                <!-- Error message if any -->
                <div id="detailError" class="d-none mt-2">
                    <strong class="small text-danger">Error Message:</strong>
                    <div class="text-danger small mt-1" id="detailErrorText"></div>
                </div>
            </div>
            <div class="modal-footer py-2 gap-2">
                <button type="button" class="btn btn-sm btn-success" id="btnShareDebug">
                    <i class="bi bi-share"></i> Copy Share Snippet
                </button>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const LIST_URL   = '<?= base_url('AbdmBridgeLog/list') ?>';
const DETAIL_URL = '<?= base_url('AbdmBridgeLog/detail') ?>';
let currentPage = 1;
let currentRow  = null;
const modal = new bootstrap.Modal(document.getElementById('logDetailModal'));

/* ── helpers ── */
function statusClass(s) {
    if (!s) return '';
    if (s === 'success') return 'status-success';
    if (s === 'error')   return 'status-error';
    return 'status-pending';
}
function channelBadge(ch) {
    const map = { eatria_bridge:'success', bridge:'primary', csnotk:'purple', abdm:'info', healthplix:'warning' };
    const color = map[ch] ?? 'secondary';
    return `<span class="badge bg-${color}">${escHtml(ch ?? '-')}</span>`;
}
function httpBadge(m) {
    if (!m) return '-';
    const cls = m === 'POST' ? 'text-primary' : 'text-success';
    return `<span class="${cls} fw-bold">${escHtml(m)}</span>`;
}
function responseBadge(code) {
    if (!code) return '-';
    const cls = code >= 200 && code < 300 ? 'bg-success' : 'bg-danger';
    return `<span class="badge ${cls}">${code}</span>`;
}
function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function trunc(s, n) {
    if (!s) return '';
    return s.length > n ? s.slice(0, n) + '…' : s;
}

/* ── load list ── */
function loadList(page) {
    currentPage = page ?? 1;
    const params = new URLSearchParams({
        channel:   document.getElementById('filterChannel').value,
        status:    document.getElementById('filterStatus').value,
        date_from: document.getElementById('filterDateFrom').value,
        date_to:   document.getElementById('filterDateTo').value,
        search:    document.getElementById('filterSearch').value,
        page:      currentPage,
    });

    document.getElementById('logBody').innerHTML =
        '<tr><td colspan="11" class="text-center text-muted py-3">Loading…</td></tr>';

    fetch(LIST_URL + '?' + params)
        .then(r => r.json())
        .then(data => {
            if (!data.ok) { renderError('Failed to load logs.'); return; }
            renderRows(data.rows);
            renderPagination(data.page, data.pages, data.total);
        })
        .catch(() => renderError('Network error.'));
}

function renderRows(rows) {
    const tbody = document.getElementById('logBody');
    if (!rows || rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11" class="text-center text-muted py-4">No log entries found.</td></tr>';
        return;
    }
    tbody.innerHTML = rows.map(r => `
        <tr>
            <td class="text-muted">${r.id}</td>
            <td class="text-nowrap small">${escHtml(r.created_at ?? '')}</td>
            <td>${channelBadge(r.channel)}</td>
            <td class="trunc" style="max-width:200px" title="${escHtml(r.event_type)}">${escHtml(r.event_type ?? '-')}</td>
            <td>${httpBadge(r.http_method)}</td>
            <td class="trunc" style="max-width:180px" title="${escHtml(r.endpoint)}">${escHtml(trunc(r.endpoint, 55))}</td>
            <td>${responseBadge(r.response_code)}</td>
            <td class="small text-muted">${escHtml(trunc(r.entity_id ?? '', 14))}</td>
            <td><span class="${statusClass(r.status)}">${escHtml(r.status ?? '-')}</span></td>
            <td class="small text-danger trunc" style="max-width:160px" title="${escHtml(r.error_message)}">${escHtml(trunc(r.error_message ?? '', 60))}</td>
            <td>
                <button class="btn btn-xs btn-sm btn-outline-primary py-0 px-2" onclick="openDetail(${r.id})">
                    <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-xs btn-sm btn-outline-success py-0 px-2 ms-1" onclick="quickShare(${r.id}, this)">
                    <i class="bi bi-share"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function renderPagination(page, pages, total) {
    document.getElementById('totalBadge').textContent = total + ' entries';
    document.getElementById('pageInfo').textContent = `Page ${page} of ${pages} — ${total} total`;
    const wrap = document.getElementById('paginationWrap');
    wrap.innerHTML = '';
    if (pages <= 1) return;

    const addBtn = (label, p, disabled) => {
        const btn = document.createElement('button');
        btn.className = 'btn btn-sm ' + (p === page ? 'btn-primary' : 'btn-outline-secondary');
        btn.textContent = label;
        btn.disabled = disabled;
        btn.onclick = () => loadList(p);
        wrap.appendChild(btn);
    };

    addBtn('«', 1, page === 1);
    addBtn('‹', page - 1, page === 1);

    const start = Math.max(1, page - 2);
    const end   = Math.min(pages, page + 2);
    for (let i = start; i <= end; i++) addBtn(i, i, false);

    addBtn('›', page + 1, page === pages);
    addBtn('»', pages, page === pages);
}

function renderError(msg) {
    document.getElementById('logBody').innerHTML =
        `<tr><td colspan="11" class="text-center text-danger py-3">${escHtml(msg)}</td></tr>`;
}

/* ── detail modal ── */
function openDetail(id) {
    document.getElementById('detailMeta').innerHTML = '<div class="col text-muted small">Loading…</div>';
    document.getElementById('detailReqJson').textContent = '{}';
    document.getElementById('detailResJson').textContent = '{}';
    document.getElementById('detailError').classList.add('d-none');
    currentRow = null;
    modal.show();

    fetch(DETAIL_URL + '/' + id)
        .then(r => r.json())
        .then(data => {
            if (!data.ok) { document.getElementById('detailMeta').innerHTML = '<div class="text-danger small col">Not found.</div>'; return; }
            const row = data.row;
            currentRow = row;
            document.getElementById('logDetailModalLabel').textContent = `Log #${row.id} — ${row.event_type ?? ''}`;

            // Meta badges
            document.getElementById('detailMeta').innerHTML = `
                <div class="col-auto"><span class="small text-muted">Channel:</span><br>${channelBadge(row.channel)}</div>
                <div class="col-auto"><span class="small text-muted">Method:</span><br>${httpBadge(row.http_method)}</div>
                <div class="col-auto"><span class="small text-muted">HTTP:</span><br>${responseBadge(row.response_code)}</div>
                <div class="col-auto"><span class="small text-muted">Status:</span><br><span class="${statusClass(row.status)}">${escHtml(row.status)}</span></div>
                <div class="col-auto"><span class="small text-muted">Entity:</span><br><span class="small">${escHtml(row.entity_type ?? '')} ${escHtml(row.entity_id ?? '')}</span></div>
                <div class="col"><span class="small text-muted">Endpoint:</span><br><span class="small text-break">${escHtml(row.endpoint ?? '')}</span></div>
                <div class="col-auto"><span class="small text-muted">Time:</span><br><span class="small">${escHtml(row.created_at ?? '')}</span></div>
            `;

            document.getElementById('detailReqJson').textContent = row.request_json  ?? 'null';
            document.getElementById('detailResJson').textContent = row.response_json ?? 'null';

            if (row.error_message) {
                document.getElementById('detailError').classList.remove('d-none');
                document.getElementById('detailErrorText').textContent = row.error_message;
            }
        })
        .catch(() => { document.getElementById('detailMeta').innerHTML = '<div class="text-danger small col">Network error.</div>'; });
}

/* ── quick share from table row (no modal needed) ── */
function quickShare(id, btn) {
    fetch(DETAIL_URL + '/' + id)
        .then(r => r.json())
        .then(data => {
            if (!data.ok) return;
            copyShareSnippet(data.row);
            const orig = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check"></i>';
            btn.classList.replace('btn-outline-success', 'btn-success');
            setTimeout(() => { btn.innerHTML = orig; btn.classList.replace('btn-success', 'btn-outline-success'); }, 1800);
        });
}

/* ── copy helpers ── */
function copyShareSnippet(row) {
    if (!row) { alert('No row loaded.'); return; }
    const snippet = [
        '=== ABDM Bridge Debug Snippet ===',
        `Log ID    : ${row.id}`,
        `Time      : ${row.created_at ?? ''}`,
        `Channel   : ${row.channel ?? ''}`,
        `Event Type: ${row.event_type ?? ''}`,
        `Endpoint  : ${row.endpoint ?? ''}`,
        `HTTP      : ${row.http_method ?? ''} → ${row.response_code ?? ''}`,
        `Status    : ${row.status ?? ''}`,
        row.entity_type ? `Entity    : ${row.entity_type} / ${row.entity_id ?? ''}` : null,
        row.error_message ? `Error     : ${row.error_message}` : null,
        '',
        '--- Request ---',
        row.request_json  ?? 'null',
        '',
        '--- Response ---',
        row.response_json ?? 'null',
        '=================================',
    ].filter(l => l !== null).join('\n');

    navigator.clipboard.writeText(snippet).then(() => {
        showToast('Debug snippet copied to clipboard!', 'success');
    }).catch(() => {
        // Fallback
        const el = document.createElement('textarea');
        el.value = snippet; el.style.position = 'fixed'; el.style.opacity = '0';
        document.body.appendChild(el); el.select(); document.execCommand('copy');
        document.body.removeChild(el);
        showToast('Debug snippet copied!', 'success');
    });
}

function copyJson(elId) {
    const text = document.getElementById(elId)?.textContent ?? '';
    navigator.clipboard.writeText(text).then(() => showToast('Copied!', 'info'))
        .catch(() => { const el = document.createElement('textarea'); el.value = text; document.body.appendChild(el); el.select(); document.execCommand('copy'); document.body.removeChild(el); showToast('Copied!','info'); });
}

function showToast(msg, type) {
    const t = document.createElement('div');
    t.className = `alert alert-${type} position-fixed bottom-0 end-0 m-3 py-2 px-3 shadow`;
    t.style.cssText = 'z-index:9999;font-size:13px;min-width:180px;';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2200);
}

/* ── event wiring ── */
document.getElementById('filterForm').addEventListener('submit', e => { e.preventDefault(); loadList(1); });
document.getElementById('btnRefresh').addEventListener('click', () => loadList(currentPage));
document.getElementById('btnClearFilter').addEventListener('click', () => {
    ['filterChannel','filterStatus','filterDateFrom','filterDateTo','filterSearch'].forEach(id => {
        const el = document.getElementById(id);
        el.tagName === 'SELECT' ? el.selectedIndex = 0 : (el.value = '');
    });
    loadList(1);
});
document.getElementById('btnCopyReq').addEventListener('click',  () => copyJson('detailReqJson'));
document.getElementById('btnCopyRes').addEventListener('click',  () => copyJson('detailResJson'));
document.getElementById('btnShareDebug').addEventListener('click', () => copyShareSnippet(currentRow));

// Default date filter: today
const today = new Date().toISOString().slice(0, 10);
document.getElementById('filterDateFrom').value = today;
document.getElementById('filterDateTo').value   = today;

// Allow deep-link filters, e.g. AbdmBridgeLog?search=REC-xxxx&date_from=2026-05-31
(function applyUrlFilters() {
    const qs = new URLSearchParams(window.location.search || '');
    const map = [
        ['channel', 'filterChannel'],
        ['status', 'filterStatus'],
        ['search', 'filterSearch'],
        ['date_from', 'filterDateFrom'],
        ['date_to', 'filterDateTo'],
    ];
    map.forEach(([key, id]) => {
        const value = (qs.get(key) || '').trim();
        if (value === '') return;
        const el = document.getElementById(id);
        if (!el) return;
        el.value = value;
    });

    // If a queue search is provided without date range, clear date defaults
    // so older queue ids are still discoverable.
    if ((qs.get('search') || '').trim() !== '' && (qs.get('date_from') || '').trim() === '' && (qs.get('date_to') || '').trim() === '') {
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
    }
})();

loadList(1);
</script>
</body>
</html>

<section class="container-fluid py-3">

<style>
.cm-table td, .cm-table th { vertical-align: middle; }
.cm-table .cm-expand-row td { padding: .5rem .75rem; background: #f7faff; }
.cm-snomed-chip {
    display: inline-flex; align-items: center; gap: .3rem;
    background: #dbeafe; border: 1px solid #93c5fd; color: #1d4ed8;
    border-radius: 999px; padding: .1rem .55rem; font-size: .78rem; max-width: 100%;
}
.cm-snomed-chip .chip-code { font-family: monospace; color: #6b7280; font-size: .72rem; }
.cm-snomed-chip.chip-none { background: #f3f4f6; border-color: #d1d5db; color: #9ca3af; font-style: italic; }
.cm-lookup-dd { display: none; position: absolute; left: 0; right: 0; top: 100%; z-index: 1060;
    background: #fff; border: 1px solid #dee2e6; border-radius: .375rem;
    box-shadow: 0 4px 12px rgba(0,0,0,.1); max-height: 240px; overflow-y: auto; }
.cm-lookup-dd .cm-dd-item { display: block; width: 100%; text-align: left;
    padding: .35rem .6rem; border: 0; background: none; font-size: .82rem;
    cursor: pointer; border-bottom: 1px solid #f0f0f0; }
.cm-lookup-dd .cm-dd-item:hover, .cm-lookup-dd .cm-dd-item:focus { background: #f0f4ff; outline: none; }
.cm-source-badge { display: inline-block; padding: .05rem .35rem; border-radius: 3px;
    font-size: .68rem; font-weight: 600; }
.src-snomed, .src-csnotk { background: #d1fae5; color: #065f46; }
.src-local, .src-disease_master { background: #fef3c7; color: #92400e; }
.src-keyword { background: #ede9fe; color: #5b21b6; }
.cm-assign-panel { border: 1px solid #bfdbfe; border-radius: .4rem;
    background: #f0f7ff; padding: .6rem .75rem; }
</style>

    <div class="d-flex align-items-center gap-2 mb-3">
        <h5 class="mb-0">Clinical Master — SNOMED CT Coding</h5>
        <small class="text-muted">Assign SNOMED codes to Complaint &amp; Diagnosis options</small>
    </div>

    <!-- Tab nav -->
    <ul class="nav nav-tabs mb-0" id="cmsTabNav">
        <li class="nav-item">
            <button class="nav-link active" data-cms-tab="complaint" type="button">
                <i class="bi bi-chat-left-text me-1"></i> Chief Complaints
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-cms-tab="disease" type="button">
                <i class="bi bi-clipboard2-pulse me-1"></i> Diagnosis / Disease
            </button>
        </li>
    </ul>

    <!-- ══ COMPLAINTS ══════════════════════════════════════════════════ -->
    <div id="cms_tab_complaint" class="border border-top-0 rounded-bottom p-3 mb-3">

        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
            <input type="text" id="cm_search" class="form-control form-control-sm" style="max-width:200px;" placeholder="Filter list…">
            <div class="form-check mb-0 ms-1">
                <input class="form-check-input" type="checkbox" id="cm_snomed_only">
                <label class="form-check-label small" for="cm_snomed_only">SNOMED coded only</label>
            </div>
            <span class="badge bg-secondary ms-auto" id="cm_count_badge">—</span>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="cm_prev_btn">‹</button>
            <span class="small" id="cm_pager">—</span>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="cm_next_btn">›</button>
        </div>

        <div class="table-responsive">
        <table class="table table-sm table-bordered cm-table" id="tbl_cm">
            <thead class="table-light" style="font-size:.78rem">
                <tr>
                    <th width="30">#</th>
                    <th>Complaint Name</th>
                    <th width="220">SNOMED CT</th>
                    <th width="65" class="text-center">Short</th>
                    <th width="65" class="text-center">Active</th>
                    <th width="120" class="text-center">Action</th>
                </tr>
            </thead>
            <tbody id="cm_tbody">
                <tr><td colspan="6" class="text-muted text-center py-3">Loading…</td></tr>
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td colspan="4" class="p-1 position-relative">
                        <?= csrf_field() ?>
                        <input type="text" class="form-control form-control-sm" id="cm_add_lookup"
                            autocomplete="off" placeholder="Type complaint name to add…">
                        <div id="cm_add_dd" class="cm-lookup-dd"></div>
                    </td>
                    <td></td>
                </tr>
                <tr id="cm_add_form_row" style="display:none;">
                    <td></td>
                    <td colspan="5">
                        <div class="cm-assign-panel">
                            <div class="row g-2 align-items-end">
                                <div class="col-auto">
                                    <label class="form-label mb-0 small fw-semibold">Name</label>
                                    <input type="text" id="cm_add_name" class="form-control form-control-sm"
                                        style="width:180px;text-transform:uppercase;">
                                </div>
                                <div class="col-auto">
                                    <label class="form-label mb-0 small">Hinglish</label>
                                    <input type="text" id="cm_add_hinglish" class="form-control form-control-sm"
                                        style="width:120px;" placeholder="e.g. bukhar">
                                </div>
                                <div class="col-auto">
                                    <label class="form-label mb-0 small">SNOMED (pre-filled)</label>
                                    <div id="cm_add_snomed_display"></div>
                                </div>
                                <div class="col-auto d-flex gap-2 align-items-end">
                                    <div class="form-check form-check-sm mb-0">
                                        <input class="form-check-input" type="checkbox" id="cm_add_short" value="1">
                                        <label class="form-check-label small" for="cm_add_short">Short list</label>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm" id="btn_cm_add_save">Add</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_cm_add_cancel">✕</button>
                                </div>
                            </div>
                            <input type="hidden" id="cm_add_snomed_id" value="">
                            <input type="hidden" id="cm_add_snomed_term_val" value="">
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div><!-- /cms_tab_complaint -->

    <!-- ══ DISEASE ════════════════════════════════════════════════════ -->
    <div id="cms_tab_disease" style="display:none;"
        class="border border-top-0 rounded-bottom p-3 mb-3">

        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
            <input type="text" id="dm_search" class="form-control form-control-sm" style="max-width:200px;" placeholder="Filter list…">
            <div class="form-check mb-0 ms-1">
                <input class="form-check-input" type="checkbox" id="dm_snomed_only">
                <label class="form-check-label small" for="dm_snomed_only">SNOMED coded only</label>
            </div>
            <span class="badge bg-secondary ms-auto" id="dm_count_badge">—</span>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="dm_prev_btn">‹</button>
            <span class="small" id="dm_pager">—</span>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="dm_next_btn">›</button>
        </div>

        <div class="table-responsive">
        <table class="table table-sm table-bordered cm-table" id="tbl_dm">
            <thead class="table-light" style="font-size:.78rem">
                <tr>
                    <th width="30">#</th>
                    <th>Diagnosis Name</th>
                    <th width="240">SNOMED CT</th>
                    <th width="65" class="text-center">Active</th>
                    <th width="120" class="text-center">Action</th>
                </tr>
            </thead>
            <tbody id="dm_tbody">
                <tr><td colspan="5" class="text-muted text-center py-3">Loading…</td></tr>
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td colspan="2" class="p-1 position-relative">
                        <input type="text" class="form-control form-control-sm" id="dm_add_lookup"
                            autocomplete="off" placeholder="Type diagnosis name to add…">
                        <div id="dm_add_dd" class="cm-lookup-dd"></div>
                    </td>
                    <td></td>
                    <td></td>
                </tr>
                <tr id="dm_add_form_row" style="display:none;">
                    <td></td>
                    <td colspan="4">
                        <div class="cm-assign-panel">
                            <div class="row g-2 align-items-end">
                                <div class="col-auto">
                                    <label class="form-label mb-0 small fw-semibold">Name</label>
                                    <input type="text" id="dm_add_name" class="form-control form-control-sm"
                                        style="width:220px;text-transform:uppercase;">
                                </div>
                                <div class="col-auto">
                                    <label class="form-label mb-0 small">SNOMED (pre-filled)</label>
                                    <div id="dm_add_snomed_display"></div>
                                </div>
                                <div class="col-auto d-flex gap-2 align-items-end">
                                    <button type="button" class="btn btn-primary btn-sm" id="btn_dm_add_save">Add</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_dm_add_cancel">✕</button>
                                </div>
                            </div>
                            <input type="hidden" id="dm_add_snomed_id" value="">
                            <input type="hidden" id="dm_add_snomed_term_val" value="">
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div><!-- /cms_tab_disease -->

</section>

<script>
(function () {
    /* ── helpers ─────────────────────────────────────────────────────── */
    function esc(t) { return $('<div>').text((t || '').toString()).html(); }

    function getCsrf() {
        var inp = document.querySelector('input[name="<?= csrf_token() ?>"]');
        return inp ? { name: inp.getAttribute('name'), value: inp.value }
                   : { name: '<?= csrf_token() ?>', value: '<?= csrf_hash() ?>' };
    }
    function updateCsrf(d) {
        if (!d || !d.csrfName) return;
        var inp = document.querySelector('input[name="' + d.csrfName + '"]');
        if (inp) inp.value = d.csrfHash || '';
    }
    function apiPost(url, payload, cb) {
        var csrf = getCsrf(); payload = payload || {};
        payload[csrf.name] = csrf.value;
        $.post(url, payload, function (d) { updateCsrf(d); cb(d || {}); }, 'json')
         .fail(function (xhr) { cb({ update: 0, error_text: 'HTTP ' + xhr.status }); });
    }
    function apiGet(url, cb) {
        $.get(url, function (d) { cb(d || {}); }, 'json')
         .fail(function (xhr) { cb({ error: 'HTTP ' + xhr.status }); });
    }

    function toast(msg, type) {
        var cls = type === 'ok' ? 'alert-success' : type === 'err' ? 'alert-danger' : 'alert-info';
        var id = 'cmt_' + Date.now();
        $('body').append('<div id="' + id + '" class="alert ' + cls + ' alert-dismissible shadow-sm"'
            + ' style="position:fixed;top:18px;right:18px;z-index:9999;min-width:240px;max-width:380px;">'
            + esc(msg) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
        setTimeout(function () { $('#' + id).fadeOut(300, function () { $(this).remove(); }); }, 3800);
    }

    function snomedChip(cid, term) {
        if (!cid) return '<span class="cm-snomed-chip chip-none">not coded</span>';
        return '<span class="cm-snomed-chip">' + esc(term || cid)
            + ' <span class="chip-code">' + esc(cid) + '</span></span>';
    }

    function srcBadge(src) {
        var cls = (src === 'snomed' || src === 'csnotk') ? 'src-snomed'
                : (src === 'local' || src === 'disease_master') ? 'src-local' : 'src-keyword';
        return '<span class="cm-source-badge ' + cls + '">' + esc(src || '') + '</span>';
    }

    /* ══ TAB SWITCH ════════════════════════════════════════════════════ */
    $(document).on('click', '#cmsTabNav .nav-link', function () {
        $('#cmsTabNav .nav-link').removeClass('active');
        $(this).addClass('active');
        var t = $(this).data('cms-tab');
        $('#cms_tab_complaint, #cms_tab_disease').hide();
        $('#cms_tab_' + t).show();
        if (t === 'complaint') cmLoad();
        if (t === 'disease')   dmLoad();
    });

    /* ══ SNOMED EXPAND PANEL BUILDER ═══════════════════════════════════ */
    function buildAssignPanel(prefix, code, cid, term) {
        var inputId = prefix + '_s_input_' + code;
        var ddId    = prefix + '_s_dd_' + code;
        var hidCid  = prefix + '_s_cid_' + code;
        var hidTerm = prefix + '_s_term_' + code;
        var dispId  = prefix + '_s_disp_' + code;
        return '<div class="cm-assign-panel">'
            + '<div class="d-flex flex-wrap gap-2 align-items-end">'
            + '<div style="min-width:260px;position:relative;">'
            + '<label class="form-label mb-1 small fw-semibold">Search SNOMED CT</label>'
            + '<input type="text" id="' + inputId + '" class="form-control form-control-sm cm-panel-snomed-input"'
            + '  data-code="' + esc(code) + '" data-prefix="' + esc(prefix) + '"'
            + '  placeholder="Type to search…" autocomplete="off"'
            + '  value="' + esc(term || (cid ? cid : '')) + '">'
            + '<div id="' + ddId + '" class="cm-lookup-dd" style="min-width:320px;"></div>'
            + '</div>'
            + '<div><label class="form-label mb-1 small">Selected</label>'
            + '<div id="' + dispId + '">' + snomedChip(cid, term) + '</div></div>'
            + '<input type="hidden" id="' + hidCid + '" value="' + esc(cid || '') + '">'
            + '<input type="hidden" id="' + hidTerm + '" value="' + esc(term || '') + '">'
            + '<div class="d-flex gap-2 align-items-end">'
            + '<button type="button" class="btn btn-primary btn-sm btn-cms-save"'
            + '  data-code="' + esc(code) + '" data-prefix="' + esc(prefix) + '">Save SNOMED</button>'
            + '<button type="button" class="btn btn-outline-secondary btn-sm btn-cms-clear-snomed"'
            + '  data-code="' + esc(code) + '" data-prefix="' + esc(prefix) + '">Clear</button>'
            + '<button type="button" class="btn btn-link btn-sm text-secondary btn-cms-close"'
            + '  data-code="' + esc(code) + '" data-prefix="' + esc(prefix) + '">Close</button>'
            + '</div></div></div>';
    }

    /* SNOMED panel search (delegated) */
    $(document).on('input', '.cm-panel-snomed-input', function () {
        var $inp   = $(this);
        var code   = $inp.data('code');
        var prefix = $inp.data('prefix');
        var q      = $inp.val().trim();
        var ddSel  = '#' + prefix + '_s_dd_' + code;
        if (q.length < 2) { $(ddSel).hide().empty(); return; }
        var url = prefix === 'cm'
            ? '<?= base_url('Opd_prescription/complaints_search') ?>'
            : '<?= base_url('Opd_prescription/provisional_diagnosis_search') ?>';
        $.getJSON(url + '?q=' + encodeURIComponent(q), function (data) {
            var rows = data.rows || [];
            var $dd = $(ddSel).empty();
            if (!rows.length) { $dd.hide(); return; }
            rows.forEach(function (r) {
                var cid  = r.concept_id || r.snomed_concept_id || '';
                var name = r.name || '';
                var src  = r.source || '';
                $dd.append('<button type="button" class="cm-dd-item cm-panel-pick"'
                    + ' data-code="' + esc(code) + '" data-prefix="' + esc(prefix) + '"'
                    + ' data-cid="' + esc(cid) + '" data-name="' + esc(name) + '">'
                    + esc(name) + (cid ? ' <span class="chip-code">' + esc(cid) + '</span>' : '')
                    + ' ' + srcBadge(src) + '</button>');
            });
            $dd.show();
        });
    });

    $(document).on('mousedown', '.cm-panel-pick', function (e) {
        e.preventDefault();
        var code   = $(this).data('code');
        var prefix = $(this).data('prefix');
        var cid    = $(this).data('cid');
        var name   = $(this).data('name');
        $('#' + prefix + '_s_cid_' + code).val(cid || '');
        $('#' + prefix + '_s_term_' + code).val(name || '');
        $('#' + prefix + '_s_input_' + code).val(name || '');
        $('#' + prefix + '_s_disp_' + code).html(snomedChip(cid, name));
        $('#' + prefix + '_s_dd_' + code).hide().empty();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.cm-panel-snomed-input, .cm-lookup-dd, #cm_add_dd, #dm_add_dd').length) {
            $('.cm-lookup-dd').hide();
        }
    });

    /* Toggle expand row — complaints */
    $(document).on('click', '.btn-cm-snomed', function () {
        var code = $(this).data('code');
        var $exp = $('#cm_exp_' + code);
        var open = $exp.is(':visible');
        $('.cm-expand-row').hide();
        if (!open) { $exp.show(); $exp.find('.cm-panel-snomed-input').trigger('focus'); }
    });

    /* Toggle expand row — disease */
    $(document).on('click', '.btn-dm-snomed', function () {
        var code = $(this).data('code');
        var $exp = $('#dm_exp_' + code);
        var open = $exp.is(':visible');
        $('.cm-expand-row').hide();
        if (!open) { $exp.show(); $exp.find('.cm-panel-snomed-input').trigger('focus'); }
    });

    /* Close / Clear SNOMED in panel */
    $(document).on('click', '.btn-cms-close', function () {
        var code   = $(this).data('code');
        var prefix = $(this).data('prefix');
        $('#' + prefix + '_exp_' + code).hide();
    });
    $(document).on('click', '.btn-cms-clear-snomed', function () {
        var code   = $(this).data('code');
        var prefix = $(this).data('prefix');
        $('#' + prefix + '_s_cid_' + code).val('');
        $('#' + prefix + '_s_term_' + code).val('');
        $('#' + prefix + '_s_input_' + code).val('');
        $('#' + prefix + '_s_disp_' + code).html(snomedChip('', ''));
    });

    /* Save SNOMED from panel */
    $(document).on('click', '.btn-cms-save', function () {
        var code   = $(this).data('code');
        var prefix = $(this).data('prefix');
        var cid    = $('#' + prefix + '_s_cid_' + code).val().trim();
        var term   = $('#' + prefix + '_s_term_' + code).val().trim();
        var $btn   = $(this);
        var $row   = $('[data-code="' + code + '"].cm-data-row');
        var name   = $row.find('td:eq(1) .fw-semibold').first().text().trim();
        var url    = prefix === 'cm'
            ? '<?= base_url('Opd_prescription/complaints_master_save') ?>'
            : '<?= base_url('Opd_prescription/disease_master_save') ?>';
        $btn.prop('disabled', true).text('Saving…');
        apiPost(url, { Code: code, Name: name, snomed_concept_id: cid, snomed_term: term }, function (d) {
            $btn.prop('disabled', false).text('Save SNOMED');
            if (parseInt(d.update || 0)) {
                toast('SNOMED updated', 'ok');
                $('#' + prefix + '_chip_' + code).html(snomedChip(cid, term));
                $('#' + prefix + '_exp_' + code).hide();
            } else {
                toast(d.error_text || 'Save failed', 'err');
            }
        });
    });

    /* ══ COMPLAINTS ════════════════════════════════════════════════════ */
    var cmState = { start: 0, len: 25, total: 0 };
    var cmSearchTimer = null;

    function cmLoad() {
        var f = ($('#cm_search').val() || '').trim();
        var s = $('#cm_snomed_only').is(':checked') ? 1 : 0;
        apiGet('<?= base_url('Opd_prescription/complaints_master_data') ?>?start=' + cmState.start
            + '&length=' + cmState.len + '&filter=' + encodeURIComponent(f) + '&snomed_only=' + s,
            function (data) {
                cmState.total = parseInt(data.recordsTotal || 0);
                $('#cm_count_badge').text(cmState.total + ' items');
                var rows = data.data || [];
                if (!rows.length) {
                    $('#cm_tbody').html('<tr><td colspan="6" class="text-muted text-center py-3">No complaints found.</td></tr>');
                    $('#cm_pager').text('0'); return;
                }
                var html = '', i = cmState.start;
                rows.forEach(function (r) {
                    i++;
                    var coded  = !!r.snomed_concept_id;
                    var active = parseInt(r.is_active || 0) === 1;
                    html += '<tr class="cm-data-row" data-code="' + esc(r.Code) + '">'
                        + '<td class="text-muted small">' + i + '</td>'
                        + '<td><span class="fw-semibold">' + esc(r.Name || '') + '</span>'
                        + (r.name_hinglish ? '<br><small class="text-muted">' + esc(r.name_hinglish) + '</small>' : '')
                        + '</td>'
                        + '<td id="cm_chip_' + esc(r.Code) + '">' + snomedChip(r.snomed_concept_id, r.snomed_term) + '</td>'
                        + '<td class="text-center">' + (parseInt(r.show_in_short || 0)
                            ? '<span class="badge bg-primary">✓</span>' : '<span class="text-muted small">—</span>') + '</td>'
                        + '<td class="text-center">' + (active
                            ? '<span class="badge bg-success">✓</span>' : '<span class="badge bg-secondary">off</span>') + '</td>'
                        + '<td class="text-center">'
                        + '<button class="btn btn-outline-primary btn-sm py-0 px-1 me-1 btn-cm-snomed"'
                        + '  data-code="' + esc(r.Code) + '" style="font-size:.72rem;">'
                        + (coded ? '⟳' : '+') + ' SNOMED</button>'
                        + '<button class="btn btn-outline-danger btn-sm py-0 px-1 btn-cm-del"'
                        + '  data-code="' + esc(r.Code) + '" style="font-size:.72rem;">✕</button>'
                        + '</td></tr>'
                        + '<tr class="cm-expand-row" id="cm_exp_' + esc(r.Code) + '" style="display:none;">'
                        + '<td></td><td colspan="5">'
                        + buildAssignPanel('cm', r.Code, r.snomed_concept_id, r.snomed_term)
                        + '</td></tr>';
                });
                $('#cm_tbody').html(html);
                var from = cmState.start + 1;
                var to   = Math.min(cmState.start + rows.length, cmState.total);
                $('#cm_pager').text(from + '–' + to + ' / ' + cmState.total);
                $('#cm_prev_btn').prop('disabled', cmState.start === 0);
                $('#cm_next_btn').prop('disabled', cmState.start + cmState.len >= cmState.total);
            });
    }

    $('#cm_prev_btn').on('click', function () { if (cmState.start >= cmState.len) { cmState.start -= cmState.len; cmLoad(); } });
    $('#cm_next_btn').on('click', function () { if (cmState.start + cmState.len < cmState.total) { cmState.start += cmState.len; cmLoad(); } });
    $('#cm_search').on('input', function () { clearTimeout(cmSearchTimer); cmSearchTimer = setTimeout(function () { cmState.start = 0; cmLoad(); }, 300); });
    $('#cm_snomed_only').on('change', function () { cmState.start = 0; cmLoad(); });

    /* Delete complaint */
    $(document).on('click', '.btn-cm-del', function () {
        var code = $(this).data('code');
        if (!confirm('Deactivate this complaint?')) return;
        apiPost('<?= base_url('Opd_prescription/complaints_master_remove') ?>/' + code, {}, function (d) {
            if (parseInt(d.update || 0)) { toast('Deactivated', 'ok'); cmLoad(); }
            else toast(d.error_text || 'Failed', 'err');
        });
    });

    /* ── Add complaint via footer lookup ─────────────────────────────── */
    var cmAddTimer = null;

    $('#cm_add_lookup').on('input', function () {
        var q = $(this).val().trim();
        $('#cm_add_form_row').hide();
        if (q.length < 1) { $('#cm_add_dd').hide().empty(); return; }
        clearTimeout(cmAddTimer);
        cmAddTimer = setTimeout(function () {
            $.getJSON('<?= base_url('Opd_prescription/complaints_search') ?>?q=' + encodeURIComponent(q), function (data) {
                var $dd = $('#cm_add_dd').empty();
                $dd.append('<button type="button" class="cm-dd-item fw-semibold text-success cm-add-pick"'
                    + ' data-name="' + esc(q.toUpperCase()) + '" data-cid="" data-term="">+ New: "' + esc(q.toUpperCase()) + '"</button>');
                (data.rows || []).forEach(function (r) {
                    var cid = r.concept_id || ''; var name = r.name || ''; var src = r.source || '';
                    $dd.append('<button type="button" class="cm-dd-item cm-add-pick"'
                        + ' data-name="' + esc(name.toUpperCase()) + '" data-cid="' + esc(cid) + '" data-term="' + esc(name) + '">'
                        + esc(name) + (cid ? ' <span class="chip-code">' + esc(cid) + '</span>' : '')
                        + ' ' + srcBadge(src) + '</button>');
                });
                $dd.show();
            });
        }, 250);
    });

    $(document).on('mousedown', '#cm_add_dd .cm-add-pick', function (e) {
        e.preventDefault();
        var name = $(this).data('name'); var cid = $(this).data('cid'); var term = $(this).data('term');
        $('#cm_add_lookup').val(name);
        $('#cm_add_dd').hide().empty();
        $('#cm_add_name').val(name);
        $('#cm_add_hinglish').val('');
        $('#cm_add_short').prop('checked', false);
        $('#cm_add_snomed_id').val(cid || '');
        $('#cm_add_snomed_term_val').val(term || '');
        $('#cm_add_snomed_display').html(cid ? snomedChip(cid, term) : '<span class="text-muted small">none</span>');
        $('#cm_add_form_row').show();
        $('#cm_add_name').trigger('focus');
    });

    $('#btn_cm_add_cancel').on('click', function () {
        $('#cm_add_form_row').hide();
        $('#cm_add_lookup').val('');
    });

    $('#btn_cm_add_save').on('click', function () {
        var name = $('#cm_add_name').val().trim().toUpperCase();
        if (!name) { toast('Name is required', 'err'); return; }
        apiPost('<?= base_url('Opd_prescription/complaints_master_save') ?>', {
            Code: 0, Name: name,
            name_hinglish: $('#cm_add_hinglish').val().trim(),
            show_in_short: $('#cm_add_short').is(':checked') ? 1 : 0,
            is_active: 1,
            snomed_concept_id: $('#cm_add_snomed_id').val().trim(),
            snomed_term: $('#cm_add_snomed_term_val').val().trim(),
        }, function (d) {
            if (parseInt(d.update || 0)) {
                toast('Complaint added', 'ok');
                $('#cm_add_form_row').hide(); $('#cm_add_lookup').val('');
                cmLoad();
            } else toast(d.error_text || 'Failed', 'err');
        });
    });

    /* ══ DISEASE ═══════════════════════════════════════════════════════ */
    var dmState = { start: 0, len: 25, total: 0 };
    var dmSearchTimer = null;

    function dmLoad() {
        var f = ($('#dm_search').val() || '').trim();
        var s = $('#dm_snomed_only').is(':checked') ? 1 : 0;
        apiGet('<?= base_url('Opd_prescription/disease_master_data') ?>?start=' + dmState.start
            + '&length=' + dmState.len + '&filter=' + encodeURIComponent(f) + '&snomed_only=' + s,
            function (data) {
                dmState.total = parseInt(data.recordsTotal || 0);
                $('#dm_count_badge').text(dmState.total + ' items');
                var rows = data.data || [];
                if (!rows.length) {
                    $('#dm_tbody').html('<tr><td colspan="5" class="text-muted text-center py-3">No diagnoses found.</td></tr>');
                    $('#dm_pager').text('0'); return;
                }
                var html = '', i = dmState.start;
                rows.forEach(function (r) {
                    i++;
                    var coded  = !!r.snomed_concept_id;
                    var active = (r.is_active === undefined) ? true : parseInt(r.is_active || 0) === 1;
                    html += '<tr class="cm-data-row" data-code="' + esc(r.Code) + '">'
                        + '<td class="text-muted small">' + i + '</td>'
                        + '<td><span class="fw-semibold">' + esc(r.Name || '') + '</span></td>'
                        + '<td id="dm_chip_' + esc(r.Code) + '">' + snomedChip(r.snomed_concept_id, r.snomed_term) + '</td>'
                        + '<td class="text-center">' + (active
                            ? '<span class="badge bg-success">✓</span>' : '<span class="badge bg-secondary">off</span>') + '</td>'
                        + '<td class="text-center">'
                        + '<button class="btn btn-outline-primary btn-sm py-0 px-1 me-1 btn-dm-snomed"'
                        + '  data-code="' + esc(r.Code) + '" style="font-size:.72rem;">'
                        + (coded ? '⟳' : '+') + ' SNOMED</button>'
                        + '<button class="btn btn-outline-danger btn-sm py-0 px-1 btn-dm-del"'
                        + '  data-code="' + esc(r.Code) + '" style="font-size:.72rem;">✕</button>'
                        + '</td></tr>'
                        + '<tr class="cm-expand-row" id="dm_exp_' + esc(r.Code) + '" style="display:none;">'
                        + '<td></td><td colspan="4">'
                        + buildAssignPanel('dm', r.Code, r.snomed_concept_id, r.snomed_term)
                        + '</td></tr>';
                });
                $('#dm_tbody').html(html);
                var from = dmState.start + 1;
                var to   = Math.min(dmState.start + rows.length, dmState.total);
                $('#dm_pager').text(from + '–' + to + ' / ' + dmState.total);
                $('#dm_prev_btn').prop('disabled', dmState.start === 0);
                $('#dm_next_btn').prop('disabled', dmState.start + dmState.len >= dmState.total);
            });
    }

    $('#dm_prev_btn').on('click', function () { if (dmState.start >= dmState.len) { dmState.start -= dmState.len; dmLoad(); } });
    $('#dm_next_btn').on('click', function () { if (dmState.start + dmState.len < dmState.total) { dmState.start += dmState.len; dmLoad(); } });
    $('#dm_search').on('input', function () { clearTimeout(dmSearchTimer); dmSearchTimer = setTimeout(function () { dmState.start = 0; dmLoad(); }, 300); });
    $('#dm_snomed_only').on('change', function () { dmState.start = 0; dmLoad(); });

    /* Delete diagnosis */
    $(document).on('click', '.btn-dm-del', function () {
        var code = $(this).data('code');
        if (!confirm('Deactivate this diagnosis?')) return;
        apiPost('<?= base_url('Opd_prescription/disease_master_remove') ?>/' + code, {}, function (d) {
            if (parseInt(d.update || 0)) { toast('Deactivated', 'ok'); dmLoad(); }
            else toast(d.error_text || 'Failed', 'err');
        });
    });

    /* ── Add diagnosis via footer lookup ─────────────────────────────── */
    var dmAddTimer = null;

    $('#dm_add_lookup').on('input', function () {
        var q = $(this).val().trim();
        $('#dm_add_form_row').hide();
        if (q.length < 1) { $('#dm_add_dd').hide().empty(); return; }
        clearTimeout(dmAddTimer);
        dmAddTimer = setTimeout(function () {
            $.getJSON('<?= base_url('Opd_prescription/provisional_diagnosis_search') ?>?q=' + encodeURIComponent(q), function (data) {
                var $dd = $('#dm_add_dd').empty();
                $dd.append('<button type="button" class="cm-dd-item fw-semibold text-success dm-add-pick"'
                    + ' data-name="' + esc(q.toUpperCase()) + '" data-cid="" data-term="">+ New: "' + esc(q.toUpperCase()) + '"</button>');
                (data.rows || []).forEach(function (r) {
                    var cid = r.snomed_concept_id || ''; var name = r.name || ''; var src = r.source || '';
                    $dd.append('<button type="button" class="cm-dd-item dm-add-pick"'
                        + ' data-name="' + esc(name.toUpperCase()) + '" data-cid="' + esc(cid) + '" data-term="' + esc(name) + '">'
                        + esc(name) + (cid ? ' <span class="chip-code">' + esc(cid) + '</span>' : '')
                        + ' ' + srcBadge(src) + '</button>');
                });
                $dd.show();
            });
        }, 250);
    });

    $(document).on('mousedown', '#dm_add_dd .dm-add-pick', function (e) {
        e.preventDefault();
        var name = $(this).data('name'); var cid = $(this).data('cid'); var term = $(this).data('term');
        $('#dm_add_lookup').val(name);
        $('#dm_add_dd').hide().empty();
        $('#dm_add_name').val(name);
        $('#dm_add_snomed_id').val(cid || '');
        $('#dm_add_snomed_term_val').val(term || '');
        $('#dm_add_snomed_display').html(cid ? snomedChip(cid, term) : '<span class="text-muted small">none</span>');
        $('#dm_add_form_row').show();
        $('#dm_add_name').trigger('focus');
    });

    $('#btn_dm_add_cancel').on('click', function () {
        $('#dm_add_form_row').hide(); $('#dm_add_lookup').val('');
    });

    $('#btn_dm_add_save').on('click', function () {
        var name = $('#dm_add_name').val().trim().toUpperCase();
        if (!name) { toast('Name is required', 'err'); return; }
        apiPost('<?= base_url('Opd_prescription/disease_master_save') ?>', {
            Code: 0, Name: name, is_active: 1,
            snomed_concept_id: $('#dm_add_snomed_id').val().trim(),
            snomed_term: $('#dm_add_snomed_term_val').val().trim(),
        }, function (d) {
            if (parseInt(d.update || 0)) {
                toast('Diagnosis added', 'ok');
                $('#dm_add_form_row').hide(); $('#dm_add_lookup').val('');
                dmLoad();
            } else toast(d.error_text || 'Failed', 'err');
        });
    });

    /* ── Init ─────────────────────────────────────────────────────────── */
    cmLoad();

}());
</script>


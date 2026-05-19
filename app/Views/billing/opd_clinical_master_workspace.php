<section class="container-fluid py-3">

    <div class="d-flex align-items-center gap-2 mb-3">
        <h5 class="mb-0">Clinical Master</h5>
        <span class="text-muted small">Manage Chief Complaints &amp; Diagnosis options with SNOMED CT coding</span>
    </div>

    <!-- Tab nav -->
    <ul class="nav nav-tabs mb-3" id="clinicalMasterTabs">
        <li class="nav-item">
            <button class="nav-link active" data-tab="complaints" type="button">
                <i class="bi bi-chat-left-text me-1"></i> Chief Complaints
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-tab="diagnosis" type="button">
                <i class="bi bi-clipboard2-pulse me-1"></i> Diagnosis / Disease
            </button>
        </li>
    </ul>

    <!-- ══════════════ COMPLAINTS TAB ══════════════ -->
    <div id="tab_complaints">
        <div class="row g-3">

            <!-- List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex flex-wrap gap-2 align-items-center">
                        <input type="text" id="cm_filter" class="form-control form-control-sm" style="max-width:220px;" placeholder="Search name / keyword…">
                        <div class="form-check ms-2 mb-0">
                            <input class="form-check-input" type="checkbox" id="cm_snomed_only">
                            <label class="form-check-label small" for="cm_snomed_only">SNOMED coded only</label>
                        </div>
                        <button type="button" class="btn btn-success btn-sm ms-auto" id="btn_cm_new">
                            <i class="bi bi-plus-lg"></i> New Complaint
                        </button>
                        <span class="badge bg-secondary" id="cm_total_badge">—</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="tbl_cm" class="table table-bordered table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th width="160">SNOMED CT</th>
                                        <th width="80" class="text-center">Short</th>
                                        <th width="70" class="text-center">Active</th>
                                        <th width="90" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="cm_tbody">
                                    <tr><td colspan="5" class="text-muted text-center py-3">Loading…</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex align-items-center gap-3 px-3 py-2 border-top small">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="cm_prev">‹ Prev</button>
                            <span id="cm_page_info">—</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="cm_next">Next ›</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><strong id="cm_form_title">New Complaint</strong></div>
                    <div class="card-body">
                        <?= csrf_field() ?>
                        <input type="hidden" id="cm_code" value="0">

                        <div class="mb-2">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" id="cm_name" class="form-control form-control-sm"
                                placeholder="e.g. FEVER, HEADACHE" style="text-transform:uppercase;">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">SNOMED CT <small class="text-muted">(finding / symptom)</small></label>
                            <div class="position-relative">
                                <input type="text" id="cm_snomed_search" class="form-control form-control-sm"
                                    placeholder="Type to search SNOMED…" autocomplete="off">
                                <div id="cm_snomed_dd" class="list-group shadow position-absolute w-100"
                                    style="z-index:1060;display:none;max-height:210px;overflow-y:auto;"></div>
                            </div>
                            <div id="cm_snomed_selected" class="mt-1 small text-success" style="display:none;"></div>
                            <input type="hidden" id="cm_snomed_concept_id" value="">
                            <input type="hidden" id="cm_snomed_term" value="">
                            <button type="button" class="btn btn-link btn-sm p-0 mt-1" id="btn_cm_snomed_clear"
                                style="display:none;font-size:.75rem;">&#x2715; Clear SNOMED</button>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Hinglish Name</label>
                            <input type="text" id="cm_name_hinglish" class="form-control form-control-sm" placeholder="e.g. bukhar">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Keywords <small class="text-muted">(comma separated)</small></label>
                            <input type="text" id="cm_keywords" class="form-control form-control-sm"
                                placeholder="e.g. fever,bukhar,taap">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">AI Hint</label>
                            <input type="text" id="cm_ai_hint" class="form-control form-control-sm"
                                placeholder="e.g. fever with duration and pattern">
                        </div>

                        <div class="mb-2 d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="cm_show_in_short" value="1">
                                <label class="form-check-label small" for="cm_show_in_short">Show in Short List</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="cm_is_active" value="1" checked>
                                <label class="form-check-label small" for="cm_is_active">Active</label>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="button" class="btn btn-primary btn-sm" id="btn_cm_save">Save</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_cm_reset">Reset</button>
                        </div>
                        <div class="small mt-2" id="cm_msg">Ready.</div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /tab_complaints -->

    <!-- ══════════════ DIAGNOSIS TAB ══════════════ -->
    <div id="tab_diagnosis" style="display:none;">
        <div class="row g-3">

            <!-- List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex flex-wrap gap-2 align-items-center">
                        <input type="text" id="dm_filter" class="form-control form-control-sm" style="max-width:220px;" placeholder="Search diagnosis name…">
                        <div class="form-check ms-2 mb-0">
                            <input class="form-check-input" type="checkbox" id="dm_snomed_only">
                            <label class="form-check-label small" for="dm_snomed_only">SNOMED coded only</label>
                        </div>
                        <button type="button" class="btn btn-success btn-sm ms-auto" id="btn_dm_new">
                            <i class="bi bi-plus-lg"></i> New Diagnosis
                        </button>
                        <span class="badge bg-secondary" id="dm_total_badge">—</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="tbl_dm" class="table table-bordered table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th width="180">SNOMED CT</th>
                                        <th width="70" class="text-center">Active</th>
                                        <th width="90" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="dm_tbody">
                                    <tr><td colspan="4" class="text-muted text-center py-3">Loading…</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex align-items-center gap-3 px-3 py-2 border-top small">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="dm_prev">‹ Prev</button>
                            <span id="dm_page_info">—</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="dm_next">Next ›</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><strong id="dm_form_title">New Diagnosis</strong></div>
                    <div class="card-body">
                        <input type="hidden" id="dm_code" value="0">

                        <div class="mb-2">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" id="dm_name" class="form-control form-control-sm"
                                placeholder="e.g. DIABETES MELLITUS TYPE 2" style="text-transform:uppercase;">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">SNOMED CT <small class="text-muted">(disorder / clinical finding)</small></label>
                            <div class="position-relative">
                                <input type="text" id="dm_snomed_search" class="form-control form-control-sm"
                                    placeholder="Type to search SNOMED…" autocomplete="off">
                                <div id="dm_snomed_dd" class="list-group shadow position-absolute w-100"
                                    style="z-index:1060;display:none;max-height:210px;overflow-y:auto;"></div>
                            </div>
                            <div id="dm_snomed_selected" class="mt-1 small text-success" style="display:none;"></div>
                            <input type="hidden" id="dm_snomed_concept_id" value="">
                            <input type="hidden" id="dm_snomed_term" value="">
                            <button type="button" class="btn btn-link btn-sm p-0 mt-1" id="btn_dm_snomed_clear"
                                style="display:none;font-size:.75rem;">&#x2715; Clear SNOMED</button>
                        </div>

                        <div class="mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="dm_is_active" value="1" checked>
                                <label class="form-check-label small" for="dm_is_active">Active</label>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="button" class="btn btn-primary btn-sm" id="btn_dm_save">Save</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_dm_reset">Reset</button>
                        </div>
                        <div class="small mt-2" id="dm_msg">Ready.</div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /tab_diagnosis -->

</section>

<script>
(function () {
    /* ── helpers ────────────────────────────────────────────────────── */
    function esc(t) { return $('<div>').text((t||'').toString()).html(); }

    function getCsrf() {
        var inp = document.querySelector('input[name="<?= csrf_token() ?>"]');
        return inp ? { name: inp.getAttribute('name'), value: inp.value }
                   : { name: '<?= csrf_token() ?>', value: '<?= csrf_hash() ?>' };
    }
    function updateCsrf(data) {
        if (!data || !data.csrfName) return;
        var inp = document.querySelector('input[name="' + data.csrfName + '"]');
        if (inp) inp.value = data.csrfHash || '';
    }
    function apiPost(url, payload, cb) {
        var csrf = getCsrf();
        payload = payload || {};
        payload[csrf.name] = csrf.value;
        $.post(url, payload, function (d) { updateCsrf(d); cb(d || {}); }, 'json')
         .fail(function (xhr) { cb({ update: 0, error_text: 'Request failed (' + xhr.status + ')' }); });
    }
    function apiGet(url, cb) {
        $.get(url, function (d) { cb(d || {}); }, 'json')
         .fail(function (xhr) { cb({ error: 'Request failed (' + xhr.status + ')' }); });
    }
    function snomedBadge(conceptId, term) {
        if (!conceptId) return '<span class="text-muted small">—</span>';
        var label = esc(term || conceptId);
        return '<span class="badge bg-info text-dark" title="' + esc(conceptId) + '">' + label + '</span>';
    }
    function toast(msg, type) {
        var cls = type === 'ok' ? 'alert-success' : type === 'err' ? 'alert-danger' : 'alert-info';
        var id = 'ct_' + Date.now();
        $('body').append('<div id="' + id + '" class="alert ' + cls + ' shadow-sm alert-dismissible"'
            + ' style="position:fixed;top:20px;right:20px;z-index:9999;min-width:260px;max-width:400px;">'
            + esc(msg) + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
        setTimeout(function () { $('#' + id).fadeOut(300, function () { $(this).remove(); }); }, 4000);
    }

    /* ── Tab switching ──────────────────────────────────────────────── */
    $(document).on('click', '#clinicalMasterTabs .nav-link', function () {
        $('#clinicalMasterTabs .nav-link').removeClass('active');
        $(this).addClass('active');
        var tab = $(this).data('tab');
        $('#tab_complaints, #tab_diagnosis').hide();
        $('#tab_' + tab).show();
        if (tab === 'complaints') { cmLoad(); }
        if (tab === 'diagnosis')  { dmLoad(); }
    });

    /* ══════════════════════════════════════════════════════════════════
       COMPLAINTS MASTER
    ══════════════════════════════════════════════════════════════════ */
    var cmState = { start: 0, length: 25, total: 0 };
    var cmFilterTimer = null;

    function cmLoad() {
        var filter    = ($('#cm_filter').val() || '').trim();
        var snomedOnly = $('#cm_snomed_only').is(':checked') ? 1 : 0;
        var url = '<?= base_url('Opd_prescription/complaints_master_data') ?>?start=' + cmState.start
            + '&length=' + cmState.length + '&filter=' + encodeURIComponent(filter)
            + '&snomed_only=' + snomedOnly;

        $('#cm_tbody').html('<tr><td colspan="5" class="text-muted text-center">Loading…</td></tr>');
        apiGet(url, function (data) {
            cmState.total = parseInt(data.recordsTotal || 0);
            $('#cm_total_badge').text(cmState.total);
            var rows = data.data || [];
            if (!rows.length) {
                $('#cm_tbody').html('<tr><td colspan="5" class="text-muted text-center">No records found.</td></tr>');
                $('#cm_page_info').text('0 records');
                return;
            }
            var html = '';
            rows.forEach(function (r) {
                var active = parseInt(r.is_active || 0) === 1;
                html += '<tr>'
                    + '<td>' + esc(r.Name || '') + '</td>'
                    + '<td>' + snomedBadge(r.snomed_concept_id, r.snomed_term) + '</td>'
                    + '<td class="text-center">' + (parseInt(r.show_in_short || 0) === 1
                        ? '<span class="badge bg-primary">Yes</span>'
                        : '<span class="text-muted">—</span>') + '</td>'
                    + '<td class="text-center">' + (active
                        ? '<span class="badge bg-success">Yes</span>'
                        : '<span class="badge bg-secondary">No</span>') + '</td>'
                    + '<td class="text-center">'
                    + '<button class="btn btn-outline-primary btn-xs btn-cm-edit px-1 py-0 me-1" data-code="' + esc(r.Code) + '" title="Edit">Edit</button>'
                    + '<button class="btn btn-outline-danger btn-xs btn-cm-del px-1 py-0" data-code="' + esc(r.Code) + '" title="Deactivate">Del</button>'
                    + '</td></tr>';
            });
            $('#cm_tbody').html(html);

            var from = cmState.start + 1;
            var to   = Math.min(cmState.start + rows.length, cmState.total);
            $('#cm_page_info').text(from + '–' + to + ' of ' + cmState.total);
            $('#cm_prev').prop('disabled', cmState.start === 0);
            $('#cm_next').prop('disabled', cmState.start + cmState.length >= cmState.total);
        });
    }

    $('#cm_filter').on('input', function () {
        clearTimeout(cmFilterTimer);
        cmFilterTimer = setTimeout(function () { cmState.start = 0; cmLoad(); }, 300);
    });
    $('#cm_snomed_only').on('change', function () { cmState.start = 0; cmLoad(); });
    $('#cm_prev').on('click', function () { if (cmState.start >= cmState.length) { cmState.start -= cmState.length; cmLoad(); } });
    $('#cm_next').on('click', function () { if (cmState.start + cmState.length < cmState.total) { cmState.start += cmState.length; cmLoad(); } });

    // Edit row
    $(document).on('click', '.btn-cm-edit', function () {
        var code = $(this).data('code');
        apiGet('<?= base_url('Opd_prescription/complaints_master_get') ?>/' + code, function (data) {
            if (!data.row) { toast('Could not load record', 'err'); return; }
            cmFillForm(data.row);
        });
    });

    // Delete row
    $(document).on('click', '.btn-cm-del', function () {
        var code = $(this).data('code');
        if (!confirm('Deactivate this complaint?')) return;
        apiPost('<?= base_url('Opd_prescription/complaints_master_remove') ?>/' + code, {}, function (data) {
            if (parseInt(data.update || 0)) { toast('Complaint deactivated', 'ok'); cmLoad(); }
            else { toast(data.error_text || 'Failed', 'err'); }
        });
    });

    // New button
    $('#btn_cm_new').on('click', cmClearForm);

    function cmClearForm() {
        $('#cm_code').val('0');
        $('#cm_name,#cm_name_hinglish,#cm_keywords,#cm_ai_hint').val('');
        $('#cm_snomed_search,#cm_snomed_concept_id,#cm_snomed_term').val('');
        $('#cm_snomed_selected').hide().text('');
        $('#btn_cm_snomed_clear').hide();
        $('#cm_show_in_short').prop('checked', false);
        $('#cm_is_active').prop('checked', true);
        $('#cm_form_title').text('New Complaint');
        $('#cm_msg').removeClass('text-success text-danger').addClass('text-muted').text('Ready.');
    }

    function cmFillForm(r) {
        $('#cm_code').val(r.Code || 0);
        $('#cm_name').val((r.Name || '').toUpperCase());
        $('#cm_name_hinglish').val(r.name_hinglish || '');
        $('#cm_keywords').val(r.keywords || '');
        $('#cm_ai_hint').val(r.ai_hint || '');
        $('#cm_show_in_short').prop('checked', parseInt(r.show_in_short || 0) === 1);
        $('#cm_is_active').prop('checked', parseInt(r.is_active || 1) === 1);
        // SNOMED
        var scid = r.snomed_concept_id || '';
        var sterm = r.snomed_term || '';
        $('#cm_snomed_concept_id').val(scid);
        $('#cm_snomed_term').val(sterm);
        if (scid) {
            $('#cm_snomed_search').val(sterm || scid);
            $('#cm_snomed_selected').show().html('<i class="bi bi-check-circle text-success"></i> ' + esc(sterm || scid) + ' <small class="text-muted">(' + esc(scid) + ')</small>');
            $('#btn_cm_snomed_clear').show();
        } else {
            $('#cm_snomed_search').val('');
            $('#cm_snomed_selected').hide().text('');
            $('#btn_cm_snomed_clear').hide();
        }
        $('#cm_form_title').text('Edit: ' + esc(r.Name || ''));
        $('#cm_msg').removeClass('text-success text-danger').addClass('text-muted').text('Loaded.');
        $('html,body').animate({ scrollTop: $('#cm_form_title').offset().top - 80 }, 200);
    }

    // Save
    $('#btn_cm_save').on('click', function () {
        var name = $('#cm_name').val().trim().toUpperCase();
        if (!name) { $('#cm_msg').removeClass('text-muted text-success').addClass('text-danger').text('Name is required.'); return; }
        var payload = {
            Code:              $('#cm_code').val(),
            Name:              name,
            name_hinglish:     $('#cm_name_hinglish').val().trim(),
            keywords:          $('#cm_keywords').val().trim(),
            ai_hint:           $('#cm_ai_hint').val().trim(),
            show_in_short:     $('#cm_show_in_short').is(':checked') ? 1 : 0,
            is_active:         $('#cm_is_active').is(':checked') ? 1 : 0,
            snomed_concept_id: $('#cm_snomed_concept_id').val().trim(),
            snomed_term:       $('#cm_snomed_term').val().trim(),
        };
        $('#btn_cm_save').prop('disabled', true).text('Saving…');
        apiPost('<?= base_url('Opd_prescription/complaints_master_save') ?>', payload, function (data) {
            $('#btn_cm_save').prop('disabled', false).text('Save');
            if (parseInt(data.update || 0)) {
                $('#cm_msg').removeClass('text-muted text-danger').addClass('text-success').text(data.error_text || 'Saved');
                toast(data.error_text || 'Saved', 'ok');
                cmLoad();
            } else {
                $('#cm_msg').removeClass('text-muted text-success').addClass('text-danger').text(data.error_text || 'Failed');
                toast(data.error_text || 'Failed', 'err');
            }
        });
    });

    $('#btn_cm_reset').on('click', cmClearForm);

    // SNOMED search for Complaints (uses complaints_search → searchFinding)
    var cmSnomedTimer = null;
    $('#cm_snomed_search').on('input', function () {
        var q = $(this).val().trim();
        if (q.length < 2) { $('#cm_snomed_dd').hide().empty(); return; }
        clearTimeout(cmSnomedTimer);
        cmSnomedTimer = setTimeout(function () {
            $.getJSON('<?= base_url('Opd_prescription/complaints_search') ?>?q=' + encodeURIComponent(q), function (data) {
                var rows = data.rows || [];
                var $dd = $('#cm_snomed_dd').empty();
                if (!rows.length) { $dd.hide(); return; }
                rows.forEach(function (r) {
                    var cid   = r.concept_id || '';
                    var name  = r.name || '';
                    var src   = r.source || '';
                    var label = esc(name) + (cid ? ' <small class="text-muted">(' + esc(cid) + ')</small>' : '')
                        + ' <small class="badge bg-' + (src === 'snomed' ? 'info text-dark' : 'secondary') + ' ms-1">' + esc(src) + '</small>';
                    $dd.append('<button type="button" class="list-group-item list-group-item-action py-1 px-2 small"'
                        + ' data-name="' + esc(name) + '" data-cid="' + esc(cid) + '">' + label + '</button>');
                });
                $dd.show();
                // Position relative to the input
                var $input = $('#cm_snomed_search');
                $dd.css({ top: $input.outerHeight(true), left: 0 });
            });
        }, 280);
    });
    $(document).on('click', '#cm_snomed_dd .list-group-item', function () {
        var name = $(this).data('name');
        var cid  = $(this).data('cid');
        $('#cm_snomed_concept_id').val(cid || '');
        $('#cm_snomed_term').val(name || '');
        $('#cm_snomed_search').val(cid ? cid + ' — ' + name : name);
        if (name || cid) {
            $('#cm_snomed_selected').show().html('<i class="bi bi-check-circle text-success"></i> ' + esc(name) + (cid ? ' <small class="text-muted">(' + esc(cid) + ')</small>' : ''));
            $('#btn_cm_snomed_clear').show();
        }
        $('#cm_snomed_dd').hide().empty();
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#cm_snomed_search, #cm_snomed_dd').length) { $('#cm_snomed_dd').hide(); }
    });
    $('#btn_cm_snomed_clear').on('click', function () {
        $('#cm_snomed_concept_id,#cm_snomed_term,#cm_snomed_search').val('');
        $('#cm_snomed_selected').hide().text('');
        $(this).hide();
    });

    /* ══════════════════════════════════════════════════════════════════
       DIAGNOSIS / DISEASE MASTER
    ══════════════════════════════════════════════════════════════════ */
    var dmState = { start: 0, length: 25, total: 0 };
    var dmFilterTimer = null;

    function dmLoad() {
        var filter    = ($('#dm_filter').val() || '').trim();
        var snomedOnly = $('#dm_snomed_only').is(':checked') ? 1 : 0;
        var url = '<?= base_url('Opd_prescription/disease_master_data') ?>?start=' + dmState.start
            + '&length=' + dmState.length + '&filter=' + encodeURIComponent(filter)
            + '&snomed_only=' + snomedOnly;

        $('#dm_tbody').html('<tr><td colspan="4" class="text-muted text-center">Loading…</td></tr>');
        apiGet(url, function (data) {
            dmState.total = parseInt(data.recordsTotal || 0);
            $('#dm_total_badge').text(dmState.total);
            var rows = data.data || [];
            if (!rows.length) {
                $('#dm_tbody').html('<tr><td colspan="4" class="text-muted text-center">No records found.</td></tr>');
                $('#dm_page_info').text('0 records');
                return;
            }
            var html = '';
            rows.forEach(function (r) {
                var active = (r.is_active === undefined) ? true : parseInt(r.is_active || 0) === 1;
                html += '<tr>'
                    + '<td>' + esc(r.Name || '') + '</td>'
                    + '<td>' + snomedBadge(r.snomed_concept_id, r.snomed_term) + '</td>'
                    + '<td class="text-center">' + (active
                        ? '<span class="badge bg-success">Yes</span>'
                        : '<span class="badge bg-secondary">No</span>') + '</td>'
                    + '<td class="text-center">'
                    + '<button class="btn btn-outline-primary btn-xs btn-dm-edit px-1 py-0 me-1" data-code="' + esc(r.Code) + '" title="Edit">Edit</button>'
                    + '<button class="btn btn-outline-danger btn-xs btn-dm-del px-1 py-0" data-code="' + esc(r.Code) + '" title="Deactivate">Del</button>'
                    + '</td></tr>';
            });
            $('#dm_tbody').html(html);

            var from = dmState.start + 1;
            var to   = Math.min(dmState.start + rows.length, dmState.total);
            $('#dm_page_info').text(from + '–' + to + ' of ' + dmState.total);
            $('#dm_prev').prop('disabled', dmState.start === 0);
            $('#dm_next').prop('disabled', dmState.start + dmState.length >= dmState.total);
        });
    }

    $('#dm_filter').on('input', function () {
        clearTimeout(dmFilterTimer);
        dmFilterTimer = setTimeout(function () { dmState.start = 0; dmLoad(); }, 300);
    });
    $('#dm_snomed_only').on('change', function () { dmState.start = 0; dmLoad(); });
    $('#dm_prev').on('click', function () { if (dmState.start >= dmState.length) { dmState.start -= dmState.length; dmLoad(); } });
    $('#dm_next').on('click', function () { if (dmState.start + dmState.length < dmState.total) { dmState.start += dmState.length; dmLoad(); } });

    // Edit row
    $(document).on('click', '.btn-dm-edit', function () {
        var code = $(this).data('code');
        apiGet('<?= base_url('Opd_prescription/disease_master_get') ?>/' + code, function (data) {
            if (!data.row) { toast('Could not load record', 'err'); return; }
            dmFillForm(data.row);
        });
    });

    // Delete row
    $(document).on('click', '.btn-dm-del', function () {
        var code = $(this).data('code');
        if (!confirm('Deactivate this diagnosis?')) return;
        apiPost('<?= base_url('Opd_prescription/disease_master_remove') ?>/' + code, {}, function (data) {
            if (parseInt(data.update || 0)) { toast('Diagnosis deactivated', 'ok'); dmLoad(); }
            else { toast(data.error_text || 'Failed', 'err'); }
        });
    });

    // New button
    $('#btn_dm_new').on('click', dmClearForm);

    function dmClearForm() {
        $('#dm_code').val('0');
        $('#dm_name').val('');
        $('#dm_snomed_search,#dm_snomed_concept_id,#dm_snomed_term').val('');
        $('#dm_snomed_selected').hide().text('');
        $('#btn_dm_snomed_clear').hide();
        $('#dm_is_active').prop('checked', true);
        $('#dm_form_title').text('New Diagnosis');
        $('#dm_msg').removeClass('text-success text-danger').addClass('text-muted').text('Ready.');
    }

    function dmFillForm(r) {
        $('#dm_code').val(r.Code || 0);
        $('#dm_name').val((r.Name || '').toUpperCase());
        $('#dm_is_active').prop('checked', (r.is_active === undefined) ? true : parseInt(r.is_active || 1) === 1);
        // SNOMED
        var scid = r.snomed_concept_id || '';
        var sterm = r.snomed_term || '';
        $('#dm_snomed_concept_id').val(scid);
        $('#dm_snomed_term').val(sterm);
        if (scid) {
            $('#dm_snomed_search').val(sterm || scid);
            $('#dm_snomed_selected').show().html('<i class="bi bi-check-circle text-success"></i> ' + esc(sterm || scid) + ' <small class="text-muted">(' + esc(scid) + ')</small>');
            $('#btn_dm_snomed_clear').show();
        } else {
            $('#dm_snomed_search').val('');
            $('#dm_snomed_selected').hide().text('');
            $('#btn_dm_snomed_clear').hide();
        }
        $('#dm_form_title').text('Edit: ' + esc(r.Name || ''));
        $('#dm_msg').removeClass('text-success text-danger').addClass('text-muted').text('Loaded.');
        $('html,body').animate({ scrollTop: $('#dm_form_title').offset().top - 80 }, 200);
    }

    // Save
    $('#btn_dm_save').on('click', function () {
        var name = $('#dm_name').val().trim().toUpperCase();
        if (!name) { $('#dm_msg').removeClass('text-muted text-success').addClass('text-danger').text('Name is required.'); return; }
        var payload = {
            Code:              $('#dm_code').val(),
            Name:              name,
            snomed_concept_id: $('#dm_snomed_concept_id').val().trim(),
            snomed_term:       $('#dm_snomed_term').val().trim(),
            is_active:         $('#dm_is_active').is(':checked') ? 1 : 0,
        };
        $('#btn_dm_save').prop('disabled', true).text('Saving…');
        apiPost('<?= base_url('Opd_prescription/disease_master_save') ?>', payload, function (data) {
            $('#btn_dm_save').prop('disabled', false).text('Save');
            if (parseInt(data.update || 0)) {
                $('#dm_msg').removeClass('text-muted text-danger').addClass('text-success').text(data.error_text || 'Saved');
                toast(data.error_text || 'Saved', 'ok');
                dmLoad();
            } else {
                $('#dm_msg').removeClass('text-muted text-success').addClass('text-danger').text(data.error_text || 'Failed');
                toast(data.error_text || 'Failed', 'err');
            }
        });
    });

    $('#btn_dm_reset').on('click', dmClearForm);

    // SNOMED search for Diagnosis (uses provisional_diagnosis_search → searchDiagnosis)
    var dmSnomedTimer = null;
    $('#dm_snomed_search').on('input', function () {
        var q = $(this).val().trim();
        if (q.length < 2) { $('#dm_snomed_dd').hide().empty(); return; }
        clearTimeout(dmSnomedTimer);
        dmSnomedTimer = setTimeout(function () {
            $.getJSON('<?= base_url('Opd_prescription/provisional_diagnosis_search') ?>?q=' + encodeURIComponent(q), function (data) {
                var rows = data.rows || [];
                var $dd = $('#dm_snomed_dd').empty();
                if (!rows.length) { $dd.hide(); return; }
                rows.forEach(function (r) {
                    var cid   = r.snomed_concept_id || '';
                    var name  = r.name || '';
                    var src   = r.source || '';
                    var label = esc(name) + (cid ? ' <small class="text-muted">(' + esc(cid) + ')</small>' : '')
                        + ' <small class="badge bg-' + (src === 'csnotk' || src === 'snomed' ? 'info text-dark' : 'secondary') + ' ms-1">' + esc(src) + '</small>';
                    $dd.append('<button type="button" class="list-group-item list-group-item-action py-1 px-2 small"'
                        + ' data-name="' + esc(name) + '" data-cid="' + esc(cid) + '">' + label + '</button>');
                });
                $dd.show();
            });
        }, 280);
    });
    $(document).on('click', '#dm_snomed_dd .list-group-item', function () {
        var name = $(this).data('name');
        var cid  = $(this).data('cid');
        $('#dm_snomed_concept_id').val(cid || '');
        $('#dm_snomed_term').val(name || '');
        $('#dm_snomed_search').val(cid ? cid + ' — ' + name : name);
        if (name || cid) {
            $('#dm_snomed_selected').show().html('<i class="bi bi-check-circle text-success"></i> ' + esc(name) + (cid ? ' <small class="text-muted">(' + esc(cid) + ')</small>' : ''));
            $('#btn_dm_snomed_clear').show();
        }
        $('#dm_snomed_dd').hide().empty();
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#dm_snomed_search, #dm_snomed_dd').length) { $('#dm_snomed_dd').hide(); }
    });
    $('#btn_dm_snomed_clear').on('click', function () {
        $('#dm_snomed_concept_id,#dm_snomed_term,#dm_snomed_search').val('');
        $('#dm_snomed_selected').hide().text('');
        $(this).hide();
    });

    /* ── Init ───────────────────────────────────────────────────────── */
    cmLoad();

}());
</script>

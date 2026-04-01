<?php
/** @var array $med_specs */
$med_specs = $med_specs ?? [];
?>
<section class="container-fluid py-3">
    <div class="d-flex align-items-center gap-2 mb-3">
        <h5 class="mb-0"><i class="bi bi-clipboard2-pulse me-1"></i> Investigation Master</h5>
        <span class="badge bg-secondary" id="inv_total_badge">—</span>
        <div class="ms-auto d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_inv_download_csv">
                <i class="bi bi-download"></i> Export CSV
            </button>
        </div>
    </div>

    <div class="row g-3">
        <!-- ── LIST ──────────────────────────────────────────────────── -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center">
                    <input type="text" id="inv_filter_text" class="form-control form-control-sm" style="max-width:220px;" placeholder="Search name / code…">
                    <select id="inv_filter_spec" class="form-select form-select-sm" style="max-width:200px;">
                        <option value="0">All Specializations</option>
                        <?php foreach ($med_specs as $sp): ?>
                            <option value="<?= (int) ($sp->id ?? 0) ?>"><?= esc($sp->SpecName ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-warning btn-sm" id="btn_inv_filter_fav" title="Show favourites only">
                        <i class="bi bi-star-fill"></i> Favourites
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="btn_inv_filter_all">
                        Show All
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="tbl_invest_master" class="table table-bordered table-sm table-hover mb-0" style="width:100%;">
                            <thead class="table-light">
                                <tr>
                                    <th width="32"></th>
                                    <th>Name</th>
                                    <th width="90">Code</th>
                                    <th width="80">Short</th>
                                    <th width="110">Category</th>
                                    <th width="140">Specializations</th>
                                    <th width="100">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="7" class="text-muted text-center py-3">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── FORM ──────────────────────────────────────────────────── -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><strong id="inv_form_title">New Investigation</strong></div>
                <div class="card-body">
                    <?= csrf_field() ?>
                    <input type="hidden" id="inv_id" value="0">

                    <div class="mb-2">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" id="inv_name" class="form-control form-control-sm" placeholder="e.g. CBC, LFT, ECG">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Code <small class="text-muted">(auto if blank)</small></label>
                        <input type="text" id="inv_code" class="form-control form-control-sm" placeholder="e.g. CBC01">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Short Name / Abbreviation</label>
                        <input type="text" id="inv_short_name" class="form-control form-control-sm" placeholder="e.g. CBC">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Category</label>
                        <input type="text" id="inv_category_name" class="form-control form-control-sm" placeholder="e.g. Blood Test, Radiology">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Specialization(s)</label>
                        <div id="inv_spec_checkboxes" class="border rounded p-2" style="max-height:140px;overflow-y:auto;">
                            <?php foreach ($med_specs as $sp): ?>
                                <div class="form-check form-check-sm">
                                    <input class="form-check-input inv-spec-cb" type="checkbox"
                                        id="inv_spec_<?= (int) ($sp->id ?? 0) ?>"
                                        value="<?= (int) ($sp->id ?? 0) ?>">
                                    <label class="form-check-label small" for="inv_spec_<?= (int) ($sp->id ?? 0) ?>">
                                        <?= esc($sp->SpecName ?? '') ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($med_specs)): ?>
                                <span class="small text-muted">No specializations found. Add them in Doctor &gt; Specialization.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="inv_is_favourite" value="1">
                            <label class="form-check-label" for="inv_is_favourite">
                                <i class="bi bi-star-fill text-warning"></i> Mark as Favourite
                            </label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_inv_save">Save</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_inv_reset">Reset</button>
                    </div>

                    <div class="small mt-2" id="inv_msg">Ready.</div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    var invDataTable = null;
    var favOnlyMode  = false;
    var filterTimer  = null;

    /* ── Toast ─────────────────────────────────────────────────────── */
    function showToast(message, type) {
        var cls = type === 'ok' ? 'alert-success' : (type === 'err' ? 'alert-danger' : 'alert-info');
        var id  = 'inv_toast_' + Date.now();
        var html = '<div id="' + id + '" class="alert ' + cls + ' shadow-sm d-flex align-items-start justify-content-between"'
            + ' role="alert" style="position:fixed;top:20px;right:20px;z-index:9999;min-width:260px;max-width:420px;gap:12px;">'
            + '<div>' + $('<div>').text(message || '').html() + '</div>'
            + '<button type="button" class="btn-close btn-sm" data-tid="' + id + '"></button></div>';
        $('body').append(html);
        setTimeout(function() { $('#' + id).fadeOut(200, function() { $(this).remove(); }); }, 4000);
    }
    $(document).on('click', '.btn-close[data-tid]', function() {
        $('#' + $(this).data('tid')).fadeOut(150, function() { $(this).remove(); });
    });

    /* ── CSRF ───────────────────────────────────────────────────────── */
    function getCsrfPair() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        return input
            ? { name: input.getAttribute('name'), value: input.value }
            : { name: '<?= csrf_token() ?>', value: '<?= csrf_hash() ?>' };
    }
    function updateCsrf(data) {
        if (!data || !data.csrfName) return;
        var input = document.querySelector('input[name="' + data.csrfName + '"]');
        if (input) input.value = data.csrfHash || '';
    }
    function apiPost(url, payload, cb) {
        var csrf = getCsrfPair();
        payload = payload || {};
        payload[csrf.name] = csrf.value;
        $.post(url, payload, function(data) { updateCsrf(data); cb(data || {}); }, 'json');
    }
    function apiGet(url, cb) {
        $.get(url, function(data) { cb(data || {}); }, 'json');
    }

    /* ── Form helpers ───────────────────────────────────────────────── */
    function setMsg(type, text) {
        var $m = $('#inv_msg');
        $m.removeClass('text-success text-danger text-muted');
        $m.addClass(type === 'ok' ? 'text-success' : type === 'err' ? 'text-danger' : 'text-muted');
        $m.text(text || '');
    }

    function getCheckedSpecIds() {
        var ids = [];
        $('.inv-spec-cb:checked').each(function() { ids.push($(this).val()); });
        return ids.join(',');
    }

    function clearForm() {
        $('#inv_id').val('0');
        $('#inv_name,#inv_code,#inv_short_name,#inv_category_name').val('');
        $('.inv-spec-cb').prop('checked', false);
        $('#inv_is_favourite').prop('checked', false);
        $('#inv_form_title').text('New Investigation');
        setMsg('normal', 'Ready.');
    }

    function fillForm(row) {
        row = row || {};
        $('#inv_id').val(row.id || 0);
        $('#inv_name').val(row.name || '');
        $('#inv_code').val(row.code || '');
        $('#inv_short_name').val(row.short_name || '');
        $('#inv_category_name').val(row.category_name || '');
        $('#inv_is_favourite').prop('checked', parseInt(row.is_favourite || 0) === 1);
        $('.inv-spec-cb').prop('checked', false);
        var specIds = (row.spec_ids || '').split(',').map(function(s) { return s.trim(); }).filter(Boolean);
        specIds.forEach(function(sid) {
            $('#inv_spec_' + sid).prop('checked', true);
        });
        $('#inv_form_title').text('Edit: ' + (row.name || ''));
    }

    function buildPayload() {
        return {
            id:            parseInt($('#inv_id').val() || '0', 10),
            name:          ($('#inv_name').val() || '').trim(),
            code:          ($('#inv_code').val() || '').trim(),
            short_name:    ($('#inv_short_name').val() || '').trim(),
            category_name: ($('#inv_category_name').val() || '').trim(),
            spec_ids:      getCheckedSpecIds(),
            is_favourite:  $('#inv_is_favourite').is(':checked') ? 1 : 0
        };
    }

    /* ── DataTable ──────────────────────────────────────────────────── */
    function getAjaxParams() {
        return {
            filter:   ($('#inv_filter_text').val() || '').trim(),
            spec_id:  parseInt($('#inv_filter_spec').val() || '0', 10),
            fav_only: favOnlyMode ? 1 : 0
        };
    }

    function initTable() {
        if (invDataTable) return;
        if (!window.jQuery || typeof $.fn.DataTable !== 'function') return;

        invDataTable = $('#tbl_invest_master').DataTable({
            serverSide:  true,
            processing:  true,
            searching:   false,
            paging:      true,
            pageLength:  25,
            lengthChange: false,
            ajax: {
                url:  '<?= base_url('Opd_prescription/opd_invest_master_data') ?>',
                type: 'GET',
                data: function(d) {
                    var p = getAjaxParams();
                    d.filter   = p.filter;
                    d.spec_id  = p.spec_id;
                    d.fav_only = p.fav_only;
                }
            },
            columns: [
                {
                    data: 'is_favourite', orderable: false, className: 'text-center',
                    render: function(val, type, row) {
                        var isFav = parseInt(val || 0) === 1;
                        return '<button class="btn btn-link p-0 btn-toggle-fav" data-id="' + (row.id || 0) + '" '
                            + 'title="' + (isFav ? 'Remove from favourites' : 'Mark as favourite') + '">'
                            + '<i class="bi bi-star' + (isFav ? '-fill text-warning' : ' text-muted') + '"></i></button>';
                    }
                },
                { data: 'name' },
                { data: 'code', orderable: false },
                { data: 'short_name', defaultContent: '', orderable: false },
                { data: 'category_name', defaultContent: '', orderable: false },
                {
                    data: 'spec_ids', orderable: false,
                    render: function(val) {
                        if (!val) return '<span class="text-muted small">—</span>';
                        return '<span class="small">' + $('<div>').text(val).html() + '</span>';
                    }
                },
                {
                    data: 'id', orderable: false,
                    render: function(id, type, row) {
                        return '<button class="btn btn-outline-primary btn-sm btn-inv-edit" data-id="' + id + '">Edit</button> '
                            + '<button class="btn btn-outline-danger btn-sm btn-inv-del" data-id="' + id + '">Del</button>';
                    }
                }
            ],
            drawCallback: function(settings) {
                var info = settings.json || {};
                $('#inv_total_badge').text((info.recordsTotal || 0) + ' total');
            },
            language: { processing: 'Loading…', zeroRecords: 'No investigations found.' }
        });
    }

    function reloadTable() {
        if (!invDataTable) { initTable(); return; }
        invDataTable.ajax.reload(null, true);
    }

    /* ── Save ───────────────────────────────────────────────────────── */
    function saveInvestigation(payload) {
        if (!payload.name) { setMsg('err', 'Name is required.'); return; }
        apiPost('<?= base_url('Opd_prescription/opd_invest_master_save') ?>', payload, function(data) {
            if (parseInt(data.update || 0) !== 1) {
                setMsg('err', data.error_text || 'Unable to save');
                return;
            }
            $('#inv_id').val(data.insertid || 0);
            setMsg('ok', data.error_text || 'Saved');
            showToast(data.error_text || 'Saved', 'ok');
            reloadTable();
        });
    }

    /* ── Event bindings ─────────────────────────────────────────────── */
    $('#btn_inv_save').on('click', function() {
        saveInvestigation(buildPayload());
    });

    $('#btn_inv_reset').on('click', function() {
        clearForm();
    });

    // Filter text / spec — debounced
    $('#inv_filter_text, #inv_filter_spec').on('input change', function() {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(reloadTable, 350);
    });

    // Fav toggle filter
    $('#btn_inv_filter_fav').on('click', function() {
        favOnlyMode = true;
        $(this).addClass('d-none');
        $('#btn_inv_filter_all').removeClass('d-none');
        reloadTable();
    });
    $('#btn_inv_filter_all').on('click', function() {
        favOnlyMode = false;
        $(this).addClass('d-none');
        $('#btn_inv_filter_fav').removeClass('d-none');
        reloadTable();
    });

    // Row edit
    $(document).on('click', '.btn-inv-edit', function() {
        var id = parseInt($(this).data('id') || '0', 10);
        if (id <= 0) return;
        apiGet('<?= base_url('Opd_prescription/opd_invest_master_get') ?>/' + id, function(data) {
            if (parseInt(data.update || 0) !== 1) { setMsg('err', data.error_text || 'Cannot load'); return; }
            fillForm(data.row || {});
            setMsg('normal', 'Loaded for edit.');
            $('html, body').animate({ scrollTop: $('#inv_name').offset().top - 80 }, 200);
        });
    });

    // Row delete
    $(document).on('click', '.btn-inv-del', function() {
        var id = parseInt($(this).data('id') || '0', 10);
        if (id <= 0) return;
        if (!confirm('Delete this investigation from master?')) return;
        apiPost('<?= base_url('Opd_prescription/opd_invest_master_remove') ?>/' + id, {}, function(data) {
            if (parseInt(data.update || 0) !== 1) { showToast(data.error_text || 'Error', 'err'); return; }
            showToast('Deleted', 'ok');
            reloadTable();
            if (parseInt($('#inv_id').val() || '0', 10) === id) clearForm();
        });
    });

    // Row fav toggle
    $(document).on('click', '.btn-toggle-fav', function() {
        var id = parseInt($(this).data('id') || '0', 10);
        if (id <= 0) return;
        apiPost('<?= base_url('Opd_prescription/opd_invest_master_toggle_fav') ?>/' + id, {}, function(data) {
            if (parseInt(data.update || 0) !== 1) { showToast(data.error_text || 'Error', 'err'); return; }
            reloadTable();
            // sync form star if editing this row
            if (parseInt($('#inv_id').val() || '0', 10) === id) {
                $('#inv_is_favourite').prop('checked', data.is_favourite === 1);
            }
        });
    });

    // CSV export — simple GET with current filter params
    $('#btn_inv_download_csv').on('click', function() {
        var p = getAjaxParams();
        var url = '<?= base_url('Opd_prescription/opd_invest_master_data') ?>?length=9999&start=0'
            + '&filter=' + encodeURIComponent(p.filter)
            + '&spec_id=' + p.spec_id
            + '&fav_only=' + p.fav_only;
        $.get(url, function(data) {
            var rows = (data && data.data) ? data.data : [];
            if (!rows.length) { showToast('No data to export', 'err'); return; }
            var csv = 'Name,Code,Short Name,Category,Specialization IDs,Favourite\n';
            rows.forEach(function(r) {
                csv += [r.name, r.code, r.short_name || '', r.category_name || '', r.spec_ids || '', r.is_favourite || 0]
                    .map(function(v) { return '"' + String(v).replace(/"/g, '""') + '"'; })
                    .join(',') + '\n';
            });
            var blob = new Blob([csv], { type: 'text/csv' });
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'investigation_master.csv';
            a.click();
        }, 'json');
    });

    /* ── Init ───────────────────────────────────────────────────────── */
    $(function() {
        initTable();
    });
})();
</script>

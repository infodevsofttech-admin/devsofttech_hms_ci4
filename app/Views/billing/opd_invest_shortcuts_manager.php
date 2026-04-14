<section class="container-fluid py-3">
    <div class="d-flex align-items-center gap-2 mb-3">
        <h5 class="mb-0"><i class="bi bi-grid-3x2 me-1"></i> Investigation Shortcuts Manager</h5>
        <span class="badge bg-secondary" id="ism_badge">—</span>
        <div class="ms-auto d-flex gap-2">
            <button type="button" class="btn btn-success btn-sm" id="btn_ism_new_group">
                <i class="bi bi-plus-circle"></i> New Group
            </button>
        </div>
    </div>

    <!-- Live Preview -->
    <div class="card mb-3 border-info">
        <div class="card-header py-1 bg-info bg-opacity-10">
            <small class="fw-semibold text-info-emphasis"><i class="bi bi-eye me-1"></i>Panel Preview (how it will look on OPD form)</small>
        </div>
        <div class="card-body py-2" id="ism_preview">
            <span class="text-muted small">Loading…</span>
        </div>
    </div>

    <div class="row g-3">
        <!-- Groups (left) -->
        <div class="col-md-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between py-2">
                    <strong>Groups in Panel</strong>
                    <span class="badge bg-primary" id="ism_in_panel_count">0</span>
                </div>
                <div class="card-body p-2" id="ism_groups_container" style="max-height:600px;overflow-y:auto;">
                    <div class="text-muted text-center py-3 small">Loading…</div>
                </div>
            </div>
        </div>

        <!-- Not in Panel (right) -->
        <div class="col-md-5">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between py-2">
                    <strong>Not in Panel</strong>
                    <span class="badge bg-secondary" id="ism_not_in_panel_count">0</span>
                </div>
                <div class="card-header py-1 border-top-0">
                    <input type="text" id="ism_search_unassigned" class="form-control form-control-sm" placeholder="Search tests…">
                </div>
                <div class="card-body p-0" style="max-height:560px;overflow-y:auto;">
                    <div id="ism_unassigned_list">
                        <div class="text-muted text-center py-3 small">Loading…</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- New / Rename Group Modal -->
<div class="modal fade" id="ismGroupModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="ismGroupModalTitle">New Group</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ism_gmodal_old_name" value="">
                <label class="form-label small">Group Name <span class="text-danger">*</span></label>
                <input type="text" id="ism_gmodal_name" class="form-control form-control-sm" maxlength="80" placeholder="e.g. Blood Tests, Urine…">
                <div class="small mt-1 text-danger" id="ism_gmodal_msg"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="btn_ism_gmodal_ok">Create</button>
            </div>
        </div>
    </div>
</div>

<!-- Assign Test to Group Modal -->
<div class="modal fade" id="ismAssignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Assign to Group</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ism_assign_id" value="0">
                <p class="mb-2">Test: <strong id="ism_assign_name">—</strong></p>

                <div class="mb-2">
                    <label class="form-label small">Group <span class="text-danger">*</span></label>
                    <select id="ism_assign_group_sel" class="form-select form-select-sm">
                        <option value="">— New group (type below) —</option>
                    </select>
                </div>
                <div class="mb-2" id="ism_assign_newgrp_wrap">
                    <input type="text" id="ism_assign_new_group" class="form-control form-control-sm" maxlength="80" placeholder="New group name…">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Sort Order <small class="text-muted">(lower = first)</small></label>
                    <input type="number" id="ism_assign_sort" class="form-control form-control-sm" value="0" min="0">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="btn_ism_assign_ok">Assign</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var allTests  = [];
    var csrfName  = '<?= csrf_token() ?>';
    var csrfHash  = '<?= csrf_hash() ?>';
    var COLOR_CLS = ['btn-secondary', 'btn-primary', 'btn-success', 'btn-info', 'btn-danger', 'btn-warning'];

    function groupColor(idx) { return COLOR_CLS[idx % COLOR_CLS.length]; }

    /* ── XHR helpers ────────────────────────────────────────── */
    function apiGet(url, cb) {
        $.getJSON(url).done(function (d) { cb(null, d); }).fail(function (x) { cb('HTTP ' + x.status, null); });
    }
    function apiPost(url, data, cb) {
        data[csrfName] = csrfHash;
        $.post(url, data, function (d) {
            if (d && d.csrfHash) { csrfHash = d.csrfHash; if (d.csrfName) csrfName = d.csrfName; }
            cb(d);
        }).fail(function (x) { cb({ update: 0, error_text: 'HTTP ' + x.status }); });
    }

    /* ── Data helpers ───────────────────────────────────────── */
    function computeGroups() {
        var grp = {}, order = [];
        allTests.forEach(function (t) {
            var g = (t.short_name || '').trim();
            if (!g) return;
            if (!grp[g]) { grp[g] = []; order.push(g); }
            grp[g].push(t);
        });
        order.sort();
        order.forEach(function (g) {
            grp[g].sort(function (a, b) { return (a.sort_id || 0) - (b.sort_id || 0); });
        });
        return { grp: grp, order: order };
    }

    function getUnassigned(filter) {
        var f = (filter || '').toLowerCase();
        return allTests.filter(function (t) {
            if ((t.short_name || '').trim()) return false;
            return !f || (t.name || '').toLowerCase().indexOf(f) !== -1 || (t.code || '').toLowerCase().indexOf(f) !== -1;
        });
    }

    /* ── Render: preview ────────────────────────────────────── */
    function renderPreview() {
        var res = computeGroups();
        if (!res.order.length) {
            $('#ism_preview').html('<span class="text-muted small">No groups yet.</span>');
            return;
        }
        var html = '';
        res.order.forEach(function (g, ci) {
            html += '<br>';
            res.grp[g].forEach(function (t) {
                html += '<button type="button" class="btn btn-sm ' + groupColor(ci) + '" style="margin:2px;" disabled>' + esc(t.name) + '</button>';
            });
        });
        $('#ism_preview').html('<div>' + html + '</div>');
    }

    /* ── Render: groups ─────────────────────────────────────── */
    function renderGroups() {
        var res = computeGroups();
        var inPanel = allTests.filter(function (t) { return (t.short_name || '').trim(); }).length;
        $('#ism_in_panel_count').text(inPanel);

        if (!res.order.length) {
            $('#ism_groups_container').html(
                '<div class="text-muted text-center py-4 small">' +
                'No groups defined.<br>Click <strong>+ New Group</strong>, then assign tests from the right panel.' +
                '</div>'
            );
            return;
        }

        var html = '';
        res.order.forEach(function (g, ci) {
            var bsCls = groupColor(ci);
            html += '<div class="card mb-2">';
            html += '<div class="card-header py-1 d-flex align-items-center gap-2">';
            html += '<span class="badge ' + bsCls + '">' + esc(g) + '</span>';
            html += '<small class="text-muted">(' + res.grp[g].length + ' tests)</small>';
            html += '<div class="ms-auto d-flex gap-1">';
            html += '<button class="btn btn-outline-secondary btn-sm py-0 px-2 btn-ism-rename" data-group="' + esc(g) + '">Rename</button>';
            html += '<button class="btn btn-outline-danger btn-sm py-0 px-2 btn-ism-del-group" data-group="' + esc(g) + '">Remove Group</button>';
            html += '</div></div>';
            html += '<div class="card-body p-1">';
            html += '<table class="table table-sm table-hover mb-0">';
            html += '<thead class="table-light"><tr><th>Test Name</th><th width="85">Sort</th><th width="90">Action</th></tr></thead><tbody>';
            res.grp[g].forEach(function (t) {
                html += '<tr>';
                html += '<td class="align-middle small">' + esc(t.name) + '</td>';
                html += '<td><input type="number" class="form-control form-control-sm ism-sort-input" value="' + (t.sort_id || 0) + '" min="0" style="width:70px;" data-id="' + t.id + '"></td>';
                html += '<td><button class="btn btn-outline-danger btn-sm py-0 btn-ism-remove" data-id="' + t.id + '">Remove</button></td>';
                html += '</tr>';
            });
            html += '</tbody></table></div></div>';
        });
        $('#ism_groups_container').html(html);
    }

    /* ── Render: unassigned ─────────────────────────────────── */
    function renderUnassigned() {
        var filter = $('#ism_search_unassigned').val();
        var list   = getUnassigned(filter);
        $('#ism_not_in_panel_count').text(allTests.filter(function (t) { return !(t.short_name || '').trim(); }).length);

        if (!list.length) {
            $('#ism_unassigned_list').html('<div class="text-muted text-center py-3 small">All tests are assigned to groups.</div>');
            return;
        }
        var html = '<table class="table table-sm table-hover mb-0"><tbody>';
        list.forEach(function (t) {
            html += '<tr>';
            html += '<td class="align-middle small">' + esc(t.name);
            if (t.code) html += ' <span class="text-muted">(' + esc(t.code) + ')</span>';
            html += '</td>';
            html += '<td width="90" class="align-middle"><button class="btn btn-outline-primary btn-sm py-0 btn-ism-assign" data-id="' + t.id + '" data-name="' + esc(t.name) + '">+ Group</button></td>';
            html += '</tr>';
        });
        html += '</tbody></table>';
        $('#ism_unassigned_list').html(html);
    }

    function renderAll() {
        renderGroups();
        renderUnassigned();
        renderPreview();
        syncGroupSelectOptions();
    }

    /* ── Sync group dropdown options ────────────────────────── */
    function syncGroupSelectOptions() {
        var res = computeGroups();
        var $sel = $('#ism_assign_group_sel');
        var cur  = $sel.val();
        $sel.empty().append('<option value="">— New group (type below) —</option>');
        res.order.forEach(function (g) {
            $sel.append('<option value="' + esc(g) + '"' + (g === cur ? ' selected' : '') + '>' + esc(g) + '</option>');
        });
        $sel.trigger('change');
    }

    /* ── Save one item ──────────────────────────────────────── */
    var _saveTimers = {};
    function saveItem(id, shortName, sortId, cb) {
        apiPost('<?= base_url('Opd_prescription/opd_invest_shortcuts_save_item') ?>', {
            id:         id,
            short_name: shortName,
            sort_id:    sortId
        }, cb || function () {});
    }
    function debouncedSave(id, shortName, sortId) {
        clearTimeout(_saveTimers[id]);
        _saveTimers[id] = setTimeout(function () {
            allTests.forEach(function (t) { if (t.id == id) { t.short_name = shortName; t.sort_id = sortId; } });
            saveItem(id, shortName, sortId);
        }, 700);
    }

    /* ── HTML escape ────────────────────────────────────────── */
    function esc(s) { return $('<span>').text(s || '').html(); }

    /* ── Event: sort input change ───────────────────────────── */
    $(document).on('change', '.ism-sort-input', function () {
        var id  = parseInt($(this).data('id'), 10);
        var val = Math.max(0, parseInt($(this).val(), 10) || 0);
        var t   = allTests.find(function (x) { return x.id === id; });
        if (!t) return;
        t.sort_id = val;
        debouncedSave(id, t.short_name, val);
        renderGroups();  // re-sort displayed list
        renderPreview();
    });

    /* ── Event: remove test from panel ─────────────────────── */
    $(document).on('click', '.btn-ism-remove', function () {
        var id = parseInt($(this).data('id'), 10);
        var t  = allTests.find(function (x) { return x.id === id; });
        if (!t) return;
        saveItem(id, '', t.sort_id, function (d) {
            if ((d.update || 0) !== 1) { alert(d.error_text || 'Failed'); return; }
            t.short_name = '';
            renderAll();
        });
    });

    /* ── Event: rename group ────────────────────────────────── */
    $(document).on('click', '.btn-ism-rename', function () {
        var oldName = $(this).data('group');
        $('#ism_gmodal_old_name').val(oldName);
        $('#ism_gmodal_name').val(oldName);
        $('#ism_gmodal_msg').text('');
        $('#ismGroupModalTitle').text('Rename Group');
        $('#btn_ism_gmodal_ok').text('Rename');
        bootstrap.Modal.getOrCreateInstance('#ismGroupModal').show();
    });

    /* ── Event: delete group ────────────────────────────────── */
    $(document).on('click', '.btn-ism-del-group', function () {
        var g = $(this).data('group');
        if (!confirm('Remove all tests from group "' + g + '"?\n\nTests will move to "Not in Panel".')) return;
        var tasks = allTests.filter(function (t) { return (t.short_name || '').trim() === g; });
        var done  = 0;
        if (!tasks.length) return;
        tasks.forEach(function (t) {
            saveItem(t.id, '', t.sort_id, function (d) {
                if ((d.update || 0) === 1) t.short_name = '';
                if (++done === tasks.length) renderAll();
            });
        });
    });

    /* ── New Group button ───────────────────────────────────── */
    $('#btn_ism_new_group').on('click', function () {
        $('#ism_gmodal_old_name').val('');
        $('#ism_gmodal_name').val('');
        $('#ism_gmodal_msg').text('');
        $('#ismGroupModalTitle').text('New Group');
        $('#btn_ism_gmodal_ok').text('Create');
        bootstrap.Modal.getOrCreateInstance('#ismGroupModal').show();
    });

    /* ── Group modal OK ─────────────────────────────────────── */
    $('#btn_ism_gmodal_ok').on('click', function () {
        var newName = $('#ism_gmodal_name').val().trim();
        var oldName = $('#ism_gmodal_old_name').val().trim();
        if (!newName) { $('#ism_gmodal_msg').text('Group name is required.'); return; }

        bootstrap.Modal.getOrCreateInstance('#ismGroupModal').hide();

        if (oldName && oldName !== newName) {
            // Rename: update all tests in oldName group
            var tasks = allTests.filter(function (t) { return (t.short_name || '').trim() === oldName; });
            if (!tasks.length) { renderAll(); return; }
            var done = 0;
            tasks.forEach(function (t) {
                saveItem(t.id, newName, t.sort_id, function (d) {
                    if ((d.update || 0) === 1) t.short_name = newName;
                    if (++done === tasks.length) renderAll();
                });
            });
        } else if (!oldName) {
            // New group: add to dropdown so user can immediately assign tests to it
            var res = computeGroups();
            if (!res.grp[newName]) {
                // Pre-populate dropdown with new name so assign modal shows it
                $('#ism_assign_group_sel').append('<option value="' + esc(newName) + '" selected>' + esc(newName) + '</option>');
            }
            renderAll();
        }
    });

    /* ── Assign test to group ───────────────────────────────── */
    $(document).on('click', '.btn-ism-assign', function () {
        var id   = parseInt($(this).data('id'), 10);
        var name = $(this).data('name');
        $('#ism_assign_id').val(id);
        $('#ism_assign_name').text(name);
        $('#ism_assign_sort').val(0);
        syncGroupSelectOptions();
        bootstrap.Modal.getOrCreateInstance('#ismAssignModal').show();
    });

    $('#ism_assign_group_sel').on('change', function () {
        var v = $(this).val();
        if (v === '') {
            $('#ism_assign_newgrp_wrap').show();
        } else {
            $('#ism_assign_newgrp_wrap').hide();
            $('#ism_assign_new_group').val('');
        }
    });

    $('#btn_ism_assign_ok').on('click', function () {
        var id       = parseInt($('#ism_assign_id').val(), 10);
        var selGroup = $('#ism_assign_group_sel').val();
        var newGroup = $('#ism_assign_new_group').val().trim();
        var group    = selGroup || newGroup;
        var sortId   = parseInt($('#ism_assign_sort').val(), 10) || 0;

        if (!group) { alert('Please select an existing group or enter a new group name.'); return; }

        bootstrap.Modal.getOrCreateInstance('#ismAssignModal').hide();

        saveItem(id, group, sortId, function (d) {
            if ((d.update || 0) !== 1) { alert(d.error_text || 'Failed to assign'); return; }
            var t = allTests.find(function (x) { return x.id === id; });
            if (t) { t.short_name = group; t.sort_id = sortId; }
            renderAll();
        });
    });

    /* ── Search unassigned ──────────────────────────────────── */
    $('#ism_search_unassigned').on('input', function () { renderUnassigned(); });

    /* ── Init ───────────────────────────────────────────────── */
    apiGet('<?= base_url('Opd_prescription/opd_invest_shortcuts_all') ?>', function (err, d) {
        allTests = (d && d.rows) ? d.rows : [];
        $('#ism_badge').text(allTests.length + ' investigations');
        renderAll();
    });
})();
</script>

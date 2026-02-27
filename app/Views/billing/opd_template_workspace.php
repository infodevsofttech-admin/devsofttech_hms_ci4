<section class="section">
    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Clinical Templates Workspace</strong>
                    <small class="text-muted">Add, edit and monitor newly added templates</small>
                </div>
                <div class="card-body">
                    <?= csrf_field() ?>
                    <input type="hidden" id="tpl_id" value="0">

                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <label class="form-label mb-1">Section</label>
                            <select id="tpl_section" class="form-select form-select-sm">
                                <option value="finding_examinations">Examination</option>
                                <option value="diagnosis">Diagnosis</option>
                                <option value="provisional_diagnosis">Provisional Diagnosis</option>
                                <option value="prescriber_remarks">Consult</option>
                                <option value="advice">Advice</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">Scope</label>
                            <select id="tpl_scope" class="form-select form-select-sm">
                                <option value="doctor" selected>Doctor</option>
                                <option value="master">Master</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="tpl_doctor_wrap">
                            <label class="form-label mb-1">Doctor Name</label>
                            <select id="tpl_doctor_id" class="form-select form-select-sm">
                                <option value="">Select doctor</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">Template Name</label>
                            <input type="text" id="tpl_name" maxlength="100" class="form-control form-control-sm" placeholder="Template name">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label mb-1">Template Text</label>
                        <textarea id="tpl_text" rows="5" class="form-control form-control-sm" placeholder="Write clinical template text..."></textarea>
                    </div>

                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_tpl_save">Save New</button>
                        <button type="button" class="btn btn-warning btn-sm" id="btn_tpl_update" style="display:none;">Update</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_tpl_reset">Reset</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn_tpl_refresh">Refresh Monitor</button>
                    </div>

                    <div class="border rounded p-2 mb-2">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label mb-1">Monitor Section</label>
                                <select id="flt_section" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="finding_examinations">Examination</option>
                                    <option value="diagnosis">Diagnosis</option>
                                    <option value="provisional_diagnosis">Provisional Diagnosis</option>
                                    <option value="prescriber_remarks">Consult</option>
                                    <option value="advice">Advice</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1">Monitor Scope</label>
                                <select id="flt_scope" class="form-select form-select-sm">
                                    <option value="">Master + My templates</option>
                                    <option value="master">Master only</option>
                                    <option value="doctor">Doctor only</option>
                                </select>
                            </div>
                            <div class="col-md-2" id="flt_doctor_wrap" style="display:none;">
                                <label class="form-label mb-1">Doctor</label>
                                <select id="flt_doctor_id" class="form-select form-select-sm">
                                    <option value="">All doctors</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btn_tpl_apply_filter">Apply Filter</button>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded p-2 mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="small">Usage Analytics (Doctor-wise)</strong>
                            <small class="text-muted" id="tpl_analytics_summary">No usage data yet.</small>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-8">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" id="tbl_tpl_usage_top">
                                        <thead>
                                            <tr>
                                                <th>Template</th>
                                                <th width="160">Section</th>
                                                <th width="120">Scope</th>
                                                <th width="90">Uses</th>
                                                <th width="170">Last Used</th>
                                            </tr>
                                        </thead>
                                        <tbody><tr><td colspan="5" class="text-muted">No usage data yet.</td></tr></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0" id="tbl_tpl_usage_section">
                                        <thead>
                                            <tr>
                                                <th>Section</th>
                                                <th width="80">Templates</th>
                                                <th width="80">Uses</th>
                                            </tr>
                                        </thead>
                                        <tbody><tr><td colspan="3" class="text-muted">No section data.</td></tr></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="tpl_msg" class="small text-muted mb-2">Use this workspace to maintain templates.</div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle" id="tbl_tpl_monitor">
                            <thead>
                                <tr>
                                    <th width="60">ID</th>
                                    <th width="120">Scope</th>
                                    <th width="220">Section</th>
                                    <th width="240">Name</th>
                                    <th>Text</th>
                                    <th width="170">Updated</th>
                                    <th width="90">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="7" class="text-muted">No template data yet.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    function getCsrfPair() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (!input) {
            return { name: '<?= csrf_token() ?>', value: '<?= csrf_hash() ?>' };
        }
        return { name: input.getAttribute('name'), value: input.value };
    }

    function updateCsrf(data) {
        if (!data || !data.csrfName || !data.csrfHash) {
            return;
        }
        var input = document.querySelector('input[name="' + data.csrfName + '"]');
        if (input) {
            input.value = data.csrfHash;
        }
    }

    function apiPost(url, payload, cb) {
        var csrf = getCsrfPair();
        payload = payload || {};
        payload[csrf.name] = csrf.value;
        $.post(url, payload, function(data) {
            updateCsrf(data);
            cb(data || {});
        }, 'json');
    }

    function apiGet(url, cb) {
        $.get(url, function(data) {
            cb(data || {});
        }, 'json');
    }

    function sectionLabel(key) {
        var map = {
            finding_examinations: 'Examination',
            diagnosis: 'Diagnosis',
            provisional_diagnosis: 'Provisional Diagnosis',
            prescriber_remarks: 'Consult',
            advice: 'Advice'
        };
        return map[key] || key || '';
    }

    function toggleDoctorScopeUI() {
        var scope = ($('#tpl_scope').val() || '').toString();
        if (scope === 'doctor') {
            $('#tpl_doctor_wrap').show();
        } else {
            $('#tpl_doctor_wrap').hide();
            $('#tpl_doctor_id').val('');
        }
    }

    function toggleDoctorFilterUI() {
        var scope = ($('#flt_scope').val() || '').toString();
        if (scope === 'doctor') {
            $('#flt_doctor_wrap').show();
        } else {
            $('#flt_doctor_wrap').hide();
            $('#flt_doctor_id').val('');
        }
    }

    function loadDoctors() {
        apiGet('<?= base_url('Opd_prescription/clinical_template_doctors') ?>', function(data) {
            var rows = (data && data.rows) ? data.rows : [];
            var $tpl = $('#tpl_doctor_id');
            var $flt = $('#flt_doctor_id');
            $tpl.html('<option value="">Select doctor</option>');
            $flt.html('<option value="">All doctors</option>');

            rows.forEach(function(row) {
                var id = parseInt(row.id || '0', 10);
                var name = (row.name || '').toString();
                if (id <= 0 || !name) {
                    return;
                }
                var escId = $('<div>').text(String(id)).html();
                var escName = $('<div>').text(name).html();
                $tpl.append('<option value="' + escId + '">' + escName + '</option>');
                $flt.append('<option value="' + escId + '">' + escName + '</option>');
            });
        });
    }

    function setMsg(type, text) {
        var $msg = $('#tpl_msg');
        $msg.removeClass('text-success text-danger text-muted');
        if (type === 'ok') {
            $msg.addClass('text-success');
        } else if (type === 'err') {
            $msg.addClass('text-danger');
        } else {
            $msg.addClass('text-muted');
        }
        $msg.text(text || '');
    }

    function resetEditor() {
        $('#tpl_id').val('0');
        $('#tpl_section').val('finding_examinations');
        $('#tpl_scope').val('doctor');
        $('#tpl_doctor_id').val('');
        $('#tpl_name').val('');
        $('#tpl_text').val('');
        $('#btn_tpl_save').show();
        $('#btn_tpl_update').hide();
        toggleDoctorScopeUI();
    }

    function loadMonitor() {
        var q = 'section=' + encodeURIComponent($('#flt_section').val() || '')
            + '&scope=' + encodeURIComponent($('#flt_scope').val() || '')
            + '&doctor_id=' + encodeURIComponent($('#flt_doctor_id').val() || '');

        apiGet('<?= base_url('Opd_prescription/clinical_template_monitor') ?>?' + q, function(data) {
            var rows = (data && data.rows) ? data.rows : [];
            var $tb = $('#tbl_tpl_monitor tbody');
            $tb.empty();

            if (!rows.length) {
                $tb.html('<tr><td colspan="7" class="text-muted">No template data found.</td></tr>');
                return;
            }

            rows.forEach(function(row) {
                var scope = (row.scope_label || '').toString();
                var section = sectionLabel((row.section_key || '').toString());
                var name = (row.template_name || '').toString();
                var text = (row.template_text || '').toString();
                var updated = (row.updated_at || row.created_at || '').toString();
                var doctorName = (row.doctor_name || '').toString();
                var scopeLabel = scope + (scope === 'Doctor' && doctorName ? (' - ' + doctorName) : '');

                $tb.append('<tr>'
                    + '<td>' + (row.id || 0) + '</td>'
                    + '<td>' + $('<div>').text(scopeLabel).html() + '</td>'
                    + '<td>' + $('<div>').text(section).html() + '</td>'
                    + '<td>' + $('<div>').text(name).html() + '</td>'
                    + '<td style="white-space:pre-wrap;">' + $('<div>').text(text).html() + '</td>'
                    + '<td>' + $('<div>').text(updated).html() + '</td>'
                    + '<td><button type="button" class="btn btn-sm btn-outline-primary btn-edit-tpl" data-id="' + (row.id || 0) + '" data-section="' + $('<div>').text(row.section_key || '').html() + '" data-scope="' + (scope === 'Master' ? 'master' : 'doctor') + '" data-docid="' + (row.doc_id || 0) + '" data-name="' + $('<div>').text(name).html() + '" data-text="' + $('<div>').text(text).html() + '">Edit</button></td>'
                    + '</tr>');
            });
        });
    }

    function loadAnalytics() {
        var q = 'section=' + encodeURIComponent($('#flt_section').val() || '')
            + '&scope=' + encodeURIComponent($('#flt_scope').val() || '')
            + '&doctor_id=' + encodeURIComponent($('#flt_doctor_id').val() || '');

        apiGet('<?= base_url('Opd_prescription/clinical_template_usage_analytics') ?>?' + q, function(data) {
            var topRows = (data && data.top_rows) ? data.top_rows : [];
            var sectionRows = (data && data.section_rows) ? data.section_rows : [];
            var summary = (data && data.summary) ? data.summary : {};

            var $topBody = $('#tbl_tpl_usage_top tbody');
            $topBody.empty();
            if (!topRows.length) {
                $topBody.html('<tr><td colspan="5" class="text-muted">No usage data yet.</td></tr>');
            } else {
                topRows.forEach(function(row) {
                    var name = (row.template_name || '').toString();
                    var section = sectionLabel((row.section_key || '').toString());
                    var scope = parseInt(row.doc_id || '0', 10) === 0 ? 'Master' : 'Doctor';
                    var uses = parseInt(row.use_count || '0', 10);
                    var lastUsed = (row.last_used_at || '').toString();

                    $topBody.append('<tr>'
                        + '<td>' + $('<div>').text(name).html() + '</td>'
                        + '<td>' + $('<div>').text(section).html() + '</td>'
                        + '<td>' + $('<div>').text(scope).html() + '</td>'
                        + '<td>' + uses + '</td>'
                        + '<td>' + $('<div>').text(lastUsed).html() + '</td>'
                        + '</tr>');
                });
            }

            var $sectionBody = $('#tbl_tpl_usage_section tbody');
            $sectionBody.empty();
            if (!sectionRows.length) {
                $sectionBody.html('<tr><td colspan="3" class="text-muted">No section data.</td></tr>');
            } else {
                sectionRows.forEach(function(row) {
                    $sectionBody.append('<tr>'
                        + '<td>' + $('<div>').text(sectionLabel((row.section_key || '').toString())).html() + '</td>'
                        + '<td>' + parseInt(row.template_count || '0', 10) + '</td>'
                        + '<td>' + parseInt(row.total_uses || '0', 10) + '</td>'
                        + '</tr>');
                });
            }

            var totalUses = parseInt(summary.total_uses || '0', 10);
            var usedTemplates = parseInt(summary.used_templates || '0', 10);
            $('#tpl_analytics_summary').text('Used templates: ' + usedTemplates + ' | Total uses: ' + totalUses);
        });
    }

    $('#btn_tpl_save').on('click', function() {
        var section = ($('#tpl_section').val() || '').toString();
        var scope = ($('#tpl_scope').val() || 'doctor').toString();
        var doctorId = parseInt($('#tpl_doctor_id').val() || '0', 10);
        var name = ($('#tpl_name').val() || '').toString().trim();
        var text = ($('#tpl_text').val() || '').toString().trim();

        if (!section || !name || !text) {
            setMsg('err', 'Section, name and text are required.');
            return;
        }
        if (scope === 'doctor' && doctorId <= 0) {
            setMsg('err', 'Select doctor name for doctor scope.');
            return;
        }

        apiPost('<?= base_url('Opd_prescription/section_template_save') ?>', {
            section: section,
            template_name: name,
            template_text: text,
            template_scope: scope,
            template_doc_id: doctorId
        }, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to save template.');
                return;
            }
            setMsg('ok', data.error_text || 'Template saved.');
            resetEditor();
            loadMonitor();
            loadAnalytics();
        });
    });

    $('#btn_tpl_update').on('click', function() {
        var id = parseInt($('#tpl_id').val() || '0', 10);
        var section = ($('#tpl_section').val() || '').toString();
        var scope = ($('#tpl_scope').val() || 'doctor').toString();
        var doctorId = parseInt($('#tpl_doctor_id').val() || '0', 10);
        var name = ($('#tpl_name').val() || '').toString().trim();
        var text = ($('#tpl_text').val() || '').toString().trim();

        if (id <= 0 || !section || !name || !text) {
            setMsg('err', 'Template id, section, name and text are required for update.');
            return;
        }
        if (scope === 'doctor' && doctorId <= 0) {
            setMsg('err', 'Select doctor name for doctor scope.');
            return;
        }

        apiPost('<?= base_url('Opd_prescription/clinical_template_update') ?>', {
            id: id,
            section: section,
            template_scope: scope,
            template_doc_id: doctorId,
            template_name: name,
            template_text: text
        }, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to update template.');
                return;
            }
            setMsg('ok', data.error_text || 'Template updated.');
            resetEditor();
            loadMonitor();
            loadAnalytics();
        });
    });

    $(document).on('click', '.btn-edit-tpl', function() {
        $('#tpl_id').val($(this).data('id') || 0);
        $('#tpl_section').val(($(this).data('section') || '').toString());
        $('#tpl_scope').val(($(this).data('scope') || 'doctor').toString());
        $('#tpl_doctor_id').val(String($(this).data('docid') || ''));
        $('#tpl_name').val(($(this).data('name') || '').toString());
        $('#tpl_text').val(($(this).data('text') || '').toString());

        $('#btn_tpl_save').hide();
        $('#btn_tpl_update').show();
        toggleDoctorScopeUI();
        setMsg('normal', 'Edit mode enabled. Update and monitor changes below.');
    });

    $('#btn_tpl_reset').on('click', function() {
        resetEditor();
        setMsg('normal', 'Editor reset.');
    });

    $('#btn_tpl_refresh, #btn_tpl_apply_filter').on('click', function() {
        loadMonitor();
        loadAnalytics();
    });

    $('#tpl_scope').on('change', function() {
        toggleDoctorScopeUI();
    });

    $('#flt_scope').on('change', function() {
        toggleDoctorFilterUI();
    });

    loadDoctors();
    toggleDoctorFilterUI();
    resetEditor();
    loadMonitor();
    loadAnalytics();
})();
</script>

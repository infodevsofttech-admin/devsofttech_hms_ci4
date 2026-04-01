<section class="container-fluid py-3">
    <div class="d-flex align-items-center gap-2 mb-3">
        <h5 class="mb-0"><i class="bi bi-collection me-1"></i> Investigation Profile Master</h5>
        <span class="badge bg-secondary" id="inv_profile_total_badge">—</span>
    </div>

    <div class="row g-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 align-items-center">
                    <input type="text" id="inv_profile_filter_text" class="form-control form-control-sm" style="max-width:260px;" placeholder="Search profile / test...">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="tbl_inv_profile_master" class="table table-bordered table-sm table-hover mb-0" style="width:100%;">
                            <thead class="table-light">
                                <tr>
                                    <th>Profile</th>
                                    <th width="80">Code</th>
                                    <th width="80">Tests</th>
                                    <th>Investigation Names</th>
                                    <th width="100">Action</th>
                                </tr>
                            </thead>
                            <tbody><tr><td colspan="5" class="text-center text-muted py-3">Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><strong id="inv_profile_form_title">New Profile</strong></div>
                <div class="card-body">
                    <?= csrf_field() ?>
                    <input type="hidden" id="inv_profile_code" value="0">

                    <div class="mb-2">
                        <label class="form-label">Profile Name</label>
                        <input type="text" id="inv_profile_name_master" class="form-control form-control-sm" placeholder="e.g. Fever Panel, Pre-Op Panel">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Investigations</label>
                        <select id="inv_profile_tests_select" class="form-select form-select-sm" multiple="multiple" style="width:100%;"></select>
                        <div class="small text-muted mt-1">Search and select multiple investigations.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_inv_profile_save">Save</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_inv_profile_reset">Reset</button>
                    </div>

                    <div class="small mt-2 text-muted" id="inv_profile_msg">Ready.</div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    var profileTable = null;
    var filterTimer = null;

    function getCsrfPair() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        return input
            ? { name: input.getAttribute('name'), value: input.value }
            : { name: '<?= csrf_token() ?>', value: '<?= csrf_hash() ?>' };
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
        $.get(url, function(data) { cb(data || {}); }, 'json');
    }

    function setMsg(type, text) {
        var $msg = $('#inv_profile_msg');
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

    function clearForm() {
        $('#inv_profile_code').val('0');
        $('#inv_profile_name_master').val('');
        $('#inv_profile_tests_select').val(null).trigger('change');
        $('#inv_profile_form_title').text('New Profile');
        setMsg('normal', 'Ready.');
    }

    function fillForm(row) {
        row = row || {};
        $('#inv_profile_code').val(row.profile_code || 0);
        $('#inv_profile_name_master').val(row.profile_name || '');
        var values = [];
        (row.tests || []).forEach(function(test) {
            var code = (test.code || '').toString();
            var name = (test.name || '').toString();
            if (!code || !name) {
                return;
            }
            if (!$('#inv_profile_tests_select option[value="' + code + '"]').length) {
                $('#inv_profile_tests_select').append(new Option(name + ' [' + code + ']', code, true, true));
            }
            values.push(code);
        });
        $('#inv_profile_tests_select').val(values).trigger('change');
        $('#inv_profile_form_title').text('Edit: ' + (row.profile_name || ''));
    }

    function buildPayload() {
        return {
            profile_code: parseInt($('#inv_profile_code').val() || '0', 10),
            profile_name: ($('#inv_profile_name_master').val() || '').trim(),
            investigation_codes: ($('#inv_profile_tests_select').val() || []).join(',')
        };
    }

    function initInvestigationMultiSelect() {
        var $select = $('#inv_profile_tests_select');
        if (!$.fn || !$.fn.select2 || !$select.length) {
            return;
        }
        $select.select2({
            width: '100%',
            placeholder: 'Search investigation...',
            ajax: {
                delay: 250,
                transport: function(params, success, failure) {
                    var term = (params.data && params.data.term) ? params.data.term : '';
                    $.ajax({
                        url: '<?= base_url('Opd_prescription/investigation_search') ?>',
                        dataType: 'json',
                        data: { q: term },
                        success: success,
                        error: failure
                    });
                },
                processResults: function(data) {
                    var rows = (data && data.rows) ? data.rows : [];
                    return {
                        results: rows.map(function(row) {
                            var name = (row.name || '').toString();
                            var code = (row.code || '').toString();
                            return {
                                id: code,
                                text: name + (code ? ' [' + code + ']' : '')
                            };
                        })
                    };
                }
            }
        });
    }

    function initTable() {
        if (profileTable || !$.fn || typeof $.fn.DataTable !== 'function') {
            return;
        }
        profileTable = $('#tbl_inv_profile_master').DataTable({
            serverSide: true,
            processing: true,
            searching: false,
            paging: true,
            pageLength: 25,
            lengthChange: false,
            ajax: {
                url: '<?= base_url('Opd_prescription/opd_invest_profile_master_data') ?>',
                type: 'GET',
                data: function(d) {
                    d.filter = ($('#inv_profile_filter_text').val() || '').trim();
                }
            },
            columns: [
                { data: 'profile_name' },
                { data: 'profile_code' },
                { data: 'test_count', orderable: false },
                { data: 'test_names', orderable: false },
                {
                    data: 'profile_code', orderable: false,
                    render: function(code) {
                        return '<button type="button" class="btn btn-outline-primary btn-sm btn-inv-profile-edit" data-code="' + code + '">Edit</button> '
                            + '<button type="button" class="btn btn-outline-danger btn-sm btn-inv-profile-del" data-code="' + code + '">Del</button>';
                    }
                }
            ],
            drawCallback: function(settings) {
                var json = settings.json || {};
                $('#inv_profile_total_badge').text((json.recordsTotal || 0) + ' total');
            }
        });
    }

    function reloadTable() {
        if (profileTable) {
            profileTable.ajax.reload(null, true);
        }
    }

    $('#btn_inv_profile_save').on('click', function() {
        var payload = buildPayload();
        if (!payload.profile_name) {
            setMsg('err', 'Profile name is required.');
            return;
        }
        if (!payload.investigation_codes) {
            setMsg('err', 'Select at least one investigation.');
            return;
        }
        apiPost('<?= base_url('Opd_prescription/opd_invest_profile_master_save') ?>', payload, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to save profile');
                return;
            }
            $('#inv_profile_code').val(data.insertid || 0);
            setMsg('ok', data.error_text || 'Saved');
            reloadTable();
        });
    });

    $('#btn_inv_profile_reset').on('click', function() {
        clearForm();
    });

    $('#inv_profile_filter_text').on('input', function() {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(reloadTable, 250);
    });

    $(document).on('click', '.btn-inv-profile-edit', function() {
        var code = parseInt($(this).data('code') || '0', 10);
        if (code <= 0) {
            return;
        }
        apiGet('<?= base_url('Opd_prescription/opd_invest_profile_master_get') ?>/' + code, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to load profile');
                return;
            }
            fillForm(data.row || {});
            setMsg('normal', 'Profile loaded for edit.');
        });
    });

    $(document).on('click', '.btn-inv-profile-del', function() {
        var code = parseInt($(this).data('code') || '0', 10);
        if (code <= 0) {
            return;
        }
        if (!confirm('Delete this profile?')) {
            return;
        }
        apiPost('<?= base_url('Opd_prescription/opd_invest_profile_master_remove') ?>/' + code, {}, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to delete profile');
                return;
            }
            setMsg('ok', data.error_text || 'Deleted');
            reloadTable();
            if (parseInt($('#inv_profile_code').val() || '0', 10) === code) {
                clearForm();
            }
        });
    });

    $(function() {
        initInvestigationMultiSelect();
        initTable();
    });
})();
</script>

<section class="section">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Rx-Group List</strong>
                    <button type="button" class="btn btn-primary btn-sm" id="btn_new_rx_group">Add New Rx-Group</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" id="tbl_rx_group">
                            <thead><tr><th>Rx-Group Name</th><th width="150">Access</th><th width="90">Action</th></tr></thead>
                            <tbody><tr><td colspan="3" class="text-muted">No data</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header"><strong>Rx-Group</strong></div>
                <div class="card-body">
                    <?= csrf_field() ?>
                    <input type="hidden" id="rx_id" value="<?= (int) ($initial_rx_id ?? 0) ?>">

                    <div class="mb-2">
                        <label class="form-label">Rx-Group Name</label>
                        <input type="text" id="rx_group_name" class="form-control form-control-sm" maxlength="150">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Complaints</label>
                        <input type="text" id="rx_complaints" class="form-control form-control-sm" maxlength="1000">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Diagnosis</label>
                        <input type="text" id="rx_diagnosis" class="form-control form-control-sm" maxlength="1000">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Investigation</label>
                        <input type="text" id="rx_investigation" class="form-control form-control-sm" maxlength="1000">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Finding Examinations</label>
                        <input type="text" id="rx_finding" class="form-control form-control-sm" maxlength="1000">
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_save_rx_group">Save Rx-Group</button>
                        <button type="button" class="btn btn-warning btn-sm" id="btn_save_new_rx_group">Save AS New Rx-Group</button>
                        <button type="button" class="btn btn-info btn-sm" id="btn_open_opd_medicine">Add & Edit Medicine</button>
                    </div>

                    <div class="small mt-2 text-muted" id="rx_group_msg">Ready.</div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    var evtNs = '.rxGroupWorkspace';

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

    function setMsg(type, text) {
        var $msg = $('#rx_group_msg');
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
        $('#rx_id').val('0');
        $('#rx_group_name,#rx_complaints,#rx_diagnosis,#rx_investigation,#rx_finding').val('');
    }

    function fillForm(row) {
        row = row || {};
        $('#rx_id').val(row.id || 0);
        $('#rx_group_name').val(row.rx_group_name || '');
        $('#rx_complaints').val(row.complaints || '');
        $('#rx_diagnosis').val(row.diagnosis || '');
        $('#rx_investigation').val(row.investigation || '');
        $('#rx_finding').val(row.Finding_Examinations || '');
    }

    function loadOne(rxId) {
        if (parseInt(rxId || '0', 10) <= 0) {
            return;
        }
        apiGet('<?= base_url('Opd_prescription/rx_group_get') ?>/' + rxId, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to load Rx-Group');
                return;
            }
            fillForm(data.row || {});
            setMsg('normal', 'Rx-Group loaded.');
        });
    }

    function loadList() {
        apiGet('<?= base_url('Opd_prescription/rx_group_data') ?>', function(data) {
            var rows = (data && data.rows) ? data.rows : [];
            var $tb = $('#tbl_rx_group tbody');
            $tb.empty();
            if (!rows.length) {
                $tb.html('<tr><td colspan="3" class="text-muted">No Rx-Group found</td></tr>');
                return;
            }

            rows.forEach(function(row) {
                var nameHtml = $('<div>').text(row.rx_group_name || '').html();
                var rowCount = parseInt(row.row_count || '1', 10);
                if (rowCount > 1) {
                    nameHtml += ' <span class="badge bg-warning text-dark">x' + rowCount + '</span>';
                }
                var accessLabel = String(row.access_label || 'Global');
                var accessClass = 'bg-secondary';
                if (accessLabel === 'Global') {
                    accessClass = 'bg-success';
                } else if (accessLabel === 'Doctor') {
                    accessClass = 'bg-primary';
                } else if (accessLabel === 'Global + Doctor') {
                    accessClass = 'bg-warning text-dark';
                }
                var accessHtml = '<span class="badge ' + accessClass + '">' + $('<div>').text(accessLabel).html() + '</span>';
                $tb.append('<tr>'
                    + '<td>' + nameHtml + '</td>'
                    + '<td>' + accessHtml + '</td>'
                    + '<td><button type="button" class="btn btn-sm btn-primary btn-rx-edit" data-id="' + (row.id || 0) + '">Edit</button></td>'
                    + '</tr>');
            });
        });
    }

    function saveRxGroup(forceNew) {
        var id = forceNew ? 0 : parseInt($('#rx_id').val() || '0', 10);
        var payload = {
            id: id,
            rx_group_name: ($('#rx_group_name').val() || '').trim(),
            complaints: ($('#rx_complaints').val() || '').trim(),
            diagnosis: ($('#rx_diagnosis').val() || '').trim(),
            investigation: ($('#rx_investigation').val() || '').trim(),
            finding_examinations: ($('#rx_finding').val() || '').trim()
        };

        if (!payload.rx_group_name) {
            setMsg('err', 'Rx-Group Name is required.');
            return;
        }

        apiPost('<?= base_url('Opd_prescription/rx_group_save') ?>', payload, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to save Rx-Group');
                return;
            }
            $('#rx_id').val(data.insertid || 0);
            setMsg('ok', data.error_text || 'Saved');
            loadList();
        });
    }

    // This view is loaded multiple times via AJAX; ensure only one set of handlers is active.
    $(document).off('click' + evtNs, '.btn-rx-edit').on('click' + evtNs, '.btn-rx-edit', function() {
        loadOne($(this).data('id') || 0);
    });

    $(document).off('click' + evtNs, '#btn_new_rx_group').on('click' + evtNs, '#btn_new_rx_group', function() {
        clearForm();
        setMsg('normal', 'New Rx-Group mode');
    });

    $(document).off('click' + evtNs, '#btn_save_rx_group').on('click' + evtNs, '#btn_save_rx_group', function() {
        saveRxGroup(false);
    });

    $(document).off('click' + evtNs, '#btn_save_new_rx_group').on('click' + evtNs, '#btn_save_new_rx_group', function() {
        saveRxGroup(true);
    });

    $(document).off('click' + evtNs, '#btn_open_opd_medicine').on('click' + evtNs, '#btn_open_opd_medicine', function() {
        var rxId = parseInt($('#rx_id').val() || '0', 10);
        if (rxId <= 0) {
            setMsg('err', 'Save or select Rx-Group first, then add medicines.');
            return;
        }

        var url = '<?= base_url('Opd_prescription/rx_group_medicine') ?>/' + rxId;
        if (typeof load_form === 'function') {
            load_form(url, 'Rx-Group Medicine');
            return;
        }
        window.location.href = url;
    });

    loadList();
    var initialRxId = parseInt($('#rx_id').val() || '0', 10);
    if (initialRxId > 0) {
        loadOne(initialRxId);
    }
})();
</script>

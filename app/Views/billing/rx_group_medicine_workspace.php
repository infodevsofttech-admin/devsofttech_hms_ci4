<section class="section">
    <div class="row g-3">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Rx-Group Medicine List</strong>
                    <span class="badge bg-secondary" id="rx_med_group_badge"><?= trim((string) ($rx_group_name ?? '')) !== '' ? esc((string) $rx_group_name) : ('Rx-Group #' . (int) ($rx_group_id ?? 0)) ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" id="tbl_rx_group_med">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Salt/Generic</th>
                                    <th>Dose Plan</th>
                                    <th width="80">Edit</th>
                                    <th width="90">Remove</th>
                                </tr>
                            </thead>
                            <tbody><tr><td colspan="5" class="text-muted">No medicine rows found.</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card">
                <div class="card-header"><strong>Add / Edit Rx-Group Medicine</strong></div>
                <div class="card-body">
                    <?= csrf_field() ?>
                    <input type="hidden" id="rx_group_id" value="<?= (int) ($rx_group_id ?? 0) ?>">
                    <input type="hidden" id="rx_med_item_id" value="0">
                    <input type="hidden" id="rx_med_id" value="0">

                    <div class="mb-2">
                        <label class="form-label">Medicine Name (Brand)</label>
                        <input type="text" id="rx_med_name" list="rx_med_name_suggest" class="form-control form-control-sm" placeholder="Type brand or medicine name">
                        <datalist id="rx_med_name_suggest"></datalist>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Type</label>
                        <input type="text" id="rx_med_type" class="form-control form-control-sm" placeholder="TAB/CAP/SYR/INJ">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Salt / Generic Name</label>
                        <input type="text" id="rx_genericname" list="rx_generic_suggest" class="form-control form-control-sm" placeholder="Type generic or salt name">
                        <datalist id="rx_generic_suggest"></datalist>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-4">
                            <select id="rx_dosage" class="form-select form-select-sm">
                                <option value="">Dose</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <select id="rx_dosage_when" class="form-select form-select-sm">
                                <option value="">When</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <select id="rx_dosage_freq" class="form-select form-select-sm">
                                <option value="">Frequency</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-4"><input type="text" id="rx_no_of_days" class="form-control form-control-sm" placeholder="Duration"></div>
                        <div class="col-4"><input type="text" id="rx_qty" class="form-control form-control-sm" placeholder="Qty"></div>
                        <div class="col-4">
                            <select id="rx_dose_where" class="form-select form-select-sm">
                                <option value="">Where</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Remark</label>
                        <input type="text" id="rx_remark" class="form-control form-control-sm" placeholder="Remark">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_rx_med_save">Add / Update</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_rx_med_reset">Reset</button>
                    </div>

                    <div class="small text-muted mt-2" id="rx_med_msg">Ready.</div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    var medSuggestCache = [];
    var doseMasterCache = { dose: [], when: [], freq: [], where: [] };
    var pendingFillRow = null;

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

    function apiGet(url, cb) {
        $.get(url, function(data) {
            cb(data || {});
        }, 'json');
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

    function setMsg(type, text) {
        var $msg = $('#rx_med_msg');
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

    function renderSelectOptions($select, rows, placeholder) {
        var html = '<option value="">' + $('<div>').text(placeholder || 'Select').html() + '</option>';
        (rows || []).forEach(function(row) {
            var id = (row && row.id !== undefined) ? String(row.id) : '';
            var label = (row && row.label !== undefined) ? String(row.label) : '';
            if (!id || !label) {
                return;
            }
            html += '<option value="' + $('<div>').text(id).html() + '">' + $('<div>').text(label).html() + '</option>';
        });
        $select.html(html);
    }

    function ensureOption($select, value) {
        value = (value || '').toString().trim();
        if (!value) {
            return;
        }

        var escaped = value.replace(/"/g, '&quot;');
        if ($select.find('option[value="' + escaped + '"]').length) {
            return;
        }

        $select.append('<option value="' + $('<div>').text(value).html() + '">' + $('<div>').text(value + ' (Current)').html() + '</option>');
    }

    function loadDoseMasters(done) {
        apiGet('<?= base_url('Opd_prescription/rx_group_dose_masters') ?>', function(data) {
            doseMasterCache = {
                dose: (data && data.dose) ? data.dose : [],
                when: (data && data.when) ? data.when : [],
                freq: (data && data.freq) ? data.freq : [],
                where: (data && data.where) ? data.where : []
            };

            renderSelectOptions($('#rx_dosage'), doseMasterCache.dose, 'Dose');
            renderSelectOptions($('#rx_dosage_when'), doseMasterCache.when, 'When');
            renderSelectOptions($('#rx_dosage_freq'), doseMasterCache.freq, 'Frequency');
            renderSelectOptions($('#rx_dose_where'), doseMasterCache.where, 'Where');

            if (pendingFillRow) {
                var row = pendingFillRow;
                pendingFillRow = null;
                fillForm(row);
            }

            if (typeof done === 'function') {
                done();
            }
        });
    }

    function clearForm() {
        $('#rx_med_item_id').val('0');
        $('#rx_med_id').val('0');
        $('#rx_med_name,#rx_med_type,#rx_genericname,#rx_no_of_days,#rx_qty,#rx_remark').val('');
        $('#rx_dosage,#rx_dosage_when,#rx_dosage_freq,#rx_dose_where').val('');
    }

    function fillForm(row) {
        row = row || {};
        $('#rx_med_item_id').val(row.id || 0);
        $('#rx_med_id').val(row.med_id || 0);
        $('#rx_med_name').val(row.med_name || '');
        $('#rx_med_type').val(row.med_type || '');
        $('#rx_genericname').val(row.genericname || '');
        ensureOption($('#rx_dosage'), row.dosage || '');
        ensureOption($('#rx_dosage_when'), row.dosage_when || '');
        ensureOption($('#rx_dosage_freq'), row.dosage_freq || '');
        ensureOption($('#rx_dose_where'), row.dosage_where || '');
        $('#rx_dosage').val((row.dosage || '').toString());
        $('#rx_dosage_when').val((row.dosage_when || '').toString());
        $('#rx_dosage_freq').val((row.dosage_freq || '').toString());
        $('#rx_no_of_days').val(row.no_of_days || '');
        $('#rx_qty').val(row.qty || '');
        $('#rx_dose_where').val((row.dosage_where || '').toString());
        $('#rx_remark').val(row.remark || '');
    }

    function loadOne(itemId) {
        if (parseInt(itemId || '0', 10) <= 0) {
            return;
        }
        apiGet('<?= base_url('Opd_prescription/rx_group_medicine_get') ?>/' + itemId, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to load row');
                return;
            }
            if (!$('#rx_dosage option').length || $('#rx_dosage option').length <= 1) {
                pendingFillRow = data.row || {};
                loadDoseMasters();
            } else {
                fillForm(data.row || {});
            }
            setMsg('normal', 'Medicine row loaded.');
        });
    }

    function loadList() {
        var rxGroupId = parseInt($('#rx_group_id').val() || '0', 10);
        if (rxGroupId <= 0) {
            return;
        }
        apiGet('<?= base_url('Opd_prescription/rx_group_medicine_list') ?>/' + rxGroupId, function(data) {
            var rows = (data && data.rows) ? data.rows : [];
            var groupName = (data && data.rx_group_name) ? String(data.rx_group_name).trim() : '';
            if (groupName) {
                $('#rx_med_group_badge').text(groupName);
            }
            var $tb = $('#tbl_rx_group_med tbody');
            $tb.empty();
            if (!rows.length) {
                $tb.html('<tr><td colspan="5" class="text-muted">No medicine rows found.</td></tr>');
                return;
            }

            rows.forEach(function(row) {
                var dosePlan = [
                    row.dosage_label || row.dosage || '',
                    row.dosage_when_label || row.dosage_when || '',
                    row.dosage_freq_label || row.dosage_freq || '',
                    row.dosage_where_label || row.dosage_where || '',
                    row.no_of_days || ''
                ].join(' ').replace(/\s+/g, ' ').trim();
                $tb.append('<tr>'
                    + '<td>' + $('<div>').text(row.med_name || '').html() + '</td>'
                    + '<td>' + $('<div>').text(row.genericname || '').html() + '</td>'
                    + '<td>' + $('<div>').text(dosePlan).html() + '</td>'
                    + '<td><button type="button" class="btn btn-sm btn-outline-primary btn-rx-med-edit" data-id="' + (row.id || 0) + '">Edit</button></td>'
                    + '<td><button type="button" class="btn btn-sm btn-outline-danger btn-rx-med-remove" data-id="' + (row.id || 0) + '">Remove</button></td>'
                    + '</tr>');
            });
        });
    }

    function loadMedicineSuggestions(q) {
        q = (q || '').trim();
        if (!q) {
            return;
        }
        apiGet('<?= base_url('Opd_prescription/rx_group_medicine_suggest') ?>?q=' + encodeURIComponent(q), function(data) {
            var rows = (data && data.rows) ? data.rows : [];
            medSuggestCache = rows;
            var html = '';
            rows.forEach(function(row) {
                var label = row.med_name || '';
                var salt = row.salt_name || row.genericname || '';
                if (salt) {
                    label += ' | ' + salt;
                }
                html += '<option value="' + $('<div>').text(row.med_name || '').html() + '">' + $('<div>').text(label).html() + '</option>';
            });
            $('#rx_med_name_suggest').html(html);
        });
    }

    function loadGenericSuggestions(q) {
        var url = '<?= base_url('Opd_prescription/rx_group_generic_suggest') ?>';
        q = (q || '').trim();
        if (q) {
            url += '?q=' + encodeURIComponent(q);
        }
        apiGet(url, function(data) {
            var rows = (data && data.rows) ? data.rows : [];
            var html = '';
            rows.forEach(function(row) {
                html += '<option value="' + $('<div>').text(row.value || '').html() + '"></option>';
            });
            $('#rx_generic_suggest').html(html);
        });
    }

    $('#rx_med_name').on('input', function() {
        loadMedicineSuggestions($(this).val() || '');
    });

    $('#rx_med_name').on('change', function() {
        var selected = ($(this).val() || '').trim().toLowerCase();
        if (!selected) {
            return;
        }
        medSuggestCache.forEach(function(row) {
            var name = ((row && row.med_name) ? row.med_name : '').toString().trim().toLowerCase();
            if (name !== selected) {
                return;
            }
            $('#rx_med_id').val(row.med_id || 0);
            if (!$('#rx_med_type').val()) {
                $('#rx_med_type').val(row.med_type || '');
            }
            if (!$('#rx_genericname').val()) {
                $('#rx_genericname').val(row.salt_name || row.genericname || '');
            }
        });
    });

    $('#rx_genericname').on('focus input', function() {
        loadGenericSuggestions($(this).val() || '');
    });

    $('#btn_rx_med_save').on('click', function() {
        var rxGroupId = parseInt($('#rx_group_id').val() || '0', 10);
        if (rxGroupId <= 0) {
            setMsg('err', 'Save Rx-Group first, then add medicine.');
            return;
        }

        var payload = {
            item_id: parseInt($('#rx_med_item_id').val() || '0', 10),
            med_id: parseInt($('#rx_med_id').val() || '0', 10),
            med_name: ($('#rx_med_name').val() || '').trim(),
            med_type: ($('#rx_med_type').val() || '').trim(),
            genericname: ($('#rx_genericname').val() || '').trim(),
            dosage: ($('#rx_dosage').val() || '').trim(),
            dosage_when: ($('#rx_dosage_when').val() || '').trim(),
            dosage_freq: ($('#rx_dosage_freq').val() || '').trim(),
            no_of_days: ($('#rx_no_of_days').val() || '').trim(),
            qty: ($('#rx_qty').val() || '').trim(),
            dosage_where: ($('#rx_dose_where').val() || '').trim(),
            remark: ($('#rx_remark').val() || '').trim()
        };

        if (!payload.med_name) {
            setMsg('err', 'Medicine name is required.');
            return;
        }

        apiPost('<?= base_url('Opd_prescription/rx_group_medicine_save') ?>/' + rxGroupId, payload, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to save medicine row');
                return;
            }
            setMsg('ok', data.error_text || 'Saved');
            clearForm();
            loadList();
        });
    });

    $(document).on('click', '.btn-rx-med-edit', function() {
        loadOne($(this).data('id') || 0);
    });

    $(document).on('click', '.btn-rx-med-remove', function() {
        var id = parseInt($(this).data('id') || '0', 10);
        if (id <= 0) {
            return;
        }
        if (!window.confirm('Remove this medicine row from Rx-Group?')) {
            return;
        }
        apiPost('<?= base_url('Opd_prescription/rx_group_medicine_remove') ?>/' + id, {}, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to remove row');
                return;
            }
            setMsg('ok', data.error_text || 'Removed');
            if (parseInt($('#rx_med_item_id').val() || '0', 10) === id) {
                clearForm();
            }
            loadList();
        });
    });

    $('#btn_rx_med_reset').on('click', function() {
        clearForm();
        setMsg('normal', 'Form reset');
    });

    loadDoseMasters(function() {
        loadList();
    });
    loadGenericSuggestions('');
})();
</script>

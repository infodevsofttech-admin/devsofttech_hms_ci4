<section class="section">
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Advice Master</strong>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-warning btn-sm" id="btn_advice_add_mode">Add Advice</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_advice_reload">Refresh</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0" id="tbl_advice_master">
                            <thead>
                            <tr>
                                <th width="90">ID</th>
                                <th>Advice (English)</th>
                                <th>Advice (Local)</th>
                                <th width="90">Edit</th>
                                <th width="110">Remove</th>
                            </tr>
                            </thead>
                            <tbody><tr><td colspan="5" class="text-muted">No advice found</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><strong>Add / Edit Advice</strong></div>
                <div class="card-body">
                    <?= csrf_field() ?>
                    <input type="hidden" id="advice_id" value="<?= (int) ($initial_advice_id ?? 0) ?>">

                    <div class="mb-2">
                        <label class="form-label">Advice (English)</label>
                        <textarea id="advice_en" class="form-control form-control-sm" rows="3" maxlength="1000" placeholder="Enter advice text"></textarea>
                    </div>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                            <label class="form-label mb-0" for="advice_target_lang">Advice (Local Language)</label>
                            <div class="d-flex gap-2">
                                <select id="advice_target_lang" class="form-select form-select-sm" style="min-width: 170px;">
                                    <option value="hi" selected>Hindi</option>
                                    <option value="bn">Bengali</option>
                                    <option value="mr">Marathi</option>
                                    <option value="ta">Tamil</option>
                                    <option value="te">Telugu</option>
                                    <option value="gu">Gujarati</option>
                                    <option value="kn">Kannada</option>
                                    <option value="ml">Malayalam</option>
                                    <option value="pa">Punjabi</option>
                                    <option value="or">Odia</option>
                                    <option value="as">Assamese</option>
                                    <option value="ur">Urdu</option>
                                </select>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btn_advice_translate">Translate</button>
                            </div>
                        </div>
                        <textarea id="advice_hi" class="form-control form-control-sm" rows="3" maxlength="2000" placeholder="Translated advice will appear here"></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_advice_save">Save</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_advice_reset">Reset</button>
                    </div>

                    <div class="small mt-2 text-muted" id="advice_msg">Ready.</div>
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

    function setMsg(type, text) {
        var $msg = $('#advice_msg');
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
        $('#advice_id').val('0');
        $('#advice_en,#advice_hi').val('');
    }

    function fillForm(row) {
        row = row || {};
        $('#advice_id').val(row.id || 0);
        $('#advice_en').val(row.advice || row.Name || row.advice_txt || '');
        $('#advice_hi').val(row.advice_hindi || row.advice_local || row.hindi_advice || row.advice_hin || '');
    }

    function loadOne(adviceId) {
        if (parseInt(adviceId || '0', 10) <= 0) {
            return;
        }

        apiGet('<?= base_url('Opd_prescription/opd_advice_get') ?>/' + adviceId, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to load advice');
                return;
            }
            fillForm(data.row || {});
            setMsg('normal', 'Advice loaded for edit.');
        });
    }

    function loadList() {
        apiGet('<?= base_url('Opd_prescription/opd_advice_data') ?>', function(data) {
            var rows = (data && data.rows) ? data.rows : [];
            var $tb = $('#tbl_advice_master tbody');

            if (!rows.length) {
                $tb.html('<tr><td colspan="5" class="text-muted">No advice found</td></tr>');
                return;
            }

            var html = '';
            rows.forEach(function(row) {
                var id = parseInt(row.id || '0', 10);
                var en = row.advice || '';
                var hi = row.advice_hindi || '';
                html += '<tr>'
                    + '<td>' + id + '</td>'
                    + '<td>' + $('<div>').text(en).html() + '</td>'
                    + '<td>' + $('<div>').text(hi).html() + '</td>'
                    + '<td><button type="button" class="btn btn-sm btn-primary btn-advice-edit" data-id="' + id + '">Edit</button></td>'
                    + '<td><button type="button" class="btn btn-sm btn-danger btn-advice-remove" data-id="' + id + '">Remove</button></td>'
                    + '</tr>';
            });
            $tb.html(html);
        });
    }

    $('#btn_advice_save').on('click', function() {
        var payload = {
            id: parseInt($('#advice_id').val() || '0', 10),
            advice: ($('#advice_en').val() || '').trim(),
            advice_hindi: ($('#advice_hi').val() || '').trim()
        };

        if (!payload.advice) {
            setMsg('err', 'Advice text is required.');
            return;
        }

        apiPost('<?= base_url('Opd_prescription/opd_advice_save') ?>', payload, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to save advice');
                return;
            }

            $('#advice_id').val(data.insertid || 0);
            loadList();
            setMsg('ok', data.error_text || 'Saved successfully');
        });
    });

    $('#btn_advice_translate').on('click', function() {
        var adviceText = ($('#advice_en').val() || '').trim();
        var targetLang = ($('#advice_target_lang').val() || 'hi').toString();

        if (!adviceText) {
            setMsg('err', 'Enter advice in English first.');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('Translating...');

        apiPost('<?= base_url('Opd_prescription/opd_advice_translate_ai') ?>', {
            advice: adviceText,
            target_lang: targetLang
        }, function(data) {
            $btn.prop('disabled', false).text('Translate');
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to translate advice');
                return;
            }

            $('#advice_hi').val((data.translated_text || '').toString());
            var langName = (data.target_language_name || '').toString();
            var provider = (data.provider || '').toString();
            var providerLabel = provider === 'ai-server' ? 'AI Server' : 'AI';
            setMsg('ok', 'Translated to ' + (langName || 'selected language') + ' using ' + providerLabel + '.');
        });
    });

    $(document).on('click', '.btn-advice-edit', function() {
        loadOne($(this).data('id'));
    });

    $(document).on('click', '.btn-advice-remove', function() {
        var adviceId = parseInt($(this).data('id') || '0', 10);
        if (adviceId <= 0) {
            return;
        }
        if (!confirm('Remove this advice from master?')) {
            return;
        }

        apiPost('<?= base_url('Opd_prescription/opd_advice_remove') ?>/' + adviceId, {}, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to remove advice');
                return;
            }
            if (parseInt($('#advice_id').val() || '0', 10) === adviceId) {
                clearForm();
            }
            loadList();
            setMsg('ok', data.error_text || 'Advice removed');
        });
    });

    $('#btn_advice_reset,#btn_advice_add_mode').on('click', function() {
        clearForm();
        setMsg('normal', 'Add new advice mode');
    });

    $('#btn_advice_reload').on('click', function() {
        loadList();
    });

    loadList();
    var initialAdviceId = parseInt($('#advice_id').val() || '0', 10);
    if (initialAdviceId > 0) {
        loadOne(initialAdviceId);
    }
})();
</script>

<section class="section">
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>List of Medicine</strong>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="med_master_scope" style="width:150px;">
                            <option value="active" selected>Recently Used</option>
                            <option value="all">All Medicines</option>
                            <option value="favorite">My Favorites</option>
                        </select>
                        <label class="form-check-label small d-flex align-items-center gap-1">
                            <input type="checkbox" class="form-check-input mt-0" id="med_show_all_toggle">
                            Show All
                        </label>
                        <button type="button" class="btn btn-outline-secondary btn-sm med-filter" data-filter="all">All</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm med-filter" data-filter="generic_issue">Generic/Salt Issue</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm med-filter" data-filter="generic_same_name">Generic=Name</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm med-filter" data-filter="company_blank">Company Blank</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn_med_export">Export</button>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="btn_med_check_duplicates">Check Duplicates</button>
                        <button type="button" class="btn btn-warning btn-sm" id="btn_med_add_mode">Add Medicine</button>
                        <button type="button" class="btn btn-warning btn-sm" id="btn_med_reload">Medicine List</button>
                    </div>
                </div>
                <div class="px-3 pt-2 pb-1">
                    <div class="small text-muted" id="med_dup_status">Duplicate scan not run yet.</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0" id="tbl_med_master">
                            <thead>
                                <tr>
                                    <th width="80">ID</th>
                                    <th width="70">Fav</th>
                                    <th>Name</th>
                                    <th width="100">Used</th>
                                    <th width="150">Last Used</th>
                                    <th>Generic Name</th>
                                    <th>Company Name</th>
                                    <th width="80">Edit</th>
                                    <th width="100">Remove</th>
                                </tr>
                            </thead>
                            <tbody><tr><td colspan="9" class="text-muted">No medicine found</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div id="med_duplicate_anchor"></div>
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Duplicate Medicine Names</strong>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-danger btn-sm" id="btn_med_dup_autofix">Auto Fix Old Data</button>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="btn_med_dup_refresh">Refresh</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0" id="tbl_med_dup">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th width="120">Count</th>
                                    <th width="200">IDs</th>
                                    <th width="170">Action</th>
                                </tr>
                            </thead>
                            <tbody><tr><td colspan="4" class="text-muted">Click Check Duplicates to scan.</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><strong>New Medicine</strong></div>
                <div class="card-body">
                    <?= csrf_field() ?>
                    <input type="hidden" id="med_id" value="<?= (int) ($initial_med_id ?? 0) ?>">

                    <div class="mb-2">
                        <label class="form-label">Medicine Name</label>
                        <input type="text" id="med_item_name" class="form-control form-control-sm" placeholder="Enter Medicine Name">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Formulation</label>
                        <input type="text" id="med_formulation" class="form-control form-control-sm" placeholder="Formulation">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Generic Name</label>
                        <input type="text" id="med_genericname" class="form-control form-control-sm" placeholder="Generic Name">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Salt Name</label>
                        <input type="text" id="med_salt_name" class="form-control form-control-sm" placeholder="Salt Name">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Dosage Restriction</label>
                        <input type="text" id="med_dosage_restriction" class="form-control form-control-sm" placeholder="Any key dosage restriction">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Company Name</label>
                        <input type="text" id="med_company_name" list="med_company_suggestions" class="form-control form-control-sm" placeholder="Company Name (optional)">
                        <datalist id="med_company_suggestions"></datalist>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_med_save">Update</button>
                        <button type="button" class="btn btn-outline-info btn-sm" id="btn_med_ai_details">Use AI</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_med_reset">Reset</button>
                    </div>

                    <div class="small mt-2 text-muted" id="med_msg">Ready.</div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    var activeListFilter = 'all';
    var activeMasterScope = 'active';
    var showAllMedicines = false;
    var companySuggestTimer = null;
    var medMasterDataTable = null;

    function showToast(message, type) {
        var toastId = 'med_toast_' + Date.now();
        var cls = 'alert-info';
        if (type === 'ok') {
            cls = 'alert-success';
        } else if (type === 'err') {
            cls = 'alert-danger';
        }

        var html = '<div id="' + toastId + '" class="alert ' + cls + ' shadow-sm d-flex align-items-start justify-content-between" role="alert" '
            + 'style="position:fixed;top:20px;right:20px;z-index:9999;min-width:260px;max-width:420px;gap:12px;">'
            + '<div>' + $('<div>').text(message || '').html() + '</div>'
            + '<button type="button" class="btn-close btn-sm med-toast-close" aria-label="Close" data-target-id="' + toastId + '"></button>'
            + '</div>';

        $('body').append(html);
    }

    $(document).on('click', '.med-toast-close', function() {
        var targetId = String($(this).data('target-id') || '');
        if (!targetId) {
            return;
        }
        $('#' + targetId).fadeOut(150, function() { $(this).remove(); });
    });

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

    function applyFilterButtonState() {
        $('.med-filter').removeClass('btn-primary').addClass('btn-outline-secondary');
        $('.med-filter[data-filter="' + activeListFilter + '"]').removeClass('btn-outline-secondary').addClass('btn-primary');
    }

    function renderCompanySuggestions(rows) {
        var list = (rows && rows.length) ? rows : [];
        var html = '';
        list.forEach(function(row) {
            var name = (row && row.company_name) ? String(row.company_name) : '';
            if (!name) {
                return;
            }
            html += '<option value="' + $('<div>').text(name).html() + '"></option>';
        });
        $('#med_company_suggestions').html(html);
    }

    function loadCompanySuggestions(term) {
        var url = '<?= base_url('Opd_prescription/opd_medicince_company_suggest') ?>';
        var q = (term || '').trim();
        if (q !== '') {
            url += '?term=' + encodeURIComponent(q);
        }
        apiGet(url, function(data) {
            renderCompanySuggestions((data && data.rows) ? data.rows : []);
        });
    }

    function setMsg(type, text) {
        var $msg = $('#med_msg');
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
        $('#med_id').val('0');
        $('#med_item_name,#med_formulation,#med_genericname,#med_salt_name,#med_dosage_restriction,#med_company_name').val('');
    }

    function buildMedicinePayload() {
        return {
            id: parseInt($('#med_id').val() || '0', 10),
            item_name: ($('#med_item_name').val() || '').trim(),
            formulation: ($('#med_formulation').val() || '').trim(),
            genericname: ($('#med_genericname').val() || '').trim(),
            salt_name: ($('#med_salt_name').val() || '').trim(),
            dosage_restriction: ($('#med_dosage_restriction').val() || '').trim(),
            company_name: ($('#med_company_name').val() || '').trim()
        };
    }

    function saveMedicine(payload, afterSave) {
        if (!payload.item_name) {
            setMsg('err', 'Medicine name is required.');
            return;
        }

        apiPost('<?= base_url('Opd_prescription/opd_medicince_save') ?>', payload, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to save medicine');
                return;
            }
            $('#med_id').val(data.insertid || 0);
            setMsg('ok', data.error_text || 'Saved');
            loadList();
            loadDuplicateReport();
            if (typeof afterSave === 'function') {
                afterSave(data || {});
            }
        });
    }

    function fillForm(row) {
        row = row || {};
        $('#med_id').val(row.id || 0);
        $('#med_item_name').val(row.item_name || '');
        $('#med_formulation').val(row.formulation || '');
        $('#med_genericname').val(row.genericname || '');
        $('#med_salt_name').val(row.salt_name || row.sal_name || row.salt || row.saltname || '');
        $('#med_dosage_restriction').val(row.dosage_restriction || row.dose_restriction || row.restriction_note || row.restriction || '');
        $('#med_company_name').val(row.company_name || '');
    }

    function loadOne(medId) {
        if (parseInt(medId || '0', 10) <= 0) {
            return;
        }
        apiGet('<?= base_url('Opd_prescription/opd_medicince_get') ?>/' + medId, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to load medicine');
                return;
            }
            fillForm(data.row || {});
            setMsg('normal', 'Medicine loaded for edit.');
        });
    }

    function loadList() {
        var url = '<?= base_url('Opd_prescription/opd_medicince_data') ?>';
        var query = [];
        if (activeListFilter !== 'all') {
            query.push('filter=' + encodeURIComponent(activeListFilter));
        }
        if (activeMasterScope !== 'all') {
            query.push('scope=' + encodeURIComponent(activeMasterScope));
        }
        if (showAllMedicines) {
            query.push('show_all=1');
        }
        if (query.length) {
            url += '?' + query.join('&');
        }

        apiGet(url, function(data) {
            var rows = (data && data.rows) ? data.rows : [];
            var $tb = $('#tbl_med_master tbody');
            $tb.empty();
            if (!rows.length) {
                $tb.html('<tr><td colspan="9" class="text-muted">No medicine found</td></tr>');
                if (medMasterDataTable && $.fn.dataTable.isDataTable('#tbl_med_master')) {
                    medMasterDataTable.destroy();
                    medMasterDataTable = null;
                }
                if (activeListFilter === 'generic_issue') {
                    setMsg('normal', 'No record found with Generic/Salt issue.');
                } else if (activeListFilter === 'generic_same_name') {
                    setMsg('normal', 'No record found where Generic/Salt is same as Medicine Name.');
                } else if (activeListFilter === 'company_blank') {
                    setMsg('normal', 'No record found with blank Company Name.');
                } else if (activeMasterScope === 'favorite') {
                    setMsg('normal', 'No favorite medicines found.');
                } else if (activeMasterScope === 'active') {
                    setMsg('normal', 'No recently used medicines found.');
                }
                return;
            }

            rows.forEach(function(row) {
                var medId = parseInt(row.id || 0, 10);
                var isFav = parseInt(row.is_favorite || 0, 10) === 1;
                var favBtn = '<button type="button" class="btn btn-sm ' + (isFav ? 'btn-warning' : 'btn-outline-warning') + ' btn-med-fav" data-id="' + medId + '">' + (isFav ? '★' : '☆') + '</button>';
                $tb.append('<tr id="med_row_' + (row.id || 0) + '">' 
                    + '<td>' + (row.id || 0) + '</td>'
                    + '<td>' + favBtn + '</td>'
                    + '<td>' + $('<div>').text(row.item_name || '').html() + '</td>'
                    + '<td>' + parseInt(row.use_count || 0, 10) + '</td>'
                    + '<td>' + $('<div>').text(row.last_used_at || '').html() + '</td>'
                    + '<td>' + $('<div>').text(row.genericname || '').html() + '</td>'
                    + '<td>' + $('<div>').text(row.company_name || '').html() + '</td>'
                    + '<td><button type="button" class="btn btn-sm btn-outline-primary btn-med-edit" data-id="' + (row.id || 0) + '">Edit</button></td>'
                    + '<td><button type="button" class="btn btn-sm btn-outline-danger btn-med-remove" data-id="' + (row.id || 0) + '">Remove</button></td>'
                    + '</tr>');
            });

            initMedMasterDataTable();

            if (activeListFilter === 'generic_issue') {
                setMsg('normal', rows.length + ' record(s) with unclear Generic/Salt data.');
            } else if (activeListFilter === 'generic_same_name') {
                setMsg('normal', rows.length + ' record(s) where Generic/Salt matches Medicine Name.');
            } else if (activeListFilter === 'company_blank') {
                setMsg('normal', rows.length + ' record(s) with blank/unclear Company Name.');
            }
        });
    }

    function initMedMasterDataTable() {
        if (!window.jQuery || !$.fn || typeof $.fn.DataTable !== 'function') {
            return;
        }

        if (medMasterDataTable && $.fn.dataTable.isDataTable('#tbl_med_master')) {
            medMasterDataTable.destroy();
            medMasterDataTable = null;
        }

        medMasterDataTable = $('#tbl_med_master').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            pageLength: 25,
            order: [[3, 'desc'], [2, 'asc']],
            columnDefs: [
                { orderable: false, targets: [1, 7, 8] },
            ],
        });
    }

    function loadDuplicateReport(showToastResult) {
        showToastResult = !!showToastResult;
        $('#med_dup_status').removeClass('text-success text-danger').addClass('text-muted').text('Scanning duplicate medicine names...');
        var $dupBody = $('#tbl_med_dup tbody');
        $dupBody.html('<tr><td colspan="4" class="text-muted">Checking duplicates, please wait...</td></tr>');

        apiGet('<?= base_url('Opd_prescription/opd_medicince_duplicate_report') ?>', function(data) {
            updateCsrf(data);
            var rows = (data && data.rows) ? data.rows : [];
            var $tb = $('#tbl_med_dup tbody');
            $tb.empty();

            if (!rows.length) {
                $tb.html('<tr><td colspan="4" class="text-success">No duplicate medicine names found.</td></tr>');
                $('#med_dup_status').removeClass('text-muted text-danger').addClass('text-success').text('No duplicate medicine names found.');
                if (showToastResult) {
                    showToast('No duplicate medicine names found.', 'ok');
                }
                return;
            }

            $('#med_dup_status').removeClass('text-muted text-success').addClass('text-danger').text(rows.length + ' duplicate group(s) found. Use "Merge to ID" or "Auto Fix Old Data" below.');
            if (showToastResult) {
                showToast(rows.length + ' duplicate group(s) found. Scroll down to merge or auto-fix.', 'err');
            }

            rows.forEach(function(row) {
                var keepId = parseInt(row.keep_id || '0', 10);
                var mergeIds = (row.merge_ids || []).join(',');
                var allIds = (row.all_ids || []).join(', ');

                $tb.append('<tr>'
                    + '<td>' + $('<div>').text(row.display_name || '').html() + '</td>'
                    + '<td>' + (row.count || 0) + '</td>'
                    + '<td>' + $('<div>').text(allIds).html() + '</td>'
                    + '<td><button type="button" class="btn btn-sm btn-danger btn-med-dup-merge" data-keep-id="' + keepId + '" data-merge-ids="' + mergeIds + '">Merge to ID ' + keepId + '</button></td>'
                    + '</tr>');
            });
        });
    }

    $('#btn_med_save').on('click', function() {
        saveMedicine(buildMedicinePayload());
    });

    $(document).on('click', '.btn-med-edit', function() {
        loadOne($(this).data('id') || 0);
    });

    $(document).on('click', '.btn-med-remove', function() {
        var medId = parseInt($(this).data('id') || '0', 10);
        if (medId <= 0) {
            return;
        }
        if (!window.confirm('Remove this medicine?')) {
            return;
        }
        apiPost('<?= base_url('Opd_prescription/opd_medicince_remove') ?>/' + medId, {}, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to remove medicine');
                return;
            }
            if (parseInt($('#med_id').val() || '0', 10) === medId) {
                clearForm();
            }
            setMsg('ok', data.error_text || 'Medicine removed');
            loadList();
            loadDuplicateReport();
        });
    });

    $(document).on('click', '.btn-med-fav', function() {
        var medId = parseInt($(this).data('id') || '0', 10);
        if (medId <= 0) {
            return;
        }

        apiPost('<?= base_url('Opd_prescription/medicine_favorite_toggle') ?>', { med_id: medId }, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to update favorite');
                return;
            }
            setMsg('ok', data.error_text || 'Favorite updated');
            loadList();
        });
    });

    $(document).on('click', '.btn-med-dup-merge', function() {
        var keepId = parseInt($(this).data('keep-id') || '0', 10);
        var mergeIds = String($(this).data('merge-ids') || '');
        if (keepId <= 0 || !mergeIds) {
            return;
        }

        if (!window.confirm('Merge duplicate medicine IDs (' + mergeIds + ') into ID ' + keepId + '?')) {
            return;
        }

        apiPost('<?= base_url('Opd_prescription/opd_medicince_merge_duplicates') ?>', {
            keep_id: keepId,
            merge_ids: mergeIds
        }, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to merge duplicates');
                return;
            }
            setMsg('ok', data.error_text || 'Duplicates merged');
            loadList();
            loadDuplicateReport();
        });
    });

    $('#btn_med_dup_autofix').on('click', function(e) {
        e.preventDefault();
        if (!window.confirm('Auto-fix all duplicate medicine names from old data now?')) {
            return;
        }

        apiPost('<?= base_url('Opd_prescription/opd_medicince_autofix_duplicates') ?>', {}, function(data) {
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'Unable to auto-fix duplicates');
                showToast(data.error_text || 'Unable to auto-fix duplicates', 'err');
                return;
            }
            setMsg('ok', data.error_text || 'Auto-fix completed');
            showToast(data.error_text || 'Auto-fix completed', 'ok');
            loadList();
            loadDuplicateReport();
        });
    });

    $('#btn_med_reset').on('click', function() {
        clearForm();
        setMsg('normal', 'Form reset');
    });

    $('#btn_med_add_mode').on('click', function() {
        clearForm();
        setMsg('normal', 'Add medicine mode');
    });

    $('#btn_med_reload').on('click', function() {
        loadList();
    });

    $('#btn_med_export').on('click', function() {
        var url = '<?= base_url('Opd_prescription/opd_medicince_export') ?>';
        if (activeListFilter !== 'all') {
            url += '?filter=' + encodeURIComponent(activeListFilter);
        }
        window.open(url, '_blank');
    });

    $(document).on('click', '.med-filter', function() {
        activeListFilter = String($(this).data('filter') || 'all');
        applyFilterButtonState();
        loadList();
    });

    $('#med_master_scope').on('change', function() {
        activeMasterScope = String($(this).val() || 'all');
        loadList();
    });

    $('#med_show_all_toggle').on('change', function() {
        showAllMedicines = !!$(this).is(':checked');
        loadList();
    });

    $('#btn_med_ai_details').on('click', function() {
        var itemName = ($('#med_item_name').val() || '').trim();
        if (!itemName) {
            setMsg('err', 'Enter medicine name first for AI lookup.');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('AI...');
        apiPost('<?= base_url('Opd_prescription/opd_medicince_ai_details') ?>', {
            item_name: itemName,
            formulation: ($('#med_formulation').val() || '').trim()
        }, function(data) {
            $btn.prop('disabled', false).text('Use AI');
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'AI details not available');
                return;
            }

            var details = data.details || {};
            if (($('#med_genericname').val() || '').trim() === '' && (details.genericname || '').trim() !== '') {
                $('#med_genericname').val(details.genericname || '');
            }
            if (($('#med_salt_name').val() || '').trim() === '' && (details.salt_name || '').trim() !== '') {
                $('#med_salt_name').val(details.salt_name || '');
            }
            if (($('#med_dosage_restriction').val() || '').trim() === '' && (details.dosage_restriction || '').trim() !== '') {
                $('#med_dosage_restriction').val(details.dosage_restriction || '');
            }

            setMsg('ok', (data.error_text || 'AI details prepared') + '. Review and click Update.');

            var provider = String(data.provider || '');
            var confidence = String(data.match_confidence || '');
            var medId = parseInt($('#med_id').val() || '0', 10);
            var isNewMedicine = medId <= 0;

            if (provider === 'local-master' && confidence === 'exact') {
                var matchedName = String(data.matched_item_name || '').trim();
                var askExact = 'Exact local match found';
                if (matchedName) {
                    askExact += ' (' + matchedName + ')';
                }
                askExact += '. Auto-save now?';
                if (window.confirm(askExact)) {
                    saveMedicine(buildMedicinePayload(), function() {
                        setMsg('ok', 'Auto-saved using exact local master match.');
                    });
                }
                return;
            }

            if (isNewMedicine && (provider === 'ai-server' || provider === 'local-brand-fallback')) {
                if (window.confirm('Details prepared for a new medicine. Save this new master entry now?')) {
                    saveMedicine(buildMedicinePayload(), function() {
                        setMsg('ok', 'New medicine saved with suggested generic/salt details.');
                    });
                }
            }
        });
    });

    $('#med_company_name').on('focus', function() {
        loadCompanySuggestions('');
    });

    $('#med_company_name').on('input', function() {
        var term = String($(this).val() || '');
        if (companySuggestTimer) {
            clearTimeout(companySuggestTimer);
        }
        companySuggestTimer = setTimeout(function() {
            loadCompanySuggestions(term);
        }, 250);
    });

    $('#btn_med_check_duplicates,#btn_med_dup_refresh').on('click', function(e) {
        e.preventDefault();
        loadDuplicateReport(true);
        var anchor = document.getElementById('med_duplicate_anchor');
        if (anchor && typeof anchor.scrollIntoView === 'function') {
            anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    applyFilterButtonState();
    $('#med_master_scope').val(activeMasterScope);
    $('#med_show_all_toggle').prop('checked', false);
    loadList();
    loadDuplicateReport();
    loadCompanySuggestions('');
    var initialMedId = parseInt($('#med_id').val() || '0', 10);
    if (initialMedId > 0) {
        loadOne(initialMedId);
    }
})();
</script>

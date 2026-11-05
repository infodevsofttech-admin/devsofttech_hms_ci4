<section class="section">
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <strong>List of Medicine</strong>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm med-filter" data-filter="all">All</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm med-filter" data-filter="generic_issue">Generic/Salt Issue</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm med-filter" data-filter="generic_same_name">Generic=Name</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm med-filter" data-filter="company_blank">Company Blank</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm med-filter" data-filter="coding_missing">SNOMED/ATC Missing</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm med-filter" data-filter="snomed_missing">SNOMED Missing</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm med-filter" data-filter="atc_missing">ATC Missing</button>
                        <button type="button" class="btn btn-success btn-sm" id="btn_med_bulk_sync_salt" title="Bulk update missing Generic/Salt, Substitute, Company &amp; SNOMED from Pharmacy &amp; ABDM Drug Master">Update Salt (Drug Master)</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn_med_export">Export</button>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="btn_med_check_duplicates">Check Duplicates</button>
                        <button type="button" class="btn btn-warning btn-sm" id="btn_med_add_mode">Add Medicine</button>
                        <button type="button" class="btn btn-warning btn-sm" id="btn_med_reload">Medicine List</button>
                    </div>
                </div>
                <div class="px-3 pt-2 pb-1">
                    <div class="small mb-1" id="med_coding_summary">
                        <span class="badge bg-secondary">Loading coding summary...</span>
                    </div>
                    <div id="med_bulk_sync_box" class="alert alert-info py-2 px-3 mb-2 small" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong id="med_bulk_sync_title">Syncing missing Salt/Generic from Drug Master...</strong>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" id="btn_med_bulk_sync_stop">Stop</button>
                        </div>
                        <div class="progress mb-1" style="height: 8px;">
                            <div id="med_bulk_sync_bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;"></div>
                        </div>
                        <div id="med_bulk_sync_status" class="text-muted">Preparing batch...</div>
                    </div>
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
                        <div class="input-group input-group-sm">
                            <input type="text" id="med_item_name" class="form-control form-control-sm" placeholder="Enter Medicine Name" autocomplete="off">
                            <button type="button" class="btn btn-outline-success" id="btn_drug_master_search" title="Search Pharmacy &amp; ABDM Drug Master">
                                <i class="fa fa-search"></i> Drug Master
                            </button>
                        </div>
                        <div id="drug_master_suggest_panel" class="border rounded bg-white shadow-sm mt-1 p-0" style="display:none;max-height:260px;overflow-y:auto;position:relative;z-index:220;"></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Formulation / Type</label>
                        <select id="med_formulation" class="form-select form-select-sm">
                            <option value="">Select Type</option>
                            <optgroup label="Oral Solid">
                                <option value="TAB">TAB (Tablet)</option>
                                <option value="CAP">CAP (Capsule)</option>
                                <option value="SACHET">SACHET (Granules / Powder)</option>
                                <option value="POWDER">POWDER (Oral Powder)</option>
                                <option value="LOZENGE">LOZENGE (Chewable / Troche)</option>
                            </optgroup>
                            <optgroup label="Oral Liquid">
                                <option value="SYR">SYR (Syrup)</option>
                                <option value="SUSP">SUSP (Suspension)</option>
                                <option value="SOLN">SOLN (Oral Solution)</option>
                                <option value="DROPS">DROPS (Oral Drops)</option>
                            </optgroup>
                            <optgroup label="Dermatology &amp; Topical">
                                <option value="CREAM">CREAM (Cream)</option>
                                <option value="OINT">OINT (Ointment)</option>
                                <option value="GEL">GEL (Gel)</option>
                                <option value="LOTION">LOTION (Lotion)</option>
                                <option value="SHAMPOO">SHAMPOO (Medicated Shampoo)</option>
                                <option value="SOAP">SOAP (Medicated Soap / Bar)</option>
                                <option value="OIL">OIL (Medicated Oil)</option>
                                <option value="SPRAY">SPRAY (Topical Spray)</option>
                                <option value="DUSTING POWDER">DUSTING POWDER</option>
                                <option value="PAINT">PAINT (Gum / Throat Paint)</option>
                                <option value="PATCH">PATCH (Transdermal Patch)</option>
                            </optgroup>
                            <optgroup label="Respiratory &amp; ENT">
                                <option value="RESPULES">RESPULES (Nebulizer)</option>
                                <option value="INHALER">INHALER (MDI / Rotacaps)</option>
                                <option value="NASAL SPRAY">NASAL SPRAY</option>
                                <option value="NASAL DROP">NASAL DROP</option>
                                <option value="EAR DROP">EAR DROP</option>
                                <option value="GARGLE">GARGLE / MOUTHWASH</option>
                            </optgroup>
                            <optgroup label="Ophthalmic (Eye)">
                                <option value="EYE DROP">EYE DROP</option>
                                <option value="EYE OINT">EYE OINT (Eye Ointment)</option>
                            </optgroup>
                            <optgroup label="Parenteral (Injections &amp; Infusions)">
                                <option value="INJ">INJ (Injection)</option>
                                <option value="IV FLUID">IV FLUID (Infusion)</option>
                            </optgroup>
                            <optgroup label="Rectal &amp; Vaginal">
                                <option value="SUPPOSITORY">SUPPOSITORY (Rectal)</option>
                                <option value="PESSARY">PESSARY (Vaginal Tablet)</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Generic Name</label>
                        <input type="text" id="med_genericname" class="form-control form-control-sm" placeholder="Generic Name">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Substitute <small class="text-muted">(other brand names with same composition)</small></label>
                        <input type="text" id="med_salt_name" class="form-control form-control-sm" placeholder="e.g. Brand A, Brand B, Brand C">
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
                    <div class="mb-2">
                        <label class="form-label">SNOMED CT Code <small class="text-muted">(substance / product)</small></label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="med_snomed_code" class="form-control form-control-sm" placeholder="e.g. 372687004">
                            <button type="button" class="btn btn-outline-info" id="btn_snomed_search" title="Search SNOMED CT substance concepts for this medicine name">
                                <i class="fa fa-search"></i> SNOMED
                            </button>
                        </div>
                        <div id="snomed_fsn_display" class="small text-info mt-1" style="display:none;"></div>
                        <div id="snomed_suggest_panel" class="border rounded bg-white shadow-sm mt-1 p-0" style="display:none;max-height:240px;overflow-y:auto;position:relative;z-index:200;"></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">ATC Code <small class="text-muted">(optional)</small></label>
                        <input type="text" id="med_atc_code" class="form-control form-control-sm" placeholder="e.g. N02BE01">
                    </div>

                    <hr class="my-2">
                    <div class="small fw-semibold text-muted mb-1">Prescription Defaults <span class="text-info">(auto-fills on consult form)</span></div>
                    <div class="row g-1 mb-2">
                        <div class="col-6">
                            <label class="form-label form-label-sm mb-0">Dose</label>
                            <input type="text" id="med_default_dosage" list="med_dosage_suggestions" class="form-control form-control-sm" placeholder="e.g. 1 Tab / 500mg">
                            <datalist id="med_dosage_suggestions">
                                <option value="1 Tab">
                                <option value="2 Tab">
                                <option value="1 Cap">
                                <option value="500 mg">
                                <option value="250 mg">
                                <option value="650 mg">
                                <option value="5 ml">
                                <option value="10 ml">
                                <option value="1 Puff">
                                <option value="1 Sachet">
                                <option value="1 Application">
                            </datalist>
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm mb-0">When</label>
                            <select id="med_default_when" class="form-select form-select-sm">
                                <option value="">Select when</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm mb-0">Frequency</label>
                            <select id="med_default_freq" class="form-select form-select-sm">
                                <option value="">Select frequency</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label form-label-sm mb-0">Route</label>
                            <select id="med_default_where" class="form-select form-select-sm">
                                <option value="">Select route</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label form-label-sm mb-0">Days</label>
                            <input type="text" id="med_default_days" class="form-control form-control-sm" placeholder="e.g. 5">
                        </div>
                        <div class="col-4">
                            <label class="form-label form-label-sm mb-0">Qty</label>
                            <input type="text" id="med_default_qty" class="form-control form-control-sm" placeholder="e.g. 15">
                        </div>
                        <div class="col-4">
                            <label class="form-label form-label-sm mb-0">Remark</label>
                            <input type="text" id="med_default_remark" class="form-control form-control-sm" placeholder="">
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-primary btn-sm" id="btn_med_save">Update</button>
                        <button type="button" class="btn btn-outline-success btn-sm" id="btn_med_ai_details" title="Auto-fill Generic/Salt, Substitute, Company &amp; SNOMED from Pharmacy &amp; ABDM Drug Master">Fetch from Drug Master</button>
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
    var companySuggestTimer = null;
    var medMasterDataTable = null;
    var defaultDoseMasterCache = { dose: [], when: [], freq: [], where: [] };

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
        $('#med_item_name,#med_formulation,#med_genericname,#med_salt_name,#med_dosage_restriction,#med_company_name,#med_snomed_code,#med_atc_code').val('');
        $('#med_default_days,#med_default_qty,#med_default_remark').val('');
        $('#med_default_dosage,#med_default_when,#med_default_freq,#med_default_where').val('');
        $('#snomed_fsn_display').hide().text('');
        closeSnomedPanel();
    }

    function renderDefaultMasterSelect($select, rows, placeholder) {
        var html = '<option value="">' + $('<div>').text(placeholder || 'Select').html() + '</option>';
        (rows || []).forEach(function(row) {
            var id = (row && row.id !== undefined) ? String(row.id) : '';
            var label = (row && row.label !== undefined) ? String(row.label) : '';
            var desc = (row && row.secondary_label !== undefined) ? String(row.secondary_label) : '';
            if (!id || !label || id === '0') {
                return;
            }
            var text = label;
            if (desc && desc.toLowerCase() !== label.toLowerCase()) {
                text += ' - ' + desc;
            }
            html += '<option value="' + $('<div>').text(label).html() + '">' + $('<div>').text(text).html() + '</option>';
        });
        $select.html(html);
    }

    function ensureDefaultMasterOption($select, value) {
        value = (value || '').toString().trim();
        if (!value || /^\d+$/.test(value)) {
            return;
        }
        var safeValue = value.replace(/"/g, '&quot;');
        if ($select.find('option[value="' + safeValue + '"]').length) {
            return;
        }
        $select.append('<option value="' + $('<div>').text(value).html() + '">' + $('<div>').text(value + ' (Current)').html() + '</option>');
    }

    function setDefaultMasterValue($select, value) {
        var raw = (value || '').toString().trim();
        if (!raw || /^\d+$/.test(raw)) {
            $select.val('');
            return;
        }

        ensureDefaultMasterOption($select, raw);
        if ($select.find('option[value="' + raw.replace(/"/g, '&quot;') + '"]').length) {
            $select.val(raw);
            return;
        }

        var probe = raw.toLowerCase();
        var matchedVal = '';
        $select.find('option').each(function() {
            var txt = ($(this).text() || '').toString().trim().toLowerCase();
            if (!txt) {
                return;
            }
            if (txt === probe || txt.indexOf(probe) !== -1) {
                matchedVal = ($(this).val() || '').toString();
                return false;
            }
        });

        if (matchedVal) {
            $select.val(matchedVal);
            return;
        }

        ensureDefaultMasterOption($select, raw);
        $select.val(raw);
    }

    function loadDefaultDoseMasters(done) {
        apiGet('<?= base_url('Opd_prescription/rx_group_dose_masters') ?>', function(data) {
            defaultDoseMasterCache = {
                dose: (data && data.dose) ? data.dose : [],
                when: (data && data.when) ? data.when : [],
                freq: (data && data.freq) ? data.freq : [],
                where: (data && data.where) ? data.where : []
            };

            renderDefaultMasterSelect($('#med_default_when'), defaultDoseMasterCache.when, 'Select when');
            renderDefaultMasterSelect($('#med_default_freq'), defaultDoseMasterCache.freq, 'Select frequency');
            renderDefaultMasterSelect($('#med_default_where'), defaultDoseMasterCache.where, 'Select route');

            if (typeof done === 'function') {
                done();
            }
        });
    }

    function buildMedicinePayload() {
        return {
            id: parseInt($('#med_id').val() || '0', 10),
            item_name: ($('#med_item_name').val() || '').trim(),
            formulation: ($('#med_formulation').val() || '').trim(),
            genericname: ($('#med_genericname').val() || '').trim(),
            salt_name: ($('#med_salt_name').val() || '').trim(),
            dosage_restriction: ($('#med_dosage_restriction').val() || '').trim(),
            company_name: ($('#med_company_name').val() || '').trim(),
            snomed_code: ($('#med_snomed_code').val() || '').trim(),
            atc_code: ($('#med_atc_code').val() || '').trim(),
            dosage: ($('#med_default_dosage').val() || '').trim(),
            dosage_when: ($('#med_default_when').val() || '').trim(),
            dosage_freq: ($('#med_default_freq').val() || '').trim(),
            dosage_where: ($('#med_default_where').val() || '').trim(),
            no_of_days: ($('#med_default_days').val() || '').trim(),
            qty: ($('#med_default_qty').val() || '').trim(),
            remark: ($('#med_default_remark').val() || '').trim()
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
        var rawForm = (row.formulation || '').toString().trim().toUpperCase();
        if (rawForm === 'TABLETS' || rawForm === 'TABLET' || rawForm === 'TABS' || rawForm === 'TA' || rawForm === '1 TAB') {
            rawForm = 'TAB';
        } else if (rawForm === 'CAPSULES' || rawForm === 'CAPSULE' || rawForm === 'CAPS') {
            rawForm = 'CAP';
        } else if (rawForm === 'SYRUP' || rawForm === 'SYRUPS' || rawForm === 'SYP') {
            rawForm = 'SYR';
        } else if (rawForm === 'INJECTIONS' || rawForm === 'INJECTION' || rawForm === '1 AMP' || rawForm === 'AMP' || rawForm === 'AMPOULE' || rawForm === 'VIAL' || rawForm === 'IV' || rawForm === 'IM') {
            rawForm = 'INJ';
        } else if (rawForm === 'I V FLUD' || rawForm === 'IV FLUIDS' || rawForm === 'I V FLUD IN 24 HRS' || rawForm === 'INFUSION' || rawForm === 'IV INFUSION') {
            rawForm = 'IV FLUID';
        } else if (rawForm === 'OINTMENT' || rawForm === 'OINTMENTS') {
            rawForm = 'OINT';
        } else if (rawForm === 'CREAMS') {
            rawForm = 'CREAM';
        } else if (rawForm === 'GELS' || rawForm === 'JELLY') {
            rawForm = 'GEL';
        } else if (rawForm === 'EYE DROPS') {
            rawForm = 'EYE DROP';
        } else if (rawForm === 'EAR DROPS') {
            rawForm = 'EAR DROP';
        } else if (rawForm === 'DROPS (PAEDIATRIC)' || rawForm === 'ORAL DROPS' || rawForm === 'DROP') {
            rawForm = 'DROPS';
        } else if (rawForm === 'POWDERS' || rawForm === 'ORAL POWDER') {
            rawForm = 'POWDER';
        } else if (rawForm === 'LOTIONS') {
            rawForm = 'LOTION';
        } else if (rawForm === 'SPRAYS') {
            rawForm = 'SPRAY';
        } else if (rawForm === 'PATCHES') {
            rawForm = 'PATCH';
        } else if (rawForm === 'SACHETS' || rawForm === '1 SACHET' || rawForm === 'GRANULES') {
            rawForm = 'SACHET';
        } else if (rawForm === 'SUSPENSION' || rawForm === 'SUSPENSIONS') {
            rawForm = 'SUSP';
        } else if (rawForm === 'SOLUTION' || rawForm === 'SOLUTIONS' || rawForm === 'ORAL SOLUTION') {
            rawForm = 'SOLN';
        } else if (rawForm === 'SHAMPOOS') {
            rawForm = 'SHAMPOO';
        } else if (rawForm === 'SOAPS' || rawForm === 'SOAP BAR') {
            rawForm = 'SOAP';
        } else if (rawForm === 'OILS') {
            rawForm = 'OIL';
        } else if (rawForm === 'INHALERS' || rawForm === 'ROTACAP' || rawForm === 'ROTACAPS' || rawForm === 'INHALTION' || rawForm === 'INHALATION' || rawForm === 'MDI') {
            rawForm = 'INHALER';
        } else if (rawForm === 'RESPULE' || rawForm === 'NEBULISER' || rawForm === 'NEBULIZER') {
            rawForm = 'RESPULES';
        } else if (rawForm === 'MOUTHWASH' || rawForm === 'MOUTH WASH' || rawForm === 'GARGLES') {
            rawForm = 'GARGLE';
        } else if (rawForm === 'SUPPOSITORIES') {
            rawForm = 'SUPPOSITORY';
        } else if (rawForm === 'PESSARIES' || rawForm === 'VAGINAL PESSARY' || rawForm === 'VAGINAL TAB' || rawForm === 'VAGINAL TABLET') {
            rawForm = 'PESSARY';
        }
        if (rawForm && !$('#med_formulation option[value="' + rawForm.replace(/"/g, '&quot;') + '"]').length) {
            $('#med_formulation').append('<option value="' + $('<div>').text(rawForm).html() + '">' + $('<div>').text(rawForm).html() + '</option>');
        }
        $('#med_formulation').val(rawForm);
        $('#med_genericname').val(row.genericname || '');
        $('#med_salt_name').val(row.salt_name || row.sal_name || row.salt || row.saltname || '');
        $('#med_dosage_restriction').val(row.dosage_restriction || row.dose_restriction || row.restriction_note || row.restriction || '');
        $('#med_company_name').val(row.company_name || '');
        $('#med_snomed_code').val(row.snomed_code || row.medicine_snomed_code || row.snomed_ct_code || row.sctid || '');
        // Show FSN display if SNOMED code already set
        var existingSnomed = ($('#med_snomed_code').val() || '').trim();
        if (existingSnomed) {
            $('#snomed_fsn_display').text('SNOMED: ' + existingSnomed + ' — click SNOMED to verify/update').show();
        } else {
            $('#snomed_fsn_display').hide();
        }
        $('#med_atc_code').val(row.atc_code || row.who_atc_code || '');
        $('#med_default_dosage').val(row.dosage || '');
        setDefaultMasterValue($('#med_default_when'), row.dosage_when || '');
        setDefaultMasterValue($('#med_default_freq'), row.dosage_freq || '');
        setDefaultMasterValue($('#med_default_where'), row.dosage_where || '');
        $('#med_default_days').val(row.no_of_days || '');
        $('#med_default_qty').val(row.qty || '');
        $('#med_default_remark').val(row.remark || '');
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

    function renderCodingSummary(summary) {
        summary = summary || {};
        var total = parseInt(summary.total || 0, 10);
        var genericMissing = parseInt(summary.generic_missing || 0, 10);
        var codingMissing = parseInt(summary.coding_missing || 0, 10);
        var snomedMissing = parseInt(summary.snomed_missing || 0, 10);
        var atcMissing = parseInt(summary.atc_missing || 0, 10);
        var bothMissing = parseInt(summary.both_missing || 0, 10);

        var html = ''
            + '<span class="badge bg-light text-dark border me-1">Total: ' + total + '</span>'
            + '<span class="badge bg-danger me-1">Generic/Salt Missing: ' + genericMissing + '</span>'
            + '<span class="badge bg-warning text-dark me-1">SNOMED Missing: ' + snomedMissing + '</span>'
            + '<span class="badge bg-info text-dark me-1">ATC Missing: ' + atcMissing + '</span>'
            + '<span class="badge bg-secondary me-1">Any Coding Missing: ' + codingMissing + '</span>'
            + '<span class="badge bg-dark">Both Missing: ' + bothMissing + '</span>';

        $('#med_coding_summary').html(html);
    }

    function loadCodingSummary() {
        apiGet('<?= base_url('Opd_prescription/opd_medicince_coding_summary') ?>', function(data) {
            if ((data && data.update) != 1) {
                $('#med_coding_summary').html('<span class="badge bg-secondary">Coding summary unavailable</span>');
                return;
            }
            renderCodingSummary(data.summary || {});
        });
    }

    function loadList() {
        loadCodingSummary();
        initMedMasterDataTable();
        if (medMasterDataTable) {
            medMasterDataTable.ajax.reload(null, true);
        }
    }

    function initMedMasterDataTable() {
        if (!window.jQuery || !$.fn || typeof $.fn.DataTable !== 'function') {
            return;
        }

        if (medMasterDataTable) {
            return;
        }

        function esc(value) {
            return $('<div>').text(value || '').html();
        }

        medMasterDataTable = $('#tbl_med_master').DataTable({
            processing: true,
            serverSide: true,
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            pageLength: 25,
            ajax: {
                url: '<?= base_url('Opd_prescription/opd_medicince_data') ?>',
                type: 'GET',
                data: function(d) {
                    d.filter = activeListFilter;
                    d.scope = 'all';
                    d.show_all = 1;
                },
                dataSrc: function(json) {
                    var total = parseInt((json && json.recordsFiltered) || 0, 10);
                    if (!total) {
                        if (activeListFilter === 'generic_issue') {
                            setMsg('normal', 'No record found with Generic/Salt issue.');
                        } else if (activeListFilter === 'generic_same_name') {
                            setMsg('normal', 'No record found where Generic/Salt is same as Medicine Name.');
                        } else if (activeListFilter === 'company_blank') {
                            setMsg('normal', 'No record found with blank Company Name.');
                        } else if (activeListFilter === 'coding_missing') {
                            setMsg('normal', 'No record found with missing SNOMED/ATC coding.');
                        } else if (activeListFilter === 'snomed_missing') {
                            setMsg('normal', 'No record found with missing SNOMED code.');
                        } else if (activeListFilter === 'atc_missing') {
                            setMsg('normal', 'No record found with missing ATC code.');
                        } else {
                            setMsg('normal', 'No medicine found.');
                        }
                    } else if (activeListFilter === 'generic_issue') {
                        setMsg('normal', total + ' record(s) with unclear Generic/Salt data.');
                    } else if (activeListFilter === 'generic_same_name') {
                        setMsg('normal', total + ' record(s) where Generic/Salt matches Medicine Name.');
                    } else if (activeListFilter === 'company_blank') {
                        setMsg('normal', total + ' record(s) with blank/unclear Company Name.');
                    } else if (activeListFilter === 'coding_missing') {
                        setMsg('normal', total + ' record(s) with missing SNOMED or ATC code.');
                    } else if (activeListFilter === 'snomed_missing') {
                        setMsg('normal', total + ' record(s) with missing SNOMED code.');
                    } else if (activeListFilter === 'atc_missing') {
                        setMsg('normal', total + ' record(s) with missing ATC code.');
                    } else {
                        setMsg('normal', '');
                    }
                    return (json && json.data) ? json.data : [];
                },
                error: function() {
                    setMsg('err', 'Unable to load medicine list. Try Refresh.');
                }
            },
            order: [[3, 'desc'], [2, 'asc']],
            columns: [
                {
                    data: 'id',
                    render: function(data) {
                        return parseInt(data || 0, 10);
                    }
                },
                {
                    data: 'is_favorite',
                    render: function(data, type, row) {
                        var medId = parseInt((row && row.id) || 0, 10);
                        var isFav = parseInt(data || 0, 10) === 1;
                        return '<button type="button" class="btn btn-sm ' + (isFav ? 'btn-warning' : 'btn-outline-warning') + ' btn-med-fav" data-id="' + medId + '">' + (isFav ? '★' : '☆') + '</button>';
                    }
                },
                {
                    data: 'item_name',
                    render: function(data, type, row) {
                        var name = String(data || '').trim();
                        var formulation = String((row && row.formulation) ? row.formulation : '').trim();
                        if (!name) {
                            return '';
                        }
                        return formulation ? (esc(name) + ' (' + esc(formulation) + ')') : esc(name);
                    }
                },
                {
                    data: 'use_count',
                    render: function(data) {
                        return parseInt(data || 0, 10);
                    }
                },
                {
                    data: 'last_used_at',
                    render: function(data) {
                        return esc(data);
                    }
                },
                {
                    data: 'genericname',
                    render: function(data) {
                        return esc(data);
                    }
                },
                {
                    data: 'company_name',
                    render: function(data) {
                        return esc(data);
                    }
                },
                {
                    data: 'id',
                    render: function(data) {
                        var medId = parseInt(data || 0, 10);
                        return '<button type="button" class="btn btn-sm btn-outline-primary btn-med-edit" data-id="' + medId + '">Edit</button>';
                    }
                },
                {
                    data: 'id',
                    render: function(data) {
                        var medId = parseInt(data || 0, 10);
                        return '<button type="button" class="btn btn-sm btn-outline-danger btn-med-remove" data-id="' + medId + '">Remove</button>';
                    }
                }
            ],
            columnDefs: [
                { orderable: false, targets: [1, 7, 8] },
            ],
            language: {
                emptyTable: 'No medicine found'
            }
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

    // ── SNOMED CT substance search ────────────────────────────────────────────
    function closeSnomedPanel() {
        $('#snomed_suggest_panel').hide().empty();
    }

    function renderSnomedPanel(rows) {
        var $panel = $('#snomed_suggest_panel');
        $panel.empty();

        if (!rows || !rows.length) {
            $panel.append('<div class="px-3 py-2 text-muted small">No SNOMED CT substance concepts found.</div>');
            $panel.show();
            return;
        }

        rows.forEach(function(row) {
            var conceptId  = String(row.concept_id || '');
            var displayTerm = String(row.display_term || row.term || '');
            var fsn        = String(row.fsn || '');
            var hierarchy  = String(row.hierarchy || '');
            var source     = String(row.source || '');
            var badgeCls   = (source === 'csnotk') ? 'bg-primary' : 'bg-secondary';
            var badgeLabel = (source === 'csnotk') ? 'API' : 'local';

            var $item = $('<div class="px-3 py-2 border-bottom snomed-suggest-item" style="cursor:pointer;">')
                .data('concept-id', conceptId)
                .data('display-term', displayTerm)
                .data('fsn', fsn);

            $item.html(
                '<div class="d-flex justify-content-between align-items-start">'
                + '<div>'
                + '<span class="fw-semibold">' + $('<div>').text(displayTerm).html() + '</span>'
                + (hierarchy ? ' <span class="badge bg-light text-dark border">' + $('<div>').text(hierarchy).html() + '</span>' : '')
                + '</div>'
                + '<span class="badge ' + badgeCls + ' ms-2 flex-shrink-0">' + badgeLabel + '</span>'
                + '</div>'
                + '<div class="text-muted small">' + $('<div>').text(conceptId).html() + (fsn && fsn !== displayTerm ? ' — ' + $('<div>').text(fsn).html() : '') + '</div>'
            );

            $item.on('mouseenter', function() { $(this).addClass('bg-light'); })
                 .on('mouseleave', function() { $(this).removeClass('bg-light'); });

            $item.on('click', function() {
                var cid  = String($(this).data('concept-id') || '');
                var dTerm = String($(this).data('display-term') || '');
                var fsnVal = String($(this).data('fsn') || '');

                $('#med_snomed_code').val(cid);

                var fsnText = fsnVal || dTerm;
                if (fsnText) {
                    $('#snomed_fsn_display').text('▶ ' + fsnText).show();
                } else {
                    $('#snomed_fsn_display').hide();
                }

                // Auto-fill generic name if currently empty
                if (dTerm && ($('#med_genericname').val() || '').trim() === '') {
                    $('#med_genericname').val(dTerm);
                    setMsg('normal', 'SNOMED concept selected. Generic name pre-filled — review before saving.');
                } else {
                    setMsg('normal', 'SNOMED concept selected (ID: ' + cid + ').');
                }

                closeSnomedPanel();
            });

            $panel.append($item);
        });

        $panel.show();
    }

    function snomedSearch(term) {
        if (!term || term.length < 2) {
            closeSnomedPanel();
            return;
        }
        var $btn = $('#btn_snomed_search');
        $btn.prop('disabled', true).text('...');
        var url = '<?= base_url('Opd_prescription/opd_medicince_snomed_lookup') ?>?term=' + encodeURIComponent(term) + '&limit=12';
        apiGet(url, function(data) {
            $btn.prop('disabled', false).html('<i class="fa fa-search"></i> SNOMED');
            if ((data.update || 0) != 1) {
                setMsg('err', data.message || 'SNOMED lookup failed');
                return;
            }
            renderSnomedPanel(data.rows || []);
            if (data.message) {
                setMsg('normal', data.message);
            }
        });
    }

    $('#btn_snomed_search').on('click', function() {
        var term = ($('#med_item_name').val() || '').trim();
        if (!term) {
            // Fallback: try the SNOMED code field value as a term
            term = ($('#med_snomed_code').val() || '').trim();
        }
        if (!term) {
            setMsg('err', 'Enter a medicine name first, then click SNOMED to search.');
            return;
        }
        snomedSearch(term);
    });

    // Close SNOMED panel on outside click
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#snomed_suggest_panel, #btn_snomed_search, #med_snomed_code').length) {
            closeSnomedPanel();
        }
    });

    // Clear FSN display when SNOMED code is manually cleared
    $('#med_snomed_code').on('input', function() {
        if (!$(this).val().trim()) {
            $('#snomed_fsn_display').hide();
        }
    });

    // ── Drug Master Search, Auto-Fill & Bulk Sync ─────────────────────────────
    var drugMasterSuggestTimer = null;
    var bulkSyncRunning = false;
    var bulkSyncStopRequested = false;

    function closeDrugMasterPanel() {
        $('#drug_master_suggest_panel').hide().empty();
    }

    function applyDrugMasterDetailsToForm(details, messageText) {
        details = details || {};
        var gen = String(details.genericname || details.generic_name || '').trim();
        var salt = String(details.salt_name || '').trim();
        var form = String(details.formulation || '').trim().toUpperCase();
        var comp = String(details.company_name || '').trim();
        var snomed = String(details.snomed_code || details.identifier || '').trim();
        var route = String(details.route || '').trim();
        var restriction = String(details.dosage_restriction || '').trim();

        if (gen !== '') {
            $('#med_genericname').val(gen);
        }
        if (salt !== '') {
            $('#med_salt_name').val(salt);
        } else {
            // Clear salt_name if it was previously a duplicate of genericname
            var curSalt = ($('#med_salt_name').val() || '').trim();
            if (curSalt !== '' && gen !== '' && curSalt.toLowerCase() === gen.toLowerCase()) {
                $('#med_salt_name').val('');
            }
        }
        if (form !== '') {
            if (!$('#med_formulation option[value="' + form.replace(/"/g, '&quot;') + '"]').length) {
                $('#med_formulation').append('<option value="' + $('<div>').text(form).html() + '">' + $('<div>').text(form).html() + '</option>');
            }
            $('#med_formulation').val(form);
        }
        if (comp !== '' && comp !== '0') {
            $('#med_company_name').val(comp);
        }
        if (snomed !== '') {
            $('#med_snomed_code').val(snomed);
            $('#snomed_fsn_display').text('▶ Drug Master ID: ' + snomed).show();
        }
        if (route !== '' && ($('#med_default_where').val() || '').trim() === '') {
            setDefaultMasterValue($('#med_default_where'), route);
        }
        if (restriction !== '' && ($('#med_dosage_restriction').val() || '').trim() === '') {
            $('#med_dosage_restriction').val(restriction);
        }

        if (messageText) {
            setMsg('ok', messageText);
        }
    }

    function fetchDrugMasterDetails(extraParams) {
        var itemName = ($('#med_item_name').val() || '').trim();
        var identifier = (extraParams && extraParams.identifier) ? String(extraParams.identifier) : '';
        if (!itemName && !identifier) {
            setMsg('err', 'Enter medicine name first to fetch from Drug Master.');
            return;
        }

        var $btn = $('#btn_med_ai_details');
        $btn.prop('disabled', true).text('Fetching...');
        apiPost('<?= base_url('Opd_prescription/opd_medicince_drug_master_fetch') ?>', {
            item_name: itemName,
            formulation: ($('#med_formulation').val() || '').trim(),
            med_id: parseInt($('#med_id').val() || '0', 10),
            identifier: identifier,
            type: (extraParams && extraParams.type) ? String(extraParams.type) : 'brand'
        }, function(data) {
            $btn.prop('disabled', false).text('Fetch from Drug Master');
            if ((data.update || 0) != 1) {
                setMsg('err', data.error_text || 'No match found in Drug Master');
                return;
            }

            applyDrugMasterDetailsToForm(
                data.details || {},
                (data.error_text || 'Details fetched from Drug Master') + '. Review and click Update.'
            );
        });
    }

    function renderDrugMasterPanel(rows) {
        var $panel = $('#drug_master_suggest_panel');
        $panel.empty();

        if (!rows || !rows.length) {
            $panel.append('<div class="px-3 py-2 text-muted small">No matches found in Pharmacy / ABDM Drug Master.</div>');
            $panel.show();
            return;
        }

        rows.forEach(function(row) {
            var display = String(row.display_name || row.brand_name || '');
            var brand = String(row.brand_name || display);
            var generic = String(row.generic_name || '');
            var form = String(row.formulation || '');
            var comp = String(row.company_name || '');
            var snomed = String(row.snomed_code || row.identifier || '');
            var source = String(row.source || 'Drug Master');
            var badgeCls = (source === 'Pharmacy Master') ? 'bg-success' : 'bg-primary';

            var $item = $('<div class="px-3 py-2 border-bottom drug-master-suggest-item" style="cursor:pointer;">').data('row', row);
            $item.html(
                '<div class="d-flex justify-content-between align-items-start">'
                + '<div>'
                + '<span class="fw-semibold">' + $('<div>').text(display).html() + '</span>'
                + (form ? ' <span class="badge bg-light text-dark border">' + $('<div>').text(form).html() + '</span>' : '')
                + '</div>'
                + '<span class="badge ' + badgeCls + ' ms-2 flex-shrink-0">' + $('<div>').text(source).html() + '</span>'
                + '</div>'
                + '<div class="text-success small">Salt: ' + $('<div>').text(generic || 'N/A').html() + '</div>'
                + '<div class="text-muted small">'
                + (comp ? 'Mfg: ' + $('<div>').text(comp).html() : '')
                + (snomed ? (comp ? ' | ' : '') + 'Code: ' + $('<div>').text(snomed).html() : '')
                + '</div>'
            );

            $item.on('mouseenter', function() { $(this).addClass('bg-light'); })
                 .on('mouseleave', function() { $(this).removeClass('bg-light'); });

            $item.on('click', function() {
                var r = $(this).data('row') || {};
                if (($('#med_id').val() || '0') === '0' && r.brand_name) {
                    $('#med_item_name').val(r.brand_name);
                }
                applyDrugMasterDetailsToForm({
                    genericname: r.generic_name || '',
                    formulation: r.formulation || '',
                    company_name: r.company_name || '',
                    snomed_code: r.snomed_code || '',
                    route: r.route || ''
                }, 'Selected from ' + (r.source || 'Drug Master') + '. Fetching substitutes...');
                closeDrugMasterPanel();

                // Also fetch substitute brands from backend
                fetchDrugMasterDetails({
                    identifier: r.identifier || '',
                    type: r.type || 'brand'
                });
            });

            $panel.append($item);
        });

        $panel.show();
    }

    function searchDrugMasterAutocomplete(term) {
        term = (term || '').trim();
        if (term.length < 2) {
            closeDrugMasterPanel();
            return;
        }
        var $btn = $('#btn_drug_master_search');
        $btn.prop('disabled', true).text('...');
        apiGet('<?= base_url('Opd_prescription/opd_medicince_drug_master_search') ?>?q=' + encodeURIComponent(term) + '&limit=10', function(data) {
            $btn.prop('disabled', false).html('<i class="fa fa-search"></i> Drug Master');
            renderDrugMasterPanel((data && data.rows) ? data.rows : []);
        });
    }

    $('#btn_drug_master_search').on('click', function() {
        var term = ($('#med_item_name').val() || '').trim();
        if (!term) {
            setMsg('err', 'Enter a medicine name first.');
            return;
        }
        searchDrugMasterAutocomplete(term);
    });

    $('#med_item_name').on('input', function() {
        var term = String($(this).val() || '').trim();
        if (drugMasterSuggestTimer) {
            clearTimeout(drugMasterSuggestTimer);
        }
        if (term.length < 3) {
            closeDrugMasterPanel();
            return;
        }
        drugMasterSuggestTimer = setTimeout(function() {
            searchDrugMasterAutocomplete(term);
        }, 320);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#drug_master_suggest_panel, #btn_drug_master_search, #med_item_name').length) {
            closeDrugMasterPanel();
        }
    });

    $('#btn_med_ai_details').on('click', function() {
        fetchDrugMasterDetails({});
    });

    // ── Bulk Sync Missing Salt from Drug Master ──────────────────────────────
    $('#btn_med_bulk_sync_stop').on('click', function() {
        bulkSyncStopRequested = true;
        $(this).prop('disabled', true).text('Stopping...');
    });

    $('#btn_med_bulk_sync_salt').on('click', function() {
        if (bulkSyncRunning) {
            return;
        }
        if (!window.confirm('Update missing Generic/Salt, Substitute brands, Company Name & SNOMED codes for OPD medicines from Pharmacy Drug Master and ABDM Drug Master API now?')) {
            return;
        }

        bulkSyncRunning = true;
        bulkSyncStopRequested = false;
        var totalProcessed = 0;
        var totalUpdated = 0;
        var initialRemaining = 0;

        $('#btn_med_bulk_sync_salt').prop('disabled', true).text('Syncing...');
        $('#btn_med_bulk_sync_stop').prop('disabled', false).text('Stop').show();
        $('#med_bulk_sync_title').text('Syncing missing Salt/Generic from Drug Master...');
        $('#med_bulk_sync_bar').css('width', '5%').addClass('progress-bar-animated');
        $('#med_bulk_sync_status').text('Starting batch sync...');
        $('#med_bulk_sync_box').show();

        function finishBulkSync(reasonText) {
            bulkSyncRunning = false;
            $('#btn_med_bulk_sync_salt').prop('disabled', false).text('Update Salt (Drug Master)');
            $('#btn_med_bulk_sync_stop').hide();
            $('#med_bulk_sync_bar').removeClass('progress-bar-animated').css('width', '100%');
            $('#med_bulk_sync_title').text(reasonText || 'Drug Master Sync Completed');
            $('#med_bulk_sync_status').text('Processed: ' + totalProcessed + ' | Updated: ' + totalUpdated + ' medicine(s) with Salt/Generic details.');
            showToast('Updated ' + totalUpdated + ' medicine(s) from Drug Master.', 'ok');
            loadList();
        }

        function runNextBatch(lastId) {
            if (bulkSyncStopRequested) {
                finishBulkSync('Drug Master Sync Stopped by User');
                return;
            }

            apiPost('<?= base_url('Opd_prescription/opd_medicince_bulk_sync_drug_master') ?>', {
                last_id: lastId,
                batch_size: 25
            }, function(data) {
                if ((data.update || 0) != 1) {
                    finishBulkSync('Sync stopped: ' + (data.error_text || 'Error'));
                    return;
                }

                var proc = parseInt(data.processed_in_batch || 0, 10);
                var upd = parseInt(data.updated_in_batch || 0, 10);
                var rem = parseInt(data.remaining || 0, 10);
                var nextLastId = parseInt(data.last_id || lastId, 10);

                totalProcessed += proc;
                totalUpdated += upd;
                if (initialRemaining <= 0) {
                    initialRemaining = totalProcessed + rem;
                }

                var pct = initialRemaining > 0 ? Math.min(99, Math.round((totalProcessed / initialRemaining) * 100)) : 100;
                $('#med_bulk_sync_bar').css('width', pct + '%');
                $('#med_bulk_sync_status').text(
                    'Processed: ' + totalProcessed + ' / ' + initialRemaining
                    + ' | Updated: ' + totalUpdated
                    + ' | Remaining: ' + rem
                );

                if (data.has_more && proc > 0 && !bulkSyncStopRequested) {
                    setTimeout(function() {
                        runNextBatch(nextLastId);
                    }, 120);
                } else {
                    finishBulkSync('Drug Master Sync Completed');
                }
            });
        }

        runNextBatch(0);
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
    loadList();
    loadDuplicateReport();
    loadCompanySuggestions('');
    loadDefaultDoseMasters(function() {
        var initialMedId = parseInt($('#med_id').val() || '0', 10);
        if (initialMedId > 0) {
            loadOne(initialMedId);
        }
    });
})();
</script>

<?php $doc = $doc_master[0] ?? null; ?>
<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h3 class="mb-0">Dr. <?= esc($doc->p_fname ?? '') ?> <small class="text-muted"><?= esc($doc->Spec ?? '') ?></small></h3>
        <a href="javascript:load_form('<?= base_url('Opd/get_appointment_data') ?>/<?= esc($opd_date) ?>','OPD Appointment List');" class="btn btn-link">Back to OPD</a>
    </div>
</section>

<section class="content">
    <div class="row mb-2">
        <div class="col-md-2"><strong>Booked :</strong> <?= esc((int) ($doc->count_booking ?? 0)) ?></div>
        <div class="col-md-2"><strong>Waiting :</strong> <?= esc((int) ($doc->count_wait ?? 0)) ?></div>
        <div class="col-md-2"><strong>Visited :</strong> <?= esc((int) ($doc->count_visit ?? 0)) ?></div>
        <div class="col-md-2"><strong>Cancelled :</strong> <?= esc((int) ($doc->count_cancel ?? 0)) ?></div>
        <div class="col-md-2"><strong>Total :</strong> <?= esc((int) ($doc->No_opd ?? 0)) ?></div>
    </div>

    <ul class="nav nav-tabs" id="opdQueueTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab_booking" type="button">Booked</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_waiting" type="button">Waiting <span id="waitingMissingBadge" class="badge rounded-pill bg-warning text-dark ms-1 d-none" role="button" title="Show only patients with missing vitals">0</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_visited" type="button">Visited</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_cancelled" type="button">Cancelled</button></li>
    </ul>

    <div class="tab-content border border-top-0 p-2 bg-white" id="opdQueueTabContent">
        <div class="tab-pane fade show active" id="tab_booking">
            <?= view('billing/opd_appointment_list_table', ['rows' => $opd_list_0, 'showQueue' => false, 'tabType' => 'booking', 'opd_date' => $opd_date, 'doc_id' => $doc_id]) ?>
        </div>
        <div class="tab-pane fade" id="tab_waiting">
            <div class="d-flex justify-content-end mb-2">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="toggle_missing_vitals_only">
                    <label class="form-check-label" for="toggle_missing_vitals_only">Only Missing Vitals</label>
                </div>
            </div>
            <?= view('billing/opd_appointment_list_table', ['rows' => $opd_list_1, 'showQueue' => true, 'tabType' => 'waiting', 'opd_date' => $opd_date, 'doc_id' => $doc_id]) ?>
            <div id="waitingNoRowsHint" class="small text-muted mt-2 d-none">All waiting patients already have vitals.</div>
        </div>
        <div class="tab-pane fade" id="tab_visited">
            <?= view('billing/opd_appointment_list_table', ['rows' => $opd_list_2, 'showQueue' => true, 'tabType' => 'visited', 'opd_date' => $opd_date, 'doc_id' => $doc_id]) ?>
        </div>
        <div class="tab-pane fade" id="tab_cancelled">
            <?= view('billing/opd_appointment_list_table', ['rows' => $opd_list_3, 'showQueue' => true, 'tabType' => 'cancelled', 'opd_date' => $opd_date, 'doc_id' => $doc_id]) ?>
        </div>
    </div>
</section>

<div class="modal fade" id="tallModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testentryLabel">OPD Scan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height:75vh;overflow:auto;">
                <div id="testentry-bodyc"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="vitalsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vitalsModalLabel">Nursing Vitals Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?= csrf_field() ?>
                <input type="hidden" id="vital_opd_id" value="0">
                <input type="hidden" id="vital_opd_session_id" value="0">

                <div class="small text-muted mb-2" id="vital_patient_label">Patient: -</div>
                <div class="row g-2">
                    <div class="col-md-2"><input type="text" class="form-control form-control-sm" id="vital_pulse" placeholder="Pulse"></div>
                    <div class="col-md-2"><input type="text" class="form-control form-control-sm" id="vital_spo2" placeholder="SPO2"></div>
                    <div class="col-md-2"><input type="text" class="form-control form-control-sm" id="vital_bp" placeholder="BP Systolic"></div>
                    <div class="col-md-2"><input type="text" class="form-control form-control-sm" id="vital_diastolic" placeholder="Diastolic"></div>
                    <div class="col-md-2"><input type="text" class="form-control form-control-sm" id="vital_temp" placeholder="Temp"></div>
                    <div class="col-md-2"><input type="text" class="form-control form-control-sm" id="vital_rr_min" placeholder="RR/min"></div>
                    <div class="col-md-4"><input type="text" class="form-control form-control-sm" id="vital_height" placeholder="Height"></div>
                    <div class="col-md-4"><input type="text" class="form-control form-control-sm" id="vital_weight" placeholder="Weight"></div>
                    <div class="col-md-4"><input type="text" class="form-control form-control-sm" id="vital_waist" placeholder="Waist"></div>
                </div>

                <div class="small mt-2 text-muted" id="vital_msg">Fill vitals and click Save.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm" id="btn_save_vitals">Save Vitals</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // Reusable Bootstrap modal instances for scan and vitals dialogs.
    var modalEl = document.getElementById('tallModal');
    var modalObj = modalEl ? new bootstrap.Modal(modalEl) : null;
    var vitalsModalEl = document.getElementById('vitalsModal');
    var vitalsModalObj = vitalsModalEl ? new bootstrap.Modal(vitalsModalEl) : null;

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

    function setVitalMessage(type, text) {
        var $msg = $('#vital_msg');
        $msg.removeClass('text-muted text-success text-danger');
        if (type === 'ok') {
            $msg.addClass('text-success');
        } else if (type === 'err') {
            $msg.addClass('text-danger');
        } else {
            $msg.addClass('text-muted');
        }
        $msg.text(text || '');
    }

    function fillVitals(vitals) {
        vitals = vitals || {};
        $('#vital_pulse').val(vitals.pulse || '');
        $('#vital_spo2').val(vitals.spo2 || '');
        $('#vital_bp').val(vitals.bp || '');
        $('#vital_diastolic').val(vitals.diastolic || '');
        $('#vital_temp').val(vitals.temp || '');
        $('#vital_rr_min').val(vitals.rr_min || '');
        $('#vital_height').val(vitals.height || '');
        $('#vital_weight').val(vitals.weight || '');
        $('#vital_waist').val(vitals.waist || '');
    }

    function applyMissingVitalsFilter() {
        // Filter waiting rows to optionally show only patients whose vitals are still missing.
        var onlyMissing = $('#toggle_missing_vitals_only').is(':checked');
        var $rows = $('#tab_waiting tbody tr[data-opd-id]');
        var visibleCount = 0;

        $rows.each(function() {
            var hasVitals = parseInt($(this).attr('data-has-vitals') || '0', 10) === 1;
            var show = !onlyMissing || !hasVitals;
            $(this).toggle(show);
            if (show) {
                visibleCount++;
            }
        });

        $('#waitingNoRowsHint').toggleClass('d-none', !(onlyMissing && $rows.length > 0 && visibleCount === 0));
    }

    function updateMissingVitalsBadge() {
        // Count waiting rows missing vitals and show badge only when count > 0.
        var missingCount = 0;
        $('#tab_waiting tbody tr[data-opd-id]').each(function() {
            var hasVitals = parseInt($(this).attr('data-has-vitals') || '0', 10) === 1;
            if (!hasVitals) {
                missingCount++;
            }
        });

        var $badge = $('#waitingMissingBadge');
        $badge.text(String(missingCount));
        $badge.toggleClass('d-none', missingCount <= 0);
    }

    $(document).off('click.opdscanopen', '.btn-opd-scan').on('click.opdscanopen', '.btn-opd-scan', function() {
        // Open the scan modal and lazy-load the scan/upload panel for the selected OPD.
        var opdid = parseInt($(this).data('opdid') || '0', 10);
        if (!opdid || !modalObj) {
            return;
        }

        $('#testentryLabel').text('OPD Scan');
        $('#testentry-bodyc').html('<div class="text-muted">Loading...</div>');
        modalObj.show();

        $.post('<?= base_url('Opd/opd_load_doc') ?>/' + opdid, {}, function(html) {
            $('#testentry-bodyc').html(html || '<div class="text-danger">Unable to load scan panel.</div>');
        }).fail(function() {
            $('#testentry-bodyc').html('<div class="text-danger">Unable to load scan panel.</div>');
        });
    });

    $(document).off('click.opdvitalsopen', '.btn-opd-vitals').on('click.opdvitalsopen', '.btn-opd-vitals', function() {
        // Open vitals modal and prefill with any existing values from prescription session.
        var opdid = parseInt($(this).data('opdid') || '0', 10);
        var patient = ($(this).data('patient') || '').toString();
        if (!opdid || !vitalsModalObj) {
            return;
        }

        $('#vital_opd_id').val(opdid);
        $('#vital_opd_session_id').val('0');
        $('#vital_patient_label').text('Patient: ' + (patient || '-'));
        fillVitals({});
        setVitalMessage('normal', 'Loading previous vitals...');
        vitalsModalObj.show();

        $.getJSON('<?= base_url('Opd_prescription/vitals_get') ?>/' + opdid, function(data) {
            updateCsrf(data);
            if (parseInt(data.update || 0, 10) !== 1) {
                setVitalMessage('err', data.error_text || 'Unable to load vitals');
                return;
            }

            $('#vital_opd_session_id').val(parseInt(data.opd_session_id || '0', 10));
            fillVitals(data.vitals || {});
            setVitalMessage('normal', 'Vitals loaded.');
        }).fail(function() {
            setVitalMessage('err', 'Unable to load vitals');
        });
    });

    $('#btn_save_vitals').on('click', function() {
        // Persist vitals, then update the waiting list row state without full-page refresh.
        var opdId = parseInt($('#vital_opd_id').val() || '0', 10);
        if (opdId <= 0) {
            setVitalMessage('err', 'Invalid OPD selected');
            return;
        }

        var csrf = getCsrfPair();
        var payload = {
            opd_id: opdId,
            opd_session_id: parseInt($('#vital_opd_session_id').val() || '0', 10),
            pulse: ($('#vital_pulse').val() || '').trim(),
            spo2: ($('#vital_spo2').val() || '').trim(),
            bp: ($('#vital_bp').val() || '').trim(),
            diastolic: ($('#vital_diastolic').val() || '').trim(),
            temp: ($('#vital_temp').val() || '').trim(),
            rr_min: ($('#vital_rr_min').val() || '').trim(),
            height: ($('#vital_height').val() || '').trim(),
            weight: ($('#vital_weight').val() || '').trim(),
            waist: ($('#vital_waist').val() || '').trim()
        };
        payload[csrf.name] = csrf.value;

        var $btn = $(this);
        $btn.prop('disabled', true).text('Saving...');
        $.post('<?= base_url('Opd_prescription/vitals_save') ?>', payload, function(data) {
            updateCsrf(data);
            $btn.prop('disabled', false).text('Save Vitals');
            if (parseInt(data.update || 0, 10) !== 1) {
                setVitalMessage('err', data.error_text || 'Unable to save vitals');
                return;
            }

            $('#vital_opd_session_id').val(parseInt(data.opd_session_id || '0', 10));
            setVitalMessage('ok', data.error_text || 'Vitals saved successfully');

            var $row = $('#tab_waiting tbody tr[data-opd-id="' + opdId + '"]');
            if ($row.length) {
                $row.attr('data-has-vitals', '1');
                var $vitalBtn = $row.find('td:last').find('.btn-opd-vitals').first();
                if ($vitalBtn.length) {
                    var btnText = ($vitalBtn.text() || '').trim();
                    if (btnText.indexOf('✓') === -1) {
                        $vitalBtn.text('Vitals ✓');
                    }
                    $vitalBtn.removeClass('btn-warning text-dark btn-outline-warning').addClass('btn-success');
                    $vitalBtn.attr('title', 'Vitals Filled');
                }
                updateMissingVitalsBadge();
                applyMissingVitalsFilter();
            }
        }, 'json').fail(function() {
            $btn.prop('disabled', false).text('Save Vitals');
            setVitalMessage('err', 'Unable to save vitals');
        });
    });

    $(document).off('change.missingVitals', '#toggle_missing_vitals_only').on('change.missingVitals', '#toggle_missing_vitals_only', function() {
        applyMissingVitalsFilter();
    });

    $(document).off('click.missingBadge', '#waitingMissingBadge').on('click.missingBadge', '#waitingMissingBadge', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if ($(this).hasClass('d-none')) {
            return;
        }
        var $toggle = $('#toggle_missing_vitals_only');
        $toggle.prop('checked', true);
        applyMissingVitalsFilter();

        var waitingTabTrigger = document.querySelector('button[data-bs-target="#tab_waiting"]');
        if (waitingTabTrigger) {
            bootstrap.Tab.getOrCreateInstance(waitingTabTrigger).show();
        }
    });

    updateMissingVitalsBadge();
    applyMissingVitalsFilter();
})();
</script>

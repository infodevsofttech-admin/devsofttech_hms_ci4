<?php
$csrfName = csrf_token();
$csrfHash = csrf_hash();
$initialPatientId = (int) ($patient_id ?? 0);
?>
<div class="container-fluid py-3 immunization-ui">
    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0">Immunization Record</h5>
                <div class="small text-muted">Patient immunization schedule and vaccination completion</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLoadSchedule">UIP Schedule Master</button>
            </div>
        </div>
        <div class="card-body">
            <input type="hidden" id="csrfName" value="<?= esc($csrfName) ?>">
            <input type="hidden" id="csrfHash" value="<?= esc($csrfHash) ?>">

            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label mb-1">UHID / Patient ID</label>
                    <input type="text" class="form-control" id="patientId" value="<?= $initialPatientId > 0 ? esc((string) $initialPatientId) : '' ?>" placeholder="e.g. P26071000012">
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-primary w-100" id="btnLoadPatient">Load Patient</button>
                </div>
                <div class="col-md-7">
                    <div class="small text-muted pb-2">Enter UHID like P26071000012 or internal patient id.</div>
                </div>
            </div>

            <div class="mt-3" id="statusBox"></div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-header py-2 fw-semibold">Patient</div>
                <div class="card-body">
                    <div class="small text-muted mb-1">Selected patient</div>
                    <div class="fs-6 fw-semibold" id="patientName">-</div>
                    <div class="small mt-2" id="patientMeta">Enter a patient ID and load.</div>
                    <hr>
                    <div class="mb-2">
                        <label class="form-label mb-1">Generate Basis</label>
                        <select class="form-select form-select-sm" id="generationMode" disabled>
                            <option value="eligible" selected>Age/Gender eligible doses only</option>
                            <option value="full">Full UIP schedule</option>
                        </select>
                        <div class="small text-muted mt-1" id="generationHint">Load patient first.</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success w-100 mb-3" id="btnGenerate" disabled>Generate UIP</button>
                    <div class="row g-2 text-center" id="summaryGrid">
                        <div class="col-6"><div class="summary-tile"><span id="sumTotal">0</span><small>Total</small></div></div>
                        <div class="col-6"><div class="summary-tile"><span id="sumDue">0</span><small>Due</small></div></div>
                        <div class="col-6"><div class="summary-tile"><span id="sumOverdue">0</span><small>Overdue</small></div></div>
                        <div class="col-6"><div class="summary-tile"><span id="sumCompleted">0</span><small>Completed</small></div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <div class="fw-semibold">Vaccination Timeline</div>
                    <div class="small text-muted" id="recordCount">No records loaded</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" id="recordTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Age</th>
                                    <th>Vaccine</th>
                                    <th>Dose</th>
                                    <th>Due</th>
                                    <th>Given</th>
                                    <th>Status</th>
                                    <th>Lot</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="8" class="text-center text-muted py-4">No patient loaded.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-3 d-none" id="scheduleCard">
        <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <div class="fw-semibold">UIP Master Schedule</div>
            <div class="small text-muted" id="scheduleCount"></div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0" id="scheduleTable">
                    <thead class="table-light">
                        <tr>
                            <th>Age</th>
                            <th>Vaccine</th>
                            <th>Dose</th>
                            <th>Route</th>
                            <th>Target Disease</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="completeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title">Complete Vaccination</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="completeRecordId">
                    <div class="alert alert-light border mb-3" id="completeTitle"></div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label mb-1">Given On</label>
                            <input type="datetime-local" class="form-control" id="givenDate">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1">Lot Number</label>
                            <input type="text" class="form-control" id="lotNumber">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1">Expiry On</label>
                            <input type="date" class="form-control" id="expiryDate">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1">Manufacturer</label>
                            <input type="text" class="form-control" id="manufacturer">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1">Location</label>
                            <input type="text" class="form-control" id="locationName">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1">Performer ID</label>
                            <input type="number" min="1" step="1" class="form-control" id="performerId">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1">Reaction Notes</label>
                            <textarea class="form-control" rows="2" id="reactionNotes"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1">Notes</label>
                            <textarea class="form-control" rows="2" id="notes"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnSaveComplete">Save Completed</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="jsonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="jsonModalTitle">FHIR Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <pre class="json-preview mb-0" id="jsonPreview">{}</pre>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.immunization-ui .summary-tile {
    border: 1px solid #e3e7ee;
    border-radius: 6px;
    padding: 10px 8px;
    background: #f8fafc;
}
.immunization-ui .summary-tile span {
    display: block;
    font-weight: 700;
    font-size: 1.15rem;
    line-height: 1.1;
}
.immunization-ui .summary-tile small {
    color: #6c757d;
}
.immunization-ui .json-preview {
    min-height: 360px;
    max-height: 70vh;
    white-space: pre-wrap;
    word-break: break-word;
    background: #0f172a;
    color: #dbeafe;
    border-radius: 6px;
    padding: 14px;
    font-size: 12px;
}
</style>

<script>
(function () {
    var currentPatientId = 0;
    var records = [];

    function esc(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function csrfPayload() {
        var name = $('#csrfName').val();
        var hash = $('#csrfHash').val();
        var payload = {};
        payload[name] = hash;
        return payload;
    }

    function updateCsrf(response) {
        if (response && response.csrfName && response.csrfHash) {
            $('#csrfName').val(response.csrfName);
            $('#csrfHash').val(response.csrfHash);
        }
    }

    function ajaxError(xhr) {
        var json = xhr.responseJSON || {};
        return json.error_text || json.message || json.error || xhr.statusText || 'Request failed';
    }

    function showStatus(type, message) {
        var cls = type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-danger');
        $('#statusBox').html('<div class="alert ' + cls + ' py-2 mb-0">' + esc(message) + '</div>');
    }

    function selectedPatientKey() {
        return $.trim($('#patientId').val() || '');
    }

    function resolvePatientKey(callback) {
        var key = selectedPatientKey();
        if (!key) {
            showStatus('warning', 'Enter UHID / Patient ID.');
            return;
        }

        $.ajax({
            url: '<?= site_url('Document_Patient/open_by_key') ?>',
            method: 'GET',
            dataType: 'json',
            data: {patient_key: key},
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (data) {
            var resolvedId = parseInt(data.patient_id || 0, 10);
            if (parseInt(data.status || 0, 10) === 1 && resolvedId > 0) {
                currentPatientId = resolvedId;
                callback(resolvedId);
                return;
            }
            showStatus('warning', data.message || 'Patient not found');
        }).fail(function (xhr) {
            showStatus('warning', ajaxError(xhr));
        });
    }

    function badge(status, dueDate) {
        var raw = (status || 'due').toLowerCase();
        var label = raw;
        var cls = 'bg-secondary';
        var today = new window.Date().toISOString().slice(0, 10);
        if (raw === 'completed') {
            cls = 'bg-success';
        } else if (raw === 'due' && dueDate && dueDate < today) {
            cls = 'bg-danger';
            label = 'overdue';
        } else if (raw === 'due') {
            cls = 'bg-warning text-dark';
        }
        return '<span class="badge ' + cls + '">' + esc(label) + '</span>';
    }

    function renderPatient(data) {
        var patient = data.patient || {};
        var name = $.trim([(patient.p_fname || ''), (patient.p_lname || '')].join(' ')) || ('Patient #' + currentPatientId);
        $('#patientName').text(name);
        $('#patientMeta').html([
            patient.p_code ? 'UHID: ' + esc(patient.p_code) : '',
            patient.gender ? 'Gender: ' + esc(patient.gender) : '',
            patient.age ? 'Age: ' + esc(patient.age) : '',
            patient.age_in_month ? 'Age in months: ' + esc(patient.age_in_month) : '',
            patient.dob ? 'DOB: ' + esc(patient.dob) : '',
            patient.mphone1 ? 'Mobile: ' + esc(patient.mphone1) : ''
        ].filter(Boolean).join('<br>') || 'Patient loaded.');

        var summary = data.summary || {};
        $('#sumTotal').text(summary.total || 0);
        $('#sumDue').text(summary.due || 0);
        $('#sumOverdue').text(summary.overdue || 0);
        $('#sumCompleted').text(summary.completed || 0);

        records = data.items || [];
        $('#recordCount').text(records.length + ' record(s)');
        $('#btnGenerate, #generationMode').prop('disabled', false);
        $('#generationHint').text('Default creates only doses due for the patient age. Current child UIP master has no gender-specific dose rule.');

        var rows = [];
        records.forEach(function (record) {
            var canComplete = (record.status || '').toLowerCase() !== 'completed';
            rows.push('<tr>' +
                '<td>' + esc(record.age_label || '-') + '</td>' +
                '<td><div class="fw-semibold">' + esc(record.vaccine_name || '-') + '</div><div class="small text-muted">' + esc(record.target_disease_name || '') + '</div></td>' +
                '<td>' + esc(record.dose_number || '-') + '</td>' +
                '<td>' + esc(record.due_date || '-') + '</td>' +
                '<td>' + esc(record.given_date || '-') + '</td>' +
                '<td>' + badge(record.status, record.due_date) + '</td>' +
                '<td>' + esc(record.lot_number || '-') + '</td>' +
                '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary btnComplete" data-id="' + esc(record.id) + '" ' + (canComplete ? '' : 'disabled') + '>Complete</button></td>' +
                '</tr>');
        });
        $('#recordTable tbody').html(rows.length ? rows.join('') : '<tr><td colspan="8" class="text-center text-muted py-4">No immunization records yet. Click Generate UIP.</td></tr>');
    }

    function loadPatient() {
        resolvePatientKey(function (patientId) {
            currentPatientId = patientId;
        $.ajax({
            url: '<?= site_url('Immunization/patient') ?>/' + patientId,
            method: 'GET',
            dataType: 'json',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (data) {
            if (parseInt(data.ok || 0, 10) !== 1) {
                showStatus('warning', data.error_text || 'Patient load failed');
                return;
            }
            renderPatient(data);
            showStatus('success', 'Patient immunization timeline loaded.');
        }).fail(function (xhr) {
            showStatus('warning', ajaxError(xhr));
        });
        });
    }

    function generateSchedule() {
        resolvePatientKey(function (patientId) {
            currentPatientId = patientId;
        var payload = csrfPayload();
        payload.generation_mode = $('#generationMode').val() || 'eligible';
        $.ajax({
            url: '<?= site_url('Immunization/generate_patient_schedule') ?>/' + patientId,
            method: 'POST',
            dataType: 'json',
            data: payload,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (data) {
            updateCsrf(data);
            if (parseInt(data.ok || 0, 10) !== 1) {
                showStatus('warning', data.error || data.error_text || 'Schedule generation failed');
                return;
            }
            var futureSkipped = parseInt(data.future_skipped || 0, 10);
            var detail = 'UIP generated: ' + (data.created || 0) + ' created, ' + (data.skipped || 0) + ' already existed';
            if (futureSkipped > 0) {
                detail += ', ' + futureSkipped + ' future dose(s) skipped by age';
            }
            showStatus('success', detail + '.');
            loadPatient();
        }).fail(function (xhr) {
            updateCsrf(xhr.responseJSON || {});
            showStatus('warning', ajaxError(xhr));
        });
        });
    }

    function loadSchedule() {
        load_form('<?= site_url('Immunization/schedule_master') ?>', 'UIP Schedule Master');
    }

    function openComplete(recordId) {
        var record = records.find(function (item) { return parseInt(item.id, 10) === parseInt(recordId, 10); });
        if (!record) {
            return;
        }
        $('#completeRecordId').val(record.id);
        $('#completeTitle').text((record.vaccine_name || 'Vaccine') + ' | Due: ' + (record.due_date || '-') + ' | Dose: ' + (record.dose_number || '-'));
        $('#givenDate').val(new window.Date().toISOString().slice(0, 16));
        $('#lotNumber').val(record.lot_number || '');
        $('#expiryDate').val(record.expiry_date || '');
        $('#manufacturer').val(record.manufacturer || '');
        $('#locationName').val(record.location_name || '');
        $('#performerId').val(record.performer_id || '');
        $('#reactionNotes').val(record.reaction_notes || '');
        $('#notes').val(record.notes || '');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('completeModal')).show();
    }

    function saveComplete() {
        var recordId = parseInt($('#completeRecordId').val(), 10) || 0;
        if (recordId <= 0) {
            return;
        }
        var payload = csrfPayload();
        payload.given_date = $('#givenDate').val();
        payload.lot_number = $('#lotNumber').val();
        payload.expiry_date = $('#expiryDate').val();
        payload.manufacturer = $('#manufacturer').val();
        payload.location_name = $('#locationName').val();
        payload.performer_id = $('#performerId').val();
        payload.reaction_notes = $('#reactionNotes').val();
        payload.notes = $('#notes').val();

        $.ajax({
            url: '<?= site_url('Immunization/complete') ?>/' + recordId,
            method: 'POST',
            dataType: 'json',
            data: payload,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (data) {
            updateCsrf(data);
            if (parseInt(data.ok || 0, 10) !== 1) {
                showStatus('warning', data.error_text || 'Save failed');
                return;
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('completeModal')).hide();
            showStatus('success', 'Vaccination marked completed.');
            loadPatient();
        }).fail(function (xhr) {
            updateCsrf(xhr.responseJSON || {});
            showStatus('warning', ajaxError(xhr));
        });
    }

    $('#btnLoadPatient').on('click', loadPatient);
    $('#btnGenerate').on('click', generateSchedule);
    $('#btnLoadSchedule').on('click', loadSchedule);
    $('#btnSaveComplete').on('click', saveComplete);
    $('#recordTable').on('click', '.btnComplete', function () {
        openComplete($(this).data('id'));
    });
    $('#patientId').on('keydown', function (event) {
        if (event.key === 'Enter') {
            loadPatient();
        }
    });

    if (selectedPatientKey() !== '') {
        loadPatient();
    }
})();
</script>
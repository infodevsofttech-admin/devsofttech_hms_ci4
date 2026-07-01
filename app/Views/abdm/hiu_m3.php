<div class="container-fluid py-3">
    <div class="row g-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">ABDM M3 HIU Console</h5>
                    <button class="btn btn-sm btn-outline-primary" id="btnRefreshTimeline">Refresh Timeline</button>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        Search patient first. Consent Request is enabled only when ABHA Number and ABHA Address are both present.
                    </div>

                    <div class="card border-0 bg-light mb-3">
                        <div class="card-body py-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label mb-1">Search Patient</label>
                                    <input id="patient_search" class="form-control" placeholder="Name / UHID / Mobile / ABHA">
                                </div>
                                <div class="col-md-2">
                                    <button id="btnPatientSearch" class="btn btn-outline-secondary w-100">Search</button>
                                </div>
                                <div class="col-md-4">
                                    <div id="patient_status" class="small text-muted">No patient selected.</div>
                                </div>
                            </div>
                            <div class="table-responsive mt-2">
                                <table class="table table-sm table-hover mb-0" id="patient_table">
                                    <thead>
                                        <tr>
                                            <th>Action</th>
                                            <th>Patient</th>
                                            <th>UHID</th>
                                            <th>ABHA Number</th>
                                            <th>ABHA Address</th>
                                            <th>Last Consent State</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-2"><input id="selected_patient" class="form-control" placeholder="Selected Patient" readonly></div>
                        <div class="col-md-2"><input id="request_id" class="form-control" placeholder="requestId"></div>
                        <div class="col-md-2"><input id="transaction_id" class="form-control" placeholder="transactionId"></div>
                        <div class="col-md-2"><input id="consent_request_id" class="form-control" placeholder="ABDM consentRequestId"></div>
                        <div class="col-md-2"><input id="consent_artifact_id" class="form-control" placeholder="ABDM consentId (artifact)"></div>
                        <div class="col-md-2"><input id="abha_address" class="form-control" placeholder="abha_address" readonly></div>
                    </div>

                    <div class="small mb-3" id="readiness_box"></div>

                    <div class="mb-3">
                        <textarea id="payload_json" class="form-control" rows="8" placeholder='{"consent":{"patient":{"id":"someone@abdm"},"hiu":{"id":"YOUR_HIU_ID"},"hiTypes":["OPConsultation"]}}'></textarea>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-primary" id="btnConsentRequest" data-op="consent_request" disabled>Create Consent Request</button>
                        <button class="btn btn-outline-primary" id="btnConsentStatus" data-op="consent_status" disabled>Check Consent Status</button>
                        <button class="btn btn-outline-primary" id="btnConsentFetch" data-op="consent_fetch" disabled>Fetch Consent Artifact</button>
                        <button class="btn btn-success" id="btnHiRequest" data-op="hi_request" disabled>Request Health Information</button>
                    </div>

                    <hr>
                    <pre id="result_box" class="bg-dark text-light p-3 rounded" style="min-height:120px">{}</pre>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">Timeline / Logs</div>
                <div class="card-body">
                    <div class="row g-2 mb-2">
                        <div class="col-md-2"><input id="f_hfr_id" class="form-control" placeholder="Filter hfr_id"></div>
                        <div class="col-md-2"><input id="f_consent_id" class="form-control" placeholder="Filter consent_id"></div>
                        <div class="col-md-2"><input id="f_transaction_id" class="form-control" placeholder="Filter transaction_id"></div>
                        <div class="col-md-2"><input id="f_abha_address" class="form-control" placeholder="Filter abha_address"></div>
                        <div class="col-md-2"><input id="f_date_from" type="date" class="form-control"></div>
                        <div class="col-md-2"><input id="f_date_to" type="date" class="form-control"></div>
                        <div class="col-md-12"><button id="btnApplyFilter" class="btn btn-outline-secondary w-100">Apply Filters</button></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle" id="timeline_table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Time</th>
                                    <th>Operation</th>
                                    <th>State</th>
                                    <th>Status</th>
                                    <th>HFR</th>
                                    <th>ABHA Address</th>
                                    <th>Consent</th>
                                    <th>Txn</th>
                                    <th>Request</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var csrfName = '<?= csrf_token() ?>';
    var csrfHash = '<?= csrf_hash() ?>';
    var selectedPatient = null;
    var consentPollTimer = null;
    var consentPollAttempts = 0;
    var consentPollMaxAttempts = 12;

    var opRoute = {
        consent_request: '<?= base_url('AbdmHiu/consent_request') ?>',
        consent_status: '<?= base_url('AbdmHiu/consent_request_status') ?>',
        consent_fetch: '<?= base_url('AbdmHiu/consent_request_fetch') ?>',
        hi_request: '<?= base_url('AbdmHiu/health_information_request') ?>'
    };

    var patientLookupUrl = '<?= base_url('AbdmHiu/patient_lookup') ?>';

    function escHtml(v) {
        return (v || '').toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function fieldEl(ids) {
        for (var i = 0; i < ids.length; i++) {
            var el = document.getElementById(ids[i]);
            if (el) {
                return el;
            }
        }
        return null;
    }

    function getFieldValue(ids) {
        var el = fieldEl(ids);
        return el ? (el.value || '').toString().trim() : '';
    }

    function setFieldValue(ids, value) {
        var el = fieldEl(ids);
        if (el) {
            el.value = value;
        }
    }

    function genReq(prefix) {
        var p = prefix || 'REQ-HIU';
        return p + '-' + Date.now();
    }

    function updateReadinessBox(consentStateText) {
        var box = document.getElementById('readiness_box');
        if (!selectedPatient) {
            box.innerHTML = '<span class="text-muted">Select a patient to continue.</span>';
            return;
        }

        var hasNum = selectedPatient.has_abha_number === 1;
        var hasAddr = selectedPatient.has_abha_address === 1;
        var state = (consentStateText || '').trim();

        box.innerHTML = '' +
            '<span class="badge ' + (hasNum ? 'bg-success' : 'bg-secondary') + ' me-1">ABHA Number: ' + (hasNum ? 'Ready' : 'Missing') + '</span>' +
            '<span class="badge ' + (hasAddr ? 'bg-success' : 'bg-secondary') + ' me-1">ABHA Address: ' + (hasAddr ? 'Ready' : 'Missing') + '</span>' +
            '<span class="badge bg-info text-dark">Consent: ' + escHtml(state || 'Unknown') + '</span>';
    }

    function isConsentApproved(statusText) {
        var s = (statusText || '').toString().trim().toLowerCase();
        return ['approved', 'granted', 'active', 'linked', 'completed'].indexOf(s) !== -1;
    }

    function isTerminalConsentState(statusText) {
        var s = (statusText || '').toString().trim().toLowerCase();
        return ['approved', 'granted', 'active', 'completed', 'revoked', 'denied', 'expired', 'rejected', 'failed', 'error'].indexOf(s) !== -1;
    }

    function isGatewayRequestId(v) {
        return /^REQ-\d{14}-[A-Za-z0-9]{6,}$/.test((v || '').toString().trim());
    }

    function isValidConsentArtifactId(v) {
        var val = (v || '').toString().trim();
        return val !== '' && !isGatewayRequestId(val);
    }

    function syncButtons(consentStateText) {
        var hasPatient = !!selectedPatient;
        var hasNum = hasPatient && selectedPatient.has_abha_number === 1;
        var hasAddr = hasPatient && selectedPatient.has_abha_address === 1;
        var hasConsentRequestRef = (getFieldValue(['consent_request_id', 'consent_id']) !== '');
        var hasConsentArtifactRef = (getFieldValue(['consent_artifact_id', 'consent_id']) !== '');

        document.getElementById('btnConsentRequest').disabled = !(hasNum && hasAddr);
        document.getElementById('btnConsentStatus').disabled = !hasConsentRequestRef;
        document.getElementById('btnConsentFetch').disabled = !hasConsentArtifactRef;
        document.getElementById('btnHiRequest').disabled = !(hasConsentArtifactRef && isConsentApproved(consentStateText));
        updateReadinessBox(consentStateText);
    }

    function setSelectedPatient(p) {
        selectedPatient = p;
        setFieldValue(['selected_patient'], (p.patient_name || '') + (p.patient_code ? ' (' + p.patient_code + ')' : ''));
        setFieldValue(['abha_address'], p.abha_address || '');
        setFieldValue(['f_abha_address'], p.abha_address || '');
        setFieldValue(['request_id'], genReq('REQ-CONSENT'));
        setFieldValue(['transaction_id'], genReq('TXN-HIU'));

        var latest = p.latest_consent || {};
        var consentRequestRef = (latest.abdm_consent_request_id || '').toString();
        var consentArtifactRef = (latest.abdm_consent_artifact_id || latest.consent_id || '').toString();
        if (consentRequestRef && !isGatewayRequestId(consentRequestRef)) {
            setFieldValue(['consent_request_id', 'consent_id'], consentRequestRef);
            setFieldValue(['f_consent_id'], consentRequestRef);
        }
        if (isValidConsentArtifactId(consentArtifactRef)) {
            setFieldValue(['consent_artifact_id', 'consent_id'], consentArtifactRef);
        } else {
            setFieldValue(['consent_artifact_id', 'consent_id'], '');
        }

        var consentState = (latest.workflow_state || latest.status || 'Unknown').toString();
        document.getElementById('patient_status').textContent = 'Selected: ' + (p.patient_name || '-') + ' | Consent: ' + consentState;
        syncButtons(consentState);
        fetchTimeline();
    }

    function renderPatientRows(items) {
        var tbody = document.querySelector('#patient_table tbody');
        tbody.innerHTML = '';
        items.forEach(function (p, idx) {
            var tr = document.createElement('tr');
            tr.innerHTML = '' +
                '<td><button class="btn btn-sm btn-outline-primary" data-select-idx="' + idx + '">Select</button></td>' +
                '<td>' + escHtml(p.patient_name || '') + '</td>' +
                '<td>' + escHtml(p.patient_code || '') + '</td>' +
                '<td>' + escHtml(p.abha_number || '') + '</td>' +
                '<td>' + escHtml(p.abha_address || '') + '</td>' +
                '<td>' + escHtml(((p.latest_consent || {}).workflow_state || (p.latest_consent || {}).status || '-')) + '</td>';
            tbody.appendChild(tr);
        });

        tbody.querySelectorAll('button[data-select-idx]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx = parseInt(btn.getAttribute('data-select-idx'), 10);
                if (!Number.isNaN(idx) && items[idx]) {
                    setSelectedPatient(items[idx]);
                }
            });
        });
    }

    function searchPatients() {
        var q = document.getElementById('patient_search').value.trim();
        var url = patientLookupUrl + '?q=' + encodeURIComponent(q);
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if ((res.ok || 0) !== 1) {
                    document.getElementById('patient_status').textContent = res.error || 'Patient search failed';
                    return;
                }
                renderPatientRows(res.items || []);
                document.getElementById('patient_status').textContent = (res.count || 0) + ' patient(s) found';
            })
            .catch(function (e) {
                document.getElementById('patient_status').textContent = e.message || 'Patient search failed';
            });
    }

    function readPayload(op) {
        var custom = {};
        try {
            var raw = document.getElementById('payload_json').value.trim();
            custom = raw ? JSON.parse(raw) : {};
        } catch (e) {
            throw new Error('Invalid payload JSON');
        }

        var payload = Object.assign({}, custom, {
            requestId: getFieldValue(['request_id']),
            transactionId: getFieldValue(['transaction_id']),
            abha_address: getFieldValue(['abha_address']),
            patient_id: selectedPatient ? selectedPatient.patient_id : null
        });

        var consentRequestVal = getFieldValue(['consent_request_id', 'consent_id']);
        if (consentRequestVal !== '') {
            payload.consentRequestId = consentRequestVal;
            payload.abdm_consent_request_id = consentRequestVal;
        }

        var consentArtifactVal = getFieldValue(['consent_artifact_id', 'consent_id']);
        if (consentArtifactVal !== '') {
            payload.consentId = consentArtifactVal;
            payload.abdm_consent_artifact_id = consentArtifactVal;
        }

        if (!payload.timestamp) {
            payload.timestamp = new Date().toISOString();
        }

        delete payload.abha_id;
        delete payload.abhaId;
        delete payload.consent_id;

        payload[csrfName] = csrfHash;
        return payload;
    }

    function isValidAbhaAddress(v) {
        return /^[A-Za-z0-9._-]+@[A-Za-z0-9.-]+$/.test((v || '').toString().trim());
    }

    function buildGatewayPayload(op, raw) {
        var requestId = (raw.requestId || raw.request_id || '').toString().trim();
        var timestamp = (raw.timestamp || '').toString().trim();
        var abhaAddress = (raw.abha_address || '').toString().trim();
        var base = {
            requestId: requestId,
            timestamp: timestamp || new Date().toISOString()
        };

        if (op === 'consent_request') {
            var consent = raw.consent;
            if (!consent || typeof consent !== 'object' || Array.isArray(consent) || Object.keys(consent).length === 0) {
                if (!isValidAbhaAddress(abhaAddress)) {
                    throw new Error('Patient ABHA address must be in format like 91510165305101@sbx.');
                }
                consent = {
                    purpose: {
                        code: 'CAREMGT',
                        text: 'Care Management',
                        refUri: 'https://abdm.gov.in'
                    },
                    patient: { id: abhaAddress },
                    requester: { name: 'Hospital HMS' },
                    hiTypes: ['OPConsultation', 'DiagnosticReport'],
                    permission: {
                        accessMode: 'VIEW',
                        dateRange: {
                            from: new Date(Date.now() - (30 * 24 * 60 * 60 * 1000)).toISOString(),
                            to: new Date().toISOString()
                        },
                        dataEraseAt: new Date(Date.now() + (365 * 24 * 60 * 60 * 1000)).toISOString(),
                        frequency: {
                            unit: 'HOUR',
                            value: 1,
                            repeats: 0
                        }
                    }
                };
            }

            if (!consent.patient || typeof consent.patient !== 'object') {
                consent.patient = {};
            }
            if (!consent.patient.id && isValidAbhaAddress(abhaAddress)) {
                consent.patient.id = abhaAddress;
            }
            if (!isValidAbhaAddress(consent.patient.id || '')) {
                throw new Error('Patient ABHA address must be in format like 91510165305101@sbx.');
            }

            base.consent = consent;
            return base;
        }

        if (op === 'consent_status') {
            var consentRequestId = (raw.abdm_consent_request_id || raw.consentRequestId || '').toString().trim();
            if (!consentRequestId) {
                throw new Error('consentRequestId is required for consent status.');
            }
            if (isGatewayRequestId(consentRequestId)) {
                throw new Error('mapping_error: consentRequestId looks like gateway request_id (REQ-...). Use actual ABDM consentRequestId from init response.');
            }
            base.consentRequestId = consentRequestId;
            return base;
        }

        if (op === 'consent_fetch') {
            var consentId = (raw.abdm_consent_artifact_id || raw.consentId || '').toString().trim();
            if (!consentId) {
                throw new Error('consentId is required for consent fetch.');
            }
            if (isGatewayRequestId(consentId)) {
                throw new Error('mapping_error: consentId looks like gateway request_id (REQ-...). Use ABDM consent artifact id only.');
            }
            base.consentId = consentId;
            return base;
        }

        if (op === 'hi_request') {
            var hiRequest = raw.hiRequest;
            if (!hiRequest || typeof hiRequest !== 'object' || Array.isArray(hiRequest)) {
                throw new Error('hiRequest object is required for health information request.');
            }

            if (!hiRequest.consent || typeof hiRequest.consent !== 'object') {
                hiRequest.consent = {};
            }
            if (!hiRequest.consent.id) {
                hiRequest.consent.id = (raw.consentId || raw.consent_id || '').toString().trim();
            }
            if (!(hiRequest.consent.id || '').toString().trim()) {
                throw new Error('hiRequest.consent.id is required for health information request.');
            }

            base.hiRequest = hiRequest;
            return base;
        }

        throw new Error('Unsupported operation');
    }

    function setResult(obj) {
        document.getElementById('result_box').textContent = JSON.stringify(obj, null, 2);
    }

    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(body)
        }).then(function (r) {
            return r.json().catch(function () { return { ok: 0, message: 'Non-JSON response', http_code: r.status }; });
        });
    }

    function run(op, options) {
        options = options || {};
        var url = opRoute[op];
        if (!url) {
            setResult({ ok: 0, message: 'Unsupported operation' });
            return;
        }
        var payload;
        try {
            payload = buildGatewayPayload(op, readPayload(op));
        } catch (e) {
            setResult({ ok: 0, message: e.message || 'Validation failed' });
            return;
        }
        postJson(url, payload).then(function (res) {
            if (!options.silent) {
                setResult(res);
            }

            if ((res.ok || 0) === 1) {
                var data = res.data || {};
                var consentReqRef = (data.abdm_consent_request_id || data.consentRequestId || data.consent_request_id || '').toString();
                if (consentReqRef && !isGatewayRequestId(consentReqRef)) {
                    setFieldValue(['consent_request_id', 'consent_id'], consentReqRef);
                    setFieldValue(['f_consent_id'], consentReqRef);
                }

                var consentArtifactRef = (data.abdm_consent_artifact_id || data.consentId || data.consent_id || '').toString();
                if (isValidConsentArtifactId(consentArtifactRef)) {
                    setFieldValue(['consent_artifact_id', 'consent_id'], consentArtifactRef);
                }

                var state = (data.workflow_state || data.consent_status || data.status || res.workflow_state || '').toString();
                syncButtons(state);

                if (op === 'consent_request') {
                    startConsentPolling();
                }
                if (op === 'consent_status' && isTerminalConsentState(state)) {
                    stopConsentPolling();
                }
            } else if (op === 'consent_status') {
                var errState = ((res.data || {}).workflow_state || res.workflow_state || '').toString();
                var errText = (res.error || ((res.data || {}).error_text) || '').toString().toLowerCase();
                if (isTerminalConsentState(errState) || errText.indexOf('mapping_error') !== -1) {
                    stopConsentPolling();
                }
            } else if (op === 'consent_fetch') {
                var fetchErr = (res.error || ((res.data || {}).error_text) || '').toString().toLowerCase();
                if (fetchErr.indexOf('mapping_error') !== -1) {
                    setFieldValue(['consent_artifact_id', 'consent_id'], '');
                }
            }

            fetchTimeline();
        }).catch(function (e) {
            setResult({ ok: 0, message: e.message });
        });
    }

    function startConsentPolling() {
        stopConsentPolling();
        consentPollAttempts = 0;
        consentPollTimer = setInterval(function () {
            consentPollAttempts++;
            if (consentPollAttempts > consentPollMaxAttempts) {
                stopConsentPolling();
                setResult({ ok: 0, message: 'Consent status polling stopped after max retries. Use Refresh/Status manually or wait for callback.' });
                return;
            }
            run('consent_status', { silent: true });
        }, 15000);
    }

    function stopConsentPolling() {
        if (consentPollTimer) {
            clearInterval(consentPollTimer);
            consentPollTimer = null;
        }
        consentPollAttempts = 0;
    }

    function fetchTimeline() {
        var qs = new URLSearchParams({
            hfr_id: getFieldValue(['f_hfr_id']),
            consent_id: getFieldValue(['f_consent_id']),
            transaction_id: getFieldValue(['f_transaction_id']),
            abha_address: getFieldValue(['f_abha_address']),
            date_from: getFieldValue(['f_date_from']),
            date_to: getFieldValue(['f_date_to'])
        });

        fetch('<?= base_url('AbdmHiu/timeline') ?>?' + qs.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var tbody = document.querySelector('#timeline_table tbody');
            tbody.innerHTML = '';
            (res.items || []).forEach(function (row) {
                var tr = document.createElement('tr');
                tr.innerHTML = '' +
                    '<td>' + (row.id || '') + '</td>' +
                    '<td>' + (row.created_at || '') + '</td>' +
                    '<td>' + (row.operation || '') + '</td>' +
                    '<td>' + (row.workflow_state || '') + '</td>' +
                    '<td>' + (row.status || '') + '</td>' +
                    '<td>' + (row.hfr_id || '') + '</td>' +
                    '<td>' + (row.abha_address || '') + '</td>' +
                    '<td>' + (row.consent_id || '') + '</td>' +
                    '<td>' + (row.transaction_id || '') + '</td>' +
                    '<td>' + (row.request_id || '') + '</td>' +
                    '<td>' + (row.last_error || '') + '</td>';
                tbody.appendChild(tr);
            });
        });
    }

    var btnPatientSearch = document.getElementById('btnPatientSearch');
    if (btnPatientSearch) {
        btnPatientSearch.addEventListener('click', searchPatients);
    }
    var patientSearch = document.getElementById('patient_search');
    if (patientSearch) {
        patientSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchPatients();
            }
        });
    }

    document.querySelectorAll('button[data-op]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            run(btn.getAttribute('data-op'));
        });
    });

    var consentReqInput = fieldEl(['consent_request_id', 'consent_id']);
    if (consentReqInput) {
        consentReqInput.addEventListener('input', function () {
            syncButtons();
        });
    }

    var consentArtifactInput = fieldEl(['consent_artifact_id']);
    if (consentArtifactInput) {
        consentArtifactInput.addEventListener('input', function () {
            syncButtons();
        });
    }

    var btnApplyFilter = document.getElementById('btnApplyFilter');
    if (btnApplyFilter) {
        btnApplyFilter.addEventListener('click', fetchTimeline);
    }
    var btnRefreshTimeline = document.getElementById('btnRefreshTimeline');
    if (btnRefreshTimeline) {
        btnRefreshTimeline.addEventListener('click', fetchTimeline);
    }

    syncButtons();
    fetchTimeline();
})();
</script>

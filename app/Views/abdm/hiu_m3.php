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
                        <div class="col-md-3"><input id="selected_patient" class="form-control" placeholder="Selected Patient" readonly></div>
                        <div class="col-md-2"><input id="request_id" class="form-control" placeholder="requestId"></div>
                        <div class="col-md-2"><input id="transaction_id" class="form-control" placeholder="transactionId"></div>
                        <div class="col-md-2"><input id="consent_id" class="form-control" placeholder="consentId / consentRequestId"></div>
                        <div class="col-md-3"><input id="abha_address" class="form-control" placeholder="abha_address" readonly></div>
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

    function syncButtons(consentStateText) {
        var hasPatient = !!selectedPatient;
        var hasNum = hasPatient && selectedPatient.has_abha_number === 1;
        var hasAddr = hasPatient && selectedPatient.has_abha_address === 1;
        var hasConsentRef = (document.getElementById('consent_id').value.trim() !== '' || document.getElementById('request_id').value.trim() !== '');

        document.getElementById('btnConsentRequest').disabled = !(hasNum && hasAddr);
        document.getElementById('btnConsentStatus').disabled = !hasConsentRef;
        document.getElementById('btnConsentFetch').disabled = !hasConsentRef;
        document.getElementById('btnHiRequest').disabled = !(hasConsentRef && isConsentApproved(consentStateText));
        updateReadinessBox(consentStateText);
    }

    function setSelectedPatient(p) {
        selectedPatient = p;
        document.getElementById('selected_patient').value = (p.patient_name || '') + (p.patient_code ? ' (' + p.patient_code + ')' : '');
        document.getElementById('abha_address').value = p.abha_address || '';
        document.getElementById('f_abha_address').value = p.abha_address || '';
        document.getElementById('request_id').value = genReq('REQ-CONSENT');
        document.getElementById('transaction_id').value = genReq('TXN-HIU');

        var latest = p.latest_consent || {};
        var consentRef = (latest.consent_id || latest.request_id || '').toString();
        if (consentRef) {
            document.getElementById('consent_id').value = consentRef;
            document.getElementById('f_consent_id').value = consentRef;
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
            requestId: document.getElementById('request_id').value.trim(),
            transactionId: document.getElementById('transaction_id').value.trim(),
            abha_address: document.getElementById('abha_address').value.trim(),
            patient_id: selectedPatient ? selectedPatient.patient_id : null
        });

        var consentVal = document.getElementById('consent_id').value.trim();
        if (consentVal !== '') {
            payload.consentId = consentVal;
            payload.consentRequestId = consentVal;
        }

        if (!payload.timestamp) {
            payload.timestamp = new Date().toISOString();
        }

        payload[csrfName] = csrfHash;
        return payload;
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
        var payload = readPayload(op);
        postJson(url, payload).then(function (res) {
            if (!options.silent) {
                setResult(res);
            }

            if ((res.ok || 0) === 1) {
                var data = res.data || {};
                var consentRef = (data.consentId || data.consentRequestId || data.consent_id || data.requestId || data.request_id || '').toString();
                if (consentRef) {
                    document.getElementById('consent_id').value = consentRef;
                    document.getElementById('f_consent_id').value = consentRef;
                }

                var state = (data.workflow_state || data.consent_status || data.status || res.workflow_state || '').toString();
                syncButtons(state);

                if (op === 'consent_request') {
                    startConsentPolling();
                }
                if (op === 'consent_status' && isConsentApproved(state)) {
                    stopConsentPolling();
                }
            }

            fetchTimeline();
        }).catch(function (e) {
            setResult({ ok: 0, message: e.message });
        });
    }

    function startConsentPolling() {
        stopConsentPolling();
        consentPollTimer = setInterval(function () {
            run('consent_status', { silent: true });
        }, 15000);
    }

    function stopConsentPolling() {
        if (consentPollTimer) {
            clearInterval(consentPollTimer);
            consentPollTimer = null;
        }
    }

    function fetchTimeline() {
        var qs = new URLSearchParams({
            hfr_id: document.getElementById('f_hfr_id').value.trim(),
            consent_id: document.getElementById('f_consent_id').value.trim(),
            transaction_id: document.getElementById('f_transaction_id').value.trim(),
            abha_address: document.getElementById('f_abha_address').value.trim(),
            date_from: document.getElementById('f_date_from').value,
            date_to: document.getElementById('f_date_to').value
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

    document.getElementById('btnPatientSearch').addEventListener('click', searchPatients);
    document.getElementById('patient_search').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchPatients();
        }
    });

    document.querySelectorAll('button[data-op]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            run(btn.getAttribute('data-op'));
        });
    });

    document.getElementById('consent_id').addEventListener('input', function () {
        syncButtons();
    });

    document.getElementById('btnApplyFilter').addEventListener('click', fetchTimeline);
    document.getElementById('btnRefreshTimeline').addEventListener('click', fetchTimeline);

    syncButtons();
    fetchTimeline();
})();
</script>

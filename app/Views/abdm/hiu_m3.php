<div class="container-fluid py-3">
    <div class="row g-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="mb-0">ABDM M3 HIU Console</h5>
                        <span id="poll_summary_badge" class="badge bg-secondary">Poll: waiting</span>
                        <button class="btn btn-sm btn-outline-warning" id="btnPollErrors" type="button">Show Poll Errors</button>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-success" id="btnOpenDocumentsAjax" type="button">View Fetched Documents</button>
                        <button class="btn btn-sm btn-outline-primary" id="btnRefreshTimeline">Refresh Timeline</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="poll_error_panel" class="alert alert-warning d-none mb-3">
                        <div class="fw-bold mb-1">Recent Poll Errors</div>
                        <ul id="poll_error_list" class="mb-0 ps-3"></ul>
                    </div>

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

                    <div class="card border-info mb-3 d-none" id="patient_consent_window">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">Consent Request Window</div>
                                <div class="small text-muted" id="patient_consent_meta">Select a patient to view consent requests.</div>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary" id="btnStartFreshFlow" type="button">Start Fresh Flow</button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0" id="patient_consent_table">
                                    <thead>
                                        <tr>
                                            <th>Action</th>
                                            <th>Consent Request ID</th>
                                            <th>Consent ID</th>
                                            <th>Status</th>
                                            <th>Created On</th>
                                            <th>Last Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="6" class="text-muted text-center">Select a patient to load consent requests.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card border-success mb-3 d-none" id="workflow_todo_card">
                        <div class="card-header py-2 fw-semibold">Workflow Todo</div>
                        <div class="card-body py-2">
                            <div class="d-flex flex-wrap gap-2" id="workflow_todo_items">
                                <span class="badge bg-secondary" data-step="1">1. Select Patient</span>
                                <span class="badge bg-secondary" data-step="2">2. Create Consent Request</span>
                                <span class="badge bg-secondary" data-step="3">3. Check Consent Status</span>
                                <span class="badge bg-secondary" data-step="4">4. Poll &amp; Fetch Decrypted Data</span>
                            </div>
                        </div>
                    </div>

                    <div class="small text-muted mb-1">Auto-filled context (read-only): request and consent IDs are managed from selected patient workflow.</div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-2"><input id="selected_patient" class="form-control" placeholder="Selected Patient" readonly></div>
                        <div class="col-md-2"><input id="request_id" class="form-control" placeholder="requestId" readonly></div>
                        <div class="col-md-2"><input id="transaction_id" class="form-control" placeholder="transactionId" readonly></div>
                        <div class="col-md-2"><input id="consent_request_id" class="form-control" placeholder="ABDM consentRequestId" readonly></div>
                        <div class="col-md-2"><input id="consent_artifact_id" class="form-control" placeholder="ABDM consentId (artifact)" readonly></div>
                        <div class="col-md-2"><input id="abha_address" class="form-control" placeholder="abha_address" readonly></div>
                    </div>

                    <div class="small mb-3" id="readiness_box"></div>

                    <div class="mb-3">
                        <textarea id="payload_json" class="form-control" rows="8" placeholder='{"consent":{"patient":{"id":"someone@abdm"},"hiu":{"id":"YOUR_HIU_ID"},"hiTypes":["OPConsultation"]}}'></textarea>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-primary" id="btnConsentRequest" data-op="consent_request" disabled>Create Consent Request</button>
                        <button class="btn btn-outline-primary" id="btnConsentStatus" data-op="consent_status" disabled>Check Consent Status</button>
                        <button class="btn btn-outline-success" id="btnConsentFetch" data-op="data_fetch" disabled>Poll &amp; Fetch Decrypted Data</button>
                    </div>

                    <div class="d-flex gap-3 flex-wrap mt-2">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="toggleAutoStatusPolling">
                            <label class="form-check-label small" for="toggleAutoStatusPolling">Auto Status Polling</label>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="toggleAutoNextStep">
                            <label class="form-check-label small" for="toggleAutoNextStep">Auto Next Step (Status -> Data Fetch)</label>
                        </div>
                    </div>

                    <hr>
                    <pre id="result_box" class="bg-dark text-light p-3 rounded" style="min-height:120px">{}</pre>

                    <div class="card border-success mt-3 d-none" id="data_preview_card">
                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                            <div class="fw-semibold">Decrypted Data Preview</div>
                            <div id="data_preview_meta" class="small text-muted">No data sessions yet.</div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped mb-0" id="data_preview_table">
                                    <thead>
                                        <tr>
                                            <th>Txn ID</th>
                                            <th>Consent Ref</th>
                                            <th>Status</th>
                                            <th>Records</th>
                                            <th>Care Contexts</th>
                                            <th>Bundle Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="6" class="text-muted text-center">Run Poll &amp; Fetch Decrypted Data to load records.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
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
                        <div class="col-md-4"><button id="btnApplyFilter" class="btn btn-outline-secondary w-100">Apply Filters</button></div>
                        <div class="col-md-4"><button id="btnUseLatestLog" class="btn btn-outline-primary w-100">Use Latest Log Context</button></div>
                        <div class="col-md-4"><button id="btnClearFilters" class="btn btn-outline-dark w-100">Clear Filters</button></div>
                        <div class="col-md-12">
                            <div id="log_next_step" class="alert alert-light py-2 px-3 mb-0 small text-muted">Recommended next step will appear after timeline loads.</div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle" id="timeline_table">
                            <thead>
                                <tr>
                                    <th>Action</th>
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

        <div class="col-12 d-none" id="ajax_docs_panel">
            <div class="card shadow-sm border-success">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Fetched ABDM Documents (AJAX View)</h6>
                    <button class="btn btn-sm btn-outline-secondary" id="btnCloseAjaxDocs" type="button">Close</button>
                </div>
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label mb-1">Search Patient</label>
                            <input id="ajax_doc_patient_search" class="form-control" placeholder="Name / UHID / ABHA / Mobile">
                        </div>
                        <div class="col-md-2">
                            <button id="btnAjaxDocPatientSearch" class="btn btn-outline-secondary w-100" type="button">Search</button>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1">Search Documents</label>
                            <input id="ajax_doc_search" class="form-control" placeholder="title / care context / doctor">
                        </div>
                        <div class="col-md-2">
                            <button id="btnAjaxDocSearch" class="btn btn-outline-primary w-100" type="button">Load Documents</button>
                        </div>
                        <div class="col-12">
                            <div class="small text-muted" id="ajax_doc_patient_status">Select a patient to view mapped ABDM documents.</div>
                        </div>
                    </div>

                    <div class="table-responsive mt-2">
                        <table class="table table-sm table-hover mb-0" id="ajax_doc_patient_table">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Patient</th>
                                    <th>UHID</th>
                                    <th>ABHA Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="4" class="text-muted text-center">Search patient to start.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-lg-5">
                            <div class="card h-100">
                                <div class="card-header py-2">Document List</div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" style="max-height: 420px;">
                                        <table class="table table-sm table-striped mb-0" id="ajax_doc_table">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Title</th>
                                                    <th>Care Context</th>
                                                    <th>Doctor</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td colspan="4" class="text-muted text-center">No documents loaded.</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="card h-100">
                                <div class="card-header py-2">Document Detail</div>
                                <div class="card-body" id="ajax_doc_detail_box">
                                    <div class="text-muted">Select a document to view human-readable summary.</div>
                                </div>
                            </div>
                        </div>
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
    var timelineItems = [];
    var consentPollTimer = null;
    var consentPollAttempts = 0;
    var consentPollMaxAttempts = 12;
    var recentPollErrors = [];
    var autoCooldownUntilMs = 0;
    var autoCooldownReason = '';
    var persistentTimelineMap = {};

    function isAutoStatusPollingEnabled() {
        var el = document.getElementById('toggleAutoStatusPolling');
        return !!(el && el.checked);
    }

    function isAutoNextStepEnabled() {
        var el = document.getElementById('toggleAutoNextStep');
        return !!(el && el.checked);
    }

    function resolveConsentStateFromResponse(res, data) {
        data = data || {};
        var consentObj = data.consent || {};
        return (
            (consentObj.status || '') ||
            (data.consent_status || '') ||
            (data.status || '') ||
            (res.workflow_state || '') ||
            (res.status || '')
        ).toString();
    }

    var opRoute = {
        consent_request: '<?= base_url('AbdmHiu/consent_request') ?>',
        consent_status: '<?= base_url('AbdmHiu/consent_reconcile') ?>',
        data_fetch: '<?= base_url('AbdmHiu/data_fetch') ?>'
    };
    var pollSummaryUrl = '<?= base_url('AbdmHiu/poll_summary') ?>';

    var patientLookupUrl = '<?= base_url('AbdmHiu/patient_lookup') ?>';
    var docListUrl = '<?= base_url('AbdmHiu/documents_list') ?>';
    var docDetailBaseUrl = '<?= base_url('AbdmHiu/document_detail') ?>';
    var ajaxDocSelectedPatient = null;
    var ajaxDocItems = [];

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
        var lockedCreate = hasExistingConsentFlowContext();
        var policyNote = lockedCreate
            ? '<span class="badge bg-warning text-dark ms-1">Policy: reuse logged consent flow (Status -> Data Fetch)</span>'
            : '<span class="badge bg-light text-dark ms-1">Policy: create consent only once per active flow</span>';
        var cooldownNote = isAutoCoolingDown()
            ? '<span class="badge bg-danger ms-1">Auto-next paused: ' + escHtml(autoCooldownReason || 'temporary cooldown') + '</span>'
            : '';

        box.innerHTML = '' +
            '<span class="badge ' + (hasNum ? 'bg-success' : 'bg-secondary') + ' me-1">ABHA Number: ' + (hasNum ? 'Ready' : 'Missing') + '</span>' +
            '<span class="badge ' + (hasAddr ? 'bg-success' : 'bg-secondary') + ' me-1">ABHA Address: ' + (hasAddr ? 'Ready' : 'Missing') + '</span>' +
            '<span class="badge bg-info text-dark">Consent: ' + escHtml(state || 'Unknown') + '</span>' +
            policyNote +
            cooldownNote;
    }

    function isConsentApproved(statusText) {
        var s = (statusText || '').toString().trim().toLowerCase();
        return ['approved', 'granted', 'active', 'linked', 'completed', 'status_checked'].indexOf(s) !== -1;
    }

    function isTerminalConsentState(statusText) {
        var s = (statusText || '').toString().trim().toLowerCase();
        return ['approved', 'granted', 'active', 'completed', 'revoked', 'denied', 'expired', 'rejected', 'failed', 'error'].indexOf(s) !== -1;
    }

    function isSuccessConsentState(statusText) {
        var s = (statusText || '').toString().trim().toLowerCase();
        return ['approved', 'granted', 'active', 'status_checked', 'completed'].indexOf(s) !== -1;
    }

    function isGatewayRequestId(v) {
        return /^REQ-/i.test((v || '').toString().trim());
    }

    function isValidConsentArtifactId(v) {
        var val = (v || '').toString().trim();
        return val !== '';
    }

    function hasExistingConsentFlowContext() {
        var consentRequestRef = getFieldValue(['consent_request_id', 'consent_id']);
        var consentArtifactRef = getFieldValue(['consent_artifact_id', 'consent_id']);
        return consentRequestRef !== '' || isValidConsentArtifactId(consentArtifactRef);
    }

    function canCreateConsentRequest(consentStateText) {
        if (!selectedPatient || selectedPatient.has_abha_number !== 1 || selectedPatient.has_abha_address !== 1) {
            return false;
        }

        // Policy: reuse existing consent flow context for patient instead of creating new consent requests.
        if (hasExistingConsentFlowContext()) {
            return false;
        }

        var s = (consentStateText || '').toString().trim().toLowerCase();
        if (s !== '' && ['requested', 'status_checked', 'granted', 'approved', 'active'].indexOf(s) !== -1) {
            return false;
        }

        return true;
    }

    function nowMs() {
        return Date.now();
    }

    function isAutoCoolingDown() {
        return nowMs() < autoCooldownUntilMs;
    }

    function setAutoCooldown(ms, reason) {
        autoCooldownUntilMs = nowMs() + Math.max(0, ms);
        autoCooldownReason = (reason || '').toString();
    }

    function clearAutoCooldown() {
        autoCooldownUntilMs = 0;
        autoCooldownReason = '';
    }

    function parseJsonObject(raw) {
        if (!raw) {
            return {};
        }
        if (typeof raw === 'object') {
            return raw;
        }
        var txt = raw.toString().trim();
        if (!txt) {
            return {};
        }
        try {
            var parsed = JSON.parse(txt);
            return (parsed && typeof parsed === 'object') ? parsed : {};
        } catch (e) {
            return {};
        }
    }

    function pickFirstNonEmpty(values) {
        for (var i = 0; i < values.length; i++) {
            var v = (values[i] || '').toString().trim();
            if (v !== '') {
                return v;
            }
        }
        return '';
    }

    function extractWorkflowContext(row) {
        var req = parseJsonObject(row && row.request_json ? row.request_json : '{}');
        var res = parseJsonObject(row && row.response_json ? row.response_json : '{}');
        var consent = parseJsonObject(res.consent || {});

        var requestId = pickFirstNonEmpty([
            row && row.request_id,
            req.request_id,
            req.requestId
        ]);

        var transactionId = pickFirstNonEmpty([
            row && row.transaction_id,
            req.transaction_id,
            req.transactionId,
            res.transaction_id,
            res.transactionId
        ]);

        var abhaAddress = pickFirstNonEmpty([
            row && row.abha_address,
            req.abha_address,
            req.abhaAddress,
            ((req.consent || {}).patient || {}).id
        ]);

        var consentRequestId = pickFirstNonEmpty([
            row && row.abdm_consent_request_id,
            req.abdm_consent_request_id,
            req.consent_request_id,
            req.consentRequestId,
            res.abdm_consent_request_id,
            res.consent_request_id,
            res.consentRequestId,
            consent.consent_request_id
        ]);

        var consentArtifactIdRaw = pickFirstNonEmpty([
            row && row.abdm_consent_artifact_id,
            row && row.consent_id,
            req.abdm_consent_artifact_id,
            req.consent_id,
            req.consentId,
            res.abdm_consent_artifact_id,
            res.consent_id,
            res.consentId
        ]);

        return {
            requestId: requestId,
            transactionId: transactionId,
            abhaAddress: abhaAddress,
            hfrId: pickFirstNonEmpty([row && row.hfr_id, req.hfr_id]),
            consentRequestId: consentRequestId,
            consentArtifactId: isValidConsentArtifactId(consentArtifactIdRaw) ? consentArtifactIdRaw : '',
            operation: (row && row.operation ? row.operation : '').toString().toLowerCase(),
            workflowState: (row && row.workflow_state ? row.workflow_state : '').toString().toLowerCase(),
            status: (row && row.status ? row.status : '').toString().toLowerCase(),
            lastError: (row && row.last_error ? row.last_error : '').toString().toLowerCase()
        };
    }

    function deriveConsentStatusText(row, ctx) {
        var res = parseJsonObject(row && row.response_json ? row.response_json : '{}');
        var consent = parseJsonObject(res.consent || {});
        var rawStatus = pickFirstNonEmpty([
            consent.status,
            res.consent_status,
            ''
        ]).toUpperCase();

        if (rawStatus !== '') {
            return rawStatus;
        }

        var wf = (ctx.workflowState || '').toUpperCase();
        if (wf === 'REQUESTED') {
            return 'REQUESTED';
        }
        if (wf === 'CONSENT_FETCHED' || wf === 'DATA_RECEIVED') {
            return 'DATA_RECEIVED';
        }
        if (wf === 'STATUS_CHECKED') {
            return 'STATUS_CHECKED';
        }
        if (wf === 'DATA_REQUESTED' || wf === 'DATA_RECEIVED') {
            return 'DATA_RECEIVED';
        }
        if ((ctx.status || '').toLowerCase() === 'failed') {
            return 'FAILED';
        }

        return 'UNKNOWN';
    }

    function renderStatusBadge(statusText) {
        var s = (statusText || '').toString().trim().toUpperCase();
        var cls = 'bg-secondary';
        if (['GRANTED', 'APPROVED', 'ACTIVE'].indexOf(s) !== -1) {
            cls = 'bg-success';
        } else if (['REQUESTED', 'PENDING', 'STATUS_CHECKED'].indexOf(s) !== -1) {
            cls = 'bg-warning text-dark';
        } else if (['FETCHED', 'CONSENT_FETCHED', 'DATA_RECEIVED'].indexOf(s) !== -1) {
            cls = 'bg-info text-dark';
        } else if (['FAILED', 'DENIED', 'REVOKED', 'EXPIRED'].indexOf(s) !== -1) {
            cls = 'bg-danger';
        }

        return '<span class="badge ' + cls + '">' + escHtml(s || 'UNKNOWN') + '</span>';
    }

    function computeConsentRegistry(items) {
        var byKey = {};

        items.forEach(function (row, idx) {
            var ctx = extractWorkflowContext(row);
            var key = pickFirstNonEmpty([
                ctx.requestId,
                ctx.consentRequestId,
                ctx.consentArtifactId,
                'row-' + (row.id || idx)
            ]);

            var record = {
                key: key,
                row_idx: idx,
                row_id: parseInt(row.id || 0, 10) || 0,
                request_id: ctx.requestId,
                consent_request_id: ctx.consentRequestId,
                consent_id: ctx.consentArtifactId,
                consent_status: deriveConsentStatusText(row, ctx),
                workflow_state: (row.workflow_state || '').toString(),
                operation: (row.operation || '').toString(),
                status: (row.status || '').toString(),
                created_at: (row.created_at || '').toString(),
                updated_at: (row.updated_at || row.created_at || '').toString(),
                last_error: (row.last_error || '').toString()
            };

            var existing = byKey[key];
            if (!existing) {
                byKey[key] = record;
            } else if (record.row_id > existing.row_id) {
                // Keep latest row for timestamps/state while preserving previously known consent identifiers/status.
                if (!record.consent_id && existing.consent_id) {
                    record.consent_id = existing.consent_id;
                }
                if (!record.consent_request_id && existing.consent_request_id) {
                    record.consent_request_id = existing.consent_request_id;
                }
                if ((record.consent_status === '' || record.consent_status === 'UNKNOWN') && existing.consent_status) {
                    record.consent_status = existing.consent_status;
                }
                byKey[key] = record;
            } else {
                // Older row may still contain richer consent mapping fields.
                if (!existing.consent_id && record.consent_id) {
                    existing.consent_id = record.consent_id;
                }
                if (!existing.consent_request_id && record.consent_request_id) {
                    existing.consent_request_id = record.consent_request_id;
                }
                if ((existing.consent_status === '' || existing.consent_status === 'UNKNOWN') && record.consent_status) {
                    existing.consent_status = record.consent_status;
                }
                byKey[key] = existing;
            }
        });

        var list = Object.keys(byKey).map(function (k) { return byKey[k]; });
        list.sort(function (a, b) { return (b.row_id || 0) - (a.row_id || 0); });
        return list;
    }

    function consentRowAction(rec) {
        var status = (rec.consent_status || '').toUpperCase();
        var state = (rec.workflow_state || '').toUpperCase();
        var hasArtifact = (rec.consent_id || '').toString().trim() !== '';

        if (status === 'REQUESTED' || state === 'REQUESTED') {
            return { op: 'consent_status', label: 'Check Consent Status', cls: 'btn-outline-primary' };
        }
        if (status === 'GRANTED' || status === 'STATUS_CHECKED' || state === 'GRANTED' || state === 'STATUS_CHECKED') {
            return { op: 'data_fetch', label: 'Poll & Fetch Decrypted Data', cls: 'btn-outline-success' };
        }
        if (hasArtifact && (state === 'CONSENT_FETCHED' || state === 'DATA_RECEIVED' || status === 'FETCHED' || status === 'DATA_RECEIVED')) {
            return { op: 'data_fetch', label: 'Poll & Fetch Decrypted Data', cls: 'btn-outline-success' };
        }

        if (hasArtifact) {
            return { op: 'use', label: 'Use Context', cls: 'btn-outline-secondary' };
        }

        return { op: 'consent_status', label: 'Check Consent Status', cls: 'btn-outline-primary' };
    }

    function renderConsentLists(items) {
        var panel = document.getElementById('patient_consent_window');
        var meta = document.getElementById('patient_consent_meta');
        var tbody = document.querySelector('#patient_consent_table tbody');

        if (!panel || !meta || !tbody) {
            return;
        }

        if (!selectedPatient) {
            panel.classList.add('d-none');
            return;
        }

        panel.classList.remove('d-none');
        meta.textContent = 'Patient: ' + (selectedPatient.patient_name || '-') + ' | UHID: ' + (selectedPatient.patient_code || '-') + ' | ABHA: ' + (selectedPatient.abha_address || '-');

        var selectedAbha = (selectedPatient.abha_address || '').toString().trim();
        var selectedItems = (items || []).filter(function (row) {
            var ctx = extractWorkflowContext(row);
            return selectedAbha !== '' && ctx.abhaAddress === selectedAbha;
        });

        var registry = computeConsentRegistry(selectedItems);
        updateWorkflowTodo(selectedItems, registry);

        tbody.innerHTML = '';
        if (!registry.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-muted text-center">No consent requests found for selected patient.</td></tr>';
            return;
        }

        registry.forEach(function (rec) {
            var action = consentRowAction(rec);
            var tr = document.createElement('tr');
            tr.innerHTML = '' +
                '<td><button type="button" class="btn btn-sm ' + action.cls + '" data-consent-row-idx="' + rec.row_idx + '" data-consent-action="' + action.op + '">' + escHtml(action.label) + '</button></td>' +
                '<td>' + escHtml(rec.consent_request_id || rec.request_id || '-') + '</td>' +
                '<td>' + escHtml(rec.consent_id || '-') + '</td>' +
                '<td>' + renderStatusBadge(rec.consent_status || rec.workflow_state || '-') + '</td>' +
                '<td>' + escHtml(rec.created_at || '-') + '</td>' +
                '<td>' + escHtml(rec.updated_at || '-') + '</td>';
            tbody.appendChild(tr);
        });

        tbody.querySelectorAll('button[data-consent-row-idx]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx = parseInt((btn.getAttribute('data-consent-row-idx') || ''), 10);
                var op = (btn.getAttribute('data-consent-action') || 'use').toString();
                if (Number.isNaN(idx) || !timelineItems[idx]) {
                    setResult({ ok: 0, message: 'Unable to resolve consent row context.' });
                    return;
                }
                runTimelineAction(idx, op);
            });
        });
    }

    function updateWorkflowTodo(selectedItems, registry) {
        var card = document.getElementById('workflow_todo_card');
        var itemsHost = document.getElementById('workflow_todo_items');
        if (!card || !itemsHost) {
            return;
        }

        if (!selectedPatient) {
            card.classList.add('d-none');
            return;
        }
        card.classList.remove('d-none');

        var latest = (registry && registry.length) ? registry[0] : null;
        var latestStatus = latest ? (latest.consent_status || '').toString().toUpperCase() : '';
        var latestState = latest ? (latest.workflow_state || '').toString().toUpperCase() : '';
        var hasConsentRequest = !!(latest && (latest.consent_request_id || latest.request_id));
        var hasStatusChecked = latestState === 'STATUS_CHECKED' || latestStatus === 'STATUS_CHECKED' || latestStatus === 'GRANTED';
        var hasArtifact = !!(latest && latest.consent_id);

        var selectedAbha = (selectedPatient.abha_address || '').toString().trim();
        var hasDataFetch = (selectedItems || []).some(function (row) {
            var ctx = extractWorkflowContext(row);
            var op = (row.operation || '').toString().toLowerCase();
            var status = (row.status || '').toString().toLowerCase();
            return ctx.abhaAddress === selectedAbha && op === 'data_fetch' && status === 'success';
        });

        var done = {
            1: true,
            2: hasConsentRequest,
            3: hasStatusChecked,
            4: hasDataFetch
        };

        itemsHost.querySelectorAll('span[data-step]').forEach(function (el) {
            var step = parseInt(el.getAttribute('data-step') || '0', 10);
            if (done[step]) {
                el.className = 'badge bg-success';
            } else {
                el.className = 'badge bg-secondary';
            }
        });
    }

    function mergeTimelineItems(items) {
        (items || []).forEach(function (row) {
            var key = (row && row.id !== undefined && row.id !== null) ? ('id:' + row.id) : null;
            if (key) {
                persistentTimelineMap[key] = row;
            }
        });

        var merged = Object.keys(persistentTimelineMap).map(function (k) { return persistentTimelineMap[k]; });
        merged.sort(function (a, b) {
            return (parseInt(b.id || 0, 10) || 0) - (parseInt(a.id || 0, 10) || 0);
        });

        return merged;
    }

    function renderTimelineRows(rows) {
        var tbody = document.querySelector('#timeline_table tbody');
        if (!tbody) {
            return;
        }
        tbody.innerHTML = '';
        timelineItems = Array.isArray(rows) ? rows : [];

        timelineItems.forEach(function (row, idx) {
            var ctx = extractWorkflowContext(row);
            var nextAction = getRowNextAction(ctx);
            var canStatus = !!(ctx.requestId || ctx.consentRequestId || ctx.consentArtifactId);
            var canFetch = !!ctx.consentArtifactId;

            var rowIdx = idx;
            var actions = [];
            actions.push('<button type="button" class="btn btn-sm btn-outline-info" data-row-action="use" data-row-idx="' + rowIdx + '">Use</button>');
            if (nextAction && nextAction.op) {
                actions.push('<button type="button" class="btn btn-sm btn-warning" data-row-action="auto_next" data-row-idx="' + rowIdx + '">Auto Next</button>');
            }

            if (canStatus) {
                actions.push('<button type="button" class="btn btn-sm ' + ((nextAction && nextAction.op === 'consent_status') ? 'btn-primary' : 'btn-outline-primary') + '" data-row-action="consent_status" data-row-idx="' + rowIdx + '">Check Status</button>');
            }
            if (canFetch) {
                actions.push('<button type="button" class="btn btn-sm ' + ((nextAction && nextAction.op === 'data_fetch') ? 'btn-primary' : 'btn-outline-success') + '" data-row-action="data_fetch" data-row-idx="' + rowIdx + '">Fetch Decrypted Data</button>');
            }

            var tr = document.createElement('tr');
            tr.innerHTML = '' +
                '<td><div class="d-flex flex-wrap gap-1">' + actions.join('') + '</div></td>' +
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

        tbody.querySelectorAll('button[data-row-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var action = (btn.getAttribute('data-row-action') || '').toString();
                var idx = parseInt(btn.getAttribute('data-row-idx'), 10);
                if (!action || Number.isNaN(idx) || !timelineItems[idx]) {
                    setResult({ ok: 0, message: 'Invalid timeline action context.' });
                    return;
                }
                runTimelineAction(idx, action);
            });
        });
    }

    function getRowNextAction(ctx) {
        var hasStatusRef = !!(ctx.requestId || ctx.consentRequestId || ctx.consentArtifactId);
        var hasArtifact = !!ctx.consentArtifactId;

        if (ctx.status === 'failed') {
            if (ctx.lastError.indexOf('consentid is required') !== -1 && hasStatusRef) {
                return { op: 'consent_status', label: 'Check Consent Status' };
            }
            if (ctx.lastError.indexOf('consent record not found') !== -1 && hasStatusRef) {
                return { op: 'consent_status', label: 'Check Consent Status' };
            }
            if (ctx.operation === 'data_fetch' && hasArtifact) {
                return { op: 'data_fetch', label: 'Retry Data Fetch' };
            }
        }

        if (ctx.operation === 'consent_request' && hasStatusRef) {
            return { op: 'consent_status', label: 'Check Consent Status' };
        }

        if (ctx.operation === 'consent_status' || ctx.operation === 'consent_reconcile') {
            if (ctx.workflowState === 'requested' && hasStatusRef) {
                return { op: 'consent_status', label: 'Check Consent Status' };
            }
            if ((ctx.workflowState === 'granted' || ctx.workflowState === 'approved' || ctx.workflowState === 'status_checked') && hasStatusRef) {
                return { op: 'data_fetch', label: 'Fetch Decrypted Data' };
            }
            if (hasStatusRef) {
                return { op: 'consent_status', label: 'Check Consent Status' };
            }
        }

        if ((ctx.operation === 'consent_fetch' || ctx.operation === 'data_fetch') && hasArtifact) {
            return { op: 'data_fetch', label: 'Fetch Decrypted Data' };
        }

        return null;
    }

    function getLatestTimelineRow() {
        if (!timelineItems.length) {
            return null;
        }
        return timelineItems[0] || null;
    }

    function suggestNextStep(row) {
        if (!row) {
            return {
                level: 'muted',
                message: 'No timeline rows found yet. Click Apply Filters or run an operation first.'
            };
        }

        var ctx = extractWorkflowContext(row);
        var operation = ctx.operation;
        var state = ctx.workflowState;
        var status = ctx.status;
        var error = ctx.lastError;
        var hasArtifact = !!ctx.consentArtifactId;
        var nextAction = getRowNextAction(ctx);

        if (status === 'failed') {
            if (error.indexOf('consentid is required') !== -1) {
                return { level: 'warning', message: 'Consent id missing. Run Check Consent Status until consent is GRANTED, then fetch decrypted data.' };
            }
            if (error.indexOf('consent record not found') !== -1) {
                return { level: 'warning', message: 'Consent not found on gateway. Verify request_id/consentRequestId and run Check Consent Status again.' };
            }
            if (error.indexOf('cloudfront') !== -1 || error.indexOf('request blocked') !== -1) {
                return { level: 'danger', message: 'Gateway edge blocked the call (CloudFront 403). Retry later or ask gateway team to unblock HIU endpoints.' };
            }
            return { level: 'warning', message: 'Latest operation failed. Review error column and retry the appropriate step.' };
        }

        if (operation === 'consent_request') {
            return { level: 'info', message: 'Next step: ' + (nextAction ? nextAction.label : 'Check Consent Status') + ' until patient approval is reflected.' };
        }
        if (operation === 'consent_status' || operation === 'consent_reconcile') {
            if (state === 'requested') {
                return { level: 'info', message: 'Consent still REQUESTED. Wait for patient approval in ABDM app, then Check Consent Status again.' };
            }
            if ((state === 'granted' || state === 'approved' || state === 'status_checked') && hasArtifact) {
                return { level: 'success', message: 'Consent seems ready. Next step: ' + (nextAction ? nextAction.label : 'Fetch Decrypted Data') + '.' };
            }
            if (state === 'granted' || state === 'approved' || state === 'status_checked') {
                return { level: 'info', message: 'Consent state is ready. Use Latest Log Context and run data fetch with consent id.' };
            }
        }
        if (operation === 'consent_fetch' || operation === 'data_fetch') {
            return { level: 'success', message: 'Data polling executed. Monitor timeline/poll summary for decrypted records.' };
        }

        return { level: 'muted', message: 'Use this row context and continue with the next HIU operation.' };
    }

    function renderNextStepHint(row) {
        var box = document.getElementById('log_next_step');
        if (!box) {
            return;
        }
        var hint = suggestNextStep(row);
        var classMap = {
            success: 'alert alert-success py-2 px-3 mb-0 small',
            info: 'alert alert-info py-2 px-3 mb-0 small',
            warning: 'alert alert-warning py-2 px-3 mb-0 small',
            danger: 'alert alert-danger py-2 px-3 mb-0 small',
            muted: 'alert alert-light py-2 px-3 mb-0 small text-muted'
        };
        box.className = classMap[hint.level] || classMap.muted;
        box.textContent = hint.message;
    }

    function applyLogContext(row, options) {
        options = options || {};
        if (!row) {
            if (options.showResult !== false) {
                setResult({ ok: 0, message: 'No timeline row found to apply.' });
            }
            return;
        }

        var ctx = extractWorkflowContext(row);
        var requestId = ctx.requestId;
        var transactionId = ctx.transactionId;
        var hfrId = ctx.hfrId;
        var abhaAddress = ctx.abhaAddress;
        var consentRequestId = ctx.consentRequestId;
        var consentArtifactId = ctx.consentArtifactId;
        var nextAction = getRowNextAction(ctx);

        if (requestId !== '') {
            setFieldValue(['request_id'], requestId);
        }
        if (transactionId !== '') {
            setFieldValue(['transaction_id'], transactionId);
        }
        if (abhaAddress !== '') {
            setFieldValue(['abha_address'], abhaAddress);
        }
        if (consentRequestId !== '') {
            setFieldValue(['consent_request_id', 'consent_id'], consentRequestId);
        }
        if (consentArtifactId !== '') {
            setFieldValue(['consent_artifact_id', 'consent_id'], consentArtifactId);
        }

        syncButtons((row.workflow_state || row.status || '').toString());
        renderNextStepHint(row);

        if (options.showResult !== false) {
            setResult({
                ok: 1,
                message: 'Applied log context to input fields.',
                operation: row.operation || '',
                workflow_state: row.workflow_state || '',
                status: row.status || '',
                request_id: requestId,
                consent_request_id: consentRequestId,
                consent_artifact_id: consentArtifactId,
                next_action: nextAction ? nextAction.op : ''
            });
        }

        return ctx;
    }

    function runTimelineAction(idx, op) {
        if (Number.isNaN(idx) || !timelineItems[idx]) {
            setResult({ ok: 0, message: 'Timeline row not found for action.' });
            return;
        }
        var row = timelineItems[idx];
        var ctx = extractWorkflowContext(row);
        var nextAction = getRowNextAction(ctx);

        if (op === 'auto_next') {
            if (!nextAction || !nextAction.op) {
                setResult({ ok: 0, message: 'No recommended next action for this row.' });
                return;
            }
            applyLogContext(row, { showResult: false });
            run(nextAction.op);
            return;
        }

        applyLogContext(row, { showResult: false });
        if (op === 'use') {
            setResult({ ok: 1, message: 'Log context applied. You can run the next step now.' });
            return;
        }
        run(op);
    }

    function syncButtons(consentStateText) {
        var hasPatient = !!selectedPatient;
        var consentRequestRef = getFieldValue(['consent_request_id', 'consent_id']);
        var consentArtifactRef = getFieldValue(['consent_artifact_id', 'consent_id']);
        var hasConsentRequestRef = (consentRequestRef !== '');
        var hasConsentArtifactRef = (consentArtifactRef !== '');
        var canFetchByState = isConsentApproved(consentStateText) || isTerminalConsentState(consentStateText);

        document.getElementById('btnConsentRequest').disabled = !canCreateConsentRequest(consentStateText);
        document.getElementById('btnConsentStatus').disabled = !hasConsentRequestRef;
        document.getElementById('btnConsentFetch').disabled = !((hasConsentArtifactRef || hasConsentRequestRef) && isSuccessConsentState(consentStateText) && canFetchByState);
        updateReadinessBox(consentStateText);
    }

    function setSelectedPatient(p) {
        selectedPatient = p;
        persistentTimelineMap = {};
        setFieldValue(['selected_patient'], (p.patient_name || '') + (p.patient_code ? ' (' + p.patient_code + ')' : ''));
        setFieldValue(['abha_address'], p.abha_address || '');
        setFieldValue(['request_id'], genReq('REQ-CONSENT'));
        setFieldValue(['transaction_id'], genReq('TXN-HIU'));

        var latest = p.latest_consent || {};
        var consentRequestRef = (latest.abdm_consent_request_id || '').toString();
        var consentArtifactRef = (latest.abdm_consent_artifact_id || latest.consent_id || '').toString();
        if (consentRequestRef) {
            setFieldValue(['consent_request_id', 'consent_id'], consentRequestRef);
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

    function normalizeConsentHiTypes(hiTypes) {
        var arr = Array.isArray(hiTypes) ? hiTypes : (hiTypes ? [hiTypes] : []);
        var map = {
            opconsultrecord: 'OPConsultation',
            opconsultation: 'OPConsultation',
            diagnosticreportrecord: 'DiagnosticReport',
            diagnosticreport: 'DiagnosticReport',
            prescriptionrecord: 'Prescription',
            prescription: 'Prescription',
            dischargesummaryrecord: 'DischargeSummary',
            dischargesummary: 'DischargeSummary',
            invoice: 'Invoice',
            invoicerecord: 'Invoice',
            healthdocumentrecord: 'HealthDocument',
            healthdocument: 'HealthDocument',
            immunization: 'ImmunizationRecord',
            immunizationrecord: 'ImmunizationRecord',
            wellness: 'Wellness',
            wellnessrecord: 'Wellness'
        };

        var out = [];
        arr.forEach(function (item) {
            var raw = (item || '').toString().trim();
            if (!raw) {
                return;
            }
            var key = raw.toLowerCase();
            var resolved = map[key] || raw;
            if (out.indexOf(resolved) === -1) {
                out.push(resolved);
            }
        });

        return out;
    }

    function buildGatewayPayload(op, raw) {
        var requestId = (raw.requestId || raw.request_id || '').toString().trim();
        var timestamp = (raw.timestamp || '').toString().trim();
        var abhaAddress = (raw.abha_address || '').toString().trim();
        var base = {
            requestId: requestId,
            timestamp: timestamp || new Date().toISOString()
        };
        if (abhaAddress) {
            base.abha_address = abhaAddress;
        }

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
                            from: new Date(Date.now() - (365 * 24 * 60 * 60 * 1000)).toISOString(),
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

            consent.hiTypes = normalizeConsentHiTypes(consent.hiTypes || []);
            if (!consent.hiTypes.length) {
                throw new Error('consent.hiTypes is required for consent request.');
            }

            base.consent = consent;
            return base;
        }

        if (op === 'consent_status') {
            var requestIdRef = (raw.request_id || raw.requestId || '').toString().trim();
            var consentRequestId = (raw.abdm_consent_request_id || raw.consentRequestId || '').toString().trim();
            var consentIdRef = (raw.abdm_consent_artifact_id || raw.consentId || raw.consent_id || '').toString().trim();

            if (!requestIdRef && !consentRequestId && !consentIdRef) {
                throw new Error('Provide one of request_id, consentRequestId, or consentId for consent status check.');
            }

            if (requestIdRef) {
                base.request_id = requestIdRef;
            }
            if (consentRequestId) {
                base.consent_request_id = consentRequestId;
            }
            if (consentIdRef) {
                base.consent_id = consentIdRef;
            }
            return base;
        }

        if (op === 'data_fetch') {
            var consentIdFetch = (raw.abdm_consent_artifact_id || raw.consentId || raw.consent_id || '').toString().trim();
            var consentRequestRefFetch = (raw.abdm_consent_request_id || raw.consentRequestId || raw.consent_request_id || '').toString().trim();

            // Gateway accepts original consent request id in consent_id for NAT polling.
            var requestIdRefFetch = (raw.request_id || raw.requestId || '').toString().trim();
            var consentRef = consentIdFetch || consentRequestRefFetch || requestIdRefFetch;
            if (!consentRef) {
                throw new Error('consent_id is required for decrypted data fetch. Run status polling until consent is GRANTED.');
            }

            base.consent_id = consentRef;
            return base;
        }

        throw new Error('Unsupported operation');
    }

    function setResult(obj) {
        renderDataPreview(obj);
        document.getElementById('result_box').textContent = JSON.stringify(obj, null, 2);
    }

    function parseMaybeJson(raw) {
        if (raw && typeof raw === 'object') {
            return raw;
        }
        var txt = (raw || '').toString().trim();
        if (!txt) {
            return null;
        }
        try {
            return JSON.parse(txt);
        } catch (e) {
            return null;
        }
    }

    function extractSessionsFromResponse(res) {
        var data = (res && typeof res === 'object') ? (res.data || {}) : {};
        var sessions = [];

        if (Array.isArray(data.sessions)) {
            sessions = data.sessions;
        } else if (Array.isArray(res.sessions)) {
            sessions = res.sessions;
        }

        return sessions;
    }

    function summarizeBundleType(decryptedItem) {
        var parsed = parseMaybeJson((decryptedItem || {}).decrypted_data);
        if (!parsed || typeof parsed !== 'object') {
            return '-';
        }
        var bundleType = (parsed.type || '').toString().trim();
        var resourceType = (parsed.resourceType || '').toString().trim();
        if (resourceType && bundleType) {
            return resourceType + ' / ' + bundleType;
        }
        return resourceType || bundleType || '-';
    }

    function renderDataPreview(res) {
        var card = document.getElementById('data_preview_card');
        var meta = document.getElementById('data_preview_meta');
        var tbody = document.querySelector('#data_preview_table tbody');
        if (!card || !meta || !tbody) {
            return;
        }

        var sessions = extractSessionsFromResponse(res);
        if (!sessions.length) {
            card.classList.add('d-none');
            return;
        }

        card.classList.remove('d-none');
        var totalRecords = 0;
        sessions.forEach(function (s) {
            var items = Array.isArray(s.decrypted_data) ? s.decrypted_data : [];
            totalRecords += items.length;
        });
        meta.textContent = 'Sessions: ' + sessions.length + ' | Records: ' + totalRecords;

        tbody.innerHTML = '';
        sessions.forEach(function (session) {
            var records = Array.isArray(session.decrypted_data) ? session.decrypted_data : [];
            var contexts = records.map(function (r) {
                return (r.careContextReference || '').toString().trim();
            }).filter(function (v) { return v !== ''; });

            var tr = document.createElement('tr');
            tr.innerHTML = '' +
                '<td>' + escHtml((session.transaction_id || '-').toString()) + '</td>' +
                '<td>' + escHtml((session.consent_id || '-').toString()) + '</td>' +
                '<td>' + escHtml((session.status || '-').toString()) + '</td>' +
                '<td>' + records.length + '</td>' +
                '<td>' + escHtml((contexts.slice(0, 3).join(', ') || '-').toString()) + '</td>' +
                '<td>' + escHtml(summarizeBundleType(records[0] || {})) + '</td>';
            tbody.appendChild(tr);
        });
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

    function parseDisplayDate(v) {
        var txt = (v || '').toString().trim();
        if (!txt) {
            return '-';
        }
        var d = new Date(txt.replace(' ', 'T'));
        if (isNaN(d.getTime())) {
            return txt;
        }
        return d.toLocaleString();
    }

    function openAjaxDocsPanel() {
        var panel = document.getElementById('ajax_docs_panel');
        if (!panel) {
            return;
        }
        panel.classList.remove('d-none');
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        if (!ajaxDocSelectedPatient) {
            ajaxDocsSearchPatients();
        }
    }

    function closeAjaxDocsPanel() {
        var panel = document.getElementById('ajax_docs_panel');
        if (!panel) {
            return;
        }
        panel.classList.add('d-none');
    }

    function ajaxDocsRenderPatients(items) {
        var tbody = document.querySelector('#ajax_doc_patient_table tbody');
        if (!tbody) {
            return;
        }
        tbody.innerHTML = '';
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-muted text-center">No patients found.</td></tr>';
            return;
        }

        items.forEach(function (p, idx) {
            var tr = document.createElement('tr');
            tr.innerHTML = '' +
                '<td><button class="btn btn-sm btn-outline-primary" data-ajax-doc-patient-idx="' + idx + '">Select</button></td>' +
                '<td>' + escHtml(p.patient_name || '') + '</td>' +
                '<td>' + escHtml(p.patient_code || '') + '</td>' +
                '<td>' + escHtml(p.abha_address || '') + '</td>';
            tbody.appendChild(tr);
        });

        tbody.querySelectorAll('button[data-ajax-doc-patient-idx]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx = parseInt(btn.getAttribute('data-ajax-doc-patient-idx'), 10);
                if (Number.isNaN(idx) || !items[idx]) {
                    return;
                }
                ajaxDocSelectedPatient = items[idx];
                var status = document.getElementById('ajax_doc_patient_status');
                if (status) {
                    status.textContent = 'Selected: ' + (ajaxDocSelectedPatient.patient_name || '-') + ' | ABHA: ' + (ajaxDocSelectedPatient.abha_address || '-');
                }
                ajaxDocsLoadDocuments();
            });
        });
    }

    function ajaxDocsSearchPatients() {
        var q = getFieldValue(['ajax_doc_patient_search']);
        fetch(patientLookupUrl + '?q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if ((res.ok || 0) !== 1) {
                var status = document.getElementById('ajax_doc_patient_status');
                if (status) {
                    status.textContent = res.error || 'Patient search failed';
                }
                return;
            }
            ajaxDocsRenderPatients(Array.isArray(res.items) ? res.items : []);
            var s = document.getElementById('ajax_doc_patient_status');
            if (s) {
                s.textContent = (res.count || 0) + ' patient(s) found';
            }
        })
        .catch(function (e) {
            var status = document.getElementById('ajax_doc_patient_status');
            if (status) {
                status.textContent = e.message || 'Patient search failed';
            }
        });
    }

    function ajaxDocsRenderList(items) {
        ajaxDocItems = items || [];
        var tbody = document.querySelector('#ajax_doc_table tbody');
        if (!tbody) {
            return;
        }
        tbody.innerHTML = '';

        if (!ajaxDocItems.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-muted text-center">No documents loaded.</td></tr>';
            var box = document.getElementById('ajax_doc_detail_box');
            if (box) {
                box.innerHTML = '<div class="text-muted">No documents found for selected patient.</div>';
            }
            return;
        }

        ajaxDocItems.forEach(function (doc, idx) {
            var tr = document.createElement('tr');
            tr.style.cursor = 'pointer';
            tr.setAttribute('data-ajax-doc-idx', idx);
            tr.innerHTML = '' +
                '<td>' + escHtml(parseDisplayDate(doc.document_date || doc.created_at)) + '</td>' +
                '<td>' + escHtml(doc.document_title || '-') + '</td>' +
                '<td>' + escHtml(doc.care_context_reference || '-') + '</td>' +
                '<td>' + escHtml(doc.practitioner_name || '-') + '</td>';
            tbody.appendChild(tr);
        });

        tbody.querySelectorAll('tr[data-ajax-doc-idx]').forEach(function (row) {
            row.addEventListener('click', function () {
                var idx = parseInt(row.getAttribute('data-ajax-doc-idx'), 10);
                if (Number.isNaN(idx) || !ajaxDocItems[idx]) {
                    return;
                }
                ajaxDocsLoadDetail((ajaxDocItems[idx].id || 0));
            });
        });

        ajaxDocsLoadDetail((ajaxDocItems[0].id || 0));
    }

    function ajaxDocsLoadDocuments() {
        if (!ajaxDocSelectedPatient) {
            setResult({ ok: 0, message: 'Select a patient first in fetched documents panel.' });
            return;
        }

        var q = getFieldValue(['ajax_doc_search']);
        var url = docListUrl
            + '?patient_id=' + encodeURIComponent(ajaxDocSelectedPatient.patient_id || 0)
            + '&abha_address=' + encodeURIComponent(ajaxDocSelectedPatient.abha_address || '')
            + '&q=' + encodeURIComponent(q);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if ((res.ok || 0) !== 1) {
                    ajaxDocsRenderList([]);
                    return;
                }
                ajaxDocsRenderList(Array.isArray(res.items) ? res.items : []);
            })
            .catch(function () {
                ajaxDocsRenderList([]);
            });
    }

    function ajaxDocsRenderBulletList(title, rows, formatter) {
        var list = Array.isArray(rows) ? rows : [];
        if (!list.length) {
            return '<div class="mb-2"><div class="fw-semibold">' + escHtml(title) + '</div><div class="text-muted">No data</div></div>';
        }
        var html = '<div class="mb-2"><div class="fw-semibold">' + escHtml(title) + '</div><ul class="mb-0">';
        list.forEach(function (row) {
            html += '<li>' + formatter(row) + '</li>';
        });
        html += '</ul></div>';
        return html;
    }

    function ajaxDocsLoadDetail(id) {
        var box = document.getElementById('ajax_doc_detail_box');
        if (!box) {
            return;
        }
        if (!id) {
            box.innerHTML = '<div class="text-muted">Select a document to view detail.</div>';
            return;
        }

        box.innerHTML = '<div class="text-muted">Loading...</div>';
        fetch(docDetailBaseUrl + '/' + encodeURIComponent(id), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if ((res.ok || 0) !== 1) {
                box.innerHTML = '<div class="text-danger">Unable to load detail.</div>';
                return;
            }

            var item = res.item || {};
            var s = item.summary || {};
            var html = ''
                + '<div class="row g-2 mb-3">'
                + '<div class="col-md-6"><span class="text-muted">Patient:</span> <strong>' + escHtml(s.patient_name || item.patient_name || '-') + '</strong></div>'
                + '<div class="col-md-6"><span class="text-muted">ABHA:</span> ' + escHtml(item.abha_address || '-') + '</div>'
                + '<div class="col-md-6"><span class="text-muted">Document:</span> ' + escHtml(item.document_title || '-') + '</div>'
                + '<div class="col-md-6"><span class="text-muted">Date:</span> ' + escHtml(parseDisplayDate(item.document_date || '')) + '</div>'
                + '<div class="col-md-6"><span class="text-muted">Doctor:</span> ' + escHtml(item.practitioner_name || '-') + '</div>'
                + '<div class="col-md-6"><span class="text-muted">Organization:</span> ' + escHtml(item.organization_name || '-') + '</div>'
                + '<div class="col-md-6"><span class="text-muted">Care Context:</span> ' + escHtml(item.care_context_reference || '-') + '</div>'
                + '<div class="col-md-6"><span class="text-muted">Bundle:</span> ' + escHtml(item.bundle_type || '-') + '</div>'
                + '</div>';

            html += ajaxDocsRenderBulletList('Diagnoses', s.conditions || [], function (row) {
                return escHtml((row && row.text) || '-');
            });
            html += ajaxDocsRenderBulletList('Vitals', s.vitals || [], function (row) {
                return escHtml(((row && row.name) || '-') + ': ' + ((row && row.value) || '-'));
            });
            html += ajaxDocsRenderBulletList('Medications', s.medications || [], function (row) {
                var name = (row && row.name) || '-';
                var dose = (row && row.dose) || '';
                return escHtml(name + (dose ? (' | ' + dose) : ''));
            });

            box.innerHTML = html;
        })
        .catch(function (e) {
            box.innerHTML = '<div class="text-danger">' + escHtml(e.message || 'Unable to load detail') + '</div>';
        });
    }

    function run(op, options) {
        options = options || {};
        var url = opRoute[op];
        if (!url) {
            setResult({ ok: 0, message: 'Unsupported operation' });
            return;
        }

        if (op === 'consent_request' && hasExistingConsentFlowContext()) {
            setResult({
                ok: 0,
                message: 'Consent request already exists for this patient flow. Continue with Check Consent Status.'
            });
            syncButtons();
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
                if (consentReqRef) {
                    setFieldValue(['consent_request_id', 'consent_id'], consentReqRef);
                }

                var consentArtifactRef = (data.abdm_consent_artifact_id || data.consentId || data.consent_id || '').toString();
                if (isValidConsentArtifactId(consentArtifactRef)) {
                    setFieldValue(['consent_artifact_id', 'consent_id'], consentArtifactRef);
                }

                var state = resolveConsentStateFromResponse(res, data);
                syncButtons(state);

                if (op === 'consent_request') {
                    startConsentPolling();
                }

                // Ordered workflow automation: status success -> data fetch polling.
                if (op === 'consent_status' && isSuccessConsentState(state) && (isValidConsentArtifactId(consentArtifactRef) || consentReqRef)) {
                    if (isAutoNextStepEnabled() && !isAutoCoolingDown()) {
                        run('data_fetch', { silent: true });
                    }
                }
                if (op === 'data_fetch' && isConsentApproved(state) && isValidConsentArtifactId(consentArtifactRef)) {
                    // Always stop status polling once artifact fetch succeeds.
                    stopConsentPolling();
                    clearAutoCooldown();
                }
                if ((op === 'consent_status' || op === 'data_fetch') && isTerminalConsentState(state)) {
                    stopConsentPolling();
                }
            } else if (op === 'consent_status' || op === 'data_fetch') {
                var errState = resolveConsentStateFromResponse(res, res.data || {});
                var errText = (res.error || ((res.data || {}).error_text) || '').toString().toLowerCase();
                if (isTerminalConsentState(errState) || errText.indexOf('mapping_error') !== -1) {
                    stopConsentPolling();
                }
                if (op === 'data_fetch') {
                    var dataCode = parseInt(((res.data || {}).http_code || res.http_code || 0), 10);
                    if (dataCode === 403 || errText.indexOf('cloudfront') !== -1 || errText.indexOf('request blocked') !== -1) {
                        setAutoCooldown(180000, 'Data fetch blocked (403). Retry after cooldown or after gateway unblocks endpoint.');
                        syncButtons();
                    }
                }
            }

            fetchTimeline();
        }).catch(function (e) {
            setResult({ ok: 0, message: e.message });
        });
    }

    function startConsentPolling() {
        if (!isAutoStatusPollingEnabled()) {
            return;
        }
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

    function renderPollErrors() {
        var list = document.getElementById('poll_error_list');
        if (!list) {
            return;
        }

        list.innerHTML = '';
        if (!recentPollErrors.length) {
            var li = document.createElement('li');
            li.textContent = 'No recent polling errors in selected lookback window.';
            list.appendChild(li);
            return;
        }

        recentPollErrors.forEach(function (row) {
            var when = (row.updated_at || '-').toString();
            var op = (row.operation || '-').toString();
            var req = (row.request_id || '-').toString();
            var err = (row.last_error || 'Unknown error').toString();
            var li = document.createElement('li');
            li.textContent = when + ' | ' + op + ' | ' + req + ' | ' + err;
            list.appendChild(li);
        });
    }

    function refreshPollSummary() {
        fetch(pollSummaryUrl + '?lookback=180', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var badge = document.getElementById('poll_summary_badge');
            if (!badge) {
                return;
            }
            if ((res.ok || 0) !== 1) {
                badge.className = 'badge bg-danger';
                badge.textContent = 'Poll: unavailable';
                return;
            }

            var consentUpdates = parseInt(res.consent_updates || 0, 10);
            var dataUpdates = parseInt(res.data_updates || 0, 10);
            var failed = parseInt(res.failed || 0, 10);
            var pending = parseInt(res.pending || 0, 10);
            var lastAt = (res.last_polled_at || '-').toString();
            recentPollErrors = Array.isArray(res.recent_errors) ? res.recent_errors : [];
            renderPollErrors();

            badge.className = failed > 0 ? 'badge bg-warning text-dark' : 'badge bg-success';
            badge.textContent = 'Poll c:' + consentUpdates + ' d:' + dataUpdates + ' f:' + failed + ' p:' + pending + ' | ' + lastAt;
        })
        .catch(function () {
            var badge = document.getElementById('poll_summary_badge');
            if (!badge) {
                return;
            }
            badge.className = 'badge bg-danger';
            badge.textContent = 'Poll: unavailable';
            recentPollErrors = [];
            renderPollErrors();
        });
    }

    function fetchTimeline() {
        function hasExplicitTimelineFilter() {
            return getFieldValue(['f_hfr_id']) !== ''
                || getFieldValue(['f_consent_id']) !== ''
                || getFieldValue(['f_transaction_id']) !== ''
                || getFieldValue(['f_abha_address']) !== ''
                || getFieldValue(['f_date_from']) !== ''
                || getFieldValue(['f_date_to']) !== '';
        }

        if (!selectedPatient && !hasExplicitTimelineFilter()) {
            timelineItems = [];
            persistentTimelineMap = {};
            renderTimelineRows([]);
            renderNextStepHint(null);
            var tbody = document.querySelector('#timeline_table tbody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="12" class="text-muted text-center">Select a patient to view workflow logs, or use filters to query global logs.</td></tr>';
            }
            return;
        }

        var abhaFilter = getFieldValue(['f_abha_address']);
        if (abhaFilter === '' && selectedPatient && selectedPatient.abha_address) {
            abhaFilter = selectedPatient.abha_address;
        }

        var qs = new URLSearchParams({
            hfr_id: getFieldValue(['f_hfr_id']),
            consent_id: getFieldValue(['f_consent_id']),
            transaction_id: getFieldValue(['f_transaction_id']),
            abha_address: abhaFilter,
            date_from: getFieldValue(['f_date_from']),
            date_to: getFieldValue(['f_date_to'])
        });

        fetch('<?= base_url('AbdmHiu/timeline') ?>?' + qs.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var incomingItems = Array.isArray(res.items) ? res.items : [];
            if (incomingItems.length) {
                mergeTimelineItems(incomingItems);
            }

            var mergedItems = mergeTimelineItems([]);
            renderTimelineRows(mergedItems);
            renderConsentLists(mergedItems);

            renderNextStepHint(getLatestTimelineRow());

            refreshPollSummary();
        });
    }

    function clearTimelineFilters() {
        setFieldValue(['f_hfr_id'], '');
        setFieldValue(['f_consent_id'], '');
        setFieldValue(['f_transaction_id'], '');
        setFieldValue(['f_abha_address'], '');
        setFieldValue(['f_date_from'], '');
        setFieldValue(['f_date_to'], '');
        fetchTimeline();
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
    var btnOpenDocumentsAjax = document.getElementById('btnOpenDocumentsAjax');
    if (btnOpenDocumentsAjax) {
        btnOpenDocumentsAjax.addEventListener('click', openAjaxDocsPanel);
    }
    var btnCloseAjaxDocs = document.getElementById('btnCloseAjaxDocs');
    if (btnCloseAjaxDocs) {
        btnCloseAjaxDocs.addEventListener('click', closeAjaxDocsPanel);
    }
    var btnAjaxDocPatientSearch = document.getElementById('btnAjaxDocPatientSearch');
    if (btnAjaxDocPatientSearch) {
        btnAjaxDocPatientSearch.addEventListener('click', ajaxDocsSearchPatients);
    }
    var btnAjaxDocSearch = document.getElementById('btnAjaxDocSearch');
    if (btnAjaxDocSearch) {
        btnAjaxDocSearch.addEventListener('click', ajaxDocsLoadDocuments);
    }
    var ajaxDocPatientSearch = document.getElementById('ajax_doc_patient_search');
    if (ajaxDocPatientSearch) {
        ajaxDocPatientSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                ajaxDocsSearchPatients();
            }
        });
    }
    var ajaxDocSearch = document.getElementById('ajax_doc_search');
    if (ajaxDocSearch) {
        ajaxDocSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                ajaxDocsLoadDocuments();
            }
        });
    }
    var btnUseLatestLog = document.getElementById('btnUseLatestLog');
    if (btnUseLatestLog) {
        btnUseLatestLog.addEventListener('click', function () {
            applyLogContext(getLatestTimelineRow());
        });
    }
    var btnClearFilters = document.getElementById('btnClearFilters');
    if (btnClearFilters) {
        btnClearFilters.addEventListener('click', clearTimelineFilters);
    }
    var btnPollErrors = document.getElementById('btnPollErrors');
    if (btnPollErrors) {
        btnPollErrors.addEventListener('click', function () {
            var panel = document.getElementById('poll_error_panel');
            if (!panel) {
                return;
            }
            panel.classList.toggle('d-none');
            if (panel.classList.contains('d-none')) {
                btnPollErrors.textContent = 'Show Poll Errors';
            } else {
                renderPollErrors();
                btnPollErrors.textContent = 'Hide Poll Errors';
            }
        });
    }

    var btnStartFreshFlow = document.getElementById('btnStartFreshFlow');
    if (btnStartFreshFlow) {
        btnStartFreshFlow.addEventListener('click', function () {
            setFieldValue(['request_id'], genReq('REQ-CONSENT'));
            setFieldValue(['transaction_id'], genReq('TXN-HIU'));
            setFieldValue(['consent_request_id', 'consent_id'], '');
            setFieldValue(['consent_artifact_id'], '');
            syncButtons();
            setResult({ ok: 1, message: 'Started fresh flow context for selected patient.' });
        });
    }

    var toggleAutoStatusPolling = document.getElementById('toggleAutoStatusPolling');
    if (toggleAutoStatusPolling) {
        toggleAutoStatusPolling.checked = false;
        toggleAutoStatusPolling.addEventListener('change', function () {
            if (!toggleAutoStatusPolling.checked) {
                stopConsentPolling();
            }
            syncButtons();
        });
    }

    var toggleAutoNextStep = document.getElementById('toggleAutoNextStep');
    if (toggleAutoNextStep) {
        toggleAutoNextStep.checked = false;
        toggleAutoNextStep.addEventListener('change', function () {
            syncButtons();
        });
    }

    syncButtons();
    refreshPollSummary();
    fetchTimeline();
})();
</script>

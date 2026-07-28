<div class="container-fluid py-3">
    <div class="row g-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">ABHA Patient Request List</h5>
                    <button class="btn btn-sm btn-outline-primary" id="btnRefreshPatientRequests" type="button">Refresh</button>
                </div>
                <div class="card-body">
                    <div class="alert alert-info small mb-3">
                        Consent request history for every patient, most recent first. Open a patient's OPD profile to raise a new request.
                    </div>

                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-4">
                            <label class="form-label mb-1">Search Patient</label>
                            <input id="prlSearch" class="form-control" placeholder="Name / UHID / Mobile / ABHA Address">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">Status</label>
                            <select id="prlStatus" class="form-select">
                                <option value="">All</option>
                                <option value="REQUESTED">Requested</option>
                                <option value="GRANTED">Granted</option>
                                <option value="COMPLETED">Completed</option>
                                <option value="REVOKED">Revoked</option>
                                <option value="EXPIRED">Expired</option>
                                <option value="DENIED">Denied</option>
                                <option value="FAILED">Failed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button id="btnPrlSearch" class="btn btn-outline-secondary w-100" type="button">Apply</button>
                        </div>
                        <div class="col-md-3">
                            <div id="prlCountBox" class="small text-muted text-md-end">Loading...</div>
                        </div>
                    </div>

                    <div class="table-responsive border rounded">
                        <table class="table table-sm table-hover mb-0" id="prlTable">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>UHID</th>
                                    <th>ABHA Address</th>
                                    <th>Status</th>
                                    <th>HI Types</th>
                                    <th>Requested By</th>
                                    <th>Requested On</th>
                                    <th>Expiry</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="9" class="text-muted text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="prlDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Consent Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="prlDetailBody">
                <div class="text-muted small">Loading...</div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var listUrl = '<?= base_url('AbdmHiu/patient_request_list_data') ?>';
    var fetchOnlyBaseUrl = '<?= base_url('billing/patient/abdm_content_fetch_only') ?>';
    var openPatientBaseUrl = '<?= base_url('billing/patient/show_profile_opd') ?>';
    var currentItems = [];
    var fetchRunningIdx = -1;

    function escHtml(v) {
        return (v || '').toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function fmtTs(value) {
        value = (value || '').toString().trim();
        if (value === '') {
            return '-';
        }
        var d = new Date(value.indexOf('T') === -1 ? value.replace(' ', 'T') : value);
        if (isNaN(d.getTime())) {
            return escHtml(value);
        }
        return escHtml(d.toLocaleString());
    }

    function statusBadgeClass(status) {
        switch ((status || '').toString().toUpperCase()) {
            case 'GRANTED': return 'bg-info-subtle text-info border border-info-subtle';
            case 'COMPLETED': return 'bg-success-subtle text-success border border-success-subtle';
            case 'REVOKED': return 'bg-secondary-subtle text-secondary border border-secondary-subtle';
            case 'EXPIRED': return 'bg-warning-subtle text-warning border border-warning-subtle';
            case 'DENIED': return 'bg-danger-subtle text-danger border border-danger-subtle';
            case 'FAILED': return 'bg-danger-subtle text-danger border border-danger-subtle';
            default: return 'bg-warning-subtle text-warning border border-warning-subtle';
        }
    }

    function itemBadgeClass(status) {
        switch ((status || '').toString().toUpperCase()) {
            case 'GRANTED': return 'bg-success-subtle text-success border border-success-subtle';
            case 'REVOKED': return 'bg-secondary-subtle text-secondary border border-secondary-subtle';
            case 'EXPIRED': return 'bg-warning-subtle text-warning border border-warning-subtle';
            case 'DENIED': return 'bg-danger-subtle text-danger border border-danger-subtle';
            case 'FAILED': return 'bg-danger-subtle text-danger border border-danger-subtle';
            default: return 'bg-info-subtle text-info border border-info-subtle';
        }
    }

    function renderDetail(consent) {
        if (!consent) {
            return '<div class="text-danger small">No consent details available.</div>';
        }
        var metaHtml = '<div class="row small text-muted mb-3">'
            + '<div class="col-md-6">Patient: <strong>' + escHtml(consent.patient_name || '-') + '</strong></div>'
            + '<div class="col-md-6">UHID: <strong>' + escHtml(consent.patient_code || '-') + '</strong></div>'
            + '<div class="col-md-6">Consent ID: <strong>' + escHtml(consent.consent_id || '-') + '</strong></div>'
            + '<div class="col-md-6">ABHA: <strong>' + escHtml(consent.abha_address || '-') + '</strong></div>'
            + '<div class="col-md-6">Purpose: <strong>' + escHtml(consent.purpose || '-') + '</strong></div>'
            + '<div class="col-md-6">Status: <strong>' + escHtml(consent.status || '-') + '</strong></div>'
            + '<div class="col-md-6">Valid From: <strong>' + fmtTs(consent.valid_from) + '</strong></div>'
            + '<div class="col-md-6">Valid To: <strong>' + fmtTs(consent.valid_to) + '</strong></div>'
            + '<div class="col-md-6">Requested On: <strong>' + fmtTs(consent.requested_on) + '</strong></div>'
            + '<div class="col-md-6">Erase Date: <strong>' + fmtTs(consent.erase_at) + '</strong></div>'
            + '</div>';

        var rowsHtml = '';
        (consent.items || []).forEach(function(item) {
            rowsHtml += '<tr>'
                + '<td>' + escHtml(item.document_name || '') + '</td>'
                + '<td>' + escHtml(item.permission || 'VIEW') + '</td>'
                + '<td><span class="badge ' + itemBadgeClass(item.status) + '">' + escHtml(item.status || '') + '</span></td>'
                + '<td>' + fmtTs(item.timestamp) + '</td>'
                + '</tr>';
        });
        if (rowsHtml === '') {
            rowsHtml = '<tr><td colspan="4" class="text-muted text-center">No health information types recorded for this request.</td></tr>';
        }

        return metaHtml + '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">'
            + '<thead><tr><th>Document Type</th><th>Permission</th><th>Status</th><th>Timestamp</th></tr></thead>'
            + '<tbody>' + rowsHtml + '</tbody></table></div>';
    }

    function renderTable(items) {
        if (!items || !items.length) {
            $('#prlTable tbody').html('<tr><td colspan="9" class="text-muted text-center">No consent requests found.</td></tr>');
            return;
        }

        var html = '';
        items.forEach(function(consent, idx) {
            var status = (consent.status || '').toString().toUpperCase();
            var hiTypes = (consent.requested_hi_types && consent.requested_hi_types.length) ? consent.requested_hi_types : (consent.granted_hi_types || []);
            var hiTypesText = hiTypes.length ? hiTypes.join(', ') : '-';
            var patientId = Number(consent.patient_id || 0);
            var canFetch = (status === 'GRANTED' || status === 'COMPLETED') && patientId > 0;
            var canOpen = patientId > 0;

            html += '<tr>'
                + '<td class="small">' + escHtml(consent.patient_name || '-') + '</td>'
                + '<td class="small">' + escHtml(consent.patient_code || '-') + '</td>'
                + '<td class="small">' + escHtml(consent.abha_address || '-') + '</td>'
                + '<td><span class="badge ' + statusBadgeClass(status) + '">' + escHtml(status || 'UNKNOWN') + '</span></td>'
                + '<td class="small">' + escHtml(hiTypesText) + '</td>'
                + '<td class="small">' + escHtml(consent.requested_by || 'HMS') + '</td>'
                + '<td class="small">' + fmtTs(consent.requested_on) + '</td>'
                + '<td class="small">' + fmtTs(consent.valid_to) + '</td>'
                + '<td class="text-end">'
                + '<button type="button" class="btn btn-link btn-sm p-0 me-2 prl-view-btn" data-idx="' + idx + '">View</button>'
                + (canFetch ? ('<button type="button" class="btn btn-link btn-sm p-0 me-2 prl-fetch-btn" data-idx="' + idx + '">Fetch Records</button>') : '')
                + (canOpen ? ('<button type="button" class="btn btn-link btn-sm p-0 prl-open-btn" data-idx="' + idx + '">Open Patient</button>') : '')
                + '</td>'
                + '</tr>';
        });
        $('#prlTable tbody').html(html);
    }

    function loadList() {
        $('#prlCountBox').text('Loading...');
        var url = listUrl + '?q=' + encodeURIComponent($('#prlSearch').val() || '') + '&status=' + encodeURIComponent($('#prlStatus').val() || '');
        fetch(url, { credentials: 'same-origin' })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (!data || data.ok !== 1) {
                    throw new Error((data && data.error) || 'Unable to load consent requests.');
                }
                currentItems = Array.isArray(data.requests) ? data.requests : [];
                renderTable(currentItems);
                $('#prlCountBox').text(currentItems.length + ' request(s) found.');
            })
            .catch(function(err) {
                $('#prlTable tbody').html('<tr><td colspan="9" class="text-danger text-center">' + escHtml('Failed to load: ' + (err.message || err)) + '</td></tr>');
                $('#prlCountBox').text('Failed to load.');
            });
    }

    $(document).off('click.prl', '#btnRefreshPatientRequests').on('click.prl', '#btnRefreshPatientRequests', function() {
        loadList();
    });
    $(document).off('click.prl', '#btnPrlSearch').on('click.prl', '#btnPrlSearch', function() {
        loadList();
    });
    $(document).off('keypress.prl', '#prlSearch').on('keypress.prl', '#prlSearch', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            loadList();
        }
    });
    $(document).off('change.prl', '#prlStatus').on('change.prl', '#prlStatus', function() {
        loadList();
    });

    $(document).off('click.prl', '.prl-view-btn').on('click.prl', '.prl-view-btn', function() {
        var idx = Number($(this).data('idx'));
        var consent = currentItems[idx];
        $('#prlDetailBody').html(renderDetail(consent));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('prlDetailModal')).show();
    });

    $(document).off('click.prl', '.prl-open-btn').on('click.prl', '.prl-open-btn', function() {
        var idx = Number($(this).data('idx'));
        var consent = currentItems[idx];
        if (consent && consent.patient_id && typeof load_form === 'function') {
            load_form(openPatientBaseUrl + '/' + consent.patient_id, consent.patient_name || 'Patient');
        }
    });

    $(document).off('click.prl', '.prl-fetch-btn').on('click.prl', '.prl-fetch-btn', function() {
        var idx = Number($(this).data('idx'));
        if (fetchRunningIdx !== -1) {
            return;
        }
        var consent = currentItems[idx];
        if (!consent || !consent.patient_id) {
            return;
        }

        fetchRunningIdx = idx;
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.text('Fetching...').prop('disabled', true);

        var url = fetchOnlyBaseUrl + '/' + consent.patient_id + '?consent_id=' + encodeURIComponent(consent.consent_id || '') + '&consent_request_id=' + encodeURIComponent(consent.consent_request_id || '');
        fetch(url, { credentials: 'same-origin' })
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (!data || data.ok !== 1) {
                    throw new Error((data && data.error) || 'Fetch failed.');
                }
                fetchRunningIdx = -1;
                $btn.text(originalText).prop('disabled', false);
                loadList();
            })
            .catch(function(err) {
                fetchRunningIdx = -1;
                $btn.text(originalText).prop('disabled', false);
                alert('Fetch failed: ' + (err.message || err));
            });
    });

    loadList();
})();
</script>

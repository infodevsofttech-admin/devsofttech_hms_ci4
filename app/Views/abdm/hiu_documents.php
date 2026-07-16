<div class="container-fluid py-3">
    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">ABDM Fetched Patient Documents</h5>
            <a class="btn btn-sm btn-outline-primary" href="<?= base_url('AbdmHiu') ?>">Back To HIU Console</a>
        </div>
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label mb-1">Search Patient</label>
                    <input id="patient_search" class="form-control" placeholder="Name / UHID / ABHA / Mobile">
                </div>
                <div class="col-md-2">
                    <button id="btnPatientSearch" class="btn btn-outline-secondary w-100">Search</button>
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1">Search Documents</label>
                    <input id="doc_search" class="form-control" placeholder="title / care context / doctor">
                </div>
                <div class="col-md-2">
                    <button id="btnDocSearch" class="btn btn-outline-primary w-100">Load Documents</button>
                </div>
            </div>
            <div class="small text-muted mt-2" id="patient_status">Select a patient to view mapped ABDM documents.</div>

            <div class="table-responsive mt-3">
                <table class="table table-sm table-hover" id="patient_table">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Patient</th>
                            <th>UHID</th>
                            <th>ABHA Address</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header">Document List</div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 560px;">
                        <table class="table table-sm table-striped mb-0" id="doc_table">
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
            <div class="card shadow-sm h-100">
                <div class="card-header">Document Detail</div>
                <div class="card-body" id="doc_detail_box">
                    <div class="text-muted">Select a document to view human-readable summary.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var selectedPatient = null;
    var docs = [];

    var patientLookupUrl = '<?= base_url('AbdmHiu/patient_lookup') ?>';
    var docsListUrl = '<?= base_url('AbdmHiu/documents_list') ?>';
    var docDetailBase = '<?= base_url('AbdmHiu/document_detail') ?>';

    function esc(v) {
        return (v || '').toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function parseDate(v) {
        var t = (v || '').toString().trim();
        if (!t) return '-';
        var d = new Date(t.replace(' ', 'T'));
        if (isNaN(d.getTime())) return t;
        return d.toLocaleString();
    }

    function renderPatientRows(items) {
        var tbody = document.querySelector('#patient_table tbody');
        tbody.innerHTML = '';
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-muted text-center">No patients found.</td></tr>';
            return;
        }

        items.forEach(function (p, idx) {
            var tr = document.createElement('tr');
            tr.innerHTML = '' +
                '<td><button class="btn btn-sm btn-outline-primary" data-p-idx="' + idx + '">Select</button></td>' +
                '<td>' + esc(p.patient_name) + '</td>' +
                '<td>' + esc(p.patient_code) + '</td>' +
                '<td>' + esc(p.abha_address) + '</td>';
            tbody.appendChild(tr);
        });

        tbody.querySelectorAll('button[data-p-idx]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx = parseInt(btn.getAttribute('data-p-idx'), 10);
                if (!Number.isNaN(idx) && items[idx]) {
                    selectedPatient = items[idx];
                    document.getElementById('patient_status').textContent = 'Selected: ' +
                        (selectedPatient.patient_name || '-') + ' | ABHA: ' + (selectedPatient.abha_address || '-');
                    loadDocuments();
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
                    document.getElementById('patient_status').textContent = res.error || 'Search failed';
                    return;
                }
                renderPatientRows(res.items || []);
                document.getElementById('patient_status').textContent = (res.count || 0) + ' patient(s) found';
            })
            .catch(function (e) {
                document.getElementById('patient_status').textContent = e.message || 'Search failed';
            });
    }

    function renderDocs(items) {
        docs = items || [];
        var tbody = document.querySelector('#doc_table tbody');
        tbody.innerHTML = '';
        if (!docs.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-muted text-center">No ABDM documents mapped for selected patient yet.</td></tr>';
            return;
        }

        docs.forEach(function (doc, idx) {
            var tr = document.createElement('tr');
            tr.style.cursor = 'pointer';
            tr.setAttribute('data-doc-idx', idx);
            tr.innerHTML = '' +
                '<td>' + esc(parseDate(doc.document_date || doc.created_at)) + '</td>' +
                '<td>' + esc(doc.document_title || '-') + '</td>' +
                '<td>' + esc(doc.care_context_reference || '-') + '</td>' +
                '<td>' + esc(doc.practitioner_name || '-') + '</td>';
            tbody.appendChild(tr);
        });

        tbody.querySelectorAll('tr[data-doc-idx]').forEach(function (row) {
            row.addEventListener('click', function () {
                var idx = parseInt(row.getAttribute('data-doc-idx'), 10);
                if (!Number.isNaN(idx) && docs[idx]) {
                    loadDocumentDetail(docs[idx].id);
                }
            });
        });

        loadDocumentDetail(docs[0].id);
    }

    function loadDocuments() {
        if (!selectedPatient) {
            return;
        }

        var q = document.getElementById('doc_search').value.trim();
        var url = docsListUrl +
            '?patient_id=' + encodeURIComponent(selectedPatient.patient_id || 0) +
            '&abha_address=' + encodeURIComponent(selectedPatient.abha_address || '') +
            '&q=' + encodeURIComponent(q);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if ((res.ok || 0) !== 1) {
                    return;
                }
                renderDocs(res.items || []);
            });
    }

    function renderList(label, arr, itemFormatter) {
        arr = Array.isArray(arr) ? arr : [];
        if (!arr.length) {
            return '<div class="mb-2"><div class="fw-semibold">' + esc(label) + '</div><div class="text-muted">No data</div></div>';
        }

        var html = '<div class="mb-2"><div class="fw-semibold">' + esc(label) + '</div><ul class="mb-0">';
        arr.forEach(function (item) {
            html += '<li>' + itemFormatter(item) + '</li>';
        });
        html += '</ul></div>';
        return html;
    }

    function loadDocumentDetail(id) {
        var box = document.getElementById('doc_detail_box');
        box.innerHTML = '<div class="text-muted">Loading...</div>';
        fetch(docDetailBase + '/' + encodeURIComponent(id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if ((res.ok || 0) !== 1) {
                    box.innerHTML = '<div class="text-danger">Unable to load detail.</div>';
                    return;
                }

                var item = res.item || {};
                var s = item.summary || {};
                var html = '' +
                    '<div class="row g-2 mb-3">' +
                        '<div class="col-md-6"><div><span class="text-muted">Patient:</span> <strong>' + esc(s.patient_name || item.patient_name || '-') + '</strong></div></div>' +
                        '<div class="col-md-6"><div><span class="text-muted">ABHA:</span> ' + esc(item.abha_address || '-') + '</div></div>' +
                        '<div class="col-md-6"><div><span class="text-muted">Document:</span> ' + esc(item.document_title || '-') + '</div></div>' +
                        '<div class="col-md-6"><div><span class="text-muted">Date:</span> ' + esc(parseDate(item.document_date || '')) + '</div></div>' +
                        '<div class="col-md-6"><div><span class="text-muted">Doctor:</span> ' + esc(item.practitioner_name || '-') + '</div></div>' +
                        '<div class="col-md-6"><div><span class="text-muted">Organization:</span> ' + esc(item.organization_name || '-') + '</div></div>' +
                        '<div class="col-md-6"><div><span class="text-muted">Care Context:</span> ' + esc(item.care_context_reference || '-') + '</div></div>' +
                        '<div class="col-md-6"><div><span class="text-muted">Bundle:</span> ' + esc(item.bundle_type || '-') + '</div></div>' +
                    '</div>';

                html += renderList('Diagnoses', s.conditions || [], function (x) {
                    return esc((x && x.text) || '-');
                });

                html += renderList('Vitals', s.vitals || [], function (x) {
                    return esc(((x && x.name) || '-') + ': ' + ((x && x.value) || '-'));
                });

                html += renderList('Medications', s.medications || [], function (x) {
                    var name = (x && x.name) || '-';
                    var dose = (x && x.dose) || '';
                    return esc(name + (dose ? (' | ' + dose) : ''));
                });

                box.innerHTML = html;
            })
            .catch(function (e) {
                box.innerHTML = '<div class="text-danger">' + esc(e.message || 'Unable to load detail') + '</div>';
            });
    }

    document.getElementById('btnPatientSearch').addEventListener('click', searchPatients);
    document.getElementById('btnDocSearch').addEventListener('click', loadDocuments);
})();
</script>

<div class="pagetitle">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1><i class="bi bi-folder-check text-primary me-2"></i>MRD & IPD Medical Record Documents Workspace</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Home</a></li>
                    <li class="breadcrumb-item active">MRD & IPD Document Scanner</li>
                </ol>
            </nav>
        </div>
        <div>
            <span class="badge bg-success px-3 py-2 fs-6"><i class="bi bi-shield-check me-1"></i>NABH Guidelines Compliant</span>
            <span class="badge bg-primary px-3 py-2 fs-6 ms-1"><i class="bi bi-cloud-arrow-up me-1"></i>ABDM FHIR R4 Ready</span>
        </div>
    </div>
</div>

<section class="section mt-3">
    <!-- MAIN NABH IPD MEDICAL RECORD SCANNER & ABDM HEALTH DOCUMENT PORTAL -->
    <div class="card shadow border-0 mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center py-3" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);">
            <div>
                <h5 class="m-0 text-white fw-bold"><i class="bi bi-scanner me-2"></i>NABH IPD Medical Record Scanner & ABDM Health Document Upload</h5>
                <small class="text-white-50">Type IPD No. to view required paper scan checklist as per NABH guidelines</small>
            </div>
            <span class="badge bg-warning text-dark"><i class="bi bi-star-fill me-1"></i>NABH IPD Checklist</span>
        </div>
        <div class="card-body p-4">
            <!-- IPD Search Box -->
            <div class="row g-3 align-items-end mb-4">
                <div class="col-md-7 col-lg-8">
                    <label class="form-label fw-bold" for="nabh_ipd_search_input">
                        <i class="bi bi-search text-primary me-1"></i>Enter IPD No. / Admission No. / UHID / Patient Name
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-hospital"></i></span>
                        <input type="text" class="form-control form-control-lg" id="nabh_ipd_search_input" placeholder="e.g. IPD-2026-001, 17, 5, DEVENDER..." onkeypress="if(event.key==='Enter'){ searchIpdPatientForNabh(event); return false; }">
                        <button type="button" class="btn btn-lg btn-primary px-4" id="btn_search_ipd_nabh" onclick="searchIpdPatientForNabh(event)">
                            <i class="bi bi-search me-1"></i> Search IPD Admission
                        </button>
                    </div>
                </div>
                <div class="col-md-5 col-lg-4 text-end">
                    <small class="text-muted d-block">Need help locating IPD admission?</small>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="load_form('<?= base_url('DoctorDocument/workspace') ?>')">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh Workspace
                    </button>
                </div>
            </div>

            <!-- Patient Info Banner (Hidden until searched) -->
            <div id="nabh_ipd_patient_card" class="card bg-light border border-info mb-4" style="display: none;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-1 text-primary" id="nabh_ipd_patient_name">DEVENDER SINGH</h5>
                            <div class="d-flex flex-wrap gap-3 text-dark small">
                                <span><strong>IPD No:</strong> <span class="badge bg-primary fs-6" id="nabh_ipd_no">IPD-17</span></span>
                                <span><strong>UHID:</strong> <span class="badge bg-secondary fs-6" id="nabh_ipd_uhid">11</span></span>
                                <span><strong>Gender/Age:</strong> <span id="nabh_ipd_gender_age">Male / 47</span></span>
                                <span><strong>Admission Date:</strong> <span id="nabh_ipd_admission_date">30-07-2026</span></span>
                                <span><strong>Attending Doctor:</strong> <span id="nabh_ipd_doctor">Dr. Nidhi Pandey</span></span>
                            </div>
                        </div>
                        <div>
                            <span class="badge bg-info text-dark p-2 me-1" id="nabh_ipd_abha_badge"><i class="bi bi-qr-code me-1"></i>ABHA Address</span>
                            <small class="d-block text-muted text-end fw-bold" id="nabh_ipd_abha_address">singhdevender0328@sbx</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- NABH Guideline Required Documents Grid (Hidden until patient selected) -->
            <div id="nabh_ipd_checklist_section" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark m-0"><i class="bi bi-check2-square text-success me-2"></i>NABH Guideline Mandatory IPD Paper Documents Checklist</h6>
                    <small class="text-muted">Upload scanned paper records (JPG, PNG, PDF) & push as FHIR HealthDocumentRecord to ABDM</small>
                </div>

                <div class="row g-3" id="nabh_doc_categories_container">
                    <!-- Populated dynamically via JavaScript -->
                </div>
            </div>

            <!-- Initial Placeholder -->
            <div id="nabh_ipd_placeholder" class="text-center py-5 border rounded bg-light">
                <i class="bi bi-folder-symlink display-4 text-muted"></i>
                <h5 class="mt-3 text-muted">Search an IPD Admission to View NABH Document Upload List</h5>
                <p class="text-muted small">Enter an IPD No. (e.g. 17 or 5) above to load the NABH mandatory scanned document checklist.</p>
            </div>
        </div>
    </div>
</section>

<script>
var _activeIpdRecord = null;

var _nabhCategories = [
    { code: 'NABH-IPD-01', title: 'Admission Request & Initial Assessment Form', icon: 'bi-file-earmark-text', desc: 'Patient admission order, initial clinical assessment, emergency notes & vital baseline.' },
    { code: 'NABH-IPD-02', title: 'Signed Informed Consent Forms', icon: 'bi-pen-fill', desc: 'Signed consent forms for general admission, surgery, anesthesia, high risk procedures & blood transfusion.' },
    { code: 'NABH-IPD-03', title: 'Doctor Clinical Notes & Daily Orders', icon: 'bi-journal-medical', desc: 'Daily clinical progress notes, doctor rounds, medication orders & treatment plans.' },
    { code: 'NABH-IPD-04', title: 'Nursing Flowsheet & Vitals Chart', icon: 'bi-activity', desc: '24-hour nursing assessment, vital signs monitoring chart, fluid intake/output & drug administration record.' },
    { code: 'NABH-IPD-05', title: 'OT Surgery Notes & Anesthesia Record', icon: 'bi-scissors', desc: 'Pre-operative evaluation, intra-operative surgical notes, anesthesia record & post-op recovery note.' },
    { code: 'NABH-IPD-06', title: 'Laboratory & Radiology Diagnostic Reports', icon: 'bi-file-earmark-pdf', desc: 'Scanned paper lab investigation reports, ECG charts, X-ray & CT/MRI imaging reports.' },
    { code: 'NABH-IPD-07', title: 'Discharge Summary & Advice Note', icon: 'bi-file-earmark-check', desc: 'NABH discharge summary, discharge instructions, prescription & follow-up advice.' },
    { code: 'NABH-IPD-08', title: 'Billing Summary & TPA/Insurance Claim Form', icon: 'bi-credit-card-2-front', desc: 'IPD final bill summary, government scheme approval, insurance claim forms & payment receipts.' },
    { code: 'NABH-IPD-09', title: 'Other Scanned Paper Medical Records', icon: 'bi-folder-plus', desc: 'Any other paper medical records, external referral slips, or patient consent attachments.' }
];

function searchIpdPatientForNabh(ev) {
    if (ev && ev.preventDefault) ev.preventDefault();
    if (ev && ev.stopPropagation) ev.stopPropagation();

    var query = (document.getElementById('nabh_ipd_search_input').value || '').trim();
    if (!query) {
        alert('Please enter an IPD No., UHID, or Patient Name.');
        return false;
    }

    var btn = document.getElementById('btn_search_ipd_nabh');
    var origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Searching...';

    var searchUrl = '<?= base_url('DoctorDocument/search_ipd_patient') ?>?ipd_key=' + encodeURIComponent(query);
    fetch(searchUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = origHtml;

            if (data.status === 'success' && Array.isArray(data.records) && data.records.length > 0) {
                _activeIpdRecord = data.records[0];
                renderActiveIpdRecord(_activeIpdRecord);
            } else {
                alert((data && data.message) ? data.message : 'No IPD admission found matching "' + query + '"');
            }
        })
        .catch(function(e) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
            alert('Unable to search IPD patient: ' + e.message);
        });

    return false;
}

function renderActiveIpdRecord(rec) {
    document.getElementById('nabh_ipd_placeholder').style.display = 'none';
    document.getElementById('nabh_ipd_patient_card').style.display = 'block';
    document.getElementById('nabh_ipd_checklist_section').style.display = 'block';

    document.getElementById('nabh_ipd_patient_name').textContent = rec.patient_name || 'Patient';
    document.getElementById('nabh_ipd_no').textContent = rec.ipd_no || ('IPD-' + rec.ipd_id);
    document.getElementById('nabh_ipd_uhid').textContent = rec.uhid || rec.patient_id;
    document.getElementById('nabh_ipd_gender_age').textContent = (rec.gender || 'N/A') + ' / ' + (rec.age || 'N/A');
    document.getElementById('nabh_ipd_admission_date').textContent = rec.admission_date || 'N/A';
    document.getElementById('nabh_ipd_doctor').textContent = rec.doctor_name || 'Attending Doctor';
    document.getElementById('nabh_ipd_abha_address').textContent = rec.abha_address || rec.abha_id || 'Not linked';

    var container = document.getElementById('nabh_doc_categories_container');
    container.innerHTML = '';

    _nabhCategories.forEach(function(cat) {
        var existing = (rec.scans || {})[cat.code] || null;
        var col = document.createElement('div');
        col.className = 'col-md-6 col-lg-4';

        var statusBadge = '<span class="badge bg-secondary mb-2" id="badge_status_' + cat.code + '"><i class="bi bi-clock me-1"></i>Pending Scan</span>';
        if (existing) {
            if (existing.abdm_status === 'pushed') {
                statusBadge = '<span class="badge bg-success mb-2" id="badge_status_' + cat.code + '"><i class="bi bi-check-circle-fill me-1"></i>Pushed to ABDM (201)</span>';
            } else {
                statusBadge = '<span class="badge bg-info text-dark mb-2" id="badge_status_' + cat.code + '"><i class="bi bi-file-earmark-check me-1"></i>Scanned & Saved</span>';
            }
        }

        var fileInfo = existing ? '<div class="small text-truncate text-muted mt-1" id="file_info_' + cat.code + '"><i class="bi bi-paperclip me-1"></i><a href="' + existing.file_url + '" target="_blank" class="fw-bold text-decoration-none">' + hesc(existing.file_name) + '</a></div>' : '<div class="small text-muted mt-1" id="file_info_' + cat.code + '">No scan uploaded yet.</div>';

        var pushBtnDisabled = existing ? '' : 'disabled';
        var pushBtnClass = (existing && existing.abdm_status === 'pushed') ? 'btn-outline-success' : 'btn-primary';
        var pushBtnText = (existing && existing.abdm_status === 'pushed') ? '<i class="bi bi-check2-circle"></i> Pushed to ABDM' : '<i class="bi bi-cloud-arrow-up"></i> Push to ABDM';

        col.innerHTML = `
            <div class="card h-100 shadow-sm border border-light-subtle">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start gap-1">
                            <h6 class="fw-bold text-dark mb-1">
                                <i class="bi ${cat.icon} text-primary me-2"></i>${hesc(cat.title)}
                            </h6>
                        </div>
                        ${statusBadge}
                        <p class="text-muted small mb-2" style="font-size:0.8rem; line-height:1.2;">${hesc(cat.desc)}</p>
                        ${fileInfo}
                    </div>
                    <div class="mt-3 pt-2 border-top">
                        <div class="mb-2">
                            <input type="file" class="form-control form-control-sm" id="file_input_${cat.code}" accept=".jpg,.jpeg,.png,.webp,.pdf">
                        </div>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary flex-grow-1" onclick="uploadNabhScan(event, '${cat.code}', '${hesc(cat.title)}')">
                                <i class="bi bi-upload"></i> Upload
                            </button>
                            <button type="button" class="btn btn-sm ${pushBtnClass} flex-grow-1" id="btn_push_${cat.code}" ${pushBtnDisabled} onclick="pushNabhToAbdm(event, '${cat.code}', ${existing ? existing.patient_doc_id : 0})">
                                ${pushBtnText}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(col);
    });
}

function uploadNabhScan(ev, catCode, catTitle) {
    if (ev && ev.preventDefault) ev.preventDefault();
    if (ev && ev.stopPropagation) ev.stopPropagation();

    if (!_activeIpdRecord) {
        alert('Please search and select an IPD admission first.');
        return false;
    }

    var fileInput = document.getElementById('file_input_' + catCode);
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        alert('Please choose a scanned paper file (JPG, PNG, PDF) to upload for ' + catTitle);
        return false;
    }

    var btn = ev ? (ev.currentTarget || ev.target) : null;
    var origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    }

    var formData = new FormData();
    formData.append('ipd_id', _activeIpdRecord.ipd_id);
    formData.append('patient_id', _activeIpdRecord.patient_id);
    formData.append('doctor_id', _activeIpdRecord.doctor_id || 1);
    formData.append('nabh_category', catCode);
    formData.append('category_title', catTitle);
    formData.append('scanned_file', fileInput.files[0]);

    var uploadUrl = '<?= base_url('DoctorDocument/upload_ipd_nabh_document') ?>';
    fetch(uploadUrl, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }

        if (res.status === 'success') {
            alert('✅ ' + res.message);
            var badge = document.getElementById('badge_status_' + catCode);
            if (badge) {
                badge.className = 'badge bg-info text-dark mb-2';
                badge.innerHTML = '<i class="bi bi-file-earmark-check me-1"></i>Scanned & Saved';
            }
            var info = document.getElementById('file_info_' + catCode);
            if (info) {
                info.innerHTML = '<i class="bi bi-paperclip me-1"></i><a href="' + res.file_url + '" target="_blank" class="fw-bold text-decoration-none">' + hesc(res.file_name) + '</a>';
            }
            var pushBtn = document.getElementById('btn_push_' + catCode);
            if (pushBtn) {
                pushBtn.disabled = false;
                pushBtn.setAttribute('onclick', "pushNabhToAbdm(event, '" + catCode + "', " + res.patient_doc_id + ")");
            }
        } else {
            alert('❌ Upload failed: ' + (res.message || 'Unknown error'));
        }
    })
    .catch(function(e) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
        alert('❌ Error uploading scan: ' + e.message);
    });

    return false;
}

function pushNabhToAbdm(ev, catCode, patientDocId) {
    if (ev && ev.preventDefault) ev.preventDefault();
    if (ev && ev.stopPropagation) ev.stopPropagation();

    if (patientDocId <= 0) {
        alert('Upload a scanned file first before pushing to ABDM.');
        return false;
    }

    var btn = document.getElementById('btn_push_' + catCode);
    var origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Pushing...';
    }

    var formData = new URLSearchParams();
    formData.append('patient_doc_id', patientDocId);
    formData.append('ipd_id', _activeIpdRecord ? _activeIpdRecord.ipd_id : 0);
    formData.append('nabh_category', catCode);

    fetch('<?= base_url('DoctorDocument/push_ipd_nabh_to_abdm') ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }

        if (res.ok === 1 || res.status === 'queued') {
            alert('🚀 Successfully pushed HealthDocumentRecord to ABDM Gateway Bridge!\nCare Context: ' + (res.care_context_reference || 'DOC-' + patientDocId));
            var badge = document.getElementById('badge_status_' + catCode);
            if (badge) {
                badge.className = 'badge bg-success mb-2';
                badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Pushed to ABDM (201)';
            }
            if (btn) {
                btn.className = 'btn btn-sm btn-outline-success flex-grow-1';
                btn.innerHTML = '<i class="bi bi-check2-circle"></i> Pushed to ABDM';
            }
        } else {
            alert('❌ ABDM Push Failed: ' + (res.error || res.error_text || 'Bridge rejected request'));
        }
    })
    .catch(function(e) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
        alert('❌ Error pushing to ABDM: ' + e.message);
    });

    return false;
}

function hesc(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>

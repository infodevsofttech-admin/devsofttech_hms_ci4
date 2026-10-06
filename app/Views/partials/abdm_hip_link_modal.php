<!-- ABDM M2: HIP-Initiated Linking Modal (Method 4 Demographic Auth & Deep-Link SMS) -->
<div class="modal fade" id="abdmHipLinkModal" tabindex="-1" aria-labelledby="abdmHipLinkModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="abdmHipLinkModalTitle">
                    <i class="bi bi-link-45deg me-2 fs-5"></i>Link Hospital Records to ABHA (HIP)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Tab Navigation -->
            <ul class="nav nav-tabs px-3 pt-2 bg-light border-bottom" id="abdmHipModalTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold" id="tab-hip-link" data-bs-toggle="tab" data-bs-target="#pane-hip-link" type="button" role="tab" aria-selected="true">
                        <i class="bi bi-person-check me-1 text-primary"></i> Demographic Auth (Method 4)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold" id="tab-hip-sms" data-bs-toggle="tab" data-bs-target="#pane-hip-sms" type="button" role="tab" aria-selected="false">
                        <i class="bi bi-chat-dots me-1 text-info"></i> Deep Link SMS (sms/notify2)
                    </button>
                </li>
            </ul>

            <div class="modal-body p-4">
                <div class="tab-content" id="abdmHipModalTabContent">
                    
                    <!-- ========================================================================= -->
                    <!-- TAB 1: Method 4 Demographic Auth Care Context Linking -->
                    <!-- ========================================================================= -->
                    <div class="tab-pane fade show active" id="pane-hip-link" role="tabpanel">
                        <div class="alert alert-light border border-info-subtle d-flex align-items-center mb-3 py-2 px-3 small">
                            <i class="bi bi-info-circle-fill text-primary fs-5 me-2 flex-shrink-0"></i>
                            <div>
                                <strong>ABDM V3 HIECM Standard (Method 4):</strong> Hospital submits patient demographics to gateway. On successful verification, ABDM generates a JWT Link Token used to link hospital care contexts directly.
                            </div>
                        </div>

                        <!-- Patient Demographics Form -->
                        <div class="card mb-3 border-light-subtle bg-light-subtle">
                            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                                <span class="fw-bold small text-uppercase text-secondary">
                                    <i class="bi bi-person-badge me-1"></i> Patient Demographics (Verified for Token)
                                </span>
                                <span id="hipPatientUhidBadge" class="badge bg-secondary">UHID: -</span>
                            </div>
                            <div class="card-body py-3">
                                <div class="row g-2 mb-2">
                                    <div class="col-md-7">
                                        <label class="form-label small fw-semibold" for="hipAbhaAddress">ABHA Address (Health ID) <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text"><i class="bi bi-at"></i></span>
                                            <input type="text" id="hipAbhaAddress" class="form-control" placeholder="username@sbx" autocomplete="off">
                                            <button class="btn btn-outline-secondary" type="button" id="btnReloadCareContexts" title="Reload patient records from HMS & Bridge">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-semibold" for="hipAbhaNumber">14-Digit ABHA Number</label>
                                        <input type="text" id="hipAbhaNumber" class="form-control form-control-sm" placeholder="14-digit number (auto-resolved)" maxlength="17">
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold" for="hipPatientName">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" id="hipPatientName" class="form-control form-control-sm" placeholder="Patient full name">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold" for="hipGender">Gender <span class="text-danger">*</span></label>
                                        <select id="hipGender" class="form-select form-select-sm">
                                            <option value="M">Male (M)</option>
                                            <option value="F">Female (F)</option>
                                            <option value="O">Other (O)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold" for="hipYob">Birth Year <span class="text-danger">*</span></label>
                                        <input type="number" id="hipYob" class="form-control form-control-sm" placeholder="YYYY" min="1900" max="2026">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Care Contexts Selection -->
                        <div class="card mb-3 border-light-subtle">
                            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center flex-wrap gap-1">
                                <span class="fw-bold small text-uppercase text-secondary">
                                    <i class="bi bi-journal-medical me-1"></i> Hospital Care Contexts
                                </span>
                                <div class="d-flex align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small" id="btnSelectAllCareContexts">Select All</button>
                                    <span class="text-muted">|</span>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small text-secondary" id="btnDeselectAllCareContexts">Deselect All</button>
                                    <span class="text-muted">|</span>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none small text-primary" id="btnToggleCustomContext">
                                        <i class="bi bi-plus-circle me-1"></i>Custom
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-2" style="max-height: 240px; overflow-y: auto;">
                                <!-- Loading Spinner -->
                                <div id="hipCareContextsLoading" class="text-center py-3 d-none">
                                    <div class="spinner-border spinner-border-sm text-primary me-1" role="status"></div>
                                    <span class="small text-muted">Loading patient care contexts...</span>
                                </div>

                                <!-- Dynamic Checkboxes Container -->
                                <div id="hipCareContextsContainer" class="d-flex flex-column gap-2">
                                    <div class="text-muted small p-2 text-center" id="hipCareContextsEmpty">
                                        No care contexts loaded. Enter an ABHA address or patient ID above to load visits.
                                    </div>
                                </div>

                                <!-- Custom Care Context Input Form (collapsible) -->
                                <div id="hipCustomContextBox" class="border rounded p-2 mt-2 bg-light d-none">
                                    <div class="small fw-bold mb-1 text-primary"><i class="bi bi-plus-square me-1"></i>Add Custom Care Context</div>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <input type="text" id="hipCustomRef" class="form-control form-control-sm" placeholder="Reference (e.g. OPD-1024)">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" id="hipCustomDisplay" class="form-control form-control-sm" placeholder="Display name">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-sm btn-primary w-100" id="btnAddCustomContext">Add</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Alert Box -->
                        <div id="hipLinkStatusAlert" class="alert alert-info py-2 px-3 small d-none" role="alert"></div>
                    </div>

                    <!-- ========================================================================= -->
                    <!-- TAB 2: Deep Link SMS Notification (sms/notify2) -->
                    <!-- ========================================================================= -->
                    <div class="tab-pane fade" id="pane-hip-sms" role="tabpanel">
                        <div class="alert alert-light border border-info-subtle d-flex align-items-center mb-3 py-2 px-3 small">
                            <i class="bi bi-phone-vibrate text-info fs-5 me-2 flex-shrink-0"></i>
                            <div>
                                <strong>NHA M2 Test Requirement (Row 56 / sms/notify2):</strong> When a patient registers with a phone number only and does not have an ABHA address, the hospital notifies HIE-CM. The patient receives an official SMS with deep links to download the PHR app and discover their hospital records.
                            </div>
                        </div>

                        <div class="card border-light-subtle bg-light-subtle p-3 mb-3">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold" for="hipSmsPhone">Patient Mobile Number <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i> +91</span>
                                    <input type="tel" id="hipSmsPhone" class="form-control" placeholder="10-digit mobile number" maxlength="10">
                                </div>
                                <div class="form-text">Must be a valid 10-digit Indian mobile number.</div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-semibold" for="hipSmsHipName">Hospital / Facility Display Name</label>
                                <input type="text" id="hipSmsHipName" class="form-control form-control-sm" placeholder="Hospital Name">
                                <div class="form-text">Included in the SMS message sent to the patient.</div>
                            </div>
                        </div>

                        <!-- SMS Status Alert Box -->
                        <div id="hipSmsStatusAlert" class="alert alert-info py-2 px-3 small d-none" role="alert"></div>
                    </div>

                </div>
            </div>

            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCheckPatientLinks" title="Query Bridge & ABDM registry for all care contexts already linked to this ABHA">
                    <i class="bi bi-search me-1"></i>View Linked Records
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-sm btn-success fw-semibold" id="btnSubmitHipLink" onclick="executeHipInitiatedLinking()">
                        <i class="bi bi-shield-lock-fill me-1"></i>Verify & Link Records
                    </button>
                    <button type="button" class="btn btn-sm btn-info text-white fw-semibold d-none" id="btnSubmitHipSms" onclick="executeHipSmsNotify()">
                        <i class="bi bi-send-fill me-1"></i>Send ABDM Deep-Link SMS
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // CSRF helper
    function getCsrfData() {
        var tokenInput = document.querySelector('input[name="csrf_token_name"]')
            || document.querySelector('input[name="csrf_hms"]')
            || document.querySelector('input[name="<?= csrf_token() ?>"]');
        var name = tokenInput ? tokenInput.name : '<?= csrf_token() ?>';
        var hash = tokenInput ? tokenInput.value : '<?= csrf_hash() ?>';
        if (typeof window.csrfHash !== 'undefined' && window.csrfHash) hash = window.csrfHash;
        if (typeof window.csrfName !== 'undefined' && window.csrfName) name = window.csrfName;
        return { name: name, hash: hash };
    }

    function updateCsrf(res) {
        if (res && res.csrfHash) {
            window.csrfHash = res.csrfHash;
            if (res.csrfName) window.csrfName = res.csrfName;
            var inputs = document.querySelectorAll('input[name="' + (res.csrfName || 'csrf_hms') + '"]');
            inputs.forEach(function(i) { i.value = res.csrfHash; });
        }
    }

    // Switch buttons based on active tab
    var modalTabs = document.getElementById('abdmHipModalTabs');
    if (modalTabs) {
        modalTabs.addEventListener('shown.bs.tab', function(e) {
            var btnLink = document.getElementById('btnSubmitHipLink');
            var btnSms = document.getElementById('btnSubmitHipSms');
            if (e.target.id === 'tab-hip-sms') {
                if (btnLink) btnLink.classList.add('d-none');
                if (btnSms) btnSms.classList.remove('d-none');
            } else {
                if (btnLink) btnLink.classList.remove('d-none');
                if (btnSms) btnSms.classList.add('d-none');
            }
        });
    }

    // Toggle Custom Context Input
    var btnToggleCustom = document.getElementById('btnToggleCustomContext');
    var boxCustom = document.getElementById('hipCustomContextBox');
    if (btnToggleCustom && boxCustom) {
        btnToggleCustom.addEventListener('click', function() {
            boxCustom.classList.toggle('d-none');
        });
    }

    // Add Custom Context to Container
    var btnAddCustom = document.getElementById('btnAddCustomContext');
    if (btnAddCustom) {
        btnAddCustom.addEventListener('click', function() {
            var ref = (document.getElementById('hipCustomRef').value || '').trim();
            var disp = (document.getElementById('hipCustomDisplay').value || '').trim();
            if (!ref) {
                alert('Please enter a care context reference (e.g. OPD-1024).');
                return;
            }
            if (!disp) disp = ref;
            appendCareContextCheckbox({
                ref: ref,
                display: disp,
                hi_type: 'OPConsultRecord',
                is_linked: false
            }, true);
            document.getElementById('hipCustomRef').value = '';
            document.getElementById('hipCustomDisplay').value = '';
            boxCustom.classList.add('d-none');
        });
    }

    // Select / Deselect All
    var btnSelectAll = document.getElementById('btnSelectAllCareContexts');
    var btnDeselectAll = document.getElementById('btnDeselectAllCareContexts');
    if (btnSelectAll) {
        btnSelectAll.addEventListener('click', function() {
            document.querySelectorAll('.care-context-cb:not(:disabled)').forEach(function(cb) { cb.checked = true; });
        });
    }
    if (btnDeselectAll) {
        btnDeselectAll.addEventListener('click', function() {
            document.querySelectorAll('.care-context-cb:not(:disabled)').forEach(function(cb) { cb.checked = false; });
        });
    }

    // Reload Care Contexts on button click or blur
    var btnReload = document.getElementById('btnReloadCareContexts');
    if (btnReload) {
        btnReload.addEventListener('click', function() {
            var abha = (document.getElementById('hipAbhaAddress').value || '').trim();
            var pId = (window.currentHipTaskContext && window.currentHipTaskContext.patientId) ? window.currentHipTaskContext.patientId : 0;
            if (abha || pId > 0) {
                loadPatientCareContexts(pId, abha);
            } else {
                alert('Please enter an ABHA Address first.');
            }
        });
    }

    // View Linked Records button
    var btnCheckLinks = document.getElementById('btnCheckPatientLinks');
    if (btnCheckLinks) {
        btnCheckLinks.addEventListener('click', function() {
            window.checkAbdmLinkedRecords();
        });
    }

    function appendCareContextCheckbox(item, isChecked) {
        var container = document.getElementById('hipCareContextsContainer');
        var emptyMsg = document.getElementById('hipCareContextsEmpty');
        if (emptyMsg) emptyMsg.remove();

        var ref = item.ref || '';
        var display = item.display || ref;
        var hiType = item.hi_type || 'Record';
        var isLinked = !!item.is_linked;
        var isFhirReady = (typeof item.is_fhir_ready !== 'undefined') ? !!item.is_fhir_ready : true;
        var isPrimary = !!item.is_primary;

        // Check if already in list
        var existing = container.querySelector('input[value="' + ref.replace(/"/g, '\\"') + '"]');
        if (existing) {
            if (isPrimary && !existing.disabled) {
                existing.checked = true;
            }
            return;
        }

        var wrapper = document.createElement('div');
        var baseClass = 'border rounded p-2 d-flex align-items-center justify-content-between ';
        if (isPrimary) {
            wrapper.className = baseClass + 'bg-primary-subtle border-primary shadow-sm';
        } else if (!isFhirReady) {
            wrapper.className = baseClass + 'bg-light border-warning-subtle text-muted';
        } else {
            wrapper.className = baseClass + 'bg-white';
        }
        
        var left = document.createElement('div');
        left.className = 'form-check mb-0';

        var cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.className = 'form-check-input care-context-cb';
        cb.id = 'cc_' + Math.random().toString(36).substring(2, 9);
        cb.value = ref;
        cb.dataset.hiType = hiType;
        cb.dataset.isPrimary = isPrimary ? '1' : '0';
        cb.dataset.isFhirReady = isFhirReady ? '1' : '0';
        cb.dataset.isLinked = isLinked ? '1' : '0';

        if (isLinked) {
            cb.disabled = true;
            cb.checked = false;
        } else if (!isFhirReady) {
            cb.disabled = true;
            cb.checked = false;
            cb.title = 'Incomplete findings - finalize report before linking';
        } else if (typeof isChecked === 'boolean') {
            cb.checked = isChecked;
        } else if (isPrimary) {
            cb.checked = true;
        } else {
            // If opened for a specific task, only check the primary task context by default
            var hasSpecificTask = !!(window.currentHipTaskContext && window.currentHipTaskContext.taskId);
            cb.checked = !hasSpecificTask;
        }

        var label = document.createElement('label');
        label.className = 'form-check-label ms-2 small fw-semibold' + (!isFhirReady ? ' text-secondary' : '');
        label.htmlFor = cb.id;
        label.textContent = display;

        left.appendChild(cb);
        left.appendChild(label);

        var badgeSpan = document.createElement('div');
        badgeSpan.className = 'd-flex align-items-center gap-1 flex-wrap justify-content-end';

        if (isPrimary) {
            var primaryBadge = document.createElement('span');
            primaryBadge.className = 'badge bg-primary text-white small';
            primaryBadge.innerHTML = '<i class="bi bi-star-fill me-1"></i>Current Task';
            badgeSpan.appendChild(primaryBadge);
        }

        var typeBadge = document.createElement('span');
        typeBadge.className = 'badge bg-secondary-subtle text-secondary border small';
        typeBadge.textContent = hiType;
        badgeSpan.appendChild(typeBadge);

        if (isLinked) {
            var linkedBadge = document.createElement('span');
            linkedBadge.className = 'badge bg-success-subtle text-success border border-success small cc-badge-linked';
            linkedBadge.innerHTML = '<i class="bi bi-check-circle me-1"></i>Linked';
            badgeSpan.appendChild(linkedBadge);
        } else if (!isFhirReady) {
            var draftBadge = document.createElement('span');
            draftBadge.className = 'badge bg-warning-subtle text-dark border border-warning small cc-badge-draft';
            draftBadge.innerHTML = '<i class="bi bi-clock me-1"></i>Draft / Incomplete';
            draftBadge.title = 'Clinical findings must be documented before linking to ABDM';
            badgeSpan.appendChild(draftBadge);
        } else {
            var readyBadge = document.createElement('span');
            readyBadge.className = 'badge bg-success-subtle text-success border border-success-subtle small cc-badge-ready';
            readyBadge.innerHTML = '<i class="bi bi-check2-circle me-1"></i>FHIR Ready';
            badgeSpan.appendChild(readyBadge);
        }

        wrapper.appendChild(left);
        wrapper.appendChild(badgeSpan);
        container.appendChild(wrapper);
    }

    /**
     * Load patient demographics and care contexts from server.
     */
    window.loadPatientCareContexts = function(patientId, abhaAddress, taskType, entityId, taskId, source) {
        var loading = document.getElementById('hipCareContextsLoading');
        var container = document.getElementById('hipCareContextsContainer');
        if (loading) loading.classList.remove('d-none');
        if (container) container.innerHTML = '';

        taskType = taskType || (window.currentHipTaskContext ? window.currentHipTaskContext.taskType : '');
        entityId = entityId || (window.currentHipTaskContext ? window.currentHipTaskContext.entityId : '');
        taskId = taskId || (window.currentHipTaskContext ? window.currentHipTaskContext.taskId : 0);
        source = source || (window.currentHipTaskContext ? window.currentHipTaskContext.source : '');

        var csrf = getCsrfData();
        var url = '<?= base_url('AbdmGateway/hip_patient_care_contexts') ?>'
            + '?patient_id=' + encodeURIComponent(patientId || 0)
            + '&abha_address=' + encodeURIComponent(abhaAddress || '')
            + '&task_type=' + encodeURIComponent(taskType || '')
            + '&entity_id=' + encodeURIComponent(entityId || '')
            + '&task_id=' + encodeURIComponent(taskId || 0)
            + '&source=' + encodeURIComponent(source || '');

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (loading) loading.classList.add('d-none');
            updateCsrf(data);

            if (!data.ok) {
                var errTxt = data.error_text || data.error || (data.message || 'Failed to load patient records');
                container.innerHTML = '<div class="alert alert-warning py-2 px-3 small mb-0"><i class="bi bi-exclamation-triangle me-1"></i>' + errTxt + '</div>';
                return;
            }

            if (data.patient) {
                var p = data.patient;
                if (p.abha_address) document.getElementById('hipAbhaAddress').value = p.abha_address;
                var pAbhaNum = (p.abha_number || '').trim();
                if (pAbhaNum.indexOf('@') !== -1 || pAbhaNum.replace(/\D/g, '').length !== 14) {
                    pAbhaNum = '';
                }
                if (pAbhaNum) document.getElementById('hipAbhaNumber').value = pAbhaNum;
                if (p.name) document.getElementById('hipPatientName').value = p.name;
                if (p.gender) document.getElementById('hipGender').value = p.gender;
                if (p.year_of_birth) document.getElementById('hipYob').value = p.year_of_birth;
                if (p.phone) document.getElementById('hipSmsPhone').value = p.phone;
                if (p.patient_ref) {
                    var uhidBadge = document.getElementById('hipPatientUhidBadge');
                    if (uhidBadge) uhidBadge.textContent = 'UHID: ' + p.patient_ref;
                }
            }
            if (data.hospital_name) {
                document.getElementById('hipSmsHipName').value = data.hospital_name;
            }

            var contexts = data.care_contexts || [];
            var currentTaskAlreadyLinked = false;
            var currentTaskLinkedRef = '';
            if (contexts.length > 0) {
                contexts.forEach(function(item) {
                    appendCareContextCheckbox(item);
                    if (item.is_primary && item.is_linked) {
                        currentTaskAlreadyLinked = true;
                        currentTaskLinkedRef = item.ref;
                    }
                });
            } else {
                container.innerHTML = '<div class="text-muted small p-3 text-center" id="hipCareContextsEmpty">No clinical visit records found for this patient. Click <b>Custom</b> to add a test context.</div>';
            }

            if (currentTaskAlreadyLinked && window.currentHipTaskContext && window.currentHipTaskContext.taskId > 0) {
                var tId = window.currentHipTaskContext.taskId;
                var alertBox = document.getElementById('hipLinkStatusAlert');
                if (alertBox) {
                    alertBox.className = 'alert alert-info py-2 px-3 small';
                    alertBox.innerHTML = '<strong><i class="bi bi-info-circle-fill me-1"></i>Already Linked:</strong> The record for this task (<code>' + currentTaskLinkedRef + '</code>) is already linked to patient\'s ABHA in ABDM.';
                    alertBox.classList.remove('d-none');
                }
                try {
                    var csrf = getCsrfData();
                    var markFormData = new URLSearchParams();
                    markFormData.append('task_id', tId);
                    markFormData.append('status', 'completed');
                    markFormData.append('note', 'Care context already linked in ABDM: ' + currentTaskLinkedRef);
                    markFormData.append(csrf.name || 'csrf_hms', csrf.hash);

                    fetch('<?= base_url('AbdmTaskBoard/mark_status') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: markFormData.toString()
                    }).then(function(mRes) { return mRes.json(); }).then(function(mResult) {
                        updateCsrf(mResult);
                        if (window.currentHipTaskContext && typeof window.currentHipTaskContext.onLinked === 'function') {
                            window.currentHipTaskContext.onLinked({
                                task_id: tId,
                                care_contexts: [{ ref: currentTaskLinkedRef }]
                            });
                        }
                    }).catch(function() {});
                } catch (e) {}
            }
        })
        .catch(function(err) {
            if (loading) loading.classList.add('d-none');
            container.innerHTML = '<div class="text-danger small p-2 text-center">Failed to load care contexts: ' + err.message + '</div>';
        });
    };

    /**
     * Public helper to open Link Modal with patient prefill.
     */
    window.openAbdmHipLinkModal = function(patientId, abhaAddress, prefill) {
        prefill = prefill || {};
        window.currentHipTaskContext = {
            patientId: patientId,
            abhaAddress: abhaAddress,
            taskId: prefill.task_id || 0,
            taskType: prefill.task_type || '',
            entityId: prefill.entity_id || '',
            source: prefill.source || '',
            careContext: prefill.care_context || '',
            onLinked: prefill.onLinked || null
        };

        var modalEl = document.getElementById('abdmHipLinkModal');
        if (!modalEl) return;
        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);

        // Reset fields
        var rawAbhaInput = (abhaAddress || prefill.abha_address || prefill.abha_id || '').trim();
        var isAbhaAddress = rawAbhaInput.indexOf('@') !== -1;
        var clean14Num = rawAbhaInput.replace(/\D/g, '');
        var is14Num = !isAbhaAddress && clean14Num.length === 14;

        document.getElementById('hipAbhaAddress').value = isAbhaAddress ? rawAbhaInput : (prefill.abha_address || '');
        document.getElementById('hipAbhaNumber').value = is14Num ? clean14Num : ((prefill.abha_number || '').indexOf('@') === -1 ? (prefill.abha_number || '') : '');
        document.getElementById('hipPatientName').value = prefill.name || prefill.patient_name || '';
        document.getElementById('hipGender').value = prefill.gender || 'M';
        document.getElementById('hipYob').value = prefill.year_of_birth || prefill.yob || '';
        document.getElementById('hipSmsPhone').value = prefill.phone || prefill.mphone1 || '';
        
        var alertBox = document.getElementById('hipLinkStatusAlert');
        if (alertBox) { alertBox.classList.add('d-none'); alertBox.innerHTML = ''; }
        var smsAlert = document.getElementById('hipSmsStatusAlert');
        if (smsAlert) { smsAlert.classList.add('d-none'); smsAlert.innerHTML = ''; }

        // Switch to Tab 1
        var linkTabTrigger = document.getElementById('tab-hip-link');
        if (linkTabTrigger) {
            var tab = new bootstrap.Tab(linkTabTrigger);
            tab.show();
        }

        modal.show();

        // Load visits
        if (patientId > 0 || (abhaAddress && abhaAddress.length > 3)) {
            loadPatientCareContexts(patientId, abhaAddress, prefill.task_type, prefill.entity_id, prefill.task_id, prefill.source);
        } else {
            var container = document.getElementById('hipCareContextsContainer');
            if (container) {
                container.innerHTML = '<div class="text-muted small p-3 text-center" id="hipCareContextsEmpty">Please enter ABHA address above or select a patient to view visits.</div>';
            }
        }
    };

    /**
     * Public helper to open modal directly on Tab 2 (Deep Link SMS).
     */
    window.openAbdmHipSmsModal = function(phone, hipName) {
        var modalEl = document.getElementById('abdmHipLinkModal');
        if (!modalEl) return;
        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);

        if (phone) document.getElementById('hipSmsPhone').value = phone;
        if (hipName) document.getElementById('hipSmsHipName').value = hipName;

        var smsTabTrigger = document.getElementById('tab-hip-sms');
        if (smsTabTrigger) {
            var tab = new bootstrap.Tab(smsTabTrigger);
            tab.show();
        }

        modal.show();
    };

    function syncAndPushTaskFhirRecord(selectedContexts, alertBox, abhaAddress) {
        if (!window.currentHipTaskContext || !window.currentHipTaskContext.taskType || !window.currentHipTaskContext.entityId) {
            return;
        }
        var ctxTaskType = window.currentHipTaskContext.taskType;
        var ctxEntityId = window.currentHipTaskContext.entityId;
        var ctxPatientId = window.currentHipTaskContext.patientId || 0;
        var firstRef = (selectedContexts && selectedContexts.length > 0) ? selectedContexts[0].ref : '';
        var csrf = getCsrfData();

        if (ctxTaskType === 'radiology_report_publish' || ctxTaskType === 'lab_report_publish') {
            var pushData = new URLSearchParams();
            pushData.append('lab_req_id', ctxEntityId);
            pushData.append('patient_id', ctxPatientId);
            pushData.append('abha_id', abhaAddress);
            if (firstRef) pushData.append('care_context_reference', firstRef);
            pushData.append(csrf.name || 'csrf_hms', csrf.hash);

            fetch('<?= base_url('AbdmGateway/share_diagnosis_report_bundle') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: pushData.toString()
            }).then(function(r) { return r.json(); }).then(function(pRes) {
                updateCsrf(pRes);
                if (pRes && pRes.ok === 1 && alertBox) {
                    alertBox.innerHTML += '<div class="mt-2 pt-2 border-top border-success-subtle text-dark small"><i class="bi bi-file-earmark-medical text-success me-1"></i><strong>FHIR Document Stored on Bridge:</strong> Report bundle is active on Bridge for Care Context <code>' + (pRes.care_context_reference || firstRef) + '</code>.<br><i class="bi bi-info-circle text-primary me-1"></i><strong>To view in PHR App:</strong> Open your PHR app (e.g. ABHA App) &rarr; tap <em>Linked Facilities</em> &rarr; tap <em>"Fetch Records" / "Pull Records"</em>.</div>';
                }
            }).catch(function() {});
        } else if (ctxTaskType === 'opd_prescription_publish') {
            var pushData = new URLSearchParams();
            pushData.append('opd_id', ctxEntityId);
            pushData.append('patient_id', ctxPatientId);
            pushData.append('abha_id', abhaAddress);
            pushData.append('push_to_gateway', '1');
            if (firstRef) pushData.append('care_context_reference', firstRef);
            pushData.append(csrf.name || 'csrf_hms', csrf.hash);

            fetch('<?= base_url('AbdmGateway/share_prescription_bundle') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: pushData.toString()
            }).then(function(r) { return r.json(); }).then(function(pRes) {
                updateCsrf(pRes);
                if (pRes && pRes.ok === 1 && alertBox) {
                    alertBox.innerHTML += '<div class="mt-2 pt-2 border-top border-success-subtle text-dark small"><i class="bi bi-file-earmark-medical text-success me-1"></i><strong>FHIR Document Stored on Bridge:</strong> Consultation bundle is active on Bridge for Care Context <code>' + (pRes.care_context_reference || firstRef) + '</code>.<br><i class="bi bi-info-circle text-primary me-1"></i><strong>To view in PHR App:</strong> Open your PHR app (e.g. ABHA App) &rarr; tap <em>Linked Facilities</em> &rarr; tap <em>"Fetch Records" / "Pull Records"</em>.</div>';
                }
            }).catch(function() {});
        } else if (ctxTaskType === 'invoice_publish') {
            var pushData = new URLSearchParams();
            pushData.append('source', (window.currentHipTaskContext ? window.currentHipTaskContext.source : '') || '');
            pushData.append('bill_id', ctxEntityId);
            pushData.append('patient_id', ctxPatientId);
            pushData.append('abha_id', abhaAddress);
            if (firstRef) pushData.append('care_context_reference', firstRef);
            pushData.append(csrf.name || 'csrf_hms', csrf.hash);

            fetch('<?= base_url('AbdmGateway/share_invoice_source_bundle') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: pushData.toString()
            }).then(function(r) { return r.json(); }).then(function(pRes) {
                updateCsrf(pRes);
                if (pRes && pRes.ok === 1 && alertBox) {
                    alertBox.innerHTML += '<div class="mt-2 pt-2 border-top border-success-subtle text-dark small"><i class="bi bi-file-earmark-medical text-success me-1"></i><strong>FHIR Document Stored on Bridge:</strong> Invoice bundle is active on Bridge for Care Context <code>' + (pRes.care_context_reference || firstRef) + '</code>.<br><i class="bi bi-info-circle text-primary me-1"></i><strong>To view in PHR App:</strong> Open your PHR app (e.g. ABHA App) &rarr; tap <em>Linked Facilities</em> &rarr; tap <em>"Fetch Records" / "Pull Records"</em>.</div>';
                }
            }).catch(function() {});
        } else if (ctxTaskType === 'immunization_record_publish') {
            var pushData = new URLSearchParams();
            pushData.append('record_id', ctxEntityId);
            pushData.append('patient_id', ctxPatientId);
            pushData.append('abha_id', abhaAddress);
            pushData.append('push_to_gateway', '1');
            if (firstRef) pushData.append('care_context_reference', firstRef);
            pushData.append(csrf.name || 'csrf_hms', csrf.hash);

            fetch('<?= base_url('AbdmGateway/share_immunization_bundle') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: pushData.toString()
            }).then(function(r) { return r.json(); }).then(function(pRes) {
                updateCsrf(pRes);
                if (pRes && pRes.ok === 1 && alertBox) {
                    alertBox.innerHTML += '<div class="mt-2 pt-2 border-top border-success-subtle text-dark small"><i class="bi bi-file-earmark-medical text-success me-1"></i><strong>FHIR Document Stored on Bridge:</strong> Immunization bundle is active on Bridge for Care Context <code>' + (pRes.care_context_reference || firstRef) + '</code>.<br><i class="bi bi-info-circle text-primary me-1"></i><strong>To view in PHR App:</strong> Open your PHR app (e.g. ABHA App) &rarr; tap <em>Linked Facilities</em> &rarr; tap <em>"Fetch Records" / "Pull Records"</em>.</div>';
                }
            }).catch(function() {});
        } else if (ctxTaskType === 'ipd_discharge_publish') {
            var pushData = new URLSearchParams();
            pushData.append('ipd_id', ctxEntityId);
            pushData.append('patient_id', ctxPatientId);
            pushData.append('abha_id', abhaAddress);
            pushData.append('push_to_gateway', '1');
            if (firstRef) pushData.append('care_context_reference', firstRef);
            pushData.append(csrf.name || 'csrf_hms', csrf.hash);

            fetch('<?= base_url('AbdmGateway/share_ipd_discharge_bundle') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: pushData.toString()
            }).then(function(r) { return r.json(); }).then(function(pRes) {
                updateCsrf(pRes);
                if (pRes && pRes.ok === 1 && alertBox) {
                    alertBox.innerHTML += '<div class="mt-2 pt-2 border-top border-success-subtle text-dark small"><i class="bi bi-file-earmark-medical text-success me-1"></i><strong>FHIR Document Stored on Bridge:</strong> Discharge summary bundle is active on Bridge for Care Context <code>' + (pRes.care_context_reference || firstRef) + '</code>.<br><i class="bi bi-info-circle text-primary me-1"></i><strong>To view in PHR App:</strong> Open your PHR app (e.g. ABHA App) &rarr; tap <em>Linked Facilities</em> &rarr; tap <em>"Fetch Records" / "Pull Records"</em>.</div>';
                }
            }).catch(function() {});
        } else if (ctxTaskType === 'wellness_record_publish') {
            var pushData = new URLSearchParams();
            pushData.append('opd_id', ctxEntityId);
            pushData.append('patient_id', ctxPatientId);
            pushData.append('abha_id', abhaAddress);
            pushData.append('push_to_gateway', '1');
            if (firstRef) pushData.append('care_context_reference', firstRef);
            pushData.append(csrf.name || 'csrf_hms', csrf.hash);

            fetch('<?= base_url('AbdmGateway/share_wellness_bundle') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: pushData.toString()
            }).then(function(r) { return r.json(); }).then(function(pRes) {
                updateCsrf(pRes);
                if (pRes && pRes.ok === 1 && alertBox) {
                    alertBox.innerHTML += '<div class="mt-2 pt-2 border-top border-success-subtle text-dark small"><i class="bi bi-file-earmark-medical text-success me-1"></i><strong>FHIR Document Stored on Bridge:</strong> Wellness bundle is active on Bridge for Care Context <code>' + (pRes.care_context_reference || firstRef) + '</code>.<br><i class="bi bi-info-circle text-primary me-1"></i><strong>To view in PHR App:</strong> Open your PHR app (e.g. ABHA App) &rarr; tap <em>Linked Facilities</em> &rarr; tap <em>"Fetch Records" / "Pull Records"</em>.</div>';
                }
            }).catch(function() {});
        }
    }

    /**
     * Execute 2-step HIP-Initiated Linking via Method 4.
     */
    window.executeHipInitiatedLinking = async function() {
        var abhaAddress = document.getElementById('hipAbhaAddress').value.trim();
        var rawAbhaNum  = (document.getElementById('hipAbhaNumber').value || '').trim();
        var abhaNumber  = '';
        if (rawAbhaNum.indexOf('@') === -1 && rawAbhaNum.replace(/\D/g, '').length === 14) {
            abhaNumber = rawAbhaNum.replace(/\D/g, '');
        }
        var name        = document.getElementById('hipPatientName').value.trim();
        var gender      = document.getElementById('hipGender').value;
        var yobVal      = document.getElementById('hipYob').value.trim();
        var yearOfBirth = parseInt(yobVal, 10);
        var alertBox    = document.getElementById('hipLinkStatusAlert');
        var submitBtn   = document.getElementById('btnSubmitHipLink');

        var selectedContexts = [];
        var dominantHiType = '';
        var hasIncomplete = false;

        document.querySelectorAll('.care-context-cb:checked').forEach(function(cb) {
            var ref = cb.value;
            var display = cb.nextElementSibling ? cb.nextElementSibling.textContent : ref;
            var itemHiType = cb.dataset.hiType || '';
            if (!dominantHiType && itemHiType) {
                dominantHiType = itemHiType;
            }
            if (cb.dataset.isFhirReady === '0') {
                hasIncomplete = true;
            }
            selectedContexts.push({
                ref: ref,
                display: display,
                hi_type: itemHiType
            });
        });

        if (!abhaAddress) {
            alert('Please provide patient ABHA address (e.g. user@sbx).');
            return;
        }
        if (!name) {
            alert('Please provide patient name.');
            return;
        }
        if (isNaN(yearOfBirth) || yearOfBirth < 1900) {
            alert('Please provide a valid 4-digit birth year.');
            return;
        }
        if (selectedContexts.length === 0) {
            alert('Please select at least one care context to link.');
            return;
        }
        if (hasIncomplete) {
            alert('One or more selected records are marked as incomplete. Only finalized records with documented findings can be linked to ABDM.');
            return;
        }

        var allAlreadyLinked = selectedContexts.length > 0 && selectedContexts.every(function(c) {
            var cb = document.querySelector('.care-context-cb[value="' + c.ref.replace(/"/g, '\\"') + '"]');
            return cb && cb.dataset.isLinked === '1';
        });
        if (allAlreadyLinked) {
            alertBox.className = 'alert alert-success py-2 px-3 small';
            alertBox.innerHTML = '<strong><i class="bi bi-check-circle-fill me-1"></i>Already Linked!</strong> '
                + 'The selected care context(s) are already linked with this patient\'s ABHA. Patient can view and fetch the records directly in their PHR app.';
            alertBox.classList.remove('d-none');

            syncAndPushTaskFhirRecord(selectedContexts, alertBox, abhaAddress);

            // If initiated for a specific task board task, sync task to completed so status isn't stuck on Ready to link!
            if (window.currentHipTaskContext && window.currentHipTaskContext.taskId > 0) {
                var tId = window.currentHipTaskContext.taskId;
                var taskNote = 'Care context already linked to ABHA: ' + selectedContexts.map(function(c) { return c.ref; }).join(', ');
                try {
                    var csrf = getCsrfData();
                    var markFormData = new URLSearchParams();
                    markFormData.append('task_id', tId);
                    markFormData.append('status', 'completed');
                    markFormData.append('note', taskNote);
                    markFormData.append(csrf.name || 'csrf_hms', csrf.hash);

                    fetch('<?= base_url('AbdmTaskBoard/mark_status') ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: markFormData.toString()
                    }).then(function(mRes) { return mRes.json(); }).then(function(mResult) {
                        updateCsrf(mResult);
                        if (window.currentHipTaskContext && typeof window.currentHipTaskContext.onLinked === 'function') {
                            window.currentHipTaskContext.onLinked({
                                task_id: tId,
                                care_contexts: selectedContexts
                            });
                        }
                    }).catch(function() {});
                } catch (e) {}
            } else if (window.currentHipTaskContext && typeof window.currentHipTaskContext.onLinked === 'function') {
                window.currentHipTaskContext.onLinked({
                    care_contexts: selectedContexts
                });
            }

            return;
        }

        var origBtnHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Verifying...';

        alertBox.className = 'alert alert-info py-2 px-3 small';
        alertBox.innerHTML = '<strong>Step 1/2:</strong> Requesting Link Token from ABDM Gateway via Demographic Auth...';
        alertBox.classList.remove('d-none');

        try {
            var csrf = getCsrfData();

            // Step 1: Request Link Token
            var tokenRes = await fetch('<?= base_url('AbdmGateway/hip_link_token') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    abha_address: abhaAddress,
                    abha_number: abhaNumber,
                    name: name,
                    gender: gender,
                    year_of_birth: yearOfBirth,
                    csrf_hms: csrf.hash
                })
            });

            var tokenData = await tokenRes.json();
            updateCsrf(tokenData);

            if (!tokenData.ok) {
                var err = tokenData.error_text || tokenData.error || (tokenData.detail && tokenData.detail.error && tokenData.detail.error.message) || 'Failed to request link token';
                throw new Error(err);
            }

            var linkTokenId = tokenData.link_token_id;
            var isReused = tokenData.status === 'reused';
            alertBox.innerHTML = '<strong>Step 2/2:</strong> ' + (isReused ? 'Active link token verified' : 'Link token acquired') + ' (ID: ' + linkTokenId + '). Linking ' + selectedContexts.length + ' care context(s)...';

            // Allow token callback buffer if needed (if newly generated)
            var waitMs = isReused ? 300 : 2000;
            await new Promise(function(r) { setTimeout(r, waitMs); });

            var hiTypeToSend = dominantHiType || 'OPConsultRecord';
            if (hiTypeToSend === 'OPConsultation') {
                hiTypeToSend = 'OPConsultRecord';
            }

            // Step 2: Link Care Contexts
            csrf = getCsrfData();
            var linkRes = await fetch('<?= base_url('AbdmGateway/hip_link_carecontext') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    abha_address: abhaAddress,
                    abha_number: abhaNumber,
                    link_token_id: linkTokenId,
                    patient_ref: abhaAddress,
                    display: name,
                    hi_type: hiTypeToSend,
                    care_contexts: selectedContexts,
                    csrf_hms: csrf.hash
                })
            });

            var linkData = await linkRes.json();
            updateCsrf(linkData);

            if (linkData.ok) {
                alertBox.className = 'alert alert-success py-2 px-3 small';
                alertBox.innerHTML = '<strong><i class="bi bi-check-circle-fill me-1"></i>Success!</strong> '
                    + 'Care contexts successfully submitted to ABDM for linking. Patient can now discover and access them in their ABHA / PHR app.';

                syncAndPushTaskFhirRecord(selectedContexts, alertBox, abhaAddress);

                // If initiated for a specific task board task, mark the task as completed
                if (window.currentHipTaskContext && window.currentHipTaskContext.taskId > 0) {
                    var tId = window.currentHipTaskContext.taskId;
                    var taskNote = 'Care context linked to ABHA: ' + selectedContexts.map(function(c) { return c.ref; }).join(', ');
                    try {
                        csrf = getCsrfData();
                        var markFormData = new URLSearchParams();
                        markFormData.append('task_id', tId);
                        markFormData.append('status', 'completed');
                        markFormData.append('note', taskNote);
                        markFormData.append(csrf.name || 'csrf_hms', csrf.hash);

                        fetch('<?= base_url('AbdmTaskBoard/mark_status') ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: markFormData.toString()
                        }).then(function(mRes) { return mRes.json(); }).then(function(mResult) {
                            updateCsrf(mResult);
                            if (window.currentHipTaskContext && typeof window.currentHipTaskContext.onLinked === 'function') {
                                window.currentHipTaskContext.onLinked({
                                    task_id: tId,
                                    care_contexts: selectedContexts
                                });
                            }
                        }).catch(function() {});
                    } catch (e) {}
                } else if (window.currentHipTaskContext && typeof window.currentHipTaskContext.onLinked === 'function') {
                    window.currentHipTaskContext.onLinked({
                        care_contexts: selectedContexts
                    });
                }
                
                // Refresh care contexts list
                loadPatientCareContexts(
                    window.currentHipTaskContext ? window.currentHipTaskContext.patientId : 0,
                    abhaAddress
                );
            } else {
                var errDetail = linkData.error_text || linkData.error || (linkData.detail && linkData.detail.error && linkData.detail.error.message) || 'Care context linking request failed';
                var isAlreadyLinkedErr = /already\s+(linked|registered|added|exists)/i.test(errDetail) || /1017/i.test(errDetail);
                if (isAlreadyLinkedErr) {
                    alertBox.className = 'alert alert-success py-2 px-3 small';
                    alertBox.innerHTML = '<strong><i class="bi bi-check-circle-fill me-1"></i>Already Linked!</strong> '
                        + 'ABDM reports this care context is already linked to patient\'s ABHA. Task status updated to Linked.';

                    syncAndPushTaskFhirRecord(selectedContexts, alertBox, abhaAddress);

                    if (window.currentHipTaskContext && window.currentHipTaskContext.taskId > 0) {
                        var tId = window.currentHipTaskContext.taskId;
                        var taskNote = 'Care context confirmed already linked in ABDM: ' + selectedContexts.map(function(c) { return c.ref; }).join(', ');
                        try {
                            csrf = getCsrfData();
                            var markFormData = new URLSearchParams();
                            markFormData.append('task_id', tId);
                            markFormData.append('status', 'completed');
                            markFormData.append('note', taskNote);
                            markFormData.append(csrf.name || 'csrf_hms', csrf.hash);

                            fetch('<?= base_url('AbdmTaskBoard/mark_status') ?>', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                },
                                body: markFormData.toString()
                            }).then(function(mRes) { return mRes.json(); }).then(function(mResult) {
                                updateCsrf(mResult);
                                if (window.currentHipTaskContext && typeof window.currentHipTaskContext.onLinked === 'function') {
                                    window.currentHipTaskContext.onLinked({
                                        task_id: tId,
                                        care_contexts: selectedContexts
                                    });
                                }
                            }).catch(function() {});
                        } catch (e) {}
                    } else if (window.currentHipTaskContext && typeof window.currentHipTaskContext.onLinked === 'function') {
                        window.currentHipTaskContext.onLinked({
                            care_contexts: selectedContexts
                        });
                    }

                    loadPatientCareContexts(
                        window.currentHipTaskContext ? window.currentHipTaskContext.patientId : 0,
                        abhaAddress
                    );
                    return;
                }
                throw new Error(errDetail);
            }
        } catch (err) {
            alertBox.className = 'alert alert-danger py-2 px-3 small';
            alertBox.innerHTML = '<strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Error:</strong> ' + err.message;
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnHtml;
        }
    };

    /**
     * Dispatch Deep Link SMS (sms/notify2).
     */
    window.executeHipSmsNotify = async function() {
        var phone = document.getElementById('hipSmsPhone').value.trim();
        var hipName = document.getElementById('hipSmsHipName').value.trim();
        var alertBox = document.getElementById('hipSmsStatusAlert');
        var submitBtn = document.getElementById('btnSubmitHipSms');

        if (!phone || phone.length < 10) {
            alert('Please enter a valid 10-digit mobile number.');
            return;
        }

        var origBtnHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending SMS...';

        alertBox.className = 'alert alert-info py-2 px-3 small';
        alertBox.innerHTML = 'Dispatching deep-link SMS notification request to ABDM HIE-CM...';
        alertBox.classList.remove('d-none');

        try {
            var csrf = getCsrfData();
            var res = await fetch('<?= base_url('AbdmGateway/hip_sms_notify') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    phone_number: phone,
                    hip_name: hipName,
                    csrf_hms: csrf.hash
                })
            });

            var data = await res.json();
            updateCsrf(data);

            if (data.ok) {
                alertBox.className = 'alert alert-success py-2 px-3 small';
                alertBox.innerHTML = '<strong><i class="bi bi-check-circle-fill me-1"></i>SMS Dispatched!</strong> '
                    + (data.message || 'Deep-link SMS request accepted by ABDM. The patient will receive an SMS to download the ABHA app.')
                    + (data.request_id ? ' <span class="text-muted">(Req: ' + data.request_id + ')</span>' : '');
            } else {
                throw new Error(data.error_text || data.error || 'Failed to dispatch deep-link SMS');
            }
        } catch (err) {
            alertBox.className = 'alert alert-danger py-2 px-3 small';
            alertBox.innerHTML = '<strong><i class="bi bi-exclamation-triangle-fill me-1"></i>Error:</strong> ' + err.message;
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnHtml;
        }
    };

    /**
     * Check already linked care contexts from ABDM registry.
     */
    window.checkAbdmLinkedRecords = function() {
        var abha = (document.getElementById('hipAbhaAddress').value || '').trim();
        if (!abha) {
            alert('Please enter an ABHA Address first.');
            return;
        }

        var btn = document.getElementById('btnCheckPatientLinks');
        var origHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Checking...';
        }

        fetch('<?= base_url('AbdmGateway/hip_patient_links') ?>?abha_address=' + encodeURIComponent(abha), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
            var records = (res.data && res.data[0] && res.data[0].careContexts) ? res.data[0].careContexts : [];
            if (records.length === 0) {
                alert('No care contexts currently linked to ' + abha + ' on ABDM Bridge.');
            } else {
                var list = records.map(function(c, i) {
                    return (i + 1) + '. ' + (c.referenceNumber || c.ref) + ' (' + (c.display || 'Record') + ') - Status: ' + (c.status || 'linked');
                }).join('\n');
                alert('Found ' + records.length + ' care context(s) linked to ' + abha + ':\n\n' + list);
            }
        })
        .catch(function(e) {
            if (btn) { btn.disabled = false; btn.innerHTML = origHtml; }
            alert('Failed to check linked records: ' + e.message);
        });
    };
})();
</script>

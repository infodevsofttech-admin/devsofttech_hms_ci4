<style>
    .insp-hero-badge { width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; }
    .insp-kpi-card { border-radius: 8px; transition: transform .15s ease, box-shadow .15s ease; }
    .insp-kpi-card:hover { transform: translateY(-2px); box-shadow: 0 .25rem .75rem rgba(0,0,0,.08); }
    .quick-pill { transition: all 0.15s ease-in-out; cursor: pointer; }
    .quick-pill:hover { background-color: #0d6efd !important; color: #fff !important; border-color: #0d6efd !important; }
    .care-context-row { transition: background-color .15s ease; }
    .font-mono { font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    pre.fhir-json-block {
        background: #0f172a;
        color: #e2e8f0;
        padding: 16px;
        border-radius: 8px;
        max-height: 520px;
        overflow-y: auto;
        font-size: 12.5px;
        line-height: 1.5;
        white-space: pre-wrap;
        word-break: break-all;
    }
</style>

<div class="container-fluid py-3" id="abdmFhirInspectorPage">
    <!-- Top Action Breadcrumb & Title -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <span class="insp-hero-badge bg-primary-subtle text-primary border border-primary-subtle shadow-sm">
                <i class="bi bi-funnel-fill fs-4"></i>
            </span>
            <div>
                <h4 class="mb-0 fw-bold d-flex align-items-center gap-2">
                    ABHA Filter &amp; FHIR Inspector
                    <span class="badge bg-primary fs-6">ABDM R4</span>
                </h4>
                <div class="small text-muted">Inspect patient care contexts, verify live national gateway links, and preview FHIR R4 clinical bundles</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="javascript:load_form('<?= base_url('AbdmTaskBoard') ?>','ABDM Task Board')" class="btn btn-sm btn-outline-primary fw-semibold">
                <i class="bi bi-kanban me-1"></i> ABDM Task Board
            </a>
            <button type="button" class="btn btn-sm btn-outline-success fw-semibold" onclick="if (window.inspCurrentPatientData) { openAbdmHipLinkModal(window.inspCurrentPatientData.id, window.inspCurrentPatientData.abha_address, { name: window.inspCurrentPatientData.name, gender: window.inspCurrentPatientData.gender, yob: window.inspCurrentPatientData.year_of_birth, phone: window.inspCurrentPatientData.phone, uhid: window.inspCurrentPatientData.patient_ref || window.inspCurrentPatientData.p_code, onLinked: function() { if (typeof window.inspectPatient === 'function' && window.inspCurrentPatientData && window.inspCurrentPatientData.abha_address) { window.inspectPatient(window.inspCurrentPatientData.abha_address); } } }); } else { openAbdmHipLinkModal(); }">
                <i class="bi bi-link-45deg me-1"></i> Link Records to ABHA (HIP)
            </button>
            <button type="button" class="btn btn-sm btn-outline-info fw-semibold" onclick="openAbdmHipSmsModal()">
                <i class="bi bi-chat-dots me-1"></i> Deep Link SMS (sms/notify2)
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPageRefresh" title="Refresh current patient inspection">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Search & Autocomplete Filter Card -->
    <div class="card shadow-sm mb-3 border-0 bg-white" style="border-radius: 10px;">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md">
                    <label class="form-label small fw-semibold text-secondary mb-1">
                        <i class="bi bi-search me-1"></i> Search Patient / ABHA Identifier
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-person-bounding-box text-muted"></i></span>
                        <input
                            type="text"
                            class="form-control"
                            id="inspSearchInput"
                            list="inspPatientDatalist"
                            placeholder="Enter ABHA Address (e.g. user@sbx), 14-digit ABHA Number, UHID (e.g. P15353), or Patient Name..."
                            value="<?= esc((string) ($filter_abha ?? '')) ?>"
                            autocomplete="off"
                        >
                        <button type="button" class="btn btn-primary px-3 fw-semibold" id="btnSearchPatient">
                            <i class="bi bi-funnel me-1"></i> Inspect Records
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btnClearSearch" title="Clear input">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <datalist id="inspPatientDatalist">
                        <?php foreach (($abha_patients ?? []) as $ap): ?>
                            <?php
                                $val = ! empty($ap['abha_address']) ? $ap['abha_address'] : $ap['abha_number'];
                                $displayLabel = ($ap['name'] ?? '') . ' (' . ($ap['p_code'] ?? '') . ') - ' . (! empty($ap['abha_address']) ? $ap['abha_address'] : $ap['abha_number']);
                            ?>
                            <option value="<?= esc($val) ?>"><?= esc($displayLabel) ?></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>

            <!-- Quick Patient Pills -->
            <?php if (! empty($recent_pills ?? [])): ?>
            <div class="d-flex align-items-center gap-1 flex-wrap mt-2 pt-2 border-top">
                <span class="text-muted small fw-semibold me-1" style="font-size: 11.5px;"><i class="bi bi-lightning-charge text-warning me-1"></i>Quick Inspect:</span>
                <?php foreach ($recent_pills as $rp): ?>
                    <?php
                        $val = ! empty($rp['abha_address']) ? $rp['abha_address'] : $rp['abha_number'];
                        if (empty($val)) continue;
                    ?>
                    <button
                        type="button"
                        class="btn btn-sm btn-light border py-1 px-2 text-primary quick-pill"
                        data-abha="<?= esc($val) ?>"
                        data-name="<?= esc($rp['name'] ?? '') ?>"
                        style="font-size: 11.5px; border-radius: 6px;"
                    >
                        <i class="bi bi-person me-1"></i><?= esc($rp['name'] ?? $val) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Empty State Prompt (shown before search) -->
    <div id="inspEmptyPrompt" class="card shadow-sm border-0 text-center p-5 bg-white <?= (! empty($filter_abha) || ! empty($patient_id)) ? 'd-none' : '' ?>" style="border-radius: 10px;">
        <div class="my-3">
            <span class="d-inline-flex align-items-center justify-content-center bg-light text-primary rounded-circle p-3 mb-3 border">
                <i class="bi bi-file-earmark-medical fs-1"></i>
            </span>
            <h5 class="fw-bold">Select or Search an ABHA Patient</h5>
            <p class="text-muted small mx-auto" style="max-width: 520px;">
                Enter an ABHA Address (e.g. <code>91310013085603@sbx</code>), 14-digit ABHA Number, or Patient UHID above, or click any patient from the <strong>Quick Inspect</strong> bar to view all clinical visits, gateway link status, and inspect FHIR R4 bundles.
            </p>
        </div>
    </div>

    <!-- Active Patient Inspector Container (shown after search/selection) -->
    <div id="inspActivePatientContainer" class="<?= (empty($filter_abha) && empty($patient_id)) ? 'd-none' : '' ?>">
        
        <!-- Patient Banner Card -->
        <div class="card shadow-sm mb-3 border-0 bg-white" style="border-radius: 10px; overflow: hidden;">
            <div class="card-header bg-primary text-white py-3 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <i class="bi bi-shield-check fs-4"></i>
                    <h5 class="mb-0 fw-bold" id="dspPatientName">Patient Demographics</h5>
                    <span class="badge bg-white text-primary fw-semibold" id="dspPatientGender">M</span>
                    <span class="badge bg-white text-primary fw-semibold" id="dspPatientYob">YOB: -</span>
                    <span class="badge bg-white text-primary fw-semibold" id="dspPatientUhid">UHID: -</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-light py-1 px-3 fw-semibold shadow-sm" id="btnLiveGatewayCheck">
                        <i class="bi bi-cloud-check text-primary me-1"></i> Live Gateway Check
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light py-1 px-3" id="btnLinkThisPatient">
                        <i class="bi bi-link-45deg me-1"></i> Link Hospital Records
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light py-1 px-2" id="btnReloadPatient" title="Reload patient records">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="row g-3 align-items-center pb-2 border-bottom">
                    <div class="col-md-3">
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px;">ABHA Address</div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-primary font-mono" id="dspAbhaAddress" style="word-break: break-all;">-</span>
                            <button class="btn btn-sm btn-link p-0 text-secondary" id="btnCopyAbha" title="Copy ABHA Address"><i class="bi bi-copy"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px;">14-Digit ABHA Number</div>
                        <div class="fw-semibold font-mono text-dark" id="dspAbhaNumber">-</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px;">Mobile Number</div>
                        <div class="fw-semibold text-dark" id="dspPhone">-</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 11px;">Gateway Link Status</div>
                        <div id="dspGatewayStatus"><span class="badge bg-secondary">Unchecked</span></div>
                    </div>
                </div>

                <!-- KPI Metric Tiles -->
                <div class="row g-2 mt-2">
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 bg-light text-center insp-kpi-card">
                            <div class="small text-muted fw-semibold text-uppercase" style="font-size: 11px;">Total Care Contexts</div>
                            <div class="fs-4 fw-bold text-dark" id="kpiTotal">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 bg-light text-center insp-kpi-card">
                            <div class="small text-muted fw-semibold text-uppercase" style="font-size: 11px;">Linked Contexts</div>
                            <div class="fs-4 fw-bold text-success" id="kpiLinked">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 bg-light text-center insp-kpi-card">
                            <div class="small text-muted fw-semibold text-uppercase" style="font-size: 11px;">Unlinked Contexts</div>
                            <div class="fs-4 fw-bold text-warning" id="kpiUnlinked">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 bg-light text-center insp-kpi-card">
                            <div class="small text-muted fw-semibold text-uppercase" style="font-size: 11px;">FHIR Ready Bundles</div>
                            <div class="fs-4 fw-bold text-info" id="kpiFhir">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Care Contexts Records Table Card -->
        <div class="card shadow-sm border-0 bg-white" style="border-radius: 10px;">
            <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-secondary text-uppercase small"><i class="bi bi-journal-medical me-1"></i> Patient Care Contexts &amp; Clinical Bundles</span>
                    <span class="badge bg-secondary-subtle text-secondary" id="tableFilterCountBadge">0 records</span>
                </div>
                <!-- Filter Pills -->
                <div class="d-flex align-items-center gap-1 flex-wrap" id="contextTypeFilters">
                    <button type="button" class="btn btn-sm btn-primary py-0 px-2 filter-pill active" data-filter="all">All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 filter-pill" data-filter="linked">Linked</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 filter-pill" data-filter="unlinked">Unlinked</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 filter-pill" data-filter="OPConsultRecord">OPConsult</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 filter-pill" data-filter="HealthDocumentRecord">HealthDocument</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 filter-pill" data-filter="DiagnosticReportRecord">Diagnostic</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 filter-pill" data-filter="WellnessRecord">Wellness</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 filter-pill" data-filter="InvoiceRecord">Invoice</button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="careContextsTable">
                        <thead class="table-light">
                            <tr class="small text-muted text-uppercase" style="font-size: 11px;">
                                <th style="width: 260px;">Care Context Ref</th>
                                <th>Visit / Record Description</th>
                                <th style="width: 170px;">HI Type</th>
                                <th style="width: 200px;">FHIR Bundle Status</th>
                                <th style="width: 140px;">Context Link Status</th>
                                <th style="width: 130px;" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="careContextsTbody">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    Loading patient care contexts &amp; FHIR status...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- FHIR R4 Bundle JSON Inspector Modal -->
<!-- ========================================================================= -->
<div class="modal fade" id="inspFhirModal" tabindex="-1" aria-labelledby="inspFhirModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark text-white py-2 px-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <i class="bi bi-file-earmark-code fs-5 text-info"></i>
                    <h6 class="modal-title fw-bold" id="inspFhirModalTitle">FHIR R4 Bundle Inspector</h6>
                    <span class="badge bg-primary small" id="inspFhirHiTypeBadge">Record</span>
                    <span class="badge bg-secondary-subtle text-light small font-mono" id="inspFhirRefBadge">-</span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 bg-light">
                <!-- Summary bar -->
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2 small">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="text-muted">Resource:</span> <strong class="text-primary" id="inspFhirResType">Bundle</strong>
                            <span class="text-muted">|</span>
                            <span class="text-muted">Entries:</span> <span class="badge bg-info text-dark" id="inspFhirEntryCount">0</span>
                            <span class="text-muted">|</span>
                            <span class="text-muted">Components:</span> <span id="inspFhirEntryTypes" class="small">-</span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnCopyFhirJson">
                                <i class="bi bi-clipboard me-1"></i>Copy JSON
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDownloadFhirJson">
                                <i class="bi bi-download me-1"></i>Download
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Formatted JSON Block -->
                <div class="position-relative">
                    <pre class="fhir-json-block" id="inspFhirJsonContent">Loading FHIR R4 Bundle JSON...</pre>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-white d-flex justify-content-between">
                <span class="text-muted small"><i class="bi bi-shield-check text-success me-1"></i>ABDM NRCeS FHIR R4 Standard Profile</span>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Include the HIP Link Modal Partial -->
<?= view('partials/abdm_hip_link_modal') ?>

<script>
(function() {
    var currentPatientData = null;
    var currentCareContexts = [];
    var currentFilter = 'all';
    var activeModalJson = null;

    // Helper: Escaping HTML
    function hesc(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Load Patient Demographics & Care Contexts
    window.inspectPatient = function(searchVal) {
        searchVal = (searchVal || '').trim();
        if (!searchVal) {
            alert('Please enter an ABHA Address, ABHA Number, or Patient UHID.');
            return;
        }

        var emptyPrompt = document.getElementById('inspEmptyPrompt');
        var container = document.getElementById('inspActivePatientContainer');
        var tbody = document.getElementById('careContextsTbody');

        if (emptyPrompt) emptyPrompt.classList.add('d-none');
        if (container) container.classList.remove('d-none');

        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>Loading patient care contexts &amp; FHIR status...</td></tr>';

        var url = '<?= base_url('AbdmFhirInspector/patient_data') ?>?abha_address=' + encodeURIComponent(searchVal);

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res || !res.ok || !res.patient) {
                var errTxt = (res && (res.error_text || res.error || res.message)) || 'Patient record not found';
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-warning py-4"><i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>' + hesc(errTxt) + ' for <strong>' + hesc(searchVal) + '</strong>.</td></tr>';
                document.getElementById('dspPatientName').textContent = 'Patient Not Found';
                document.getElementById('dspAbhaAddress').textContent = searchVal;
                document.getElementById('dspAbhaNumber').textContent = '-';
                document.getElementById('dspPhone').textContent = '-';
                document.getElementById('dspGatewayStatus').innerHTML = '<span class="badge bg-secondary">Unchecked</span>';
                document.getElementById('kpiTotal').textContent = '0';
                document.getElementById('kpiLinked').textContent = '0';
                document.getElementById('kpiUnlinked').textContent = '0';
                document.getElementById('kpiFhir').textContent = '0';
                currentPatientData = null;
                window.inspCurrentPatientData = null;
                currentCareContexts = [];
                return;
            }

            currentPatientData = res.patient;
            window.inspCurrentPatientData = res.patient;
            currentCareContexts = res.care_contexts || [];
            var linkedRefs = res.linked_refs || [];

            // Populate Demographics
            var p = res.patient;
            document.getElementById('dspPatientName').textContent = p.name || 'Patient';
            document.getElementById('dspPatientGender').textContent = p.gender || 'M';
            document.getElementById('dspPatientYob').textContent = 'YOB: ' + (p.year_of_birth || '-');
            document.getElementById('dspPatientUhid').textContent = 'UHID: ' + (p.patient_ref || ('P-' + p.id));
            document.getElementById('dspAbhaAddress').textContent = p.abha_address || searchVal;
            document.getElementById('dspAbhaNumber').textContent = p.abha_number || '-';
            document.getElementById('dspPhone').textContent = p.phone || '-';

            // Gateway Status
            if (linkedRefs.length > 0) {
                document.getElementById('dspGatewayStatus').innerHTML = '<span class="badge bg-success shadow-sm"><i class="bi bi-shield-check me-1"></i>Gateway Verified (' + linkedRefs.length + ' links)</span>';
            } else {
                var localLinked = currentCareContexts.some(function(c) { return c.is_linked; });
                if (localLinked) {
                    document.getElementById('dspGatewayStatus').innerHTML = '<span class="badge bg-primary shadow-sm"><i class="bi bi-check2-circle me-1"></i>Local Linked</span>';
                } else {
                    document.getElementById('dspGatewayStatus').innerHTML = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Unlinked</span>';
                }
            }

            // KPIs
            var totalCnt = currentCareContexts.length;
            var linkedCnt = 0;
            var fhirCnt = 0;
            currentCareContexts.forEach(function(c) {
                if (c.is_linked) linkedCnt++;
                if (c.is_fhir_ready) fhirCnt++;
            });
            document.getElementById('kpiTotal').textContent = totalCnt;
            document.getElementById('kpiLinked').textContent = linkedCnt;
            document.getElementById('kpiUnlinked').textContent = totalCnt - linkedCnt;
            document.getElementById('kpiFhir').textContent = fhirCnt;

            // Render Table
            renderCareContextsTable();
        })
        .catch(function(err) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4"><i class="bi bi-exclamation-octagon me-1"></i>Failed to inspect patient: ' + hesc(err.message || 'Network error') + '</td></tr>';
        });
    };

    function renderCareContextsTable() {
        var tbody = document.getElementById('careContextsTbody');
        var countBadge = document.getElementById('tableFilterCountBadge');

        var filtered = currentCareContexts.filter(function(c) {
            if (currentFilter === 'all') return true;
            if (currentFilter === 'linked') return !!c.is_linked;
            if (currentFilter === 'unlinked') return !c.is_linked;
            return c.hi_type === currentFilter;
        });

        if (countBadge) {
            countBadge.textContent = filtered.length + ' / ' + currentCareContexts.length + ' records';
        }

        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-info-circle me-1"></i>No care contexts found matching the active filter.</td></tr>';
            return;
        }

        var html = '';
        filtered.forEach(function(c) {
            var ref = c.ref || '';
            var isLinked = !!c.is_linked;
            var isFhir = !!c.is_fhir_ready;
            var hiType = c.hi_type || 'OPConsultRecord';

            var linkBadge = isLinked
                ? '<span class="badge bg-success shadow-sm"><i class="bi bi-check-circle me-1"></i>LINKED</span>'
                : '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>UNLINKED</span>';

            var fhirBadge = isFhir
                ? '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="bi bi-file-earmark-code me-1"></i>Ready</span>'
                : '<span class="badge bg-warning-subtle text-dark border border-warning small"><i class="bi bi-clock me-1"></i>Draft</span>';

            var previewBtn = '<button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 ms-2 btn-open-fhir" data-ref="' + hesc(ref) + '" data-hitype="' + hesc(hiType) + '"><i class="bi bi-eye me-1"></i>Preview FHIR</button>';

            var actionBtn = '';
            if (isLinked) {
                actionBtn = '<span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2"><i class="bi bi-check-all me-1"></i>Active</span>';
            } else if (!isFhir) {
                actionBtn = '<button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" disabled title="Clinical findings must be documented before linking to ABDM"><i class="bi bi-clock me-1"></i>Incomplete</button>';
            } else {
                actionBtn = '<button type="button" class="btn btn-sm btn-outline-success py-0 px-2 btn-table-link" data-ref="' + hesc(ref) + '" data-type="' + hesc(hiType) + '" data-entity-id="' + hesc(c.entity_id || '') + '"><i class="bi bi-link-45deg me-1"></i>Link to ABHA</button>';
            }

            html += '<tr class="care-context-row">';
            html += '<td><code class="fw-bold text-primary font-mono" style="font-size: 12.5px;">' + hesc(ref) + '</code></td>';
            html += '<td><span class="fw-semibold text-dark">' + hesc(c.display || ref) + '</span></td>';
            html += '<td><span class="badge bg-secondary-subtle text-secondary border">' + hesc(hiType) + '</span></td>';
            html += '<td>' + fhirBadge + previewBtn + '</td>';
            html += '<td>' + linkBadge + '</td>';
            html += '<td class="text-end">' + actionBtn + '</td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;
    }

    // Filter Buttons
    var filterPills = document.querySelectorAll('#contextTypeFilters .filter-pill');
    filterPills.forEach(function(btn) {
        btn.addEventListener('click', function() {
            filterPills.forEach(function(b) {
                b.classList.remove('btn-primary', 'active');
                b.classList.add('btn-outline-secondary');
            });
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-primary', 'active');
            currentFilter = btn.dataset.filter || 'all';
            renderCareContextsTable();
        });
    });

    // Search Input & Buttons
    var searchInput = document.getElementById('inspSearchInput');
    var btnSearch = document.getElementById('btnSearchPatient');
    var btnClear = document.getElementById('btnClearSearch');

    if (btnSearch) {
        btnSearch.addEventListener('click', function() {
            inspectPatient(searchInput.value);
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                inspectPatient(searchInput.value);
            }
        });
    }

    if (btnClear) {
        btnClear.addEventListener('click', function() {
            searchInput.value = '';
            document.getElementById('inspActivePatientContainer').classList.add('d-none');
            document.getElementById('inspEmptyPrompt').classList.remove('d-none');
        });
    }

    // Quick Inspect Pills
    document.querySelectorAll('.quick-pill').forEach(function(p) {
        p.addEventListener('click', function() {
            var val = p.dataset.abha;
            if (val) {
                searchInput.value = val;
                inspectPatient(val);
            }
        });
    });

    // Copy ABHA Button
    var btnCopy = document.getElementById('btnCopyAbha');
    if (btnCopy) {
        btnCopy.addEventListener('click', function() {
            var text = document.getElementById('dspAbhaAddress').textContent.trim();
            if (text && text !== '-') {
                navigator.clipboard.writeText(text).then(function() {
                    btnCopy.innerHTML = '<i class="bi bi-check text-success"></i>';
                    setTimeout(function() { btnCopy.innerHTML = '<i class="bi bi-copy"></i>'; }, 2000);
                });
            }
        });
    }

    // Live Gateway Check
    var btnGateway = document.getElementById('btnLiveGatewayCheck');
    if (btnGateway) {
        btnGateway.addEventListener('click', function() {
            var abha = document.getElementById('dspAbhaAddress').textContent.trim();
            if (!abha || abha === '-') {
                alert('No active ABHA address loaded.');
                return;
            }
            var origHtml = btnGateway.innerHTML;
            btnGateway.disabled = true;
            btnGateway.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Checking ABDM...';

            fetch('<?= base_url('AbdmFhirInspector/gateway_check') ?>?abha_address=' + encodeURIComponent(abha), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                btnGateway.disabled = false;
                btnGateway.innerHTML = origHtml;
                if (!res.ok) {
                    alert('ABDM Gateway Check: ' + (res.error_text || 'Failed to query gateway'));
                    return;
                }
                var count = res.count || 0;
                var careContexts = res.care_contexts || [];
                if (count > 0) {
                    var list = careContexts.map(function(c, i) { return (i + 1) + '. ' + c.reference + ' (' + (c.display || 'Record') + ')'; }).join('\n');
                    alert('Confirmed: ABDM National Gateway verified ' + count + ' care context(s) linked to ' + abha + ':\n\n' + list);
                } else {
                    alert('ABDM National Gateway reports 0 care contexts linked to ' + abha + ' yet.');
                }
                // Reload patient data to reflect gateway updates
                inspectPatient(abha);
            })
            .catch(function(e) {
                btnGateway.disabled = false;
                btnGateway.innerHTML = origHtml;
                alert('Gateway check request failed: ' + e.message);
            });
        });
    }

    // Reload Patient Button
    var btnReload = document.getElementById('btnReloadPatient');
    var btnPageRef = document.getElementById('btnPageRefresh');
    [btnReload, btnPageRef].forEach(function(b) {
        if (b) {
            b.addEventListener('click', function() {
                var abha = document.getElementById('dspAbhaAddress').textContent.trim();
                if (abha && abha !== '-') {
                    inspectPatient(abha);
                } else if (searchInput.value.trim()) {
                    inspectPatient(searchInput.value.trim());
                }
            });
        }
    });

    // Link Hospital Records Button (Header)
    var btnLinkHead = document.getElementById('btnLinkThisPatient');
    if (btnLinkHead) {
        btnLinkHead.addEventListener('click', function() {
            var pData = window.inspCurrentPatientData || currentPatientData;
            if (pData) {
                openAbdmHipLinkModal(pData.id, pData.abha_address, {
                    name: pData.name,
                    gender: pData.gender,
                    yob: pData.year_of_birth,
                    phone: pData.phone,
                    uhid: pData.patient_ref || pData.p_code,
                    onLinked: function() {
                        if (typeof window.inspectPatient === 'function' && pData.abha_address) {
                            window.inspectPatient(pData.abha_address);
                        }
                    }
                });
            } else {
                openAbdmHipLinkModal();
            }
        });
    }

    window.inspOpenFhirBundleModal = openFhirBundleModal;

    // Singleton Document-level Click Handler to prevent multiple listener duplication on SPA reloads
    if (!window._inspDocClickListenerAttached) {
        window._inspDocClickListenerAttached = true;

        document.addEventListener('click', function(e) {
            var linkBtn = e.target.closest('.btn-table-link');
            if (linkBtn && window.inspCurrentPatientData) {
                var pData = window.inspCurrentPatientData;
                var ref = linkBtn.dataset.ref;
                var type = linkBtn.dataset.type;
                var entityId = linkBtn.dataset.entityId || '';
                var taskType = '';
                if (type === 'HealthDocumentRecord') {
                    taskType = 'health_document_publish';
                } else if (type === 'OPConsultRecord') {
                    taskType = 'opd_prescription_publish';
                } else if (type === 'DiagnosticReportRecord' || type === 'DiagnosticReport') {
                    taskType = 'lab_report_publish';
                } else if (type === 'WellnessRecord') {
                    taskType = 'wellness_record_publish';
                } else if (type === 'InvoiceRecord') {
                    taskType = 'invoice_publish';
                }

                openAbdmHipLinkModal(pData.id, pData.abha_address, {
                    name: pData.name,
                    gender: pData.gender,
                    yob: pData.year_of_birth,
                    phone: pData.phone,
                    uhid: pData.patient_ref || pData.p_code,
                    care_context: ref,
                    task_type: taskType,
                    entity_id: entityId,
                    onLinked: function() {
                        if (typeof window.inspectPatient === 'function' && pData.abha_address) {
                            window.inspectPatient(pData.abha_address);
                        }
                    }
                });
                return;
            }

            var fhirBtn = e.target.closest('.btn-open-fhir');
            if (fhirBtn && typeof window.inspOpenFhirBundleModal === 'function') {
                var ref = fhirBtn.dataset.ref;
                var hiType = fhirBtn.dataset.hitype || 'Record';
                window.inspOpenFhirBundleModal(ref, hiType);
                return;
            }
        });
    }

    function openFhirBundleModal(ref, hiType) {
        var modalEl = document.getElementById('inspFhirModal');
        if (!modalEl) return;
        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);

        document.getElementById('inspFhirModalTitle').textContent = 'FHIR R4 Bundle - ' + ref;
        document.getElementById('inspFhirHiTypeBadge').textContent = hiType;
        document.getElementById('inspFhirRefBadge').textContent = ref;
        document.getElementById('inspFhirJsonContent').textContent = 'Loading FHIR R4 Bundle JSON from server...';
        document.getElementById('inspFhirEntryCount').textContent = '...';
        document.getElementById('inspFhirEntryTypes').textContent = 'Resolving...';

        modal.show();

        var pId = currentPatientData ? currentPatientData.id : 0;
        var abha = currentPatientData ? currentPatientData.abha_address : '';
        var url = '<?= base_url('AbdmFhirInspector/preview_bundle') ?>?ref=' + encodeURIComponent(ref) + '&patient_id=' + encodeURIComponent(pId) + '&abha=' + encodeURIComponent(abha);

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            var bundle = res.bundle || res.fhir_bundle || (res.data && res.data.bundle) || res;
            activeModalJson = bundle;

            var jsonFormatted = JSON.stringify(bundle, null, 2);
            document.getElementById('inspFhirJsonContent').textContent = jsonFormatted;

            var entries = bundle.entry || [];
            document.getElementById('inspFhirEntryCount').textContent = entries.length;
            document.getElementById('inspFhirResType').textContent = bundle.resourceType || 'Bundle';

            var types = (bundle.entry || []).map(function(e) {
                return (e.resource && e.resource.resourceType) ? e.resource.resourceType : '';
            }).filter(Boolean);
            var uniqueTypes = Array.from(new Set(types));
            document.getElementById('inspFhirEntryTypes').innerHTML = uniqueTypes.map(function(t) {
                return '<span class="badge bg-light text-dark border me-1">' + t + '</span>';
            }).join('');
        })
        .catch(function(err) {
            document.getElementById('inspFhirJsonContent').textContent = 'Error loading bundle: ' + err.message;
        });
    }

    // Copy & Download Modal Buttons
    var btnCopyJson = document.getElementById('btnCopyFhirJson');
    if (btnCopyJson) {
        btnCopyJson.addEventListener('click', function() {
            var content = document.getElementById('inspFhirJsonContent').textContent;
            if (content) {
                navigator.clipboard.writeText(content).then(function() {
                    btnCopyJson.innerHTML = '<i class="bi bi-check text-success me-1"></i>Copied!';
                    setTimeout(function() { btnCopyJson.innerHTML = '<i class="bi bi-clipboard me-1"></i>Copy JSON'; }, 2000);
                });
            }
        });
    }

    var btnDownloadJson = document.getElementById('btnDownloadFhirJson');
    if (btnDownloadJson) {
        btnDownloadJson.addEventListener('click', function() {
            var content = document.getElementById('inspFhirJsonContent').textContent;
            if (content) {
                var blob = new Blob([content], { type: 'application/json' });
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'fhir-bundle-' + Date.now() + '.json';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }
        });
    }

    // Auto-inspect if query parameter present
    var initSearch = '<?= esc((string) ($filter_abha ?? '')) ?>';
    if (initSearch) {
        inspectPatient(initSearch);
    }
})();
</script>

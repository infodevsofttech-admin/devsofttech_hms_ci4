<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?= esc($pageTitle ?? 'Central Pharmacy Mobile') ?></title>
    
    <!-- Bootstrap 5 CSS & Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Local HTML5 QR & Barcode Scanner -->
    <script src="<?= base_url('assets/vendor/html5-qrcode/html5-qrcode.min.js') ?>"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <style>
        :root {
            --primary-navy: #0f172a;
            --accent-blue: #2563eb;
            --accent-teal: #0d9488;
            --accent-purple: #7c3aed;
            --bg-body: #f8fafc;
        }

        body {
            background-color: var(--bg-body);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            padding-bottom: 85px;
            user-select: none;
            -webkit-user-select: none;
        }

        /* App Header */
        .app-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            position: sticky;
            top: 0;
            z-index: 1030;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Pill Tabs */
        .nav-pills-custom {
            background: #e2e8f0;
            border-radius: 12px;
            padding: 4px;
        }
        .nav-pills-custom .nav-link {
            border-radius: 9px;
            color: #475569;
            font-weight: 600;
            font-size: 13px;
            padding: 8px 12px;
            transition: all 0.2s ease;
        }
        .nav-pills-custom .nav-link.active {
            background-color: #ffffff;
            color: var(--accent-blue);
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        /* Patient Banner */
        .patient-card {
            background: #ffffff;
            border-radius: 14px;
            border-left: 5px solid var(--accent-blue);
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .patient-card.ipd-mode {
            border-left-color: var(--accent-purple);
        }

        /* Medicine Cards */
        .medicine-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: transform 0.1s ease;
        }
        .medicine-card:active {
            transform: scale(0.99);
        }

        /* Rack Badge */
        .badge-rack {
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            font-weight: 600;
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 6px;
        }

        /* Bottom Floating Bar */
        .bottom-cart-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 10px 16px;
            box-shadow: 0 -4px 15px rgba(0,0,0,0.08);
            z-index: 1025;
        }

        /* Stepper buttons */
        .btn-stepper {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-weight: bold;
        }

        /* Live search dropdown */
        .search-results-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1050;
            background: #ffffff;
            border-radius: 12px;
            max-height: 350px;
            overflow-y: auto;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .search-result-item {
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
        }
        .search-result-item:hover, .search-result-item:active {
            background-color: #f8fafc;
        }

        /* Modal Scanner */
        #scanner-reader {
            width: 100% !important;
            border-radius: 12px;
            overflow: hidden;
        }

        /* Toast notifications */
        .app-toast {
            position: fixed;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1090;
            min-width: 280px;
            max-width: 90%;
            pointer-events: none;
        }
    </style>
</head>
<body>

    <!-- Top App Navigation -->
    <header class="app-header py-2 px-3">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="fs-4 text-primary"><i class="bi bi-capsule"></i></span>
                <div>
                    <h6 class="mb-0 fw-bold text-white" style="letter-spacing: -0.3px;">
                        <?= esc($hospitalSetting['hospital_name'] ?? 'HMS Central Pharmacy') ?>
                    </h6>
                    <small class="text-white-50" style="font-size: 11px;">
                        <i class="bi bi-phone"></i> Mobile Store &amp; Floor Picker
                    </small>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-light btn-sm rounded-pill px-2 py-1" onclick="openBarcodeScanner('universal');" title="Scan Barcode">
                    <i class="bi bi-qr-code-scan"></i>
                </button>
                <div class="dropdown">
                    <button class="btn btn-dark btn-sm rounded-pill py-1 px-2 border-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" style="font-size: 11px;">
                        <i class="bi bi-person-fill text-info"></i> <span id="hdr_operator_name"><?= esc($sessionUser ?: 'Picker') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><h6 class="dropdown-header">Staff Session</h6></li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0);" onclick="promptChangeOperator();">
                                <i class="bi bi-pencil-square me-2"></i> Change Operator Name
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= base_url('login/logout') ?>">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="container-fluid px-3 pt-3">

        <!-- Top Pill Tabs -->
        <ul class="nav nav-pills nav-pills-custom nav-fill mb-3" id="mainTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-picker" data-bs-toggle="pill" data-bs-target="#pane-picker" type="button" role="tab">
                    <i class="bi bi-cart3 me-1"></i> Rx Picker <span class="badge bg-primary rounded-pill ms-1" id="badge_cart_count">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-audit" data-bs-toggle="pill" data-bs-target="#pane-audit" type="button" role="tab">
                    <i class="bi bi-boxes me-1"></i> Stock Audit
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-recent" data-bs-toggle="pill" data-bs-target="#pane-recent" type="button" role="tab" onclick="loadRecentDrafts();">
                    <i class="bi bi-clock-history me-1"></i> Drafts
                </button>
            </li>
        </ul>

        <!-- TAB PANES -->
        <div class="tab-content" id="mainTabContent">

            <!-- ============================================================= -->
            <!-- TAB 1: PRESCRIPTION PICKER & DRAFT BASKET                     -->
            <!-- ============================================================= -->
            <div class="tab-pane fade show active" id="pane-picker" role="tabpanel">

                <!-- 1. Patient Search Header -->
                <div class="position-relative mb-3">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0 border-end-0 ps-0" id="patient_search_input" placeholder="Search UHID, OPD, IPD ID, Phone, Name..." autocomplete="off">
                        <button class="btn btn-outline-secondary bg-white border-start-0 text-primary" type="button" onclick="openBarcodeScanner('patient');" title="Scan Patient Barcode">
                            <i class="bi bi-upc-scan fs-5"></i>
                        </button>
                    </div>
                    
                    <!-- Search Results Dropdown -->
                    <div class="search-results-dropdown shadow" id="patient_results_dropdown" style="display: none;"></div>
                </div>

                <!-- 2. Active Patient Selected Card -->
                <div class="patient-card p-3 mb-3" id="active_patient_card" style="display: none;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge mb-1" id="pt_badge_type" style="background:#2563eb;">OPD Patient</span>
                            <h5 class="fw-bold mb-0 text-dark" id="pt_name">Patient Name</h5>
                            <div class="text-muted small" style="font-size: 12px;">
                                UHID: <strong class="text-dark" id="pt_uhid">P12345</strong> | <span id="pt_demographics">Male, 35 Y</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="clearSelectedPatient();" title="Clear Patient">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="d-flex gap-2 flex-wrap text-muted small pt-2 border-top" style="font-size: 11px;">
                        <div id="pt_ipd_details" style="display:none;">
                            <i class="bi bi-hospital text-purple"></i> <span id="pt_ipd_code">IPD-102</span>
                            <span class="badge bg-warning-subtle text-dark border ms-1" id="pt_bed_no">Bed: 201</span>
                        </div>
                        <div id="pt_opd_details">
                            <i class="bi bi-file-medical text-teal"></i> <span id="pt_opd_code">OPD-305</span>
                        </div>
                        <div>
                            <i class="bi bi-person-badge text-primary"></i> <span id="pt_doctor">Dr. Sharma</span>
                        </div>
                        <div id="pt_mobile_wrapper">
                            <i class="bi bi-telephone text-success"></i> <span id="pt_mobile">9876543210</span>
                        </div>
                    </div>
                </div>

                <!-- 3. Medicine Search & Rack Finder -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <label class="form-label fw-bold text-dark small mb-1">
                            <i class="bi bi-search me-1 text-primary"></i> Find Medicine &amp; Shelf Location
                        </label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" id="med_search_input" placeholder="Type medicine name or salt..." autocomplete="off">
                            <button class="btn btn-primary" type="button" id="btn_search_med" onclick="searchMedicines();">
                                <i class="bi bi-arrow-right"></i>
                            </button>
                            <button class="btn btn-outline-secondary" type="button" onclick="openBarcodeScanner('medicine');" title="Scan Box Barcode">
                                <i class="bi bi-upc"></i>
                            </button>
                        </div>

                        <!-- Quick Filter Pills -->
                        <div class="d-flex gap-1 overflow-x-auto pb-1" style="font-size: 12px;">
                            <span class="badge bg-light text-dark border py-1 px-2" style="cursor:pointer;" onclick="quickFilterMed('Tab');">Tablets</span>
                            <span class="badge bg-light text-dark border py-1 px-2" style="cursor:pointer;" onclick="quickFilterMed('Syp');">Syrups</span>
                            <span class="badge bg-light text-dark border py-1 px-2" style="cursor:pointer;" onclick="quickFilterMed('Inj');">Injections</span>
                            <span class="badge bg-light text-dark border py-1 px-2" style="cursor:pointer;" onclick="quickFilterMed('Drop');">Drops</span>
                        </div>
                    </div>
                </div>

                <!-- 4. Medicine Search Results Area -->
                <div id="med_results_container">
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-basket fs-1 text-secondary opacity-50 d-block mb-2"></i>
                        <span>Search medicine above or scan box barcode to pick.</span>
                    </div>
                </div>

            </div>

            <!-- ============================================================= -->
            <!-- TAB 2: PHYSICAL STOCK AUDIT & RECONCILIATION                  -->
            <!-- ============================================================= -->
            <div class="tab-pane fade" id="pane-audit" role="tabpanel">
                
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-2 text-primary">
                            <i class="bi bi-clipboard2-check me-1"></i> Rack &amp; Shelf Physical Stock Count
                        </h6>
                        <small class="text-muted d-block mb-3" style="font-size: 12px;">
                            Select a rack or search medicine to compare live system stock vs physical shelf count.
                        </small>

                        <div class="row g-2 mb-2">
                            <div class="col-7">
                                <label class="form-label small fw-bold text-muted mb-0">Select / Type Rack</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control form-control-sm" id="audit_rack_input" placeholder="e.g. Rack A" list="rack_datalist" autocomplete="off">
                                    <datalist id="rack_datalist"></datalist>
                                    <button class="btn btn-outline-secondary" type="button" onclick="clearAuditRack();" title="Clear Rack / Shelf"><i class="bi bi-x"></i></button>
                                </div>
                            </div>
                            <div class="col-5">
                                <label class="form-label small fw-bold text-muted mb-0">Shelf No.</label>
                                <input type="text" class="form-control form-control-sm" id="audit_shelf_input" placeholder="e.g. 1" autocomplete="off">
                            </div>
                        </div>

                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="audit_search_q" placeholder="Search medicine name or batch no..." autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" id="btn_clear_audit_q" onclick="clearAuditSearch();" title="Clear Search" style="display:none;">
                                <i class="bi bi-x-lg"></i>
                            </button>
                            <button class="btn btn-primary" type="button" id="btn_audit_load" onclick="loadAuditItems();">
                                <i class="bi bi-arrow-clockwise"></i> Load
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Audit Items List Area -->
                <div id="audit_items_container">
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-boxes fs-1 text-secondary opacity-50 d-block mb-2"></i>
                        <span>Select a rack or type medicine/batch name to start shelf counting.</span>
                    </div>
                </div>

            </div>

            <!-- ============================================================= -->
            <!-- TAB 3: RECENT DRAFTS SENT TO COUNTER                          -->
            <!-- ============================================================= -->
            <div class="tab-pane fade" id="pane-recent" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-1"></i> Today's Draft Baskets</h6>
                    <button class="btn btn-sm btn-outline-primary" type="button" onclick="loadRecentDrafts();">
                        <i class="bi bi-arrow-repeat"></i> Refresh
                    </button>
                </div>

                <div id="recent_drafts_container">
                    <div class="text-center py-4 text-muted">Loading drafts...</div>
                </div>
            </div>

        </div>

    </div>

    <!-- Floating Bottom Bar for Basket -->
    <div class="bottom-cart-bar d-flex justify-content-between align-items-center" id="bottom_cart_bar">
        <div>
            <div class="fw-bold text-dark" style="font-size: 15px;">
                <span id="cart_total_items">0</span> Items Picked
            </div>
            <small class="text-muted" style="font-size: 12px;">
                Est. Total: ₹<strong class="text-primary" id="cart_total_amount">0.00</strong>
            </small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showBasketDrawer();" title="View Picked Items">
                <i class="bi bi-list-check"></i> Review
            </button>
            <button type="button" class="btn btn-primary btn-sm fw-bold px-3 shadow-sm btn-send-draft-action" id="btn_send_draft" onclick="sendDraftToCounter();">
                <i class="bi bi-send-fill me-1"></i> Send to Counter
            </button>
        </div>
    </div>

    <!-- Offcanvas / Modal for Reviewing Basket Items -->
    <div class="modal fade" id="basketModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header bg-light py-2">
                    <h6 class="modal-title fw-bold text-dark">
                        <i class="bi bi-cart-check text-primary me-1"></i> Picked Medicines Basket
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-2" id="basket_items_list">
                    <!-- Dynamic Cart Rows -->
                </div>
                <div class="modal-footer d-flex justify-content-between py-2 bg-light">
                    <div>
                        <div class="small text-muted">Total Estimated Amount</div>
                        <h5 class="fw-bold text-primary mb-0" id="basket_modal_total">₹0.00</h5>
                    </div>
                    <button type="button" class="btn btn-primary fw-bold btn-send-draft-action" id="btn_modal_confirm_draft" onclick="sendDraftToCounter();">
                        <i class="bi bi-send-fill me-1"></i> Confirm &amp; Send Draft
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Barcode / QR Scanner -->
    <div class="modal fade" id="scannerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white py-2">
                    <h6 class="modal-title fw-bold" id="scanner_modal_title">
                        <i class="bi bi-qr-code-scan me-1"></i> Scan Barcode
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="stopBarcodeScanner();"></button>
                </div>
                <div class="modal-body p-2 text-center">
                    <div id="scanner-reader"></div>
                    <small class="text-muted d-block mt-2">Point phone camera at patient parchi QR/Barcode or medicine strip</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div class="app-toast" id="app_toast" style="display: none;">
        <div class="alert alert-success shadow-lg py-2 px-3 mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <span id="toast_msg" class="small fw-semibold">Action completed</span>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // =========================================================================
    // STATE & GLOBAL VARIABLES
    // =========================================================================
    var baseUrl = '<?= rtrim(base_url(), '/') ?>/';
    var csrfHeader = '<?= csrf_header() ?>';
    var csrfTokenName = '<?= csrf_token() ?>';
    var csrfHash = '<?= csrf_hash() ?>';

    // Global AJAX setup: inject CSRF token header & X-Requested-With
    jQuery.ajaxSetup({
        headers: {
            '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    var selectedPatient = null;
    var cartItems = [];
    var html5QrCodeScanner = null;
    var currentScannerTarget = 'universal';
    var operatorName = localStorage.getItem('hms_pharmacy_picker_name') || '<?= esc($sessionUser ?: 'Floor Staff') ?>';

    document.getElementById('hdr_operator_name').innerText = operatorName;

    // Show Toast
    function showToast(msg, isError) {
        var toastEl = jQuery('#app_toast');
        var alertEl = toastEl.find('.alert');
        if (isError) {
            alertEl.removeClass('alert-success').addClass('alert-danger').find('i').attr('class', 'bi bi-exclamation-triangle-fill fs-5');
        } else {
            alertEl.removeClass('alert-danger').addClass('alert-success').find('i').attr('class', 'bi bi-check-circle-fill fs-5');
        }
        jQuery('#toast_msg').text(msg);
        toastEl.fadeIn(200);
        setTimeout(function() {
            toastEl.fadeOut(300);
        }, 3000);
    }

    function promptChangeOperator() {
        var name = prompt("Enter your Operator / Staff Name:", operatorName);
        if (name && name.trim() !== '') {
            operatorName = name.trim();
            localStorage.setItem('hms_pharmacy_picker_name', operatorName);
            document.getElementById('hdr_operator_name').innerText = operatorName;
            showToast("Operator updated: " + operatorName);
        }
    }

    // =========================================================================
    // 1. PATIENT SEARCH LOGIC (UHID, OPD, IPD, NAME, PHONE)
    // =========================================================================
    var searchTimer = null;
    jQuery('#patient_search_input').on('input', function() {
        var term = jQuery(this).val().trim();
        clearTimeout(searchTimer);
        if (term.length < 2) {
            jQuery('#patient_results_dropdown').hide().empty();
            return;
        }

        searchTimer = setTimeout(function() {
            jQuery.ajax({
                url: baseUrl + 'Medical/mobile_patient_search',
                type: 'GET',
                data: { q: term },
                dataType: 'json',
                success: function(res) {
                    var dropdown = jQuery('#patient_results_dropdown');
                    dropdown.empty();
                    if (!res.patients || res.patients.length === 0) {
                        dropdown.html('<div class="p-3 text-muted text-center small">No patients found matching "' + escapeHtml(term) + '"</div>').show();
                        return;
                    }

                    var html = '';
                    res.patients.forEach(function(p) {
                        var badgeClass = p.is_ipd ? 'bg-primary-subtle text-primary border-primary' : 'bg-success-subtle text-success border-success';
                        html += '<div class="search-result-item" onclick="selectPatient(' + JSON.stringify(p).replace(/"/g, '&quot;') + ')">' +
                            '<div class="d-flex justify-content-between align-items-center">' +
                                '<div>' +
                                    '<span class="badge border ' + badgeClass + ' me-1" style="font-size:10px;">' + escapeHtml(p.badge) + '</span>' +
                                    '<strong class="text-dark">' + escapeHtml(p.patient_name) + '</strong>' +
                                '</div>' +
                                '<small class="text-muted">' + escapeHtml(p.uhid) + '</small>' +
                            '</div>' +
                            '<div class="d-flex justify-content-between text-muted mt-1" style="font-size:11px;">' +
                                '<span><i class="bi bi-person"></i> ' + p.gender + ', ' + p.age + '</span>' +
                                (p.doc_name ? '<span><i class="bi bi-heart-pulse"></i> ' + escapeHtml(p.doc_name) + '</span>' : '') +
                                (p.mobile ? '<span><i class="bi bi-phone"></i> ' + escapeHtml(p.mobile) + '</span>' : '') +
                            '</div>' +
                        '</div>';
                    });
                    dropdown.html(html).show();
                }
            });
        }, 250);
    });

    // Close dropdown on click outside
    jQuery(document).on('click', function(e) {
        if (!jQuery(e.target).closest('#patient_search_input, #patient_results_dropdown').length) {
            jQuery('#patient_results_dropdown').hide();
        }
    });

    function selectPatient(p) {
        selectedPatient = p;
        jQuery('#patient_search_input').val('');
        jQuery('#patient_results_dropdown').hide();

        var card = jQuery('#active_patient_card');
        if (p.is_ipd) {
            card.addClass('ipd-mode');
            jQuery('#pt_badge_type').text('IPD Admitted').css('background', 'var(--accent-purple)');
            jQuery('#pt_ipd_details').show();
            jQuery('#pt_ipd_code').text(p.ipd_code || ('IPD #' + p.ipd_id));
            if (p.bed_no) {
                jQuery('#pt_bed_no').text('Bed: ' + p.bed_no).show();
            } else {
                jQuery('#pt_bed_no').hide();
            }
            jQuery('#pt_opd_details').hide();
        } else {
            card.removeClass('ipd-mode');
            jQuery('#pt_badge_type').text('OPD Patient').css('background', 'var(--accent-blue)');
            jQuery('#pt_ipd_details').hide();
            jQuery('#pt_opd_details').show();
            jQuery('#pt_opd_code').text(p.opd_code || ('OPD #' + p.opd_id));
        }

        jQuery('#pt_name').text(p.patient_name);
        jQuery('#pt_uhid').text(p.uhid);
        jQuery('#pt_demographics').text((p.gender || '-') + ', ' + (p.age ? p.age + ' Y' : '-'));
        jQuery('#pt_doctor').text(p.doc_name || 'Doctor');
        if (p.mobile) {
            jQuery('#pt_mobile').text(p.mobile);
            jQuery('#pt_mobile_wrapper').show();
        } else {
            jQuery('#pt_mobile_wrapper').hide();
        }

        card.slideDown(200);
        showToast("Selected: " + p.patient_name);
    }

    function clearSelectedPatient() {
        selectedPatient = null;
        jQuery('#active_patient_card').slideUp(200);
        jQuery('#patient_search_input').val('').focus();
    }

    // =========================================================================
    // 2. MEDICINE SEARCH & RACK PICKER
    // =========================================================================
    var medTimer = null;
    jQuery('#med_search_input').on('input', function() {
        clearTimeout(medTimer);
        var q = jQuery(this).val().trim();
        if (q.length < 2) return;
        medTimer = setTimeout(searchMedicines, 300);
    });

    function quickFilterMed(term) {
        jQuery('#med_search_input').val(term);
        searchMedicines();
    }

    function searchMedicines() {
        var q = jQuery('#med_search_input').val().trim();
        var container = jQuery('#med_results_container');
        container.html('<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary"></div> Searching medicines &amp; live stock...</div>');

        jQuery.ajax({
            url: baseUrl + 'Medical/mobile_medicine_search',
            type: 'GET',
            data: { q: q },
            dataType: 'json',
            success: function(res) {
                if (!res.items || res.items.length === 0) {
                    container.html('<div class="text-center py-4 text-muted"><i class="bi bi-search fs-3 d-block mb-1"></i> No medicines found in store stock matching "' + escapeHtml(q) + '".</div>');
                    return;
                }

                var html = '';
                res.items.forEach(function(item) {
                    var rackStr = (item.rack_no || item.shelf_no) ? ('📍 Rack: ' + (item.rack_no || '-') + ' | Shelf: ' + (item.shelf_no || '-')) : '📍 Shelf not set';
                    
                    html += '<div class="medicine-card p-3 mb-2 shadow-sm">' +
                        '<div class="d-flex justify-content-between align-items-start mb-1">' +
                            '<div>' +
                                '<h6 class="fw-bold mb-0 text-dark">' + escapeHtml(item.item_name) + '</h6>' +
                                (item.genericname ? '<div class="text-muted" style="font-size:11px;">' + escapeHtml(item.genericname) + '</div>' : '') +
                            '</div>' +
                            '<span class="badge badge-rack">' + escapeHtml(rackStr) + '</span>' +
                        '</div>' +
                        '<div class="small text-muted mb-2" style="font-size:11px;">Pack: ' + escapeHtml(item.packing) + ' | ' + escapeHtml(item.formulation) + '</div>';

                    // Batches Table for this Medicine
                    html += '<div class="bg-light p-2 rounded border" style="font-size: 12px;">';
                    item.batches.forEach(function(b) {
                        var isAvail = b.available_units > 0;
                        var stockClass = isAvail ? 'text-success fw-bold' : 'text-danger';
                        var expStr = b.exp_formatted || b.expiry_date || '-';

                        html += '<div class="d-flex justify-content-between align-items-center py-1 border-bottom last-border-0">' +
                            '<div>' +
                                '<code>' + escapeHtml(b.batch_no) + '</code> <small class="text-muted ms-1">Exp: ' + expStr + '</small>' +
                                '<div class="small">₹' + b.selling_unit_rate.toFixed(2) + ' | Stock: <span class="' + stockClass + '">' + b.available_units + '</span></div>' +
                            '</div>' +
                            '<div class="d-flex align-items-center gap-1">' +
                                '<div class="input-group input-group-sm" style="width: 100px;">' +
                                    '<button class="btn btn-outline-secondary py-0 px-2" type="button" onclick="decrementQty(' + b.batch_id + ');">-</button>' +
                                    '<input type="number" class="form-control text-center py-0 px-1" id="qty_' + b.batch_id + '" value="1" min="1" max="' + (b.available_units || 999) + '" style="font-size:12px;">' +
                                    '<button class="btn btn-outline-secondary py-0 px-2" type="button" onclick="incrementQty(' + b.batch_id + ');">+</button>' +
                                '</div>' +
                                '<button type="button" class="btn btn-sm btn-primary py-1 px-2 ms-1" onclick="addToCart(' + 
                                    item.item_code + ', \'' + escapeQuotes(item.item_name) + '\', ' + 
                                    b.batch_id + ', \'' + escapeQuotes(b.batch_no) + '\', \'' + 
                                    b.expiry_date + '\', ' + b.selling_unit_rate + ', ' + b.mrp + ', \'' + 
                                    escapeQuotes(item.packing) + '\', \'' + escapeQuotes(item.rack_no) + '\', \'' + 
                                    escapeQuotes(item.shelf_no) + '\', \'' + escapeQuotes(item.formulation) + '\');">' +
                                    '<i class="bi bi-plus-lg"></i> Add' +
                                '</button>' +
                            '</div>' +
                        '</div>';
                    });
                    html += '</div></div>';
                });

                container.html(html);
            }
        });
    }

    function decrementQty(batchId) {
        var input = jQuery('#qty_' + batchId);
        var val = parseInt(input.val(), 10) || 1;
        if (val > 1) {
            input.val(val - 1);
        }
    }

    function incrementQty(batchId) {
        var input = jQuery('#qty_' + batchId);
        var val = parseInt(input.val(), 10) || 1;
        input.val(val + 1);
    }

    // =========================================================================
    // 3. BASKET / CART LOGIC
    // =========================================================================
    function addToCart(itemCode, itemName, batchId, batchNo, expiryDate, rate, mrp, packing, rackNo, shelfNo, formulation) {
        var qtyInput = jQuery('#qty_' + batchId);
        var qty = parseInt(qtyInput.val(), 10) || 1;

        // Check if item batch already exists in cart
        var existing = cartItems.find(function(it) { return it.batch_id === batchId; });
        if (existing) {
            existing.qty += qty;
        } else {
            cartItems.push({
                item_code: itemCode,
                item_name: itemName,
                batch_id: batchId,
                batch_no: batchNo,
                expiry_date: expiryDate,
                rate: rate,
                mrp: mrp,
                packing: packing,
                rack_no: rackNo,
                shelf_no: shelfNo,
                formulation: formulation,
                qty: qty
            });
        }

        updateCartUI();
        showToast("Added " + qty + "x " + itemName + " to basket.");
    }

    function removeFromCart(index) {
        cartItems.splice(index, 1);
        updateCartUI();
        renderBasketModalList();
    }

    function updateCartUI() {
        var totalCount = 0;
        var totalAmount = 0.0;
        cartItems.forEach(function(it) {
            totalCount += it.qty;
            totalAmount += (it.qty * it.rate);
        });

        jQuery('#badge_cart_count').text(cartItems.length);
        jQuery('#cart_total_items').text(totalCount);
        jQuery('#cart_total_amount').text(totalAmount.toFixed(2));
        jQuery('#basket_modal_total').text('₹' + totalAmount.toFixed(2));
    }

    function showBasketDrawer() {
        renderBasketModalList();
        var modal = new bootstrap.Modal(document.getElementById('basketModal'));
        modal.show();
    }

    function renderBasketModalList() {
        var container = jQuery('#basket_items_list');
        if (cartItems.length === 0) {
            container.html('<div class="text-center py-4 text-muted"><i class="bi bi-cart-x fs-1 d-block mb-1"></i> Basket is empty.</div>');
            return;
        }

        var html = '<div class="list-group list-group-flush">';
        cartItems.forEach(function(it, idx) {
            var lineTot = (it.qty * it.rate).toFixed(2);
            html += '<div class="list-group-item px-2 py-2 d-flex justify-content-between align-items-center">' +
                '<div style="max-width:70%;">' +
                    '<strong class="text-dark small">' + escapeHtml(it.item_name) + '</strong>' +
                    '<div class="text-muted" style="font-size:11px;">' +
                        '<code>' + escapeHtml(it.batch_no) + '</code> | Qty: ' + it.qty + ' x ₹' + it.rate.toFixed(2) +
                        (it.rack_no ? ' | <span class="badge bg-light text-dark border">R:' + it.rack_no + '</span>' : '') +
                    '</div>' +
                '</div>' +
                '<div class="text-end">' +
                    '<div class="fw-bold text-dark small">₹' + lineTot + '</div>' +
                    '<button type="button" class="btn btn-link text-danger p-0 small" onclick="removeFromCart(' + idx + ');" style="font-size:11px;">Remove</button>' +
                '</div>' +
            '</div>';
        });
        html += '</div>';
        container.html(html);
    }

    // =========================================================================
    // 4. SEND DRAFT TO BILLING COUNTER
    // =========================================================================
    function sendDraftToCounter() {
        if (cartItems.length === 0) {
            alert("Basket is empty! Please pick medicines first.");
            return;
        }

        var payload = {
            patient_id: selectedPatient ? selectedPatient.patient_id : 0,
            uhid: selectedPatient ? selectedPatient.uhid : '',
            patient_name: selectedPatient ? selectedPatient.patient_name : 'Walk-in Customer',
            mobile: selectedPatient ? selectedPatient.mobile : '',
            ipd_id: selectedPatient ? selectedPatient.ipd_id : 0,
            ipd_code: selectedPatient ? selectedPatient.ipd_code : '',
            bed_no: selectedPatient ? selectedPatient.bed_no : '',
            opd_id: selectedPatient ? selectedPatient.opd_id : 0,
            opd_code: selectedPatient ? selectedPatient.opd_code : '',
            doc_id: selectedPatient ? (selectedPatient.doc_id || 0) : 0,
            doc_name: selectedPatient ? selectedPatient.doc_name : '',
            picker_name: operatorName,
            items: cartItems
        };
        payload[csrfTokenName] = csrfHash;

        var btns = jQuery('.btn-send-draft-action');
        btns.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Sending Draft...');

        jQuery.ajax({
            url: baseUrl + 'Medical/mobile_save_draft',
            type: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            dataType: 'json',
            success: function(res) {
                btns.prop('disabled', false);
                jQuery('#btn_send_draft').html('<i class="bi bi-send-fill me-1"></i> Send to Counter');
                jQuery('#btn_modal_confirm_draft').html('<i class="bi bi-send-fill me-1"></i> Confirm &amp; Send Draft');

                if (res.status === 1) {
                    var m = bootstrap.Modal.getInstance(document.getElementById('basketModal'));
                    if (m) m.hide();

                    alert("🎉 SUCCESS!\n\nDraft Bill #" + res.draft_code + " created successfully.\nIt is now live on the Desktop Billing Counter (Invoice_Med_Draft).");
                    
                    // Reset basket and patient for next customer
                    cartItems = [];
                    updateCartUI();
                    clearSelectedPatient();
                    jQuery('#med_results_container').empty();
                    jQuery('#med_search_input').val('');
                } else {
                    alert(res.message || 'Failed to save draft bill.');
                }
            },
            error: function(xhr, status, error) {
                btns.prop('disabled', false);
                jQuery('#btn_send_draft').html('<i class="bi bi-send-fill me-1"></i> Send to Counter');
                jQuery('#btn_modal_confirm_draft').html('<i class="bi bi-send-fill me-1"></i> Confirm &amp; Send Draft');

                var errDetail = "HTTP " + xhr.status + " (" + (xhr.statusText || error || 'Connection Error') + ")";
                try {
                    var parsed = JSON.parse(xhr.responseText);
                    if (parsed && parsed.message) {
                        errDetail += "\n\nServer message: " + parsed.message;
                    }
                } catch(e) {}
                alert("Failed to communicate with hospital server.\n\n" + errDetail + "\n\nPlease check WiFi/intranet connection or contact IT support.");
            }
        });
    }

    // =========================================================================
    // 5. PHYSICAL STOCK AUDIT & RECONCILIATION LOGIC
    // =========================================================================
    function loadRacksDatalist() {
        jQuery.ajax({
            url: baseUrl + 'Medical/mobile_audit_racks',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.racks) {
                    var dl = jQuery('#rack_datalist');
                    dl.empty();
                    res.racks.forEach(function(r) {
                        dl.append('<option value="' + escapeHtml(r) + '">');
                    });
                }
            }
        });
    }
    loadRacksDatalist();

    function clearAuditSearch() {
        jQuery('#audit_search_q').val('').focus();
        jQuery('#btn_clear_audit_q').hide();
        loadAuditItems();
    }

    function clearAuditRack() {
        jQuery('#audit_rack_input').val('');
        jQuery('#audit_shelf_input').val('');
        loadAuditItems();
    }

    var auditSearchTimer = null;
    jQuery('#audit_search_q').on('input', function() {
        clearTimeout(auditSearchTimer);
        var q = jQuery(this).val().trim();
        if (q.length > 0) {
            jQuery('#btn_clear_audit_q').show();
        } else {
            jQuery('#btn_clear_audit_q').hide();
        }

        var rack = jQuery('#audit_rack_input').val().trim();
        var shelf = jQuery('#audit_shelf_input').val().trim();

        if (q.length === 0 && rack === '' && shelf === '') {
            jQuery('#audit_items_container').html(
                '<div class="text-center py-4 text-muted">' +
                '<i class="bi bi-boxes fs-1 text-secondary opacity-50 d-block mb-2"></i>' +
                '<span>Select a rack or type medicine/batch name to start shelf counting.</span>' +
                '</div>'
            );
            return;
        }

        auditSearchTimer = setTimeout(loadAuditItems, 300);
    });

    jQuery('#audit_rack_input, #audit_shelf_input').on('change input', function() {
        clearTimeout(auditSearchTimer);
        auditSearchTimer = setTimeout(loadAuditItems, 350);
    });

    jQuery('#audit_search_q, #audit_rack_input, #audit_shelf_input').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(auditSearchTimer);
            loadAuditItems();
        }
    });

    function loadAuditItems() {
        var rack = jQuery('#audit_rack_input').val().trim();
        var shelf = jQuery('#audit_shelf_input').val().trim();
        var q = jQuery('#audit_search_q').val().trim();

        if (q.length > 0) {
            jQuery('#btn_clear_audit_q').show();
        } else {
            jQuery('#btn_clear_audit_q').hide();
        }

        var container = jQuery('#audit_items_container');

        if (q === '' && rack === '' && shelf === '') {
            container.html(
                '<div class="text-center py-4 text-muted">' +
                '<i class="bi bi-boxes fs-1 text-secondary opacity-50 d-block mb-2"></i>' +
                '<span>Select a rack or type medicine/batch name to start shelf counting.</span>' +
                '</div>'
            );
            return;
        }

        container.html('<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary"></div> Searching shelf batches...</div>');

        jQuery.ajax({
            url: baseUrl + 'Medical/mobile_audit_items',
            type: 'GET',
            data: { rack: rack, shelf: shelf, q: q },
            dataType: 'json',
            success: function(res) {
                if (!res.items || res.items.length === 0) {
                    var notFoundMsg = q 
                        ? ('No medicines or batches found matching "' + escapeHtml(q) + '"' + (rack ? ' in rack ' + escapeHtml(rack) : '') + '.')
                        : ('No batches found in rack "' + escapeHtml(rack) + '"' + (shelf ? ' / shelf ' + escapeHtml(shelf) : '') + '.');
                    container.html('<div class="text-center py-4 text-muted"><i class="bi bi-inbox fs-3 d-block mb-1"></i> ' + notFoundMsg + '</div>');
                    return;
                }

                var html = '';
                res.items.forEach(function(it) {
                    var diffId = 'diff_' + it.batch_id;
                    var physicalInputId = 'phys_' + it.batch_id;
                    var reasonId = 'reason_' + it.batch_id;
                    var remarksId = 'rem_' + it.batch_id;
                    var cardId = 'audit_card_' + it.batch_id;

                    var alreadyAuditedBadge = '';
                    var prefillCount = it.system_qty;
                    if (it.last_audited_today) {
                        alreadyAuditedBadge = '<span class="badge bg-success-subtle text-success border border-success ms-1"><i class="bi bi-check2"></i> Audited Today (' + it.last_audited_today.physical_qty + ')</span>';
                        prefillCount = it.last_audited_today.physical_qty;
                    }

                    html += '<div class="medicine-card p-3 mb-2 shadow-sm" id="' + cardId + '">' +
                        '<div class="d-flex justify-content-between align-items-start mb-1">' +
                            '<div>' +
                                '<h6 class="fw-bold mb-0 text-dark">' + escapeHtml(it.item_name) + ' ' + alreadyAuditedBadge + '</h6>' +
                                '<div class="small text-muted"><code>' + escapeHtml(it.batch_no) + '</code> | Exp: ' + (it.exp_formatted || it.expiry_date) + ' | Pack: ' + it.packing + '</div>' +
                            '</div>' +
                            '<span class="badge badge-rack">R: ' + (it.rack_no || '-') + ' | S: ' + (it.shelf_no || '-') + '</span>' +
                        '</div>' +

                        '<div class="row g-2 align-items-center mt-1 pt-2 border-top">' +
                            '<div class="col-4 text-center">' +
                                '<div class="text-muted small" style="font-size:11px;">System Book</div>' +
                                '<div class="fw-bold text-dark fs-6" id="sys_' + it.batch_id + '">' + it.system_qty + '</div>' +
                            '</div>' +
                            '<div class="col-4 text-center">' +
                                '<div class="text-muted small" style="font-size:11px;">Physical Count</div>' +
                                '<input type="number" class="form-control form-control-sm text-center fw-bold text-primary" id="' + physicalInputId + '" value="' + prefillCount + '" oninput="calcAuditDiff(' + it.batch_id + ');">' +
                            '</div>' +
                            '<div class="col-4 text-center">' +
                                '<div class="text-muted small" style="font-size:11px;">Variance</div>' +
                                '<div class="fw-bold fs-6" id="' + diffId + '"><span class="badge bg-success">0 OK</span></div>' +
                            '</div>' +
                        '</div>' +

                        '<div class="row g-2 mt-1">' +
                            '<div class="col-6">' +
                                '<select class="form-select form-select-sm" id="' + reasonId + '" style="font-size:11px;">' +
                                    '<option value="OK">Matched (OK)</option>' +
                                    '<option value="PHYSICAL_VARIATION">Count Variance</option>' +
                                    '<option value="DAMAGED">Damaged / Broken</option>' +
                                    '<option value="EXPIRED">Expired on Shelf</option>' +
                                    '<option value="SHORTAGE">Missing / Shortage</option>' +
                                    '<option value="SURPLUS">Found Surplus</option>' +
                                '</select>' +
                            '</div>' +
                            '<div class="col-6">' +
                                '<input type="text" class="form-control form-control-sm" id="' + remarksId + '" placeholder="Remarks (optional)" style="font-size:11px;">' +
                            '</div>' +
                        '</div>' +

                        '<div class="mt-2 text-end">' +
                            '<button type="button" class="btn btn-sm btn-success py-1 px-3 fw-bold" onclick="saveAuditRow(' + it.batch_id + ', ' + it.item_code + ', \'' + escapeQuotes(it.rack_no) + '\', \'' + escapeQuotes(it.shelf_no) + '\');">' +
                                '<i class="bi bi-check-lg"></i> Save Count' +
                            '</button>' +
                        '</div>' +
                    '</div>';
                });

                container.html(html);
                
                // Initialize diff calculations
                res.items.forEach(function(it) {
                    calcAuditDiff(it.batch_id);
                });
            }
        });
    }

    function calcAuditDiff(batchId) {
        var sys = parseInt(jQuery('#sys_' + batchId).text(), 10) || 0;
        var phys = parseInt(jQuery('#phys_' + batchId).val(), 10);
        if (isNaN(phys)) phys = 0;
        var diff = phys - sys;
        var diffDiv = jQuery('#diff_' + batchId);
        var reasonSelect = jQuery('#reason_' + batchId);

        if (diff === 0) {
            diffDiv.html('<span class="badge bg-success">0 OK</span>');
            reasonSelect.val('OK');
        } else if (diff < 0) {
            diffDiv.html('<span class="badge bg-danger">' + diff + ' Short</span>');
            if (reasonSelect.val() === 'OK') reasonSelect.val('SHORTAGE');
        } else {
            diffDiv.html('<span class="badge bg-primary">+' + diff + ' Extra</span>');
            if (reasonSelect.val() === 'OK') reasonSelect.val('SURPLUS');
        }
    }

    function saveAuditRow(batchId, itemCode, defaultRack, defaultShelf) {
        var sys = parseInt(jQuery('#sys_' + batchId).text(), 10) || 0;
        var phys = parseInt(jQuery('#phys_' + batchId).val(), 10) || 0;
        var auditType = jQuery('#reason_' + batchId).val();
        var remarks = jQuery('#rem_' + batchId).val().trim();
        var rackNo = jQuery('#audit_rack_input').val().trim() || defaultRack;
        var shelfNo = jQuery('#audit_shelf_input').val().trim() || defaultShelf;

        var payload = {
            batch_id: batchId,
            item_code: itemCode,
            system_qty: sys,
            physical_qty: phys,
            audit_type: auditType,
            remarks: remarks,
            rack_no: rackNo,
            shelf_no: shelfNo,
            audited_by_name: operatorName
        };
        payload[csrfTokenName] = csrfHash;

        jQuery.ajax({
            url: baseUrl + 'Medical/mobile_save_audit',
            type: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json',
            dataType: 'json',
            success: function(res) {
                if (res.status === 1) {
                    var card = jQuery('#audit_card_' + batchId);
                    card.css('border-color', '#22c55e').addClass('bg-success-subtle');
                    setTimeout(function() {
                        card.removeClass('bg-success-subtle');
                    }, 1200);
                    showToast("Count saved: " + res.status_tag + " (" + (res.variation_qty >= 0 ? "+" + res.variation_qty : res.variation_qty) + ")");
                } else {
                    alert(res.message || 'Error recording audit.');
                }
            },
            error: function(xhr, status, error) {
                var errDetail = "HTTP " + xhr.status + " (" + (xhr.statusText || error || 'Connection Error') + ")";
                alert("Failed to save audit count.\n\n" + errDetail);
            }
        });
    }

    // =========================================================================
    // 6. RECENT DRAFTS LIST
    // =========================================================================
    function loadRecentDrafts() {
        var container = jQuery('#recent_drafts_container');
        container.html('<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary"></div> Fetching today\'s drafts...</div>');

        jQuery.ajax({
            url: baseUrl + 'Medical/mobile_recent_drafts',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (!res.drafts || res.drafts.length === 0) {
                    container.html('<div class="text-center py-4 text-muted"><i class="bi bi-clock-history fs-3 d-block mb-1"></i> No drafts created from mobile today.</div>');
                    return;
                }

                var html = '';
                res.drafts.forEach(function(d) {
                    var isFinal = d.status === 'BILLED';
                    var statusBadge = isFinal 
                        ? '<span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check-circle-fill"></i> Billed</span>' 
                        : '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning"><i class="bi bi-hourglass-split"></i> At Counter</span>';

                    html += '<div class="medicine-card p-3 mb-2 shadow-sm">' +
                        '<div class="d-flex justify-content-between align-items-start mb-1">' +
                            '<div>' +
                                '<h6 class="fw-bold mb-0 text-dark">' + escapeHtml(d.patient_name) + '</h6>' +
                                '<div class="text-muted small">UHID: ' + escapeHtml(d.uhid) + ' | <span class="badge bg-light text-dark border">' + escapeHtml(d.ipd_tag) + '</span></div>' +
                            '</div>' +
                            '<div>' + statusBadge + '</div>' +
                        '</div>' +
                        '<div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top small text-muted">' +
                            '<span><code>' + escapeHtml(d.draft_code) + '</code> (' + d.total_items + ' items)</span>' +
                            '<span class="fw-bold text-dark fs-6">₹' + d.net_amount.toFixed(2) + '</span>' +
                            '<span><i class="bi bi-clock"></i> ' + escapeHtml(d.time_str) + '</span>' +
                        '</div>' +
                    '</div>';
                });

                container.html(html);
            },
            error: function(xhr, status, error) {
                var errDetail = "HTTP " + xhr.status + " (" + (xhr.statusText || error || 'Connection Error') + ")";
                container.html('<div class="text-center py-4 text-danger">' +
                    '<i class="bi bi-exclamation-triangle fs-3 d-block mb-1"></i>' +
                    'Failed to load recent drafts: ' + escapeHtml(errDetail) + '<br>' +
                    '<button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="loadRecentDrafts();">' +
                        '<i class="bi bi-arrow-repeat"></i> Retry' +
                    '</button>' +
                '</div>');
            }
        });
    }

    // =========================================================================
    // 7. BARCODE / QR SCANNER MODAL INTEGRATION
    // =========================================================================
    function openBarcodeScanner(target) {
        currentScannerTarget = target || 'universal';
        var modalTitle = 'Scan Barcode';
        if (target === 'patient') modalTitle = 'Scan Patient Parchi QR / Barcode';
        if (target === 'medicine') modalTitle = 'Scan Medicine Box / Strip Barcode';

        document.getElementById('scanner_modal_title').innerHTML = '<i class="bi bi-qr-code-scan me-1"></i> ' + modalTitle;

        var modal = new bootstrap.Modal(document.getElementById('scannerModal'));
        modal.show();

        setTimeout(startCameraScanner, 400);
    }

    function startCameraScanner() {
        if (!window.Html5Qrcode) {
            alert("Camera barcode library not available.");
            return;
        }

        stopBarcodeScanner();

        html5QrCodeScanner = new Html5Qrcode("scanner-reader");
        var config = { fps: 10, qrbox: { width: 250, height: 250 } };

        html5QrCodeScanner.start({ facingMode: "environment" }, config, onScanSuccess)
            .catch(function(err) {
                document.getElementById('scanner-reader').innerHTML = '<div class="alert alert-warning py-2 small">Camera permission denied or camera not found.<br>' + err + '</div>';
            });
    }

    function stopBarcodeScanner() {
        if (html5QrCodeScanner) {
            html5QrCodeScanner.stop().then(function() {
                html5QrCodeScanner.clear();
                html5QrCodeScanner = null;
            }).catch(function() {
                html5QrCodeScanner = null;
            });
        }
    }

    function onScanSuccess(decodedText) {
        stopBarcodeScanner();
        var modal = bootstrap.Modal.getInstance(document.getElementById('scannerModal'));
        if (modal) modal.hide();

        var scanned = decodedText.trim();
        showToast("Scanned: " + scanned);

        if (currentScannerTarget === 'patient' || currentScannerTarget === 'universal') {
            jQuery('#patient_search_input').val(scanned).trigger('input');
        } else if (currentScannerTarget === 'medicine') {
            jQuery('#med_search_input').val(scanned);
            searchMedicines();
        }
    }

    // Utility escape functions
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
        });
    }

    function escapeQuotes(str) {
        if (!str) return '';
        return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }
    </script>
</body>
</html>

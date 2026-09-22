<?php helper('common'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title><?= lang('Auth.login') ?></title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <link href="assets/img/logo.ico" rel="icon" type="image/x-icon">
    <link href="assets/img/favicon.png" rel="alternate icon" type="image/png">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
    <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/custom-theme-dark-borders.css" rel="stylesheet">

</head>

<body>

    <?php $footerVersion = hms_footer_version(); ?>

    <main>
        <div class="container">

            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

                            <div class="d-flex justify-content-center py-4">
                                <a href="/" class="logo d-flex align-items-center w-auto">
                                    <img src="assets/img/logo.png" alt="">
                                    <span class="d-none d-lg-block">E-Atria</span>
                                </a>
                            </div>

                            <div class="card mb-3">

                                <div class="card-body">

                                    <div class="pt-4 pb-2">
                                        <h5 class="card-title text-center pb-0 fs-4"><?= lang('Auth.loginTitle') ?></h5>
                                        <p class="text-center small"><?= lang('Auth.loginSubtitle') ?></p>
                                    </div>

                                    <?php if (session('error') !== null) : ?>
                                        <div class="alert alert-danger" role="alert"><?= esc(session('error')) ?></div>
                                    <?php elseif (session('errors') !== null) : ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?php if (is_array(session('errors'))) : ?>
                                                <?php foreach (session('errors') as $error) : ?>
                                                    <?= esc($error) ?>
                                                    <br>
                                                <?php endforeach ?>
                                            <?php else : ?>
                                                <?= esc(session('errors')) ?>
                                            <?php endif ?>
                                        </div>
                                    <?php endif ?>

                                    <?php if (session('message') !== null) : ?>
                                        <div class="alert alert-success" role="alert"><?= esc(session('message')) ?></div>
                                    <?php endif ?>

                                    <form action="<?= url_to('login') ?>" method="post" class="row g-3 needs-validation" novalidate>
                                        <?= csrf_field() ?>

                                        <div class="col-12">
                                            <label for="floatingUsernameInput"><?= lang('Auth.username') ?></label>
                                            <div class="input-group has-validation">
                                                <span class="input-group-text" id="inputGroupPrepend">@</span>
                                                <input type="text" class="form-control" id="floatingUsernameInput" name="username" inputmode="text" autocomplete="username" placeholder="<?= lang('Auth.username') ?>" value="<?= old('username') ?>" required>
                                                <div class="invalid-feedback">Please enter your username.</div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label for="floatingPasswordInput"><?= lang('Auth.password') ?></label>
                                            <input type="password" name="password" class="form-control" id="floatingPasswordInput" autocomplete="current-password" required>
                                            <div class="invalid-feedback">Please enter your password!</div>
                                        </div>

                                        <?php if (setting('Auth.sessionConfig')['allowRemembering']) : ?>
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input type="checkbox" name="remember" class="form-check-input" <?php if (old('remember')) : ?> checked<?php endif ?>>
                                                    <label class="form-check-label" for="rememberMe"><?= lang('Auth.rememberMe') ?></label>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary w-100"><?= lang('Auth.login') ?></button>
                                        </div>

                                        <?php if (setting('Auth.allowMagicLinkLogins')) : ?>
                                            <div class="col-12">
                                                <p class="text-center"><?= lang('Auth.forgotPassword') ?> <a href="<?= url_to('magic-link') ?>"><?= lang('Auth.useMagicLink') ?></a></p>
                                            </div>
                                        <?php endif ?>

                                        <!-- Mobile PWA Quick Scan Button inside Login Card -->
                                        <hr class="my-2 opacity-25">
                                        <div class="col-12 text-center">
                                            <button type="button" class="btn btn-outline-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2 fw-semibold shadow-sm" id="btn_open_mobile_qr_modal" data-bs-toggle="modal" data-bs-target="#loginMobileAppsModal" style="border-radius: 8px;">
                                                <i class="bi bi-qr-code-scan fs-5"></i>
                                                <span>Scan QR for Mobile Apps (PWA)</span>
                                            </button>
                                            <div class="small text-muted mt-1" style="font-size: 11px;">
                                                <i class="bi bi-phone me-1"></i> Scan with phone camera &bull; Install to home screen
                                            </div>
                                        </div>

                                    </form>

                                </div>
                            </div>

                            <!-- Mobile Application QR & PWA Installation Card -->
                            <div class="card border-0 shadow-sm mt-1 mb-2 w-100 text-center" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 12px; color: white;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="badge bg-primary px-2 py-1" style="font-size: 10px;"><i class="bi bi-phone me-1"></i> Mobile PWA Suite</span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;"><i class="bi bi-download me-1"></i> Installable Apps</span>
                                    </div>
                                    <h6 class="fw-bold text-white mb-1" style="font-size: 14px;"><i class="bi bi-qr-code-scan me-1 text-info"></i> Hospital Mobile Apps &amp; QR Codes</h6>
                                    <p class="text-light small mb-2 opacity-75" style="font-size: 11px; line-height: 1.4;">
                                        Instant home-screen installation on Android &amp; iPhone. Scan QR code to launch Nursing, Doctor, Pharmacy POS &amp; Token Queue apps.
                                    </p>
                                    <button type="button" class="btn btn-primary btn-sm w-100 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm py-2" data-bs-toggle="modal" data-bs-target="#loginMobileAppsModal" style="border-radius: 8px;">
                                        <i class="bi bi-qr-code"></i> View All Mobile Apps &amp; QR Codes
                                    </button>
                                </div>
                            </div>

                            <div class="text-center mt-1 mb-3">
                                <a href="<?= base_url('HMS_FEATURES_PRESENTATION.html') ?>" target="_blank" rel="noopener"
                                   class="text-muted small d-inline-flex align-items-center gap-1" style="text-decoration:none;">
                                    <i class="bi bi-file-earmark-text"></i> View HMS Features &amp; Capabilities
                                </a>
                            </div>

                        </div>
                    </div>
                </div>

            </section>

        </div>
    </main>

    <?php
    // Detect server network interfaces for LAN QR generation
    $detectedNetworkInterfaces = [];
    if (function_exists('shell_exec')) {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $netOutput = @shell_exec('ipconfig');
            if ($netOutput) {
                $netLines = explode("\n", $netOutput);
                $curAdapter = '';
                foreach ($netLines as $nLine) {
                    $nLine = trim($nLine);
                    if (preg_match('/^(adapter|Wireless LAN adapter|Ethernet adapter|Unknown adapter)\s+([^:]+):/i', $nLine, $adM)) {
                        $curAdapter = trim($adM[2]);
                    }
                    if (preg_match('/IPv4 Address[ .:]+([0-9.]+)/i', $nLine, $ipM)) {
                        $foundIp = trim($ipM[1]);
                        if ($foundIp !== '127.0.0.1') {
                            $label = $curAdapter ? ($foundIp . ' (' . $curAdapter . ')') : $foundIp;
                            $detectedNetworkInterfaces[$foundIp] = $label;
                        }
                    }
                }
            }
        } else {
            $netOutput = @shell_exec("hostname -I 2>/dev/null");
            if ($netOutput) {
                $parts = preg_split('/\s+/', trim($netOutput));
                foreach ($parts as $foundIp) {
                    $foundIp = trim($foundIp);
                    if ($foundIp && $foundIp !== '127.0.0.1') {
                        $detectedNetworkInterfaces[$foundIp] = $foundIp . ' (LAN)';
                    }
                }
            }
        }
    }

    $serverPort = $_SERVER['SERVER_PORT'] ?? '8080';
    $httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
    $requestScheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

    // Best default IP: Wi-Fi if present, else first LAN IP, else current host
    $defaultIp = '';
    foreach ($detectedNetworkInterfaces as $ipKey => $ipLabel) {
        if (stripos($ipLabel, 'Wi-Fi') !== false || stripos($ipLabel, 'Wireless') !== false) {
            $defaultIp = $ipKey;
            break;
        }
    }
    if (!$defaultIp && !empty($detectedNetworkInterfaces)) {
        $defaultIp = array_key_first($detectedNetworkInterfaces);
    }
    if (!$defaultIp) {
        $defaultIp = explode(':', $httpHost)[0];
    }
    $defaultHost = ($defaultIp === 'localhost' || $defaultIp === '127.0.0.1') ? $httpHost : ($defaultIp . ':' . $serverPort);
    ?>

    <!-- ========================================================================= -->
    <!-- MODAL: All Hospital Mobile Applications & QR Codes Hub (PWA Installation) -->
    <!-- ========================================================================= -->
    <div class="modal fade" id="loginMobileAppsModal" tabindex="-1" aria-labelledby="loginMobileAppsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
                
                <!-- Modal Header -->
                <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                    <div class="d-flex align-items-center gap-3">
                        <img src="<?= base_url('assets/img/logo.png') ?>" alt="HMS Logo" style="height: 36px; width: auto; filter: brightness(0) invert(1);" onError="this.style.display='none'">
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white d-flex align-items-center gap-2" id="loginMobileAppsModalLabel">
                                <i class="bi bi-phone text-info"></i> Hospital Mobile Applications &amp; QR Codes Hub
                            </h5>
                            <span class="small text-light opacity-75" style="font-size: 12px;">
                                Progressive Web Applications (PWA) &bull; Native Home Screen Installation Type &bull; No App Store Required
                            </span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-light btn-sm fw-semibold d-none d-md-flex align-items-center gap-1" id="btn_print_qr_sheet" onclick="printAllMobileQRs()">
                            <i class="bi bi-printer"></i> Print Counter Poster
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-4" style="background: #f8fafc;">

                    <!-- Network & Wi-Fi IP Selector Strip -->
                    <div class="card border-0 shadow-sm mb-4" style="background: #ffffff; border-radius: 12px; border-left: 4px solid #2563eb !important;">
                        <div class="card-body p-3">
                            <div class="row align-items-center g-2">
                                <div class="col-lg-5 col-md-12">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-wifi fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark small">Hospital Wi-Fi / LAN IP for Mobile QR:</div>
                                            <div class="text-muted" style="font-size: 11px;">
                                                Phones on hospital Wi-Fi connect directly using this IP address.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-7">
                                    <select class="form-select form-select-sm fw-bold border-primary" id="mobile_qr_host_select" onchange="onMobileQrHostChanged(this.value)">
                                        <?php if (!empty($detectedNetworkInterfaces)): ?>
                                            <?php foreach ($detectedNetworkInterfaces as $ipVal => $ipDesc): ?>
                                                <?php $isDef = ($ipVal === $defaultIp); ?>
                                                <option value="<?= esc($ipVal . ':' . $serverPort) ?>" <?= $isDef ? 'selected' : '' ?>>
                                                    📶 <?= esc($ipDesc) ?>:<?= esc($serverPort) ?> <?= $isDef ? '★ (Recommended)' : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <option value="<?= esc($httpHost) ?>">💻 Browser Host (<?= esc($httpHost) ?>)</option>
                                        <option value="custom">✏️ Custom Domain / IP Address...</option>
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-5">
                                    <div class="input-group input-group-sm" id="custom_host_input_group" style="display: none;">
                                        <input type="text" class="form-control" id="custom_host_input" placeholder="e.g. 192.168.1.50:8080" value="<?= esc($defaultHost) ?>">
                                        <button class="btn btn-primary" type="button" onclick="applyCustomHost()">Apply</button>
                                    </div>
                                    <div id="selected_host_badge" class="text-end text-success small fw-bold" style="font-size: 11px;">
                                        <i class="bi bi-check-circle-fill me-1"></i> QR Target: <span id="current_qr_host_display"><?= esc($defaultHost) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Tabs: All QR Grid vs Single App vs PWA Installation Guide -->
                    <ul class="nav nav-pills mb-3 gap-2" id="mobileAppsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold px-3 py-2" id="tab-all-apps-grid" data-bs-toggle="pill" data-bs-target="#pane-all-apps-grid" type="button" role="tab" aria-controls="pane-all-apps-grid" aria-selected="true">
                                <i class="bi bi-grid-fill me-1"></i> All Mobile QR Codes (5 Apps Grid)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold px-3 py-2" id="tab-single-focus" data-bs-toggle="pill" data-bs-target="#pane-single-focus" type="button" role="tab" aria-controls="pane-single-focus" aria-selected="false">
                                <i class="bi bi-aspect-ratio me-1"></i> Large QR Focus View
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold px-3 py-2 bg-white text-dark border" id="tab-install-guide" data-bs-toggle="pill" data-bs-target="#pane-install-guide" type="button" role="tab" aria-controls="pane-install-guide" aria-selected="false">
                                <i class="bi bi-download text-primary me-1"></i> How to Install App on Mobile (PWA Guide)
                            </button>
                        </li>
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content" id="mobileAppsTabContent">

                        <!-- TAB 1: ALL APPS QR GRID -->
                        <div class="tab-pane fade show active" id="pane-all-apps-grid" role="tabpanel" aria-labelledby="tab-all-apps-grid">
                            
                            <div class="row g-3" id="all_apps_qr_container">

                                <!-- 1. Unified Mobile Hub (All-in-One) -->
                                <div class="col-xl-4 col-md-6">
                                    <div class="card h-100 border shadow-sm p-3 app-qr-card" style="border-radius: 14px; background: #ffffff; border-top: 4px solid #0f172a !important;">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="badge bg-dark text-white px-2 py-1 small">🌟 Unified Hub</span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;">PWA Installable</span>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                            <i class="bi bi-phone-fill text-primary"></i> HMS Mobile Apps Hub
                                        </h6>
                                        <p class="text-muted small mb-2" style="font-size: 11px; min-height: 32px;">
                                            All-in-one hospital portal. Single home screen installation for Doctor, Nurse, Pharmacy &amp; Queue.
                                        </p>
                                        <div class="text-center my-2 p-2 bg-light rounded border">
                                            <img id="qr_img_hub" class="img-fluid bg-white p-1 rounded shadow-sm border app-qr-image" src="" alt="HMS Apps Hub QR" style="width: 170px; height: 170px; object-fit: contain;">
                                            <div class="mt-1 small fw-semibold text-secondary" style="font-size: 10px;" id="qr_url_txt_hub">/app</div>
                                        </div>
                                        <div class="d-flex gap-1 mt-auto pt-2">
                                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" onclick="copyAppUrl('hub')" title="Copy URL">
                                                <i class="bi bi-clipboard"></i> Copy Link
                                            </button>
                                            <a id="qr_link_hub" href="/app" target="_blank" class="btn btn-primary btn-sm flex-fill fw-semibold">
                                                <i class="bi bi-box-arrow-up-right"></i> Open on PC
                                            </a>
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="focusAppView('hub')" title="Zoom View">
                                                <i class="bi bi-zoom-in"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Medical Store & Multi-Building Pharmacy POS -->
                                <div class="col-xl-4 col-md-6">
                                    <div class="card h-100 border shadow-sm p-3 app-qr-card" style="border-radius: 14px; background: #ffffff; border-top: 4px solid #0891b2 !important;">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="badge text-white px-2 py-1 small" style="background: #0891b2;">💊 Pharmacy &amp; POS</span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;">PWA Installable</span>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                            <i class="bi bi-capsule" style="color: #0891b2;"></i> Medical Store Companion
                                        </h6>
                                        <p class="text-muted small mb-2" style="font-size: 11px; min-height: 32px;">
                                            Rack/Shelf Draft Bill Collector, Stock Shelf Auditor, Inward Bill Camera &amp; Expiry Monitor.
                                        </p>
                                        <div class="text-center my-2 p-2 bg-light rounded border">
                                            <img id="qr_img_pharmacy" class="img-fluid bg-white p-1 rounded shadow-sm border app-qr-image" src="" alt="Pharmacy Companion QR" style="width: 170px; height: 170px; object-fit: contain;">
                                            <div class="mt-1 small fw-semibold text-secondary" style="font-size: 10px;" id="qr_url_txt_pharmacy">/MedicalStore</div>
                                        </div>
                                        <div class="d-flex gap-1 mt-auto pt-2">
                                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" onclick="copyAppUrl('pharmacy')" title="Copy URL">
                                                <i class="bi bi-clipboard"></i> Copy Link
                                            </button>
                                            <a id="qr_link_pharmacy" href="/MedicalStore" target="_blank" class="btn btn-info text-white btn-sm flex-fill fw-semibold" style="background: #0891b2; border-color: #0891b2;">
                                                <i class="bi bi-box-arrow-up-right"></i> Open on PC
                                            </a>
                                            <button type="button" class="btn btn-outline-info btn-sm" onclick="focusAppView('pharmacy')" title="Zoom View">
                                                <i class="bi bi-zoom-in"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. Nursing Care Bedside Mobile PWA -->
                                <div class="col-xl-4 col-md-6">
                                    <div class="card h-100 border shadow-sm p-3 app-qr-card" style="border-radius: 14px; background: #ffffff; border-top: 4px solid #2563eb !important;">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="badge bg-primary text-white px-2 py-1 small">❤️ IPD Nursing</span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;">PWA Installable</span>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                            <i class="bi bi-heart-pulse-fill text-primary"></i> Nursing Care Bedside App
                                        </h6>
                                        <p class="text-muted small mb-2" style="font-size: 11px; min-height: 32px;">
                                            IPD Bedside Vitals (BP, SpO2, Pulse), Intake/Output Charting &amp; Paper Document Camera Scanner.
                                        </p>
                                        <div class="text-center my-2 p-2 bg-light rounded border">
                                            <img id="qr_img_nursing" class="img-fluid bg-white p-1 rounded shadow-sm border app-qr-image" src="" alt="Nursing Care QR" style="width: 170px; height: 170px; object-fit: contain;">
                                            <div class="mt-1 small fw-semibold text-secondary" style="font-size: 10px;" id="qr_url_txt_nursing">/app/nursing</div>
                                        </div>
                                        <div class="d-flex gap-1 mt-auto pt-2">
                                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" onclick="copyAppUrl('nursing')" title="Copy URL">
                                                <i class="bi bi-clipboard"></i> Copy Link
                                            </button>
                                            <a id="qr_link_nursing" href="/app/nursing" target="_blank" class="btn btn-primary btn-sm flex-fill fw-semibold">
                                                <i class="bi bi-box-arrow-up-right"></i> Open on PC
                                            </a>
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="focusAppView('nursing')" title="Zoom View">
                                                <i class="bi bi-zoom-in"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- 4. DoctorCare EMR & Rounds PWA -->
                                <div class="col-xl-4 col-md-6">
                                    <div class="card h-100 border shadow-sm p-3 app-qr-card" style="border-radius: 14px; background: #ffffff; border-top: 4px solid #059669 !important;">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="badge text-white px-2 py-1 small" style="background: #059669;">🩺 Doctor EMR</span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;">PWA Installable</span>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                            <i class="bi bi-person-badge-fill text-success"></i> DoctorCare Rounds &amp; EMR
                                        </h6>
                                        <p class="text-muted small mb-2" style="font-size: 11px; min-height: 32px;">
                                            Doctor Ward Rounds, Clinical Notes, OPD Prescriptions, Lab Investigation &amp; Vitals review.
                                        </p>
                                        <div class="text-center my-2 p-2 bg-light rounded border">
                                            <img id="qr_img_doctor" class="img-fluid bg-white p-1 rounded shadow-sm border app-qr-image" src="" alt="Doctor EMR QR" style="width: 170px; height: 170px; object-fit: contain;">
                                            <div class="mt-1 small fw-semibold text-secondary" style="font-size: 10px;" id="qr_url_txt_doctor">/app/doctor</div>
                                        </div>
                                        <div class="d-flex gap-1 mt-auto pt-2">
                                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" onclick="copyAppUrl('doctor')" title="Copy URL">
                                                <i class="bi bi-clipboard"></i> Copy Link
                                            </button>
                                            <a id="qr_link_doctor" href="/app/doctor" target="_blank" class="btn btn-success btn-sm flex-fill fw-semibold" style="background: #059669; border-color: #059669;">
                                                <i class="bi bi-box-arrow-up-right"></i> Open on PC
                                            </a>
                                            <button type="button" class="btn btn-outline-success btn-sm" onclick="focusAppView('doctor')" title="Zoom View">
                                                <i class="bi bi-zoom-in"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- 5. OPD Token Tracker TV Display -->
                                <div class="col-xl-4 col-md-6">
                                    <div class="card h-100 border shadow-sm p-3 app-qr-card" style="border-radius: 14px; background: #ffffff; border-top: 4px solid #d97706 !important;">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="badge text-white px-2 py-1 small" style="background: #d97706;">📺 Token Display</span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 10px;">PWA Installable</span>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                            <i class="bi bi-display-fill text-warning"></i> OPD Token Tracker Display
                                        </h6>
                                        <p class="text-muted small mb-2" style="font-size: 11px; min-height: 32px;">
                                            Waiting Room Token Calling Screen, Live Room Numbers, TTS Audio Voice &amp; TV display mode.
                                        </p>
                                        <div class="text-center my-2 p-2 bg-light rounded border">
                                            <img id="qr_img_queue" class="img-fluid bg-white p-1 rounded shadow-sm border app-qr-image" src="" alt="OPD Queue QR" style="width: 170px; height: 170px; object-fit: contain;">
                                            <div class="mt-1 small fw-semibold text-secondary" style="font-size: 10px;" id="qr_url_txt_queue">/app/queue</div>
                                        </div>
                                        <div class="d-flex gap-1 mt-auto pt-2">
                                            <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" onclick="copyAppUrl('queue')" title="Copy URL">
                                                <i class="bi bi-clipboard"></i> Copy Link
                                            </button>
                                            <a id="qr_link_queue" href="/app/queue" target="_blank" class="btn btn-warning btn-sm flex-fill fw-semibold text-dark">
                                                <i class="bi bi-box-arrow-up-right"></i> Open on PC
                                            </a>
                                            <button type="button" class="btn btn-outline-warning btn-sm text-dark" onclick="focusAppView('queue')" title="Zoom View">
                                                <i class="bi bi-zoom-in"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- 6. Standee / Print Quick Banner Card -->
                                <div class="col-xl-4 col-md-6">
                                    <div class="card h-100 border-0 shadow-sm p-4 text-center d-flex flex-column align-items-center justify-content-center" style="border-radius: 14px; background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white;">
                                        <i class="bi bi-printer text-info fs-1 mb-2"></i>
                                        <h6 class="fw-bold text-white mb-1">Print Counter QR Posters</h6>
                                        <p class="small text-light opacity-75 mb-3" style="font-size: 11px;">
                                            Print clean acrylic standees / posters for pharmacy rack, nursing station, or OPD desks.
                                        </p>
                                        <button type="button" class="btn btn-light btn-sm fw-bold px-4 py-2 text-dark shadow-sm" onclick="printAllMobileQRs()">
                                            <i class="bi bi-printer me-1"></i> Print All 5 QR Codes
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- TAB 2: SINGLE APP FOCUS VIEW -->
                        <div class="tab-pane fade" id="pane-single-focus" role="tabpanel" aria-labelledby="tab-single-focus">
                            <div class="row g-4 align-items-center justify-content-center py-2">
                                <div class="col-lg-5 col-md-6 text-center">
                                    <div class="p-3 bg-white rounded-4 shadow-sm border d-inline-block">
                                        <img id="focus_qr_img" src="" alt="Focused QR" style="width: 250px; height: 250px; object-fit: contain;" class="rounded border p-2 bg-white">
                                    </div>
                                    <div class="mt-2 text-muted small" style="font-size: 11px;">
                                        <i class="bi bi-camera me-1"></i> Point phone camera to scan
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small text-secondary">SELECT APPLICATION:</label>
                                        <select class="form-select form-select-lg fw-bold" id="focus_app_select" onchange="renderFocusApp(this.value)">
                                            <option value="hub">🌟 HMS Mobile Apps Hub (All-in-One)</option>
                                            <option value="pharmacy">💊 Medical Store &amp; Multi-Building Pharmacy POS</option>
                                            <option value="nursing">❤️ Nursing Care Bedside Assistant</option>
                                            <option value="doctor">🩺 DoctorCare EMR &amp; Rounds</option>
                                            <option value="queue">📺 OPD Token Tracker &amp; TV Queue Display</option>
                                        </select>
                                    </div>

                                    <div class="card border p-3 mb-3 bg-white" style="border-radius: 12px;">
                                        <h5 class="fw-bold text-dark mb-1" id="focus_app_title">HMS Mobile Apps Hub</h5>
                                        <span class="badge bg-primary mb-2 align-self-start" id="focus_app_badge">Active PWA &bull; Installation Type</span>
                                        <p class="text-muted small mb-2" id="focus_app_desc" style="font-size: 12px;">
                                            Central launchpad with 1-click home screen install.
                                        </p>
                                        <div class="input-group input-group-sm mb-2">
                                            <input type="text" class="form-control text-primary font-monospace" id="focus_app_url" readonly>
                                            <button class="btn btn-outline-primary" type="button" onclick="copyFocusUrl()">
                                                <i class="bi bi-clipboard"></i> Copy
                                            </button>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <a id="focus_app_launch" href="#" target="_blank" class="btn btn-primary btn-sm flex-fill fw-bold py-2">
                                            <i class="bi bi-box-arrow-up-right me-1"></i> Open on Computer
                                        </a>
                                        <button type="button" class="btn btn-outline-dark btn-sm fw-bold py-2" onclick="printSingleQR()">
                                            <i class="bi bi-printer me-1"></i> Print QR Standee
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: PWA INSTALLATION GUIDE -->
                        <div class="tab-pane fade" id="pane-install-guide" role="tabpanel" aria-labelledby="tab-install-guide">
                            <div class="row g-3">
                                
                                <!-- Android Instructions -->
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm p-3" style="border-radius: 14px; background: #ffffff; border-top: 4px solid #10b981 !important;">
                                        <div class="d-flex align-items-center gap-2 mb-3">
                                            <div class="bg-success-subtle text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                                <i class="bi bi-android2 fs-4"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold text-dark mb-0">Android Installation (Chrome / Edge)</h6>
                                                <small class="text-muted">Samsung Galaxy, OnePlus, Xiaomi, Vivo, Oppo, Google Pixel</small>
                                            </div>
                                        </div>
                                        <ol class="small text-secondary ps-3 mb-0" style="line-height: 1.8;">
                                            <li><strong>Scan QR Code:</strong> Open your smartphone camera or Google Lens and scan the QR code. Tap the link to open in Google Chrome.</li>
                                            <li><strong>Tap Install Banner:</strong> An <em>"Install HMS App"</em> or <em>"Add to Home Screen"</em> banner will automatically pop up at the bottom of the screen. Tap it!</li>
                                            <li><strong>Alternative Menu:</strong> If no banner appears, tap the <strong>three dots menu (⋮)</strong> at the top right of Chrome, and select <strong>"Install app"</strong> or <strong>"Add to Home screen"</strong>.</li>
                                            <li><strong>Finished:</strong> The app icon will be installed directly on your Android home screen. It operates in full-screen standalone mode without any browser URL bar!</li>
                                        </ol>
                                    </div>
                                </div>

                                <!-- iPhone / iPad Instructions -->
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm p-3" style="border-radius: 14px; background: #ffffff; border-top: 4px solid #3b82f6 !important;">
                                        <div class="d-flex align-items-center gap-2 mb-3">
                                            <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                                                <i class="bi bi-apple fs-4"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold text-dark mb-0">iPhone &amp; iPad Installation (Safari)</h6>
                                                <small class="text-muted">Apple iOS 14+ Devices</small>
                                            </div>
                                        </div>
                                        <ol class="small text-secondary ps-3 mb-0" style="line-height: 1.8;">
                                            <li><strong>Scan QR Code:</strong> Open the built-in <strong>Camera app</strong> on your iPhone or iPad, point it at the QR code, and tap the yellow link notification to open in <strong>Safari</strong>.</li>
                                            <li><strong>Tap Share Button:</strong> At the bottom of Safari, tap the <strong>Share</strong> button (a square icon with an arrow pointing upwards <i class="bi bi-box-arrow-up"></i>).</li>
                                            <li><strong>Add to Home Screen:</strong> Scroll down the share sheet and tap <strong>"Add to Home Screen"</strong> (<i class="bi bi-plus-square"></i>).</li>
                                            <li><strong>Tap Add:</strong> In the top-right corner, tap <strong>"Add"</strong>. The HMS app icon is instantly created on your home screen and launches standalone!</li>
                                        </ol>
                                    </div>
                                </div>

                                <!-- Why Installation Type (PWA) Benefits -->
                                <div class="col-12">
                                    <div class="card border-0 shadow-sm p-3 bg-light" style="border-radius: 12px;">
                                        <div class="row align-items-center g-2 text-center text-md-start">
                                            <div class="col-md-3 text-center">
                                                <span class="badge bg-primary-subtle text-primary px-3 py-2 fs-6 rounded-pill">
                                                    <i class="bi bi-stars me-1"></i> PWA Technology
                                                </span>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="fw-bold text-dark small mb-1">Why Progressive Web Apps (PWA)?</div>
                                                <div class="text-muted small" style="font-size: 11px;">
                                                    &bull; <strong>Zero App Store Setup:</strong> No Apple App Store or Google Play Store accounts needed.<br>
                                                    &bull; <strong>Always Up-to-Date:</strong> Whenever hospital software updates, all mobile devices automatically receive latest features on reload.<br>
                                                    &bull; <strong>High Security &amp; Speed:</strong> Runs over hospital local Wi-Fi intranet with instant barcode/camera scanning and offline caching.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="modal-footer bg-white py-2 px-4 border-top d-flex align-items-center justify-content-between">
                    <span class="small text-muted" style="font-size: 11px;">
                        <i class="bi bi-shield-check text-success me-1"></i> Hospital Intranet &bull; TLS / SSL &bull; Instant Mobile Installation
                    </span>
                    <button type="button" class="btn btn-secondary btn-sm px-4 fw-bold" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>

    <!-- Hidden Container for Printing QR Posters -->
    <div id="print_qr_sheet_container" style="display: none;"></div>

    <script>
    (function() {
        // App definitions
        var APP_DEFS = {
            hub: {
                title: 'HMS Mobile Apps Hub',
                path: '/app',
                badge: 'Unified Portal • Active PWA',
                color: '#0f172a',
                desc: 'All-in-one hospital mobile portal. Single home screen install for Doctor, Nurse, Pharmacy POS & Token Queue.'
            },
            pharmacy: {
                title: 'Medical Store & Pharmacy POS',
                path: '/MedicalStore',
                badge: 'Pharmacy POS • Active PWA',
                color: '#0891b2',
                desc: 'Rack/Shelf Draft Bill Collector, Stock Shelf Barcode Auditor, Inward Purchase Bill Camera & Expiry Telemetry.'
            },
            nursing: {
                title: 'Nursing Care Bedside App',
                path: '/app/nursing',
                badge: 'IPD Nursing • Active PWA',
                color: '#2563eb',
                desc: 'IPD Bedside Vitals (BP, SpO2, Pulse), Intake/Output Charting & Paper Document Camera Scanner.'
            },
            doctor: {
                title: 'DoctorCare Rounds & EMR',
                path: '/app/doctor',
                badge: 'Doctor EMR • Active PWA',
                color: '#059669',
                desc: 'Doctor Ward Rounds, Clinical Notes, OPD Prescriptions, Lab Investigation & Diagnostic review.'
            },
            queue: {
                title: 'OPD Token Tracker TV Display',
                path: '/app/queue',
                badge: 'Token Screen • Active PWA',
                color: '#d97706',
                desc: 'Waiting Room Token Calling Screen, Live Room Numbers, TTS Audio Voice Announcements & TV display mode.'
            }
        };

        var currentHost = '<?= esc($defaultHost) ?>';
        var currentScheme = window.location.protocol ? window.location.protocol.replace(':', '') : 'http';

        function getFullAppUrl(appKey) {
            var path = APP_DEFS[appKey] ? APP_DEFS[appKey].path : '/app';
            return currentScheme + '://' + currentHost + path;
        }

        function getQrImgSrc(url) {
            return 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&margin=1&data=' + encodeURIComponent(url);
        }

        function refreshAllQrCodes() {
            var displaySpan = document.getElementById('current_qr_host_display');
            if (displaySpan) displaySpan.textContent = currentHost;

            Object.keys(APP_DEFS).forEach(function(key) {
                var appUrl = getFullAppUrl(key);
                var qrSrc = getQrImgSrc(appUrl);

                var imgEl = document.getElementById('qr_img_' + key);
                var linkEl = document.getElementById('qr_link_' + key);
                var txtEl = document.getElementById('qr_url_txt_' + key);

                if (imgEl) imgEl.src = qrSrc;
                if (linkEl) linkEl.href = appUrl;
                if (txtEl) txtEl.textContent = appUrl;
            });

            // Update focused view if active
            var focusSelect = document.getElementById('focus_app_select');
            if (focusSelect) {
                renderFocusApp(focusSelect.value);
            }
        }

        window.onMobileQrHostChanged = function(val) {
            var customGroup = document.getElementById('custom_host_input_group');
            if (val === 'custom') {
                if (customGroup) customGroup.style.display = 'flex';
                var inp = document.getElementById('custom_host_input');
                if (inp) currentHost = inp.value.trim() || window.location.host;
            } else {
                if (customGroup) customGroup.style.display = 'none';
                currentHost = val;
            }
            refreshAllQrCodes();
        };

        window.applyCustomHost = function() {
            var inp = document.getElementById('custom_host_input');
            if (inp && inp.value.trim()) {
                currentHost = inp.value.trim();
                refreshAllQrCodes();
            }
        };

        window.copyAppUrl = function(appKey) {
            var url = getFullAppUrl(appKey);
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    alert('Copied URL to clipboard: ' + url);
                });
            } else {
                var t = document.createElement('textarea');
                t.value = url;
                document.body.appendChild(t);
                t.select();
                document.execCommand('copy');
                document.body.removeChild(t);
                alert('Copied URL to clipboard: ' + url);
            }
        };

        window.focusAppView = function(appKey) {
            var focusTabBtn = document.getElementById('tab-single-focus');
            var select = document.getElementById('focus_app_select');
            if (select) select.value = appKey;
            if (focusTabBtn && window.bootstrap && window.bootstrap.Tab) {
                window.bootstrap.Tab.getOrCreateInstance(focusTabBtn).show();
            }
            renderFocusApp(appKey);
        };

        window.renderFocusApp = function(appKey) {
            var def = APP_DEFS[appKey] || APP_DEFS.hub;
            var appUrl = getFullAppUrl(appKey);
            var qrSrc = getQrImgSrc(appUrl);

            var img = document.getElementById('focus_qr_img');
            var title = document.getElementById('focus_app_title');
            var badge = document.getElementById('focus_app_badge');
            var desc = document.getElementById('focus_app_desc');
            var urlInp = document.getElementById('focus_app_url');
            var launch = document.getElementById('focus_app_launch');

            if (img) img.src = qrSrc;
            if (title) title.textContent = def.title;
            if (badge) badge.textContent = def.badge;
            if (desc) desc.textContent = def.desc;
            if (urlInp) urlInp.value = appUrl;
            if (launch) launch.href = appUrl;
        };

        window.copyFocusUrl = function() {
            var urlInp = document.getElementById('focus_app_url');
            if (urlInp && urlInp.value) {
                navigator.clipboard ? navigator.clipboard.writeText(urlInp.value) : document.execCommand('copy');
                alert('Copied URL to clipboard: ' + urlInp.value);
            }
        };

        window.printAllMobileQRs = function() {
            var printWin = window.open('', '_blank', 'width=900,height=800');
            if (!printWin) {
                alert('Please allow popups to print the QR Poster.');
                return;
            }

            var cardsHtml = '';
            Object.keys(APP_DEFS).forEach(function(k) {
                var def = APP_DEFS[k];
                var url = getFullAppUrl(k);
                var qrSrc = getQrImgSrc(url);

                cardsHtml += '<div style="border: 2px solid #334155; border-radius: 12px; padding: 16px; width: 260px; text-align: center; page-break-inside: avoid; background: #fff; margin: 10px; display: inline-block; vertical-align: top;">' +
                    '<div style="font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">' + def.title + '</div>' +
                    '<div style="font-size: 11px; color: #475569; margin-bottom: 10px;">' + def.badge + '</div>' +
                    '<img src="' + qrSrc + '" style="width: 180px; height: 180px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 4px;" alt="QR Code"/>' +
                    '<div style="font-size: 10px; font-weight: 700; color: #2563eb; margin-top: 8px; word-break: break-all;">' + url + '</div>' +
                    '<div style="font-size: 10px; color: #64748b; margin-top: 6px;">1. Connect Phone to Hospital Wi-Fi<br>2. Scan QR &amp; Tap "Add to Home Screen"</div>' +
                    '</div>';
            });

            var printContent = '<!DOCTYPE html><html><head><title>HMS Hospital Mobile Apps - Counter QR Poster</title>' +
                '<style>' +
                'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; padding: 20px; text-align: center; }' +
                '@media print { body { background: #fff; padding: 0; } }' +
                '</style></head><body>' +
                '<div style="margin-bottom: 16px;">' +
                '<h2 style="margin: 0; color: #0f172a;">Hospital Mobile Applications &amp; QR Directory</h2>' +
                '<p style="margin: 4px 0 0 0; color: #64748b; font-size: 13px;">Progressive Web Applications (PWA) &bull; Scan with Camera to Install on Mobile Home Screen</p>' +
                '</div>' +
                '<div>' + cardsHtml + '</div>' +
                '<script>setTimeout(function(){ window.print(); }, 800);<' + '/script>' +
                '</body></html>';

            printWin.document.open();
            printWin.document.write(printContent);
            printWin.document.close();
        };

        window.printSingleQR = function() {
            var select = document.getElementById('focus_app_select');
            var appKey = select ? select.value : 'hub';
            var def = APP_DEFS[appKey] || APP_DEFS.hub;
            var url = getFullAppUrl(appKey);
            var qrSrc = getQrImgSrc(url);

            var printWin = window.open('', '_blank', 'width=600,height=700');
            if (!printWin) {
                alert('Please allow popups to print the QR Standee.');
                return;
            }

            var printContent = '<!DOCTYPE html><html><head><title>' + def.title + ' - QR Standee</title>' +
                '<style>' +
                'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 40px 20px; text-align: center; background: #fff; }' +
                '.standee { border: 3px solid #0f172a; border-radius: 18px; padding: 30px; max-width: 420px; margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }' +
                '@media print { .standee { box-shadow: none; border-color: #000; } }' +
                '</style></head><body>' +
                '<div class="standee">' +
                '<h2 style="margin: 0 0 6px 0; color: #0f172a;">' + def.title + '</h2>' +
                '<div style="font-size: 13px; font-weight: bold; color: #2563eb; margin-bottom: 20px;">' + def.badge + '</div>' +
                '<img src="' + qrSrc + '" style="width: 260px; height: 260px; border: 2px solid #e2e8f0; border-radius: 12px; padding: 8px;" alt="QR Code"/>' +
                '<div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-top: 14px; word-break: break-all;">' + url + '</div>' +
                '<div style="margin-top: 16px; padding: 12px; background: #f1f5f9; border-radius: 8px; font-size: 12px; color: #334155; text-align: left;">' +
                '<strong>How to use on Phone:</strong><br>' +
                '1. Connect your phone to Hospital Wi-Fi.<br>' +
                '2. Open phone camera and scan this QR code.<br>' +
                '3. Tap "Add to Home Screen" to install application.' +
                '</div>' +
                '</div>' +
                '<script>setTimeout(function(){ window.print(); }, 800);<' + '/script>' +
                '</body></html>';

            printWin.document.open();
            printWin.document.write(printContent);
            printWin.document.close();
        };

        // Initialize when modal is shown
        document.addEventListener('DOMContentLoaded', function() {
            var modalEl = document.getElementById('loginMobileAppsModal');
            if (modalEl) {
                modalEl.addEventListener('show.bs.modal', function() {
                    refreshAllQrCodes();
                });
            }
            refreshAllQrCodes();
        });
    })();
    </script>

    <footer class="py-3 text-center text-muted small">
        &copy; 2017 - 2026 <strong><a href="https://www.e-atria.in" target="_blank" rel="noopener">E-Atria</a></strong>. All Rights Reserved |
        <a href="<?= base_url('software-use-license.html') ?>" target="_blank" rel="noopener">Software Use License</a> |
        Version: <?= esc($footerVersion) ?>
    </footer>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/chart.js/chart.umd.js"></script>
    <script src="assets/vendor/echarts/echarts.min.js"></script>
    <script src="assets/vendor/quill/quill.js"></script>
    <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
    <script src="assets/vendor/tinymce/tinymce.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>

    <script src="assets/js/main.js"></script>

</body>

</html>
<nav class="header-nav ms-auto">
            <?php
                $authUser = $user ?? (function_exists('auth') ? auth()->user() : null);

                // Shortcut permission flags
                $hdrCanBilling = false;
                $hdrCanIpdBilling = false;
                $hdrCanDoctorWork = false;
                $hdrCanPharmacy = false;
                $hdrCanManageSessions = false;
                if ($authUser && method_exists($authUser, 'can')) {
                    $hdrCanBilling = $authUser->can('billing.access')
                        || $authUser->can('billing.opd.edit')
                        || $authUser->can('billing.opd.pay')
                        || $authUser->can('billing.charges.view')
                        || $authUser->can('billing.charges.edit')
                        || $authUser->can('billing.charges.pay')
                        || $authUser->can('billing.charges.cancel')
                        || $authUser->can('billing.charges.correct')
                        || $authUser->can('billing.ipd.access')
                        || $authUser->can('billing.ipd.current-admission')
                        || $authUser->can('billing.ipd.invoice')
                        || $authUser->can('billing.ipd.cash-balance')
                        || $authUser->can('billing.ipd.export')
                        || $authUser->can('billing.*');
                    $hdrCanIpdBilling = $authUser->can('billing.ipd.access')
                        || $authUser->can('billing.ipd.current-admission')
                        || $authUser->can('billing.ipd.invoice')
                        || $authUser->can('billing.ipd.cash-balance')
                        || $authUser->can('billing.access');
                    $hdrCanDoctorWork = $authUser->can('doctor_work.access')
                        || $authUser->can('doctor_work.appointment.view')
                        || $authUser->can('doctor_work.*');
                    $hdrCanPharmacy = $authUser->can('pharmacy.access')
                        || $authUser->can('billing.access');
                    $hdrCanManageSessions = $authUser->can('users.edit')
                        || $authUser->can('users.manage-admins')
                        || $authUser->can('users.*');
                }
                if ($authUser && method_exists($authUser, 'inGroup')) {
                    $inAdminGroup = $authUser->inGroup('superadmin', 'admin', 'developer');
                    if (! $hdrCanBilling)     { $hdrCanBilling     = $inAdminGroup; }
                    if (! $hdrCanIpdBilling)  { $hdrCanIpdBilling  = $inAdminGroup; }
                    if (! $hdrCanDoctorWork)  { $hdrCanDoctorWork  = $inAdminGroup; }
                    if (! $hdrCanPharmacy)    { $hdrCanPharmacy    = $inAdminGroup; }
                    if (! $hdrCanManageSessions) { $hdrCanManageSessions = $inAdminGroup; }
                }

                $loginId = trim((string) ($authUser->username ?? ''));
                if ($loginId === '') {
                    $loginId = trim((string) ($authUser->email ?? ''));
                }
                if ($loginId === '') {
                    $loginId = 'User';
                }

                $displayName = $loginId;
                $displayUserId = (int) ($authUser->id ?? 0);
                $displayPhoto = '';

                if ($displayUserId > 0) {
                    $tables = config('Auth')->tables;
                    $identitiesTable = (string) ($tables['identities'] ?? 'auth_identities');
                    if (function_exists('db_connect')) {
                        $db = db_connect();
                        if ($db && $db->tableExists($identitiesTable)) {
                            $identityRow = $db->table($identitiesTable)
                                ->select('extra')
                                ->where('user_id', $displayUserId)
                                ->where('type', 'email_password')
                                ->get(1)
                                ->getRowArray();

                            $extraRaw = trim((string) ($identityRow['extra'] ?? ''));
                            if ($extraRaw !== '') {
                                $decoded = json_decode($extraRaw, true);
                                if (is_array($decoded)) {
                                    $fullName = trim((string) ($decoded['full_name'] ?? ''));
                                    if ($fullName !== '') {
                                        $displayName = $fullName;
                                    }

                                    $profilePhoto = trim((string) ($decoded['profile_photo'] ?? ''));
                                    if ($profilePhoto !== '') {
                                        $displayPhoto = basename($profilePhoto);
                                    }
                                }
                            }
                        }
                    }
                }

                $profileImageUrl = base_url('assets/img/profile-img.jpg');
                if ($displayPhoto !== '') {
                    $profileImageAbsolute = FCPATH . 'assets/images/user_profile/' . $displayPhoto;
                    if (is_file($profileImageAbsolute)) {
                        $profileImageUrl = base_url('assets/images/user_profile/' . $displayPhoto);
                    }
                }

                $serverTimeZoneId = 'Asia/Kolkata';
                $serverNow = new DateTimeImmutable('now', new DateTimeZone($serverTimeZoneId));
                $serverTimeZoneLabel = trim((string) $serverNow->format('T'));
                if ($serverTimeZoneLabel === '' || $serverTimeZoneLabel === 'GMT') {
                    $serverTimeZoneLabel = $serverTimeZoneId;
                }
                $serverEpochMs = (int) round(microtime(true) * 1000);
                $serverDisplayTime = $serverNow->format('d-m-Y h:i A') . ' (' . $serverTimeZoneLabel . ')';
            ?>
            <ul class="d-flex align-items-center">
                <?php if ($hdrCanBilling) { ?>
                <li class="nav-item d-flex align-items-center" style="margin-right:14px;">
                    <a class="nav-shortcut-icon text-decoration-none" href="javascript:load_form('<?= base_url('/billing/patient') ?>','Patient List')" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Patient List">
                        <i class="bi bi-person-lines-fill fs-5"></i>
                    </a>
                </li>
                <?php } ?>
                <?php if ($hdrCanIpdBilling) { ?>
                <li class="nav-item d-flex align-items-center" style="margin-right:20px;">
                    <a class="nav-shortcut-icon text-decoration-none" href="javascript:load_form('<?= base_url('/billing/ipd') ?>','IPD Billing')" data-bs-toggle="tooltip" data-bs-placement="bottom" title="IPD Billing">
                        <i class="bi bi-hospital fs-4"></i>
                    </a>
                </li>
                <?php } ?>
                <?php if ($hdrCanDoctorWork) { ?>
                <li class="nav-item d-flex align-items-center" style="margin-right:20px;">
                    <a class="nav-shortcut-icon text-decoration-none" href="javascript:load_form('<?= base_url('/opd/appointment') ?>','OPD Appointment List')" data-bs-toggle="tooltip" data-bs-placement="bottom" title="OPD Appointment">
                        <i class="bi bi-calendar2-check fs-4"></i>
                    </a>
                </li>
                <?php } ?>
                <?php if ($hdrCanPharmacy) { ?>
                <li class="nav-item d-flex align-items-center" style="margin-right:20px;">
                    <a class="nav-shortcut-icon text-decoration-none" href="javascript:load_form('<?= base_url('/Medical') ?>','Pharmacy')" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Pharmacy">
                        <i class="bi bi-capsule fs-4"></i>
                    </a>
                </li>
                <?php } ?>
                <li class="nav-item d-flex align-items-center" style="margin-right:20px;">
                    <a class="nav-shortcut-icon text-decoration-none text-primary" href="javascript:void(0)" id="btn_hdr_apps_launcher" data-bs-toggle="tooltip" data-bs-placement="bottom" title="HMS Mobile & PWA Apps">
                        <i class="bi bi-phone-vibrate fs-4"></i>
                    </a>
                </li>
                <li class="nav-item pe-3 d-flex align-items-center text-nowrap">
                    <a class="text-decoration-none" href="<?= base_url('help.html') ?>" target="_blank" rel="noopener">
                        <i class="bi bi-question-circle me-1"></i>
                        <span>Help</span>
                    </a>
                </li>
                <li class="nav-item pe-3 d-none d-md-flex align-items-center text-nowrap" title="Server time">
                    <i class="bi bi-clock me-1"></i>
                    <span id="header-server-datetime"
                          data-server-epoch-ms="<?= esc((string) $serverEpochMs) ?>"
                          data-server-timezone-id="<?= esc($serverTimeZoneId) ?>"
                          data-server-timezone-label="<?= esc($serverTimeZoneLabel) ?>"><?= esc($serverDisplayTime) ?></span>
                </li>
                <li class="nav-item dropdown pe-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                        <img src="<?= esc($profileImageUrl) ?>" alt="Profile" class="rounded-circle" id="header-user-avatar">
                        <span class="d-none d-md-block dropdown-toggle ps-2" id="header-user-with-id">
                            <?= esc($displayName) ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                        <li class="dropdown-header">
                            <h6 id="header-user-title"><?= esc($displayName) ?></h6>
                            <span id="header-user-login-id">Login ID: <?= esc($loginId) ?></span><br>
                            <span id="header-user-id">User ID: <?= esc((string) ($authUser->id ?? '')) ?></span>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="javascript:load_form('<?= base_url('my-profile') ?>','My Profile');">
                                <i class="bi bi-person"></i>
                                <span>My Profile</span>
                            </a>
                        </li>
                        <?php if ($hdrCanManageSessions) { ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="javascript:load_form('<?= base_url('setting/admin/user-management/sessions') ?>','Who is Online');">
                                <i class="bi bi-person-check"></i>
                                <span>Who is Online</span>
                            </a>
                        </li>
                        <?php } ?>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="<?= base_url('logout') ?>">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Sign Out</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>

<script>
    (function () {
        var el = document.getElementById('header-server-datetime');
        if (!el) {
            return;
        }

        var serverEpochMs = Number(el.getAttribute('data-server-epoch-ms') || '0');
        if (!Number.isFinite(serverEpochMs) || serverEpochMs <= 0) {
            return;
        }

        var tzId = String(el.getAttribute('data-server-timezone-id') || '');
        var tzLabel = String(el.getAttribute('data-server-timezone-label') || tzId || 'UTC');
        var dateCtor = window['Date'];
        var intlObj = window['Intl'];
        if (!dateCtor) {
            return;
        }

        var clientBaseMs = dateCtor.now();

        function pad2(value) {
            return String(value).padStart(2, '0');
        }

        function formatServerDateTime(epochMs) {
            var dateObj = new dateCtor(epochMs);
            var parts = null;

            try {
                if (!intlObj || !intlObj.DateTimeFormat) {
                    throw new Error('Intl unavailable');
                }

                parts = new intlObj.DateTimeFormat('en-GB', {
                    timeZone: tzId || undefined,
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                }).formatToParts(dateObj);
            } catch (e) {
                parts = null;
            }

            if (parts) {
                var map = {};
                parts.forEach(function (part) {
                    if (part.type !== 'literal') {
                        map[part.type] = part.value;
                    }
                });

                var dd = map.day || pad2(dateObj.getDate());
                var mm = map.month || pad2(dateObj.getMonth() + 1);
                var yyyy = map.year || String(dateObj.getFullYear());
                var hh = map.hour || '12';
                var min = map.minute || pad2(dateObj.getMinutes());
                var period = String(map.dayPeriod || '').toUpperCase();
                if (period !== 'AM' && period !== 'PM') {
                    period = dateObj.getHours() >= 12 ? 'PM' : 'AM';
                }

                return dd + '-' + mm + '-' + yyyy + ' ' + hh + ':' + min + ' ' + period + ' (' + tzLabel + ')';
            }

            var fallbackHours = dateObj.getHours();
            var fallbackPeriod = fallbackHours >= 12 ? 'PM' : 'AM';
            var fallbackHour12 = fallbackHours % 12;
            if (fallbackHour12 === 0) {
                fallbackHour12 = 12;
            }

            return pad2(dateObj.getDate()) + '-' + pad2(dateObj.getMonth() + 1) + '-' + dateObj.getFullYear()
                + ' ' + pad2(fallbackHour12) + ':' + pad2(dateObj.getMinutes()) + ' ' + fallbackPeriod + ' (' + tzLabel + ')';
        }

        function refreshClock() {
            var nowMs = dateCtor.now();
            var currentServerMs = serverEpochMs + (nowMs - clientBaseMs);
            el.textContent = formatServerDateTime(currentServerMs);
        }

        refreshClock();
        setInterval(refreshClock, 1000);
    })();
</script>

<!-- Modal for HMS Mobile & PWA Apps Launcher -->
<div class="modal fade" id="hmsAppsLauncherModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-2" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;">
                <h5 class="modal-title fs-6 fw-bold mb-0 d-flex align-items-center gap-2">
                    <img src="<?= base_url('assets/img/logo.png') ?>" alt="Logo" style="height:24px; width:auto; filter:brightness(0) invert(1);" onError="this.style.display='none'">
                    <i class="bi bi-grid-3x3-gap-fill me-1 text-primary"></i> HMS Mobile &amp; PWA Apps Launcher
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Step 1: App Selection Cards -->
                <label class="form-label fw-bold small text-secondary mb-2">1. SELECT APPLICATION</label>
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <div class="card p-2 text-center border-primary bg-primary-subtle text-primary h-100 app-card-item active" style="cursor: pointer;" id="app_card_nursing" data-app="nursing">
                            <i class="bi bi-heart-pulse-fill fs-3 mb-1"></i>
                            <div class="fw-bold small" style="font-size:12px;">Nursing Care</div>
                            <span class="badge bg-primary mt-1" style="font-size:9px;">Active PWA</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card p-2 text-center border-secondary bg-light text-dark h-100 app-card-item" style="cursor: pointer;" id="app_card_doctor" data-app="doctor">
                            <i class="bi bi-person-badge-fill fs-3 mb-1 text-primary"></i>
                            <div class="fw-bold small" style="font-size:12px;">Doctor App</div>
                            <span class="badge bg-success mt-1" style="font-size:9px;">Active PWA</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card p-2 text-center border-secondary bg-light text-muted opacity-75 h-100">
                            <i class="bi bi-capsule fs-3 mb-1"></i>
                            <div class="fw-bold small" style="font-size:12px;">Pharmacy App</div>
                            <span class="badge bg-secondary mt-1" style="font-size:9px;">Coming Soon</span>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Staff User Selection -->
                <div class="mb-3">
                    <label class="form-label fw-bold small text-secondary mb-1" id="hdr_apps_staff_label">2. SELECT NURSING STAFF PROFILE</label>
                    <select class="form-select form-select-sm" id="hdr_apps_nurse_select">
                        <option value="">-- General / All Staff Access --</option>
                    </select>
                </div>

                <!-- Step 3: QR Code & Access Link -->
                <div class="p-3 bg-light rounded border text-center">
                    <div class="fw-bold small text-dark mb-1" id="hdr_apps_target_name">Nursing Care App</div>
                    <span class="badge bg-secondary mb-2" id="hdr_apps_target_code">/app/nursing</span>

                    <div class="my-2">
                        <img id="hdr_apps_qr_img" src="" alt="App QR Code" style="width: 160px; height: 160px; border-radius: 8px;" class="border p-1 bg-white shadow-sm" />
                    </div>
                    <p class="small text-muted mb-2" style="font-size: 11px;">
                        <i class="bi bi-qr-code-scan me-1"></i> Scan with Phone Camera to launch app on Mobile, or click link below to open on Computer.
                    </p>

                    <div class="input-group input-group-sm mb-2">
                        <input type="text" class="form-control form-control-sm text-center" id="hdr_apps_url_input" readonly />
                        <button class="btn btn-outline-primary" type="button" id="btn_hdr_apps_copy_url"><i class="bi bi-clipboard"></i> Copy</button>
                    </div>

                    <a href="#" target="_blank" class="btn btn-primary btn-sm w-100 fw-bold" id="hdr_apps_launch_link">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Launch App on Computer / Browser
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var selectedApp = 'nursing';

    function getAppBaseUrl() {
        return '<?= base_url('app') ?>/' + selectedApp;
    }

    function updateHdrAppDetails() {
        var sel = document.getElementById('hdr_apps_nurse_select');
        var code = sel ? sel.value : '';
        var appBaseUrl = getAppBaseUrl();
        var paramKey = (selectedApp === 'doctor') ? 'doctor_id=' : 'nurse_code=';
        var appUrl = appBaseUrl + (code ? '?' + paramKey + encodeURIComponent(code) : '');
        var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(appUrl);

        var qrImg = document.getElementById('hdr_apps_qr_img');
        var urlInput = document.getElementById('hdr_apps_url_input');
        var launchLink = document.getElementById('hdr_apps_launch_link');
        var targetName = document.getElementById('hdr_apps_target_name');
        var targetCode = document.getElementById('hdr_apps_target_code');

        if (qrImg) qrImg.src = qrUrl;
        if (urlInput) urlInput.value = appUrl;
        if (launchLink) launchLink.href = appUrl;

        var title = (selectedApp === 'doctor') ? 'DoctorCare EMR App' : 'Nursing Care App';

        if (code && sel && sel.options[sel.selectedIndex]) {
            var text = sel.options[sel.selectedIndex].text;
            if (targetName) targetName.textContent = title + ': ' + text;
            if (targetCode) targetCode.textContent = code;
        } else {
            if (targetName) targetName.textContent = title;
            if (targetCode) targetCode.textContent = '/app/' + selectedApp;
        }
    }

    function showAppsModal() {
        var modalEl = document.getElementById('hmsAppsLauncherModal');
        if (modalEl) {
            if (modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }
            if (window.bootstrap && window.bootstrap.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else if (typeof $ !== 'undefined' && $.fn && $.fn.modal) {
                $(modalEl).modal('show');
            }
        }
    }

    function loadAppStaffList() {
        var sel = document.getElementById('hdr_apps_nurse_select');
        var label = document.getElementById('hdr_apps_staff_label');
        if (!sel) return;

        if (selectedApp === 'doctor') {
            if (label) label.textContent = '2. SELECT DOCTOR PROFILE';
            fetch('<?= base_url('api/v1/doctor/list') ?>')
                .then(function(r) { return r.json(); })
                .then(function(resp) {
                    sel.innerHTML = '<option value="">-- General / All Doctor Access --</option>';
                    if (resp && resp.status === 1 && Array.isArray(resp.data)) {
                        resp.data.forEach(function(d) {
                            var opt = document.createElement('option');
                            opt.value = d.id;
                            opt.textContent = d.name;
                            sel.appendChild(opt);
                        });
                    }
                    updateHdrAppDetails();
                });
        } else {
            if (label) label.textContent = '2. SELECT NURSING STAFF PROFILE';
            fetch('<?= base_url('api/v1/nursing/nurses') ?>')
                .then(function(r) { return r.json(); })
                .then(function(resp) {
                    sel.innerHTML = '<option value="">-- General / All Staff Access --</option>';
                    if (resp && resp.status === 1 && Array.isArray(resp.data)) {
                        resp.data.forEach(function(n) {
                            var opt = document.createElement('option');
                            opt.value = n.nurse_code;
                            opt.textContent = '[' + n.nurse_code + '] ' + n.name;
                            sel.appendChild(opt);
                        });
                    }
                    updateHdrAppDetails();
                });
        }
    }

    function openAppsModal() {
        loadAppStaffList();
        showAppsModal();
    }

    document.addEventListener('click', function(e) {
        var launcherBtn = e.target.closest('#btn_hdr_apps_launcher');
        if (launcherBtn) {
            e.preventDefault();
            openAppsModal();
            return;
        }

        var appCard = e.target.closest('.app-card-item');
        if (appCard) {
            var appType = appCard.getAttribute('data-app');
            if (appType) {
                selectedApp = appType;
                document.querySelectorAll('.app-card-item').forEach(function(c) {
                    c.classList.remove('border-primary', 'bg-primary-subtle', 'text-primary');
                    c.classList.add('border-secondary', 'bg-light', 'text-dark');
                });
                appCard.classList.remove('border-secondary', 'bg-light', 'text-dark');
                appCard.classList.add('border-primary', 'bg-primary-subtle', 'text-primary');
                loadAppStaffList();
            }
            return;
        }

        var copyBtn = e.target.closest('#btn_hdr_apps_copy_url');
        if (copyBtn) {
            e.preventDefault();
            var input = document.getElementById('hdr_apps_url_input');
            if (input) {
                input.select();
                try {
                    navigator.clipboard ? navigator.clipboard.writeText(input.value) : document.execCommand('copy');
                } catch(ex) {
                    document.execCommand('copy');
                }
                alert('App URL copied to clipboard!');
            }
        }
    });

    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'hdr_apps_nurse_select') {
            updateHdrAppDetails();
        }
    });
})();
</script>
<nav class="header-nav ms-auto">
            <?php
                $authUser = $user ?? (function_exists('auth') ? auth()->user() : null);

                $loginId = trim((string) ($authUser->username ?? ''));
                if ($loginId === '') {
                    $loginId = trim((string) ($authUser->email ?? ''));
                }
                if ($loginId === '') {
                    $loginId = 'User';
                }

                $displayName = $loginId;
                $displayUserId = (int) ($authUser->id ?? 0);

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
                                }
                            }
                        }
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
                        <img src="<?= base_url('assets/img/profile-img.jpg') ?>" alt="Profile" class="rounded-circle">
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
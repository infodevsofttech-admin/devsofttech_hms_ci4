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
            ?>
            <ul class="d-flex align-items-center">
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
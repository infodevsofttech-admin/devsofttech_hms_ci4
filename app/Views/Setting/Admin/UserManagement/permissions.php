<div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h3 class="card-title mb-0">Additional User Permissions</h3>
                <div class="text-muted small">Role permissions are inherited automatically. Use this page only for extra access needed by one user.</div>
            </div>
            <div class="card-tools ms-auto">
                <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management') ?>','maindiv','User Management');">
                    <i class="bi bi-arrow-left"></i>
                    Back to User List
                </button>
            </div>
        </div>
            <div class="card-body">
                <?php $message = $message ?? session('message'); ?>
                <?php $errors = $errors ?? session('errors'); ?>
                <?php if (! empty($message)) : ?>
                    <div class="alert alert-success"><?= esc($message) ?></div>
                <?php endif ?>
                <?php if (! empty($errors)) : ?>
                    <div class="alert alert-danger">
                        <?php foreach ((array) $errors as $error) : ?>
                            <div><?= esc($error) ?></div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
                <?php
                $selectedEmail = '';
                $selectedPersonName = '';
                $selectedPhoneNo = '';
                if (! empty($selectedUser)) {
                    $selectedIdentity = $selectedUser->getEmailIdentity();
                    $selectedEmail = trim((string) ($selectedIdentity->secret ?? ''));
                    $selectedExtra = $selectedIdentity->extra ?? [];
                    if (is_string($selectedExtra) && trim($selectedExtra) !== '') {
                        $decodedExtra = json_decode($selectedExtra, true);
                        $selectedExtra = is_array($decodedExtra) ? $decodedExtra : [];
                    }
                    if (is_array($selectedExtra)) {
                        $selectedPersonName = trim((string) ($selectedExtra['full_name'] ?? ''));
                        $selectedPhoneNo = trim((string) ($selectedExtra['phone_no'] ?? ''));
                    }
                }
                ?>
                <form class="needs-validation" novalidate action="<?= base_url('setting/admin/user-management/permissions') ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= ! empty($selectedUser) ? (int) $selectedUser->id : '' ?>">

                    <?php if (! empty($selectedUser)) : ?>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-person-badge fs-4 text-primary"></i>
                                <div>
                                    <div class="fw-bold"><?= esc($selectedPersonName !== '' ? $selectedPersonName : (string) ($selectedUser->username ?? 'User')) ?></div>
                                    <div class="text-muted small">Selected user for additional permissions</div>
                                </div>
                            </div>
                            <div class="row g-2 small">
                                <div class="col-md-4"><strong>Login ID:</strong> <?= esc((string) ($selectedUser->username ?? '-')) ?></div>
                                <div class="col-md-4"><strong>User ID:</strong> <?= (int) $selectedUser->id ?></div>
                                <div class="col-md-4"><strong>Status:</strong> <?= ! empty($selectedUser->active) ? 'Active' : 'Inactive' ?></div>
                                <div class="col-md-4"><strong>Email:</strong> <?= esc($selectedEmail !== '' ? $selectedEmail : '-') ?></div>
                                <div class="col-md-4"><strong>Phone:</strong> <?= esc($selectedPhoneNo !== '' ? $selectedPhoneNo : '-') ?></div>
                                <div class="col-md-4"><strong>Role:</strong> <?= esc(! empty($selectedRoleTitles) ? implode(', ', $selectedRoleTitles) : 'No role') ?></div>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-warning">No user was selected. Return to the user list and choose the permissions action for a user.</div>
                    <?php endif ?>

                    <div class="mt-4">
                        <?php if (! empty($selectedUser)) : ?>
                            <div class="alert alert-secondary py-2 mb-3">
                                <strong>Assigned role:</strong>
                                <?= esc(! empty($selectedRoleTitles) ? implode(', ', $selectedRoleTitles) : 'No role') ?>.
                                Permissions marked <span class="badge bg-secondary">Inherited from Role</span> are already available through this role.
                            </div>
                        <?php endif ?>
                        <label class="form-label">Direct grants</label>
                        <div class="text-muted small mb-2">Checked boxes are additional permissions saved specifically for this user. Unchecking one does not remove access inherited from the role.</div>
                        <div class="border rounded p-3 bg-light">
                            <?php
                            $selectedPermissions = [];
                            $permissions = $permissions ?? [];
                            $inheritedPermissions = $inheritedPermissions ?? [];
                            if (! empty($selectedUser)) {
                                $selectedPermissions = $selectedUser->getPermissions() ?? [];
                            }

                            $groupedPermissions = [];
                            $groupTitles = [
                                'admin' => 'Admin',
                                'users' => 'Users',
                                'beta' => 'Beta',
                                'template' => 'Templates',
                                'pharmacy' => 'Pharmacy',
                                'finance' => 'Finance',
                                'opd' => 'OPD Doctor Panel',
                                'billing.opd' => 'OPD',
                                'billing.charges' => 'Charges',
                                'billing.ipd' => 'IPD Billing',
                                'billing.access' => 'Billing Access',
                                'billing.patient' => 'Patient Billing',
                                'billing.payment_request' => 'Payment Requests',
                                'billing.refund' => 'Refunds',
                                'billing.items' => 'Billing Items',
                                'billing.packages' => 'Billing Packages',
                                'abdm' => 'ABDM',
                                'diagnosis' => 'Diagnosis',
                                'doctor_work' => 'Doctor Work',
                                'hospital_stock' => 'Hospital Stock',
                                'media' => 'Media',
                                'reports' => 'Reports',
                                'settings' => 'Settings',
                                'other' => 'Other',
                            ];
                            foreach ($permissions as $permissionKey => $permissionLabel) {
                                $parts = explode('.', $permissionKey);
                                $groupKey = $parts[0] ?? 'other';
                                if ($groupKey === 'billing' && isset($parts[1])) {
                                    $groupKey = $groupKey . '.' . $parts[1];
                                }

                                if (! isset($groupTitles[$groupKey])) {
                                    $titleBase = str_replace(['-', '_'], ' ', $groupKey);
                                    $titleBase = str_replace('billing ', '', $titleBase);
                                    $groupTitles[$groupKey] = ucwords($titleBase);
                                }

                                if (! isset($groupedPermissions[$groupKey])) {
                                    $groupedPermissions[$groupKey] = [];
                                }

                                $groupedPermissions[$groupKey][$permissionKey] = $permissionLabel;
                            }
                            if (! isset($groupedPermissions['other'])) {
                                $groupedPermissions['other'] = [];
                            }

                            $groupOrder = [
                                'admin',
                                'users',
                                'template',
                                'pharmacy',
                                'finance',
                                'opd',
                                'billing.opd',
                                'billing.charges',
                                'billing.ipd',
                                'billing',
                                'abdm',
                                'beta',
                                'other',
                            ];

                            // Ensure new permission groups are still shown even if not in the predefined display order.
                            foreach (array_keys($groupedPermissions) as $detectedGroup) {
                                if (! in_array($detectedGroup, $groupOrder, true)) {
                                    $groupOrder[] = $detectedGroup;
                                }
                            }

                            $groupHelp = [
                                'admin' => 'Controls access to the administrative area and core application settings. Grant only to trusted system administrators.',
                                'users' => 'Controls user-account lifecycle actions such as creating, editing, deleting, and managing privileged users.',
                                'template' => 'Controls access to clinical, diagnostic, discharge, document, and print-template configuration areas.',
                                'pharmacy' => 'Controls pharmacy access, invoice administration, old-invoice editing, purchase status changes, and user discount limits. The base discount is configured by hospital setting PHARMACY_NORMAL_MAX_DISCOUNT_PERCENT.',
                                'finance' => 'Controls Accounts and Finance workflows including cash submission, verification, bank reconciliation, compliance, vendors, purchase orders, GRNs, invoices, and payouts.',
                                'opd' => 'Controls access to the OPD doctor panel used for clinical work on outpatient visits.',
                                'billing.access' => 'Provides the main entry permission for billing workflows. More specific billing permissions below restrict individual operations.',
                                'billing.patient' => 'Controls exceptional patient billing actions, including editing a patient name after the normal time restriction.',
                                'billing.opd' => 'Controls OPD registration editing and payment confirmation actions.',
                                'billing.charges' => 'Controls charges invoice viewing, editing, date changes, payment, cancellation, and correction actions.',
                                'billing.ipd' => 'Controls IPD billing access, admissions, invoices, cash reports, bill printing, discharge edits, status management, and exports.',
                                'billing.payment_request' => 'Controls viewing and processing of organization payment requests.',
                                'billing.refund' => 'Controls viewing and processing of billing refund requests.',
                                'billing.items' => 'Controls access to billing item masters and permission to maintain them.',
                                'billing.packages' => 'Controls access to billing package masters and permission to maintain them.',
                                'abdm' => 'Controls ABDM access, ABHA operations, task board, gateway actions, and Bridge communication logs.',
                                'diagnosis' => 'Controls entry to the diagnosis module and access to diagnosis reports.',
                                'doctor_work' => 'Controls doctor workflows including appointments, Rx groups, medicines, advice, clinical templates, and immunization records.',
                                'hospital_stock' => 'Controls hospital stock masters, indents, approvals, issues, purchases, receiving, reports, and alerts.',
                                'media' => 'Controls optional image preparation actions such as rotating or cropping before upload.',
                                'reports' => 'Controls access to operational, collection, insurance, NABH audit, billing, and document issue reports.',
                                'settings' => 'Controls access to operational settings such as bed management and charges configuration.',
                                'beta' => 'Provides access to features designated for beta testing before general availability.',
                                'other' => 'Contains permissions that do not yet belong to a dedicated module heading.',
                            ];
                            ?>
                            <?php if (! empty($permissions)) : ?>
                                <div class="row g-3">
                                    <?php foreach ($groupOrder as $groupKey) : ?>
                                        <?php if (empty($groupedPermissions[$groupKey])) { continue; } ?>
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="d-flex align-items-center gap-1 mb-2">
                                                <div class="fw-bold"><?= esc($groupTitles[$groupKey] ?? $groupKey) ?></div>
                                                <button type="button" class="btn btn-sm p-0 border-0 text-primary permission-help" data-bs-toggle="modal" data-bs-target="#permissionHelpModal" data-group-key="<?= esc($groupKey, 'attr') ?>" title="About <?= esc($groupTitles[$groupKey] ?? $groupKey, 'attr') ?> permissions" aria-label="About <?= esc($groupTitles[$groupKey] ?? $groupKey, 'attr') ?> permissions">
                                                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                            <?php foreach ($groupedPermissions[$groupKey] as $permissionKey => $permissionLabel) : ?>
                                                <div class="form-check mb-1">
                                                    <input class="form-check-input" type="checkbox" id="perm_<?= esc($permissionKey) ?>" name="permissions[]" value="<?= esc($permissionKey) ?>" <?= in_array($permissionKey, $selectedPermissions, true) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="perm_<?= esc($permissionKey) ?>">
                                                        <?= esc($permissionLabel) ?>
                                                        <?php if (in_array($permissionKey, $inheritedPermissions, true)) : ?>
                                                            <span class="badge bg-secondary ms-1">Inherited from Role</span>
                                                        <?php endif ?>
                                                    </label>
                                                </div>
                                            <?php endforeach ?>
                                        </div>
                                    <?php endforeach ?>
                                </div>
                            <?php else : ?>
                                <div class="text-muted">No permissions configured.</div>
                            <?php endif ?>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Save Permissions</button>
                        <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management') ?>','maindiv','User Management');">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="permissionHelpModal" tabindex="-1" aria-labelledby="permissionHelpModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="permissionHelpModalLabel">Permission Guide</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="permissionHelpSummary" class="mb-3"></p>
                        <h6>Permissions in this section</h6>
                        <div id="permissionHelpList" class="list-group list-group-flush"></div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            (function() {
                var form = document.querySelector('form[action="<?= base_url('setting/admin/user-management/permissions') ?>"]');
                var moduleGuides = <?= json_encode(array_reduce(array_keys($groupedPermissions), static function (array $guides, string $groupKey) use ($groupedPermissions, $groupTitles, $groupHelp): array {
                    $permissionRows = [];
                    foreach ($groupedPermissions[$groupKey] as $permissionKey => $permissionLabel) {
                        $permissionRows[] = ['key' => $permissionKey, 'label' => $permissionLabel];
                    }
                    $guides[$groupKey] = [
                        'title' => ($groupTitles[$groupKey] ?? $groupKey) . ' Permission Guide',
                        'summary' => $groupHelp[$groupKey] ?? 'Review these permissions before granting additional access for this module.',
                        'permissions' => $permissionRows,
                    ];
                    return $guides;
                }, []), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

                $(document).off('show.bs.modal.permissionHelp', '#permissionHelpModal').on('show.bs.modal.permissionHelp', '#permissionHelpModal', function(event) {
                    var button = event.relatedTarget;
                    var groupKey = button ? button.getAttribute('data-group-key') : '';
                    var guide = moduleGuides[groupKey];
                    if (!guide) {
                        return;
                    }

                    $('#permissionHelpModalLabel').text(guide.title);
                    $('#permissionHelpSummary').text(guide.summary);
                    var list = document.getElementById('permissionHelpList');
                    list.replaceChildren();
                    guide.permissions.forEach(function(permission) {
                        var item = document.createElement('div');
                        item.className = 'list-group-item px-0';
                        var key = document.createElement('code');
                        key.className = 'd-block mb-1';
                        key.textContent = permission.key;
                        var label = document.createElement('div');
                        label.textContent = permission.label;
                        item.append(key, label);
                        list.appendChild(item);
                    });
                });

                if (!form || !window.jQuery) {
                    return;
                }

                $(form).off('submit.userPermissions').on('submit.userPermissions', function(event) {
                    event.preventDefault();

                    $.post($(form).attr('action'), $(form).serialize())
                        .done(function(html) {
                            $('#maindiv').html(html);
                        })
                        .fail(function() {
                            alert('Request failed. Please try again.');
                        });
                });
            })();
        </script>
    </div>

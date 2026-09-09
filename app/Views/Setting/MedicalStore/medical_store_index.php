<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="text-primary mb-0"><i class="bi bi-capsule me-1"></i> Medical Stores &amp; Pharmacy Counters Master</h5>
            <small class="text-muted">Manage hospital building stores, Indian Drug Licenses (20B/21B), GSTIN, direct URLs, and machine terminal security</small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-success btn-sm" id="btn_open_excel_import">
                <i class="bi bi-file-earmark-arrow-up me-1"></i> Excel / CSV Import
            </button>
            <button type="button" class="btn btn-primary btn-sm" id="btn_add_store">
                <i class="bi bi-plus-lg me-1"></i> Add Medical Store
            </button>
        </div>
    </div>

    <!-- Search Card -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2 px-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" id="store_search_input" placeholder="Search by store name, code, slug, building, DL 20B, GSTIN..." value="<?= esc($searchQuery ?? '') ?>">
                        <button class="btn btn-primary" type="button" id="btn_store_search">Search</button>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_refresh_store_list">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stores Table -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="stores_table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;" class="ps-3">#</th>
                            <th>Store Name &amp; Building</th>
                            <th>Direct Counter Link (URL)</th>
                            <th>Drug Licenses (20B / 21B)</th>
                            <th>GSTIN &amp; State</th>
                            <th>Pharmacist</th>
                            <th>Terminal Security</th>
                            <th style="width: 130px;" class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stores)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-hospital fs-3 d-block mb-1"></i>
                                    No medical stores found. Click <strong>"Add Medical Store"</strong> to create a new pharmacy counter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stores as $idx => $st): ?>
                                <?php 
                                    $directUrl = base_url('MedicalStore/' . ($st['store_slug'] ?: $st['store_code']));
                                ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-secondary"><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="fw-bold text-dark fs-6"><?= esc($st['store_name']) ?></div>
                                        <small class="text-muted">
                                            <i class="bi bi-geo-alt me-1"></i><?= esc($st['building_name'] ?: 'Main Block') ?> • <?= esc($st['floor_no'] ?: 'Ground Floor') ?>
                                            <?php if ($st['is_main_store']): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary px-1.5 py-0.5 ms-1">CENTRAL STORE</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <code class="text-primary fw-bold" style="font-size: 11px;">MedicalStore/<?= esc($st['store_slug']) ?></code>
                                            <button type="button" class="btn btn-sm btn-light py-0 px-1 border" title="Copy Direct URL Link" onclick="copyText('<?= esc($directUrl) ?>')">
                                                <i class="bi bi-copy text-muted" style="font-size: 11px;"></i>
                                            </button>
                                            <a href="<?= esc($directUrl) ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-1" title="Open Counter POS">
                                                <i class="bi bi-box-arrow-up-right" style="font-size: 11px;"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small"><strong>20B:</strong> <?= esc($st['drug_license_no_20b'] ?: 'N/A') ?></div>
                                        <div class="small"><strong>21B:</strong> <?= esc($st['drug_license_no_21b'] ?: 'N/A') ?></div>
                                    </td>
                                    <td>
                                        <div><code class="text-success fw-bold"><?= esc($st['gstin'] ?: 'N/A') ?></code></div>
                                        <small class="text-muted d-block">State: <?= esc($st['state_code']) ?> (<?= esc($st['state_name']) ?>)</small>
                                        <?php if (!empty($st['abdm_hfr_id'])): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-0.5" style="font-size: 10px;" title="ABDM Health Facility Registry">
                                                <i class="bi bi-shield-check me-0.5"></i> HFR: <?= esc($st['abdm_hfr_id']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?= esc($st['registered_pharmacist_name'] ?: 'Not Assigned') ?></div>
                                        <small class="text-muted"><?= esc($st['pharmacist_reg_no'] ?: '') ?></small>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info py-0.5 px-2" onclick="openTerminalSecurityModal(<?= htmlspecialchars(json_encode($st), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="bi bi-shield-lock-fill me-1"></i> Key &amp; OTP
                                            <span class="badge bg-secondary ms-1"><?= (int)$st['authorized_devices_count'] ?> PCs</span>
                                        </button>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm py-0.5 px-2" onclick="editStoreModal(<?= htmlspecialchars(json_encode($st), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm py-0.5 px-2 ms-1" onclick="deleteStore(<?= (int)$st['store_id'] ?>, '<?= esc($st['store_name']) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Authorized Terminals / Machines Section -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-2.5 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-display me-1 text-primary"></i> Authorized Computer Terminals &amp; Machines</h6>
            <small class="text-muted">Protected counter computers verified via HMS Admin Security Key or OTP</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 12px;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Machine / Computer Name</th>
                            <th>Medical Store</th>
                            <th>IP Address</th>
                            <th>Verified Method</th>
                            <th>Authorized Date</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($devices)): ?>
                            <tr>
                                <td colSpan="7" class="text-center py-3 text-muted">
                                    No machine terminals registered yet. Open a store link on a counter PC and verify using the Security Key or OTP.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($devices as $d): ?>
                                <tr>
                                    <td class="ps-3 fw-bold">
                                        <i class="bi bi-pc-display me-1 text-primary"></i> <?= esc($d['machine_name']) ?>
                                    </td>
                                    <td><?= esc($d['store_name']) ?></td>
                                    <td><code><?= esc($d['ip_address'] ?: 'Localhost') ?></code></td>
                                    <td><span class="badge bg-light text-dark border"><?= esc($d['verified_by_method']) ?></span></td>
                                    <td><?= esc(date('d-m-Y h:i A', strtotime($d['authorized_at']))) ?></td>
                                    <td>
                                        <?php if ($d['status'] === 'authorized'): ?>
                                            <span class="badge bg-success">Active Authorized</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Access Revoked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <?php if ($d['status'] === 'authorized'): ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="revokeTerminal(<?= (int)$d['device_id'] ?>)">
                                                <i class="bi bi-slash-circle me-1"></i> Revoke
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">Revoked</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Add / Edit Medical Store -->
<div class="modal fade" id="storeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-2.5">
                <h6 class="modal-title fw-bold" id="storeModalTitle"><i class="bi bi-capsule me-1"></i> Add Medical Store Counter</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="storeForm">
                <input type="hidden" name="store_id" id="form_store_id" value="0">
                <div class="modal-body p-3">
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Store Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="store_code" id="form_store_code" placeholder="e.g. ST-OPD-1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Unique URL Link Slug <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light" style="font-size: 11px;">/MedicalStore/</span>
                                <input type="text" class="form-control form-control-sm" name="store_slug" id="form_store_slug" placeholder="storeA" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Is Central Warehouse?</label>
                            <select class="form-select form-select-sm" name="is_main_store" id="form_is_main_store">
                                <option value="0">Sub-Counter / Building Store</option>
                                <option value="1">Central Main Pharmacy</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold mb-1">Medical Store Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="store_name" id="form_store_name" placeholder="e.g. Apollo City Medicos - OPD Counter" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Building Name</label>
                            <input type="text" class="form-control form-control-sm" name="building_name" id="form_building_name" placeholder="e.g. OPD Block B">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Floor</label>
                            <input type="text" class="form-control form-control-sm" name="floor_no" id="form_floor_no" placeholder="e.g. 1st Floor">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Counter / Room No</label>
                            <input type="text" class="form-control form-control-sm" name="room_no" id="form_room_no" placeholder="e.g. Counter 2">
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mt-3 mb-2" style="font-size: 13px;"><i class="bi bi-shield-check me-1"></i> Indian Statutory Drug Licenses &amp; GST</h6>
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Drug License Form 20-B (Allopathic)</label>
                            <input type="text" class="form-control form-control-sm" name="drug_license_no_20b" id="form_dl_20b" placeholder="DL-20B-DEL-XXXXX">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Drug License Form 21-B (Schedule C/C1)</label>
                            <input type="text" class="form-control form-control-sm" name="drug_license_no_21b" id="form_dl_21b" placeholder="DL-21B-DEL-XXXXX">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Drug License Form 20-F (Schedule X)</label>
                            <input type="text" class="form-control form-control-sm" name="drug_license_no_20f_x" id="form_dl_20f" placeholder="DL-20F-DEL-XXXXX">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">GSTIN (15-digit)</label>
                            <input type="text" class="form-control form-control-sm text-uppercase" name="gstin" id="form_gstin" placeholder="07AAAAA0000A1Z5">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">PAN Number</label>
                            <input type="text" class="form-control form-control-sm text-uppercase" name="pan_no" id="form_pan" placeholder="AAAAA0000A">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">FSSAI License No</label>
                            <input type="text" class="form-control form-control-sm" name="fssai_no" id="form_fssai" placeholder="100XXXXXXXXXX">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold mb-1">State Code (GST)</label>
                            <input type="text" class="form-control form-control-sm" name="state_code" id="form_state_code" placeholder="07" value="07">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label small fw-bold mb-1">State Name</label>
                            <input type="text" class="form-control form-control-sm" name="state_name" id="form_state_name" placeholder="Delhi" value="Delhi">
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mt-3 mb-2" style="font-size: 13px;"><i class="bi bi-shield-check me-1"></i> ABDM (Ayushman Bharat Digital Mission) Registration</h6>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">ABDM HFR Facility ID</label>
                            <input type="text" class="form-control form-control-sm" name="abdm_hfr_id" id="form_abdm_hfr_id" placeholder="e.g. IN0710001234">
                            <div class="form-text" style="font-size: 10px;">Health Facility Registry ID</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">ABDM HIP ID</label>
                            <input type="text" class="form-control form-control-sm" name="abdm_hip_id" id="form_abdm_hip_id" placeholder="e.g. HIP-CITYHOSP-01">
                            <div class="form-text" style="font-size: 10px;">Health Information Provider ID</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Pharmacist ABDM HPR ID</label>
                            <input type="text" class="form-control form-control-sm" name="pharmacist_hpr_id" id="form_pharmacist_hpr_id" placeholder="e.g. 91-XXXX-XXXX-XXXX">
                            <div class="form-text" style="font-size: 10px;">Healthcare Professional Registry ID</div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mt-3 mb-2" style="font-size: 13px;"><i class="bi bi-person-badge me-1"></i> Pharmacist &amp; POS Bill Configuration</h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold mb-1">Registered Pharmacist Name</label>
                            <input type="text" class="form-control form-control-sm" name="registered_pharmacist_name" id="form_pharmacist_name" placeholder="Pharmacist Full Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold mb-1">State Pharmacy Council Reg No</label>
                            <input type="text" class="form-control form-control-sm" name="pharmacist_reg_no" id="form_pharmacist_reg_no" placeholder="e.g. DPC-Reg-45892">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Bill Series Prefix</label>
                            <input type="text" class="form-control form-control-sm" name="invoice_prefix" id="form_invoice_prefix" placeholder="OPD1/26-27/">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Next Sequence No</label>
                            <input type="number" class="form-control form-control-sm" name="next_invoice_no" id="form_next_invoice_no" value="1001">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Store UPI VPA (for QR Code)</label>
                            <input type="text" class="form-control form-control-sm" name="upi_id" id="form_upi_id" placeholder="hospital@upi">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold mb-1">Terms &amp; Conditions (Printed on Invoice)</label>
                            <textarea class="form-control form-control-sm" name="terms_conditions" id="form_terms" rows="2" placeholder="Goods once sold returnable within 7 days with bill. Store below 25°C."></textarea>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mt-3 mb-2" style="font-size: 13px;"><i class="bi bi-geo-alt me-1"></i> Store Contact &amp; Physical Address</h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold mb-1">Contact Phone / Mobile</label>
                            <input type="text" class="form-control form-control-sm" name="contact_phone" id="form_contact_phone" placeholder="e.g. +91 98765 43210">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold mb-1">Contact Email</label>
                            <input type="email" class="form-control form-control-sm" name="contact_email" id="form_contact_email" placeholder="pharmacy@hospital.com">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold mb-1">Store / Counter Physical Address</label>
                            <textarea class="form-control form-control-sm" name="address" id="form_address" rows="2" placeholder="Full address of the hospital pharmacy counter..."></textarea>
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mt-3 mb-2" style="font-size: 13px;"><i class="bi bi-bank me-1"></i> Bank Account Details (for Statement Reconciliation &amp; Invoices)</h6>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Bank Name</label>
                            <input type="text" class="form-control form-control-sm" name="bank_name" id="form_bank_name" placeholder="e.g. State Bank of India">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Bank Account Number</label>
                            <input type="text" class="form-control form-control-sm" name="bank_account_no" id="form_bank_account_no" placeholder="e.g. 10293847561">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold mb-1">Bank IFSC Code</label>
                            <input type="text" class="form-control form-control-sm text-uppercase" name="bank_ifsc" id="form_bank_ifsc" placeholder="e.g. SBIN0001234">
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn_save_store">
                        <i class="bi bi-check-circle me-1"></i> Save Medical Store
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Terminal Verification & Computer Security Keys -->
<div class="modal fade" id="terminalSecurityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-2.5">
                <h6 class="modal-title fw-bold"><i class="bi bi-shield-lock me-1 text-warning"></i> Computer Machine Verification</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="alert alert-info py-2 mb-3" style="font-size: 12px;">
                    <i class="bi bi-info-circle me-1"></i> When opening the store link on a counter PC for the first time, staff must enter either the permanent <strong>Security Key</strong> or an <strong>Activation OTP</strong>.
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted mb-1">Permanent Machine Security Key</label>
                    <div class="input-group">
                        <input type="text" class="form-control fw-bold text-primary font-monospace" id="modal_sec_key" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyText(document.getElementById('modal_sec_key').value)">
                            <i class="bi bi-copy"></i>
                        </button>
                        <button class="btn btn-outline-warning" type="button" id="btn_regen_key" title="Regenerate Security Key">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3 p-3 bg-light rounded-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small fw-bold mb-0">6-Digit Counter Activation OTP</label>
                        <button type="button" class="btn btn-primary btn-sm py-0.5 px-2" id="btn_generate_otp">
                            <i class="bi bi-key-fill me-1"></i> Generate New OTP
                        </button>
                    </div>
                    <div id="otp_display_box" style="display: none;">
                        <div class="display-6 fw-bold text-success text-center letter-spacing-2" id="otp_code_val" style="letter-spacing: 6px;">
                            ------
                        </div>
                        <small class="text-muted d-block text-center mt-1" id="otp_expiry_val"></small>
                    </div>
                    <small class="text-muted d-block" id="otp_idle_text">
                        Click "Generate New OTP" to generate an emergency 60-minute activation PIN for this store.
                    </small>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 3: Excel / CSV Inventory Import -->
<div class="modal fade" id="excelImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white py-2.5">
                <h6 class="modal-title fw-bold"><i class="bi bi-file-earmark-arrow-up me-1"></i> Excel / CSV Inventory Import</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="excelImportForm" enctype="multipart/form-data">
                <div class="modal-body p-3">
                    <p class="small text-muted mb-3">
                        Quickly migrate stock from Microsoft Excel or CSV files into the store's shared master catalog and batch stock.
                    </p>

                    <div class="mb-3">
                        <label class="form-label small fw-bold mb-1">Target Medical Store Counter <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" name="store_id" id="excel_store_id" required>
                            <?php foreach ($stores as $st): ?>
                                <option value="<?= (int)$st['store_id'] ?>"><?= esc($st['store_name']) ?> (<?= esc($st['building_name'] ?: 'Building') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold mb-1">Upload CSV / Excel Export <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm" name="import_file" id="excel_file" accept=".csv, .txt" required>
                        <small class="text-muted" style="font-size: 11px;">
                            Supported columns: Item Name, Generic, Batch No, Expiry (MM/YY), MRP, PTR/Cost, HSN, GST%, Opening Qty.
                        </small>
                    </div>

                    <div class="alert alert-light border py-2 mb-0" style="font-size: 11px;">
                        <i class="bi bi-download text-primary me-1"></i>
                        <a href="javascript:downloadSampleCsv()" class="text-decoration-none fw-bold">Download Sample CSV Template</a>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm" id="btn_submit_excel">
                        <i class="bi bi-upload me-1"></i> Start Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var activeStoreObj = null;

function copyText(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Copied to clipboard: ' + text);
    });
}

function openTerminalSecurityModal(store) {
    activeStoreObj = store;
    document.getElementById('modal_sec_key').value = store.security_key || '';
    document.getElementById('otp_display_box').style.display = 'none';
    document.getElementById('otp_idle_text').style.display = 'block';

    var modal = new bootstrap.Modal(document.getElementById('terminalSecurityModal'));
    modal.show();
}

function editStoreModal(store) {
    document.getElementById('storeModalTitle').innerHTML = '<i class="bi bi-pencil-square me-1"></i> Edit Medical Store: ' + store.store_name;
    document.getElementById('form_store_id').value = store.store_id;
    document.getElementById('form_store_code').value = store.store_code || '';
    document.getElementById('form_store_slug').value = store.store_slug || '';
    document.getElementById('form_store_name').value = store.store_name || '';
    document.getElementById('form_building_name').value = store.building_name || '';
    document.getElementById('form_floor_no').value = store.floor_no || '';
    document.getElementById('form_room_no').value = store.room_no || '';
    document.getElementById('form_is_main_store').value = store.is_main_store || 0;
    document.getElementById('form_dl_20b').value = store.drug_license_no_20b || '';
    document.getElementById('form_dl_21b').value = store.drug_license_no_21b || '';
    document.getElementById('form_dl_20f').value = store.drug_license_no_20f_x || '';
    document.getElementById('form_gstin').value = store.gstin || '';
    document.getElementById('form_pan').value = store.pan_no || '';
    document.getElementById('form_fssai').value = store.fssai_no || '';
    document.getElementById('form_state_code').value = store.state_code || '07';
    document.getElementById('form_state_name').value = store.state_name || 'Delhi';
    document.getElementById('form_pharmacist_name').value = store.registered_pharmacist_name || '';
    document.getElementById('form_pharmacist_reg_no').value = store.pharmacist_reg_no || '';
    document.getElementById('form_invoice_prefix').value = store.invoice_prefix || 'INV/';
    document.getElementById('form_next_invoice_no').value = store.next_invoice_no || 1001;
    document.getElementById('form_abdm_hfr_id').value = store.abdm_hfr_id || '';
    document.getElementById('form_abdm_hip_id').value = store.abdm_hip_id || '';
    document.getElementById('form_pharmacist_hpr_id').value = store.pharmacist_hpr_id || '';
    document.getElementById('form_upi_id').value = store.upi_id || '';
    document.getElementById('form_terms').value = store.terms_conditions || '';
    document.getElementById('form_contact_phone').value = store.contact_phone || '';
    document.getElementById('form_contact_email').value = store.contact_email || '';
    document.getElementById('form_address').value = store.address || '';
    document.getElementById('form_bank_name').value = store.bank_name || '';
    document.getElementById('form_bank_account_no').value = store.bank_account_no || '';
    document.getElementById('form_bank_ifsc').value = store.bank_ifsc || '';

    var modal = new bootstrap.Modal(document.getElementById('storeModal'));
    modal.show();
}

document.getElementById('btn_add_store').addEventListener('click', function() {
    document.getElementById('storeForm').reset();
    document.getElementById('storeModalTitle').innerHTML = '<i class="bi bi-capsule me-1"></i> Add New Medical Store Counter';
    document.getElementById('form_store_id').value = '0';
    document.getElementById('form_store_code').value = 'ST-' + Math.floor(100 + Math.random() * 900);
    document.getElementById('form_store_slug').value = 'counter' + Math.floor(1 + Math.random() * 9);
    var modal = new bootstrap.Modal(document.getElementById('storeModal'));
    modal.show();
});

document.getElementById('btn_open_excel_import').addEventListener('click', function() {
    var modal = new bootstrap.Modal(document.getElementById('excelImportModal'));
    modal.show();
});

// Save Store Form
$('#storeForm').on('submit', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    $.ajax({
        url: '<?= base_url('setting/admin/medical-store/save') ?>',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            if (res.ok) {
                alert(res.message);
                location.reload();
            } else {
                alert(res.error || 'Failed to save store');
            }
        },
        error: function(err) {
            alert('Request error: ' + err.statusText);
        }
    });
});

// Generate OTP
$('#btn_generate_otp').on('click', function() {
    if (!activeStoreObj) return;
    $.ajax({
        url: '<?= base_url('setting/admin/medical-store/generate-otp') ?>',
        method: 'POST',
        data: { store_id: activeStoreObj.store_id },
        dataType: 'json',
        success: function(res) {
            if (res.ok) {
                document.getElementById('otp_code_val').innerText = res.otp;
                document.getElementById('otp_expiry_val').innerText = 'Valid until ' + res.expiry;
                document.getElementById('otp_display_box').style.display = 'block';
                document.getElementById('otp_idle_text').style.display = 'none';
            } else {
                alert(res.error || 'Failed to generate OTP');
            }
        }
    });
});

// Regenerate Security Key
$('#btn_regen_key').on('click', function() {
    if (!activeStoreObj || !confirm('Are you sure you want to regenerate the Security Key?')) return;
    $.ajax({
        url: '<?= base_url('setting/admin/medical-store/regenerate-key') ?>',
        method: 'POST',
        data: { store_id: activeStoreObj.store_id },
        dataType: 'json',
        success: function(res) {
            if (res.ok) {
                document.getElementById('modal_sec_key').value = res.security_key;
                alert('Security key updated! Please share this with counter staff.');
            }
        }
    });
});

// Revoke Terminal Access
function revokeTerminal(deviceId) {
    if (!confirm('Are you sure you want to revoke counter access for this computer?')) return;
    $.ajax({
        url: '<?= base_url('setting/admin/medical-store/revoke-device') ?>',
        method: 'POST',
        data: { device_id: deviceId },
        dataType: 'json',
        success: function(res) {
            if (res.ok) {
                alert(res.message);
                location.reload();
            }
        }
    });
}

function deleteStore(storeId, storeName) {
    if (!confirm('Are you sure you want to deactivate store: ' + storeName + '?')) return;
    $.ajax({
        url: '<?= base_url('setting/admin/medical-store/delete') ?>',
        method: 'POST',
        data: { store_id: storeId },
        dataType: 'json',
        success: function(res) {
            if (res.ok) {
                alert(res.message);
                location.reload();
            }
        }
    });
}

// Excel / CSV Import Submit
$('#excelImportForm').on('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);
    var btn = $('#btn_submit_excel');
    btn.prop('disabled', true).text('Importing records...');

    $.ajax({
        url: '<?= base_url('setting/admin/medical-store/import-excel') ?>',
        method: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="bi bi-upload me-1"></i> Start Import');
            if (res.ok) {
                alert(res.message);
                location.reload();
            } else {
                alert(res.error || 'Import failed');
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).html('<i class="bi bi-upload me-1"></i> Start Import');
            alert('Import error occurred');
        }
    });
});

function downloadSampleCsv() {
    var csvContent = "Item Name,Generic Name,Category,Packing,Batch No,Expiry Date,MRP,PTR,HSN Code,GST Rate,Opening Qty,Barcode\n" +
        "Augmentin 625 Duo Tablet,Amoxycillin + Clavulanic Acid,Tablet,10 Tab,AUG-991,10/27,204.50,155.00,3004,12,100,890103000001\n" +
        "Pan 40 Tablet,Pantoprazole 40mg,Tablet,15 Tab,PAN-442,04/27,162.00,115.00,3004,12,150,890103000002\n" +
        "Dolo 650 Tablet,Paracetamol 650mg,Tablet,15 Tab,DOL-112,08/27,34.00,22.50,3004,12,200,890103000003\n" +
        "Azithral 500 Tablet,Azithromycin 500mg,Tablet,5 Tab,AZI-771,12/26,125.00,89.00,3004,12,80,890103000004\n";

    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement("a");
    var url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", "Pharmacy_Sample_Stock_Import.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Search and Refresh
$('#btn_store_search').on('click', function() {
    var q = $('#store_search_input').val();
    window.location.href = '<?= base_url('setting/admin/medical-store') ?>?q=' + encodeURIComponent(q);
});

$('#btn_refresh_store_list').on('click', function() {
    window.location.href = '<?= base_url('setting/admin/medical-store') ?>';
});
</script>

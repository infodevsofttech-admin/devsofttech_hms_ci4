<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Doctors</h3>
        <div class="card-tools ms-auto d-flex gap-2 flex-wrap">
            <button onclick="load_form_div('<?= base_url('setting/admin/doctor/specs') ?>','maindiv','Specialities');" type="button" class="btn btn-outline-primary">
                <i class="bi bi-journal-medical"></i>
                Manage Specialities
            </button>
            <button onclick="load_form_div('<?= base_url('setting/admin/doctor/ipd-fee-types') ?>','maindiv','IPD Fee Types');" type="button" class="btn btn-outline-primary">
                <i class="bi bi-cash-coin"></i>
                Manage IPD Fee Types
            </button>
            <button onclick="load_form_div('<?= base_url('setting/admin/doctor/new') ?>','maindiv','Add Doctor');" type="button" class="btn btn-primary">
                <i class="bi bi-person-plus"></i>
                Add New
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped datatable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone Number</th>
                        <th>Email</th>
                        <th>Device Code</th>
                        <th>User Account</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($data)) : ?>
                        <?php foreach ($data as $row) : ?>
                            <tr>
                                <td><?= esc(($row->p_title ?? '') . ' ' . ($row->p_fname ?? '')) ?></td>
                                <td><?= esc($row->mphone1 ?? '') ?></td>
                                <td><?= esc($row->email1 ?? '') ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-warning-subtle text-dark border border-warning font-monospace fs-6 px-2 py-1">
                                            <i class="bi bi-shield-lock me-1 text-warning-emphasis"></i><?= esc($row->device_code ?? '-') ?>
                                        </span>
                                        <?php if (! empty($row->device_code)) : ?>
                                            <button type="button" class="btn btn-outline-secondary btn-sm p-0 px-1 btn-copy-code" data-code="<?= esc($row->device_code) ?>" title="Copy Device Code">
                                                <i class="bi bi-clipboard" style="font-size: 11px;"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (! empty($row->linked_username)) : ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" title="Linked User Account">
                                            <i class="bi bi-person-check me-1"></i><?= esc($row->linked_username) ?>
                                        </span>
                                    <?php else : ?>
                                        <button type="button" onclick="load_form_div('<?= base_url('setting/admin/user-management/new?doctor_id=' . (int)$row->id) ?>','maindiv','Create User');" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;">
                                            <i class="bi bi-person-plus me-1"></i>Link User
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-outline-dark btn-sm me-1 btn-qr-doctor" data-id="<?= (int)$row->id ?>" data-name="<?= esc(($row->p_title ?? '') . ' ' . ($row->p_fname ?? '')) ?>" data-device="<?= esc($row->device_code ?? '') ?>">
                                        <i class="bi bi-qr-code"></i> App Access
                                    </button>
                                    <button onclick="load_form_div('<?= base_url('setting/admin/doctor/' . $row->id) ?>','maindiv','<?= esc(($row->p_title ?? '') . ' ' . ($row->p_fname ?? '')) ?>');" type="button" class="btn btn-success btn-sm">Edit</button>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No doctors found.</td>
                        </tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Doctor App Pairing & QR Code -->
<div class="modal fade" id="doctorQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title fs-6 mb-0" id="docQrModalTitle"><i class="bi bi-phone me-1"></i> DoctorCare Mobile App Access</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <h6 class="fw-bold mb-1 text-dark" id="qr_doc_name">Doctor Name</h6>
                <div class="mb-3">
                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-shield-check me-1"></i>Device Auth Secured</span>
                </div>

                <div class="p-2 border rounded d-inline-block bg-white shadow-sm mb-3">
                    <img id="qr_doc_code_img" src="" alt="Doctor App QR Code" style="width: 170px; height: 170px;" />
                </div>
                
                <div class="card bg-light border-0 text-start mx-auto p-2 mb-2" style="max-width: 340px;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">Doctor ID / Code:</small>
                        <span class="fw-bold font-monospace text-primary" id="qr_card_doc_id">-</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-muted">Device Security Code:</small>
                        <span class="badge bg-warning text-dark font-monospace fs-6 px-2 py-0" id="qr_card_doc_device">-</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">App Security PIN:</small>
                        <span class="small text-secondary fst-italic">Configured in Master</span>
                    </div>
                </div>

                <p class="small text-muted mb-0" style="font-size: 11px;">
                    <i class="bi bi-info-circle me-1"></i> Scan to open the app on mobile, then enter the <strong>Doctor ID</strong>, <strong>Device Security Code</strong>, and <strong>PIN</strong> to pair this phone.
                </p>
            </div>
            <div class="modal-footer bg-light py-2 justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var doctorAppBaseUrl = '<?= base_url('app/doctor') ?>';

    document.addEventListener('click', function(e) {
        var qrBtn = e.target.closest('.btn-qr-doctor');
        if (qrBtn) {
            var docId = qrBtn.getAttribute('data-id') || '0';
            var name = qrBtn.getAttribute('data-name') || '';
            var deviceCode = qrBtn.getAttribute('data-device') || '';
            
            // Clean app URL - no query parameters leaking doctor identity
            var appUrl = doctorAppBaseUrl;
            var qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(appUrl);

            document.getElementById('qr_doc_name').textContent = name;
            document.getElementById('qr_card_doc_id').textContent = 'DOC-' + docId;
            document.getElementById('qr_card_doc_device').textContent = deviceCode || 'N/A';
            document.getElementById('qr_doc_code_img').src = qrApiUrl;

            var modalEl = document.getElementById('doctorQrModal');
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

        var copyBtn = e.target.closest('.btn-copy-code');
        if (copyBtn) {
            var code = copyBtn.getAttribute('data-code') || '';
            if (code) {
                navigator.clipboard.writeText(code).then(function() {
                    alert('Device Security Code copied: ' + code);
                }).catch(function() {
                    prompt('Copy Device Security Code:', code);
                });
            }
        }
    });
})();
</script>

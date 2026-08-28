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
                                    <button type="button" class="btn btn-outline-dark btn-sm me-1 btn-qr-doctor" data-id="<?= (int)$row->id ?>" data-name="<?= esc(($row->p_title ?? '') . ' ' . ($row->p_fname ?? '')) ?>">
                                        <i class="bi bi-qr-code"></i> QR
                                    </button>
                                    <button onclick="load_form_div('<?= base_url('setting/admin/doctor/' . $row->id) ?>','maindiv','<?= esc(($row->p_title ?? '') . ' ' . ($row->p_fname ?? '')) ?>');" type="button" class="btn btn-success btn-sm">Edit</button>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No doctors found.</td>
                        </tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Doctor App QR Code -->
<div class="modal fade" id="doctorQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title fs-6 mb-0" id="docQrModalTitle"><i class="bi bi-qr-code-scan me-1"></i> Doctor App QR Code</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <h6 class="fw-bold mb-1 text-dark" id="qr_doc_name">Doctor Name</h6>
                <span class="badge bg-primary mb-3">Doctor PWA Access</span>
                <div class="p-2 border rounded d-inline-block bg-white shadow-sm mb-3">
                    <img id="qr_doc_code_img" src="" alt="Doctor App QR Code" style="width: 180px; height: 180px;" />
                </div>
                <p class="small text-muted mb-0" style="font-size: 11px;">Scan with mobile phone to launch <strong>DoctorCare PWA</strong> for this doctor profile.</p>
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
            var appUrl = doctorAppBaseUrl + '?doctor_id=' + encodeURIComponent(docId);
            var qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(appUrl);

            document.getElementById('qr_doc_name').textContent = name;
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
    });
})();
</script>

<section class="content finance-doctor-charges-payout">
    <div class="mb-3">
        <h2 class="mb-1">Doctor Charges Payout</h2>
        <p class="text-muted mb-0">Calculate and manage doctor payout from Pathology, Radiology, and Other charge invoices.</p>
    </div>

    <div id="dc_payout_alert"></div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <label class="form-label form-label-sm">From Date</label>
                    <input type="date" class="form-control form-control-sm" id="dc_payout_from_date" value="<?= date('Y-m-01') ?>">
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label form-label-sm">To Date</label>
                    <input type="date" class="form-control form-control-sm" id="dc_payout_to_date" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label form-label-sm">Doctor</label>
                    <select class="form-select form-select-sm" id="dc_payout_doctor_id" name="doctor_id">
                        <option value="">Select Doctor</option>
                        <?php foreach (($doctor_options ?? []) as $doctor): ?>
                            <option value="<?= (int) ($doctor['id'] ?? 0) ?>"><?= esc((string) ($doctor['name'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label form-label-sm">State/Unit</label>
                    <select class="form-select form-select-sm" id="dc_payout_state_unit" name="state_unit">
                        <option value="">All State/Unit</option>
                        <?php foreach (($state_unit_options ?? []) as $opt): ?>
                            <option value="<?= esc((string) $opt) ?>"><?= esc((string) $opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label form-label-sm">Charge Source</label>
                    <select class="form-select form-select-sm" id="dc_payout_source" name="payout_source">
                        <option value="pathology">Pathology Charges</option>
                        <option value="radiology">Radiology Charges</option>
                        <option value="other_items">Other Charge Items</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="button" class="btn btn-primary btn-sm" onclick="refreshDcPayoutSummary()">
                        <i class="bi bi-arrow-repeat me-1"></i>Load Summary
                    </button>
                </div>
            </div>

            <div class="mt-3" id="dc_payout_summary_wrap">
                <div class="text-muted">Select filters and click Load Summary.</div>
            </div>

            <div class="mt-3" id="dc_payout_drafts_wrap">
                <div class="text-muted">Loading payout drafts...</div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="dcPayoutEditModal" tabindex="-1" aria-labelledby="dcPayoutEditModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dcPayoutEditModalLabel">Edit Payout Draft</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dc_edit_payout_id" value="0">
                <div class="mb-2">
                    <label class="form-label form-label-sm">Payout Date</label>
                    <input type="date" class="form-control form-control-sm" id="dc_edit_payout_date">
                </div>
                <div class="mb-2">
                    <label class="form-label form-label-sm">Approved Amount</label>
                    <input type="number" class="form-control form-control-sm" id="dc_edit_approved_amount" min="0" step="0.01">
                </div>
                <div class="mb-0">
                    <label class="form-label form-label-sm">Remarks</label>
                    <textarea class="form-control form-control-sm" id="dc_edit_remarks" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="submitDcPayoutDraftEdit()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    window.showDcPayoutAlert = function (message, ok) {
        var box = document.getElementById('dc_payout_alert');
        if (!box) return;
        box.innerHTML = '<div class="alert ' + (ok ? 'alert-success' : 'alert-danger') + ' alert-dismissible fade show" role="alert">'
            + message
            + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
            + '</div>';
    };

    window.refreshDcPayoutSummary = function () {
        var fromDate = document.getElementById('dc_payout_from_date')?.value || '';
        var toDate = document.getElementById('dc_payout_to_date')?.value || '';
        var doctorId = document.getElementById('dc_payout_doctor_id')?.value || '';
        var stateUnit = document.getElementById('dc_payout_state_unit')?.value || '';
        var payoutSource = document.getElementById('dc_payout_source')?.value || 'pathology';

        load_form_div(
            '<?= base_url('Finance/payout/doctor-charges-summary') ?>?from_date=' + encodeURIComponent(fromDate)
                + '&to_date=' + encodeURIComponent(toDate)
                + '&doctor_id=' + encodeURIComponent(doctorId)
                + '&state_unit=' + encodeURIComponent(stateUnit)
                + '&payout_source=' + encodeURIComponent(payoutSource),
            'dc_payout_summary_wrap'
        );

        load_form_div(
            '<?= base_url('Finance/payout/doctor-charges-drafts-table') ?>?from_date=' + encodeURIComponent(fromDate)
                + '&to_date=' + encodeURIComponent(toDate)
                + '&doctor_id=' + encodeURIComponent(doctorId)
                + '&state_unit=' + encodeURIComponent(stateUnit)
                + '&payout_source=' + encodeURIComponent(payoutSource),
            'dc_payout_drafts_wrap'
        );
    };

    window.editDcPayoutDraft = function (payoutId, payoutDate, approvedAmount, remarks) {
        document.getElementById('dc_edit_payout_id').value = String(payoutId || 0);
        document.getElementById('dc_edit_payout_date').value = String(payoutDate || '');
        document.getElementById('dc_edit_approved_amount').value = String(approvedAmount || '0');
        document.getElementById('dc_edit_remarks').value = String(remarks || '');

        var modalEl = document.getElementById('dcPayoutEditModal');
        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    };

    window.submitDcPayoutDraftEdit = function () {
        var payoutId = document.getElementById('dc_edit_payout_id')?.value || '0';
        var newDate = document.getElementById('dc_edit_payout_date')?.value || '';
        var newAmount = document.getElementById('dc_edit_approved_amount')?.value || '0';
        var newRemarks = document.getElementById('dc_edit_remarks')?.value || '';

        if (!payoutId || payoutId === '0') {
            window.showDcPayoutAlert('Invalid payout draft selected.', false);
            return;
        }

        var fd = new window.FormData();
        fd.append('payout_id', String(payoutId || '0'));
        fd.append('payout_date', String(newDate || ''));
        fd.append('approved_amount', String(newAmount || '0'));
        fd.append('remarks', String(newRemarks || ''));

        fetch('<?= base_url('Finance/payout/doctor-charges-draft-update') ?>', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: fd,
        }).then(function (res) { return res.json().then(function (data) { return {ok: res.ok, data: data}; }); })
        .then(function (result) {
            var ok = result.ok && result.data && result.data.status === 1;
            window.showDcPayoutAlert((result.data && result.data.message) ? result.data.message : (ok ? 'Updated.' : 'Failed.'), ok);
            if (ok) {
                var modalEl = document.getElementById('dcPayoutEditModal');
                if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                }
                window.refreshDcPayoutSummary();
            }
        }).catch(function () {
            window.showDcPayoutAlert('Network or server error while updating draft.', false);
        });
    };

    window.deleteDcPayoutDraft = function (payoutId) {
        if (!window.confirm('Delete this payout draft? Linked charge records will be unlocked for recalculation.')) {
            return;
        }

        var fd = new window.FormData();
        fd.append('payout_id', String(payoutId));

        fetch('<?= base_url('Finance/payout/doctor-charges-draft-delete') ?>', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: fd,
        }).then(function (res) { return res.json().then(function (data) { return {ok: res.ok, data: data}; }); })
        .then(function (result) {
            var ok = result.ok && result.data && result.data.status === 1;
            window.showDcPayoutAlert((result.data && result.data.message) ? result.data.message : (ok ? 'Deleted.' : 'Failed.'), ok);
            if (ok) {
                window.refreshDcPayoutSummary();
            }
        }).catch(function () {
            window.showDcPayoutAlert('Network or server error while deleting draft.', false);
        });
    };

    document.getElementById('dc_payout_source')?.addEventListener('change', refreshDcPayoutSummary);

    refreshDcPayoutSummary();
})();
</script>

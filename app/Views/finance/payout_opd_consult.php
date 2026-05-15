<section class="content finance-opd-payout">
    <div class="mb-3">
        <h2 class="mb-1">OPD Consult Payout</h2>
        <p class="text-muted mb-0">Start payout calculation from OPD consultation and collection data. This screen is enabled for testing and will be extended in the next phase.</p>
    </div>

    <div id="opd_payout_alert"></div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <label class="form-label form-label-sm">From Date</label>
                    <input type="date" class="form-control form-control-sm" id="opd_payout_from_date" value="<?= date('Y-m-01') ?>">
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label form-label-sm">To Date</label>
                    <input type="date" class="form-control form-control-sm" id="opd_payout_to_date" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label form-label-sm">Doctor</label>
                    <select class="form-select form-select-sm" id="opd_payout_doctor_id" name="doctor_id">
                        <option value="">Select Doctor</option>
                        <?php foreach (($doctor_options ?? []) as $doctor): ?>
                            <option value="<?= (int) ($doctor['id'] ?? 0) ?>"><?= esc((string) ($doctor['name'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label form-label-sm">State/Unit</label>
                    <select class="form-select form-select-sm" id="opd_payout_state_unit" name="state_unit">
                        <option value="">All State/Unit</option>
                        <?php foreach (($state_unit_options ?? []) as $opt): ?>
                            <option value="<?= esc((string) $opt) ?>"><?= esc((string) $opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="button" class="btn btn-primary btn-sm" onclick="refreshOpdConsultPayoutSummary()">
                        <i class="bi bi-arrow-repeat me-1"></i>Load Summary
                    </button>
                </div>
            </div>

            <hr>

            <div class="alert alert-info mb-0" role="alert">
                <strong>Phase note:</strong> Menu and page are now available under Accounts And Finance -> Payout -> OPD Consult Payout.
                Calculation grid, policy split engine, and payout approval workflow will be implemented next.
            </div>

            <div class="mt-3" id="opd_consult_payout_summary_wrap">
                <div class="text-muted">Loading summary...</div>
            </div>

            <div class="mt-3" id="opd_consult_payout_drafts_wrap">
                <div class="text-muted">Loading payout drafts...</div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="opdPayoutEditModal" tabindex="-1" aria-labelledby="opdPayoutEditModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="opdPayoutEditModalLabel">Edit Payout Draft</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="opd_edit_payout_id" value="0">
                <div class="mb-2">
                    <label class="form-label form-label-sm">Payout Date</label>
                    <input type="date" class="form-control form-control-sm" id="opd_edit_payout_date">
                </div>
                <div class="mb-2">
                    <label class="form-label form-label-sm">Approved Amount</label>
                    <input type="number" class="form-control form-control-sm" id="opd_edit_approved_amount" min="0" step="0.01">
                </div>
                <div class="mb-0">
                    <label class="form-label form-label-sm">Remarks</label>
                    <textarea class="form-control form-control-sm" id="opd_edit_remarks" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="submitOpdPayoutDraftEdit()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    window.showOpdPayoutAlert = function (message, ok) {
        var box = document.getElementById('opd_payout_alert');
        if (!box) return;
        box.innerHTML = '<div class="alert ' + (ok ? 'alert-success' : 'alert-danger') + ' alert-dismissible fade show" role="alert">'
            + message
            + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
            + '</div>';
    };

    window.refreshOpdConsultPayoutSummary = function () {
        var fromDate = document.getElementById('opd_payout_from_date')?.value || '';
        var toDate = document.getElementById('opd_payout_to_date')?.value || '';
        var doctorId = document.getElementById('opd_payout_doctor_id')?.value || '';
        var stateUnit = document.getElementById('opd_payout_state_unit')?.value || '';

        load_form_div(
            '<?= base_url('Finance/payout/opd-consult-summary') ?>?from_date=' + encodeURIComponent(fromDate)
                + '&to_date=' + encodeURIComponent(toDate)
                + '&doctor_id=' + encodeURIComponent(doctorId)
                + '&state_unit=' + encodeURIComponent(stateUnit),
            'opd_consult_payout_summary_wrap'
        );

        load_form_div(
            '<?= base_url('Finance/payout/opd-consult-drafts-table') ?>?from_date=' + encodeURIComponent(fromDate)
                + '&to_date=' + encodeURIComponent(toDate)
                + '&doctor_id=' + encodeURIComponent(doctorId)
                + '&state_unit=' + encodeURIComponent(stateUnit),
            'opd_consult_payout_drafts_wrap'
        );
    };

    window.editOpdPayoutDraft = function (payoutId, payoutDate, approvedAmount, remarks) {
        document.getElementById('opd_edit_payout_id').value = String(payoutId || 0);
        document.getElementById('opd_edit_payout_date').value = String(payoutDate || '');
        document.getElementById('opd_edit_approved_amount').value = String(approvedAmount || '0');
        document.getElementById('opd_edit_remarks').value = String(remarks || '');

        var modalEl = document.getElementById('opdPayoutEditModal');
        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    };

    window.submitOpdPayoutDraftEdit = function () {
        var payoutId = document.getElementById('opd_edit_payout_id')?.value || '0';
        var newDate = document.getElementById('opd_edit_payout_date')?.value || '';
        var newAmount = document.getElementById('opd_edit_approved_amount')?.value || '0';
        var newRemarks = document.getElementById('opd_edit_remarks')?.value || '';

        if (!payoutId || payoutId === '0') {
            window.showOpdPayoutAlert('Invalid payout draft selected.', false);
            return;
        }

        var fd = new window.FormData();
        fd.append('payout_id', String(payoutId || '0'));
        fd.append('payout_date', String(newDate || ''));
        fd.append('approved_amount', String(newAmount || '0'));
        fd.append('remarks', String(newRemarks || ''));

        fetch('<?= base_url('Finance/payout/opd-consult-draft-update') ?>', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: fd,
        }).then(function (res) { return res.json().then(function (data) { return {ok: res.ok, data: data}; }); })
        .then(function (result) {
            var ok = result.ok && result.data && result.data.status === 1;
            window.showOpdPayoutAlert((result.data && result.data.message) ? result.data.message : (ok ? 'Updated.' : 'Failed.'), ok);
            if (ok) {
                var modalEl = document.getElementById('opdPayoutEditModal');
                if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                }
                window.refreshOpdConsultPayoutSummary();
            }
        }).catch(function () {
            window.showOpdPayoutAlert('Network or server error while updating draft.', false);
        });
    };

    window.deleteOpdPayoutDraft = function (payoutId) {
        if (!window.confirm('Delete this payout draft? Linked OPDs will be unlocked for recalculation.')) {
            return;
        }

        var fd = new window.FormData();
        fd.append('payout_id', String(payoutId));

        fetch('<?= base_url('Finance/payout/opd-consult-draft-delete') ?>', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: fd,
        }).then(function (res) { return res.json().then(function (data) { return {ok: res.ok, data: data}; }); })
        .then(function (result) {
            var ok = result.ok && result.data && result.data.status === 1;
            window.showOpdPayoutAlert((result.data && result.data.message) ? result.data.message : (ok ? 'Deleted.' : 'Failed.'), ok);
            if (ok) {
                window.refreshOpdConsultPayoutSummary();
            }
        }).catch(function () {
            window.showOpdPayoutAlert('Network or server error while deleting draft.', false);
        });
    };

    refreshOpdConsultPayoutSummary();
})();
</script>

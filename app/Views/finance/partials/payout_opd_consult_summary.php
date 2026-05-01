<?php
$summary = $summary ?? [];
$doctorBreakdown = $doctor_breakdown ?? [];
$fromDate = (string) ($from_date ?? '');
$toDate = (string) ($to_date ?? '');
$doctorName = trim((string) ($doctor_name ?? 'All Doctors'));
$stateUnit = trim((string) ($state_unit ?? ''));
$stateUnitLabel = trim((string) ($state_unit_label ?? 'State/Unit'));
$sourceType = trim((string) ($source_type ?? 'consultation'));
$sourceLabel = trim((string) ($source_label ?? 'OPD Consultation'));
$scopeLabel = trim((string) ($scope_label ?? 'Completed OPDs'));
$unitLabel = trim((string) ($unit_label ?? 'OPDs'));
$isConsultation = $sourceType === 'consultation';

$formatDate = static function (string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);
    return $timestamp === false ? $value : date('d-m-Y', $timestamp);
};

$money = static function ($value): string {
    return 'Rs ' . number_format((float) $value, 2);
};
?>

<div class="card border-0 bg-light mb-3">
    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <strong>Source:</strong> <?= esc($sourceLabel) ?>
        </div>
        <div>
            <strong>Summary Range:</strong> <?= esc($formatDate($fromDate)) ?> to <?= esc($formatDate($toDate)) ?>
        </div>
        <div>
            <strong>Doctor:</strong> <?= esc($doctorName !== '' ? $doctorName : 'All Doctors') ?>
        </div>
        <div>
            <strong><?= esc($stateUnitLabel) ?>:</strong> <?= esc($stateUnit !== '' ? $stateUnit : 'All State/Unit') ?>
        </div>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-xl-3 col-md-4 col-6"><div class="card border-primary"><div class="card-body py-2"><div class="small text-muted"><?= esc($scopeLabel) ?></div><div class="h5 mb-0 text-primary"><?= (int) ($summary['completed_opd'] ?? 0) ?></div></div></div></div>
    <?php if ($isConsultation): ?>
    <div class="col-xl-3 col-md-4 col-6"><div class="card border-secondary"><div class="card-body py-2"><div class="small text-muted">Routine OPDs</div><div class="h5 mb-0"><?= (int) ($summary['routine_opd'] ?? 0) ?></div></div></div></div>
    <div class="col-xl-3 col-md-4 col-6"><div class="card border-danger"><div class="card-body py-2"><div class="small text-muted">Emergency OPDs</div><div class="h5 mb-0 text-danger"><?= (int) ($summary['emergency_opd'] ?? 0) ?></div></div></div></div>
    <?php else: ?>
    <div class="col-xl-3 col-md-4 col-6"><div class="card border-secondary"><div class="card-body py-2"><div class="small text-muted">Calculated Invoices</div><div class="h5 mb-0"><?= (int) ($summary['calculated_opd'] ?? 0) ?></div></div></div></div>
    <div class="col-xl-3 col-md-4 col-6"><div class="card border-danger"><div class="card-body py-2"><div class="small text-muted">Locked Credit</div><div class="h5 mb-0 text-danger"><?= esc($money($summary['credit_amount'] ?? 0)) ?></div></div></div></div>
    <?php endif; ?>
    <div class="col-xl-3 col-md-4 col-6"><div class="card border-info"><div class="card-body py-2"><div class="small text-muted">Doctors Involved</div><div class="h5 mb-0 text-info"><?= (int) ($summary['doctor_count'] ?? 0) ?></div></div></div></div>
</div>

<?php if ($isConsultation): ?>
<div class="row g-2 mb-3">
    <div class="col-xl-3 col-md-4 col-6"><div class="card border-dark"><div class="card-body py-2"><div class="small text-muted">Already Calculated OPDs</div><div class="h5 mb-0\"><?= (int) ($summary['calculated_opd'] ?? 0) ?></div></div></div></div>
</div>
<?php endif; ?>

<div class="row g-2 mb-3">
    <div class="col-xl-3 col-md-4 col-6"><div class="card border-success"><div class="card-body py-2"><div class="small text-muted"><?= $isConsultation ? 'Gross OPD Revenue' : 'Gross Charges Amount' ?></div><div class="h5 mb-0 text-success"><?= esc($money($summary['gross_amount'] ?? 0)) ?></div></div></div></div>
    <div class="col-xl-3 col-md-4 col-6"><div class="card border-success"><div class="card-body py-2"><div class="small text-muted">Total Received</div><div class="h5 mb-0 text-success"><?= esc($money($summary['total_received'] ?? 0)) ?></div></div></div></div>
    <div class="col-xl-3 col-md-4 col-6"><div class="card border-warning"><div class="card-body py-2"><div class="small text-muted">Cash Received</div><div class="h5 mb-0 text-warning"><?= esc($money($summary['cash_received'] ?? 0)) ?></div></div></div></div>
    <div class="col-xl-3 col-md-4 col-6"><div class="card border-primary"><div class="card-body py-2"><div class="small text-muted">Bank Received</div><div class="h5 mb-0 text-primary"><?= esc($money($summary['bank_received'] ?? 0)) ?></div></div></div></div>
</div>

<div class="row g-2 mb-3">
    <div class="col-xl-3 col-md-4 col-6"><div class="card border-dark"><div class="card-body py-2"><div class="small text-muted">Total Credit Amount</div><div class="h5 mb-0"><?= esc($money($summary['credit_amount'] ?? 0)) ?></div></div></div></div>
    <div class="col-xl-3 col-md-4 col-6"><div class="card border-dark"><div class="card-body py-2"><div class="small text-muted">Organizational Credit</div><div class="h5 mb-0"><?= esc($money($summary['org_credit_amount'] ?? 0)) ?></div></div></div></div>
    <div class="col-xl-3 col-md-4 col-6"><div class="card border-info"><div class="card-body py-2"><div class="small text-muted"><?= $isConsultation ? 'Approved Consents' : 'Charge Group' ?></div><div class="h5 mb-0 text-info"><?= $isConsultation ? (int) ($summary['approved_consents'] ?? 0) : esc($sourceLabel) ?></div></div></div></div>
    <div class="col-xl-3 col-md-4 col-6"><div class="card border-secondary"><div class="card-body py-2"><div class="small text-muted">Payout Records / Amount</div><div class="h6 mb-0"><?= (int) ($summary['payout_count'] ?? 0) ?> / <?= esc($money($summary['payout_amount'] ?? 0)) ?></div></div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <strong>Doctor-wise <?= esc($sourceLabel) ?> and Collection Summary</strong>
        <span class="small text-muted"><?= $isConsultation ? 'Running and New are shown for internal review; routine figure is non-emergency total.' : 'Collection is allocated by invoice share for selected charge category.' ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Doctor</th>
                        <th class="text-end"><?= esc($unitLabel) ?></th>
                        <?php if ($isConsultation): ?>
                        <th class="text-end">Routine</th>
                        <th class="text-end">Emergency</th>
                        <th class="text-end">Running</th>
                        <th class="text-end">New</th>
                        <?php endif; ?>
                        <th class="text-end">Gross Amount</th>
                        <th class="text-end">Cash</th>
                        <th class="text-end">Bank</th>
                        <th class="text-end">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($doctorBreakdown)): ?>
                        <tr>
                            <td colspan="<?= $isConsultation ? '11' : '7' ?>" class="text-center text-muted py-3">No summary records found for the selected filters.</td>
                        </tr>
                    <?php else: ?>
                        <?php $sr = 1; foreach ($doctorBreakdown as $row): ?>
                            <tr>
                                <td><?= $sr++ ?></td>
                                <td><?= esc((string) ($row['doctor_name'] ?? '')) ?></td>
                                <td class="text-end"><?= (int) ($row['completed_opd'] ?? 0) ?></td>
                                <?php if ($isConsultation): ?>
                                <td class="text-end"><?= (int) ($row['routine_opd'] ?? 0) ?></td>
                                <td class="text-end"><?= (int) ($row['emergency_opd'] ?? 0) ?></td>
                                <td class="text-end"><?= (int) ($row['running_opd'] ?? 0) ?></td>
                                <td class="text-end"><?= (int) ($row['new_opd'] ?? 0) ?></td>
                                <?php endif; ?>
                                <td class="text-end"><?= esc($money($row['gross_amount'] ?? 0)) ?></td>
                                <td class="text-end"><?= esc($money($row['cash_received'] ?? 0)) ?></td>
                                <td class="text-end"><?= esc($money($row['bank_received'] ?? 0)) ?></td>
                                <td class="text-end"><?= esc($money($row['credit_amount'] ?? 0)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $baseForPayout = (float) (($summary['total_received'] ?? 0) > 0 ? ($summary['total_received'] ?? 0) : ($summary['gross_amount'] ?? 0)); ?>
<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white">
        <strong>Payout Calculation (Preview: <?= esc($sourceLabel) ?>)</strong>
    </div>
    <div class="card-body">
        <div class="row g-2 align-items-end mb-2">
            <div class="col-lg-3 col-md-6">
                <label class="form-label form-label-sm">Base Amount</label>
                <input type="number" class="form-control form-control-sm" id="opd_payout_base_amount" value="<?= number_format($baseForPayout, 2, '.', '') ?>" step="0.01" min="0">
            </div>
            <div class="row g-1 mb-2">
                <div class="col-xl-2 col-md-3 col-6"><div class="card border-start border-primary border-2 border-top-0 border-end-0 border-bottom-0 rounded-0"><div class="card-body py-1 px-2"><div class="text-muted" style="font-size:.7rem"><?= esc($scopeLabel) ?></div><div class="fw-bold text-primary"><?= (int) ($summary['completed_opd'] ?? 0) ?></div></div></div></div>
                <?php if ($isConsultation): ?>
                <div class="col-xl-2 col-md-3 col-6"><div class="card border-start border-secondary border-2 border-top-0 border-end-0 border-bottom-0 rounded-0"><div class="card-body py-1 px-2"><div class="text-muted" style="font-size:.7rem">Routine OPDs</div><div class="fw-bold"><?= (int) ($summary['routine_opd'] ?? 0) ?></div></div></div></div>
                <div class="col-xl-2 col-md-3 col-6"><div class="card border-start border-danger border-2 border-top-0 border-end-0 border-bottom-0 rounded-0"><div class="card-body py-1 px-2"><div class="text-muted" style="font-size:.7rem">Emergency OPDs</div><div class="fw-bold text-danger"><?= (int) ($summary['emergency_opd'] ?? 0) ?></div></div></div></div>
                <div class="col-xl-2 col-md-3 col-6"><div class="card border-start border-dark border-2 border-top-0 border-end-0 border-bottom-0 rounded-0"><div class="card-body py-1 px-2"><div class="text-muted" style="font-size:.7rem">Already Calculated</div><div class="fw-bold"><?= (int) ($summary['calculated_opd'] ?? 0) ?></div></div></div></div>
                <?php else: ?>
                <div class="col-xl-2 col-md-3 col-6"><div class="card border-start border-secondary border-2 border-top-0 border-end-0 border-bottom-0 rounded-0"><div class="card-body py-1 px-2"><div class="text-muted" style="font-size:.7rem">Already Calculated</div><div class="fw-bold"><?= (int) ($summary['calculated_opd'] ?? 0) ?></div></div></div></div>
                <div class="col-xl-2 col-md-3 col-6"><div class="card border-start border-danger border-2 border-top-0 border-end-0 border-bottom-0 rounded-0"><div class="card-body py-1 px-2"><div class="text-muted" style="font-size:.7rem">Locked Credit</div><div class="fw-bold text-danger" style="font-size:.85rem"><?= esc($money($summary['credit_amount'] ?? 0)) ?></div></div></div></div>
                <?php endif; ?>
                <div class="col-xl-2 col-md-3 col-6"><div class="card border-start border-info border-2 border-top-0 border-end-0 border-bottom-0 rounded-0"><div class="card-body py-1 px-2"><div class="text-muted" style="font-size:.7rem">Doctors Involved</div><div class="fw-bold text-info"><?= (int) ($summary['doctor_count'] ?? 0) ?></div></div></div></div>
                <div class="col-xl-2 col-md-3 col-6"><div class="card border-start border-success border-2 border-top-0 border-end-0 border-bottom-0 rounded-0"><div class="card-body py-1 px-2"><div class="text-muted" style="font-size:.7rem"><?= $isConsultation ? 'Gross OPD Revenue' : 'Gross Charges' ?></div><div class="fw-bold text-success" style="font-size:.85rem"><?= esc($money($summary['gross_amount'] ?? 0)) ?></div></div></div></div>
                <div class="col-xl-2 col-md-3 col-6"><div class="card border-start border-success border-2 border-top-0 border-end-0 border-bottom-0 rounded-0"><div class="card-body py-1 px-2"><div class="text-muted" style="font-size:.7rem">Total Received</div><div class="fw-bold text-success" style="font-size:.85rem"><?= esc($money($summary['total_received'] ?? 0)) ?></div></div></div></div>
                <div class="col-xl-2 col-md-3 col-6"><div class="card border-start border-warning border-2 border-top-0 border-end-0 border-bottom-0 rounded-0"><div class="card-body py-1 px-2"><div class="text-muted" style="font-size:.7rem">Cash Received</div><div class="fw-bold text-warning" style="font-size:.85rem"><?= esc($money($summary['cash_received'] ?? 0)) ?></div></div></div></div>
                <div class="col-xl-2 col-md-3 col-6"><div class="card border-start border-primary border-2 border-top-0 border-end-0 border-bottom-0 rounded-0"><div class="card-body py-1 px-2"><div class="text-muted" style="font-size:.7rem">Bank Received</div><div class="fw-bold text-primary" style="font-size:.85rem"><?= esc($money($summary['bank_received'] ?? 0)) ?></div></div></div></div>
                <div class="col-xl-2 col-md-3 col-6"><div class="card border-start border-dark border-2 border-top-0 border-end-0 border-bottom-0 rounded-0"><div class="card-body py-1 px-2"><div class="text-muted" style="font-size:.7rem">Total Credit</div><div class="fw-bold" style="font-size:.85rem"><?= esc($money($summary['credit_amount'] ?? 0)) ?></div></div></div></div>
                <div class="col-xl-2 col-md-3 col-6"><div class="card border-start border-dark border-2 border-top-0 border-end-0 border-bottom-0 rounded-0"><div class="card-body py-1 px-2"><div class="text-muted" style="font-size:.7rem">Org. Credit</div><div class="fw-bold" style="font-size:.85rem"><?= esc($money($summary['org_credit_amount'] ?? 0)) ?></div></div></div></div>
                <div class="col-xl-2 col-md-3 col-6"><div class="card border-start border-info border-2 border-top-0 border-end-0 border-bottom-0 rounded-0"><div class="card-body py-1 px-2"><div class="text-muted" style="font-size:.7rem"><?= $isConsultation ? 'Approved Consents' : 'Charge Group' ?></div><div class="fw-bold text-info"><?= $isConsultation ? (int) ($summary['approved_consents'] ?? 0) : esc($sourceLabel) ?></div></div></div></div>
                <div class="col-xl-2 col-md-3 col-6"><div class="card border-start border-secondary border-2 border-top-0 border-end-0 border-bottom-0 rounded-0"><div class="card-body py-1 px-2"><div class="text-muted" style="font-size:.7rem">Payout Drafts / Amt</div><div class="fw-bold" style="font-size:.8rem"><?= (int) ($summary['payout_count'] ?? 0) ?> / <?= esc($money($summary['payout_amount'] ?? 0)) ?></div></div></div></div>
            </div>
<script>
(function () {
    function toNumber(id) {
        var el = document.getElementById(id);
        if (!el) return 0;
        var value = parseFloat(el.value || '0');
        return isNaN(value) ? 0 : value;
    }

    function formatMoney(value) {
        var num = isNaN(value) ? 0 : value;
        return 'Rs ' + num.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function recalc() {
        var baseAmount = toNumber('opd_payout_base_amount');
        var doctorShare = toNumber('opd_payout_doctor_share');
        var hospitalShare = toNumber('opd_payout_hospital_share');
        var deductions = toNumber('opd_payout_deductions');
        var adjustments = toNumber('opd_payout_adjustments');

        var doctorGross = (baseAmount * doctorShare) / 100;
        var hospitalGross = (baseAmount * hospitalShare) / 100;
        var netPayable = doctorGross - deductions + adjustments;

        var doctorEl = document.getElementById('opd_payout_doctor_gross');
        var hospitalEl = document.getElementById('opd_payout_hospital_gross');
        var netEl = document.getElementById('opd_payout_net_payable');

        if (doctorEl) doctorEl.textContent = formatMoney(doctorGross);
        if (hospitalEl) hospitalEl.textContent = formatMoney(hospitalGross);
        if (netEl) netEl.textContent = formatMoney(netPayable);
    }

    ['opd_payout_base_amount', 'opd_payout_doctor_share', 'opd_payout_hospital_share', 'opd_payout_deductions', 'opd_payout_adjustments']
        .forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', recalc);
            }
        });

    var draftBtn = document.getElementById('opd_payout_create_draft_btn');
    if (draftBtn) {
        draftBtn.addEventListener('click', function () {
            var fromDate = document.getElementById('opd_payout_from_date')?.value || '';
            var toDate = document.getElementById('opd_payout_to_date')?.value || '';
            var doctorId = document.getElementById('opd_payout_doctor_id')?.value || '';
            var stateUnit = document.getElementById('opd_payout_state_unit')?.value || '';

            if (!doctorId) {
                if (typeof window.showOpdPayoutAlert === 'function') {
                    window.showOpdPayoutAlert('Please select a doctor before creating payout draft.', false);
                }
                return;
            }

            var fd = new window.FormData();
            fd.append('from_date', fromDate);
            fd.append('to_date', toDate);
            fd.append('doctor_id', doctorId);
            fd.append('state_unit', stateUnit);
            fd.append('base_amount', String(toNumber('opd_payout_base_amount')));
            fd.append('doctor_share', String(toNumber('opd_payout_doctor_share')));
            fd.append('hospital_share', String(toNumber('opd_payout_hospital_share')));
            fd.append('deductions', String(toNumber('opd_payout_deductions')));
            fd.append('adjustments', String(toNumber('opd_payout_adjustments')));

            fetch('<?= base_url('Finance/payout/opd-consult-draft-create') ?>', {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: fd,
            })
            .then(function (res) { return res.json().then(function (data) { return {ok: res.ok, data: data}; }); })
            .then(function (result) {
                var ok = result.ok && result.data && result.data.status === 1;
                var msg = (result.data && result.data.message) ? result.data.message : (ok ? 'Payout draft created.' : 'Unable to create payout draft.');
                if (ok && result.data.case_reference) {
                    msg += ' Ref: ' + result.data.case_reference;
                }
                if (typeof window.showOpdPayoutAlert === 'function') {
                    window.showOpdPayoutAlert(msg, ok);
                }
                if (ok && typeof window.refreshOpdConsultPayoutSummary === 'function') {
                    window.refreshOpdConsultPayoutSummary();
                }
            })
            .catch(function () {
                if (typeof window.showOpdPayoutAlert === 'function') {
                    window.showOpdPayoutAlert('Network or server error while creating payout draft.', false);
                }
            });
        });
    }

    recalc();
})();
</script>

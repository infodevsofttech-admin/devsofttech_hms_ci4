<?php
$discharge = $discharge_info[0] ?? null;
$ipd = $ipd_info ?? null;
$canEditDischarge = (bool) ($can_edit_discharge ?? false);
$ipdId = (int) ($ipd->id ?? 0);
$statusValue = (int) ($discharge->discarge_patient_status ?? 0);
$statusMap = [
    0 => 'Status Pending',
    1 => 'Improved/ Recovered',
    2 => 'LAMA',
    3 => 'Referred',
    4 => 'Satisfactory',
    5 => 'Expired',
    6 => 'Admission Cancelled',
    7 => 'Discharge on Request',
];

$dischargeDate = (string) ($discharge->discharge_date ?? date('Y-m-d'));
$dischargeTime = (string) ($discharge->discharge_time ?? date('H:i'));
if (strlen($dischargeTime) > 5) {
    $dischargeTime = substr($dischargeTime, 0, 5);
}
?>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Current Discharge Snapshot</h5>
                <p class="mb-1"><strong>Status :</strong> <?= esc($discharge->status_desc ?? ($statusMap[$statusValue] ?? 'Status Pending')) ?></p>
                <p class="mb-1"><strong>Discharge Date :</strong> <?= esc((string) ($discharge->discharge_date ?? '')) ?></p>
                <p class="mb-1"><strong>Discharge Time :</strong> <?= esc((string) ($discharge->discharge_time ?? '')) ?></p>
                <p class="mb-1"><strong>Discharge By :</strong> <?= esc((string) ($discharge->discharge_by ?? '')) ?></p>
                <p class="mb-1"><strong>Balance Approved By :</strong> <?= esc((string) ($discharge->discharge_balance_user ?? '')) ?></p>
                <p class="mb-0"><strong>Balance Remark :</strong> <?= esc((string) ($discharge->discharge_balance_remark ?? '')) ?></p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100 border-primary-subtle">
            <div class="card-body">
                <h5 class="card-title">Current Financial Adjustments</h5>
                <p class="mb-1"><strong>Deduction 1 :</strong> <?= esc(number_format((float) ($ipd->Discount ?? 0), 2)) ?> | <?= esc((string) ($ipd->Discount_Remark ?? '')) ?></p>
                <p class="mb-1"><strong>Deduction 2 :</strong> <?= esc(number_format((float) ($ipd->Discount2 ?? 0), 2)) ?> | <?= esc((string) ($ipd->Discount_Remark2 ?? '')) ?></p>
                <p class="mb-1"><strong>Deduction 3 :</strong> <?= esc(number_format((float) ($ipd->Discount3 ?? 0), 2)) ?> | <?= esc((string) ($ipd->Discount_Remark3 ?? '')) ?></p>
                <hr>
                <p class="mb-1"><strong>Additional Charge 1 :</strong> <?= esc(number_format((float) ($ipd->chargeamount1 ?? 0), 2)) ?> | <?= esc((string) ($ipd->charge1 ?? '')) ?></p>
                <p class="mb-0"><strong>Additional Charge 2 :</strong> <?= esc(number_format((float) ($ipd->chargeamount2 ?? 0), 2)) ?> | <?= esc((string) ($ipd->charge2 ?? '')) ?></p>
            </div>
        </div>
    </div>
</div>

<?php if ($canEditDischarge) : ?>
    <div class="card mt-3 border-warning-subtle">
        <div class="card-body">
            <h5 class="card-title mb-3">Discharge Process Controls</h5>

            <div class="alert alert-info py-2 px-3 mb-3">Update deductions/additional charges first, then confirm discharge status/date/time.</div>

            <div class="row g-3">
                <?php for ($slot = 1; $slot <= 3; $slot++) : ?>
                    <?php $amountField = $slot === 1 ? 'Discount' : 'Discount' . $slot; ?>
                    <?php $remarkField = $slot === 1 ? 'Discount_Remark' : 'Discount_Remark' . $slot; ?>
                    <div class="col-12">
                        <div class="border rounded p-3 bg-light-subtle">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label">Deduction Remark <?= $slot ?></label>
                                    <input type="text" class="form-control" id="dis_remark_<?= $slot ?>" value="<?= esc((string) ($ipd->{$remarkField} ?? '')) ?>" placeholder="Deduction remark">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Deduction Amount <?= $slot ?></label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="dis_amount_<?= $slot ?>" value="<?= esc((string) number_format((float) ($ipd->{$amountField} ?? 0), 2, '.', '')) ?>" placeholder="0.00">
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-primary w-100 js-update-discount" data-slot="<?= $slot ?>">Update Deduction <?= $slot ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>

                <?php for ($slot = 1; $slot <= 2; $slot++) : ?>
                    <?php $amountField = 'chargeamount' . $slot; ?>
                    <?php $remarkField = 'charge' . $slot; ?>
                    <div class="col-12">
                        <div class="border rounded p-3 bg-light-subtle">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label">Additional Charge Remark <?= $slot ?></label>
                                    <input type="text" class="form-control" id="charge_remark_<?= $slot ?>" value="<?= esc((string) ($ipd->{$remarkField} ?? '')) ?>" placeholder="Charge remark">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Additional Charge Amount <?= $slot ?></label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="charge_amount_<?= $slot ?>" value="<?= esc((string) number_format((float) ($ipd->{$amountField} ?? 0), 2, '.', '')) ?>" placeholder="0.00">
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-primary w-100 js-update-charge" data-slot="<?= $slot ?>">Update Additional Charge <?= $slot ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>

            <hr class="my-4">

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Discharge Date</label>
                    <input type="date" class="form-control" id="discharge_date" value="<?= esc($dischargeDate) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Discharge Time</label>
                    <input type="time" class="form-control" id="discharge_time" value="<?= esc($dischargeTime) ?>" step="60">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Patient Status</label>
                    <select class="form-select" id="discharge_status">
                        <?php foreach ($statusMap as $statusId => $statusLabel) : ?>
                            <option value="<?= $statusId ?>" <?= $statusId === $statusValue ? 'selected' : '' ?>><?= esc($statusLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Balance Approved By</label>
                    <input type="text" class="form-control" id="balance_user" value="<?= esc((string) ($discharge->discharge_balance_user ?? '')) ?>" placeholder="Full name">
                </div>
                <div class="col-md-9">
                    <label class="form-label">Discharge / Balance Remark</label>
                    <input type="text" class="form-control" id="discharge_remark" value="<?= esc((string) ($discharge->discharge_remark ?? '')) ?>" placeholder="Discharge remark">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Balance Remark</label>
                    <input type="text" class="form-control" id="balance_remark" value="<?= esc((string) ($discharge->discharge_balance_remark ?? '')) ?>" placeholder="Balance remark">
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="button" id="btn-update-discharge-process" class="btn btn-danger">Update Discharge Status</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            if (!window.jQuery) {
                return;
            }

            var ipdId = <?= $ipdId ?>;

            function refreshDischargeTab(response) {
                if (response && Number(response.update || 0) === 1 && response.html) {
                    $('#tab_discharge_content').html(response.html);
                    if (typeof window.notify === 'function') {
                        window.notify('success', 'Discharge Process', response.message || 'Updated successfully');
                    }
                } else if (response && response.message) {
                    if (typeof window.notify === 'function') {
                        window.notify('danger', 'Discharge Process', response.message);
                    } else {
                        alert(response.message);
                    }
                }
            }

            function post(url, payload) {
                return $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    data: payload
                });
            }

            $(document).on('click', '#tab_discharge_content .js-update-discount', function() {
                var slot = Number($(this).data('slot') || 0);
                if (slot < 1 || slot > 3) {
                    return;
                }

                post('<?= site_url('billing/ipd/panel/' . $ipdId . '/discharge/discount') ?>/' + slot, {
                    amount: $('#dis_amount_' + slot).val(),
                    remark: $('#dis_remark_' + slot).val()
                }).done(refreshDischargeTab).fail(function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Unable to update discount';
                    alert(msg);
                });
            });

            $(document).on('click', '#tab_discharge_content .js-update-charge', function() {
                var slot = Number($(this).data('slot') || 0);
                if (slot < 1 || slot > 2) {
                    return;
                }

                post('<?= site_url('billing/ipd/panel/' . $ipdId . '/discharge/charge') ?>/' + slot, {
                    amount: $('#charge_amount_' + slot).val(),
                    remark: $('#charge_remark_' + slot).val()
                }).done(refreshDischargeTab).fail(function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Unable to update additional charge';
                    alert(msg);
                });
            });

            $(document).on('click', '#tab_discharge_content #btn-update-discharge-process', function() {
                var status = Number($('#discharge_status').val() || 0);
                var disTime = String($('#discharge_time').val() || '').trim();
                if (status === 0) {
                    alert('Please select patient status.');
                    return;
                }
                if (disTime === '') {
                    alert('Please enter discharge time.');
                    return;
                }

                post('<?= site_url('billing/ipd/panel/' . $ipdId . '/discharge/update') ?>', {
                    discharge_status: status,
                    discharge_date: $('#discharge_date').val(),
                    discharge_time: disTime,
                    discharge_remark: $('#discharge_remark').val(),
                    balance_user: $('#balance_user').val(),
                    balance_remark: $('#balance_remark').val()
                }).done(refreshDischargeTab).fail(function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Unable to update discharge status';
                    alert(msg);
                });
            });
        })();
    </script>
<?php else : ?>
    <div class="alert alert-secondary mt-3 mb-0">
        You do not have permission to edit discharge process details. Contact your administrator for billing.ipd.discharge.edit.
    </div>
<?php endif; ?>

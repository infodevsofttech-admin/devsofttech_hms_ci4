<?php
$payment = $ipd_payment[0] ?? null;
$ipd = $ipd_master[0] ?? null;
$patient = $patient_master[0] ?? null;

$hospitalName = defined('H_Name') ? (string) constant('H_Name') : 'Hospital';
$hospitalAddress1 = defined('H_address_1') ? (string) constant('H_address_1') : '';
$hospitalAddress2 = defined('H_address_2') ? (string) constant('H_address_2') : '';
$hospitalPhone = defined('H_phone_No') ? (string) constant('H_phone_No') : '';
$hospitalLogoName = defined('H_logo') ? trim((string) constant('H_logo')) : '';

$logoSrc = '';
if ($hospitalLogoName !== '') {
    $logoSrc = base_url('assets/images/' . rawurlencode($hospitalLogoName));
}

// QR Code Data: IPD-{IPD_ID}:{PAYMENT_ID}:{AMOUNT}:{TIMESTAMP}
$qrData = 'IPD-' . $ipd->id . ':' . $payid . ':' . $payment->amount . ':' . date('dmYHi');
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($qrData);
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <strong>IPD Payment Receipt #<?= esc($payid ?? '') ?></strong>
            <br><small class="text-muted"><?= esc($payment->Amount_str ?? '') ?></small>
        </div>
        <button class="btn btn-sm btn-primary" onclick="window.open('<?= site_url('billing/ipd/payment/pdf-receipt/' . (int) $ipd_id . '/' . (int) $payid) ?>', '_blank');" title="Print Receipt as PDF">
            <i class="bi bi-printer"></i> Print Receipt
        </button>
    </div>
    <div class="card-body">
        <!-- Hospital Header -->
        <div class="row mb-3 pb-3 border-bottom">
            <div class="col-md-2 text-center">
                <?php if ($logoSrc !== ''): ?>
                    <img src="<?= esc($logoSrc) ?>" alt="Hospital Logo" style="max-height:80px; max-width:100%;">
                <?php else: ?>
                    <div class="text-muted"><small>No Logo</small></div>
                <?php endif; ?>
            </div>
            <div class="col-md-8 text-center">
                <h5 class="mb-1"><strong><?= esc($hospitalName) ?></strong></h5>
                <small>
                    <?php 
                    $address = trim($hospitalAddress1 . ', ' . $hospitalAddress2, ', ');
                    if ($address !== '') echo esc($address) . '<br>';
                    if ($hospitalPhone !== '') echo 'Phone: ' . esc($hospitalPhone);
                    ?>
                </small>
            </div>
            <div class="col-md-2 text-center">
                <img src="<?= esc($qrCodeUrl) ?>" alt="QR Code" style="max-height:80px;">
                <small class="d-block mt-1">Receipt #<?= esc($payid ?? '') ?></small>
            </div>
        </div>

        <!-- Receipt Details -->
        <div class="row mb-3">
            <div class="col-md-6">
                <p class="mb-1"><strong>Patient Information</strong></p>
                <p class="mb-1"><strong>UHID :</strong> <?= esc($patient->p_code ?? '') ?></p>
                <p class="mb-1"><strong>Name :</strong> <?= esc($patient->title ?? '') ?> <?= esc($patient->p_fname ?? '') ?></p>
                <p class="mb-1"><strong>Gender :</strong> <?= esc($patient->xgender ?? '') ?> / <strong>Age :</strong> <?= esc($patient->age ?? '') ?></p>
                <p class="mb-0"><strong>Phone :</strong> <?= esc($patient->mphone1 ?? '') ?></p>
            </div>
            <div class="col-md-6 text-end">
                <p class="mb-1"><strong>Payment Information</strong></p>
                <p class="mb-1"><strong>IPD Code :</strong> <?= esc($ipd->ipd_code ?? '') ?></p>
                <p class="mb-1"><strong>Payment Date :</strong> <?= esc($payment->payment_date_str ?? '') ?></p>
                <p class="mb-0"><strong>Receipt #:</strong> <?= esc($payid ?? '') ?></p>
            </div>
        </div>

        <hr>

        <!-- Amount Details -->
        <div class="row mb-3">
            <div class="col-md-12">
                <p class="mb-2"><strong>Payment Amount : </strong><span class="text-success" style="font-size: 1.3em;">Rs. <?= esc(number_format((float) ($payment->amount ?? 0), 2)) ?></span></p>
                <p class="mb-2"><strong>Amount in Words : </strong>Rs. <?= esc(number_to_word((float) ($payment->amount ?? 0))) ?></p>
                <p class="mb-0"><strong>Prepared By :</strong> <?= esc($payment->update_by ?? '') ?></p>
            </div>
        </div>

        <hr class="my-3">

        <div class="text-center mt-4">
            <p class="small text-muted">This is a computer-generated receipt. No signature is required.</p>
        </div>
    </div>
</div>

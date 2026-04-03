<?php
$payment  = $ipd_payment[0]  ?? null;
$ipd      = $ipd_master[0]   ?? null;
$patient  = $patient_master[0] ?? null;

$hospitalName     = defined('H_Name')      ? (string) constant('H_Name')      : 'Hospital';
$hospitalAddress1 = defined('H_address_1') ? (string) constant('H_address_1') : '';
$hospitalAddress2 = defined('H_address_2') ? (string) constant('H_address_2') : '';
$hospitalPhone    = defined('H_phone_No')  ? (string) constant('H_phone_No')  : '';
$hospitalLogoName = defined('H_logo')      ? trim((string) constant('H_logo')) : '';

// Absolute path for mPDF logo loading
$logoPath = '';
if ($hospitalLogoName !== '') {
    $candidate = FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . ltrim($hospitalLogoName, '/\\');
    if (is_file($candidate)) {
        $logoPath = $candidate;
    }
}

$barContent = ':IPD-' . (int)$ipd_id . ':' . (int)$payid . ':' . ($payment->amount ?? 0) . ':PT' . date('dmYhis');
?>
<style>
@page {
    sheet-size: A4;
    margin-top: 4cm;
    margin-bottom: 14cm;
    margin-left: 0.5cm;
    margin-right: 0.5cm;
    margin-header: 0.5cm;
    header: html_myHeader;
}
body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
</style>

<htmlpageheader name="myHeader">
<table cellspacing="0" style="font-size:10px;width:100%;border-style:inset;">
<tr>
    <td style="width:20%;vertical-align:top;">
        <?php if ($logoPath !== ''): ?>
        <img style="width:90px;vertical-align:top;" src="<?= $logoPath ?>" />
        <?php endif; ?>
    </td>
    <td style="width:60%;vertical-align:top;">
        <p align="center" style="font-size:26px;"><b><?= htmlspecialchars($hospitalName) ?></b></p>
        <p align="center" style="font-size:15px;">
            <?= htmlspecialchars(trim($hospitalAddress1 . ', ' . $hospitalAddress2, ', ')) ?>
            <?php if ($hospitalPhone !== ''): ?><br>Phone: <?= htmlspecialchars($hospitalPhone) ?><?php endif; ?>
        </p>
    </td>
    <td style="width:20%;vertical-align:top;text-align:right;">
        <barcode code="<?= htmlspecialchars($barContent) ?>" size="0.8" type="QR" error="M" class="barcode" />
    </td>
</tr>
</table>
<h3 align="center">IPD Payment <?= htmlspecialchars($payment->Amount_str ?? '') ?> Receipt : <?= (int) $payid ?></h3>
</htmlpageheader>

<table width="100%" style="font-size:12px;">
<tr>
    <td width="50%">
        Patient Info.<br/>
        UHID : <?= htmlspecialchars($patient->p_code ?? '') ?><br>
        Name : <strong><?= htmlspecialchars($patient->title ?? '') ?> <?= htmlspecialchars(strtoupper($patient->p_fname ?? '')) ?></strong><br>
        <?= htmlspecialchars($patient->p_relative ?? '') ?> <?= htmlspecialchars(strtoupper($patient->p_rname ?? '')) ?><br>
        Sex : <b><?= htmlspecialchars($patient->xgender ?? '') ?> <?= htmlspecialchars($patient->age ?? '') ?></b> P.No. :<?= htmlspecialchars($patient->mphone1 ?? '') ?>
    </td>
    <td width="50%" style="text-align:right;">
        IPD No. : <?= htmlspecialchars($ipd->ipd_code ?? '') ?><br>
        Date : <strong><?= htmlspecialchars($payment->payment_date_str ?? '') ?></strong>
    </td>
</tr>
</table>
<hr/>
<?php
$payMode = ((int) ($payment->payment_mode ?? 0) === 1) ? 'Cash' : 'Bank';
$isBankPayment = ((int) ($payment->payment_mode ?? 0) !== 1);
$paymentSource = trim((string) ($payment->pay_type ?? ''));
$bankName = trim((string) ($payment->bank_name ?? ''));
$cardTranId = trim((string) ($payment->card_tran_id ?? ''));
$bankMachine = trim((string) ($payment->bankcard_machine ?? ''));
$cardNumber = trim((string) ($payment->cust_card ?? ''));
$cardRemark = trim((string) ($payment->card_remark ?? ''));
?>
<p style="font-size:15px;">
    <b>IPD <?= htmlspecialchars($payment->Amount_str ?? '') ?> Amount : </b>Rs. <?= number_format((float)($payment->amount ?? 0), 2) ?><br/>
    <b>Payment Mode : </b><?= htmlspecialchars($payMode) ?><br/>
    <?php if ($isBankPayment): ?>
        <?php if ($paymentSource !== ''): ?>
            <b>Bank/UPI Type : </b><?= htmlspecialchars($paymentSource) ?><br/>
        <?php endif; ?>
        <?php if ($bankName !== ''): ?>
            <b>Bank Name : </b><?= htmlspecialchars($bankName) ?><br/>
        <?php endif; ?>
        <?php if ($cardTranId !== ''): ?>
            <b>Transaction ID : </b><?= htmlspecialchars($cardTranId) ?><br/>
        <?php endif; ?>
        <?php if ($bankMachine !== ''): ?>
            <b>Bank/Card Machine : </b><?= htmlspecialchars($bankMachine) ?><br/>
        <?php endif; ?>
        <?php if ($cardNumber !== ''): ?>
            <b>Card/Reference No : </b><?= htmlspecialchars($cardNumber) ?><br/>
        <?php endif; ?>
        <?php if ($cardRemark !== ''): ?>
            <b>Bank Remark : </b><?= htmlspecialchars($cardRemark) ?><br/>
        <?php endif; ?>
    <?php endif; ?>
    <b>Amount In Words : </b>Rs.<?= htmlspecialchars(number_to_word((float)($payment->amount ?? 0))) ?> Only
</p>
<p>
    <b>Prepared By :</b><?= htmlspecialchars($payment->update_by ?? '') ?>
</p>
<table width="100%" style="margin-top:30px;">
<tr>
    <td width="60%">&nbsp;</td>
    <td width="40%" style="text-align:center;">
        <div style="border-top:1px solid #333;padding-top:4px;font-size:11px;"><b>Authorised Signature</b></div>
    </td>
</tr>
</table>
<p style="text-align:center;font-size:9px;color:#666;margin-top:20px;border-top:1px dashed #aaa;padding-top:6px;">
    This is a computer-generated receipt and does not require a physical signature.
</p>

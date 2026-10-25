<?php
$records = $records ?? [];
$fromDate = $from_date ?? date('Y-m-01');
$toDate = $to_date ?? date('Y-m-d');
$totalUnits = (float) ($total_units ?? 0);
$totalLoss = (float) ($total_loss ?? 0);
?>
<style>
    body { font-family: sans-serif; font-size: 10px; color: #222; }
    h2 { margin: 0 0 4px 0; color: #b02a37; }
    .subtitle { font-size: 11px; margin-bottom: 8px; color: #555; }
    .meta { margin-bottom: 12px; font-size: 10px; color: #444; border-bottom: 1px solid #ddd; padding-bottom: 6px; }
    .summary-box { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .summary-box th, .summary-box td { border: 1px solid #bbb; padding: 5px 8px; }
    .summary-box th { background: #f2f2f2; text-align: left; }
    .data-table { border-collapse: collapse; width: 100%; }
    .data-table th, .data-table td { border: 1px solid #999; padding: 4px 6px; }
    .data-table th { background: #343a40; color: #fff; font-size: 9.5px; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .total-row { background: #f8f9fa; font-weight: bold; }
    .sign-section { margin-top: 40px; width: 100%; }
    .sign-box { width: 33%; text-align: center; font-size: 10px; padding-top: 40px; border-top: 1px dashed #777; }
</style>

<h2>EXPIRED &amp; DAMAGED MEDICINE DISPOSAL REGISTER</h2>
<div class="subtitle"><strong>Audit Certificate for Destruction &amp; Stock Loss (NABH / BMW Rules Compliant)</strong></div>
<div class="meta">
    <strong>Report Period:</strong> <?= esc(date('d-m-Y', strtotime($fromDate))) ?> to <?= esc(date('d-m-Y', strtotime($toDate))) ?> &nbsp;|&nbsp;
    <strong>Generated On:</strong> <?= esc((string) ($generated_at ?? date('d-m-Y H:i'))) ?>
</div>

<table class="summary-box">
    <tr>
        <th>Total Disposed Batches</th>
        <th>Total Quantity Written Off / Lost</th>
        <th>Total Purchase Value (Financial Loss)</th>
    </tr>
    <tr>
        <td><strong><?= count($records) ?></strong> batches</td>
        <td style="color:#b02a37;"><strong><?= number_format($totalUnits, 2) ?></strong> units</td>
        <td style="color:#0d6efd;"><strong>₹ <?= number_format($totalLoss, 2) ?></strong></td>
    </tr>
</table>

<table class="data-table">
    <thead>
    <tr>
        <th style="width: 25px;">#</th>
        <th>Voucher No</th>
        <th>Date</th>
        <th>Medicine Name</th>
        <th>Batch No</th>
        <th>Exp. Date</th>
        <th>Supplier</th>
        <th class="text-right">Qty</th>
        <th class="text-right">Rate (₹)</th>
        <th class="text-right">Loss Amt (₹)</th>
        <th>Reason</th>
        <th>Disposal Method</th>
        <th>Disposed By</th>
    </tr>
    </thead>
    <tbody>
    <?php if (empty($records)): ?>
        <tr><td colspan="13" class="text-center" style="padding:15px;">No medicine disposal records found for the selected period.</td></tr>
    <?php endif; ?>
    <?php $sr = 1; foreach ($records as $r): ?>
        <tr>
            <td class="text-center"><?= $sr++ ?></td>
            <td><strong><?= esc($r->disposal_no ?? ('EXP#' . $r->id)) ?></strong></td>
            <td><?= esc(date('d-m-Y', strtotime($r->disposal_date))) ?></td>
            <td><?= esc($r->item_name ?? '-') ?></td>
            <td><?= esc($r->batch_no ?? '-') ?></td>
            <td><?= esc($r->expiry_date ? date('d-m-Y', strtotime($r->expiry_date)) : '-') ?></td>
            <td><?= esc($r->supplier_name ?? '-') ?></td>
            <td class="text-right" style="font-weight:bold; color:#b02a37;"><?= esc(number_format((float) ($r->qty ?? 0), 2)) ?></td>
            <td class="text-right"><?= esc(number_format((float) ($r->purchase_unit_rate ?? 0), 2)) ?></td>
            <td class="text-right" style="font-weight:bold;"><?= esc(number_format((float) ($r->total_cost_amount ?? 0), 2)) ?></td>
            <td><?= esc($r->disposal_reason ?? 'Expired') ?></td>
            <td><?= esc($r->disposal_method ?? '-') ?></td>
            <td><?= esc($r->disposed_by_name ?? '-') ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (! empty($records)): ?>
        <tr class="total-row">
            <td colspan="7" class="text-right">Total Summary:</td>
            <td class="text-right" style="color:#b02a37;"><?= number_format($totalUnits, 2) ?></td>
            <td></td>
            <td class="text-right" style="color:#b02a37;">₹ <?= number_format($totalLoss, 2) ?></td>
            <td colspan="3"></td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

<table class="sign-section">
    <tr>
        <td class="sign-box">
            <strong>Pharmacist / Store Incharge</strong><br>
            Signature &amp; Date
        </td>
        <td style="width: 5%;"></td>
        <td class="sign-box">
            <strong>Medical Superintendent / Administrator</strong><br>
            Verified &amp; Approved
        </td>
        <td style="width: 5%;"></td>
        <td class="sign-box">
            <strong>Bio-Medical Waste Handover</strong><br>
            Agency Rep. Signature / Stamp
        </td>
    </tr>
</table>

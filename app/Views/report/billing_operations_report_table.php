<?php
$opdRows = $opd_rows ?? [];
$invoiceSummary = $invoice_summary ?? [];
$invoiceRows = $invoice_rows ?? [];
$ipdSummary = $ipd_summary ?? [];
$ipdPaymentRows = $ipd_payment_rows ?? [];

$formatIndianDateTime = static function ($value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('d-m-Y H:i:s', $timestamp);
};

$minRangeDisplay = $formatIndianDateTime($min_range ?? '');
$maxRangeDisplay = $formatIndianDateTime($max_range ?? '');
?>
<div class="mb-3 small text-muted">
    Date: <strong><?= esc($minRangeDisplay) ?></strong> to <strong><?= esc($maxRangeDisplay) ?></strong>
    <?php if (! empty($doctor_filter)): ?>
        | Doctor: <strong><?= esc((string) $doctor_filter) ?></strong>
    <?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="border rounded p-2 bg-light">
            <div class="small text-muted">OPD Doctors</div>
            <div class="h5 mb-0"><?= count($opdRows) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="border rounded p-2 bg-light">
            <div class="small text-muted">Total Invoices</div>
            <div class="h5 mb-0"><?= (int) ($invoiceSummary['invoice_count'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="border rounded p-2 bg-light">
            <div class="small text-muted">Invoice Amount</div>
            <div class="h5 mb-0"><?= number_format((float) ($invoiceSummary['total_invoice_amount'] ?? 0), 2) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="border rounded p-2 bg-light">
            <div class="small text-muted">IPD Net Received</div>
            <div class="h5 mb-0"><?= number_format((float) ($ipdSummary['net_received'] ?? 0), 2) ?></div>
        </div>
    </div>
</div>

<h6>1) OPD Done By Doctors</h6>
<table class="table table-sm table-bordered align-middle mb-4">
    <thead>
        <tr>
            <th style="width:60px;">#</th>
            <th>Doctor</th>
            <th style="width:160px;">Total OPDs</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($opdRows)): ?>
            <tr><td colspan="3" class="text-center text-muted">No OPD data found.</td></tr>
        <?php else: ?>
            <?php $sr = 1; foreach ($opdRows as $row): ?>
                <tr>
                    <td><?= $sr++ ?></td>
                    <td><?= esc((string) ($row->doc_name ?? '')) ?></td>
                    <td><?= (int) ($row->total_opd ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<h6>2) Charges Invoice / Tests / Amount / Received</h6>
<div class="table-responsive mb-2">
    <table class="table table-sm table-bordered align-middle">
        <thead>
            <tr>
                <th>Total Tests</th>
                <th>Total Test Qty</th>
                <th>Total Test Amount</th>
                <th>Total Received</th>
                <th>Total Refund</th>
                <th>Net Received</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= (int) ($invoiceSummary['total_test_count'] ?? 0) ?></td>
                <td><?= number_format((float) ($invoiceSummary['total_test_qty'] ?? 0), 2) ?></td>
                <td><?= number_format((float) ($invoiceSummary['total_test_amount'] ?? 0), 2) ?></td>
                <td><?= number_format((float) ($invoiceSummary['total_received'] ?? 0), 2) ?></td>
                <td><?= number_format((float) ($invoiceSummary['total_refund'] ?? 0), 2) ?></td>
                <td><?= number_format(((float) ($invoiceSummary['total_received'] ?? 0)) - ((float) ($invoiceSummary['total_refund'] ?? 0)), 2) ?></td>
            </tr>
        </tbody>
    </table>
</div>

<table class="table table-sm table-bordered align-middle mb-4">
    <thead>
        <tr>
            <th>#</th>
            <th>Invoice</th>
            <th>Date</th>
            <th>Test Count</th>
            <th>Test Amount</th>
            <th>Invoice Amount</th>
            <th>Received</th>
            <th>Pending</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($invoiceRows)): ?>
            <tr><td colspan="8" class="text-center text-muted">No invoice data found.</td></tr>
        <?php else: ?>
            <?php $sr = 1; foreach ($invoiceRows as $row): ?>
                <tr>
                    <td><?= $sr++ ?></td>
                    <td><?= esc((string) ($row->inv_name ?? ('INV#' . ($row->id ?? '')))) ?></td>
                    <td><?= esc($formatIndianDateTime((string) ($row->inv_date ?? ''))) ?></td>
                    <td><?= (int) ($row->test_count ?? 0) ?></td>
                    <td><?= number_format((float) ($row->test_amount ?? 0), 2) ?></td>
                    <td><?= number_format((float) ($row->invoice_amount ?? 0), 2) ?></td>
                    <td><?= number_format((float) ($row->net_received ?? 0), 2) ?></td>
                    <td><?= number_format((float) ($row->pending_amount ?? 0), 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<h6>3) IPD Related Payment Received</h6>
<div class="table-responsive mb-2">
    <table class="table table-sm table-bordered align-middle">
        <thead>
            <tr>
                <th>IPD Cases</th>
                <th>Total Received</th>
                <th>Total Refund</th>
                <th>Net Received</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= (int) ($ipdSummary['ipd_count'] ?? 0) ?></td>
                <td><?= number_format((float) ($ipdSummary['total_received'] ?? 0), 2) ?></td>
                <td><?= number_format((float) ($ipdSummary['total_refund'] ?? 0), 2) ?></td>
                <td><?= number_format((float) ($ipdSummary['net_received'] ?? 0), 2) ?></td>
            </tr>
        </tbody>
    </table>
</div>

<table class="table table-sm table-bordered align-middle">
    <thead>
        <tr>
            <th>#</th>
            <th>IPD Code</th>
            <th>Patient</th>
            <th>Received</th>
            <th>Refund</th>
            <th>Net</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($ipdPaymentRows)): ?>
            <tr><td colspan="6" class="text-center text-muted">No IPD payment data found.</td></tr>
        <?php else: ?>
            <?php $sr = 1; foreach ($ipdPaymentRows as $row): ?>
                <?php
                    $received = (float) ($row->total_received ?? 0);
                    $refund = (float) ($row->total_refund ?? 0);
                ?>
                <tr>
                    <td><?= $sr++ ?></td>
                    <td><?= esc((string) ($row->ipd_code ?? ('IPD#' . ($row->ipd_id ?? '')))) ?></td>
                    <td><?= esc((string) ($row->patient_name ?? '')) ?></td>
                    <td><?= number_format($received, 2) ?></td>
                    <td><?= number_format($refund, 2) ?></td>
                    <td><?= number_format($received - $refund, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

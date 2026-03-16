<style>
@page {
    margin-top: 4.5cm;
    margin-bottom: 1.2cm;
    margin-left: 0.5cm;
    margin-right: 0.5cm;
    margin-header: 0.5cm;
    margin-footer: 0.5cm;
    header: html_myHeader;
    footer: html_myFooter;
}
</style>

<?php $invoice = $purchase_return_invoice[0] ?? null; ?>
<?php
$storeName = defined('M_store') ? (string) constant('M_store') : (defined('H_Name') ? (string) constant('H_Name') : 'Medical Store');
$storeAddress = defined('M_address') ? (string) constant('M_address') : '';
$storePhone = defined('M_Phone_Number') ? (string) constant('M_Phone_Number') : '';
$storeEmail = defined('H_Email') ? (string) constant('H_Email') : '';
$storeGst = defined('H_Med_GST') ? (string) constant('H_Med_GST') : '';
$storeLic = defined('M_LIC') ? (string) constant('M_LIC') : '';
?>

<htmlpageheader name="myHeader">
<table style="font-size:10px;" cellpadding="5">
    <tr>
        <td colspan="2"><p align="center" style="font-size:15px;"><?= esc($storeName) ?></p></td>
    </tr>
    <tr>
        <td style="width:60%;vertical-align:top;">
            <p align="center" style="font-size:10px;">
                <?= esc($storeAddress) ?>
                <?php if ($storePhone !== ''): ?>
                    <br>Phone: <?= esc($storePhone) ?>
                <?php endif; ?>
                <?php if ($storeEmail !== ''): ?>
                    , Email: <?= esc($storeEmail) ?>
                <?php endif; ?>
                <?php if ($storeGst !== ''): ?>
                    <br><b>GST: <?= esc($storeGst) ?></b>
                <?php endif; ?>
                <?php if ($storeLic !== ''): ?>
                    L.No: <?= esc($storeLic) ?>
                <?php endif; ?>
            </p>
        </td>
        <td style="width:40%;vertical-align:top;">
            <p style="font-size:10px;">
                Invoice To:<br>
                <strong>Supplier: <?= esc((string) ($invoice->name_supplier ?? '-')) ?></strong><br>
                <strong>Purchase Return Invoice No.: <?= esc((string) ($invoice->Invoice_no ?? '')) ?></strong><br>
                <strong>Invoice Date: <?= esc((string) ($invoice->str_date_of_invoice ?? '')) ?></strong><br>
            </p>
        </td>
    </tr>
</table>
<h3 align="center">Purchase Return</h3>
</htmlpageheader>

<htmlpagefooter name="myFooter">
<table width="100%" style="font-size:12px;">
    <tr>
        <td width="33%">Page : {PAGENO}/{nbpg}</td>
        <td width="66%" style="text-align:right;">Invoice No.: <?= esc((string) ($invoice->Invoice_no ?? '')) ?> / Name : <?= esc((string) ($invoice->name_supplier ?? '-')) ?></td>
    </tr>
</table>
</htmlpagefooter>

<table style="font-size:12px;width:100%;border-style:solid;border-width:0.1mm;padding:0.5mm;" topntail="true" autosize="true">
    <tr>
        <th style="width:20px;text-align:right;">#</th>
        <th style="width:40px;text-align:right;">RecNo</th>
        <th style="width:200px;text-align:left;">Item Name</th>
        <th style="width:100px;text-align:left;">Batch No</th>
        <th style="width:50px;text-align:left;">Exp.</th>
        <th style="width:100px;text-align:right;">MRP of Pack</th>
        <th style="width:100px;text-align:right;">Return Unit Qty/ Pack.</th>
        <th style="width:100px;text-align:right;">P. Rate/Unit</th>
        <th style="width:100px;text-align:right;">GST %</th>
        <th style="width:100px;text-align:right;">Amount</th>
        <th style="width:100px;text-align:right;">GST Amount</th>
        <th style="width:100px;text-align:right;">Net Amount</th>
    </tr>
    <?php $srno = 0; $totalAmt = 0.0; ?>
    <?php foreach (($purchase_return_invoice_item ?? []) as $row): ?>
        <?php
            $srno++;
            $lineAmt = round((float) ($row->r_qty ?? 0) * (float) ($row->purchase_unit_rate ?? 0), 2);
            $gstPer = (float) ($row->gst_per ?? 0);
            $gstAmount = round(($gstPer * $lineAmt) / 100, 2);
            $netAmt = round($lineAmt + $gstAmount, 2);
            $totalAmt += $netAmt;
        ?>
        <tr>
            <td style="text-align:right;"><?= $srno ?></td>
            <td style="text-align:right;"><?= (int) ($row->r_id ?? 0) ?></td>
            <td style="text-align:left;"><?= esc((string) ($row->Item_name ?? '')) ?></td>
            <td style="text-align:left;"><?= esc((string) ($row->batch_no_r_s ?? ($row->batch_no ?? ''))) ?></td>
            <td style="text-align:left;"><?= esc((string) ($row->exp_date_str ?? '')) ?></td>
            <td style="text-align:right;"><?= esc((string) ($row->mrp ?? 0)) ?></td>
            <td style="text-align:right;"><?= esc((string) floatval($row->r_qty ?? 0)) ?> / <?= esc((string) floatval($row->qty_pak ?? 0)) ?></td>
            <td style="text-align:right;"><?= esc((string) ($row->purchase_price ?? 0)) ?> / <?= esc((string) ($row->purchase_unit_rate ?? 0)) ?></td>
            <td style="text-align:right;"><?= esc((string) $gstPer) ?></td>
            <td style="text-align:right;"><?= esc((string) $lineAmt) ?></td>
            <td style="text-align:right;"><?= esc((string) $gstAmount) ?></td>
            <td style="text-align:right;"><?= esc((string) $netAmt) ?></td>
        </tr>
    <?php endforeach; ?>
    <tr>
        <th>#</th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        <th>Total</th>
        <th style="text-align:right;"><?= esc((string) round($totalAmt, 2)) ?></th>
    </tr>
</table>

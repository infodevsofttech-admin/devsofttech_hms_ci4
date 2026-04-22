<style>
@page {
    margin-top: 4.2cm;
    margin-bottom: 1.2cm;
    margin-left: 1cm;
    margin-right: 0.5cm;
    margin-header: 0.5cm;
    margin-footer: 0.5cm;
    header: html_myHeader;
    footer: html_myFooter;
}
</style>

<htmlpageheader name="myHeader">
<table style="font-size:12px;" cellpadding="5">
    <tr>
        <td style="width:60%;vertical-align:top;">
            <p align="center" style="font-size:25px;">
                <?php echo defined('H_Name') ? constant('H_Name') : ''; ?>
            </p>
            <p align="center" style="font-size:12px;">
                <?php echo defined('M_address') ? constant('M_address') : ''; ?>,
                <?php echo defined('H_address_2') ? constant('H_address_2') : ''; ?>
            </p>
        </td>
        <td style="width:40%;">
            <p>
                <strong>Location Name :</strong> <?= esc($invoice_stock_master[0]->issued_name ?? '') ?><br/>
                <strong>Indent No. :</strong> <?= esc($invoice_stock_master[0]->indent_code ?? '') ?><br/>
                <strong>Date :</strong> <?= esc($invoice_stock_master[0]->str_inv_date ?? '') ?>
            </p>
        </td>
    </tr>
</table>
<h3 align="center">Indent</h3>
</htmlpageheader>

<htmlpagefooter name="myFooter">
<table width="100%" style="font-size:10px;">
    <tr>
        <td width="33%">Page : {PAGENO}/{nbpg}</td>
        <td width="67%" style="text-align:right;">
            Invoice No.: <?= esc($invoice_stock_master[0]->indent_code ?? '') ?>
            / Location : <?= esc($invoice_stock_master[0]->issued_name ?? '') ?>
        </td>
    </tr>
</table>
</htmlpagefooter>

<table style="font-size:10px;width:100%;border-collapse:collapse;border-style:solid;border-width:0.1mm;padding:3mm;" autosize="1">
<?php
$srno       = 0;
$head_start = 0;
foreach ($inv_items as $row) {
    if ($head_start == 0) {
        echo '<tr style="border-style:solid;border-width:0.1mm;"><td colspan="7" style="font-size:18px;"><b>Invoice ID : </b>'
             . esc($row->indent_code) . '  [<b>Dated : </b><i>' . esc($row->str_inv_date) . '</i>]</td></tr>';
        echo '<tr>';
        echo '<th style="width:20px;">#</th>';
        echo '<th align="left" style="width:200px;">Item Name</th>';
        echo '<th align="left">Batch No</th>';
        echo '<th>Exp.</th>';
        echo '<th align="right">Rate</th>';
        echo '<th align="right">Qty.</th>';
        echo '<th align="right">Gross Amt</th>';
        echo '</tr>';
    }
    $srno++;
    $head_start++;

    if (isset($row->sale_return) && $row->sale_return == 1) {
        echo '<tr><td colspan="7">Sale Return</td></tr>';
    }

    echo '<tr>';
    echo '<td style="width:20px;">' . $srno . '</td>';
    echo '<td style="width:200px;">' . esc($row->item_Name) . ' ' . esc($row->formulation) . '</td>';
    echo '<td>' . esc($row->batch_no) . '</td>';
    echo '<td>' . esc($row->expiry) . '</td>';
    echo '<td align="right">' . $row->price . '</td>';
    echo '<td align="right">' . $row->qty . '</td>';
    echo '<td align="right">' . $row->amount . '</td>';
    echo '</tr>';
}

if (count($invoice_stock_master) > 0) {
    echo '<tr style="font-size:12px;border-style:solid;border-width:0.1mm;">';
    echo '<th style="width:20px;">#</th>';
    echo '<th colspan="5" align="right">Total</th>';
    echo '<th align="right">' . ($invoice_stock_master[0]->gross_amount ?? '') . '</th>';
    echo '</tr>';
}
?>
</table>

<br/><br/>
<table width="100%" style="font-size:12px;">
    <tr>
        <td style="width:60%;"></td>
        <td style="width:40%;text-align:center;">
            <b>For <?php echo defined('H_Name') ? constant('H_Name') : ''; ?></b>
        </td>
    </tr>
</table>

<style>
    @page {
        margin-top: 5cm;
        margin-bottom: 2cm;
        margin-left: 1cm;
        margin-right: 0.5cm;

        margin-header: 0.5cm;
        margin-footer: 1cm;
        header: html_myHeader;
        footer: html_myFooter;
    }
</style>

<htmlpageheader name="myHeader">

</htmlpageheader>
<htmlpagefooter name="myFooter">
    <table width="100%" style="font-size: 12px;">
        <tr>
            <td width="33%">Page : {PAGENO}/{nbpg}</td>
            <td width="66%" style="text-align: right;">IPD-ID:<?= $ipd_master[0]->ipd_code ?> / UHID:<?= $ipd_master[0]->p_code ?> /Name : <?= $ipd_master[0]->p_fname ?></td>
        </tr>
    </table>
</htmlpagefooter>

<h3>Consolidated Summary of Medicine, Disposal & Sundries Used</h3>
<hr />
<table width="100%">
    <tr>
        <td width="50%" style="vertical-align: top;">
            <b>Patient Info.</b>
        </td>
        <td width="50%" style="vertical-align: top;" >
            <b>Treating Consultant / Authorized Team Doctor</b>
        </td>
    </tr>
    <tr>
        <td width="50%" style="vertical-align: top;">
        <strong><?= $ipd_master[0]->p_fname ?></strong> <br/> <b>UHID ID :</b> <?= $ipd_master[0]->p_code ?> <br/> <b>IPD Code :</b> <?= $ipd_master[0]->ipd_code ?>
        </td>
        <td width="50%" style="vertical-align: top;">
        <?=$doc_list?>
        </td>
    </tr>
</table>

<hr />
<h3>Medicine Name</h3>
<table cellspacing="0" style="font-size: 11px;border: 1px solid;">
    <thead>
        <tr>
            <th style="border: 1px solid;width: 10px;">Sr.No.</th>
            <th style="border: 1px solid;width: 400px;">Medicine Name</th>
            <th style="border: 1px solid;width: 40px;">Qty</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $srno = 0;

        foreach ($inv_items as $row) {
            $srno = $srno + 1;

            echo '<tr>';
            echo '<td style="border: 1px solid;width: 10px;">' . $srno . '</td>';
            echo '<td style="border: 1px solid;width: 400px;">' . $row->formulation . ' ' . $row->item_Name . '<br/>(<i>' . $row->genericname . '</i>)</td>';
            echo '<td style="border: 1px solid;width: 40px;text-align: right;">', $row->i_qty_total, '</td>';
            echo '</tr>';
        }
        ?>
    </tbody>
</table>
<hr />
<h3>Disposal & Sundries</h3>

<table cellspacing="0" style="font-size: 11px;border: 1px solid;">
    <thead>
        <tr>
            <th style="border: 1px solid;width: 10px;">Sr.No.</th>
            <th style="border: 1px solid;width: 400px;">Disposal & Sundries</th>
            <th style="border: 1px solid;width: 40px;">Qty</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $srno = 0;

        foreach ($inv_items_sur as $row) {
            $srno = $srno + 1;
            echo '<tr>';
            echo '<td style="border: 1px solid;width: 10px;">' . $srno . '</td>';
            echo '<td style="border: 1px solid;width: 400px;">' . $row->formulation . ' ' . $row->item_Name . '</td>';
            echo '<td style="border: 1px solid;width: 40px;text-align: right;">', $row->i_qty_total, '</td>';
            echo '</tr>';
        }
        ?>
    </tbody>
</table>



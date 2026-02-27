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
    <table style="font-size: 12px;" cellpadding="5">
        <tr>
            <td style="width: 60%;vertical-align: top;">
                <p align="center" style="font-size: 25px;"><?= M_store ?></p>
                <p align="center" style="font-size: 12px"><?= M_address ?>, Uttarakhand<br>
                    <?php
                    if (M_Phone_Number != '') {
                        echo 'Phone: ' . M_Phone_Number;
                    }

                    if (H_Email != '') {
                        echo ' ,Email: ' . H_Email;
                    }

                    echo '<br>';

                    if (H_Med_GST != '') {
                        echo '<b>GST: ' . H_Med_GST . '</b>';
                    }

                    if (M_LIC != '') {
                        echo ' L.No: ' . M_LIC;
                    }
                    ?>
            </td>
            <td style="width: 40%;">
                <p style="font-size: 12px">
                    Statement of Supplier :
                    <strong><?= $med_supplier[0]->name_supplier ?></strong><br>
                </p>
            </td>
        </tr>
    </table>
    <h3 align="center">Statement between <?= $Statement_between ?></h3>
</htmlpageheader>
<htmlpagefooter name="myFooter">
    <table width="100%" style="font-size: 10px;">
        <tr>
            <td width="33%">Page : {PAGENO}/{nbpg}</td>
            <td width="66%" style="text-align: right;">Supplier:<?= $med_supplier[0]->name_supplier ?> </td>
        </tr>
    </table>
</htmlpagefooter>
<p>Opening Balance : <?= $balance_till_date ?> / Total Cr. : <?= $cr_total ?> / Total Dr. : <?= $dr_total ?>
    / Closing Balance : <?= $balance_till_date_close ?></p>
<table style="font-size: 10px;width:100%;border-collapse: collapse;border-style:solid ;border-width:0.1mm;padding:3mm;" autosize="1">
    <thead>
        <tr>
            <th>Sr.No</th>
            <th>Date Tran</th>
            <th>Description</th>
            <th align="right">Credit</th>
            <th align="right">Debit</th>
            <th align="right">Balance</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $i = 0;
        $balance = $balance_till_date;
        ?>
        <tr>
            <td><?php echo $i; ?></td>
            <td><?php echo MysqlDate_to_str($LAst_date_tran); ?></td>
            <td>Opening Balance : Last Date of Tran</td>
            <td align="right"></td>
            <td align="right"></td>
            <td align="right"><?php echo $balance ?> </td>
        </tr>
        <?php

        foreach ($med_supplier_ledger as $c) {
            $i += 1;
            $amt = $c->credit_debit == 0 ? $c->amount : $c->amount * -1;
            $balance += $amt;
        ?>
            <tr>
                <td><?php echo $i; ?></td>
                <td><?php echo MysqlDate_to_str($c->tran_date); ?></td>
                <td><?php echo $c->mode_desc . ' ' . $c->tran_desc; ?></td>
                <td align="right"><?php echo ($c->credit_debit == 0 ? $c->amount : ''); ?></td>
                <td align="right"><?php echo ($c->credit_debit == 1 ? $c->amount : ''); ?></td>
                <td align="right"><?php echo $balance ?> </td>
            </tr>
        <?php

        } ?>
    </tbody>
    <thead>
        <tr>
            <th>#</th>
            <th></th>
            <th></th>
            <th align="right"><?= $cr_total ?></th>
            <th align="right"><?= $dr_total ?></th>
            <th align="right"></th>
        </tr>
    </thead>
</table>
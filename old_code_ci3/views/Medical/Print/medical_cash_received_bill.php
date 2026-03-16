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
            <td style="width: 40%;" align="right">
                <?php
                $bar_content = ':IPD-' . $ipd_id . ':' . $payid . ':' . $ipd_payment[0]->amount . ':PT' . date('dmYhis');
                ?>
                <barcode code="<?= $bar_content ?>" size="0.8" type="QR" error="M" class="barcode" />
            </td>
        </tr>
    </table>
    <h3 align="center">Payment <?= $ipd_payment[0]->Amount_str ?> Receipt No.: <?= $payid ?></h3>
</htmlpageheader>
<htmlpagefooter name="myFooter">
    <table width="100%" style="font-size: 10px;">
        <tr>
            <td width="15%">Page : {PAGENO}/{nbpg}</td>
            <td width="85%" style="text-align: right;">IPD No.:<?= $ipd_master[0]->ipd_code ?> / UHID:<?= $patient_master[0]->p_code ?> /Name : <?= strtoupper($patient_master[0]->p_fname) ?></td>
        </tr>
    </table>
</htmlpagefooter>
<table width="100%" style="font-size: 15px;">
    <tr>
        <td width="50%">Patient Info.<br />
            UHID : <?= $patient_master[0]->p_code ?><br>
            Name : <strong><?= $patient_master[0]->title ?> <?= strtoupper($patient_master[0]->p_fname) ?></strong><br>
            <?= $patient_master[0]->p_relative ?> <?= strtoupper($patient_master[0]->p_rname) ?><br>
            Sex : <b><?= $patient_master[0]->xgender ?> <?= $patient_master[0]->age ?> </b> P.No. :<?= $patient_master[0]->mphone1 ?>
        </td>
        <td width="50%" style="text-align: right;">
            IPD No. : <?= $ipd_master[0]->ipd_code ?><br>
            Date : <strong><?= $ipd_payment[0]->payment_date_str ?></strong>
        </td>
    </tr>
</table>
<hr />
<p style="font-size: 20px;">
    <b>Amount : </b>Rs. <?= $ipd_payment[0]->amount ?><BR />
    <b>Amount in Words : </b>Rs.<?= number_to_word($ipd_payment[0]->amount) ?>
</p>
<p>
    <b>Prepared By :</b><?= $ipd_payment[0]->update_by ?><br />

</p>
<p style="text-align: right;">
    <b>Signature</b>
</p>
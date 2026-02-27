<style>@page {
    margin-top: 4cm;
    margin-bottom: 1.2cm;
    margin-left: 0.5cm;
    margin-right: 0.5cm;
    margin-header:0.5cm;
    margin-footer:0.5cm;
    header: html_myHeader;
    footer: html_myFooter;
}
</style>

<htmlpageheader name="myHeader">
<table cellspacing="0" style="font-size: 10px;width:100%;border-style: inset;" >
<tr>
<td style="width: 20%;vertical-align: top;">
    <img style="width: 100px;vertical-align: top;"  src="assets/images/<?= H_logo ?>" />
</td>
<td style="width: 60%;vertical-align: top;">
    <p align="center" style="font-size: 22px;" ><?= H_Name ?></p>
    <p align="center" style="font-size: 12px" ><?= H_address_1 ?>, <?= H_address_2 ?><br>
    <?php
        if(H_phone_No!='')
        {
            echo 'Phone: '.H_phone_No;
        }
    ?>
 </td>
 <td style="width: 20%;vertical-align: top;text-align: right;">
<?php
  $bar_content=':ORG-'.($req_payment_order[0]->org_id ?? '').':'.($req_payment_order[0]->pay_id ?? '').':'.($req_payment_order[0]->payment_amount ?? '').':PT'.date('dmYhis');
?>

</td>
</tr>
</table>
<h3 align="center">Org Payment Credit Receipt  : <?= esc($req_payment_order[0]->pay_id ?? '') ?></h3>
</htmlpageheader>

<table width="100%" style="font-size: 10px;">
    <tr>
        <td width="50%" >Patient Info.<br/>
            UHID : <?= esc($patient_master[0]->p_code ?? '') ?><br>
            Name : <strong><?= esc($patient_master[0]->title ?? '') ?>  <?= esc(strtoupper((string) ($patient_master[0]->p_fname ?? ''))) ?></strong><br>
            <?= esc($patient_master[0]->p_relative ?? '') ?> <?= esc(strtoupper((string) ($patient_master[0]->p_rname ?? ''))) ?><br>
            Sex : <b><?= esc($patient_master[0]->xgender ?? '') ?> <?= esc($patient_master[0]->age ?? '') ?>  </b> P.No. :<?= esc($patient_master[0]->mphone1 ?? '') ?>
        </td>
        <td width="50%" style="text-align: right;">
              Org. Case No.  : <?= esc($req_payment_order[0]->org_code ?? '') ?><br>
                Payment Date : <strong><?= esc($req_payment_order[0]->payment_date_str ?? '') ?></strong>
        </td>
    </tr>
</table>
<hr/>
<p>
    <b>Org Case Payment Amount : </b>Rs. <?= esc($req_payment_order[0]->payment_amount ?? '') ?><br/>
    <b>Amount in Words : </b>Rs.<?= number_to_word($req_payment_order[0]->payment_amount ?? 0) ?>
</p>
<p>
    <b>Prepared By :</b><?= esc($req_payment_order[0]->payment_accept_by ?? '') ?>
</p>
<p style="text-align: right;">
    <b>Signature</b>
</p>

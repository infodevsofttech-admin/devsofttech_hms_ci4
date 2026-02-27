<style>@page {

sheet-size: A4-L;
margin-top: 4.5cm;
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
<table  cellspacing="5" style="width: 100%;vertical-align: top;padding: 15px;">
    <tr>
        <td style="width: 50%;vertical-align: top;border-right: 1px solid #ddd;">
            <!--- Head Start  -->
            <table cellspacing="0"  style="font-size: 10px;width:100%;border-style: inset;" >
                <tr>
                    
                    <td style="width: 20%;vertical-align: top;">
                        <img style="width: 70px;vertical-align: top;"  src="assets/images/<?=H_logo?>" />
                    </td>
                    <td style="width: 80%;vertical-align: top;">
                        <p align="center" style="font-size: 18px;" ><?=H_Name?></p>
                        <p align="center" style="font-size: 12px" ><?=H_address_1?>, <?=H_address_2?><br>
                        <?php 
                            if(H_phone_No!='')
                            {
                                echo 'Phone: '.H_phone_No;
                            } 
                        ?>
                    </td>
                    <td style="width: 20%;vertical-align: top;text-align: right;">
                    <?php
                    $bar_content=$patient_master[0]->p_code.':'.$invoice_master[0]->invoice_code .':P-'.date('Y-m-d H:i:s').':C-'.$invoice_master[0]->confirm_invoice.':TAmt-'.$invoice_master[0]->net_amount;
                    ?>
                    <barcode code="<?=$bar_content?>" size="0.8" type="QR" error="M" class="barcode" />
                    </td>
                </tr>
            </table>
            <h3 align="center">Invoice No. :<?=$invoice_master[0]->invoice_code?></h3>
            <!-- End Here ---->
        </td>
        <td style="width: 50%;vertical-align: top;border-right: 1px solid #ddd;">
            <!--- Head Start  -->
            <table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: inset;" >
                <tr>
                      
                    <td style="width: 20%;vertical-align: top;">
                      <img style="width: 70px;vertical-align: top;"  src="assets/images/<?=H_logo?>" /> 
                    </td>
                    <td style="width: 80%;vertical-align: top;">
                        <p align="center" style="font-size: 18px;" ><?=H_Name?></p>
                        <p align="center" style="font-size: 12px" ><?=H_address_1?>, <?=H_address_2?><br>
                        <?php 
                            if(H_phone_No!='')
                            {
                                echo 'Phone: '.H_phone_No;
                            } 
                        ?>
                    </td>
                    <td style="width: 20%;vertical-align: top;text-align: right;">
                    <?php
                    $bar_content=$patient_master[0]->p_code.':'.$invoice_master[0]->invoice_code .':P-'.date('Y-m-d H:i:s').':C-'.$invoice_master[0]->confirm_invoice.':TAmt-'.$invoice_master[0]->net_amount;
                    ?>
                    <barcode code="<?=$bar_content?>" size="0.8" type="QR" error="M" class="barcode" />
                    </td>
                </tr>
            </table>
            <h3 align="center">Invoice No. :<?=$invoice_master[0]->invoice_code?> [LAB COPY]</h3> 
            <!-- End Here ---->
        </td>
    </tr>
</table>
</htmlpageheader>
<htmlpagefooter name="myFooter">
    <table cellspacing="5"  style="width: 100%;vertical-align: top;padding: 15px;">
        <tr>
            <td style="width: 50%;vertical-align: top;border-right: 1px solid #ddd;">
                <table width="100%" style="font-size: 10px;">
                    <tr>
                        <td width="15%" >Page : {PAGENO}/{nbpg}</td>
                        <td width="85%" style="text-align: right;">Invoice No.:<?=$invoice_master[0]->invoice_code?> / UHID:<?=$patient_master[0]->p_code?> /Name : <?=strtoupper($patient_master[0]->p_fname)?></td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%;vertical-align: top;">
                <table width="100%" style="font-size: 10px;">
                    <tr>
                        <td width="15%" >Page : {PAGENO}/{nbpg}</td>
                        <td width="85%" style="text-align: right;">Invoice No.:<?=$invoice_master[0]->invoice_code?> / UHID:<?=$patient_master[0]->p_code?> /Name : <?=strtoupper($patient_master[0]->p_fname)?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</htmlpagefooter>
<?php
$org_case_info="";
    if(count($case_master)>0)
    {
        $org_case_info="<br/><b>Org. Name :</b>".$case_master[0]->short_name;
        $org_case_info.="<br/><b>Org. ID :</b>".$case_master[0]->case_id_code;
    }
?>
<table  style="width: 100%;vertical-align: top;padding: 1px;font-size: 11px;">
    <tr>
        <td style="width: 50%;vertical-align: top;border-right: 1px solid #ddd;" >
            <!---Left Copy Start  -->
            <table style="width: 100%;vertical-align: top;padding: 1px;">
                <tr>
                    <td width="50%" >Bill To <br/> 
                        UHID : <?=$patient_master[0]->p_code?><br>
                        Name : <strong><?=$patient_master[0]->title?>  <?=strtoupper($patient_master[0]->p_fname)?></strong><br>
                        <?=$patient_master[0]->p_relative?> <?=strtoupper($patient_master[0]->p_rname)?><br>
                        Sex : <b><?=$patient_master[0]->xgender?> <?=$patient_master[0]->age?>  </b> P.No. :<?=$patient_master[0]->mphone1?>
                    </td>
                    <td width="50%" style="text-align: right;">
                        <?=$org_case_info?><br>
                        Address : <?=$patient_master[0]->add1.','.$patient_master[0]->city?><br>
                        Date : <strong><?=MysqlDate_to_str($invoice_master[0]->inv_date)?></strong><br>
                        <b>Refer By : </b> Dr.<?=$invoice_master[0]->refer_by_other?>
                    </td>
                </tr>
            </table>
            <hr/>
            <table  style="width:100%;vertical-align: top;padding: 1px;" >
                <tr>
                    <th style="width:10px">#</th>
                    <th>Charges Group</th>
                    <th  align="center">Charge Name</th>
                    <th  align="center">Org.Code</th>
                    <th  align="right">Rate</th>
                    <th  align="right">Qty</th>
                    <th  align="right">Amt.</th>
                </tr>
                <?php
                    $srno=1;
                    $content='';
                    foreach($invoiceDetails as $row)
                    { 
                    $content.= '<tr>';
                    $content.= '    <td>'.$srno.'</td>';
                    $content.= '    <td>'.$row->group_desc.'</td>';
                    $content.= '    <td>'.$row->item_name.'</td>';
                    $content.= '    <td>'.$row->org_code.'</td>';
                    $content.= '    <td align="right">'.$row->item_rate.'</td>';
                    $content.= '    <td align="right">'.$row->item_qty.'</td>';
                    $content.= '    <td align="right"><i class="fa fa-inr"></i>'.$row->item_amount.'</td>';
                    $srno=$srno+1;
                    $content.= '</tr>';
                   
                    }
                    echo $content;
                ?>
            
                <tr>
                    <td style="width:10px">#</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Gross Total</td>
                    <td align="right"><?=$invoice_master[0]->total_amount?></td>
                </tr>
                <?php
                    $content='';
                    if($invoice_master[0]->discount_amount>0) {
                    $content.='<tr>
                        <td style="width: 10px">#</td>
                        <td>Deduction</td>
                        <td></td>
                        <td>'.$invoice_master[0]->discount_desc.'</td>
                        <td></td>
                        <td></td>
                        <td align="right"><i class="fa fa-inr"></i>-'.$invoice_master[0]->discount_amount.'</td>
                        
                    </tr>';
                    }
                    echo $content;
                ?>
                <tr>
                    <th style="width: 10px">#</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th>Net Amount</th>
                    <th align="right"><?=$invoice_master[0]->net_amount?></th>
                </tr>
            </table>
            <hr/>
            <table style="width:500px;">
                <tr>
                    <td>
                    <?php
                        if($invoice_master[0]->ipd_id<1) {
                            $content='<p style="font-size: 10px;width:100%;border-style: inset;">
                                            Amount received : '.$invoice_master[0]->payment_part_received.'
                                                / Balance Amount : '.$invoice_master[0]->payment_part_balance.'
                                                / Net Amount : '.$invoice_master[0]->net_amount.
                                        '</p>';
                            }
                        echo $content;
                    ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>Amount in Words : </b>Rs. <?=number_to_word($invoice_master[0]->net_amount)?><br/>
                        <b>Prepared By :</b><?=$invoice_master[0]->prepared_by?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h1><?=$invoice_master[0]->Invoice_status_str?></h1>
                        <?php if($invoice_master[0]->payment_mode>2) { ?>
                            <b>Payment Details <i>[<?=$invoice_master[0]->Payment_type_str ?>]</i>
                        <?php }else{ ?>   
                            <b>Payment Details <i>[Payment No.:Mode of Payment:Amount]</i>: </b>
                            <?php
                            foreach($payment_history as $row)
                            { 
                                echo '['.$row->id.':'.$row->Payment_type_str.':'.$row->amount.']/';
                            }
                            ?>
                        <?php }  ?>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;">
                            <br/><br/>
                        <b>Signature</b><br/>
                        Date : <?=$invoice_master[0]->confirm_invoice_datetime?>
                    </td>
                </tr>
            </table>
            <!---Left End ----->

        </td>
        <td style="width: 50%;vertical-align: top;" >
            <!---Left Copy Start  -->
            <table style="width:100%;vertical-align: top;padding: 1px;">
                <tr>
                    <td width="50%" >Bill To <br/> 
                        UHID : <?=$patient_master[0]->p_code?><br>
                        Name : <strong><?=$patient_master[0]->title?>  <?=strtoupper($patient_master[0]->p_fname)?></strong><br>
                        <?=$patient_master[0]->p_relative?> <?=strtoupper($patient_master[0]->p_rname)?><br>
                        Sex : <b><?=$patient_master[0]->xgender?> <?=$patient_master[0]->age?>  </b> P.No. :<?=$patient_master[0]->mphone1?>
                    </td>
                    <td width="50%" style="text-align: right;">
                        <?=$org_case_info?><br>
                        Address : <?=$patient_master[0]->add1.','.$patient_master[0]->city?><br>
                        Date : <strong><?=MysqlDate_to_str($invoice_master[0]->inv_date)?></strong><br>
                        <b>Refer By : </b> Dr.<?=$invoice_master[0]->refer_by_other?>
                    </td>
                </tr>
            </table>
            <hr/>
            <table   style="width:100%;vertical-align: top;padding: 1px;" >
                <tr>
                    <th style="width:10px">#</th>
                    <th>Charges Group</th>
                    <th  align="center">Charge Name</th>
                    <th  align="center">Org.Code</th>
                    <th  align="right">Rate</th>
                    <th  align="right">Qty</th>
                    <th  align="right">Amt.</th>
                </tr>
                <?php
                    $srno=1;
                    $content='';
                    foreach($invoiceDetails as $row)
                    { 
                    $content.= '<tr>';
                    $content.= '    <td>'.$srno.'</td>';
                    $content.= '    <td>'.$row->group_desc.'</td>';
                    $content.= '    <td>'.$row->item_name.'</td>';
                    $content.= '    <td>'.$row->org_code.'</td>';
                    $content.= '    <td align="right">'.$row->item_rate.'</td>';
                    $content.= '    <td align="right">'.$row->item_qty.'</td>';
                    $content.= '    <td align="right"><i class="fa fa-inr"></i>'.$row->item_amount.'</td>';
                    $srno=$srno+1;
                    $content.= '</tr>';

                    
                    }
                    echo $content;
                ?>
            
                <tr>
                    <td style="width: 10px">#</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>Gross Total</td>
                    <td align="right"><?=$invoice_master[0]->total_amount?></td>
                </tr>
                <?php
                    $content='';
                    if($invoice_master[0]->discount_amount>0) {
                    $content.='<tr>
                        <td style="width: 10px">#</td>
                        <td>Deduction</td>
                        <td>'.$invoice_master[0]->discount_desc.'</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td align="right"><i class="fa fa-inr"></i>-'.$invoice_master[0]->discount_amount.'</td>
                        
                    </tr>';
                    }
                    echo $content;
                ?>
                <tr>
                    <th style="width: 10px">#</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th>Net Amount</th>
                    <th align="right"><?=$invoice_master[0]->net_amount?></th>
                </tr>
            </table>
            <hr/>
            <table style="width:500px;">
                <tr>
                    <td>
                    <?php
                        if($invoice_master[0]->ipd_id<1) {
                            $content='<p style="font-size: 10px;width:100%;border-style: inset;">
                                            Amount received : '.$invoice_master[0]->payment_part_received.'
                                                / Balance Amount : '.$invoice_master[0]->payment_part_balance.'
                                                / Net Amount : '.$invoice_master[0]->net_amount.
                                        '</p>';
                            }
                        echo $content;
                    ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <h1><?=$invoice_master[0]->Invoice_status_str?></h1>
                        <?php if($invoice_master[0]->payment_mode>2) { ?>
                            <b>Payment Details <i>[<?=$invoice_master[0]->Payment_type_str ?>]</i>
                        <?php }else{ ?>   
                            <b>Payment Details <i>[Payment No.:Mode of Payment:Amount]</i>: </b>
                            <?php
                            foreach($payment_history as $row)
                            { 
                                echo '['.$row->id.':'.$row->Payment_type_str.':'.$row->amount.']/';
                            }
                            ?>
                        <?php }  ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>Amount in Words : </b>Rs. <?=number_to_word($invoice_master[0]->net_amount)?><br/>
                        <b>Prepared By :</b><?=$invoice_master[0]->prepared_by?>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right;">
                        <b>Signature</b><br/>
                        Date : <?=$invoice_master[0]->confirm_invoice_datetime?>
                    </td>
                </tr>
            </table>
            <!---Left End ----->
        </td>
    </tr>
</table>


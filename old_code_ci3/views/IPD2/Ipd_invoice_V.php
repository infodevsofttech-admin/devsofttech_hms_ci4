<section class="content-header">
  <h1>
	IPD Invoice
	<small>IPD ID: <?=$ipdmaster[0]->ipd_code?></small>
  </h1>
</section>
<section class="content">
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="box box-danger">
    <div class="box-header">
		<div class="box-title">
			<p>
			<strong>Name :</strong><?=$person_info[0]->p_fname?>  
			<strong>/ Age :</strong><?=$person_info[0]->age?> 
			<strong>/ Gender :</strong><?=$person_info[0]->xgender?> 
			<strong>/ P Code :</strong><?=$person_info[0]->p_code?>
			<?php if($ipdmaster[0]->case_id>0) { ?>
			<strong>/ Ins. Comp. :</strong><?=$insurance[0]->ins_company_name?>
			<?php } ?>
			</p>
			<input type="hidden" id="pid" name="pid" value="<?=$person_info[0]->id?>" />
			<?php if($ipdmaster[0]->case_id>0) { ?>
			<input type="hidden" id="caseid" name="caseid" value="<?=$orgcase[0]->id?>" />
			<?php } ?>
        </div>
    </div>
	<div class="box-body">
		<table class="table table-striped table-condensed">
			<tr>
				<th style="width: 10px">#</th>
				<th>Description</th>
				<th style="width:100px;">Org.Code</th>
				<th style="width:100px;">Unit</th>
				<th style="width:100px;">Rate</th>
				<th style="width:100px;">Amount</th>
			</tr>
			<?php
			$srno=1;
			$headdesc='';
			$headTotal=0.00;
			
			if(Count($ipd_package)>0){
				echo '<tr>';
				echo '<td colspan="2"><b>Package</b></td>';
				echo '<td colspan="4"></td></tr>';
				$headdesc='Package';
				$headTotal=0.00;

				for($i=0;$i<Count($ipd_package);$i++)
				{ 
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$ipd_package[$i]->package_name.'</td>';
					echo '<td>'.$ipd_package[$i]->org_code.'</td>';
					echo '<td></td>';
					echo '<td align="right"></td>';
					echo '<td align="right">'.$ipd_package[$i]->package_Amount.'</td>';
					$srno=$srno+1;
					$headTotal += $ipd_package[$i]->package_Amount;
					echo '</tr>';
				}

				echo '<tr>';
				echo '<td></td>';
				echo '<td></td>';
				echo '<td></td>';
				echo '<td></td>';
				echo '<td align="right">Sub Total</td>';
				echo '<td align="right">'.number_format($headTotal,2).'</td>';
				
			}
			

			for($i=0;$i<Count($ipd_invoice_item);$i++)
				{ 
					if($headdesc!=$ipd_invoice_item[$i]->group_desc)
					{
						echo '<tr>';
						echo '<td colspan="2"><b>'.$ipd_invoice_item[$i]->group_desc.'</b></td>';
						echo '<td colspan="4"></td></tr>';
						$headdesc=$ipd_invoice_item[$i]->group_desc;
						$headTotal=0.00;
					}
					
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$ipd_invoice_item[$i]->item_name.' '.$ipd_invoice_item[$i]->comment.'</td>';
					echo '<td>'.$ipd_invoice_item[$i]->org_code.'</td>';
					echo '<td>'.$ipd_invoice_item[$i]->item_qty.'</td>';
					echo '<td align="right">'.$ipd_invoice_item[$i]->item_rate.'</td>';
					echo '<td align="right">'.$ipd_invoice_item[$i]->item_amount.'</td>';
					$srno=$srno+1;
					$headTotal += $ipd_invoice_item[$i]->item_amount;
					echo '</tr>';

					if($headdesc!=@$ipd_invoice_item[$i+1]->group_desc)
					{
						echo '<tr>';
						echo '<td></td>';
						echo '<td></td>';
						echo '<td></td>';
						echo '<td></td>';
						echo '<td align="right">Sub Total</td>';
						echo '<td align="right">'.number_format($headTotal,2).'</td>';
						echo '</tr>';
					}
				}


			for($i=0;$i<Count($showinvoice);$i++)
				{ 
					if($headdesc!=$showinvoice[$i]->Charge_type)
					{
						echo '<tr>';
						echo '<td colspan="2"><b>'.$showinvoice[$i]->Charge_type.'</b></td>';
						echo '<td colspan="4"></td></tr>';
						$headdesc=$showinvoice[$i]->Charge_type;
						$headTotal=0.00;
					}
					
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$showinvoice[$i]->idesc.'</td>';
					echo '<td>'.$showinvoice[$i]->orgcode.'</td>';
					echo '<td>'.$showinvoice[$i]->no_qty.'</td>';
					echo '<td align="right">'.$showinvoice[$i]->item_rate.'</td>';
					echo '<td align="right">'.$showinvoice[$i]->amount.'</td>';
					$srno=$srno+1;
					$headTotal += $showinvoice[$i]->amount;
					echo '</tr>';

					if($headdesc!=@$showinvoice[$i+1]->Charge_type)
					{
						echo '<tr>';
						echo '<td></td>';
						echo '<td></td>';
						echo '<td></td>';
						echo '<td></td>';
						echo '<td align="right">Sub Total</td>';
						echo '<td align="right">'.number_format($headTotal,2).'</td>';
						echo '</tr>';
					}
				}
			
			if(count($inv_med_list)>0)
			{
				echo '<tr>';
				echo '<td colspan="2">Medicine</b></td>';
				echo '<td colspan="3"></td></tr>';
				
				$med_total=0.00;
				
				foreach($inv_med_list as $row)
				{
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->inv_med_code.'</td>';
					echo '<td></td>';
					echo '<td></td>';
					echo '<td align="right"></td>';
					echo '<td align="right">'.$row->net_amount.'</td>';
					$srno=$srno+1;
					$med_total +=$row->net_amount;
					echo '</tr>';
				}
				
				echo '<tr>';
					echo '<td></td>';
					echo '<td></td>';
					echo '<td></td>';
					echo '<td></td>';
					echo '<td align="right">Med. Total </td>';
					echo '<td align="right">'.number_format($med_total,2).'</td>';
					echo '</tr>';
			}
			?>
			
			<!---- Total Show  ----->
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Gross Total</th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->gross_amount?></th>
			</tr>
			<?php if($ipdmaster[0]->Discount>0) { ?>
			<tr>
				<th style="width: 10px">#</th>
				<th colspan="2">Remark :</br><?=$ipdmaster[0]->Discount_Remark ?></th>
				<th></th>
				<th>Deduction </th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->Discount ?></th>
				
			</tr>
			<?php }  ?>
			<?php if($ipdmaster[0]->Discount2>0) { ?>
			<tr>
				<th style="width: 10px">#</th>
				<th colspan="2">Remark :</br><?=$ipdmaster[0]->Discount_Remark2 ?></th>
				<th></th>
				<th>Deduction </th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->Discount2 ?></th>
				
			</tr>
			<?php }  ?>
			<?php if($ipdmaster[0]->Discount3>0) { ?>
			<tr>
				<th style="width: 10px">#</th>
				<th colspan="2">Remark :</br><?=$ipdmaster[0]->Discount_Remark3 ?></th>
				<th></th>
				<th>Deduction </th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->Discount3 ?></th>
			</tr>
			<?php }  ?>
			<?php if($ipdmaster[0]->chargeamount1>0) { ?>
			<tr>
				<th style="width: 10px">#</th>
				<th colspan="2">Remark :</br><?=$ipdmaster[0]->charge1 ?></th>
				<th></th>
				<th>Charge </th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->chargeamount1 ?></th>
			</tr>
			<?php }  ?>
			<?php if($ipdmaster[0]->chargeamount2>0) { ?>
			<tr>
				<th style="width: 10px">#</th>
				<th colspan="2">Remark :</br><?=$ipdmaster[0]->charge2 ?></th>
				<th></th>
				<th>Charge </th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->chargeamount2 ?></th>
			</tr>
			<?php }  ?>
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Net Amount</th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->net_amount?></th>
			</tr>
			<tr>
				<th style="width: 10px">#</th>
				<th colspan="2" >
				Payment Recd.<br/>
					<?php
					$i=1;
					foreach($ipd_payment as $row)
					{ 
						$i=$i+1;
						echo '['.$row->id.':'.$row->pay_mode.':'.$row->pay_date_str.':'.$row->amount.'] /';
						if($i%3==0)
						{
							echo '<br/>';
						}
					}
					?>
				</th>
				<th></th>
				<th></th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->total_paid_amount?></th>
			</tr>
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Balance</th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->balance_amount?></th>
			</tr>
			<?php if($ipdmaster[0]->payable_by_tpa>0) { ?>
			<tr>
				<td style="width: 10px">#</td>
				<td colspan="2">Payable By TPA</td>
				<td></th>
				<td></td>
				<td align="right" style="text-align:right"><?=$ipdmaster[0]->payable_by_tpa ?></td>
			</tr>
			<?php }  ?>

            <?php if($ipdmaster[0]->discount_for_tpa>0) { ?>
			<tr>
				<td style="width: 10px">#</td>
				<td colspan="2">Discount For TPA</td>
				<td></td>
				<td></td>
				<td align="right" style="text-align:right"><?=$ipdmaster[0]->discount_for_tpa ?></td>
			</tr>
			<?php }  ?>
            
            <?php if($ipdmaster[0]->discount_by_hospital>0) { ?>
			<tr>
				<td style="width: 10px">#</td>
				<td colspan="2">Discount By Hospital</td>
				<td><?=$ipdmaster[0]->discount_by_hospital_remark?></td>
				<td></td>
				<td align="right" style="text-align:right"><?=$ipdmaster[0]->discount_by_hospital ?></td>
			</tr>
			<?php }  ?>
            
            <?php if($ipdmaster[0]->discount_by_hospital_2>0) { ?>
			<tr>
				<td style="width: 10px">#</td>
				<td colspan="2">Discount By Hospital/ Doctor</td>
				<td><?=$ipdmaster[0]->discount_by_hospital_2_remark?></td>
				<td></td>
				<td align="right" style="text-align:right"><?=$ipdmaster[0]->discount_by_hospital_2 ?></td>
			</tr>
			<?php }  ?>

			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Final Balance</th>
				<th align="right" style="text-align:right"><?=$ipdmaster[0]->balance_discount_after?></th>
			</tr>
		</table>
		</div>
		<div class="box-footer">
			<a href="/ipdNew/ipd_complete_invoice/<?=$ipdmaster[0]->id?>/1" target=_blank >
			<i class="fa fa-dashboard"></i> Print Bill</a>
			<a href="/ipdNew/ipd_complete_invoice/<?=$ipdmaster[0]->id?>/5" target=_blank ><i class="fa fa-dashboard"></i>Print on Letter Head w/o Payment</a>
			<a href="/ipdNew/ipd_complete_invoice/<?=$ipdmaster[0]->id?>/6" target=_blank ><i class="fa fa-dashboard"></i>Print on Letter Head </a>
			<?php if ($this->ion_auth->in_group('admin')) { ?>
				<a href="/ipdNew/ipd_complete_invoice/<?=$ipdmaster[0]->id?>/2" target=_blank >
				<i class="fa fa-dashboard"></i> Print WithOut Payment Details Bill</a>
				<a href="/ipdNew/ipd_complete_invoice/<?=$ipdmaster[0]->id?>/3" target=_blank ><i class="fa fa-dashboard"></i>Item Amt. With Discount</a>
				<a href="/ipdNew/ipd_complete_invoice/<?=$ipdmaster[0]->id?>/4" target=_blank ><i class="fa fa-dashboard"></i>TPA Final Bill</a>
				
			<?php }  ?>
			
		</div>
</div>


<?php echo form_close(); ?>
</section>


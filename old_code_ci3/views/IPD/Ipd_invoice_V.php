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
		<table class="table table-striped ">
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
			for($i=0;$i<Count($showinvoice);$i++)
				{ 
					if($headdesc!=$showinvoice[$i]->Charge_type)
					{
						echo '<tr>';
						echo '<td></td>';
						echo '<td><b>'.$showinvoice[$i]->Charge_type.'</b></td>';
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
					}
				}
			
			if(count($inv_med_list)>0)
			{
				echo '<tr>';
				echo '<td></td>';
				echo '<td><b>Medicine</b></td>';
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
				<th align="right" style="text-align:right"><?=$inv_total['total_charges']?></th>
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
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Net Amount</th>
				<th align="right" style="text-align:right"><?=$inv_total['net_amount']?></th>
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
				<th align="right" style="text-align:right"><?=$ipd_payment_total[0]->t_ipd_pay?></th>
			</tr>
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Balance</th>
				<th align="right" style="text-align:right"><?=$inv_total['balance']?></th>
				
			</tr>
		</table>
		</div>
		<div class="box-footer">
			<a href="/ipd/ipd_complete_invoice/<?=$ipdmaster[0]->id?>/1" target=_blank >
			<i class="fa fa-dashboard"></i> Print Bill</a>
			<?php if ($this->ion_auth->in_group('admin')) { ?>
				<a href="/ipd/ipd_complete_invoice/<?=$ipdmaster[0]->id?>/2" target=_blank >
				<i class="fa fa-dashboard"></i> Print WithOut Payment Details Bill</a>
				<a href="/ipd2/ipd_complete_invoice/<?=$ipdmaster[0]->id?>/1" target=_blank >TEXT INVOICE</a>
			
			<?php }  ?>
			
		</div>
</div>


<?php echo form_close(); ?>
</section>


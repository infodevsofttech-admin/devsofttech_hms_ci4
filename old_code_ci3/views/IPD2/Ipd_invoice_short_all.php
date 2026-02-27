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
				<th>Date</th>
				<th>Charges Type</th>
				<th>Description</th>
				<th>Org. Code</th>
				<th>Sub Invoice Code</th>
				<th>Amount</th>
				<th></th>
			</tr>
			<?php
			$srno=0;
			foreach($showinvoice as $row)
				{ 
					$srno=$srno+1;
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->str_date.'</td>';
					echo '<td>'.$row->Charge_type.'</td>';
					echo '<td>'.$row->Description.'</td>';
					echo '<td>'.$row->orgcode.'</td>';
					echo '<td>'.$row->Code.'</td>';
					echo '<td align="right">'.$row->Amount.'</td>';
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
				<th align="right" style="text-align:right"><?=$invoiceGtotal[0]->GTotal?></th>
				<th></th>
			</tr>
		</table>
		</div>
	<div class="box-footer">
			<a href="/ipd/ipd_complete_invoice/<?=$ipdmaster[0]->id?>/1" target=_blank ><i class="fa fa-dashboard"></i> Invoice Print</a>
		</div>
</div>


<?php echo form_close(); ?>
</section>


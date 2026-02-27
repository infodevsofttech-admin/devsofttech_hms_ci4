<section class="invoice">
 <!-- info row -->
      <div class="row invoice-info">
        <!-- /.col -->
        <div class="col-sm-4 invoice-col">
          To
          <address>
            <strong><?=$opd_master[0]->P_name ?></strong><br>
            OPD Book Date (y-m-d time) : <?=$opd_master[0]->opd_book_date ?><br>
            Gender : <?=($patient_master[0]->gender==1)?'Male':'Female' ?><br>
            Age : <?=$patient_master[0]->age ?><br>
            Phone No : <?=$patient_master[0]->mphone1 ?>
          </address>
        </div>
        <!-- /.col -->
         <div class="col-sm-4 invoice-col">
          <b>OPD ID:</b> <?=$opd_master[0]->opd_code ?><br>
          <b>Patient ID :</b> <?=$patient_master[0]->p_code ?>
		  <br>
		  <?php
				if($opd_master[0]->insurance_id>1)
				{
					echo '<strong> Ins. Comp. :</strong>'.$insurance[0]->ins_company_name.'<br>';
				}
			?>
		  <input type="hidden" id="oid" name="oid" value="<?=$opd_master[0]->opd_id ?>" />
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
      <!-- Table row -->
      <div class="row">
        <div class="col-xs-12 table-responsive">
          <table class="table table-striped">
            <thead>
            <tr>
              <th>Date</th>
              <th>Doctor</th>
              <th>Department</th>
			  <th></th>
              <th>OPD Fee</th>

            </tr>
            </thead>
            <tbody>
            <tr>
              <td><?=MysqlDate_to_str($opd_master[0]->apointment_date) ?></td>
              <td>Dr. <?=$opd_master[0]->doc_name ?></td>
              <td><?=$opd_master[0]->doc_spec ?></td>
			  <td><?=$opd_master[0]->opd_fee_desc ?></td>
              <td><?=$opd_master[0]->opd_fee_gross_amount ?></td>
           </tr>
			<?php 
			if($opd_master[0]->opd_discount>0 )
			{
			?>
			<tr>
				<td>Deduction</td>
				<td colspan=2><?=$opd_master[0]->opd_disc_remark ?></td>
				<td><?=$opd_master[0]->opd_discount ?></td>
				<td></td>
			</tr>
			<?php } ?>
			<tr>
				<th style="width: 10px">#</th>
				<th colspan=2>Net Amount</th>
				<th style=""><?=$opd_master[0]->opd_fee_amount?></th>
				<th></th>
			</tr>
			<?php if($opd_master[0]->payment_status==1) {  
			if($opd_master[0]->payment_mode==1 || $opd_master[0]->payment_mode==2)
			{
				if($opd_master[0]->opd_correction_amount==0)
				{
			?>
				<tr>
					<td><b>Correction</b></td>
					<td colspan=2><input  class="form-control varchar" name="input_corr_desc" id="input_corr_desc" placeholder="Correction  Desc." value="<?=$opd_master[0]->opd_correction_remark ?>" type="text" /> </td>
					<td><div class="form-group">
						<div class="radio">
							<label>
							<input name="optionsRadios_crdr" id="options_debit" value="1" <?=radio_checked("1",$opd_master[0]->opd_correction_crdr)?> type="radio">
								Return
							</label>
							<label>
							  <input name="optionsRadios_crdr" id="options_credit" <?=radio_checked("2",$opd_master[0]->opd_correction_crdr)?> value="0"  type="radio">
								Add
							</label>
							</div>
						</div>
					</td>
					<td><input style="width: 100px" class="form-control number" name="input_corr_amt" id="input_corr_amt" placeholder="Amount" value="<?=$opd_master[0]->opd_correction_amount ?>" type="text"  /></td>
					<td><button type="button" class="btn btn-primary" id="btn_update_ded">Update</button></td>
				</tr>
				
				
			<?php }else{   ?>
				<tr>
					<td><b>Correction</b></td>
					<td colspan=2><?=$opd_master[0]->opd_correction_remark ?></td>
					<td><?php if($opd_master[0]->opd_correction_crdr==1) { echo 'Return';} else {'Add';}  ?>
					</td>
					<td>Rs. <?=$opd_master[0]->opd_correction_amount ?></td>
					<td></td>
				</tr>
			<?php 		
					} 
				}
			 }?>
			</tbody>
          </table>
		  <?php if($opd_master[0]->payment_status==0) { ?>
			<button type="button" class="btn btn-primary" id="btn_delete_ded">Delete</button>
		  <?php } ?>
        </div>
        <!-- /.col -->
      </div>
      <!-- /.row -->
	<div class="row">
        <div class="col-xs-6">
          <a href="<?php echo '/Opd/invoice_print/'.$opd_master[0]->opd_id;  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
        </div>
		<div class="col-xs-6">
          Payment Method by : <?=$opd_master[0]->payment_mode_desc ?>           
        </div>
     </div>
</section>
<script>
	$(document).ready(function(){

		function enable_btn()
		{
			$('#btn_update_ded').attr('disabled', false);
		}

		$('#btn_update_ded').click( function()
		{	
			$('#btn_update_ded').attr('disabled', true);
			
			if(confirm("Are you sure process this correction "))
			{
			$.post('/index.php/Invoice/update_correction',{ "oid": $('#oid').val(), 
				"input_corr_desc": $('#input_corr_desc').val(), 
				"input_corr_amt": $('#input_corr_amt').val(),
				"optionsRadios_crdr":$('input:radio[name=optionsRadios_crdr]:checked').val()
				 }, function(data){
				load_form_div('/Invoice/opdinvoice/'+$('#oid').val(),'searchresult');
				});
			}else{
				setTimeout(enable_btn,5000);
			}
		});
		
	});
</script>
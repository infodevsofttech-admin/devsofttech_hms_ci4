<section class="content-header">
  <h1>
	Medical Invoice No. <?=$invoiceMaster[0]->inv_med_code?>
	<small>
	<a href="javascript:load_form_div('/Medical/edit_invoice_edit/<?=$invoiceMaster[0]->id ?>','maindiv');" >Edit</a>
	</small>
  </h1>
</section>
<section class="content">
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="col-md-8" style="padding: 5px;">
	<div class="box box-danger">
		<div class="box-header">
			<div class="row">
					<input type="hidden" id="pid" name="pid" value="<?=$invoiceMaster[0]->patient_id ?>" />
					<input type="hidden" id="med_invoice_id" name="med_invoice_id" value="<?=$invoiceMaster[0]->id ?>" />
					<?php if($invoiceMaster[0]->patient_id==0) { ?>
						<table class="table">
							<tr>
								<td>Patient Name : <input  id="P_Name" name="P_Name" value="<?=$invoiceMaster[0]->inv_name ?>" required ></td>
								<td> </td>
								<td>Doctor Name : 
								<select  id="doc_name_id" name="doc_name_id"  >	
									<option value='0' <?=combo_checked('0',$invoiceMaster[0]->doc_id)?>  >From Other Hospital</option>
									<?php 
									foreach($doclist as $row)
									{ 
										echo '<option value='.$row->id.'  '.combo_checked($row->id,$invoiceMaster[0]->doc_id).'  >'.$row->p_fname.'</option>';
									}
									?>
								</select>
								</td>
								<td> </td>
								<td>Other Doctor:
								<input class="varchar" name="input_doc_name" id="input_doc_name" placeholder="Doctor Name" value="<?=$invoiceMaster[0]->doc_name?>" type="text"  />
								</td>
								<td> </td>
								<td><button type="button" class="btn btn-primary btn-sm" id="btn_update" onclick="update_name_phone()">Update Name </button></td>
							</tr>
						</table>
					<?php }else{ ?>
					<div class="col-md-12">
						<p><strong>Name :</strong>
						<?=$invoiceMaster[0]->inv_name?>
						<strong>/ P Code :</strong><?=$invoiceMaster[0]->patient_code?> 
						<strong>/ Invoice No. :</strong><?=$invoiceMaster[0]->inv_med_code?>
						<strong>/ Date :</strong> <?=MysqlDate_to_str($invoiceMaster[0]->inv_date)?>
						<?php if($invoiceMaster[0]->ipd_id > 0) { ?>
							<strong>IPD Code :</strong>
							<a href="javascript:load_form_div('/Medical/list_med_inv/<?=$ipd_master[0]->id ?>','maindiv');" >
								<?=$ipd_master[0]->ipd_code?>
							</a>
							<strong>Admit Date : </strong><?=$ipd_list[0]->str_register_date ?> <?=$ipd_list[0]->reg_time ?>
							<strong>/ Doctor :</strong><?=$ipd_list[0]->doc_name?>
							<strong>/ TPA-Org. :</strong><?=$ipd_list[0]->admit_type?> 
							<strong>/ Bill Type :</strong><?=($invoiceMaster[0]->ipd_credit)?'Credit To Hospital':'CASH/Direct'?>
						<?php }elseif($invoiceMaster[0]->case_id > 0){  ?>
								<strong>/ Org. Case ID :<a href="javascript:load_form_div('/Medical/list_med_orginv/<?=$OCaseMaster[0]->id ?>/<?=$invoiceMaster[0]->store_id?>','maindiv');" > 
											<?=$OCaseMaster[0]->case_id_code ?>
										</a>
						<?php }else{  ?>
							<table class="table">
							<tr>
								<td>Doctor Name : 
								<select  id="doc_name_id" name="doc_name_id"  >	
									<option value='0' <?=combo_checked('0',$invoiceMaster[0]->doc_id)?>  >From Other Hospital</option>
									<?php 
									foreach($doclist as $row)
									{ 
										echo '<option value='.$row->id.'  '.combo_checked($row->id,$invoiceMaster[0]->doc_id).'  >'.$row->p_fname.'</option>';
									}
									?>
								</select>
								</td>
								<td> </td>
								<td>Other Doctor:
								<input class="varchar" name="input_doc_name" id="input_doc_name" placeholder="Doctor Name" value="<?=$invoiceMaster[0]->doc_name?>" type="text"  />
								</td>
								<td> </td>
								<td><button type="button" class="btn btn-primary btn-sm" id="btn_update" onclick="update_doctor()">Update Name </button></td>
							</tr>
						</table>


						<?php } ?>
						</p>
					</div>
				<?php } ?>
			</div>
		</div>
		<div class="box-body">
			<div class="col-md-12" >
				<div class="row " >
					<div id="show_item_list">
						<?=$content?>
					</div>
				</div>
            </div>
		</div>
		
	</div>
</div>
<?php echo form_close(); ?>
</section>


<section class="content-header">
  <h1>
	Medical Invoice
	<small></small>
  </h1>
</section>
<section class="content">
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="box box-danger">
    <div class="box-header">
		<div class="row">
			<input type="hidden" id="pid" name="pid" value="<?=$invoiceMaster[0]->patient_id ?>" />
			<input type="hidden" id="med_invoice_id" name="med_invoice_id" value="<?=$invoiceMaster[0]->id ?>" />
			<div class="col-md-2">
				<div class="form-group">
					<label>Invoice Code</label>
					<input class="form-control" name="input_inv_code" id="input_inv_code" placeholder="Inv. Code" type="text" value="<?=$invoiceMaster[0]->inv_med_code ?>" readonly=true />
				</div>
			</div>
			<div class="col-md-2">
				<div class="form-group">
					<label>Patient Code</label>
					<input class="form-control" name="input_patient_code" id="input_patient_code" placeholder="Patient Code" type="text" value="<?=$invoiceMaster[0]->patient_code ?>" readonly=true />
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					<label>Customer Name</label>
					<input class="form-control" name="input_custmer_Name" id="input_custmer_Name" placeholder="Customer Name" type="text" value="<?=$invoiceMaster[0]->inv_name ?>" />
				</div>
			</div>
			<div class="col-xs-2">
				<div class="form-group">
				<label>Doctor Name</label>
					<select class="form-control" id="doc_name_id" name="doc_name_id"  >	
						<option value='0' <?=combo_checked('0',$invoiceMaster[0]->doc_id)?>  >From Other Hospital</option>
						<?php 
						foreach($doclist as $row)
						{ 
							echo '<option value='.$row->id.'  '.combo_checked($row->id,$invoiceMaster[0]->doc_id).'  >'.$row->p_fname.'</option>';
						}
						?>
					</select>
				</div>
			</div>
			<div class="col-xs-3">
				<div class="form-group">
					<label>Other Doctor</label>
					<input class="form-control varchar" name="input_doc_name" id="input_doc_name" placeholder="Doctor Name" value="<?=$invoiceMaster[0]->doc_name?>" type="text"  />
				</div>
			</div>
		</div>
		<div class="row">
			<?php if($this->ion_auth->in_group('InvoiceDateChanged') || $this->ion_auth->in_group('admin')) { ?>
			<div class="col-md-3">
                <div class="form-group">
                    <label>Date <?=MysqlDate_to_str($invoiceMaster[0]->inv_date)?></label>
                    <div class="input-group date">
                        <div class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </div>
                        <input class="form-control pull-right datepicker" id="datepicker_invoicedate" name="datepicker_invoicedate" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask="" value="<?=MysqlDate_to_str($invoiceMaster[0]->inv_date)?>"  />
                    </div>
                </div>
            </div>
			<?php } ?>
			<?php if($invoiceMaster[0]->ipd_id > 0) { ?>
			<div class="col-md-3">
				<div class="form-group">
					<label>IPD Code</label>
					<div class="form-control"  >
						<a href="javascript:load_form_div('/Medical/list_med_inv/<?=$invoiceMaster[0]->ipd_id ?>','maindiv');" > 
							<?=$invoiceMaster[0]->ipd_code ?>
						</a>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<?php if($invoiceMaster[0]->payment_received ==0) { ?>
				<div class="form-group">
					<div class="radio">
						<label>
						  <input name="optionsRadios_credit" id="options_credit1" value="0" <?=radio_checked("0",$invoiceMaster[0]->ipd_credit)?> type="radio">
						  Cash
						</label>
						<label>
							<input name="optionsRadios_credit" id="options_credit2" value="1" <?=radio_checked("1",$invoiceMaster[0]->ipd_credit)?> type="radio">
							Credit
						</label>
					</div>
				</div>
				<?php }else{ ?>
					<div class="form-group">
						<label>Status</label>
						<div class="form-control" ><?=$invoiceMaster[0]->credit_status ?></div>
					</div>
				<?php } ?>
			</div>
			<div class="col-xs-3">
				<div class="form-group">
					<label>Org.</label>
					<input class="form-control varchar" name="in_org_name" id="in_org_name" placeholder="" value="<?=$org_Name?>" type="text" readonly=true  />
				</div>
			</div>
			<?php }elseif($invoiceMaster[0]->case_id > 0){  ?>
				<div class="col-md-3">
					<div class="form-group">
						<label>Case Code</label>
						<div class="form-control">
							<a href="javascript:load_form_div('/Medical/list_med_orginv/<?=$OCaseMaster[0]->id ?>','maindiv');" > 
								<?=$OCaseMaster[0]->case_id_code ?>
							</a>
						</div>
					</div>
				</div>
				<div class="col-md-3">
				<?php if($invoiceMaster[0]->payment_received ==0) { ?>
				<div class="form-group">
					<div class="radio">
						<label>
						  <input name="optionsRadios_credit" id="options_credit1" value="0" <?=radio_checked("0",$invoiceMaster[0]->case_credit)?> type="radio">
						  Cash
						</label>
						<label>
							<input name="optionsRadios_credit" id="options_credit2" value="1" <?=radio_checked("1",$invoiceMaster[0]->case_credit)?> type="radio">
							Credit
						</label>
					</div>
				</div>
				<?php }else{ ?>
					<div class="form-group">
						<label>Case Code</label>
						<div class="form-control" ><?=$invoiceMaster[0]->credit_org_status ?></div>
					</div>
				<?php } ?>
				</div>
			<?php }  ?>	
		</div>
		</div>
		<hr />
	</div>
	<div class="box-body">
		
		<div class="row " id="show_item_list">
		<table class="table table-striped ">
			<tr>
				<th style="width: 10px">#</th>
				<th>Item Name</th>
				<th>Batch No</th>
				<th>Exp.</th>
				<th>Rate</th>
				<th>Saved Qty.</th>
				<th>Qty.</th>
				<th>Price</th>
				<th>Disc.</th>
				<th>Amount</th>
				<th></th>
			</tr>
			<?php
			$srno=0;
				foreach($inv_items as $row)
				{ 
					$srno=$srno+1;
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->item_Name.'['.$row->formulation.']</td>';
					echo '<td>'.$row->batch_no.'</td>';
					echo '<td>'.$row->expiry.'</td>';
					echo '<td>'.$row->price.'</td>';
					echo '<td>'.$row->qty.'</td>';
					echo '<td>
					<input class="form-control" style="width:100px" name="input_qty_'.$row->id.'" id="input_qty_'.$row->id.'"  value="'.$row->qty.'" type="number" /></td>';
					echo '<td>'.$row->amount.'</td>';
					echo '<td>'.$row->disc_amount.'</td>';
					echo '<td>'.$row->tamount.'</td>';
					echo '<td>
					<button type="button" class="btn btn-primary" id="btn_update" onclick="update_qty('.$row->id.')"><i class="fa fa-edit"></i></button>
					<button type="button" class="btn btn-danger" id="btn_add_fee" onclick="remove_item_invoice('.$row->id.')"><i class="fa fa-remove"></i></button></td>';
					echo '</tr>';
				}
			echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
			?>
			<!---- Total Show  ----->
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th>Gross Total</th>
				<th><?=$invoiceGtotal[0]->Gtotal ?></th>
				<th><?=$invoiceGtotal[0]->t_dis_amt?></th>
				<th><?=$invoiceGtotal[0]->tamt?></th>
				<th></th>
			</tr>
			<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th>Tax Total</th>
				<th colspan=2>TCGST : <?=$invoiceGtotal[0]->TCGST?> / TSGST :<?=$invoiceGtotal[0]->TSGST?></th>
				<th></th>
				<th></th>
			</tr>
			</tr>
		</table>
		</div>
		<hr />
		<div class="row">
			<div class="col-md-6"> 
				<div class="form-group">
					<div class="ui-widget">
						<label for="tags">Product Search: </label>
						<input class="form-control" name="input_drug" id="input_drug" placeholder="Like Item Code , Item Name" type="text" >
					</div>
				</div>
			</div>
		</div>
		<div class="row">
		<input type="hidden" id="l_ssno" name="l_ssno"  />
			<div class="col-md-2">
				<div class="form-group">
					<label>Product Code</label>
					<div class="form-control" name="input_product_code" id="input_product_code" placeholder="Product Code"  ></div>
				</div>
			</div>
			<div class="col-md-2">
				<div class="form-group">
					<label>Product Name</label>
					<div class="form-control" name="input_product_name" id="input_product_name" placeholder="Product Code"  ></div>
				</div>
			</div>
			<div class="col-md-2">
				<div class="form-group">
					<label>Batch | Expiry</label>
					<div class="form-control" name="input_batch" id="input_batch" placeholder="Batch"  ></div>
				</div>
			</div>
			<div class="col-md-2">
				<div class="form-group">
					<label>MRP / Unit Rate</label>
					<div class="form-control" name="input_product_mrp" id="input_product_mrp" placeholder="MRP"  ></div>
				</div>
			</div>
			<div class="col-md-2">
				<div class="form-group">
					<label>Unit Rate </label>
					<input class="form-control" name="input_product_unit_rate" id="input_product_unit_rate" placeholder="Unit Rate" />
				</div>
			</div>
			
		</div>
		<div class="row">
			<div class="col-md-2">
				<div class="form-group">
					<label>Qty </label>
					<input class="form-control number" name="input_product_qty" id="input_product_qty" placeholder="Qty Like No. of Tab." type="text" value=0 />
				</div>
			</div>
			<div class="col-md-2">
				<div class="form-group">
					<label>Disc %</label>
					<input class="form-control number" name="input_disc" id="input_disc" placeholder="Discount %" type="text" value=0  />
				</div>
			</div>
			<div class="col-md-2">
				<div class="form-group">
					<button type="button" class="btn btn-primary" id="additem" onclick="add_item_invoice()" >Add</button>
				</div>
			</div>
			<div class="col-md-2">
				<div class="form-group">
					
				</div>
			</div>
			
		</div>
		<div class="row">
			<div class="col-md-10">
			</div>
			<div class="col-md-2">
				<div class="form-group">
					<button type="button" class="btn btn-success" id="finalinvoice"  >Final Invoice</button>
				</div>
			</div>
		</div>
	</div>
</div>
<?php echo form_close(); ?>
</section>
<!-- /.content -->
<script>
    $(document).ready(function(){
		$("#itype_idv").change(function(){
			$.post('/index.php/PathLab/list_pathtest_bytype',{ "itype_idv": $('#itype_idv').val(),"ins_id": $('#ins_id').val() }, function(data){
				$('.show_lab_test').html(data);
			});
		});

		$('#finalinvoice').click(function(){
			var inv_id = $('#med_invoice_id').val();
			var doc_id=$('#doc_name_id').val();
			var doc_name=$('#input_doc_name').val();
			var input_remark_ipd=$('#input_input_remark_ipd').val();
			var patient_code=$('#input_patient_code').val();
			var custmer_Name=$('#input_custmer_Name').val();
			var ipd_credit=$("input[name='optionsRadios_credit']:checked"). val();
			
			var org_credit=$("input[name='optionsRadios_credit']:checked"). val();
			
			var inv_date=$('#datepicker_invoicedate').val();
			
			var srno=$('#srno').val();

			if(srno > 0)
			{
				$.post('/index.php/Medical/go_final',{ 
				"inv_id": inv_id, 
				"doc_id": doc_id,
				"doc_name": doc_name,
				"patient_code": patient_code,
				"custmer_Name": custmer_Name,
				"ipd_credit":ipd_credit,
				"org_credit":org_credit,
				"inv_date":inv_date,
				"remark_ipd":input_remark_ipd}, function(data){

				load_form_div('/Medical/final_invoice/'+inv_id,'maindiv');
				});

			}else{
				alert('No Item Added');
			}
        });
	});
	
	function add_item_invoice()
	{
		var inv_id = $('#med_invoice_id').val();
		var l_ssno = $('#l_ssno').val();
		var input_qty = $('#input_product_qty').val();
		var elmnt = document.getElementById("input_product_qty");
		var product_unit_rate=$('#input_product_unit_rate').val();
		if(l_ssno > 0)
		{
			if(input_qty > 0)
			{
				$.post('/index.php/Medical/add_item/1',
				{ "l_ssno": l_ssno, 
				"qty": input_qty,
				"product_unit_rate": product_unit_rate,
				"disc": $('#input_disc').val(),
				"inv_id": inv_id
				}, function(data){
				
				if(data.insertid>0)
                {
					$('#show_item_list').html(data.content);
				}else{
					alert(data.error);
				}
				
				
				$("#input_product_code").html('');
				$("#input_product_name").html('');
				$("#l_ssno").html('');
				$("#input_batch").html('');
				$("#input_product_mrp").html('');
				$("#input_product_unit_rate").val('');
				$("#l_ssno").val(0);
				$("#input_drug").val('');
				$("#input_drug").val('');
				$("#input_drug").focus();
				elmnt.scrollIntoView();
				$("#input_product_qty").val('0');
				$("#input_disc").val('0');
				}, 'json');
			}else{
				alert('Qty. is 0');
			}
		}else{
			alert('Select Product First');
		}
	}
	
	function remove_item_invoice(itemid)
	{
		$.post('/index.php/Medical/add_item/0',{ "itemid": itemid, 
		"inv_id": $('#med_invoice_id').val() }, function(data){
		$('#show_item_list').html(data.content);
		}, 'json');
	}
	
	function update_qty(itemid)
	{
			var u_qty=$('#input_qty_'+itemid).val();

			$.post('/index.php/Medical/add_item/2',
			{ "itemid": itemid, 
				"u_qty": u_qty  }, function(data){
			$('#show_item_list').html(data.content);
			}, 'json');
        
	}
   
   $(document).ready(function(){
	   var cache = {};
	   
		$("#input_drug").autocomplete({
		    source: function( request, response ) {
			var term = request.term;
			if ( term in cache ) {
			  response( cache[ term ] );
			  return;
			}
			$.getJSON( "Medical/get_drug", request, function( data, status, xhr ) {
			  cache[ term ] = data;
			  response( data );
			});
		},
        minLength: 2,
        autofocus: true,
		select: function( event, ui ) {
			$("#input_product_code").html(ui.item.l_item_code);
			$("#input_product_name").html(ui.item.value);
			$("#l_ssno").html(ui.item.l_ss_no);
			$("#input_batch").html(ui.item.l_Batch+' | '+ui.item.l_Expiry);
			$("#input_product_mrp").html(ui.item.l_mrp + ' | ' + ui.item.l_unit_rate);
			$("#input_product_unit_rate").val(ui.item.l_unit_rate);
			$("#l_ssno").val(ui.item.l_ss_no);
			}		      	
		});
	  });
    
	document.getElementById("input_drug").accessKey = "s";
	document.getElementById("additem").accessKey = "i";
	
</script>
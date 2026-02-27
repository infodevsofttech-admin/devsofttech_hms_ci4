<section class="content-header">
  <h1>
	Charges
	<small></small>
  </h1>
  <ol class="breadcrumb">
	<li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
	<li class="active">Dashboard</li>
  </ol>
</section>
<section class="content">
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="box box-danger">
    <div class="box-header">
		<div class="box-title">
			<p><strong>Name :</strong><?=$person_info[0]->p_fname?>  
			<strong>/ Age :</strong><?=$person_info[0]->age?> 
			<strong>/ Gender :</strong><?=$person_info[0]->xgender?> 
			<strong>/ P Code :</strong><?=$person_info[0]->p_code?>
			<?php
				if($hc_insurance_card[0]->insurance_id>0)
				{
					echo '<strong>/ Ins. Comp. :</strong>'.$insurance[0]->ins_company_name;
				}
			?>
			</p>
			<input type="hidden" id="pid" name="pid" value="<?=$person_info[0]->id?>" />
			<input type="hidden" id="ins_id" name="ins_id" value="<?=$hc_insurance_card[0]->insurance_id?>" />
			<input type="hidden" id="lab_invoice_id" name="lab_invoice_id" value="<?=$invoiceMaster[0]->id?>" />
        </div>
    </div>
	<div class="box-body">
		<div class="row">
			<div class="col-xs-4">
				<div class="form-group">
				<label>Doctor Name</label>
					<select class="form-control" id="doc_name_id" name="doc_name_id"  >	
						<option value='0' <?=combo_checked('0',$invoiceMaster[0]->refer_by_id)?>  >From Other Hospital</option>
						<?php 
						foreach($doclist as $row)
						{ 
							echo '<option value='.$row->id.'  '.combo_checked($row->id,$invoiceMaster[0]->refer_by_id).'  >'.$row->p_fname.'</option>';
						}
						?>
					</select>
				</div>
			</div>
			<div class="col-xs-2">
				<div class="form-group">
					<label>Other Doctor</label>
					<input class="form-control varchar" name="input_doc_name" id="input_doc_name" placeholder="Doctor Name" value="<?=$invoiceMaster[0]->refer_by_other?>" type="text"  />
				</div>
			</div>
		</div>
		<div class="row " id="show_item_list">
		<table class="table table-striped ">
			<tr>
				<th style="width: 10px">#</th>
				<th>Charges Group</th>
				<th>Charge Name</th>
				<th>Rate</th>
				<th>Qty</th>
				<th>Amount</th>
				<th></th>
			</tr>
			<?php
			$srno=0;
			foreach($invoiceDetails as $row)
				{ 
					$srno=$srno+1;
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->item_type.'</td>';
					echo '<td>'.$row->item_name.'</td>';
					echo '<td>'.$row->item_rate.'</td>';
					echo '<td>'.$row->item_qty.'</td>';
					echo '<td>'.$row->item_amount.'</td>';
					echo '<td><button type="button" class="btn btn-primary" id="btn_remove" onclick="remove_item_invoice('.$row->id.')">-Remove</button></td>';
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
				<th>Gross Total</th>
				<th><?=$invoiceGtotal[0]->Gtotal?></th>
				<th></th>
			</tr>
		</table>
		</div>
		<hr />
		<div class="row">
			<div class="col-xs-4">
				<div class="form-group">
				<label>Charges Group</label>
                  <select class="form-control" id="itype_idv" name="itype_id"  >					
					<?php 
						foreach($labitemtype as $row)
						{ 
							echo '<option value='.$row->itype_id.'>'.$row->desc.'</option>';
						}
					?>
					</select>
				</div>
			</div>
			<div class="col-xs-4 show_lab_test">
				<div class="form-group">
				<label>Charge Name</label>
					<select class="form-control" id="itype_name_id" name="itype_name_id"  >					
						<?php 
						foreach($labitem as $row)
						{ 
							echo '<option value='.$row->id.'>'.$row->sdesc.'</option>';
						}
						?>
					</select>
				</div>
			</div>
			<div class="col-xs-2">
				<div class="form-group">
					<label>Qty</label>
					<input class="form-control" name="input_qty" id="input_qty" placeholder="Qty" value="1" type="number" readonly=true />
				</div>
			</div>
			<div class="col-xs-2">
				<div class="form-group">
					<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice()' >Add in List</button>
				</div>
			</div>
		</div>
		
		<div class="row">
			<div id="final_button" class="col-xs-4" >
				<div class="form-group">
					<button type="button" class="btn btn-primary" id="finalinvoice"  >Final Invoice</button>
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
			$.post('/index.php/OcasePathLap/list_pathtest_bytype',{ "itype_idv": $('#itype_idv').val(),"ins_id": $('#ins_id').val() }, function(data){
				$('.show_lab_test').html(data);
			});
		});

		$('#finalinvoice').click(function(){
			var srno = $('#srno').val();
            var inv_id = $('#lab_invoice_id').val();
			var doc_id=$('#doc_name_id').val();
			var refername=$('#input_doc_name').val();

			$.post('/index.php/OcasePathLap/update_refer_doc',{ 
			"inv_id": inv_id, 
			"doc_id": doc_id,
			"refername":refername}, function(data){
			$('#show_item_list').html(data);
			});
			
			if(srno>0)
			{
				load_form('/OcasePathLap/showinvoice/'+inv_id);
			}else{
				alert('No Item Added');
			}
            
        });
	});
	
	function add_item_invoice()
	{
		if($('#input_qty').val()>0)
		{
			$.post('/index.php/OcasePathLap/showitem/1',{ "itype_name_id": $('#itype_name_id').val(), 
			"itype_idv": $('#itype_idv').val(), 
			"lab_invoice_id": $('#lab_invoice_id').val(),
			"ins_id": $('#ins_id').val(),
			"input_qty": $('#input_qty').val() }, function(data){
			$('#show_item_list').html(data);
			});
		}
	}
	
	function remove_item_invoice(itemid)
	{
			$.post('/index.php/OcasePathLap/showitem/0',{ "itemid": itemid, 
			"lab_invoice_id": $('#lab_invoice_id').val() }, function(data){
			$('#show_item_list').html(data);
			});
        
	}
   
    
</script>
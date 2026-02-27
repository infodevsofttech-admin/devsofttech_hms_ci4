<section class="content-header">
  <h1>
	Charges
	<small></small>
  </h1>
  <ol class="breadcrumb">
	<?php
		if($invoiceMaster[0]->ipd_id>0)
		{
			echo '<li><a href="javascript:load_form(\'/Ipd/ipd_panel/'.$invoiceMaster[0]->ipd_id.'\');"><i class="fa fa-dashboard"></i> IPD Panel</a></li>';
		}
	?>
	<li><a href="javascript:load_form('/Patient/person_record/<?=$invoiceMaster[0]->attach_id ?>');"><i class="fa fa-dashboard"></i> Person</a></li>
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
			</p>
			<input type="hidden" id="pid" name="pid" value="<?=$person_info[0]->id?>" />
			<input type="hidden" id="ins_id" name="ins_id" value="<?=$pdata?>" />
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
				<th>Updated Qty</th>
				<th>Amount</th>
				<th></th>
			</tr>
			<?php
			$srno=1;
				foreach($invoiceDetails as $row)
				{ 
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->desc.'</td>';
					echo '<td>'.$row->item_name.'</td>';
					
					if(in_array($row->item_type, array(1,2,3,4,5)))
					{
						echo '<td>'.$row->item_rate.'</td>';
						echo '<td>'.$row->item_qty.'</td>';
						echo '<td>'.$row->item_amount.'</td>';
						echo '<td>';
					}else{
						echo '<td><input type=hidden name="hidden_rate_'.$row->id.'" id="hidden_rate_'.$row->id.'"  value="'.$row->item_rate.'" >'.$row->item_rate.'</td>';
						echo '<td><input class="form-control" style="width:100px" name="input_qty_'.$row->id.'" id="input_qty_'.$row->id.'"  value="'.$row->item_qty.'" type="number" /></td>';
						echo '<td>'.$row->item_amount.'</td>';
						echo '<td>';
						echo '<button type="button" class="btn btn-primary" id="btn_update" onclick="update_qty('.$row->id.')">Update</button>';
					}
					
					$sql="select * from lab_request where charge_item_id=".$row->id;
					$query = $this->db->query($sql);
					$lab_request= $query->result();
					
					if(count($lab_request)<1)
					{
						echo '<button type="button" class="btn btn-danger" id="btn_remove" onclick="remove_item_invoice('.$row->id.')">-Remove</button>';
					}

					echo '</td>';
					$srno=$srno+1;
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
				<th>Gross Total</th>
				<th><?=$invoiceGtotal[0]->Gtotal?></th>
				<th></th>
				
			</tr>
		</table>
		</div>
		<hr />
		<div class="row">
			<div class="col-xs-2">
				<div class="form-group">
				<label>Charge Type</label>
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
			<div class="col-xs-2" >
				<div class="form-group">
				<label>Charge Code</label>
				<input class="form-control number" name="input_charge_code" id="input_charge_code" placeholder="ECHS Code "  type="text"  />
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
					<label>Rate</label>
					<input class="form-control number" name="input_rate" id="input_rate" placeholder="Rate" value="0.00" type="text"  />
				</div>
			</div>
			<div class="col-xs-2">
				<div class="form-group">
					<label>Qty</label>
					<input class="form-control" name="input_qty" id="input_qty" placeholder="Qty" value="1" type="number"  />
				</div>
			</div>
			<div class="col-xs-2">
				<div class="form-group">
					<button type="button" class="btn btn-primary" id="additem" onclick='add_item_invoice()' >Add in List</button>
				</div>
			</div>
		</div>
		<div class="row">
			
			<div class="col-xs-4">
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
			$.post('/index.php/PathLab/list_pathtest_bytype',{ "itype_idv": $('#itype_idv').val(),"ins_id": $('#ins_id').val() }, 
			function(data){
				$('.show_lab_test').html(data);
				$('#input_rate').val('0.00');
			});
		});
		
		$("#itype_name_id").change(function(){
			$('#input_rate').val('0.00');
		});

		$("#input_charge_code").change(function(){
			$.post('/index.php/PathLab/get_echs_id',{ "itype_idv": $('#itype_idv').val(),
			"input_charge_code": $('#input_charge_code').val(),"ins_id": $('#ins_id').val() }, function(data){
			if(data>0)
			{
				$('#itype_name_id').val(data);
				$('#input_rate').val('0.00');
			}else{
				alert('Not Found');
				$('#input_rate').val('0.00');
			}
				
				
			});
		});
		
		$('#finalinvoice').click(function(){
			var srno = $('#srno').val();
            var inv_id = $('#lab_invoice_id').val();
			var doc_id=$('#doc_name_id').val();
			var refername=$('#input_doc_name').val();

			$.post('/index.php/PathLab/update_refer_doc',{ 
			"inv_id": inv_id, 
			"doc_id": doc_id,
			"refername":refername}, function(data){
			$('#show_item_list').html(data);
			});
			
			if(srno>0)
			{
				load_form('/PathLab/showinvoice/'+inv_id);
			}else{
				alert('No Item Added');
			}

        });
	});
	
	function add_item_invoice()
	{
		if($('#input_qty').val()>0)
		{
			$.post('/index.php/PathLab/showitem/1',{ "itype_name_id": $('#itype_name_id').val(), 
			"itype_idv": $('#itype_idv').val(), 
			"lab_invoice_id": $('#lab_invoice_id').val(),
			"ins_id": $('#ins_id').val(),
			"input_qty": $('#input_qty').val(),
			"input_rate": $('#input_rate').val() }, function(data){
			$('#show_item_list').html(data);
			});
		}
	}

	function remove_item_invoice(itemid)
	{
			$.post('/index.php/PathLab/showitem/0',{ "itemid": itemid, 
			"lab_invoice_id": $('#lab_invoice_id').val() }, function(data){
			$('#show_item_list').html(data);
			});
        
	}
	
	function update_qty(itemid)
	{
			var update_qty=$('#input_qty_'+itemid).val();
			var item_rate=$('#hidden_rate_'+itemid).val();
			
			$.post('/index.php/PathLab/showitem/2',{ "itemid": itemid, 
			"lab_invoice_id": $('#lab_invoice_id').val(),"update_qty": 
			update_qty,"item_rate": item_rate}, function(data){
			$('#show_item_list').html(data);
			});
        
	}
   
    
</script>
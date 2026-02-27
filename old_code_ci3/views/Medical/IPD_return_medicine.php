<section class="content-header">
  <h1>
	Medical Return Items
	<small></small>
  </h1>
</section>
<section class="content">
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="box box-danger">
    <div class="box-header">
		<div class="row">
			<input type="hidden" id="pid" name="pid" value="<?=$person_info[0]->id ?>" />
			<div class="col-md-2">
				<div class="form-group">
					<label>Patient Code</label>
					<input class="form-control" name="input_patient_code" id="input_patient_code" placeholder="Patient Code" type="text" value="<?=$person_info[0]->p_code ?>" readonly=true />
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					<label>Customer Name</label>
					<input class="form-control" name="input_custmer_Name" id="input_custmer_Name" placeholder="Customer Name" type="text" value="<?=$person_info[0]->p_fname ?>"  readonly=true />
				</div>
			</div>
		<?php if($inv_type==0 && $inv_type_id>0) { 	?>
			<div class="col-md-3">
				<div class="form-group">
					<label>IPD Code</label>
					<div class="form-control"  >
						<a href="javascript:load_form_div('/Medical/list_med_inv/<?=$ipd_master[0]->id ?>','maindiv');" > 
							<?=$ipd_master[0]->ipd_code ?>
						</a>
					</div>
				</div>
			</div>
			<?php }elseif($inv_type==1 && $inv_type_id>0){  ?>
				<div class="col-md-3">
					<div class="form-group">
						<label>Case Code</label>
						<div class="form-control">
							<a href="javascript:load_form_div('/Medical/list_med_orginv/<?=$org_master[0]->id ?>','maindiv');" > 
								<?=$org_master[0]->case_id_code ?>
							</a>
						</div>
					</div>
				</div>
			<?php }  ?>	
		</div>
        <div>
        <button  type="button" class="btn btn-success" id="finalreturn" 
		onclick="pre_return_list(<?=$inv_type?>,<?=$inv_type_id?>)"  >Final Return</button>
		<a href="<?php echo '/Medical/Med_Return_print/'.$ipd_master[0]->id.'/0';  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Return List</a>
        </div>
	</div>
	<div class="box-body">
        <div class="row">
            <div class="col-md-8"> 
                <div class="form-group">
                    <div class="ui-widget">
                        <label for="tags">Search Return Item: </label>
                        <input class="form-control input-sm" name="input_drug" id="input_drug" placeholder="Like Item Code , Item Name" type="text" >
                    </span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
            <span class="text-muted" name="input_inv_item_id" id="input_inv_item_id" >
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <input type="hidden" id="inv_item_id" name="inv_item_id" value=0  />
                <p class="text-red" >
                    <span class="text-green" name="input_product_name" id="input_product_name">
                    </span>
                    <span class="text-light-blue" name="input_batch" id="input_batch" >
                    </span>
                    <span class="text-lead" name="input_product_mrp" id="input_product_mrp">
                    </span>
                </p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Qty </label>
                    <input class="form-control number input-sm" name="input_product_qty" id="input_product_qty" placeholder="Qty Like No. of Tab." type="text" value=0 autocomplete="off"  />
                    <input type="hidden" id="hid_c_qty" name="hid_c_qty" value="0" />
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <button type="button" class="btn btn-primary" id="additem" onclick="add_qty()" >Add</button>
                </div>
            </div>
        </div>
	</div>
	<div class="box-footer">
        <div class="row " id="show_item_list">
        </div>
	</div>
</div>
<?php echo form_close(); ?>
</section>
<!-- /.content -->
<script>

load_form_div('/Medical/IPD_Item_Return_temp/<?=$inv_type_id?>/<?=$inv_type?>','show_item_list');
   
    function pre_return_list(inv_type,inv_type_id){

		load_form_div('/Medical/pre_reurn_item_list/'+inv_type_id+'/'+inv_type,'maindiv'); 
	}

    function add_qty()
	{
        var u_qty=parseInt($('#input_product_qty').val());
		var old_qty=parseInt($('#hid_c_qty').val());
        var itemid=parseInt($('#inv_item_id').val());
        var elmnt = document.getElementById("input_drug");
       
        var csrf_dst_name_value=$("input[name='<?=$this->security->get_csrf_token_name()?>']").val();
		
        
        if(itemid==0 || itemid=='' )
        {
            notify('Error','Select Item First');
        }else{
            if(u_qty>0 && u_qty<=old_qty)
            {
                $.post('/index.php/Medical/Update_Return',
                {   
                    "itemid": itemid, 
                    "u_qty": u_qty,
                    "<?php echo $this->security->get_csrf_token_name(); ?>" : csrf_dst_name_value
                    }, function(data){
                    if(data.update>0)
                    {
                        var inv_type=<?=$inv_type?>;
                        var inv_type_id=<?=$inv_type_id?>;

                        load_form_div('/Medical/IPD_Item_Return_temp/'+inv_type_id+'/'+inv_type,'show_item_list');
                        notify('Success',data.msg_text);
                    }else{
                        alert(data.msg_text);
                    }	
                }, 'json');
            }else{
                alert('Return Value between 1 and '+old_qty);
            }
        }

        $('#input_product_qty').val(0)
        $("#input_inv_item_id").html('');
        $("#inv_item_id").val(0);
        $("#input_product_name").html('');
        $("#input_batch").html('');
        $("#input_product_mrp").html('');
        $("#input_drug").val('');
        $("#input_drug").focus();
		elmnt.scrollIntoView();
}

    function remove_item(itemid)
	{
			$.post('/index.php/Medical/undo_Return',
				{ "itemid": itemid,
					'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>' }, function(data){
					if(data.update>0)
	                {
						var inv_type=<?=$inv_type?>;
                        var inv_type_id=<?=$inv_type_id?>;

                        load_form_div('/Medical/IPD_Item_Return_temp/'+inv_type_id+'/'+inv_type,'show_item_list');
					}
				}, 'json');
	
	}


    $(document).ready(function(){
	   var cache = {};
	   
		$("#input_drug").autocomplete({
		    source: function( request, response ) {
			$.getJSON( "Medical/IPD_Item_list/<?=$inv_type_id?>", request, function( data, status, xhr ) {
				response( data );
			});        
		},
        minLength: 1,
        autofocus: true,
		select: function( event, ui ) {
			$("#input_inv_item_id").html('| Product Code:'+ui.item.l_item_code);
			$("#input_product_name").html('Name:'+ui.item.value);
			$("#input_batch").html(' | Batch No.:'+ui.item.l_Batch);
			$("#input_product_mrp").html(' | MRP:'+ui.item.l_mrp + ' | Unit Rate :' + ui.item.l_price);
			$("#inv_item_id").val(ui.item.l_item_code);
            $("#hid_c_qty").val(ui.item.l_qty);
			}		      	
		});
	});

    

	

</script>
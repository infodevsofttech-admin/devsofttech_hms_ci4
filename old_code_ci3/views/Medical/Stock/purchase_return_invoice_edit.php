<section class="content-header">
	<h3 class="box-title">Purchase Return Invoice  / Supplier : <?=$purchase_return_invoice[0]->name_supplier ?></h3>
	<small>|<i>Invoice No.</i>: <?=$purchase_return_invoice[0]->Invoice_no ?>  |
	<i>Invoice Date</i> : <?=$purchase_return_invoice[0]->str_date_of_invoice ?> 
	<button onclick="load_form_div('/Medical_backpanel/PurchaseReturnInvoiceEdit/<?=$purchase_return_invoice[0]->id ?>','searchresult');" type="button" class="btn btn-warning">Reload Invoice</button>
	<a href="<?php echo '/Medical_Print/print_purchase_return/'.$purchase_return_invoice[0]->id;  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>	
</small>
</section>
<section class="content">
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="col-md-6" style="padding: 5px;">
	<div class="row " >
		<div id="show_item_list">
			<?=$content?>
		</div>
	</div>
</div>
<div class="col-md-6" style="padding: 5px;">
	<div class="box box-info">
		<div class="box-header with-border">
			<h4 class="box-title">Item List</h4>
			<div class="pull-right box-tools">
				<button type="button" class="btn btn-info btn-flat btn-sm" onclick="load_form_div('/Medical_backpanel/Purchase_Invoice_old/<?=$purchase_return_invoice[0]->sid ?>','search_body_part');">Show Items from same supplier</button>
				<button type="button" class="btn btn-info btn-flat btn-sm" onclick="load_form_div('/Medical_backpanel/Purchase_Invoice_product/<?=$purchase_return_invoice[0]->sid ?>','search_body_part');">Product Search</button>
			</div>
		</div>
		<!-- /.box-header -->
		<!-- form start -->
		<div class="box-body" style="overflow:scroll;" id="search_body_part" name="search_body_part">
			
		</div>
		<!-- /.box-body -->
		<div class="box-footer" id="search_footer_part" name="search_footer_part">
			
		</div>
		<!-- /.box-footer -->
	</div>
</div>
<?php echo form_close(); ?>
</section>

<script>
$(document).ready(function(){
	load_form_div('/Medical_backpanel/PurchaseReturn_invoice_item_list/<?=$purchase_return_invoice[0]->id ?>','invoice_item_list');
});

function remove_item_add(itemid)
{
	var rqty=$('#input_qty_'+itemid).val();

	var inv_id = <?=$purchase_return_invoice[0]->id ?>;

	var wait_for_next=$("#wait_for_next").val();
	var csrf_dst_name_value=$("input[name='<?=$this->security->get_csrf_token_name()?>']").val();

	if(wait_for_next>0)
	{
		alert('wait......');
		return false;
	}
	
	$.post('/index.php/Medical_backpanel/add_remove_item',
		{ "itemid": itemid, 
		"inv_id": inv_id,
		"rqty":rqty,
		"<?php echo $this->security->get_csrf_token_name(); ?>" : csrf_dst_name_value
		}, function(data){
			if(data.update==0)
			{
				notify('error','Please Attention',data.msg_text);
			}else{
				notify('success','Please Attention',data.msg_text);
				$('#show_item_list').html(data.content);
			}
			
		}, 'json');
}

function remove_custom_item()
{
	var rqty=$('#input_product_qty').val();
	var itemid=$('#l_ssno').val();

	var input_batch_no=$('#input_batch_no').val();
	var input_expiry_dt=$('#input_expiry_dt').val();

	var inv_id = <?=$purchase_return_invoice[0]->id ?>;

	var wait_for_next=$("#wait_for_next").val();
	var csrf_dst_name_value=$("input[name='<?=$this->security->get_csrf_token_name()?>']").val();

	if(wait_for_next>0)
	{
		alert('wait......');
		return false;
	}

	var elmnt = document.getElementById("input_product_qty");

	$.post('/index.php/Medical_backpanel/add_remove_item',
		{ "itemid": itemid, 
		"inv_id": inv_id,
		"rqty":rqty,
		"rbatch_no":input_batch_no,
		"rexpiry_dt":input_expiry_dt,
		"<?php echo $this->security->get_csrf_token_name(); ?>" : csrf_dst_name_value
		}, function(data){
			if(data.update==0)
			{
				notify('error','Please Attention',data.msg_text);
			}else{

				$("#input_product_code").html('');
				$("#input_product_name").html('');

				$("#input_batch").html('');
				$("#input_product_mrp").html('');
				$("#input_product_unit_rate").val('');
				$("#l_ssno").val(0);
				$("#input_drug").val('');

				$("#input_product_qty").val('0');
				$("#input_disc").val('0');

				$("#stock_product_qty").html('');

				$("#purchase_id").html('');

				$("#input_drug").focus();
				elmnt.scrollIntoView();

				notify('success','Please Attention',data.msg_text);
				$('#show_item_list').html(data.content);
			}
			
		}, 'json'); 
}

function remove_item_invoice(itemid)
{
	var wait_for_next=$("#wait_for_next").val();
	var csrf_dst_name_value=$("input[name='<?=$this->security->get_csrf_token_name()?>']").val();

	if(wait_for_next>0)
	{
		alert('wait......');
		return false;
	}
	
	$.post('/index.php/Medical_backpanel/remove_item_invoice/'+itemid,{ "itemid": itemid, 
		"<?php echo $this->security->get_csrf_token_name(); ?>" : csrf_dst_name_value
		}, function(data){
			if(data.update==1)
			{
				$('#show_item_list').html(data.content);
			}
			
			$("#<?=$this->security->get_csrf_token_name()?>").val(data.csrf_dst_name_value);
	}, 'json');
}
</script>
<div id='Medical_invoice_final'>
<section class="content-header">
  <h1>
	Indent 
	<small>No. : <?=$invoice_stock_master[0]->indent_code?></small>
  </h1>
</section>
<section class="content">
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="box box-danger">
    <div class="box-header">
		<div class="row">
            <input type="hidden" id="location_type" name="location_type" value="<?=$invoice_stock_master[0]->location_id ?>" />
            <input type="hidden" id="location_id" name="location_id" value="<?=$invoice_stock_master[0]->location_id ?>" />
            <input type="hidden" id="indent_id" name="indent_id" value="<?=$invoice_stock_master[0]->id ?>" />
            <?php if($invoice_stock_master[0]->location_id>0) { ?>
            <div class="col-md-12">
                <p><strong>Location Name :</strong>
                    <?=$invoice_stock_master[0]->issued_name?>
                    <strong>/ Indent No. :</strong><?=$invoice_stock_master[0]->indent_code?>
                    <strong>/ Date :</strong> <?=MysqlDate_to_str($invoice_stock_master[0]->indent_date)?>
                </p>
            </div>
            <?php } ?>
		</div>
    </div>
	<div class="box-body">
		<div class="row " id="show_item_list">
			<div class="col-md-12">
				<table class="table table-striped ">
					<tr>
						<th style="width: 10px">#</th>
						<th>Item code</th>
						<th>Item Name</th>
						<th>Formulation</th>
						<th>Batch No</th>
						<th>Exp.</th>
						<th>Rate</th>
						<th>Qty.</th>
						<th>Price</th>
						<th>Disc.</th>
						<th>HSNCODE/C-SGST</th>
						<th>CGST</th>
						<th>SGST</th>
						<th>Amount</th>
					</tr>
					<?php
					
					$srno=0;
						foreach($inv_items as $row)
						{ 
							$srno=$srno+1;
							echo '<tr>';
							echo '<td>'.$srno.'</td>';
							echo '<td>'.$row->item_code.'</td>';
							echo '<td>'.$row->item_Name.'</td>';
							echo '<td>'.$row->formulation.'</td>';
							echo '<td>'.$row->batch_no.'</td>';
							echo '<td>'.$row->expiry.'</td>';
							echo '<td>'.$row->price.'</td>';
							echo '<td>'.$row->qty.'</td>';
							echo '<td>'.$row->amount.'</td>';
							echo '<td>'.$row->disc_amount.'</td>';
							echo '<td>'.$row->HSNCODE.'</td>';
							echo '<td>'.$row->CGST.'</td>';
							echo '<td>'.$row->SGST.'</td>';
							echo '<td>'.$row->tamount.'</td>';
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
						<th></th>
						<th>Gross Total</th>
						<th></th>
						<th></th>
						<th></th>
						<th><?=$invoiceGtotal[0]->Gtotal ?></th>
                        <th><?=$invoiceGtotal[0]->tamt?></th>
						<th></th>
					</tr>
				</table>
			</div>
		</div>
	<!-- /.row -->
	<div class="row no-print">
        <div class="col-xs-6">
        <a href="<?php echo '/Storestock/print_single_indent/'.$invoice_stock_master[0]->id;  ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
		</div>
    </div>
    <!-- /.row -->
	</div>
</div>
<?php echo form_close(); ?>
</section>
<!-- /.content -->

<script>
	$(document).ready(function(){

        document.title = 'Indent :<?=$invoice_stock_master[0]->issued_name?>/<?=$invoice_stock_master[0]->indent_code?>';		
	function enable_btn()
	{
		$('#btn_update1').attr('disabled', false);
		$('#btn_update2').attr('disabled', false);
		$('#btn_update_return').attr('disabled', false);
		
	}

	$('#btn_update1').click( function()
	{
		var inv_id = $('#med_invoice_id').val();
		
		$('#btn_update1').attr('disabled', true);
		$('#btn_update2').attr('disabled', true);
		
		if(confirm("Are you sure process this invoice "))
		{
		
			$.post('/index.php/Medical/confirm_payment',
			{ "mode":"1","med_invoice_id":inv_id,"input_amount_paid":$('#input_amount_paid').val(),
			'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'			}, function(data){
			if(data.update==0)
				{
					$('div.jsError').html(data.error_text);
					notify('error','Please Attention',data.error_text);	
					setTimeout(enable_btn,5000);
				}else
				{
					load_form_div('/Medical/final_invoice/'+inv_id,'Medical_invoice_final');
				}
			},'json');
		}else{
			setTimeout(enable_btn,5000);
			return false;
		}
	});
	
	$('#btn_update2').click( function()
	{
		$('#btn_update1').attr('disabled', true);
		$('#btn_update2').attr('disabled', true);
		
		$.post('/index.php/Medical/confirm_payment',
		{ "mode":"2",
			"med_invoice_id":$('#med_invoice_id').val(),
			"cbo_pay_type": $('#cbo_pay_type').val(),
			"input_card_tran": $('#input_card_tran').val(),
			"input_amount_paid":$('#input_amount_paid').val(),
		'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'}, function(data){
        if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
					notify('error','Please Attention',data.error_text);	
					enable_btn();
                }else
                {
					$('div.payment_type').html(data.showcontent);
				}
		},'json');
	});
	
	$('#btn_update3').click( function()
	{
		$.post('/index.php/Medical/confirm_payment',
		{ "mode":"3","med_invoice_id":$('#med_invoice_id').val(),
			"ipd_id": $('#hidden_ipd_id').val(),
			'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'}, function(data){
        if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
					$('div.payment_type').html(data.showcontent);
				}
		},'json');
	});
	
	
	
	$('#btn_update_ded').click( function()
	{
		$('#btn_update1').attr('disabled', true);
		$('#btn_update2').attr('disabled', true);

		var gross_amount=$('#gross_amount').val();
		var discount_amount=$('#input_dis_amt').val();

		var discount_apply=$('#discount_apply').val();

		var max_discount=gross_amount*10/100;

		if(discount_amount>max_discount && discount_apply==1)
		{
			alert("Discount Amount is greater the 10%");
			return false;
		}
		
		var inv_id = $('#med_invoice_id').val();
		$.post('/index.php/Medical/update_discount',{ "med_invoice_id": inv_id, 
			"input_dis_desc": $('#input_dis_desc').val(), 
			"input_dis_amt": $('#input_dis_amt').val(),
			'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'
			}, function(data){
				load_form_div('/Medical/final_invoice/'+inv_id,'Medical_invoice_final');
				setTimeout(enable_btn,1000);
			});
	});
	
	$('#btn_update_return').click( function(e)
	{
		var inv_id = $('#med_invoice_id').val();
		
		$('#btn_update1').attr('disabled', true);
		$('#btn_update2').attr('disabled', true);

		
		
		if(confirm("Are you sure process this invoice "))
		{
			$.post('/index.php/Medical/confirm_payment',
			{ "mode":"5","med_invoice_id":inv_id,"input_amount_paid":$('#input_amount_paid').val(),
				'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'
				}, function(data){
			if(data.update==0)
				{
					$('div.jsError').html(data.error_text);
					setTimeout(enable_btn,5000);
				}else
				{
					load_form_div('/Medical/final_invoice/'+inv_id,'Medical_invoice_final');
				}
			},'json');
		}else{
			setTimeout(enable_btn,5000);
			return false;
		}
	});

	$('#btn_adjust_balance').click( function(e)
	{
		var inv_id = $('#med_invoice_id').val();

		var gross_amount=$('#gross_amount').val();
		var discount_amount=$('#input_dis_amt').val();

		var discount_apply=$('#discount_apply').val();

		var max_discount=gross_amount*12/100;

		if(discount_amount>max_discount && discount_apply==1)
		{
			alert("Discount Amount is greater the 10% : Max Discount Amt : "+max_discount);
			return false;
		}

		
		$('#btn_update1').attr('disabled', true);
		$('#btn_update2').attr('disabled', true);
		
		if(confirm("Are you sure , Adjust the Balance "))
		{
			$.post('/index.php/Medical/Adjust_Payment',
			{ "med_invoice_id":inv_id,"input_amount_paid":$('#input_amount_paid').val(),
				'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'
				}, function(data){
					load_form_div('/Medical/final_invoice/'+inv_id,'Medical_invoice_final');
					setTimeout(enable_btn,1000);
			});
		}else{
			setTimeout(enable_btn,5000);
			return false;
		}
	});
	
});

	function edit_invoice(med_invoice_id){
		load_form_div('/Medical/Invoice_med_show/'+med_invoice_id,'maindiv'); 
	}

	function BillFinal(invoice_id){
		if(confirm("Are sure for This Action"))
		{
			var inv_id = $('#med_invoice_id').val();
			
			$.post('/Medical/InvBillFinal/'+inv_id,
			{ '<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>'}, function(data){
			if(data.update==0)
					{
						alert('Something Wrong');
					}else
					{
						alert('Update Success');
						load_form_div('/Medical/final_invoice/'+inv_id,'Medical_invoice_final'); 
					}
			},'json');
		}
	}
</script>
</div>
<section class="content-header">
<?php echo form_open('Payment_Medical/payment_record', array('role'=>'form','class'=>'form_payment_search')); ?>
			<div class="input-group input-group-sm">
				<input class="form-control number" type="text" id="txtsearch" name="txtsearch">
					<span class="input-group-btn">
					<button type="submit" class="btn btn-info btn-flat">Search Payment</button>
				</span>
			</div>
			 <?php echo form_close(); ?>
</section>

<div class="searchresult" id="searchresult"></div>

 <script>
	 $(document).ready(function(){
			$('form.form_payment_search').on('submit', function(form){
				form.preventDefault();
				$.post('/index.php/Payment_Medical/payment_record', $('form.form_payment_search').serialize(), function(data){
					$('div.searchresult').html(data);
				});
			});
	 });
	 
	 
 </script>
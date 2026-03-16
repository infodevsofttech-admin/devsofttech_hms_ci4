<section class="content-header">
<?php echo form_open('Patient/search', array('role'=>'form','class'=>'form2')); ?>
			<div class="col-md-3">
				<div class="form-group">
					<label><input id="chk_reorder" name="chk_reorder" type="checkbox"   > ReOrder List</label>
				</div>
			</div>
			<div class="col-md-6">
				<div class="input-group input-group-sm">
						<input class="form-control" type="text" id="txtsearch" name="txtsearch" placeholder="Like Item Name,Supplier Name"   >
						<span class="input-group-btn">
							<button type="submit" class="btn btn-info btn-flat">Search Items</button>
						</span>
						<span class="input-group-btn">
							<button type="button" id="btn_excel" class="btn btn-info btn-flat">Search Items Excel</button>
						</span>
						<span class="input-group-btn">
							<button type="button" id="btn_excel_2" class="btn btn-info btn-flat">Items Short Excel</button>
						</span>
				</div>
			</div>
<?php echo form_close(); ?>
</section>
<div class="searchresult" id="searchresult"></div>
 <script>
	 $(document).ready(function(){
			$('form.form2').on('submit', function(form){
				form.preventDefault();
				
				$.post('/index.php/Medical_backpanel/store_Stock_result', $('form.form2').serialize(), function(data){
					$('div.searchresult').html(data);
					$('#example1').DataTable();
				});
			});
			
			$('#btn_excel').click( function()
				{
					var ReOrder='0';

					if ($('#chk_reorder').is(":checked"))
					{
						ReOrder='1';
					}
					var Get_Query="/Medical_backpanel/Stock_result_excel/"+ReOrder+"/"+$('#txtsearch').val();
					alert(Get_Query);

					window.open(Get_Query, "_blank");
			});

			$('#btn_excel_2').click( function()
				{
					var ReOrder='0';

					if ($('#chk_reorder').is(":checked"))
					{
						ReOrder='1';
					}
					var Get_Query="/Medical_backpanel/Stock_result_excel_2/"+ReOrder+"/"+$('#txtsearch').val();
					alert(Get_Query);

					window.open(Get_Query, "_blank");
			});
	 });
 </script>
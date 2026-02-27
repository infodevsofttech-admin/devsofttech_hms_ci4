<section class="content-header">
<?php echo form_open('Patient/search', array('role'=>'form','class'=>'form2')); ?>
			<div class="col-md-2">
				<div class="form-group">
					<label><input id="chk_reorder" name="chk_reorder" type="checkbox"   > ReOrder List</label>
				</div>
			</div>
			<div class="col-md-4">
				<select class="form-control select2" id=schedule_id name="schedule_id[]" multiple="multiple" data-placeholder="Select a Schedule, Blank for All"  >
					<option value='1'>Schedule H</option>
					<option value='2'>Schedule H1</option>
					<option value='3'>Schedule X</option>
					<option value='4'>Schedule G</option>
					<option value='5'>Narcotic</option>
					<option value='6'>High Risk</option>
				</select>
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
							<button type="button" id="btn_excel_3" class="btn btn-info btn-flat">Items BatchWise Excel</button>
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

				$.post('/index.php/Storestock/store_Stock_result', $('form.form2').serialize(), function(data){
					$('div.searchresult').html(data);
					$('#example1').DataTable();
				});
			});
			
			$('#btn_excel').click( function()
				{
					var ReOrder='0';
					var item_name=$('#txtsearch').val();

					if ($('#chk_reorder').is(":checked"))
					{
						ReOrder='1';
					}

					var schedule_id=$('#schedule_id').val();
					
					if(schedule_id==null || schedule_id=='')
					{
						schedule_id="0";
					}else{
						schedule_id=schedule_id.toString().split(",").join("S");
					}

					if(item_name=='')
					{
						item_name='-'
					}


					var Get_Query="/Storestock/Stock_result_excel/"+ReOrder+"/"+item_name+"/"+schedule_id;
					alert(Get_Query);

					window.open(Get_Query, "_blank");
			});

			$('#btn_excel_2').click( function()
				{
					var ReOrder='0';

					var item_name=$('#txtsearch').val();

					if ($('#chk_reorder').is(":checked"))
					{
						ReOrder='1';
					}

					var schedule_id=$('#schedule_id').val();
					
					if(schedule_id==null || schedule_id=='')
					{
						schedule_id="0";
					}else{
						schedule_id=schedule_id.toString().split(",").join("S");
					}

					if(item_name=='')
					{
						item_name='-'
					}


					var Get_Query="/Storestock/Stock_result_excel_2/0/0/"+item_name+"/"+ReOrder+"/"+schedule_id;
					alert(Get_Query);

					window.open(Get_Query, "_blank");
			});

			$('#btn_excel_3').click( function()
				{
					
					var supplier_id=0;
					var opd_date_range=0;
					var item_name=$('#txtsearch').val();
					var ReOrder='0';

					if ($('#chk_reorder').is(":checked"))
					{
						ReOrder='1';
					}

					if(item_name=='')
					{
						item_name='-'
					}

					var schedule_id=$('#schedule_id').val();
					
					if(schedule_id==null || schedule_id=='')
					{
						schedule_id="0";
					}else{
						schedule_id=schedule_id.toString().split(",").join("S");
					}


					var Get_Query="/Storestock/Stock_result_excel_3/"+supplier_id+"/"+opd_date_range+"/"+item_name+"/"+ReOrder;
					alert(Get_Query);

					window.open(Get_Query, "_blank");
			});
	 });
 </script>
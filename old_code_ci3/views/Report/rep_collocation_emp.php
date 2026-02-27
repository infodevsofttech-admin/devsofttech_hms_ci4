<section class="content-header">
    <h1>
        Report Payment 
        <small>Panel</small>
    </h1>
</section>
<section class="content">
	<div class="row">
		<div class="col-md-4">
			<label>Charges Invoice Date Range</label>
			<div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
				<i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;
				<span></span> <b class="caret"></b>
				</div>
				<input type="hidden" name="opd_date_range" id="opd_date_range"  /> 
		</div>
		
		<div class="col-md-8">
			<label>Employee Name</label>
			<select class="form-control select2" id="emp_name_id" name="emp_name_id" multiple="multiple" data-placeholder="Select a Employees"  >	
				<option value='0' >All Employees</option>
				<?php 
				foreach($emplist as $row)
				{ 
					echo '<option value='.$row->id.'  >'.$row->first_name.' '.$row->last_name.'</option>';
				}
				?>
			</select>
		</div>
	</div>
	<div class="row">
		<div class="col-md-3">
			<label>Payment Mode</label>
			<select class="form-control" id="paymode_id" name="paymode_id"  >	
				<option value="0">Cash & Bank</option>
				<?php 
				foreach($paymodelist as $row)
				{ 
					echo '<option value='.$row->id.'  >'.$row->mode_desc.'</option>';
				}
				?>
			</select>
		</div>
		<div class="col-md-3">
			<label>Order First</label>
			<select class="form-control" id="order_1" name="order_1"  >	
				<option value='1'> Employees</option>
				<option value='2'> Paymode</option>
			</select>
		</div>
		<div class="col-md-3">
			<label> </label>
			<div class="form-group">
					<button type="button" class="btn btn-primary" id="showreport"  >Show</button>
					<button type="button" class="btn btn-success" id="showreport_daywise"  >Show Day Wise</button>
					<button type="button" class="btn btn-warning" id="showreportexport"  >Export</button>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<div id="show_report"></div>
		</div>
	</div>
</section>
<script type="text/javascript" language="javascript" >
			$(document).ready(function() {
				var start = moment();
				var end = moment();
				
				function cb(start, end) {
					$('#reportrange span').html(start.format('D-MM-YYYY h:mm:ss a') + ' - ' + end.format('D-MM-YYYY h:mm:ss a'));
					$('#opd_date_range').val(start.format('YYYY-MM-DD[T00:00:00]')+'S'+end.format('YYYY-MM-DD[T23:59:59]'));
				}

				$('#reportrange').daterangepicker({
					timePicker: true,
					timePickerIncrement: 1,
					locale: {
						format: 'DD-MM-YYYY HH:mm'
					}					
				}, cb);
	
				cb(start, end);

				$('#reportrange').on('apply.daterangepicker', function(ev, picker) {
					var date_first=picker.startDate.format('YYYY-MM-DD[T]HH:mm:ss');
					var date_second=picker.endDate.format('YYYY-MM-DD[T]HH:mm:ss');

					$('#opd_date_range').val(date_first+'S'+date_second);
				});
				
				$('#showreport').click( function()
				{
					var emp_list=$('#emp_name_id').val();
					
					if(emp_list==null || emp_list=='')
					{
						emp_list="0";
					}else{
						emp_list=emp_list.toString().split(",").join("S");
					}
					
					var Get_Query="Report/report_emp_total/"+$('#opd_date_range').val()+
					"/"+emp_list+
					"/"+$('#paymode_id').val()+
					"/"+$('#order_1').val();
					var url_send=encodeURIComponent(Get_Query)
					load_report_div(Get_Query,'show_report');
					
				});

				$('#showreport_daywise').click( function()
				{
					var emp_list=$('#emp_name_id').val();
					
					if(emp_list==null || emp_list=='')
					{
						emp_list="0";
					}else{
						emp_list=emp_list.toString().split(",").join("S");
					}
					
					var Get_Query="Report/report_emp_total_daywise/"+$('#opd_date_range').val()+
					"/"+emp_list+
					"/"+$('#paymode_id').val()+
					"/"+$('#order_1').val();
					var url_send=encodeURIComponent(Get_Query)
					load_report_div(Get_Query,'show_report');
					
				});
				
				$('#showreportexport').click( function()
				{
					var emp_list=$('#emp_name_id').val();
					
					if(emp_list==null || emp_list=='')
					{
						emp_list="0";
					}else{
						emp_list=emp_list.toString().split(",").join("S");
					}
					
					var Get_Query="Report/report_emp_total/"+$('#opd_date_range').val()+
					"/"+emp_list+
					"/"+$('#paymode_id').val()+
					"/"+$('#order_1').val()+"/1";
					var url_send=encodeURIComponent(Get_Query)
					window.open(Get_Query, "_blank");
					
				});
	
			
				
		});
</script>
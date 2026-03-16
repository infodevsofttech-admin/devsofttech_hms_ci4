<section class="content-header">
    <h1>
        Org. Invoice 
        <small>Panel</small>
    </h1>
</section>
<section class="content">
	<div class="row">
		<div class="col-md-3">
			<label>Discharge Date Range</label>
			<div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
				<i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;
				<span></span> <b class="caret"></b>
				</div>
				<input type="hidden" name="opd_date_range" id="opd_date_range"  /> 
		</div>
		<div class="col-md-4"> 
			<div class="form-group">
				<label>Insurance</label>
				<select class="form-control" name="Insurance_id" id="Insurance_id" >
				<option value="-1" >All Org.</option>
				<?php 
					foreach($data_insurance as $row)
					{ 
						echo '<option value="'.$row->id.'" '.combo_checked($ins_company_id,$row->id).'  >'.$row->ins_company_name.'</option>';
					}
				?>   
				</select>
			</div>
		</div>
		<div class="col-md-3">
			<label> </label>
			<div class="form-group">
				<button type="button" class="btn btn-primary" id="showreport"  >Show</button>
				<button type="button" class="btn btn-primary" id="showreport_xls"  >Excel</button>
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
					$('#reportrange span').html(start.format('D-MM-YYYY') + ' - ' + end.format('D-MM-YYYY'));
					$('#opd_date_range').val(start.format('YYYY-MM-DD')+'S'+end.format('YYYY-MM-DD'));
				}

				$('#reportrange').daterangepicker({
					startDate: start,
					endDate: end,
					ranges: {
					   'Today': [moment(), moment()],
					   'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
					   'Last 7 Days': [moment().subtract(6, 'days'), moment()],
					   'Last 30 Days': [moment().subtract(29, 'days'), moment()],
					   'This Month': [moment().startOf('month'), moment().endOf('month')],
					   'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
					}
				}, cb);

				cb(start, end);
				
				$('#reportrange').on('apply.daterangepicker', function(ev, picker) {
				var date_first=picker.startDate.format('YYYY-MM-DD');
				var date_second=picker.endDate.format('YYYY-MM-DD');

				$('#opd_date_range').val(date_first+'S'+date_second);
			
				});
				
				$('#showreport').click( function()
				{
					var Insurance_id=$('#Insurance_id').val();
					var Get_Query="/Medical_backpanel/Org_Bills_Report/"+$('#opd_date_range').val()+"/"+Insurance_id;
					load_report_div(Get_Query,'show_report');
					//load_form_div(Get_Query,'show_report');
				});
				
				$('#showreport_xls').click( function()
				{
					var Insurance_id=$('#Insurance_id').val();
					var Get_Query="/Medical_backpanel/Org_Bills_Report/"+$('#opd_date_range').val()+"/"+Insurance_id+"/1";
					window.open(Get_Query, "_blank");
				});
	
			
				
		});
</script>
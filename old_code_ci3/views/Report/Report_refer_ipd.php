<section class="content-header">
    <h1>
        Report  
        <small>Panel</small>
    </h1>
</section>
<section class="content">
	<div class="row">
		<div class="col-md-3">
			<label>IPD Admit Date Range</label>
			<div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
				<i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;
				<span></span> <b class="caret"></b>
			</div>
			<input type="hidden" name="opd_date_range" id="opd_date_range"  /> 
		</div>
		<div class="col-md-3">
			<label>Refer</label>
			<select class="form-control select2" id="refer_id" name="refer_id" multiple="multiple" data-placeholder="Select a Doctors"  >
				<option value='0' >All</option>
				<?php 
				foreach($refer_master as $row)
				{ 
					echo '<option value='.$row->id.'  >'.$row->f_name.'</option>';
				}
				?>
			</select>
		</div>
        <div class="col-md-2">
			<label>IPD Status</label>
			<select class="form-control" id="ipd_status" name="ipd_status" >
				<option value='-1' >ALL</option>
				<option value='0' >Admit</option>
				<option value='1' >Discharge</option>
			</select>
		</div>
		<div class="col-md-3">
			<label> </label>
			<div class="form-group">
				<button type="button" class="btn btn-primary" id="showreport"  >Show</button>
				<button type="button" class="btn btn-primary" id="showreportexport"  >Export</button>
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
					var refer_id=$('#refer_id').val();
                    var ipd_status=$('#ipd_status').val();
					
					if(refer_id==null || refer_id=='')
					{
						refer_id="0";
					}else{
						refer_id=refer_id.toString().split(",").join("S");
					}
					
					
					var Get_Query="Report3/ipd_item_refer_reports_data/"+$('#opd_date_range').val()+
					"/"+refer_id+"/"+ipd_status;
					load_report_div(Get_Query,'show_report');
					
				});
				
				$('#showreportexport').click( function()
				{
					var refer_id=$('#refer_id').val();
                    var ipd_status=$('#ipd_status').val();
					
					if(refer_id==null || refer_id=='')
					{
						refer_id="0";
					}else{
						refer_id=refer_id.toString().split(",").join("S");
					}
					
					var Get_Query="Report3/ipd_item_refer_reports_data/"+$('#opd_date_range').val()+
					"/"+refer_id+"/"+ipd_status+"/1";
					
					
					window.open(Get_Query, "_blank");
					
				});
	
			
				
		});
</script>
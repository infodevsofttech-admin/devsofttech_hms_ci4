<section class="content-header">
    <h1>
        Report  
        <small>Panel</small>
    </h1>
</section>
<section class="content">
	<div class="row">
		<div class="col-md-2">
			<label>Search By Date</label>
			<select class="form-control" id="ipd_date" name="ipd_date" >
				<option value='0' >Admit</option>
				<option value='1' >Discharge</option>
			</select>
		</div>
		<div class="col-md-3">
			<label>Date Range</label>
			<div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
				<i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;
				<span></span> <b class="caret"></b>
			</div>
			<input type="hidden" name="opd_date_range" id="opd_date_range"  /> 
		</div>
		<div class="col-md-2">
			<label>Doctor Name</label>
			<select class="form-control select2" id="doc_name_id" name="doc_name_id" multiple="multiple"  >
				<option value='0'  >All Doctors</option>
				<?php 
				foreach($doclist as $row)
				{ 
					echo '<option value='.$row->id.'  >'.$row->p_fname.'</option>';
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
			<label>Type</label>
			<select class="form-control" id="ipd_type" name="ipd_type" >
			<option value='0' >ALL</option>
				<?php 
				foreach($ins_group as $row)
				{ 
					echo '<option value='.$row->id.'  >'.$row->tpa_group.'</option>';
				}
				echo '<option value="0" disabled >--------------------</option>';
				foreach($hc_insurance as $row)
				{ 
					echo '<option value='.($row->id*-1).'  >'.$row->short_name.'</option>';
				}

				?>
			</select>
		</div>
		<div class="col-md-12">
			<label> </label>
			<div class="form-group">
				<button type="button" class="btn btn-primary" id="showreport"  >Show</button>
				<button type="button" class="btn btn-primary" id="showreportexport"  >Export</button>
				<button type="button" class="btn btn-primary" id="showreportmedical"  >IPD Cr. Medical Amt Show</button>
				<button type="button" class="btn btn-primary" id="showreportprint"  >Invoice List Print</button>
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
					var doc_name_id=$('#doc_name_id').val();
					
					if(doc_name_id==null || doc_name_id=='')
					{
						doc_name_id="0";
					}else{
						doc_name_id=doc_name_id.toString().split(",").join("S");
					}
					
					
					var ipd_status=$('#ipd_status').val();
					var ipd_type=$('#ipd_type').val();
					var ipd_date=$('#ipd_date').val();
					
					var Get_Query="/Report3/ipd_discharge/"+$('#opd_date_range').val()+
					"/"+doc_name_id+ "/"+ipd_type+
					"/"+ipd_status+"/"+ipd_date;
					load_report_div(Get_Query,'show_report');
					
				});
				
				$('#showreportexport').click( function()
				{
					var doc_name_id=$('#doc_name_id').val();
					
					if(doc_name_id==null || doc_name_id=='')
					{
						doc_name_id="0";
					}else{
						doc_name_id=doc_name_id.toString().split(",").join("S");
					}
					
					var ipd_status=$('#ipd_status').val();
					var ipd_type=$('#ipd_type').val();
					var ipd_date=$('#ipd_date').val();
					
					var Get_Query="/Report3/ipd_discharge_details/"+$('#opd_date_range').val()+
					"/"+doc_name_id+ "/"+ipd_type+
					"/"+ipd_status+"/"+ipd_date+"/1";
					window.open(Get_Query, "_blank");
					//load_form_div(Get_Query,'show_report');
					
				});
				
				$('#showreportprint').click( function()
				{
					var doc_name_id=$('#doc_name_id').val();
					
					if(doc_name_id==null)
					{
						doc_name_id="0";
					}
					
					var ipd_status=$('#ipd_status').val();
					var ipd_type=$('#ipd_type').val();
					var ipd_date=$('#ipd_date').val();
					
					var Get_Query="/ipd2/ipd_invoice_print_list/"+$('#opd_date_range').val()+
					"/"+doc_name_id+ "/"+ipd_type+
					"/"+ipd_status+"/"+ipd_date+"/1";
					window.open(Get_Query, "_blank");
					
				});

				$('#showreportmedical').click( function()
				{
					var doc_name_id=$('#doc_name_id').val();
					
					if(doc_name_id==null)
					{
						doc_name_id="0";
					}
					
					var ipd_status=$('#ipd_status').val();
					var ipd_type=$('#ipd_type').val();
					var ipd_date=$('#ipd_date').val();
					
					var Get_Query="/Report3/ipd_discharge_medical/"+$('#opd_date_range').val()+
					"/"+doc_name_id+ "/"+ipd_type+
					"/"+ipd_status+"/"+ipd_date;
					load_report_div(Get_Query,'show_report');
					
				});
				
				
		});
</script>
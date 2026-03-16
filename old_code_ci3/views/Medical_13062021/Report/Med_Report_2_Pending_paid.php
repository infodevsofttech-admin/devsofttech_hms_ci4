<section class="content-header">
    <h1>
        Old Invoice Payment Report 
        <small>Panel</small>
    </h1>
</section>
<section class="content">
	<div class="row">
		<div class="col-md-3">
			<label>Date Range</label>
			<input class="form-control pull-right datepicker input-sm" name="datepicker_med_date" id="datepicker_med_date" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask value="<?=date('d/m/Y') ?>"  />
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
			
				$('#showreport').click( function()
				{
					var Sdate = $("#datepicker_med_date").datepicker("getDate");
					var strDateTime =  Sdate.getFullYear() +'-'+ (Sdate.getMonth()+1) + "-" + Sdate.getDate() ;
										
					var Get_Query="/Medical_Report/Report_2_Bal_pending_payment/"+strDateTime+"/0";
					load_report_div(Get_Query,'show_report');
				});
				
				$('#showreport_xls').click( function()
				{
					var Sdate = $("#datepicker_med_date").datepicker("getDate");
					var strDateTime =  Sdate.getFullYear() +'-'+ (Sdate.getMonth()+1) + "-" + Sdate.getDate() ;
			
					var Get_Query="/Medical_Report/Report_2_Bal_pending_payment/"+strDateTime+"/1";
					window.open(Get_Query, "_blank");
				});
	
			
				
		});
</script>
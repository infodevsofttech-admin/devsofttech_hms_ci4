<section class="content-header">
    <h1>
        Company Wise Medicine Sale / Purchase Report  
    </h1>
</section>
<section class="content">
	<div class="row">
		<div class="col-md-3">
			<label>Date Range</label>
			<div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
				<i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;
				<span></span> <b class="caret"></b>
				</div>
				<input type="hidden" name="opd_date_range" id="opd_date_range"  /> 
		</div>
        <div class="col-md-3">
            <label for="comp_id" class="control-label">Company Master</label>
            <div class="form-group">
                <select name="input_company_name" id="input_company_name" class="form-control">
                    <?php 
                    foreach($med_company as $company_master)
                    {
                        echo '<option value="'.$company_master->id.'"  >'.$company_master->company_name.'</option>';
                    } 
                    ?>
                </select>
            </div>
        </div>
		<div class="col-md-3">
			<label> </label>
			<div class="form-group">
				<button type="button" class="btn btn-primary" id="showreport"  >Show Sale</button>
				<button type="button" class="btn btn-primary" id="showreport_xls"  >Excel Sale</button>
			</div>
		</div>
        <div class="col-md-3">
			<label> </label>
			<div class="form-group">
				<button type="button" class="btn btn-primary" id="showPurreport"  >Show Purchase</button>
				<button type="button" class="btn btn-primary" id="showPurreport_xls"  >Excel Purchase</button>
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
                    var company_id=$('#input_company_name').val();

					var Get_Query="/Medical_Report/Report_company_med_sale_data/"+$('#opd_date_range').val()+"/"+company_id+"/0";
					load_report_div(Get_Query,'show_report');
				});
				
				$('#showreport_xls').click( function()
				{
                    var company_id=$('#input_company_name').val();

					var Get_Query="/Medical_Report/Report_company_med_sale_data/"+$('#opd_date_range').val()+"/"+company_id+"/1";
					window.open(Get_Query, "_blank");
				});

                $('#showPurreport').click( function()
				{
                    var company_id=$('#input_company_name').val();

					var Get_Query="/Medical_Report/Report_company_med_purchase_data/"+$('#opd_date_range').val()+"/"+company_id+"/0";
					load_report_div(Get_Query,'show_report');
				});
				
				$('#showPurreport_xls').click( function()
				{
                    var company_id=$('#input_company_name').val();
                    
					var Get_Query="/Medical_Report/Report_company_med_purchase_data/"+$('#opd_date_range').val()+"/"+company_id+"/1";
					window.open(Get_Query, "_blank");
				});
	
			
				
		});
</script>
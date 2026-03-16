<section class="content-header">
<?php echo form_open('Patient/search', array('role'=>'form','class'=>'form2')); ?>
        <div class="col-md-3">
            <div class="form-group">
            <label>Supplier</label>
                <select class="form-control input-sm" id="input_supplier" name="input_supplier"  >	
                    <?php 
                    foreach($supplier_data as $row)
                    { 
                        echo '<option value='.$row->sid.' >'.$row->name_supplier.'</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <label>Date Range</label>
            <div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                <i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;
                <span></span> <b class="caret"></b>
                </div>
                <input type="hidden" name="opd_date_range" id="opd_date_range"  /> 
        </div>
        <div class="col-md-6">
            <label>Search Items</label>
            <div class="input-group input-group-sm">
                    <input class="form-control" type="text" id="txtsearch" name="txtsearch" placeholder="Like Item Name,Supplier Name"   >
                    <span class="input-group-btn">
                        <button type="submit" class="btn btn-info btn-flat">Search Items</button>
                    </span>
                    <span class="input-group-btn">
                        <button type="button" id="btn_excel" class="btn btn-info btn-flat">Search Items Excel</button>
                    </span>
            </div>
        </div>
<?php echo form_close(); ?>
</section>
<div class="searchresult" id="searchresult"></div>
 <script>
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
				
            $('#showreport').click(function(){
                var Get_Query="/Medical_Report/Report_1_data/"+$('#opd_date_range').val()+"/1/0";
                load_report_div(Get_Query,'show_report');
            });
				
            $('#showreport_xls').click( function()
            {
                var Get_Query="/Medical_Report/Report_1_data/"+$('#opd_date_range').val()+"/1/1";
                window.open(Get_Query, "_blank");
                
            });
	



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
		
	 });
 
			
</script>
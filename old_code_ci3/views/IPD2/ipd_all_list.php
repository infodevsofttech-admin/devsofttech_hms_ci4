<section class="content-header">
    <h1> IPD List </h1>
</section>
<section class="content">
		<div class="box">
            <div class="box-header">
				<div class="col-xs-4">
                  <div class="input-group">
                        <span class="input-group-addon">
                          <input type="checkbox" id="chk_date" name="chk_date" >
                        </span>
						<div id="reportrange" class="pull-right" 
						style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
						<i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;
						<span></span> <b class="caret"></b>
						</div>
						
                  </div>
                </div>
				<div class="col-xs-2">
					<select class="form-control input-sm" id="ipd_admit_type" name="ipd_admit_type">
						<option value="-1">All</option>
						<option value="0">Admit</option>
						<option value="1">Discharge</option>
					</select>
				</div>
				
            </div>
            <div class="box-body">
				
				<table class="table table-bordered table-striped TableData" id="employee-grid" width="100%">
					<thead>
					<tr>
						<th>IPD Code</th>
						<th>Patient Code</th>
						<th>Name</th>
						<th>Type</th>
						<th>Claim No</th>
						<th>Doctor</th>
						<th>Status</th>
						<th>Registration</th>
						<th>Discharge</th>
						<th>Dis. Status</th>
					</tr>
					</thead>
					<thead>
					<tr>
						<td><input type="text" data-column="0" ></td>
						<td><input type="text" data-column="1" ></td>
						<td><input type="text" data-column="2" ></td>
						<td><input type="text" data-column="3" ></td>
						<td><input type="text" data-column="4" ></td>
						<td><input type="text" data-column="5" ></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
					</thead>
					
				<tbody></tbody>
				
			</table>
			</div>
			<div class="box-footer" id="show_msg">
				
			</div>
		</div>
</section>
<!-- /.content -->
<script type="text/javascript" language="javascript" >
			$(document).ready(function() {
				var start = moment();
				var end = moment();
				
				$("#chk_date").on("click",function() {
					if(this.checked) {
						alert('Data Search in Date Range');
						if ($("#chk_date").is(":checked")) {
							var cho_admint_type=$("#ipd_admit_type").val();
							var date_first=start.format('YYYY-MM-DD');
							var date_second=end.format('YYYY-MM-DD');
						
							dataTable.columns(7).search(date_first+'/'+date_second+'/'+cho_admint_type).draw();
						}	
					}else{
						alert('Data Search in All Data');
						dataTable.columns(7).search('').draw();
					}
				});
				
				var dataTable = $('#employee-grid').DataTable( {
					"order": [[ 0, "desc" ]],
					"processing": true,
					"serverSide": true,
					"ajax":{
						url :"IpdNew/getIpdTable", // json datasource
						type: "post",  // method  , by default get
						data: {
						'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>',
						},
						error: function(){  // error handling
							$(".employee-grid-error").html("No data found in the server");
							$("#show_msg").html('<tbody class="employee-grid-error"><tr><th colspan="3">No data found in the server</th></tr></tbody>');
						}
					},
					columnDefs: [
						{
							targets: 0,
							render: function ( data, type, row, meta ) {
								if(type === 'display'){
									url_link = "javascript:load_form('/IpdNew/ipd_panel/"+ encodeURIComponent(data) + "/1');" ;
									udata = '<a href="' + url_link + '">' + data + '</a>';
								}

								return udata;
							}
						}
					]        
				} );
				
				$("#employee-grid_filter").css("display","none");  // hiding global search box
				
				//$('.search-input-text').on( 'keyup click', function () {   // for text boxes
				//	var i =$(this).attr('data-column');  // getting column index
				//	var v =$(this).val();  // getting search input value
				//	dataTable.columns(i).search(v).draw();
				//} );

				$( ".search-input-select" ).change(function() {
				  var i =$(this).attr('data-column');  
					var v =$(this).val();
					dataTable.columns(i).search(v).draw();
				});
				
				$('input[type=text').on('input', function(){
					var i =$(this).attr('data-column');  
					var v =$(this).val(); 
					dataTable.columns(i).search(v).draw();
				});
				
				function cb(start, end) {
					$('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
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
					start=picker.startDate;
					end=picker.endDate;
					

					if ($("#chk_date").is(":checked")) {
						var cho_admint_type=$("#ipd_admit_type").val();
						dataTable.columns(7).search(date_first+'/'+date_second+'/'+cho_admint_type).draw();
					}
				});
			
			});
				
				
		
		</script>
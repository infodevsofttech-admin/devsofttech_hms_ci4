<section class="content-header">
    <h1>
        OPD List 
        <small>List</small>
    </h1>
</section>
<!-- Main content -->
    <section class="content">
	<div class="box">
		<div class="box-header">
			<div class="col-md-4">
			<label>OPD Date Range</label>
				<div id="reportrange" class="pull-right" style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
				<i class="glyphicon glyphicon-calendar fa fa-calendar"></i>&nbsp;
				<span></span> <b class="caret"></b>
				</div>
			</div>
		</div>
		<div class="box-body">
			<table class="table table-bordered table-striped TableData" id="employee-grid" width="100%">
				<thead>
					<tr>
						<th>OPD Code</th>
						<th>Name</th>
						<th>Patient Code</th>
						<th>OPD Date</th>
						<th>Doc. Name</th>
						<th>Type</th>
						<th>PayMode</th>
						<th>Fee</th>
					</tr>
					</thead>
					<thead>
					<tr>
						<td><input type="text" data-column="0" ></td>
						<td><input type="text" data-column="1" ></td>
						<td><input type="text" data-column="2" ></td>
						<td></td>
						<td><input type="text" data-column="3"  class="search-input-text"></td>
						<td>
							<select data-column="4"  class="search-input-select">
								<option value="">All</option>
								<option value="1">Direct</option>
								<option value="2">Org.</option>
							</select>
						</td>
						<td>
							<select data-column="5"  class="search-input-select">
							<option value="">All</option>
							<?php 
								foreach($pay_mode as $row)
								{ 
									echo '<option value="'.$row->id.'">'.$row->mode_desc.'</option>';
								}
							?>
							</select>
						</td>
						<td></td>
					</tr>
					</thead>
				<tbody></tbody>
				<tfoot>
					<tr>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<th>Total:</th> 
					<th></th>
				  </tr>
				</tfoot>
			</table>
		</div>
	</div>
	</section>	
<!-- /.content -->
<script type="text/javascript" language="javascript" >
			$(document).ready(function() {
				var start = moment();
				var end = moment();
				
				var dataTable = $('#employee-grid').DataTable( {
					"order": [[ 0, "desc" ]],
					"processing": true,
					"serverSide": true,
					'footerCallback': function( tfoot, data, start, end, display ) {    
						  var response = this.api().ajax.json();
						  if(response){
							 var $th = $(tfoot).find('th');
							 $th.eq(6).html(response['foot_t_sum']);
						  }
					   } ,
					"ajax":{
						url :"data/getTable", // json datasource
						type: "post",  // method  , by default get
						error: function(){  // error handling
							$(".employee-grid-error").html("");
							$("#employee-grid").append('<tbody class="employee-grid-error"><tr><th colspan="3">No data found in the server</th></tr></tbody>');
							$("#employee-grid_processing").css("display","none");
						}
					}
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

				dataTable.columns(6).search(date_first+'/'+date_second).draw();
			
				});
				
				
				
						
		} );
		
		
		
		</script>
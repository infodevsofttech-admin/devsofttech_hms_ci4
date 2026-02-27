<div class="col-md-12">
		<div class="box">
            <div class="box-header">
              <h3 class="box-title">Stock Drug</h3>
            </div>
            <div class="box-body">
				<table class="table table-bordered table-striped TableData" id="employee-grid" width="100%">
				<thead>
					<tr>
						<th>Item Code</th>
						<th>Item Name</th>
						<th>Dosage</th>
						<th>Formulation</th>
						<th>Therapeutic</th>
						<th>subtherapeutic</th>
						<th>MRF Name</th>
						<th>MRP</th>
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
						<td><input type="text" data-column="6" ></td>
						<td></td>
					</tr>
					</thead>
				<tbody></tbody>
				
			</table>
			</div>
			<div class="box-footer">
				
			</div>
		</div>
	</div>
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
						url :"Medical/getDrugTable", // json datasource
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
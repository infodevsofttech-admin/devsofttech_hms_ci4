<div class="col-md-12">
		<div class="box">
            <div class="box-header">
              <h3 class="box-title">Payment Refund Request</h3>
            </div>
            <div class="box-body">
				<table class="table table-bordered  table-condensed TableData" id="employee-grid" width="100%">
					<thead>
					<tr>
						<th>#</th>
						<th>Org. Case No.</th>
						<th>Patient Name</th>
						<th>Request Date</th>
                        <th>Amount</th>
                        <th>Status</th>
					</tr>
					</thead>
					<thead>
					<tr>
						<td><input type="text" data-column="0" ></td>
						<td><input type="text" data-column="1" ></td>
                        <td><input type="text" data-column="2" ></td>
                        <td></td>
						<td></td>
						<td></td>
					</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
			<div class="box-footer">
				<button type="submit" class="btn btn-primary">Save</button>
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
					"ajax":{
						url :"Invoice/getRequestTable", // json datasource
						dataType: "json",
						type: "post",
						data: {
						'<?php echo $this->security->get_csrf_token_name(); ?>' : '<?php echo $this->security->get_csrf_hash(); ?>',
							
						},  // method  , by default get
						error: function(){  // error handling
							$(".employee-grid-error").html("");
							$("#employee-grid").append('<tbody class="employee-grid-error"><tr><th colspan="3">No data found in the server</th></tr></tbody>');
							$("#employee-grid_processing").css("display","none");
						}
					},
					columnDefs: [
						{
							targets: 0,
							render: function ( data, type, row, meta ) {
								if(type === 'display'){
									url_link = "javascript:load_form('/Invoice/payment_form/"+ encodeURIComponent(data) + "');" ;
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
				
				
			
				});
				
				
		
		</script>
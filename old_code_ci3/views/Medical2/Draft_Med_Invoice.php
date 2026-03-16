<div class="col-md-12">
		<div class="box">
            <div class="box-header">
              <h3 class="box-title">Draft Invoice</h3>
            </div>
            <div class="box-body">
				<table class="table table-bordered table-striped TableData" id="employee-grid" width="100%">
				<thead>
					<tr>
						<th>Invoice No.</th>
						<th>Customers</th>
						<th>PCode</th>
						<th>Date</th>
						<th>Net Amount</th>
						<th>Received</th>
						<th>Balance</th>
						<th></th>
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
						<td></td>
						<td></td>
					</tr>
					</thead>
				<tbody></tbody>
				
			</table>
			</div>
			<div class="box-footer">
				<a class="btn btn-app" href="javascript:load_form_div('/Medical/search_customer','maindiv');">
					<i class="fa fa-shopping-cart"></i>Serach Customer for Counter Sale
				</a>
				<a class="btn btn-app" href="javascript:load_form_div('/Medical/Invoice_counter_new/0/0/0','maindiv');">
					<i class="fa fa-shopping-cart"></i>New Counter Sale
				</a>
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
							 $th.eq(5).html(response['foot_t_sum']);
						  }
					   } ,
					"ajax":{
						url :"Medical/getInvoiceTable", // json datasource
						type: "post",  // method  , by default get
						error: function(){  // error handling
							$(".employee-grid-error").html("");
							$("#employee-grid").append('<tbody class="employee-grid-error"><tr><th colspan="5">No data found in the server</th></tr></tbody>');
							$("#employee-grid_processing").css("display","none");
						}
					},
					"columnDefs": [ 
					{
						targets: 0,
						render: function ( data, type, row, meta ) {
							if(type === 'display'){
								if((row[8]>0 && row[10]>0) || (row[9]>0 && row[9]>11))
								{
									url_link = "/Medical/invoice_print/"+ encodeURIComponent(row[7]) + " " ;
									udata = '<a href="' + url_link + '" target="_blank">' + data + '</a>';
								}else{
									url_link = "javascript:load_form_div('/Medical/Invoice_med_show/"+ encodeURIComponent(row[7]) + "','maindiv');" ;
									udata = '<a href="' + url_link + '">' + data + '</a>';
								}
				
							}
							return udata;
						}
					}
					]
					
				} );
	
				$('#employee-grid tbody').on( 'click', 'button', function () {
					var data = dataTable.row( $(this).parents('tr') ).data();
					load_form_div('/Medical/Invoice_med_show/'+data[7],'maindiv');
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
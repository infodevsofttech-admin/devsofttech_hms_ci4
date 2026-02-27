<div class="row">
	<div class="col-md-12">
		<div class="box">
		<div class="box-header">
		  <h3 class="box-title">Report List</h3>
		</div>
		<!-- /.box-header -->
		<div class="box-body" style="height:500px;overflow-y:auto;">
			<table class="table table-bordered table-striped TableData" id="employee-grid" width="100%">
					<thead>
					<tr>
						<th>Invoice No.</th>
						<th>Customers</th>
						<th>Code</th>
						<th>Test Name</th>
						<th>Test Type</th>
						<th>Balance</th>
						<th></th>
					</tr>
					</thead>
					<thead>
					<tr>
						<td><input type="text" data-column="0" ></td>
						<td><input type="text" data-column="1" ></td>
						<td><input type="text" data-column="2" ></td>
						<td><input type="text" data-column="3" ></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
					</thead>
				<tbody></tbody>
			</table>
		</div>
		<!-- /.box-body -->
		</div>
	</div>
</div>
<div class="modal modal-wide fade" id="tallModal_1_1" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="tallModal_4_1Label"></h4>
      </div>
      <div class="modal-body">
        <div class="row">
			<div class="tallModal_4-bodyc" id="tallModal_4_1-bodyc">
			</div>
			</div>
		</div>
      </div>
   </div>
</div>
<script>
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
					url :"Lab_Admin/getLabTab4Table/"+$('#lab_type').val(), // json datasource
					type: "post",  // method  , by default get
					error: function(){  // error handling
						$(".employee-grid-error").html("");
						$("#employee-grid").append('<tbody class="employee-grid-error"><tr><th colspan="5">No data found in the server</th></tr></tbody>');
						$("#employee-grid_processing").css("display","none");
					}
				},
				"columnDefs": [ 
				{
					targets: 6,
					render: function ( data, type, row, meta ) {
						if(type === 'display'){
							content='<button  type="button" class="btn btn-primary" onclick="report_compile('+row[6]+','+row[7]+')" >Compile</button>';
							content+='<button  type="button" class="btn btn-primary" data-toggle="modal" ';
							content+=' data-target="#tallModal_4" data-testid="'+row[6]+'" ';
							content+=' data-testname="'+row[1]+'"  ';
							content+=' data-labtype="'+row[7]+'" ';
							content+=' data-etype="1" >Edit</button> ';
							content+=' <button  type="button" class="btn btn-primary" data-toggle="modal" ';
							content+=' data-target="#tallModal_4" data-testid="'+row[6]+'" ';
							content+=' data-testname="'+row[1]+'"  ';
							content+=' data-labtype="'+row[7]+'" ';
							content+=' data-etype="0" >Report Show</button> ';
						
						}
						return content;
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


	function update_request(test_id)
	{
			var lab_type=$('#lab_type').val();
		
			$.post('/index.php/Lab_Admin/lab_tab_1_process/'+test_id+'/'+lab_type,{ "test_id": test_id }, function(data){
			if(data>0)
			{
				alert('Data Saved');
			}
			load_form_div('/Lab_Admin/lab_tab_1/'+lab_type,'tab_1');
			});
	}
	
</script>
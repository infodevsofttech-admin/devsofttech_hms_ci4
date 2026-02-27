<div class="row">
	<div class="col-md-12">
		<div class="box">
		<div class="box-header">
		  <h3 class="box-title">Report List</h3>
		</div>
		<!-- /.box-header -->
		<div class="box-body" style="height:500px;overflow-y:auto;">
			<table class="table table-bordered table-striped TableData" id="data-grid-2" width="100%">
					<thead>
					<tr>
						<th>Invoice No.</th>
						<th>Customers</th>
						<th>Code</th>
						<th>Age</th>
						<th>Date</th>
						<th>Test Name</th>
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
		<!-- /.box-body -->
		</div>
	</div>
</div>
<div class="modal modal-wide fade" id="tallModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="testentryLabel">Test Name</h4>
      </div>
      <div class="modal-body">
        <div class="row">
			<div class="testentry-bodyc" id="testentry-bodyc">
					
				</div>
			</div>
		</div>
      </div>
      
    </div>
  </div>

<script type="text/javascript" language="javascript" >
			$(document).ready(function() {
				var start = moment();
				var end = moment();

				var dataTable = $('#data-grid-2').DataTable( {
					"order": [[ 0, "desc" ]],
					"processing": true,
					"serverSide": true,
					"ajax":{
						url :"Lab_Admin/getLabTab2Table/"+$('#lab_type').val(), // json datasource
						type: "post",  // method  , by default get
						error: function(){  // error handling
							$(".data-grid-2-error").html("");
							$("#data-grid-2").append('<tbody class="employee-grid-error"><tr><th colspan="5">No data found in the server</th></tr></tbody>');
							$("#data-grid-2_processing").css("display","none");
						}
					},"columnDefs": [ 
					{
						targets: 5,
						render: function ( data, type, row, meta ) {
						var content='';
						if(type === 'display'){
							var str = row[6];
							var temp_row = new Array();
							temp_row = str.split("#");
							for(i=0;i<temp_row.length;i++)
							{
								var row_array=new Array();
								row_array=temp_row[i].split(";");
								
								content+='<p>';
								
								content+='<button  type="button" class="btn btn-primary" data-toggle="modal" data-target="#tallModal" ';
								content+='data-testid="'+row_array[2]+'" data-testname="'+row_array[0]+'" data-etype="1">'+row_array[0]+'</button> ';
								
								content+='<button  type="button" 	class="btn btn-primary" data-toggle="modal" ';
								content+='data-target="#tallModal"  data-repoid="'+row_array[2]+'"  data-testid="'+row_array[3]+'" ';
								content+='data-testname="'+row_array[0]+'" data-etype="3">Upload Files</button> ' ;
								
								content+='<button  type="button" 	class="btn btn-primary" data-toggle="modal"  ';
								content+=' data-target="#tallModal" data-repoid="'+row_array[2]+'" data-testid="'+row_array[3]+'"' ;
								content+=' data-testname="'+row_array[0]+'" data-etype="2">Scan</button>  ';
								
								content+= '<button  type="button" 	class="btn btn-primary" data-toggle="modal" ';
								content+=' data-target="#tallModal" data-repoid="'+row_array[2]+'" data-testid="'+row_array[3]+'"'; 
								content+=' data-testname="'+row_array[0]+'" data-etype="4">Show Files</button>';
								
								content+='</p>';
							}
						}
						
						return content;
						}
						
					}
					]
					
				} );
			
				
				$("#data-grid-2_filter").css("display","none");  // hiding global search box
				
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
			
function update_request(req_id)
	{
			$.post('/index.php/Lab_Admin/lab_tab_2_process/'+req_id,{ "test_id": req_id }, function(data){
			$('#testentry_div').html(data);
			});
	}

	$('#tallModal').on('shown.bs.modal', function (event) {
		$('.testentry-bodyc').html('');
		
		var lab_type=$('#lab_type').val();
		
		var height = $(window).height() - 50;
		$(this).find(".modal-body").css("max-height", height);

		var button = $(event.relatedTarget);
		// Button that triggered the modal
		var testid = button.data('testid');
		var testname = button.data('testname');
		var etype = button.data('etype');

		$('#testentryLabel').html(testname);
		
		if(etype=='1')
		{
			$.post('/index.php/Lab_Admin/lab_tab_2_process/'+testid,{ "test_id": testid }, function(data){
			$('#testentry-bodyc').html(data);
			});
		}
		
		if(etype=='2')
		{
			var repoid = button.data('repoid');
			$.post('/index.php/Lab_Admin/lab_file_scan/'+repoid+'/'+testid,{ "test_id": testid }, function(data){
			$('#testentry-bodyc').html(data);
			});
		}
		
		if(etype=='3')
		{
			var repoid = button.data('repoid');
			$.post('/index.php/Lab_Admin/lab_file_upload/'+repoid+'/'+testid,{ "test_id": testid }, function(data){
			$('#testentry-bodyc').html(data);
			});
		}
		
		if(etype=='4')
		{
			var repoid = button.data('repoid');
			$.post('/index.php/Lab_Admin/lab_file_list/'+repoid+'/'+testid,{ "test_id": testid }, function(data){
			$('#testentry-bodyc').html(data);
			});
		}
		
	});
	
	$('#tallModal').on('hidden.bs.modal', function () {
		$('.testentry-bodyc').html('');
		$('#testentryLabel').html('');
		Webcam.reset();
		
	});
				
				
				
			
				
				

	
	</script>
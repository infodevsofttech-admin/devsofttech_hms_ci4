<div class="row">
	<div class="col-md-12">
		<div class="box">
		<div class="box-header">
		  <h3 class="box-title">Report List</h3>
		</div>
		<!-- /.box-header -->
		<div class="box-body" style="height:500px;overflow-y:auto;">
		  <table id="datashow4" class="table table-bordered table-striped TableData">
			<thead>
			<tr>
			  <th>Invoice No./Date</th>
			  <th>Person Name</th>
			  <th>Test Name</th>
			  <th>Test Type</th>
			  <th></th>
			 </tr>
			</thead>
			<tbody>
			<?php for ($i = 0; $i < count($labreport_preprocess); ++$i) { ?>
			<tr>
				<td><?=$labreport_preprocess[$i]->invoice_code ?>
				<br/>
				<?=$labreport_preprocess[$i]->inv_date ?>
				</td>
				<td><?=$labreport_preprocess[$i]->inv_name ?></td>
				<td><?=$labreport_preprocess[$i]->Test_List ?></td>
				<td><?=$labreport_preprocess[$i]->item_type_desc ?></td>
				<td>
				<button  type="button" class="btn btn-primary" onclick="report_compile(<?=$labreport_preprocess[$i]->inv_id ?>,<?=$labreport_preprocess[$i]->itype_id ?>)" >Compile</button>
				<button  type="button" class="btn btn-primary" data-toggle="modal"
				data-target="#tallModal_4" data-testid="<?=$labreport_preprocess[$i]->inv_id ?>" 
				data-testname="<?=$labreport_preprocess[$i]->inv_name ?>"  
				data-labtype="<?=$labreport_preprocess[$i]->itype_id ?>"
				data-etype="1" >Edit</button>
				<button  type="button" class="btn btn-primary" data-toggle="modal"
				data-target="#tallModal_4" data-testid="<?=$labreport_preprocess[$i]->inv_id ?>" 
				data-testname="<?=$labreport_preprocess[$i]->inv_name ?>"  
				data-labtype="<?=$labreport_preprocess[$i]->itype_id ?>" 
				data-etype="0" >Report Show</button>
				<button  type="button" class="btn btn-primary" onclick="report_remove(<?=$labreport_preprocess[$i]->inv_id ?>,<?=$labreport_preprocess[$i]->itype_id ?>)" >Remove from List</button>
				</td>
			</tr>
			<?php } ?>
			</tbody>
			<tfoot>
			<tr>
			  <th>Invoice No./Date</th>
			  <th>Person Name</th>
			  <th>Test Name</th>
			  <th>Charge Type</th>
			  <th></th>
			 </tr>
			</tfoot>
		  </table>
		</div>
		<!-- /.box-body -->
		</div>
	</div>
</div>
<div class="modal modal-wide fade" id="tallModal_4" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="tallModal_4Label"></h4>
      </div>
      <div class="modal-body">
        <div class="row">
			<div class="tallModal_4-bodyc" id="tallModal_4-bodyc">
			</div>
			</div>
		</div>
      </div>
   </div>
</div>
<script>

	$('#datashow4').dataTable();
	
	function report_show(inv_id)
	{
		load_report_div(Get_Query,'show_report_pdf');
	}
	
	function report_compile(inv_id,lab_type)
	{
		$.post('/index.php/Lab_Admin/report_compile/'+inv_id+'/'+lab_type,{ "inv_id": inv_id }, function(data){
			alert(data);
			});
	}
	
	function report_remove(inv_id,lab_type)
	{
		$.post('/index.php/Lab_Admin/report_remove/'+inv_id+'/'+lab_type,{ "inv_id": inv_id }, function(data){
			alert(data);
			});
	}
	
	$('#tallModal_4').on('shown.bs.modal', function (event) {
		$('.tallModal_4-bodyc').html('');
	
		var button = $(event.relatedTarget);
		// Button that triggered the modal
		var testid = button.data('testid');
		var testname = button.data('testname');
		var etype = button.data('etype');
		var labtype = button.data('labtype');
		
		if(etype=='1')
		{
			$.post('/index.php/Lab_Admin/show_print_final_edit/'+testid+'/'+labtype+'/0',{ "test_id": testid }, function(data){
			$('#tallModal_4Label').html(testname);
			$('#tallModal_4-bodyc').html(data);
			});
		}else{
			var Get_Query='/index.php/Lab_Admin/show_print_final_edit/'+testid+'/'+labtype+'/1';
			$('#tallModal_4Label').html(testname);
			load_report_div(Get_Query,'tallModal_4-bodyc');
		}
		
	});
	
	$('#tallModal_4').on('hidden.bs.modal', function () {
		$('#tallModal_4-bodyc').html('');
		$('#tallModal_4Label').html('');
	});
	
</script>
<section class="content-header">
    <h1>
        IPD Charges 
        <small><button onclick="load_form_div('/Item_IPD/search_itemtype','maindiv');" type="button" class="btn btn-primary">Go for Charges Group</button></small>
    </h1>
</section>
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
 <section class="content">
      <div class="row">
		<div class="col-xs-4">
				<div class="form-group">
				<label>Select Charge Type</label>
            <select class="form-control" id="itype_idv" name="itype_idv"  >					
					<?php 
						foreach($labitemtype as $row)
						{ 
							echo '<option value='.$row->itype_id.'>'.$row->group_desc.'</option>';
						}
					?>
					</select>
				</div>
		</div>
        <div class="col-md-2">
          <div class="form-group">
              <button onclick="load_form_div('/Item_IPD/AddRecord','maindiv');" type="button" class="btn btn-primary">Add New Charge</button>
          </div>
        </div>
    </div>
    <div class="row">
<div class="box">
<div class="box-header">
  <h3 class="box-title">Result
  </h3>
  
</div>
<!-- /.box-header -->
<div class="box-body">
<div id="search_div">
	<a href="/Item_IPD/search_print/<?=$data[0]->itype ?>" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
	<a href="/Item_IPD/item_all_print" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print ALL</a>
  <br/><br/>
  <table id="datashow1" class="table table-bordered table-striped TableData">
    <thead>
    <tr>
      <th>Group</th>
      <th>Charge Name</th>
      <th>Amount</th>
      <th></th>
    </tr>
    </thead>
    <tbody>
    <?php for ($i = 0; $i < count($data); ++$i) { ?>
    <tr>
		  <td><?=$data[$i]->group_desc ?></td>
		  <td><?=$data[$i]->idesc ?></td>
		  <td><?=$data[$i]->amount ?></td>
      <td><button  type="button" class="btn btn-primary"  data-toggle="modal" data-target="#tallModal_item" 
	  data-testid="<?=$data[$i]->id ?>" data-testname="<?=$data[$i]->group_desc ?>">Edit It....</button></td>
    </tr>
    <?php } ?>
    </tbody>
    <tfoot>
    <tr>
      <th>Group</th>
      <th>Charge Name</th>
      <th>Amount</th>
      <th></th>
    </tr>
    </tfoot>
  </table>
 
  </div>
</div>
<!-- /.box-body -->
</div>
</div>
</section>
<div class="modal modal-wide fade" id="tallModal_item" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="tallModal_itemLabel"></h4>
      </div>
      <div class="modal-body">
        <div class="row">
      			<div class="tallModal_item-bodyc" id="tallModal_item-bodyc">
      			</div>
		    </div>
		  </div>
    </div>
  </div>
 </div>

 <script type="text/javascript">
    $(document).ready(function () {
        $('#datashow1').dataTable();
		
    		$("#itype_idv").change(function(){
    			load_form_div('/Item_IPD/search_adv/'+$('#itype_idv').val(),'search_div');
    			});
    		
        });
	
      	$('#tallModal_item').on('shown.bs.modal', function (event) {
      		$('.tallModal_item-bodyc').html('');
      	
      		var button = $(event.relatedTarget);
      		// Button that triggered the modal
      		var testid = button.data('testid');
      		var testname = button.data('testname');
          var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
          

      		$('#tallModal_itemLabel').html(testname);

      		$.post('/index.php/Item_IPD/item_record/'+testid,{ "test_id": testid,'<?=$this->security->get_csrf_token_name()?>':csrf_value }, function(data){
      			$('#tallModal_item-bodyc').html(data);
      			});
      			
      		
      	
      	});
	
      	$('#tallModal_3').on('hidden.bs.modal', function () {
      		$('#tallModal_3-bodyc').html('');
      		$('#tallModal_3Label').html('');
      	});


</script>
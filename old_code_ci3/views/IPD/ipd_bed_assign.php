<div class="row">
	<div class="box-body">
	  <table id="bed_assign_list" class="table table-bordered table-striped TableData">
		<thead>
		<tr>
		  <th>Bed No.</th>
		  <th>Description</th>
		  <th>Date</th>
		  <th>Vacant Date</th>
		</tr>
		</thead>
		<tbody>
		<?php for ($i = 0; $i < count($ipd_bed_assign); ++$i) { ?>
		<tr>
		  <td><?=$ipd_bed_assign[$i]->bed_no ?></td>
		  <td><?=$ipd_bed_assign[$i]->room_name ?></td>
		  <td><?=$ipd_bed_assign[$i]->Fdate ?></td>
		  <td><?=$ipd_bed_assign[$i]->TDate ?></td>
		</tr>
		<?php } ?>
		</tbody>
	  </table>
		<hr />
		<button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#Modal_bed_assign">
			Bed Assign
		</button>
	</div>
</div>
<!-- Modal -->
<div class="modal fade" id="Modal_bed_assign" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="Modal_bed_assignLabel">Assign /Change Bed</h4>
      </div>
      <div class="modal-body">
        <div class="row">
			<div class="col-md-6" >
			<div class="form-group">
				<label>Bed No.</label>
			<div  id='Modal_bed_assign-bodyc'>
				<select class="form-control" name="room_list" id="room_list" >
				<?php 
					foreach($ipd_bed_list as $row)
					{ 
						echo '<option value="'.$row->id.'">'.$row->Bed_Desc.'</option>';
					}
				?>   
				</select>
				<input type="hidden" id="start_datetime"  value="<?=date('Y-m-d H:m:s')   ?>" />
				<input type="hidden" id="end_datetime"  value="<?=date('Y-m-d H:m:s')   ?>" />
			</div>
			</div>
			</div>
			<div class="col-md-6">
				<div class="form-group">
					<label>Date and time range:</label>
					<div class="input-group">
					  <div class="input-group-addon">
						<i class="fa fa-clock-o"></i>
					  </div>
					  <input class="form-control pull-right reservationtime" id="reservationtimerange" name="reservationtimerange" type="text">
					</div>
					<!-- /.input group -->
				  </div>
			 </div>
			<div class="col-md-3">
				<button type="button" class="btn btn-primary" id="btn_add_room" onclick="add_bed_room()" data-dismiss="modal">Assign/Change Room</button>
			</div>
		</div>
      </div>
    </div>
  </div>
</div>
<script>
	
	
	$('#Modal_bed_assign').on('shown.bs.modal', function () {
		load_form_div('/ipd/ipd_bed_list','Modal_bed_assign-bodyc');
	});
	
	$('#reservationtimerange').on('apply.daterangepicker', function(ev, picker) {
				var date_first=picker.startDate.format('YYYY-MM-DD HH:MM:SS');
				var date_second=picker.endDate.format('YYYY-MM-DD HH:MM:SS');

				$('#start_datetime').val(date_first);
				$('#end_datetime').val(date_second);
			
				});
	
	function add_bed_room()
	{
        $.post('/index.php/ipd/add_bed_room',{ 
		"room_list": $('#room_list').val(),
		"Ipd_ID": $('#Ipd_ID').val(),
		"start_datetime":$('#start_datetime').val(),
		"end_datetime": $('#end_datetime').val() }, function(data){
        $('#bed_assign_list').html(data);
        });
	}
   
</script>
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Room & Bed Assign List</h3>
    </div>
    <div class="box-body">
    <table id="Bed_list" 
        class="table table-bordered table-striped dataTable" 
        role="grid" aria-describedby="example1_info">
        <thead>
            <tr role="row">
                <th class="sorting_asc" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" style="width: 182.467px;" aria-sort="ascending" aria-label="Rendering engine: activate to sort column descending">Room</th>
                <th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" style="width: 225.017px;" aria-label="Browser: activate to sort column ascending">BED</th>
                <th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" style="width: 198.733px;" aria-label="Platform(s): activate to sort column ascending">UHID ID</th>
                <th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" style="width: 155.9px;" aria-label="Engine version: activate to sort column ascending">Patient Name</th>
                <th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" style="width: 110.883px;" aria-label="CSS grade: activate to sort column ascending">TPA</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($bed_master as $row){ ?>
            <tr role="row" class="odd">
                <td class="sorting_1"><?=$row->room_name?></td>
                <td><?=$row->bed_no?></td>
                <td><?=$row->ipd_code?> <br><?=$row->p_code?></td>
                <td><?=$row->p_fname?><br/><?=$row->p_relative?><br/><?=$row->p_rname?></td>
                <td><?=$row->admit_type?></td>
            </tr>
            <?php } ?>
        </tbody>
        <tfoot>
            <tr>
                <th rowspan="1" colspan="1">Room</th>
                <th rowspan="1" colspan="1">BED</th>
                <th rowspan="1" colspan="1">UHID/IPD ID</th>
                <th rowspan="1" colspan="1">Patient Name</th>
                <th rowspan="1" colspan="1">TPA</th>
            </tr>
        </tfoot>
    </table>
    <div class="box-footer">
        <button type="submit" class="btn btn-primary">Submit</button>
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
	$(function () {
   
         $('#Bed_list').DataTable();

         $('#Modal_bed_assign').on('shown.bs.modal', function () {
            load_form_div('/ipd/ipd_bed_list','Modal_bed_assign-bodyc');
        });
	
	    $('#reservationtimerange').on('apply.daterangepicker', function(ev, picker) {
            var date_first=picker.startDate.format('YYYY-MM-DD HH:MM:SS');
            var date_second=picker.endDate.format('YYYY-MM-DD HH:MM:SS');

            $('#start_datetime').val(date_first);
            $('#end_datetime').val(date_second);
			
		});
    })
	
	
	function add_bed_room()
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		$.post('/index.php/ipdNew/add_bed_room',{ 
		"room_list": $('#room_list').val(),
		"Ipd_ID": $('#Ipd_ID').val(),
		"start_datetime":$('#start_datetime').val(),
		"end_datetime": $('#end_datetime').val(),
		"<?=$this->security->get_csrf_token_name()?>":csrf_value
		 }, function(data){
        $('#bed_assign_list').html(data);
        });
	}
   
</script>
<input type="hidden" value="<?=$ipd_discharge[0]->ipd_id ?>" id="ipd_id" name="ipd_id" />
	
<div class="row">
	<div  class="col-md-12">
		<div class="box box-info collapsed-box">
			<div class="box-header">
			  <h3 class="box-title">Discharge Preview
				<small></small>
			  </h3>
			  <div class="pull-right box-tools">
				<button type="button" class="btn btn-info btn-sm" data-widget="collapse" data-toggle="tooltip"
						title="Collapse">
				  <i class="fa fa-plus"></i></button>
			  </div>
			</div>
			<div class="box-body pad">
			<?php  
			//$content="";
			if(count($ipd_discharge)>0){
				//$content=$ipd_discharge[0]->content;
			}
			?>
				<textarea id="editor_Discharge_Preview" name="editor_Discharge_Preview" class="editor" rows="10" cols="80"><?=$content?></textarea>
				<script>
				  $(function () {
					CKEDITOR.replace('editor_Discharge_Preview')
				  })
				</script>
			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<button type="button" class="btn btn-primary" id="btn_create" >Recreate IPD</button>
		<button type="button" class="btn btn-danger" id="btn_show">Make File and Print on Letter Head</button>
		<button type="button" class="btn btn-danger" id="btn_show2">Make File and Print On Plain Paper</button>
		<button type="button" class="btn btn-danger" id="btn_show3">New Print</button>
	</div>
</div>

<script>

$(document).ready(function(){
		$('#btn_create').click(function(){
			var ipd_id = $('#ipd_id').val();
			load_form_div('/Ipd_discharge/ipd_select/'+ipd_id+'/1','maindiv');
			
        });
	
		$('#btn_show').click(function(){
			var ipd_id = $('#ipd_id').val();
			var print_type=1
			var Get_Query='/index.php/Ipd_discharge/show_discharge/'+ipd_id+'/'+print_type;
			//load_report_div(Get_Query,'maindiv');
			window.open(Get_Query, "_blank");
        });

        $('#btn_show2').click(function(){
			var ipd_id = $('#ipd_id').val();
			var print_type=0
			var Get_Query='/index.php/Ipd_discharge/show_discharge/'+ipd_id+'/'+print_type;
			//load_report_div(Get_Query,'maindiv');
			window.open(Get_Query, "_blank");
        });

        $('#btn_show3').click(function(){
			var ipd_id = $('#ipd_id').val();

			var Get_Query='/index.php/Ipd_discharge/show_file3/'+ipd_id;
			//load_report_div(Get_Query,'maindiv');
			window.open(Get_Query, "_blank");
        });

		

        
});

</script>
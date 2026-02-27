<div class="row">
	<div class="col-md-12">
		<div class="jsError"></div>
		<div class="box">
			<div class="box-header">
				<h3 class="box-title">Org. Packing Information</h3>
				<i>Packing No.</i>:<?=$org_packing[0]->label_no ?> 
				<i>Packing Date</i> : <?=$org_packing[0]->date_of_create ?>
				<button type="button" class="btn btn-primary" id="Edit_List_Head"  >Edit List Head</button>
				<button type="button" class="btn btn-primary" id="load_list"  >Show List</button>
				<button type="button" class="btn btn-primary" id="showreport"  >Show Report</button>
				<button type="button" class="btn btn-primary" id="showreportexport"  >Export Report</button>
				<div class="box-tools">
                    <?php 
                        $attributes = array('id' => 'form_org_search');
                        echo form_open('Org_Packing/org_case_search',$attributes); ?>
						<input type="hidden" id="packing_id" name="packing_id" value="<?=$packing_id?>">
						<div class="input-group input-group-sm" style="width: 200px;">
							<input type="text" name="data_search" id="data_search" class="form-control pull-right" 
							value="<?php echo $this->input->post('data_search'); ?>"
							placeholder="Search">
							<div class="input-group-btn">
								<button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
							</div>
						</div>
                    <?php echo form_close(); ?>
                </div>
			</div>
			<div class="box-body">
				<?php echo form_open('AddNew', array('role'=>'form','class'=>'form2')); ?>
				<div id="invoice_item_list"></div>
				<?php echo form_close(); ?>
			</div>
			<div class="box-footer">
				<?php echo form_open('AddNew', array('role'=>'form','class'=>'form1')); ?>
				<input type="hidden" id="invoice_id" name="invoice_id" value="<?=$org_packing[0]->id ?>" >
				<div class="row" id="org_item_list">
					<?=$org_packing_list?>
				</div>
				<?php echo form_close(); ?>
			</div>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
        $('#form_org_search').on('submit', function(form){
            form.preventDefault();
            form_array=$('#form_org_search').serialize();
            $("#body_content").html('Data Posting....Please Wait');
            $.post('/Org_Packing/org_case_search', form_array, function(data){
                 $("#invoice_item_list").html(data);
            });
        });
    });

	function Add_list(org_id,packing_id)
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		$.post('/index.php/Org_Packing/Add_list/'+org_id+'/'+packing_id,
		{ 	"org_id": org_id,
			"packing_id":packing_id,
			'<?=$this->security->get_csrf_token_name()?>':csrf_value }, function(data){
			$('#org_item_list').html(data);
		});
	}

	function remove_item_list(org_id,packing_id)
	{
		var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

		if(confirm('Are You Sure to Remove this Item'))
		{
			$.post('/index.php/Org_Packing/remove_item_packing/'+org_id+'/'+packing_id,
			{ 	"org_id": org_id,
				'<?=$this->security->get_csrf_token_name()?>':csrf_value }, function(data){
				$('#org_item_list').html(data);
			});
		}
	}

	$('#Edit_List_Head').click( function()
	{
		var Packing_id=$('#packing_id').val();
				
		var Get_Query="/Org_Packing/PackingEdit/"+Packing_id;
		load_form_div(Get_Query,'searchresult');
		
	});

	$('#load_list').click( function()
	{
		var Packing_id=$('#packing_id').val();
				
		var Get_Query="/Org_Packing/Pack_list/"+Packing_id;
		load_form_div(Get_Query,'org_item_list');
		
	});


	$('#showreport').click( function()
	{
		var Packing_id=$('#packing_id').val();
				
		var Get_Query="/Org_Packing/Print_List/"+Packing_id;
		load_report_div(Get_Query,'org_item_list');
		
	});
	
	$('#showreportexport').click( function()
	{
		var Packing_id=$('#packing_id').val();
		
		var Get_Query="/Org_Packing/Print_List/"+Packing_id+"/1";
		window.open(Get_Query, "_blank");
		
	});

    
</script>
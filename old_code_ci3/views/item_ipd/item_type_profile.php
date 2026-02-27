<section class="content-header">
    <h1>
        IPD Charges Group
        <small><?=$data_item[0]->group_desc ?></small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="javascript:load_form('/Item_IPD/search_itemtype');"><i class="fa fa-dashboard"></i>Charges Group List</a></li>
        
    </ol>
</section>
<!-- Main content -->
<section class="content">
	<div class="jsError"></div>
      <div class="row">
        <div class="col-md-12">
            <?php echo form_open('insurance/create', array('role'=>'form','class'=>'form1')); ?>
			<input type="hidden" value="<?=$data_item[0]->itype_id ?>" id="itemtype_id" name="itemtype_id" />
				<div class="row">
					<div class="col-md-4">
						<div class="form-group">
							<label>Charges Group Name</label>
							<input class="form-control" name="input_Item_type" placeholder="Item Name" value="<?=$data_item[0]->group_desc ?>" type="text" autocomplete="off">
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group">
						<label> </label>
							<button type="button" class="btn btn-primary" id="btn_update">Update Record</button>
						</div>
					</div>
				</div>
				
			<?php echo form_close(); ?>
        </div>
	</div>
 </section>
<!-- /.content -->
<script>
    $(document).ready(function(){
        $('#btn_update').click(function(){
			$.post('/index.php/Item_IPD/UpdateItemTypeRecord', $('form.form1').serialize(), function(data){
                if(data.update==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
                    
					$('div.jsError').html(data.showcontent);
					$("#alert_show").alert();
					$("#alert_show").fadeTo(2000, 500).slideUp(500, function(){
						$("#alert_show").slideUp(500);
						});

                }
            }, 'json');
		})
  
   });
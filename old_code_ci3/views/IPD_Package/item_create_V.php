<section class="content-header">
    <h1>
        Charges 
        <small>Add New</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="javascript:load_form('/item/search');"><i class="fa fa-dashboard"></i> Charges Name</a></li>
        
    </ol>
</section>
<!-- Main content -->
    <section class="content">
	<div class="jsError"></div>
      <div class="row">
        <div class="col-md-12">
            <?php echo form_open('insurance/create', array('role'=>'form','class'=>'form1')); ?>
                        <input type="hidden" value="0" id="p_id" name="p_id" />
                        <div class="row">
							<div class="col-md-4">
								<div class="form-group">
									<label>Package Name</label>
									<input class="form-control" name="input_Item_name" placeholder="Item Name" value="" type="text" autocomplete="off">
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Package Group</label>
									<select class="form-control" name="Item_Type" id="Item_Type" >
									<?php 
										foreach($data_item_type as $row)
										{ 
											echo '<option value="'.$row->pak_id.'" >'.$row->pakage_group_name.'</option>';
										}
									?>   
									</select>
								</div>
							</div>
							<div class="col-md-4">
								<div class="form-group">
									<label>Amount</label>
									<input class="form-control" name="input_amount" placeholder="amount" value="" type="text" autocomplete="off">
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<button type="submit" class="btn btn-primary" id="btn_update">Add Record</button>
							</div>
						</div>
						
                        <?php echo form_close(); ?>
        </div>
	</div>
      <!-- ./row -->
    </section>
<!-- /.content -->

<script>
   $(document).ready(function(){
        $('form.form1').on('submit', function(form){
            form.preventDefault();
            $.post('/index.php/Package/CreateRecord', $('form.form1').serialize(), function(data){
                if(data.insertid==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
                    load_form_div('/Package/item_record/'+data.insertid,'maindiv');
                }
            }, 'json');
        });
   });
   
   
   
    
</script>
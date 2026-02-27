<section class="content-header">
    <h1>
       OPD  Charges Group
       
    </h1>
    <ol class="breadcrumb">
        <li><a href="javascript:load_form_div('/item/search_itemtype','maindiv');"><i class="fa fa-dashboard"></i>Charges Group List</a></li>
        
    </ol>
</section>
<!-- Main content -->
<section class="content">
	<div class="jsError"></div>
      <div class="row">
        <div class="col-md-12">
            <?php echo form_open('insurance/create', array('role'=>'form','class'=>'form1')); ?>
				<div class="row">
					<div class="col-md-4">
						<div class="form-group">
							<label>Charges Group Name</label>
							<input class="form-control" name="input_Item_type" placeholder="Item Name"  type="text" autocomplete="off">
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group">
							<label>Is IPD or OPD</label>
							<select class="form-control" name="Item_Type_ipd" id="Item_Type_ipd" >
								<option value="0" >Both OPD and IPD</option>
								<option value="1" >Only OPD</option>
								<option value="2" >Only IPD</option>
							</select>
						</div>
					</div>
					<div class="col-md-4">
						<div class="form-group">
						<label> </label>
							<button type="submit" class="btn btn-primary" id="btn_update">Add Record</button>
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
        $('form.form1').on('submit', function(form){
            form.preventDefault();
            $.post('/index.php/item/CreateItemTypeRecord', $('form.form1').serialize(), function(data){
                if(data.insertid==0)
                {
                    notify('error',ata.error_text);
                }else
                {
                    load_form_div('/item/itemtype_record/'+data.insertid,'maindiv');
                }
            }, 'json');
        });
   });
   
</script>
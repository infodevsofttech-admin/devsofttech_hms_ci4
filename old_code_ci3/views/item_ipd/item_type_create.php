<section class="content-header">
    <h1>
        IPD Charges Group
    </h1>
    <ol class="breadcrumb">
        <li><a href="javascript:load_form('/item/search_itemtype');"><i class="fa fa-dashboard"></i>Charges Group List</a></li>
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
            $.post('/index.php/Item_IPD/CreateItemTypeRecord', $('form.form1').serialize(), function(data){
                if(data.insertid==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
                    load_form('/Item_IPD/itemtype_record/'+data.insertid);
                }
            }, 'json');
        });
   });
   
</script>
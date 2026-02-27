<section class="content-header">
    <h1>
        Insurance Company 
        <small>Add New</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> List</a></li>
        <li class="active">Insurance Company</li>
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
							<div class="col-md-3">
								<div class="form-group">
									<label>Company Name</label>
									<input class="form-control" name="input_comp_name" placeholder="Full Name" value="" type="text" autocomplete="off">
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label>Phone Number</label>
									<input class="form-control" name="input_mphone1" placeholder="Phone Number" value="" type="text" autocomplete="off"  data-inputmask='"mask": "999-999-9999"' data-mask >
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label>E-Mail </label>
									<input class="form-control" name="input_email" placeholder="E-Mail" value="" type="text" autocomplete="off">
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label>Contact Person Name</label>
									<input class="form-control" name="input_cname" placeholder="Full Name" value="" type="text" autocomplete="off">
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<button type="submit" class="btn btn-primary" id="btn_update">Update Record</button>
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
            $.post('/index.php/insurance/CreateRecord', $('form.form1').serialize(), function(data){
                if(data.insertid==0)
                {
                    notify('Error',data.error_text);
                }else
                {
                    load_form_div('/insurance/insurance_record/'+data.insertid,'maindiv','Insurance :'+data.insertid);
                }
            }, 'json');
        });
   });
   
   
    
</script>
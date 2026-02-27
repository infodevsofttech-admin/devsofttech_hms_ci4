<section class="content-header">
    <h1>
        New Refferal Client 
        <small></small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Refferal Client List</a></li>
        <li class="active">New Refferal Client</li>
    </ol>
</section>
<!-- Main content -->
<section class="content">
<div id='userform'>
<div id="infoMessage" class="jsError" ></div>

<?php echo form_open("", array('role'=>'form','class'=>'form1'));?>
	<div class="row">
        <div class="col-md-2">
            <div class="form-group">
            <label>Title</label>
                <select class="form-control" name="cbo_title" id="cbo_title"  >
                <option value="Mr." >Mr.</option>
                <option value="Mrs." >Mrs.</option>
                <option value="Ms."  >Ms.</option>
                <option value="Dr."  >Dr.</option>
                </select>
            </div>
        </div>
		<div class="col-md-3">
            <div class="form-group">
                <label>Name</label>
                <input class="form-control input-sm" name="input_name" placeholder="Full Name" value="" type="text" autocomplete="off"  >
            </div>
		</div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Phone Number</label>
                <input class="form-control input-sm" name="input_phone_number" placeholder="Phone Number" value="" type="text" autocomplete="off"  >
            </div>
		</div>
		<div class="col-md-3">
            <div class="form-group">
            <label>Title</label>
                <select class="form-control" name="cbo_refer_type" id="cbo_refer_type"  >
                <?php
                foreach ($refer_type as $row) {
                    echo '<option value=' . $row->id . '  >' . $row->type_desc . '</option>';
                }
                ?>
                </select>
            </div>
        </div>
	</div>
	<div class="row">
		<div class="col-md-3">
            <button type="submit" class="btn btn-primary" id="btn_update">Add Record</button>
		</div>
	</div>

<?php echo form_close();?>
</div>
</section>
<script>
$(document).ready(function(){
        $('form.form1').on('submit', function(form){
            form.preventDefault();
            $.post('/index.php/Reffer/save_new_user', $('form.form1').serialize(), function(data){
                if(data.insertid==0)
                {
                    $('div.jsError').html(data.error_text);
                }else
                {
                    load_form('/Reffer/index');
                }
            }, 'json');
        });
   });
</script>
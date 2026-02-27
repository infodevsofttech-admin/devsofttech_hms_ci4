<section class="content-header">
    <h1>
        Edit Refferal Client 
        <small></small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Refferal Client List</a></li>
        <li class="active">Edit Refferal Client</li>
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
                <option value="Mr." <?=combo_checked("Mr.",$data[0]->title)?> >Mr.</option>
                <option value="Mrs." <?=combo_checked("Mrs.",$data[0]->title)?> >Mrs.</option>
                <option value="Ms."  <?=combo_checked("Ms.",$data[0]->title)?> >Ms.</option>
                <option value="Dr."  <?=combo_checked("Dr.",$data[0]->title)?> >Dr.</option>
                </select>
            </div>
        </div>
		<div class="col-md-3">
            <div class="form-group">
                <label>Name</label>
                <input class="form-control input-sm" name="input_name" placeholder="Full Name" value="<?=$data[0]->f_name ?>" type="text" autocomplete="off"  >
            </div>
		</div>
        <div class="col-md-3">
            <div class="form-group">
                <label>Phone Number</label>
                <input class="form-control input-sm" name="input_phone_number" placeholder="Phone Number" value="<?=$data[0]->phone_number ?>" type="text" autocomplete="off"  >
            </div>
		</div>
		<div class="col-md-3">
            <div class="form-group">
            <label>Title</label>
                <select class="form-control" name="cbo_refer_type" id="cbo_refer_type"  >
                <?php
                foreach ($refer_type as $row) {
                    echo '<option value=' . $row->id . '  '.combo_checked($row->id,$data[0]->refer_type).'  >' . $row->type_desc . '</option>';
                }
                ?>
                </select>
            </div>
        </div>
	</div>
	<div class="row">
		<div class="col-md-3">
            <button type="submit" class="btn btn-primary" id="btn_update">Update Record</button>
		</div>
	</div>

<?php echo form_close();?>
</div>
</section>
<script>
$(document).ready(function(){
        $('form.form1').on('submit', function(form){
            form.preventDefault();
            $.post('/index.php/Reffer/save_user/<?=$data[0]->id?>', $('form.form1').serialize(), function(data){
                if(data.update==0)
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
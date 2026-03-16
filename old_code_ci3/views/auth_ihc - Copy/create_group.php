<section class="content-header">
    <h1>
        <?php echo lang('create_group_heading');?> 
        <small><?php echo lang('create_group_subheading');?></small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Admin Group</a></li>
        <li class="active">New Group</li>
    </ol>
</section>
<section class="content">
<div id='userform'>
<div id="infoMessage" class="jsError" ></div>

<?php echo form_open("", array('role'=>'form','class'=>'form1'));?>


      <p>
            <?php echo lang('create_group_name_label', 'group_name');?> <br />
            <?php echo form_input($group_name);?>
      </p>

      <p>
            <?php echo lang('create_group_desc_label', 'description');?> <br />
            <?php echo form_input($description);?>
      </p>

      <p><?php echo form_submit('submit', lang('create_group_submit_btn'));?></p>

<?php echo form_close();?>
</div>
</section>
<script>
   $(document).ready(function(){
        $('form.form1').on('submit', function(form){
            form.preventDefault();
            $.post('/index.php/auth_ihc/create_group', $('form.form1').serialize(), function(data){
                $("#Content1").html(data);
            });
        });
   });
  
</script>
<?php 
  $attributes = array('id' => 'myform');
  echo form_open('Mobile_app/login_pin',$attributes); ?>
    <div id="main">
        <h2>Select Your Self for Login</h2>
        <div class="row">
        <?php foreach($doctor_data as $row)  {   ?>
            <div class="col-md-4">
                <div class="thumbnail">
                    <a href="javascript:load_form('/Mobile_app/login/<?=$row->id?>')">
                        <div class="caption">
                        <p>Dr. <?=$row->p_fname?></br><?=$row->SpecName?></p>
                        </div>
                    </a>
                </div>
            </div>
        <?php } ?>
        </div>
    </div>
<?php echo form_close();?>

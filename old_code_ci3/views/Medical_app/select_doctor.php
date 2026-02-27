<?php 
  $attributes = array('id' => 'myform');
  echo form_open('Medical_app/login_pin',$attributes); ?>
    <div id="main">
        <h2>Select Your Self for Login</h2>
        <div class="row">
        <?php foreach($user_data as $row)  {   ?>
            <div class="col-md-4">
                <div class="thumbnail">
                    <a href="javascript:load_form('/Medical_app/login/<?=$row->id?>')">
                        <div class="caption">
                        <p>User : <?=$row->first_name?> <?=$row->last_name?> [<?=$row->username?>]</p>
                        </div>
                    </a>
                </div>
            </div>
        <?php } ?>
        </div>
    </div>
<?php echo form_close();?>

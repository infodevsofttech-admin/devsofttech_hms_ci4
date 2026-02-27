<section class="content-header">
    <h1>
        Doctor
        <small> Dr. <?=$data[0]->p_fname; ?></small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i>Doctor</a></li>
    </ol>
</section>
<!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-md-12">
            <div class="jsError"></div>
            <?php echo form_open('Doctor/AddNew', array('role'=>'form','class'=>'form1')); ?>
            <input type="hidden" name="doc_id" id="doc_id" value="<?=$data[0]->id ?>" />
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                <label>Title</label>
                                <select class="form-control" name="select_title" id="select_title" >
                                    <option value="Dr." <?=combo_checked("Dr.",$data[0]->p_title)?>>Dr.</option>
                                    <option value="Mr." <?=combo_checked("Mr",$data[0]->p_title)?>>Mr.</option>
                                    <option value="Ms." <?=combo_checked("Ms",$data[0]->p_title)?>>Ms.</option>
                                </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input class="form-control" name="input_name" placeholder="Full Name" type="text" value="<?=$data[0]->p_fname ?>" />
                                </div>
                            </div>
                            <div class="col-md-4">
                                 <div class="form-group">
                                    <label>Email</label>
                                    <input class="form-control" name="input_email" name="input_email" placeholder="Email"  type="email" value="<?=$data[0]->email1 ?>" />
                                 </div>
                            </div>
                        </div>
                        <div class="row">
                             <div class="col-md-2">
                                <div class="form-group">
                                    <label>Gender</label>
                                    <div class="radio">
                                        <label>
                                          <input name="optionsRadios_gender" id="options_gender1" value="1" <?=radio_checked(1,$data[0]->gender)?> type="radio">
                                          Male
                                        </label>
                                        <label>
                                        <input name="optionsRadios_gender" id="options_gender2" value="2" type="radio" <?=radio_checked(2,$data[0]->gender)?> >
                                        Female
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input class="form-control" name="input_mphone1" placeholder="Phone Number" type="text"  
                                     value="<?=$data[0]->mphone1 ?>" >
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <div class="input-group date">
                                        <div class="input-group-addon">
                                            <i class="fa fa-calendar"></i>
                                        </div>
                                        <input class="form-control pull-right datepicker" name="datepicker_dob"  value="<?=MysqlDate_to_str($data[0]->dob) ?>" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""   />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row"">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Short Decription</label>
                                    <textarea  class="form-control" name="txt_doc_sign" placeholder="Short Info" ><?=$data[0]->doc_sign ?> </textarea>
                                </div>
                            </div>
                        </div>
						      <div class="row">
                             <div class="col-md-4">
                             <br />
                             <div class="form-group">
                                <button type="submit" class="btn btn-primary">Save</button>
                             </div>
                             </div>
                         </div>
						 <hr/>
						 <div class="row">
						 <div class="col-md-4">
							 <div class="row">
								 <div class="col-md-12">
								<div class="form-group">
										<label>Specility</label>
										<div id="sho_specility"  >
										<?php
											foreach($doc_spec_a as $row)
											{
												echo '<div class="input-group input-group-sm">';
												echo '	<input class="form-control" type="text" value="'.$row->SpecName.'" readonly />';
												echo '	<span class="input-group-btn">';
												echo '	<button type="button" class="btn btn-info btn-flat" onclick="remove_doc_spec('.$row->doc_spec_id.')" >Remove -</button></span>';
												echo '</div>';
											}
										?>
										</div>
								</div>
								<div class="input-group">
									<select class="form-control" name="doc_spec" id="doc_spec" >
									<option value="0">------Select-------</option>
									<?php 
										foreach($doc_spec_l as $row)
										{ 
											echo '<option value="'.$row->id.'">'.$row->SpecName.'</option>';
										}
									?>   
									</select>
									<span class="input-group-btn">
									<button type="button" class="btn btn-info btn-flat" onclick="add_doc_spec()" >Add +</button>
									</span>
								
								</div>
								</div>
							 </div>
						</div>
						<div class="col-md-8">
							<div class="row">
								<div class="box">
								<div class="box-header">
								</div>
								<div class="box-body no-padding">
								<div class="show_fee_list">
								<?php
									$table_property = array('table_open' => '<table class="table table-striped">');
									$this->table->set_template($table_property);
									$this->table->set_heading('Fee Type', 'Description', 'Amount');
									foreach($doc_fee_list as $row)
									{
										if($row->id=='')
                                        {
                                            $button_code=' Not Define';
                                        }else{
                                            $button_code='<a href="javascript:remove_fees('.$row->id.')">Remove</a>';
                                        }

                                        $this->table->add_row($row->fee_type, $row->doc_fee_desc, $row->amount,$button_code);
									}
									echo $this->table->generate();

								?>
								</div>
								</div>
								<div class="box-footer">
									<div class="col-md-3">
    									<div class="form-group">
    										<label>Fee Type</label>
    										<div class="input-group">
    											<select class="form-control" name="fee_type" id="fee_type" >
    											<?php 
    												foreach($doc_fee_type as $row)
    												{ 
    													echo '<option value="'.$row->id.'">'.$row->fee_type.'</option>';
    												}
    											?>   
    											</select>
    										</div>
    									</div>
    								</div>
    								<div class="col-md-3">
    									<div class="form-group">
    										<label>Fee Discription</label>
    										<input class="form-control" id="input_fee_desc" name="input_fee_desc" placeholder="Fee Description" type="text" />
    									</div>
    								</div>
    								<div class="col-md-3">
    									<div class="form-group">
    										<label>Amount</label>
    										<input class="form-control" id="input_fee_amount" name="input_fee_amount" placeholder="Amount" type="text" />
    									</div>
    								</div>
    								<div class="col-md-3">
    								<div class="form-group">
    									   <button type="button" class="btn btn-primary" id="btn_add_fee" onclick="add_fees()">+Add</button>
    									</div>
    								</div>
								</div>	
							</div>
							 <div class="row">
								
									
								</div>
							 </div>
						 </div>
                        <?php echo form_close(); ?>
                        
                  </div>
        </div>
      </div>
      <!-- ./row -->
    </section>
<!-- /.content -->
<script>
    $(document).ready(function(){
        $('form.form1').on('submit', function(form){
            form.preventDefault();
            
            $.post('/index.php/Doctor/UpdateRecord', $('form.form1').serialize(), function(data){
                if(data.update==0)
                {
                    notify('error', 'Please Attention', data.error_text);
                }else
                {
                    notify('success', 'Please Attention', data.showcontent);
                }
            }, 'json');
        });
   });
   
   function add_fees()
   {
        var csrf_name='<?=$this->security->get_csrf_token_name()?>';
        var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        $.post('/index.php/Doctor/AddNew_fee',
            {   "fee_type": $('#fee_type').val(), 
                "doc_id": $('#doc_id').val(), 
                "input_fee_desc": $('#input_fee_desc').val(), 
                "input_fee_amount": $('#input_fee_amount').val(),
                '<?=$this->security->get_csrf_token_name()?>':csrf_value 
            }, function(data){
        if(data.inser_id==0)
                {
                    notify('error', 'Please Attention', data.error_text);
                }else
                {
                    notify('success', 'Please Attention', data.showcontent);
					$('div.show_fee_list').html(data.show_fee_list);
                }
                $('input[name=<?=$this->security->get_csrf_token_name()?>]').val(data.csrfHash);

            }, 'json');
   }
   
   function remove_fees(rid)
   {
        var csrf_name='<?=$this->security->get_csrf_token_name()?>';
        var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        $.post('/index.php/Doctor/remove_fee',
            {   "rid": rid, 
                "doc_id": $('#doc_id').val(),
                '<?=$this->security->get_csrf_token_name()?>':csrf_value }, function(data){
        if(data.update==0)
                {
                    notify('error', 'Please Attention', data.error_text);
                }else
                {
                    notify('success', 'Please Attention', data.showcontent);    
					$('div.show_fee_list').html(data.show_fee_list);

                }
                $('input[name=<?=$this->security->get_csrf_token_name()?>]').val(data.csrfHash);
            }, 'json');
   }
      
   function add_doc_spec()
   {
        var csrf_name='<?=$this->security->get_csrf_token_name()?>';
        var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        $.post('/index.php/Doctor/doctor_record_spec',
            {   "doc_spec": $('#doc_spec').val(),
                "doc_id": $('#doc_id').val(),
                "isadd":1,
                '<?=$this->security->get_csrf_token_name()?>':csrf_value
            }, function(data){
            $('#sho_specility').html(data.show_Specility_list);
        }, 'json');
   }
   
   function remove_doc_spec(doc_spec)
   {
        var csrf_name='<?=$this->security->get_csrf_token_name()?>';
        var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        $.post('/index.php/Doctor/doctor_record_spec',
            {   "doc_spec_id": doc_spec,
                "doc_id": $('#doc_id').val(),
                "isadd":0,
                '<?=$this->security->get_csrf_token_name()?>':csrf_value
            }, function(data){
            $('#sho_specility').html(data.show_Specility_list);
        }, 'json');
   }
    
</script>
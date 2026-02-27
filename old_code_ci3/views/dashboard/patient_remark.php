<br/><br/>
<div class="row">
    <div class="col-md-4">
        <div class="row">
            <div class="box box-primary">
                <div class="box-header with-border">
                        <h3 class="box-title">Patient Personal Remark</h3>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
        <?php 
            // External Loop
            foreach($patient_remark as $row){
        ?>
                    <p class="text-warning">Write By <span class="text-success"> <?=$row->insert_by?> </span>
                    Date : <span class="text-success"> : <?=$row->ins_time?></span></p>
                    <p class="text-primary"><?=nl2br($row->remark) ?></p>
                    <hr/>
                
        <?php
            //External Loop  
            }
        ?>
                </div>
                <!-- /.box-body -->
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Remark</h3>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <div class="form-group">
                            <label for="patient_notes">Patient Personal Remark</label>
                            <textarea class="form-control" id="patient_notes" name="patient_notes" rows="5"></textarea>
                        </div>
                    </div>
                    <!-- /.box-body -->
                    <div class="box-footer">
                        <button type="button" class="btn btn-info" onclick="save_comments()">Save Remark</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
    <div class="row">
            <div class="col-md-12">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title">Assign Tag</h3>
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                    <div class="col-md-12">
                        <div class="form-group">
                                <label>Tag</label>
                                <?php
                                    foreach($patient_tag_list as $row)
                                    {
                                        echo '<div class="input-group input-group-sm">';
                                        echo '	<div class="form-control" >'.$row->tag_name.'<br/>'.$row->assign_by.'</div>';
                                        echo '	<span class="input-group-btn">';
                                        echo '	<button type="button" class="btn btn-danger btn-flat" onclick="remove_cust_tag('.$row->id.')" >-</button></span>';
                                        echo '</div>';
                                    }
                                ?>
                        </div>
                    </div>
                    </div>
                    <!-- /.box-body -->
                    <div class="box-footer">
                        <div class="input-group">
							<select class="form-control" name="cbo_tag_master" id="cbo_tag_master" >
							<option value="0">------Select-------</option>
							<?php 
								foreach($tag_master as $row)
								{ 
									echo '<option value="'.$row->id.'">'.$row->tag_name.'</option>';
								}
							?>   
							</select>
							<span class="input-group-btn">
							<button type="button" class="btn btn-info btn-flat" onclick="add_cust_tag()" >Add +</button>
							</span>
						
						</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>

    function save_comments()
	{
		var add_notes=$('#patient_notes').val();
        var csrf_name='<?=$this->security->get_csrf_token_name()?>';
        var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        if(add_notes=='')
        {
            alert('Blank Notes not Allowed');
            return false;
        }

		$.post('/Patient/add_comments/<?=$p_id?>', 
        {
            '<?=$this->security->get_csrf_token_name()?>':csrf_value,
            'add_notes':add_notes}, 
        function(data){
            if(data.update==0)
            {
                alert(data.error_text);
            }else
            {
                load_form_div('/Opd_prescription/patient_remark/<?=$p_id ?>','menu7');
            }
			
		},'json');
	}
	
	function add_cust_tag()
	{
        var csrf_name='<?=$this->security->get_csrf_token_name()?>';
        var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        $.post('/index.php/Patient/patient_tag',
            {   "tag_id": $('#cbo_tag_master').val(),
                "p_id": <?=$p_id?>,
                "isadd":1,
                '<?=$this->security->get_csrf_token_name()?>':csrf_value
            }, function(data){
            $("#menu7").html(data);
        });
	}
 
    function remove_cust_tag(cust_tag_id)
    {
        var csrf_name='<?=$this->security->get_csrf_token_name()?>';
        var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        $.post('/index.php/Patient/patient_tag',
            {   "cust_tag_assign": cust_tag_id,
                "p_id": <?=$p_id?>,
                "isadd":0,
                '<?=$this->security->get_csrf_token_name()?>':csrf_value
            }, function(data){
            $("#menu7").html(data);
        });
    }

</script>

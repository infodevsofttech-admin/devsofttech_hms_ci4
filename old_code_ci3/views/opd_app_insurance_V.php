<section class="content-header">
    <h1>
        OPD 
        <small>Registration</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">OPD</li>
    </ol>
</section>
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<div class="box box-danger">
    <div class="box-header">
        <div class="box-title">
        <p><strong>Name :</strong><?=$person_info[0]->p_fname?>  
        <strong>/ Age :</strong><?=$person_info[0]->age?> 
        <strong>/ Gender :</strong><?=$person_info[0]->xgender?> 
        <strong>/ P Code :</strong><?=$person_info[0]->p_code?>
        <input type="hidden" id="pid" value="<?=$person_info[0]->id?>" />
        <input type="hidden" id="insurance_id" value="<?=$insurance_card[0]->insurance_id?>" />
        
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Date</label>
                    <div class="input-group date">
                        <div class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </div>
                        <input class="form-control pull-right datepicker" id="datepicker_appointment" name="datepicker_appointment" type="text" data-inputmask="'alias': 'dd/mm/yyyy'" data-mask="" value=<?=date('d/m/Y') ?>  />
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                <div id="ShowDoctor" >
                    <?php 
                        foreach($doc_spec_l as $row)
                        { 
                            echo '<label>';
                            echo '<input type="radio" id="rdoc_id" name="rdoc_id" class="flat-red" value='.$row->id.'> ';
                            echo $row->p_fname.' [<i>'.$row->SpecName.'</i>]';
                            echo '</label><br/>';
                        }
                   ?>   
                     <button type="button" class="btn btn-primary" id="btnnextfee">Next to Fee</button>
                  </div>
                 
                   </div>
              </div>
        </div>
        <div id="showfee" >
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <div id="showfeedocid">
                        
                    </div>
                </div>
                
            </div>
            
        </div>
        <div  class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <button type="button" class="btn btn-primary" id="backtodoc">Back to Select Doctor</button>
                    <button type="button" class="btn btn-primary" id="btnnextconfirm">Confirm And Go for Payment</button>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="box-footer">
        
    </div>
</div>
<?php echo form_close(); ?>
<script>
   

    $(document).ready(function(){
        
        $('#showfee').hide();
        
        $('#btnnextfee').click( function()
        {
            var check_value = $('#rdoc_id:checked').val();
            var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
            
            $('#showfee').show();           
            
            $.post('/index.php/Opdcase/showfee',{ "doc_id": check_value,"insurance_id": $('#insurance_id').val(),
            '<?=$this->security->get_csrf_token_name()?>':csrf_value  }, 
                function(data){
                    $('input[name=<?=$this->security->get_csrf_token_name()?>]').val(data.csrfHash);
                    $('#showfeedocid').html(data.content);
            },'json');
            $('#ShowDoctor').hide();
            
        });
        
        $('#backtodoc').click(function(){
            $('#ShowDoctor').show();
            $('#showfee').hide();

        });
        
        $('#btnnextconfirm').click( function()
        {
            var check_value = $('#fee_id:checked').val();
            var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

            $.post('/index.php/Opdcase/confirm_opd',
                { "insurance_id": $('#insurance_id').val(),
                "insurance_fee_found":"1", 
                "fee_id": check_value,
                "doc_id":$('#doc_id').val(),
                "input_clam_id":$('#input_clam_id').val(),
                "pid":$('#pid').val(),
                "datepicker_appointment":$('#datepicker_appointment').val(),
                '<?=$this->security->get_csrf_token_name()?>':csrf_value }, function(data){
            if(data.insertid==0)
                {
                    $('div.jsError').html(data.error_text);
                    $('input[name=<?=$this->security->get_csrf_token_name()?>]').val(data.csrfHash);
                }else
                {
                    load_form('/Opdcase/invoice/'+data.insertid);
                }
            }, 'json');
        });
        
        $('#btn_case').click(function(){
            var p_id = $('#pid').val();
            load_form('/Ocasemaster/newcase/'+p_id);
        });
        
        
        
   });
   
  
   
    
</script>
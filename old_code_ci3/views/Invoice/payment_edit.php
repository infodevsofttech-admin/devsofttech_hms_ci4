<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                    <h3 class="box-title">
                        Payment ID :<?=$rec_no?>
                    </h3>
                    <div class="row">
                        <div class="col-md-2">
                            <p class="text-primary">Invoice Type : <span class="text-success"><?=$inv_Type ?></span></p>
                        </div>
                        <div class="col-md-3">
                            <p class="text-primary">Invoice No : <span class="text-success"><?=$invoice_no?></span></p>
                        </div>
                        <div class="col-md-3">
                            <p class="text-primary">Invoice To : <span class="text-success"><?=ucwords($patient_master[0]->p_fname) ?></span></p>
                        </div>
                        <div class="col-md-2">
                            <p class="text-primary">Amount : <span class="text-success"><?=$payment_history[0]->amount ?></span></p>
                        </div>
                        <div class="col-md-2">
                            <p class="text-primary">Mode of Payment. : <span class="text-success"><?php echo (($payment_history[0]->payment_mode==1)?'CASH':'BANK');?></span></p>
                        </div>
                    </div>
                    <input type="hidden" value="<?=$payment_history[0]->id ?>" id="p_id" name="p_id" />
            </div>
            <div class="box-body">
                    <div class="row">
                        <?php if($payment_history[0]->payment_mode==1){  ?>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Payment By  </label>
                                    <select class="form-control" name="cbo_pay_type" id="cbo_pay_type" " >
                                        <?php
                                            foreach($bank_data as $row){
                                                echo '<option value="'.$row->id.'" > '.$row->pay_type.' ['.$row->bank_name.']'.'</option>';
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tran. ID/Ref.  </label>
                                    <input class="form-control" id="input_card_tran" placeholder="Card Tran.ID."  type="text" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary" id="btn_update_bank">Change to BANK</button>
                            </div>
                        <?php }else{ ?>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-primary" id="btn_update_cash">Change to CASH</button>
                            </div>
                        <?php } ?>
                    </div>
                    <hr/>
                    <div class="row">
                        <div class="col-md-4">
                            <label for="user_list" class="control-label">Change User</label>
                            <div class="form-group">
                                <select name="user_list" id="user_list" class="form-control" >
                                    <?php 
                                    foreach($all_user_list as $user_list)
                                    {
                                        $selected = ($user_list['id'] == $payment_history[0]->update_by_id) ? ' selected="selected"' : "";
                                        echo '<option value="'.$user_list['id'].'" '.$selected.'>'.$user_list['username'].'['.$user_list['first_name'].' '.$user_list['last_name'].']</option>';
                                    } 
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary" id="btn_update_user">Change User</button>
                        </div>
                    </div>
                    <hr/>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Change/Correct Amount Value</label>
                                <input class="form-control input-sm number" name="input_change_value" id="input_change_value"  value="<?=$payment_history[0]->amount ?>" type="text" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary" id="btn_update_amount">Change Amount</button>
                        </div>
                    </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function(){
        $('#btn_update_bank').click( function()
        {
            var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
            
            var pay_id=$('#p_id').val();
            
            if(confirm("Are you sure Edit this Payment "))
            {
                $.post('/index.php/Payment/change_to_bank',
                {   "mode":"2",
                    "cbo_pay_type": $('#cbo_pay_type').val(),
                    "input_card_tran": $('#input_card_tran').val(),"pay_id":pay_id,
                '<?=$this->security->get_csrf_token_name()?>':csrf_value  }, function(data){
                    if(data.update==0)
                    {
                        $('div.jsError').html(data);
                    }else
                    {
                        $('div.searchresult').html(data);
                    }
                });
            }
        });	

        $('#btn_update_cash').click( function()
        {
            var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
            
            var pay_id=$('#p_id').val();
            var user_list=$('#user_list').val();
            
            if(confirm("Are you sure Edit this Payment "))
            {
                $.post('/index.php/Payment/change_to_cash',
                { 
                    "mode":"1",
                    "pay_id":pay_id,
                    '<?=$this->security->get_csrf_token_name()?>':csrf_value  }, function(data){
                if(data.update==0)
                        {
                            $('div.jsError').html(data);
                            setTimeout(enable_btn,5000);
                        }else
                        {
                            $('div.searchresult').html(data);
                        }
                });
            }
        });

        $('#btn_update_user').click( function()
        {
            var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
            
            var pay_id=$('#p_id').val();
            var user_list=$('#user_list').val();
            
            if(confirm("Are you sure Change User for this Payment "))
            {
                $.post('/index.php/Payment/change_user',
                { 
                    "user_list":user_list,
                    "pay_id":pay_id,
                    '<?=$this->security->get_csrf_token_name()?>':csrf_value  }, function(data){
                if(data.update==0)
                        {
                            $('div.jsError').html(data);
                            setTimeout(enable_btn,5000);
                        }else
                        {
                            $('div.searchresult').html(data);
                        }
                });
            }
        });	

        $('#btn_update_amount').click( function()
        {
            var csrf_value=$('input[name=<?=$this->security->get_csrf_token_name()?>]').val();
            
            var pay_id=$('#p_id').val();
            var change_value=$('#input_change_value').val();
            
            if(confirm("Are you sure Change Amount "))
            {
                $.post('/index.php/Payment/change_amount',
                { 
                    "change_value":change_value,
                    "pay_id":pay_id,
                    '<?=$this->security->get_csrf_token_name()?>':csrf_value  }, function(data){
                if(data.update==0)
                        {
                            $('div.jsError').html(data);
                            setTimeout(enable_btn,5000);
                        }else
                        {
                            $('div.searchresult').html(data);
                        }
                });
            }
        });	

        

    });


</script>

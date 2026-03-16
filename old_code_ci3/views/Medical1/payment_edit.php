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
                            <p class="text-primary">IPD/OPD No : <span class="text-success"><?=$invoice_no?></span></p>
                        </div>
                        <div class="col-md-3">
                            <p class="text-primary">Invoice To : <span class="text-success"><?=ucwords($invoice_to_name) ?></span></p>
                        </div>
                        <div class="col-md-2">
                            <p class="text-primary">Amount : <span class="text-success">Rs. <?=$payment_history[0]->Amount_str ?></span></p>
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
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Card Swipe Machine  </label>
                                    <input class="form-control" id="input_card_mac" placeholder="Card Swipe Machine Bank Name"  type="text" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Customer Bank Name  </label>
                                    <input class="form-control" id="input_card_bank" placeholder="Customer Bank Name"  type="text" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Last Four Digit of Card</label>
                                    <input class="form-control" id="input_card_digit" placeholder="Last Four Digit of Card"  type="text" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Tran. ID  </label>
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
                                <input class="form-control input-sm number" id="input_change_value"  name="input_change_value"  value="<?=$payment_history[0]->amount ?>" type="text" autocomplete="off">
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
                $.post('/index.php/Payment_Medical/change_to_bank',
                { "mode":"2",
                "input_card_mac": $('#input_card_mac').val(),
                "input_card_bank": $('#input_card_bank').val(),
                "input_card_digit": $('#input_card_digit').val(),
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
            
            if(confirm("Are you sure Edit this Payment "))
            {
                $.post('/index.php/Payment_Medical/change_to_cash',
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

        
    });


</script>

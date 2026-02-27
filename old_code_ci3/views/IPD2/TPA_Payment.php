<div class="box">
    <div class="box-header">
        <h3 class="box-title">TPA and Other Payment / Discount</h3>
    </div>
    <div class="box-body">
    <?php echo form_open('IpdNew/tpa_payment', array('role'=>'form','class'=>'form1')); ?>    
    <input type="hidden" id="hid_ipd_id" name="hid_ipd_id" value="<?=$ipd_id?>" />
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Payable By TPA</label>
                    <input class="form-control number" name="input_payable_by_tpa" id="input_payable_by_tpa" placeholder="0.00" type="text" value="<?=$ipd_info[0]->payable_by_tpa ?>"  />
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Discount For TPA</label>
                    <input class="form-control number" name="input_discount_for_tpa" id="input_discount_for_tpa" placeholder="0.00" type="text" value="<?=$ipd_info[0]->discount_for_tpa ?>"  />
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Discount By Hospital </label>
                    <input class="form-control number" name="input_discount_by_hospital" id="input_discount_by_hospital" placeholder="0.00" type="text" value="<?=$ipd_info[0]->discount_by_hospital ?>"  />
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Remark</label>
                    <input class="form-control varchar" name="input_discount_by_hospital_remark" id="input_discount_by_hospital_remark" placeholder="" type="text" value="<?=$ipd_info[0]->discount_by_hospital_remark ?>"  />
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Discount By Doctor</label>
                    <input class="form-control number" name="input_discount_by_hospital_2" id="input_discount_by_hospital_2" placeholder="0.00" type="text" value="<?=$ipd_info[0]->discount_by_hospital_2 ?>"  />
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Remark</label>
                    <input class="form-control varchar" name="input_discount_by_hospital_2_remark" id="input_discount_by_hospital_2_remark" placeholder="" type="text" value="<?=$ipd_info[0]->discount_by_hospital_2_remark ?>"  />
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-2"> 
                <div class="form-group">
                    <label> </label>
                    <button type="button" class="btn btn-primary" id="btn_update" accesskey="U" >Update Amount</button>
                </div>
            </div>
        </div>
    <?php echo form_close(); ?>
    </div>
</div>

<script>
$(document).ready(function(){
	$('#btn_update').click(function(){
		$.post('/index.php/IpdNew/tpa_payment', $('form.form1').serialize(), function(data){
			if(data.update==0)
			{
				notify('error','Please Attention',data.show_text);
			}else
			{
				$('#hid_cid').val(data.update);
				
				notify('success','Please Attention',data.show_text);

			}
		}, 'json');
	});
});
</script>
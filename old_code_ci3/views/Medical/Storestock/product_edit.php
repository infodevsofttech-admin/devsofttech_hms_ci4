<div class="row">
    <div class="col-md-12">
        <div class="jsError"></div>
        <?php echo form_open('AddNew', array('role'=>'form','class'=>'form1')); ?>
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Product </h3>
                <small><i><?=$product_data[0]->item_name?> </i></small>
            </div>
            <div class="box-body">
                <input type="hidden" id="product_id" name="product_id" value="<?=$product_data[0]->id?>">
                <input type="hidden" id="related_drug_id" name="related_drug_id"
                    value="<?=$product_data[0]->related_drug_id?>">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <div class="ui-widget">
                                <label for="tags">Product Name</label>
                                <input class="form-control input-sm" name="input_item_name" id="input_item_name"
                                    placeholder="Product Name" type="text" value="<?=$product_data[0]->item_name?>"
                                    <?php echo ($this->ion_auth->in_group('MedicalStoreAdmin'))?'':'readonly'; ?>>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label for="comp_id" class="control-label">Category</label>
                        <input class="form-control varchar input-sm" name="input_formulation" id="input_formulation" placeholder="Bottle, Register" type="text" value="<?=$product_data[0]->formulation?>"  />
                        
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="ui-widget">
                                <label for="tags">Product Description</label>
                                <input class="form-control input-sm" name="input_genericname" id="input_genericname"
                                    placeholder="Description Name" type="text" value="<?=$product_data[0]->genericname?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Packing Qty</label>
                            <input class="form-control number input-sm" name="input_packing_type"
                                id="input_packing_type" placeholder="10 Tablets in Strip,1 Bottle" type="text"
                                value="<?=$product_data[0]->packing?>" />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Re-Order Qty</label>
                            <input class="form-control number input-sm" name="input_re_order_qty"
                                id="input_re_order_qty" placeholder="Re-Order Qty " type="text"
                                value="<?=$product_data[0]->re_order_qty?>" />
                        </div>
                    </div>
                    <?php 
							$chk_ban_flag_id=$product_data[0]->ban_flag_id;
							if($chk_ban_flag_id==1)
							{
								$chk_ban_flag_id_checkbox_checked="checked";
							}else{
								$chk_ban_flag_id_checkbox_checked="";
							}

							$chk_is_continue=$product_data[0]->is_continue;
							if($chk_is_continue==1)
							{
								$chk_is_continue_checkbox_checked="checked";
							}else{
								$chk_is_continue_checkbox_checked="";
							}

							$chk_batch_applicable=$product_data[0]->batch_applicable;
							if($chk_batch_applicable==1)
							{
								$chk_batch_applicable_checkbox_checked="checked";
							}else{
								$chk_batch_applicable_checkbox_checked="";
							}

							$chk_exp_date_applicable=$product_data[0]->exp_date_applicable;
							if($chk_exp_date_applicable==1)
							{
								$chk_exp_date_applicable="checked";
							}else{
								$chk_exp_date_applicable="";
							}

							

						?>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><input id="chk_exp_date_applicable" name="chk_exp_date_applicable" type="checkbox"
                                    <?=$chk_exp_date_applicable?>>Exp.Date Applicable</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>HSNCODE</label>
                            <input class="form-control number input-sm" name="input_HSNCODE" id="input_HSNCODE"
                                placeholder="HSN CODE" type="text" value="<?=$product_data[0]->HSNCODE?>" />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>CGST</label>
                            <input class="form-control number input-sm" name="input_CGST" id="input_CGST"
                                placeholder="CGST" type="text" value="<?=$product_data[0]->CGST_per?>" />
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>SGST</label>
                            <input class="form-control number input-sm" name="input_SGST" id="input_SGST"
                                placeholder="SGST" type="text" value="<?=$product_data[0]->SGST_per?>" />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Rack No.</label>
                            <input class="form-control number input-sm" name="input_rack_no" id="input_rack_no"
                                placeholder="Rack No." type="text" value="<?=$product_data[0]->rack_no?>" />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Shelf No</label>
                            <input class="form-control number input-sm" name="input_shelf_no" id="input_shelf_no"
                                placeholder="Shelf No" type="text" value="<?=$product_data[0]->shelf_no?>" />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Cold Storage</label>
                            <input class="form-control number input-sm" name="input_cold_storage"
                                id="input_cold_storage" placeholder="Cold Storage" type="text"
                                value="<?=$product_data[0]->cold_storage?>" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="comp_id" class="control-label">Company Master</label>
                        <input class="form-control input-sm" name="input_company_name" id="input_company_name" placeholder="Company Name" type="text" value="<?=$product_data[0]->company_name?>"  >
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label> </label>
                            <button type="button" class="btn btn-primary" id="btn_update_stock"
                                accesskey="U"><u>U</u>pdate Product in Master</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <!-- Start  -->
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>
                                <input id="chk_ban_flag_id" name="chk_ban_flag_id" type="checkbox"
                                    <?=$chk_ban_flag_id_checkbox_checked?>>
                                Banned Drug</label>
                        </div>
                        <div class="form-group">
                            <label><input id="chk_batch_applicable" name="chk_batch_applicable" type="checkbox"
                                    <?=$chk_batch_applicable_checkbox_checked?>> Batch Applicable</label>
                        </div>
                        <div class="form-group">
                            <label><input id="chk_is_continue" name="chk_is_continue" type="checkbox"
                                    <?=$chk_is_continue_checkbox_checked?>>Is Continue</label>
                        </div>
                    </div>
                    <!--- End --->
                </div>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>
</div>

<script>
$(document).ready(function() {
    document.title = 'Drug Edit :<?=$product_data[0]->item_name?>';
});

function reset_input() {
    $("#input_item_name").val('');
    $("#input_formulation").val('');
    $("#input_packing_type").val('');
    $("#input_re_order_qty").val('0');
    $("#input_HSNCODE").val('');
    $("#input_CGST").val('');
    $("#input_SGST").val('');

    $("#input_ban_flag_id").val('');
    $("#input_rack_no").val('');
    $("#input_shelf_no").val('');
    $("#input_cold_storage").val('');

    $("#input_company_name").val('');

}

$(document).ready(function() {
    var cache = {};

    $("#input_drug").autocomplete({
        source: function(request, response) {
            var term = request.term;
            if (term in cache) {
                response(cache[term]);
                return;
            }
            $.getJSON("/product_stock_master/get_drug_master", {
                <?php echo $this->security->get_csrf_token_name(); ?>: '<?php echo $this->security->get_csrf_hash(); ?>',
                'term': term
            }, function(data, status, xhr) {
                cache[term] = data;
                response(data);
            });
        },
        minLength: 2,
        autofocus: true,
        select: function(event, ui) {
            $("#input_item_name").val(ui.item.l_itemname);
            $("#related_drug_id").val(ui.item.l_item_code);
            $("#input_formulation").val(ui.item.l_formulation);
            $("#input_packing_type").val(ui.item.l_packing);

            $("#input_CGST").val(ui.item.l_CGST_per);
            $("#input_SGST").val(ui.item.l_SGST_per);
            $("#input_HSNCODE").val(ui.item.l_HSNCODE);

            $("#input_company_name").val(ui.item.l_company_name);
        }
    });

    

    $('#btn_update_stock').click(function() {
        $.post('/index.php/product_stock_master/product_master_update/' + $('#product_id').val(), $(
            'form.form1').serialize(), function(data) {
            if (data.is_update_stock == 0) {
                notify('error', 'Please Attention', data.show_text);
            } else {
                notify('success', 'Please Attention', 'Update Successfully');
                load_form_div('/product_stock_master/NewProduct', 'searchresult');
            }
        }, 'json');
    });

});
</script>
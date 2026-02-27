<div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <div class="ui-widget">
                                    <label for="tags">Product Search: </label>
                                    <input class="form-control input-sm" name="input_drug" id="input_drug"
                                        placeholder="Like Item Code , Item Name" type="text">
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted" name="input_product_code" id="input_product_code">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <input type="hidden" id="l_ssno" name="l_ssno" />
                            <input type="hidden" id="purchase_id" name="purchase_id" />
                            <p class="text-red">
                                <span class="text-green" name="input_product_name" id="input_product_name">
                                </span>
                                <span class="text-light-blue" name="input_batch" id="input_batch">
                                </span>
                                <span class="text-lead" name="input_product_mrp" id="input_product_mrp">
                                </span>
                                <span class="text-yellow" name="stock_product_qty" id="stock_product_qty">
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Unit Rate </label>
                                <input class="form-control input-sm" name="input_product_unit_rate"
                                    id="input_product_unit_rate" placeholder="Unit Rate" autocomplete="off" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Qty </label>
                                <input class="form-control number input-sm" name="input_product_qty"
                                    id="input_product_qty" placeholder="Qty Like No. of Tab." type="text" value=0
                                    autocomplete="off" />
                                <input type="hidden" id="hid_c_qty" name="hid_c_qty" value="0" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Batch No.</label>
                                <input class="form-control varchar input-sm" name="input_batch_no" id="input_batch_no"
                                    placeholder="000000" type="text"   />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Exp.Date</label>
                                <input class="form-control varchar input-sm" name="input_expiry_dt" id="input_expiry_dt"
                                     type="date"  autocomplete="off" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <button type="button" class="btn btn-primary" id="additem"
                                    onclick="remove_custom_item()">Add</button>
                            </div>
                        </div>
                    </div>
<script>
    $(document).ready(function() {
    var cache = {};

    $("#input_drug").autocomplete({
        source: function(request, response) {
            $.getJSON("Medical_backpanel/get_drug", request, function(data, status, xhr) {
                response(data);
            });
        },
        minLength: 1,
        autofocus: true,
        select: function(event, ui) {
            $("#input_product_code").html('| Product Code:' + ui.item.l_item_code);
            $("#input_product_name").html('Name:' + ui.item.value);
            $("#l_ssno").val(ui.item.l_ss_no);
            $("#input_batch").html(' | Batch No.:' + ui.item.l_Batch + ' | Exp.Dt: ' + ui.item
                .l_Expiry);
            $("#input_product_mrp").html(' | MRP:' + ui.item.l_mrp + ' | Unit Rate :' + ui.item
                .l_unit_rate);
            $("#input_product_unit_rate").val(ui.item.l_unit_rate);
            $("#purchase_id").val(ui.item.l_purchase_id);
            $("#hid_c_qty").val(ui.item.l_c_qty);
            $("#input_product_qty").val(ui.item.l_c_qty);

            $("#input_batch_no").val(ui.item.l_Batch);
            $("#input_expiry_dt").val(ui.item.l_Expiry);
            
            $("#stock_product_qty").html(' |Qty : ' + ui.item.l_c_qty + ' |Pak :' + ui.item
                .l_packing);
            }
        });
    

        $("#input_batch").autocomplete({
            source: function(request, response) {
                
                $.getJSON("Medical_backpanel/get_batch/", request, function(data, status, xhr) {
                    response(data);
                });
            },
            minLength: 1,
            autofocus: true,
            select: function(event, ui) {
                $("#input_product_name").html('Name:' + ui.item.value);
                }
        });
    
    });

</script>
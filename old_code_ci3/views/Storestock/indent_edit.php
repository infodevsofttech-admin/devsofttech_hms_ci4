<section class="content-header">
    <h1>
        Indent No. <?=$invoice_stock_master[0]->indent_code?>
        <small>
            <a
                href="javascript:load_form_div('/Storestock/edit_invoice_edit/<?=$invoice_stock_master[0]->id ?>','maindiv');">Edit</a>
        </small>
    </h1>
</section>
<section class="content">
    <?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
    <div class="col-md-8" style="padding: 5px;">
        <div class="box box-danger">
            <div class="box-header">
                <div class="row">
                    <input type="hidden" id="location_type" name="location_type" value="<?=$invoice_stock_master[0]->location_id ?>" />
                    <input type="hidden" id="location_id" name="location_id" value="<?=$invoice_stock_master[0]->location_id ?>" />
                    <input type="hidden" id="indent_id" name="indent_id" value="<?=$invoice_stock_master[0]->id ?>" />
                    <?php if($invoice_stock_master[0]->location_id>0) { ?>
                    <div class="col-md-12">
                        <p><strong>Location Name :</strong>
                            <?=$invoice_stock_master[0]->issued_name?>
                            <strong>/ Indent No. :</strong><?=$invoice_stock_master[0]->indent_code?>
                            <strong>/ Date :</strong> <?=MysqlDate_to_str($invoice_stock_master[0]->indent_date)?>
                        </p>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <div class="box-body">
                <div class="col-md-12">
                    <div class="row ">
                        <div id="show_item_list">
                            <?=$content?>
                        </div>
                    </div>

                    <hr />
                    <div class="row ">
                        <div id="show_add_item_list">
                        </div>
                    </div>
                    <!--- Sale Form  -->
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
                            <input type="hidden" id="item_code" name="item_code" />
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
                                <label>Disc %</label>
                                <input class="form-control number input-sm" name="input_disc" id="input_disc"
                                    placeholder="Discount %" type="text" value=0 autocomplete="off" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <button type="button" class="btn btn-primary" id="additem"
                                    onclick="add_item_invoice()">Add</button>
                            </div>
                        </div>
                    </div>
                    <!------Sale End ---->
                </div>
            </div>
            <div class="box-footer">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <button type="button" class="btn btn-success" id="finalinvoice">Final Invoice</button>
                        </div>
                    </div>
                    <div class="col-md-10">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4" style="padding: 5px;">
        <div class="box box-info">
            <div class="box-header with-border">
                <h4 class="box-title">Sale Medicine</h4>
                <div class="pull-right box-tools">
                    <button type="button" class="btn btn-info btn-flat btn-sm"
                        onclick="load_form_div('/Storestock/Invoice_old/<?=$invoice_stock_master[0]->id ?>','search_body_part');">Search
                        OLD</button>
                </div>
            </div>
            <!-- /.box-header -->
            <!-- form start -->
            <div class="box-body" style="overflow:scroll;" id="search_body_part" name="search_body_part">

            </div>
            <!-- /.box-body -->
            <div class="box-footer" id="search_footer_part" name="search_footer_part">

            </div>
            <!-- /.box-footer -->
        </div>
    </div>

    <?php echo form_close(); ?>
</section>
<!-- /.content -->
<script>
$(document).ready(function() {

    document.title ='Indent :<?=$invoice_stock_master[0]->issued_name?>/<?=$invoice_stock_master[0]->indent_code?>';

    $('#finalinvoice').click(function() {

        var inv_id = $('#indent_id').val();
        var P_Name = $('#P_Name').val();
        var P_Phone = $('#P_Phone').val();
        var pid = $('#pid').val();

        if (pid == 0) {
            if (P_Name == '' || P_Phone == '') {
                notify('Error', 'Please Attention', 'Name and Phone No. Should not be Blank');
                $('#P_Name').focus();
                return false;
            }
        }

        load_form_div('/Storestock/final_invoice/' + inv_id, 'maindiv');
    });
});




function add_item_invoice() {
    var inv_id = $('#indent_id').val();
    var l_ssno = $('#l_ssno').val();

    var input_qty = $('#input_product_qty').val();
    var hid_c_qty = $("#hid_c_qty").val();

    var wait_for_next = $("#wait_for_next").val();

    var csrf_dst_name_value = $("input[name='<?=$this->security->get_csrf_token_name()?>']").val();

    if (wait_for_next > 0) {
        notify('error', 'Please Attention', 'Wait for last process');
        return false;
    }

    var elmnt = document.getElementById("input_product_qty");
    var product_unit_rate = $('#input_product_unit_rate').val();

    if (l_ssno > 0) {
        if (input_qty > 0) {
            //alert(hid_c_qty);

            if (parseInt(input_qty) <= parseInt(hid_c_qty)) {
                $("#wait_for_next").val('1');
                setTimeout(function() {
                    $("#wait_for_next").val('0');
                }, 1000);

                $.post('/index.php/Storestock/add_item/1', {
                        "l_ssno": l_ssno,
                        "qty": input_qty,
                        "product_unit_rate": product_unit_rate,
                        "disc": $('#input_disc').val(),
                        "inv_id": inv_id,
                        "<?php echo $this->security->get_csrf_token_name(); ?>": csrf_dst_name_value
                    }, function(data) {

                         $("#input_product_code").html('');
                        $("#input_product_name").html('');

                        $("#input_batch").html('');
                        $("#input_product_mrp").html('');
                        $("#input_product_unit_rate").val('');
                        $("#l_ssno").val(0);
                        $("#input_drug").val('');

                        $("#input_product_qty").val('0');
                        $("#input_disc").val('0');

                        $("#stock_product_qty").html('');

                        $("#input_drug").focus();
                        elmnt.scrollIntoView();

                        if (data.exist > 0) {
                            notify('Warning', 'Please Attention', 'This Item Already in List');
                        }

                        if (data.insertid > 0) {
                            $('#show_item_list').html(data.content);
                        } else {
                            notify('Error', 'Please Attention', data.error);
                            $("#wait_for_next").val('0');
                        }

                        $("#<?=$this->security->get_csrf_token_name()?>").val(data.csrf_dst_name_value);

                       


                    },
                    'json');
            } else {
                notify('error', 'Please Attention', 'Product Qty. is Less then ' + input_qty);
                $("#input_product_qty").focus();
            }

        } else {

            notify('error', 'Please Attention', 'Qty. is 0');
            $("#input_product_qty").focus();
        }
    } else {

        notify('error', 'Please Attention', 'Select Product First');
        $("#input_drug").focus();
    }
}



function add_item_from_old_list(input_qty, c_qty, l_ssno) {
    var inv_id = $('#indent_id').val();

    var wait_for_next = $("#wait_for_next").val();

    var csrf_dst_name_value = $("input[name='<?=$this->security->get_csrf_token_name()?>']").val();

    if (wait_for_next > 0) {
        notify('error', 'Please Attention', 'Wait for last process');
        return false;
    }

    var elmnt = document.getElementById("input_product_qty");
    var product_unit_rate = $('#input_product_unit_rate').val();

    if (l_ssno > 0) {
        if (input_qty > 0) {
            //alert(hid_c_qty);

            if (parseInt(input_qty) <= parseInt(hid_c_qty)) {
                $("#wait_for_next").val('1');
                setTimeout(function() {
                    $("#wait_for_next").val('0');
                }, 1000);

                $.post('/index.php/Storestock/add_item/1', {
                        "l_ssno": l_ssno,
                        "qty": input_qty,
                        "product_unit_rate": product_unit_rate,
                        "disc": $('#input_disc').val(),
                        "inv_id": inv_id,
                        "<?php echo $this->security->get_csrf_token_name(); ?>": csrf_dst_name_value
                    }, function(data) {

                        if (data.insertid > 0) {
                            $('#show_item_list').html(data.content);
                        } else {
                            notify('Error', 'Please Attention', data.error);
                            //$("#wait_for_next").val('0');
                        }

                        $("#<?=$this->security->get_csrf_token_name()?>").val(data.csrf_dst_name_value);

                    },
                    'json');
            } else {
                notify('error', 'Please Attention', 'Product Qty. is Less then ' + input_qty);
                $("#input_product_qty").focus();
            }

        } else {

            notify('error', 'Please Attention', 'Qty. is 0');
            $("#input_product_qty").focus();
        }
    } else {

        notify('error', 'Please Attention', 'Select Product First');
        $("#input_drug").focus();
    }
}

function remove_item_invoice(itemid) {
    var wait_for_next = $("#wait_for_next").val();
    var csrf_dst_name_value = $("input[name='<?=$this->security->get_csrf_token_name()?>']").val();

    if (wait_for_next > 0) {
        alert('wait......');
        return false;
    }

    $.post('/index.php/Storestock/add_item/0', {
        "itemid": itemid,
        "inv_id": $('#med_invoice_id').val(),
        "<?php echo $this->security->get_csrf_token_name(); ?>": csrf_dst_name_value
    }, function(data) {
        $('#show_item_list').html(data.content);
        $("#<?=$this->security->get_csrf_token_name()?>").val(data.csrf_dst_name_value);
    }, 'json');
}

function update_qty(itemid) {
    var u_qty = $('#input_qty_' + itemid).val();
    var old_qty = $('#hid_oldqty_' + itemid).val();

    var wait_for_next = $("#wait_for_next").val();
    var csrf_dst_name_value = $("input[name='<?=$this->security->get_csrf_token_name()?>']").val();

    if (wait_for_next > 0) {
        alert('wait......');
        return false;
    }
    $("#wait_for_next").val('1');

    setTimeout(function() {
        $("#wait_for_next").val('0');
    }, 1000);

    $.post('/index.php/Storestock/add_item/2', {
        "itemid": itemid,
        "u_qty": u_qty,
        "<?php echo $this->security->get_csrf_token_name(); ?>": csrf_dst_name_value
    }, function(data) {
        if (data.insertid > 0) {
            $('#show_item_list').html(data.content);

        } else {

            notify('error', 'Please Attention', data.error);
            $('#input_qty_' + itemid).val(old_qty);
            $("#wait_for_next").val('0');
        }
        $("#<?=$this->security->get_csrf_token_name()?>").val(data.csrf_dst_name_value);
    }, 'json');

    
}

function remove_item_add(itemid) {
    var rqty = $('#input_qty_' + itemid).val();

    var inv_id = $('#indent_id').val();

    var wait_for_next = $("#wait_for_next").val();
    var csrf_dst_name_value = $("input[name='<?=$this->security->get_csrf_token_name()?>']").val();

    if (wait_for_next > 0) {
        alert('wait......');
        return false;
    }

    $.post('/index.php/Storestock/add_remove_item', {
        "itemid": itemid,
        "inv_id": inv_id,
        "rqty": rqty,
        "<?php echo $this->security->get_csrf_token_name(); ?>": csrf_dst_name_value
    }, function(data) {
        if (data.update == 0) {
            notify('error', 'Please Attention', data.msg_text);
        } else {
            notify('success', 'Please Attention', data.msg_text);
            $('#show_item_list').html(data.content);
        }

    }, 'json');
}

$(document).ready(function() {
    var cache = {};

    $("#input_drug").autocomplete({
        source: function(request, response) {
            $.getJSON("Storestock/get_drug", request, function(data, status, xhr) {
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
            $("#item_code").val(ui.item.item_code);
            $("#hid_c_qty").val(ui.item.l_c_qty);
            $("#stock_product_qty").html(' |Qty : ' + ui.item.l_c_qty + ' |Pak :' + ui.item
                .l_packing);
        }
    });
});

document.getElementById("input_drug").accessKey = "s";
document.getElementById("additem").accessKey = "i";
</script>
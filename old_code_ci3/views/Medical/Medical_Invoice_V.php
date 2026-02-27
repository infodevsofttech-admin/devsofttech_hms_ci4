<section class="content-header">
    <h1>
        Medical Invoice No. <?=$invoiceMaster[0]->inv_med_code?>
        <small>
            <a
                href="javascript:load_form_div('/Medical/edit_invoice_edit/<?=$invoiceMaster[0]->id ?>','maindiv');">Edit</a>
        </small>
    </h1>
</section>
<section class="content">
    <?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
    <div class="col-md-8" style="padding: 5px;">
        <div class="box box-danger">
            <div class="box-header">
                <div class="row">
                    <?php 
					$ipd_no_title='';
				?>
                    <input type="hidden" id="pid" name="pid" value="<?=$invoiceMaster[0]->patient_id ?>" />
                    <input type="hidden" id="med_invoice_id" name="med_invoice_id"
                        value="<?=$invoiceMaster[0]->id ?>" />
                    <?php if($invoiceMaster[0]->patient_id==0) { ?>
                    <table class="table">
                        <tr>
                            <td>Patient Name : <input id="P_Name" name="P_Name"
                                    value="<?=$invoiceMaster[0]->inv_name ?>" required></td>
                            <td>Phone No : <input id="inv_phone_number" name="inv_phone_number"
                                    value="<?=$invoiceMaster[0]->inv_phone_number ?>" ></td>
                            <td>Doctor Name :
                                <select id="doc_name_id" name="doc_name_id">
                                    <option value='0' <?=combo_checked('0',$invoiceMaster[0]->doc_id)?>>From Other
                                        Hospital</option>
                                    <?php 
									foreach($doclist as $row)
									{ 
										echo '<option value='.$row->id.'  '.combo_checked($row->id,$invoiceMaster[0]->doc_id).'  >'.$row->p_fname.'</option>';
									}
									?>
                                </select>
                            </td>
                            <td> </td>
                            <td>Other Doctor:
                                <input class="varchar" name="input_doc_name" id="input_doc_name"
                                    placeholder="Doctor Name" value="<?=$invoiceMaster[0]->doc_name?>" type="text" />
                            </td>
                            <td> </td>
                            <td><button type="button" class="btn btn-primary btn-sm" id="btn_update"
                                    onclick="update_name_phone()">Update Name </button></td>
                        </tr>
                    </table>
                    <?php }else{ ?>
                    <div class="col-md-12">
                        <p><strong>Name :</strong>
                            <?=$invoiceMaster[0]->inv_name?>
                            <strong>/ P Code :</strong><?=$invoiceMaster[0]->patient_code?>
                            <strong>/ Invoice No. :</strong><?=$invoiceMaster[0]->inv_med_code?>
                            <strong>/ Date :</strong> <?=MysqlDate_to_str($invoiceMaster[0]->inv_date)?>
                            <?php 
							
							if($invoiceMaster[0]->ipd_id > 0) { 
								$ipd_no_title="/IPD:".$invoiceMaster[0]->ipd_id;
						?>

                            <strong>IPD Code :</strong>
                            <a
                                href="javascript:load_form_div('/Medical/list_med_inv/<?=$ipd_master[0]->id ?>','maindiv');">
                                <?=$ipd_master[0]->ipd_code?>
                            </a>
                            <strong>Admit Date : </strong><?=$ipd_list[0]->str_register_date ?>
                            <?=$ipd_list[0]->reg_time ?>
                            <strong>/ Doctor :</strong><?=$ipd_list[0]->doc_name?>
                            <strong>/ TPA-Org. :</strong><?=$ipd_list[0]->admit_type?>
                            <strong>/ Bill Type
                                :</strong><?=($invoiceMaster[0]->ipd_credit)?'Credit To Hospital':'CASH/Direct'?>
                            <?php }elseif($invoiceMaster[0]->case_id > 0){  ?>
                            <strong>/ Org. Case ID :<a
                                    href="javascript:load_form_div('/Medical/list_med_orginv/<?=$OCaseMaster[0]->id ?>/<?=$invoiceMaster[0]->store_id?>','maindiv');">
                                    <?=$OCaseMaster[0]->case_id_code ?>
                                </a>
                                <?php }else{  ?>
                                <table class="table">
                                    <tr>
                                        <td>Doctor Name :
                                            <select id="doc_name_id" name="doc_name_id">
                                                <option value='0' <?=combo_checked('0',$invoiceMaster[0]->doc_id)?>>From
                                                    Other Hospital</option>
                                                <?php 
									foreach($doclist as $row)
									{ 
										echo '<option value='.$row->id.'  '.combo_checked($row->id,$invoiceMaster[0]->doc_id).'  >'.$row->p_fname.'</option>';
									}
									?>
                                            </select>
                                        </td>
                                        <td> </td>
                                        <td>Other Doctor:
                                            <input class="varchar" name="input_doc_name" id="input_doc_name"
                                                placeholder="Doctor Name" value="<?=$invoiceMaster[0]->doc_name?>"
                                                type="text" />
                                        </td>
                                        <td> </td>
                                        <td><button type="button" class="btn btn-primary btn-sm" id="btn_update"
                                                onclick="update_doctor()">Update Name </button></td>
                                    </tr>
                                </table>


                                <?php } ?>
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
                            <input type="hidden" id="hid_expiry_alert" name="hid_expiry_alert" />            
                            
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
                                    id="input_product_unit_rate" placeholder="Unit Rate" autocomplete="off"   />
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
                                <?php if($invoiceMaster[0]->ipd_id > 0){ 
                                    $readonly="Readonly"; 
                                }else{
                                    $readonly="Readonly";
                                }   
                                ?>
                                <input class="form-control number input-sm" name="input_disc" id="input_disc"
                                    placeholder="Discount %" type="text" value=0 autocomplete="off" <?php echo $readonly ;?> />
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
    <?php if($invoiceMaster[0]->ipd_id == 0){ ?>
        <div class="box box-info">
            <div class="box-header with-border">
                
                <h4 class="box-title">Sale Medicine</h4>
                <div class="pull-right box-tools">
                    <button type="button" class="btn btn-info btn-flat btn-sm"
                        onclick="load_form_div('/Medical/Invoice_old/<?=$invoiceMaster[0]->id ?>','search_body_part');">Search
                        OLD</button>
                    <button type="button" class="btn btn-info btn-flat btn-sm" onclick="load_form_div('/Medical/Med_Return_invoice_search/<?=$invoiceMaster[0]->id ?>','search_body_part');">Search Panel</button>
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
        <?php } ?>

    </div>

    <?php echo form_close(); ?>
</section>
<!-- /.content -->
<script>
$(document).ready(function() {

    document.title =  'Med-Inv.:<?=$invoiceMaster[0]->inv_name?><?=$ipd_no_title?>/<?=$invoiceMaster[0]->inv_med_code?>';
    
    
        

    $('#finalinvoice').click(function() {

        var inv_id = $('#med_invoice_id').val();
        var P_Name = $('#P_Name').val();
        var P_Phone = $('#P_Phone').val();
        var pid = $('#pid').val();

        if (pid == 0) {
            if (P_Name == '') {
                notify('Error', 'Please Attention', 'Name  Should not be Blank');
                $('#P_Name').focus();
                return false;
            }
        }

        load_form_div('/Medical/final_invoice/' + inv_id, 'maindiv');
    });
});

function update_name_phone() {
    var P_Name = $('#P_Name').val();
    var P_Phone = $('#inv_phone_number').val();
    var med_invoice_id = $('#med_invoice_id').val();

    var doc_id = $('#doc_name_id').val();
    var doc_name = $('#input_doc_name').val();

    if (P_Name == '') {
        notify('Error', 'Please Attention', 'Name Should not be Blank');
        return false;
    }

    var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

    if (confirm('Are you Sure Update Patient Name')) {
        $.post('/index.php/Medical/update_name_phone', {
            "pid": 0,
            "customer_type": 0,
            "P_Name": P_Name,
            "P_Phone": P_Phone,
            "med_invoice_id": med_invoice_id,
            "doc_id": doc_id,
            "doc_name": doc_name,
            '<?=$this->security->get_csrf_token_name()?>': csrf_value
        }, function(data) {
            notify('success', 'Please Attention', data.remark);
        }, 'json');
    }
}

function update_doctor() {
    var med_invoice_id = $('#med_invoice_id').val();

    var doc_id = $('#doc_name_id').val();
    var doc_name = $('#input_doc_name').val();

    var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

    if (confirm('Are you Sure Update Doctor Name')) {
        $.post('/index.php/Medical/update_doctor', {
            "med_invoice_id": med_invoice_id,
            "doc_id": doc_id,
            "doc_name": doc_name,
            '<?=$this->security->get_csrf_token_name()?>': csrf_value
        }, function(data) {
            notify('success', 'Please Attention', data.remark);
        }, 'json');
    }
}


function add_item_invoice() {
    var inv_id = $('#med_invoice_id').val();
    var l_ssno = $('#l_ssno').val();

    var input_qty = $('#input_product_qty').val();
    var hid_c_qty = $("#hid_c_qty").val();

    var expiry_alert = $("#hid_expiry_alert").val();

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

                if(expiry_alert<1){
                    notify('error', 'Please Attention', 'Product Expired');
                    return false;
                }


                $.post('/index.php/Medical/add_item/1', {
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
    var inv_id = $('#med_invoice_id').val();

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

                $.post('/index.php/Medical/add_item/1', {
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

    

    $.post('/index.php/Medical/add_item/0', {
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

    $.post('/index.php/Medical/add_item/2', {
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

    var inv_id = $('#med_invoice_id').val();

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

    $.post('/index.php/Medical/add_remove_item', {
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
            $.getJSON("Medical/get_drug", request, function(data, status, xhr) {
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
            $("#hid_expiry_alert").val(ui.item.expiry_alert);
            $("#item_code").val(ui.item.item_code);
            $("#hid_c_qty").val(ui.item.l_c_qty);
            $("#stock_product_qty").html(' |Qty : ' + ui.item.l_c_qty + ' |Pak :' + ui.item
                .l_packing);
        }
    })
    .autocomplete( "instance" )._renderItem = function( ul, item ) {
        if(item.l_new_stock >0){
            //item.label= " &#127381; " + item.label ;
            item.label= " 💡 " + item.label ;
        }

        if(item.expiry_alert <2){
            return $( "<li class='ui-state-disabled'>" )
            .append( "<div>" + item.label + "<br>" + item.desc + "</div>" )
            .appendTo( ul );
        }else{
            return $( "<li>" )
            .append( "<div>" + item.label + "<br>" + item.desc + "</div>" )
            .appendTo( ul );
        }
    };
});

//load_form_div('/Medical/Invoice_old/<?=$invoiceMaster[0]->id ?>','search_body_part');
document.getElementById("input_drug").accessKey = "s";
document.getElementById("additem").accessKey = "i";
</script>
<section class="content-header">
    <h1>Refund Amount</h1>
</section>
<?php echo form_open('', array('role'=>'form','class'=>'form1')); ?>
<section class="invoice">
    <div class="row invoice-info">
        <div class="col-sm-4 invoice-col">
            <input type="hidden" value="<?=$refund_order[0]->id ?>" id="refund_id" name="refund_id" />
            <b>CODE :</b> <?=$refund_order[0]->refund_type_code ?><br>
            <b>Patient Name :</b> <?=$refund_order[0]->patient_name ?>
        </div>
        <div class="col-sm-4 invoice-col">
            <b>Refund Reason :</b> <?=$refund_order[0]->refund_type_reason ?><br>
            <b>Refund Amount :</b> Rs. <?=$refund_order[0]->refund_amount ?>
        </div>
        <div class="col-sm-4 invoice-col">
            <b>Approved By :</b> <?=$refund_order[0]->approved_by ?><br>
            <b>Date Time :</b> Rs. <?=$refund_order[0]->approved_datetime ?>
        </div>
    </div>
</section>
<?php if($refund_order[0]->refund_process==0) { ?>
<section class="invoice">
    <div class="row invoice-info">
        <div class="col-sm-4 invoice-col">
            <div class="form-group">
                <label>Receiver Name</label>
                <input class="form-control" name="input_name" id="input_name" placeholder="Full Name" type="text"
                    autocomplete="off"><br>
            </div>
        </div>
        <div class="col-sm-4 invoice-col">
            <div class="form-group">
                <label>Receiver Phone Number</label>
                <input class="form-control number" name="input_phone" id="input_amount" placeholder="Phone Number"
                    type="text" autocomplete="off"><br>
            </div>
        </div>
        <div class="col-md-4">
            <button type="button" class="btn btn-primary" id="btn_update_refund">Refund Amount</button>
        </div>
    </div>
</section>
<?php echo form_close(); ?>
<?php }else{  ?>
<section class="invoice">
    <div class="row invoice-info">
        <div class="col-sm-6 invoice-col">
            <div class="form-group">
                <label>Refund Done</label>

            </div>
        </div>
    </div>
</section>
<?php } ?>
<!-- /.content -->
<div class="clearfix"></div>

<script>
$(document).ready(function() {

    $('#btn_update_refund').click(function(e) {
        var r_name = $('#input_name').val();
        var r_phone = $('#input_phone').val();
        var r_id = $('#refund_id').val();
        var csrf_value = $('input[name=<?=$this->security->get_csrf_token_name()?>]').val();

        if (confirm("Are you sure process this Refund ")) {
            $.post('/index.php/Invoice/refund_process', {
                "r_id": r_id,
                "r_name": r_name,
                "r_phone": r_phone,
                "<?=$this->security->get_csrf_token_name()?>": csrf_value
            }, function(data) {
                if (data.update == 0) {
                    $('div.jsError').html(data.showcontent);
                    setTimeout(enable_btn, 5000);
                    load_form('/Invoice/refund_form/' + r_id);
                } else {
                    load_form('/Invoice/refund_form/' + r_id);
                }
            }, 'json');
        } else {
            setTimeout(enable_btn, 5000);
            return false;
        }

    });
});
</script>
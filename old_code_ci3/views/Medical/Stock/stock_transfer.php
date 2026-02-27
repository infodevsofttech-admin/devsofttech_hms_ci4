<section class="content-header">
    <h1>
        Purchase Item Transfer
        <small></small>
    </h1>
</section>
<section class="content">
    <div class="box box-danger">
        <div class="box-header">

        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>From Purchase SSNO </label>
                        <input class="form-control input-sm" name="input_from_ssno" id="input_from_ssno" placeholder="SS No" autocomplete="off" onchange="from_ssno_change()" />
                        <input type="hidden" id="from_ssno" name="from_ssno">
                        <input type="hidden" id="from_ssno_sale_qty" name="from_ssno_sale_qty">
                        <input type="hidden" id="from_ssno_item_id" name="from_ssno_item_id">
                    </div>
                </div>
                <div class="col-md-8" id="info_from_ssno" name="info_from_ssno">

                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>To Purchase SSNO </label>
                        <input class="form-control input-sm" name="input_to_ssno" id="input_to_ssno" placeholder="SS No" autocomplete="off" onchange="to_ssno_change()" />
                        <input type="hidden" id="to_ssno" name="to_ssno">
                        <input type="hidden" id="to_ssno_cur_qty" name="to_ssno_cur_qty">
                        <input type="hidden" id="to_ssno_item_id" name="to_ssno_item_id">
                    </div>
                </div>
                <div class="col-md-8" id="info_to_ssno" name="info_to_ssno">

                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Qty of Product in unit </label>
                        <input class="form-control input-sm" name="input_transfer_qty" id="input_transfer_qty" placeholder="Qty" autocomplete="off" />

                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <div class="col-md-4">
                <div class="form-group">
                <button type="button" class="btn btn-danger" id="btn_transfer" onclick="transfer_qty()">Tranfer Sale</button>
                </div>
            </div>
        </div>
</section>
<script>
    function from_ssno_change() {
        var ssno = $('#input_from_ssno').val();
        var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

        $.post('/index.php/Medical_backpanel/ssno_info/' + ssno, {
            "ssno": ssno,
            '<?= $this->security->get_csrf_token_name() ?>': csrf_value
        }, function(data) {
            if (data.ssno > 0) {
                $('#info_from_ssno').html('Item Name :' + data.Item_name + ' /Current Qty : ' + data.total_current_unit + '/Sale Qty :' + data.total_sale_unit);
                $('#from_ssno').val(data.ssno);
                $('#from_ssno_sale_qty').val(data.total_sale_unit);
                $('#from_ssno_item_id').val(data.item_code);
            } else {
                $('#info_from_ssno').html('No Record Found');
                $('#from_ssno').val(0);
                $('#from_ssno_sale_qty').val(0);
                $('#from_ssno_item_id').val(0);
            }
        }, 'json');
    }

    function to_ssno_change() {
        var ssno = $('#input_to_ssno').val();
        var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

        $.post('/index.php/Medical_backpanel/ssno_info/' + ssno, {
            "ssno": ssno,
            '<?= $this->security->get_csrf_token_name() ?>': csrf_value
        }, function(data) {
            if (data.ssno > 0) {
                $('#info_to_ssno').html('Item Name :' + data.Item_name + ' /Current Qty : ' + data.total_current_unit + '/Sale Qty :' + data.total_sale_unit);
                $('#to_ssno').val(data.ssno);
                $('#to_ssno_cur_qty').val(data.total_current_unit);
                $('#to_ssno_item_id').val(data.item_code);
            } else {
                $('#info_to_ssno').html('No Record Found');
                $('#to_ssno').val(0);
                $('#to_ssno_cur_qty').val(0);
                $('#to_ssno_item_id').val(0);
            }
        }, 'json');
    }

    function transfer_qty()
    {
        var from_ssno = $('#input_from_ssno').val();
        var to_ssno = $('#input_to_ssno').val();

        var from_ssno_item_id = $('#from_ssno_item_id').val();
        var to_ssno_item_id = $('#to_ssno_item_id').val();

        var from_total_sale_unit = Number($('#from_ssno_sale_qty').val());
        var to_ssno_cur_qty = Number($('#to_ssno_cur_qty').val());

        var trasfer_qty = Number($('#input_transfer_qty').val());

        var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

        if(trasfer_qty==''){
            alert('Transfer Qty should be greater then 0');
            return false;
        }

        if(from_ssno==to_ssno){
            alert('SS No. Should be Different');
            return false;
        }

        if(from_ssno_item_id != to_ssno_item_id){
            alert('Product Should be same');
            return false;
        }
        
        if(trasfer_qty>from_total_sale_unit){
            alert('Transfer Qty should be smaller or equal then FromSSNO Sale qty');
            return false;
        }

        if(trasfer_qty>to_ssno_cur_qty){
            alert('Transfer Qty should be smaller then Transfer Sale');
            return false;
        }

        $.post('/index.php/Medical_backpanel/ssno_transfer', {
            "from_ssno": from_ssno,
            "to_ssno": to_ssno,
            "tqty": trasfer_qty,
            '<?= $this->security->get_csrf_token_name() ?>': csrf_value
        }, function(data) {
            from_ssno_change();
            to_ssno_change();
            alert('Transfer Done');
        }, 'json');



    }
</script>
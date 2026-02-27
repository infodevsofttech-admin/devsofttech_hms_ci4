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
                        <label>From Product ID (Master Product)</label>
                        <input type="number" class="form-control input-sm number" name="input_from_product_id" id="input_from_product_id" placeholder="Product ID" autocomplete="off" onchange="from_product_id_change()" />
                        <input type="hidden" id="from_product_id" name="from_product_id">
                    </div>
                </div>
                <div class="col-md-8" id="info_from_product_id" name="info_from_product_id">

                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>To Product ID </label>
                        <input type="number"  class="form-control input-sm number" name="input_to_product_id" id="input_to_product_id" placeholder="Product ID" autocomplete="off" onchange="to_product_id_change()" />
                        <input type="hidden" id="to_product_id" name="to_product_id">
                    </div>
                </div>
                <div class="col-md-8" id="info_to_product_id" name="info_to_product_id">

                </div>
            </div>
        </div>
        <div class="box-footer">
            <div class="col-md-4">
                <div class="form-group">
                <button type="button" class="btn btn-danger" id="btn_transfer" onclick="product_merged()">Merge Product</button>
                </div>
            </div>
        </div>
</section>
<script>
    function from_product_id_change() {
        var prod_id = $('#input_from_product_id').val();
        var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

        $.post('/index.php/Medical_backpanel/product_info/' + prod_id, {
            "product_id": prod_id,
            '<?= $this->security->get_csrf_token_name() ?>': csrf_value
        }, function(data) {
            if (data.product_id > 0) {
                $('#info_from_product_id').html('Item Name :' + data.product_name + ' /Formulation : ' + data.formulation + '/Generic Name :' + data.genericname);
                $('#from_product_id').val(data.product_id);
                
            } else {
                $('#info_from_product_id').html('No Record Found');
                $('#from_product_id').val(0);
            }
        }, 'json');
    }

    function to_product_id_change() {
        var prod_id = $('#input_to_product_id').val();
        var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

        $.post('/index.php/Medical_backpanel/product_info/' + prod_id, {
            "product_id": prod_id,
            '<?= $this->security->get_csrf_token_name() ?>': csrf_value
        }, function(data) {
            if (data.product_id > 0) {
                $('#info_to_product_id').html('Item Name :' + data.product_name + ' /Formulation : ' + data.formulation + '/Generic Name :' + data.genericname);
                $('#to_product_id').val(data.product_id);
            } else {
                $('#info_to_product_id').html('No Record Found');
                $('#to_product_id').val(0);
            }
        }, 'json');
    }

    function product_merged()
    {
        var from_product_id = $('#from_product_id').val();
        var to_product_id = $('#to_product_id').val();

        var csrf_value = $('input[name=<?= $this->security->get_csrf_token_name() ?>]').val();

        if(from_product_id==0 && to_product_id==0 ){
            alert('Select Product');
            return false;
        }
        if(confirm('Are you sure to Merge Product'))
        {
            $.post('/index.php/Medical_backpanel/product_merged', {
                "from_product_id": from_product_id,
                "to_product_id": to_product_id,
                '<?= $this->security->get_csrf_token_name() ?>': csrf_value
            }, function(data) {
                from_product_id_change();
                to_product_id_change();
                alert('Merge Done');
            }, 'json');
        }

    }
</script>
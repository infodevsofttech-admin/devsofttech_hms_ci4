<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">IPD Charges - Add New</h3>
        <div class="card-tools ms-auto">
            <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('item-ipd/search') ?>','maindiv','IPD Charges');">
                <i class="bi bi-arrow-left"></i>
                Back to List
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="jsError"></div>
        <form action="<?= base_url('item-ipd/create') ?>" method="post" role="form" class="form1">
            <?= csrf_field() ?>
            <input type="hidden" value="0" id="p_id" name="p_id" />
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Charge Name</label>
                    <input class="form-control" name="input_Item_name" placeholder="Item Name" value="" type="text" autocomplete="off">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Charges Group</label>
                    <select class="form-select" name="Item_Type" id="Item_Type">
                        <?php foreach ($data_item_type as $row) : ?>
                            <option value="<?= esc($row->itype_id ?? '') ?>"><?= esc($row->group_desc ?? '') ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Amount</label>
                    <input class="form-control" name="input_amount" placeholder="amount" value="" type="text" autocomplete="off">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary" id="btn_update">Add Record</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('form.form1').on('submit', function(form) {
            form.preventDefault();
            $.post('<?= base_url('item-ipd/create') ?>', $('form.form1').serialize(), function(data) {
                if (data.insertid == 0) {
                    if (typeof notify === 'function') {
                        notify('error', 'Please Attention', data.error_text || 'Please Check');
                    }
                } else {
                    load_form_div('<?= base_url('item-ipd/item-record') ?>/' + data.insertid, 'maindiv');
                }
                updateCsrf(data);
            }, 'json');
        });
    });

    function updateCsrf(data) {
        if (!data || !data.csrfName || !data.csrfHash) {
            return;
        }
        var input = document.querySelector('input[name="' + data.csrfName + '"]');
        if (input) {
            input.value = data.csrfHash;
        }
    }
</script>

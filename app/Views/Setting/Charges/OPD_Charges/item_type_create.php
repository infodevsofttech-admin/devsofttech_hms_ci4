<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">OPD Charges Group - Add New</h3>
        <div class="card-tools ms-auto">
            <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('item/search-itemtype') ?>','maindiv','OPD Charge Master');">
                <i class="bi bi-arrow-left"></i>
                Back to List
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="jsError"></div>
        <form action="<?= base_url('item/create-type') ?>" method="post" role="form" class="form1">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Charges Group Name</label>
                    <input class="form-control" name="input_Item_type" placeholder="Item Name" type="text" autocomplete="off">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Is IPD or OPD</label>
                    <select class="form-select" name="Item_Type_ipd" id="Item_Type_ipd">
                        <option value="0">Both OPD and IPD</option>
                        <option value="1">Only OPD</option>
                        <option value="2">Only IPD</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
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
            $.post('<?= base_url('item/create-type') ?>', $('form.form1').serialize(), function(data) {
                if (data.insertid == 0) {
                    if (typeof notify === 'function') {
                        notify('error', 'Please Attention', data.error_text || 'Please Check');
                    }
                } else {
                    load_form_div('<?= base_url('item/itemtype-record') ?>/' + data.insertid, 'maindiv');
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

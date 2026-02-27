<?php if (! empty($data_item)) : ?>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h3 class="card-title mb-0">IPD Charge Name: <em><?= esc($data_item[0]->idesc ?? '') ?></em></h3>
        </div>
        <div class="card-body">
            <div class="jsError"></div>
            <form action="<?= base_url('item-ipd/update') ?>" method="post" role="form" class="form1">
                <?= csrf_field() ?>
                <input type="hidden" value="<?= esc($data_item[0]->id ?? '') ?>" id="p_id" name="p_id" />
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Charge Name</label>
                        <input class="form-control" name="input_Item_name" placeholder="Item Name" value="<?= esc($data_item[0]->idesc ?? '') ?>" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Charges Group</label>
                        <select class="form-select" name="Item_Type" id="Item_Type">
                            <?php foreach ($data_item_type as $row) : ?>
                                <option value="<?= esc($row->itype_id ?? '') ?>" <?= combo_checked($data_item[0]->itype ?? '', $row->itype_id ?? '') ?>>
                                    <?= esc($row->group_desc ?? '') ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Amount</label>
                        <input class="form-control" name="input_amount" placeholder="amount" value="<?= esc($data_item[0]->amount ?? '') ?>" type="text" autocomplete="off">
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-8">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="3" name="input_Item_desc" placeholder="Enter ..."><?= esc($data_item[0]->idesc_detail ?? '') ?></textarea>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary" id="btn_update">Update Record</button>
                    </div>
                </div>
            </form>
            <hr/>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Insurance Rates</h5>
                </div>
                <div class="card-body">
                    <div id="incomplist">
                        <?= view('Setting/Charges/IPD_Charges/item_insurance_list', ['data_insurance_item' => $data_insurance_item]) ?>
                    </div>
                    <hr/>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Company Name</label>
                            <select class="form-select" name="ins_company_name" id="ins_company_name">
                                <?php foreach ($data_insurance as $row) : ?>
                                    <option value="<?= esc($row->id ?? '') ?>"><?= esc($row->ins_company_name ?? '') ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Amount</label>
                            <input class="form-control" id="input_amount_1" placeholder="amount" value="<?= esc($data_item[0]->amount ?? '') ?>" type="text" autocomplete="off">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Code</label>
                            <input class="form-control" id="input_item_code" placeholder="Code Like ECHS,AIIMS" type="text" autocomplete="off">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary" id="btn_add_item" onclick="add_item_spec()">Add</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#btn_update').click(function() {
                $.post('<?= base_url('item-ipd/update') ?>', $('form.form1').serialize(), function(data) {
                    if (data.update == 0) {
                        $('div.jsError').html(data.error_text);
                        if (typeof notify === 'function') {
                            notify('error', 'Please Attention', data.error_text || 'Please Check');
                        }
                    } else if (typeof notify === 'function') {
                        notify('success', 'Saved', data.showcontent || 'Data Saved successfully');
                    }
                    if (typeof window.refreshChargeList === 'function') {
                        window.refreshChargeList();
                    }
                    updateCsrf(data);
                }, 'json');
            });
        });

        function getCsrfPair() {
            var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
            if (!input) {
                return { name: '<?= csrf_token() ?>', value: '<?= csrf_hash() ?>' };
            }
            return { name: input.getAttribute('name'), value: input.value };
        }

        function updateCsrf(data) {
            if (!data || !data.csrfName || !data.csrfHash) {
                return;
            }
            var input = document.querySelector('input[name="' + data.csrfName + '"]');
            if (input) {
                input.value = data.csrfHash;
            }
        }

        function add_item_spec() {
            var csrf = getCsrfPair();
            $.post('<?= base_url('item-ipd/insurance/add') ?>', {
                "ins_company_name": $('#ins_company_name').val(),
                "input_amount": $('#input_amount_1').val(),
                "p_id": $('#p_id').val(),
                "input_item_code": $('#input_item_code').val(),
                "isadd": 1,
                [csrf.name]: csrf.value
            }, function(data) {
                if (data && data.html) {
                    $('#incomplist').html(data.html);
                }
                updateCsrf(data);
            }, 'json');
        }

        function remove_item_spec(item_id) {
            var csrf = getCsrfPair();
            $.post('<?= base_url('item-ipd/insurance/remove') ?>', {
                "in_remove_id": item_id,
                "p_id": $('#p_id').val(),
                "isadd": 0,
                [csrf.name]: csrf.value
            }, function(data) {
                if (data && data.html) {
                    $('#incomplist').html(data.html);
                }
                updateCsrf(data);
            }, 'json');
        }
    </script>
<?php endif ?>

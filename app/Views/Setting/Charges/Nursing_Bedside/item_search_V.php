<?php
$rows = $rows ?? [];
$insuranceList = $insurance_list ?? [];
$csrfName = csrf_token();
$csrfHash = csrf_hash();
?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Clinical Procedure / Test Master</h3>
        <div class="card-tools ms-auto">
            <button type="button" class="btn btn-primary" id="btn_nursing_item_add">Add New</button>
        </div>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-striped table-sm align-middle" id="nursing_bedside_table">
            <thead>
                <tr>
                    <th style="width: 90px;">Code</th>
                    <th>Item Name</th>
                    <th style="width: 130px;">Type</th>
                    <th style="width: 130px;">Category</th>
                    <th style="width: 100px;" class="text-end">Rate</th>
                    <th style="width: 80px;">Unit</th>
                    <th style="width: 85px;">Billable</th>
                    <th style="width: 70px;">Active</th>
                    <th style="width: 70px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td><?= esc((string) ($row['item_code'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['item_name'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['item_type'] ?? '')) ?></td>
                        <td><?= esc((string) ($row['category'] ?? '')) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($row['default_rate'] ?? 0), 2)) ?></td>
                        <td><?= esc((string) ($row['unit'] ?? '')) ?></td>
                        <td><?= (int) ($row['is_billable'] ?? 0) === 1 ? 'Yes' : 'No' ?></td>
                        <td><?= (int) ($row['is_active'] ?? 0) === 1 ? 'Yes' : 'No' ?></td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm" onclick="nursingBedsideEdit(<?= (int) ($row['item_id'] ?? 0) ?>)">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="nursing_item_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="nursing_item_modal_title">Add Clinical Procedure/Test</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="nursing_item_form" class="row g-2">
                    <input type="hidden" id="nursing_item_id" value="0">
                    <div class="col-md-3"><label class="form-label">Item Code</label><input type="text" class="form-control" id="item_code"></div>
                    <div class="col-md-5"><label class="form-label">Item Name *</label><input type="text" class="form-control" id="item_name" required></div>
                    <div class="col-md-4"><label class="form-label">Item Type *</label><input type="text" class="form-control" id="item_type" required placeholder="Investigation / Procedure"></div>
                    <div class="col-md-4"><label class="form-label">Category</label><input type="text" class="form-control" id="category" placeholder="Lab Test / Cardiac"></div>
                    <div class="col-md-2"><label class="form-label">Rate</label><input type="number" step="0.01" class="form-control" id="default_rate" value="0"></div>
                    <div class="col-md-2"><label class="form-label">Unit</label><input type="text" class="form-control" id="unit" value="Unit"></div>
                    <div class="col-md-2"><label class="form-label">Billable</label><select class="form-select" id="is_billable"><option value="1">Yes</option><option value="0">No</option></select></div>
                    <div class="col-md-2"><label class="form-label">Active</label><select class="form-select" id="is_active"><option value="1">Yes</option><option value="0">No</option></select></div>
                    <div class="col-md-12"><label class="form-label">Description</label><textarea class="form-control" id="description" rows="2"></textarea></div>
                </form>

                <hr>
                <h6 class="mb-2">Insurance-specific Rate & Code</h6>
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label">Insurance Company</label>
                        <select class="form-select" id="insurance_company_id">
                            <?php foreach ($insuranceList as $insRow) : ?>
                                <option value="<?= esc($insRow['id'] ?? '') ?>"><?= esc($insRow['ins_company_name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Rate</label>
                        <input type="number" step="0.01" class="form-control" id="insurance_rate" value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Code</label>
                        <input type="text" class="form-control" id="insurance_code" placeholder="Code">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-primary w-100" id="btn_insurance_add">Add/Update</button>
                    </div>
                    <div class="col-md-12">
                        <div id="nursing_insurance_list_wrap"><?= view('Setting/Charges/Nursing_Bedside/item_insurance_list', ['rows' => []]) ?></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn_nursing_item_save">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var saveUrl = '<?= base_url('nursing-bedside-items/save') ?>';
    var getUrlBase = '<?= base_url('nursing-bedside-items') ?>';
    var insuranceListUrlBase = '<?= base_url('nursing-bedside-items/insurance') ?>';
    var insuranceAddUrl = '<?= base_url('nursing-bedside-items/insurance/add') ?>';
    var insuranceRemoveUrl = '<?= base_url('nursing-bedside-items/insurance/remove') ?>';
    var csrfName = '<?= esc($csrfName) ?>';
    var csrfHash = '<?= esc($csrfHash) ?>';

    function resetForm() {
        $('#nursing_item_id').val('0');
        $('#item_code').val('');
        $('#item_name').val('');
        $('#item_type').val('');
        $('#category').val('');
        $('#default_rate').val('0');
        $('#unit').val('Unit');
        $('#description').val('');
        $('#is_billable').val('1');
        $('#is_active').val('1');
        $('#insurance_rate').val('0');
        $('#insurance_code').val('');
        $('#nursing_insurance_list_wrap').html('Save item first, then add insurance rate/code.');
    }

    function applyCsrf(resp) {
        if (!resp) {
            return;
        }
        csrfName = resp.csrfName || csrfName;
        csrfHash = resp.csrfHash || csrfHash;
    }

    function loadInsuranceList(itemId) {
        if (!itemId || Number(itemId) <= 0) {
            $('#nursing_insurance_list_wrap').html('Save item first, then add insurance rate/code.');
            return;
        }

        $.get(insuranceListUrlBase + '/' + itemId, function(resp) {
            applyCsrf(resp);
            $('#nursing_insurance_list_wrap').html((resp && resp.html) ? resp.html : 'No insurance-specific rate added.');
            refreshInsuranceSelectState();
        }, 'json').fail(function() {
            $('#nursing_insurance_list_wrap').html('Unable to load insurance rates.');
            refreshInsuranceSelectState();
        });
    }

    function refreshInsuranceSelectState() {
        var mappedIds = {};
        $('#nursing_insurance_list_wrap tr[data-insurance-id]').each(function() {
            var id = Number($(this).attr('data-insurance-id') || 0);
            if (id > 0) {
                mappedIds[id] = true;
            }
        });

        $('#insurance_company_id option').each(function() {
            var optVal = Number($(this).val() || 0);
            var disabled = !!mappedIds[optVal];
            $(this).prop('disabled', disabled);
        });

        if ($('#insurance_company_id option:selected').prop('disabled')) {
            var firstEnabled = $('#insurance_company_id option:not(:disabled)').first().val();
            $('#insurance_company_id').val(firstEnabled || '');
        }
    }

    $('#btn_nursing_item_add').on('click', function() {
        resetForm();
        $('#nursing_item_modal_title').text('Add Clinical Procedure/Test');
        $('#nursing_item_modal').modal('show');
        refreshInsuranceSelectState();
    });

    window.nursingBedsideEdit = function(itemId) {
        $.get(getUrlBase + '/' + itemId, function(resp) {
            var row = resp && resp.row ? resp.row : null;
            if (!row) {
                alert('Item not found');
                return;
            }

            applyCsrf(resp);

            $('#nursing_item_id').val(row.item_id || 0);
            $('#item_code').val(row.item_code || '');
            $('#item_name').val(row.item_name || '');
            $('#item_type').val(row.item_type || '');
            $('#category').val(row.category || '');
            $('#default_rate').val(row.default_rate || 0);
            $('#unit').val(row.unit || 'Unit');
            $('#description').val(row.description || '');
            $('#is_billable').val(String(row.is_billable || 0));
            $('#is_active').val(String(row.is_active || 0));

            $('#nursing_item_modal_title').text('Edit Clinical Procedure/Test');
            $('#nursing_item_modal').modal('show');
            loadInsuranceList(Number(row.item_id || 0));
        }, 'json');
    };

    $('#btn_nursing_item_save').on('click', function() {
        var payload = {};
        payload[csrfName] = csrfHash;
        payload.item_id = $('#nursing_item_id').val() || 0;
        payload.item_code = $('#item_code').val() || '';
        payload.item_name = $('#item_name').val() || '';
        payload.item_type = $('#item_type').val() || '';
        payload.category = $('#category').val() || '';
        payload.default_rate = $('#default_rate').val() || 0;
        payload.unit = $('#unit').val() || 'Unit';
        payload.description = $('#description').val() || '';
        payload.is_billable = $('#is_billable').val() || 1;
        payload.is_active = $('#is_active').val() || 1;

        $.post(saveUrl, payload, function(resp) {
            applyCsrf(resp);
            if (resp && Number(resp.ok || 0) === 1) {
                var itemId = Number(resp.item_id || $('#nursing_item_id').val() || 0);
                $('#nursing_item_id').val(itemId);
                loadInsuranceList(itemId);
                alert('Saved successfully');
                return;
            }
            alert((resp && resp.message) ? resp.message : 'Unable to save');
        }, 'json').fail(function(xhr) {
            var message = (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Unable to save';
            alert(message);
        });
    });

    $('#btn_insurance_add').on('click', function() {
        var itemId = Number($('#nursing_item_id').val() || 0);
        if (itemId <= 0) {
            alert('Save item first, then add insurance rate/code.');
            return;
        }

        var payload = {};
        payload[csrfName] = csrfHash;
        payload.item_id = itemId;
        payload.insurance_id = $('#insurance_company_id').val() || 0;
        payload.amount = $('#insurance_rate').val() || 0;
        payload.code = $('#insurance_code').val() || '';

        $.post(insuranceAddUrl, payload, function(resp) {
            applyCsrf(resp);
            if (resp && Number(resp.ok || 0) === 1) {
                $('#nursing_insurance_list_wrap').html(resp.html || '');
                refreshInsuranceSelectState();
                return;
            }
            alert((resp && resp.message) ? resp.message : 'Unable to save insurance rate');
        }, 'json').fail(function(xhr) {
            var message = (xhr && xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Unable to save insurance rate';
            alert(message);
        });
    });

    window.nursingInsuranceRemove = function(mappingId) {
        var itemId = Number($('#nursing_item_id').val() || 0);
        if (itemId <= 0 || !mappingId) {
            return;
        }

        var payload = {};
        payload[csrfName] = csrfHash;
        payload.mapping_id = mappingId;
        payload.item_id = itemId;

        $.post(insuranceRemoveUrl, payload, function(resp) {
            applyCsrf(resp);
            if (resp && Number(resp.ok || 0) === 1) {
                $('#nursing_insurance_list_wrap').html(resp.html || '');
                refreshInsuranceSelectState();
                return;
            }
            alert((resp && resp.message) ? resp.message : 'Unable to remove insurance rate');
        }, 'json');
    };

    $('#nursing_item_modal').on('hidden.bs.modal', function() {
        load_form_div('<?= base_url('nursing-bedside-items') ?>', 'maindiv', 'Clinical Procedure / Test Master');
    });
})();
</script>

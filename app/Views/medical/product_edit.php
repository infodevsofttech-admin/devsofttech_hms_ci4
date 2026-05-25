<?php
$product = $product_data[0] ?? null;
$productId = (int) ($product->id ?? 0);
$selectedCatIds = array_map('intval', (array) ($selected_cat_ids ?? []));

$formulationValue = (string) ($product->formulation ?? '');
$itemName = (string) ($product->item_name ?? '');
$genericName = (string) ($product->genericname ?? '');
$packing = (string) ($product->packing ?? '');
$reOrderQty = (string) ($product->re_order_qty ?? '');
$hsnCode = (string) ($product->HSNCODE ?? '');
$cgst = (string) ($product->CGST_per ?? '');
$sgst = (string) ($product->SGST_per ?? '');
$rackNo = (string) ($product->rack_no ?? '');
$shelfNo = (string) ($product->shelf_no ?? '');
$coldStorage = (string) ($product->cold_storage ?? '');
$companyId = (int) ($product->company_id ?? 0);
$relatedDrugId = (int) ($product->related_drug_id ?? 0);
$abdmDrugType = (string) ($product->abdm_drug_type ?? '');
$abdmDrugIdentifier = (string) ($product->abdm_drug_identifier ?? '');
$abdmDrugDisplay = (string) ($product->abdm_drug_display ?? '');
$abdmDrugGeneric = (string) ($product->abdm_drug_generic ?? '');
$abdmDrugPayloadJson = (string) ($product->abdm_drug_payload_json ?? '');
$abdmDrugLastSyncedAt = (string) ($product->abdm_drug_last_synced_at ?? '');

$flag = static function ($v): string {
    return ((int) $v === 1) ? 'checked' : '';
};
?>

<div class="card">
    <div class="card-header">Product <?= $productId > 0 ? '<small class="text-muted">#' . $productId . '</small>' : '' ?></div>
    <div class="card-body pt-3">
        <style>
            #abdm-drug-suggest {
                max-height: 260px;
                overflow-y: auto;
                z-index: 1055;
                display: none;
                width: 100%;
            }
            #abdm-drug-selected {
                font-size: 12px;
            }
        </style>
        <div id="product-msg"></div>

        <form id="product-form" class="row g-2" method="post" action="javascript:void(0)">
            <?= csrf_field() ?>
            <input type="hidden" id="product_id" name="product_id" value="<?= esc((string) $productId) ?>">
            <input type="hidden" id="related_drug_id" name="related_drug_id" value="<?= esc((string) $relatedDrugId) ?>">
            <input type="hidden" id="abdm_drug_type" name="abdm_drug_type" value="<?= esc($abdmDrugType) ?>">
            <input type="hidden" id="abdm_drug_identifier" name="abdm_drug_identifier" value="<?= esc($abdmDrugIdentifier) ?>">
            <input type="hidden" id="abdm_drug_display" name="abdm_drug_display" value="<?= esc($abdmDrugDisplay) ?>">
            <input type="hidden" id="abdm_drug_generic" name="abdm_drug_generic" value="<?= esc($abdmDrugGeneric) ?>">
            <input type="hidden" id="abdm_drug_payload_json" name="abdm_drug_payload_json" value="<?= esc($abdmDrugPayloadJson) ?>">
            <input type="hidden" id="abdm_drug_last_synced_at" name="abdm_drug_last_synced_at" value="<?= esc($abdmDrugLastSyncedAt) ?>">

            <div class="col-md-6">
                <label class="form-label">Product Name</label>
                <div class="position-relative">
                    <input class="form-control" name="input_item_name" id="input_item_name" type="text" value="<?= esc($itemName) ?>" autocomplete="off">
                    <div id="abdm-drug-suggest" class="list-group position-absolute shadow-sm"></div>
                </div>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <small class="text-muted">ABDM Drug Search</small>
                    <select id="abdm_drug_search_type" class="form-select form-select-sm" style="width:auto;">
                        <option value="generic" selected>Generic</option>
                        <option value="brand">Brand</option>
                        <option value="product">Product</option>
                        <option value="substance">Substance</option>
                    </select>
                </div>
                <div id="abdm-drug-selected" class="text-success mt-1"></div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Formulation</label>
                <select name="input_formulation" id="input_formulation" class="form-select">
                    <option value="">Select</option>
                    <?php foreach (($med_formulation ?? []) as $row): ?>
                        <?php
                        $v = (string) ($row->formulation ?? ($row->formulation_length ?? ''));
                        $label = (string) ($row->formulation_length ?? $v);
                        ?>
                        <option value="<?= esc($v) ?>" <?= strcasecmp($v, $formulationValue) === 0 ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Company</label>
                <select name="input_company_name" id="input_company_name" class="form-select">
                    <option value="0">Select</option>
                    <?php foreach (($med_company ?? []) as $row): ?>
                        <option value="<?= (int) ($row->id ?? 0) ?>" <?= ((int) ($row->id ?? 0) === $companyId) ? 'selected' : '' ?>><?= esc($row->company_name ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Generic Name</label>
                <input class="form-control" name="input_genericname" id="input_genericname" type="text" value="<?= esc($genericName) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Packing</label>
                <input class="form-control" name="input_packing_type" id="input_packing_type" type="text" value="<?= esc($packing) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Re-Order Qty</label>
                <input class="form-control" name="input_re_order_qty" id="input_re_order_qty" type="text" value="<?= esc($reOrderQty) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">HSNCODE</label>
                <input class="form-control" name="input_HSNCODE" id="input_HSNCODE" type="text" value="<?= esc($hsnCode) ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label">CGST</label>
                <input class="form-control" name="input_CGST" id="input_CGST" type="text" value="<?= esc($cgst) ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label">SGST</label>
                <input class="form-control" name="input_SGST" id="input_SGST" type="text" value="<?= esc($sgst) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">Rack No</label>
                <input class="form-control" name="input_rack_no" id="input_rack_no" type="text" value="<?= esc($rackNo) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Shelf No</label>
                <input class="form-control" name="input_shelf_no" id="input_shelf_no" type="text" value="<?= esc($shelfNo) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Cold Storage</label>
                <input class="form-control" name="input_cold_storage" id="input_cold_storage" type="text" value="<?= esc($coldStorage) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Medicine Category</label>
                <select class="form-select" id="med_cat_id" name="med_cat_id[]" multiple>
                    <?php foreach (($med_product_cat_master ?? []) as $row): ?>
                        <?php $catId = (int) ($row->id ?? 0); ?>
                        <option value="<?= $catId ?>" <?= in_array($catId, $selectedCatIds, true) ? 'selected' : '' ?>><?= esc($row->med_cat_desc ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <div class="row g-2">
                    <div class="col-auto"><div class="form-check"><input class="form-check-input" type="checkbox" id="chk_ban_flag_id" name="chk_ban_flag_id" <?= $flag($product->ban_flag_id ?? 0) ?>><label class="form-check-label" for="chk_ban_flag_id">Banned Drug</label></div></div>
                    <div class="col-auto"><div class="form-check"><input class="form-check-input" type="checkbox" id="chk_batch_applicable" name="chk_batch_applicable" <?= $flag($product->batch_applicable ?? 0) ?>><label class="form-check-label" for="chk_batch_applicable">Batch Applicable</label></div></div>
                    <div class="col-auto"><div class="form-check"><input class="form-check-input" type="checkbox" id="chk_exp_date_applicable" name="chk_exp_date_applicable" <?= $flag($product->exp_date_applicable ?? 0) ?>><label class="form-check-label" for="chk_exp_date_applicable">Exp.Date Applicable</label></div></div>
                    <div class="col-auto"><div class="form-check"><input class="form-check-input" type="checkbox" id="chk_is_continue" name="chk_is_continue" <?= $flag($product->is_continue ?? 0) ?>><label class="form-check-label" for="chk_is_continue">Is Continue</label></div></div>
                    <div class="col-auto"><div class="form-check"><input class="form-check-input" type="checkbox" id="chk_narcotic" name="chk_narcotic" <?= $flag($product->narcotic ?? 0) ?>><label class="form-check-label" for="chk_narcotic">Narcotic</label></div></div>
                    <div class="col-auto"><div class="form-check"><input class="form-check-input" type="checkbox" id="chk_schedule_h" name="chk_schedule_h" <?= $flag($product->schedule_h ?? 0) ?>><label class="form-check-label" for="chk_schedule_h">Schedule H</label></div></div>
                    <div class="col-auto"><div class="form-check"><input class="form-check-input" type="checkbox" id="chk_schedule_h1" name="chk_schedule_h1" <?= $flag($product->schedule_h1 ?? 0) ?>><label class="form-check-label" for="chk_schedule_h1">Schedule H1</label></div></div>
                    <div class="col-auto"><div class="form-check"><input class="form-check-input" type="checkbox" id="chk_schedule_x" name="chk_schedule_x" <?= $flag($product->schedule_x ?? 0) ?>><label class="form-check-label" for="chk_schedule_x">Schedule X</label></div></div>
                    <div class="col-auto"><div class="form-check"><input class="form-check-input" type="checkbox" id="chk_schedule_g" name="chk_schedule_g" <?= $flag($product->schedule_g ?? 0) ?>><label class="form-check-label" for="chk_schedule_g">Schedule G</label></div></div>
                    <div class="col-auto"><div class="form-check"><input class="form-check-input" type="checkbox" id="chk_high_risk" name="chk_high_risk" <?= $flag($product->high_risk ?? 0) ?>><label class="form-check-label" for="chk_high_risk">High Risk</label></div></div>
                </div>
            </div>

            <div class="col-12">
                <button type="button" class="btn btn-primary" id="btn_update_stock">Update Product in Master</button>
                <button type="button" class="btn btn-secondary" onclick="load_form_div('<?= base_url('product_master/drug_master_list') ?>','medical-main','Drug Master :Pharmacy');">Back</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var suggestTimer = null;
    var lastQuery = '';

    if (window.jQuery && $.fn.select2) {
        $('#med_cat_id').select2({
            width: '100%',
            placeholder: 'Select categories'
        });
    }

    function showMsg(html) {
        $('#product-msg').html(html || '');
    }

    function showSelectedDrugSummary() {
        var id = $('#abdm_drug_identifier').val() || '';
        var label = $('#abdm_drug_display').val() || '';
        var type = $('#abdm_drug_type').val() || '';
        var generic = $('#abdm_drug_generic').val() || '';
        var syncedAt = $('#abdm_drug_last_synced_at').val() || '';

        if (!id && !label) {
            $('#abdm-drug-selected').html('');
            return;
        }

        var parts = [];
        if (label) { parts.push(label); }
        if (generic) { parts.push('Generic: ' + generic); }
        if (type) { parts.push('Type: ' + type); }
        if (id) { parts.push('Identifier: ' + id); }
        if (syncedAt) { parts.push('Synced: ' + syncedAt); }

        $('#abdm-drug-selected').text(parts.join(' | '));
    }

    function clearSuggestionBox() {
        $('#abdm-drug-suggest').hide().empty();
    }

    function applyDrugDetail(detail) {
        if (!detail || typeof detail !== 'object') {
            return;
        }

        var label = detail.label || '';
        var generic = detail.generic_name || '';
        var hsnCode = detail.hsn_code || '';
        var formulation = detail.formulation || '';
        var packing = detail.packing || '';

        if (label) {
            $('#input_item_name').val(label);
            $('#abdm_drug_display').val(label);
        }
        if (generic && !$('#input_genericname').val()) {
            $('#input_genericname').val(generic);
        }
        if (hsnCode && !$('#input_HSNCODE').val()) {
            $('#input_HSNCODE').val(hsnCode);
        }

        if (formulation) {
            var $f = $('#input_formulation');
            var exists = $f.find('option').filter(function () {
                return String($(this).val()).toLowerCase() === String(formulation).toLowerCase();
            }).length > 0;
            if (exists) {
                $f.val(formulation);
            }
        }

        if (packing && !$('#input_packing_type').val()) {
            $('#input_packing_type').val(packing);
        }

        $('#abdm_drug_type').val(detail.type || '');
        $('#abdm_drug_identifier').val(detail.identifier || '');
        $('#abdm_drug_generic').val(generic);
        $('#abdm_drug_payload_json').val(detail.payload_json || '{}');
        $('#abdm_drug_last_synced_at').val(detail.synced_at || '');
        showSelectedDrugSummary();
    }

    function fetchDrugDetail(type, identifier) {
        if (!identifier) {
            return;
        }

        $.getJSON('<?= base_url('product_master/drug_terminology_detail') ?>', {
            type: type || 'generic',
            identifier: identifier
        }, function (resp) {
            if (!resp || Number(resp.ok || 0) !== 1 || !resp.selected) {
                return;
            }
            applyDrugDetail(resp.selected);
        });
    }

    function renderSuggestions(items) {
        var $box = $('#abdm-drug-suggest');
        $box.empty();

        if (!Array.isArray(items) || items.length === 0) {
            clearSuggestionBox();
            return;
        }

        items.forEach(function (item) {
            var label = item.label || '';
            var type = item.type || '';
            var identifier = item.identifier || '';
            if (!label && !identifier) {
                return;
            }

            var $a = $('<a href="#" class="list-group-item list-group-item-action py-1 px-2"></a>');
            $a.text(label + (type ? ' [' + type + ']' : '') + (identifier ? ' (' + identifier + ')' : ''));
            $a.on('click', function (e) {
                e.preventDefault();
                clearSuggestionBox();
                if (label) {
                    $('#input_item_name').val(label);
                }
                fetchDrugDetail(type, identifier);
            });
            $box.append($a);
        });

        if ($box.children().length > 0) {
            $box.show();
        } else {
            clearSuggestionBox();
        }
    }

    function fetchDrugSuggestions() {
        var q = ($('#input_item_name').val() || '').trim();
        var type = ($('#abdm_drug_search_type').val() || 'generic').trim();

        if (q.length < 2) {
            clearSuggestionBox();
            return;
        }
        if (q === lastQuery) {
            return;
        }
        lastQuery = q;

        $.getJSON('<?= base_url('product_master/drug_terminology_autocomplete') ?>', {
            q: q,
            type: type,
            limit: 10
        }, function (resp) {
            if (!resp || Number(resp.ok || 0) !== 1) {
                clearSuggestionBox();
                return;
            }
            renderSuggestions(resp.suggestions || []);
        }).fail(function () {
            clearSuggestionBox();
        });
    }

    $('#input_item_name').off('input').on('input', function () {
        if (suggestTimer) {
            clearTimeout(suggestTimer);
        }
        suggestTimer = setTimeout(fetchDrugSuggestions, 250);
    });

    $('#abdm_drug_search_type').off('change').on('change', function () {
        lastQuery = '';
        fetchDrugSuggestions();
    });

    $(document).off('click.abdmDrug').on('click.abdmDrug', function (evt) {
        if (!$(evt.target).closest('#abdm-drug-suggest, #input_item_name').length) {
            clearSuggestionBox();
        }
    });

    showSelectedDrugSummary();

    $('#btn_update_stock').off('click').on('click', function () {
        $.post('<?= base_url('product_master/product_master_update') ?>/' + ($('#product_id').val() || '0'), $('#product-form').serialize(), function (data) {
            if (!data || typeof data !== 'object') {
                showMsg('<div class="alert alert-danger mb-0">Unexpected response.</div>');
                return;
            }
            showMsg(data.show_text || '');
            if ((data.is_update_stock || 0) > 0) {
                load_form_div('<?= base_url('Product_master/Product_edit') ?>/' + data.is_update_stock, 'searchresult', 'Drug Master : Edit :Pharmacy');
            }
        }, 'json').fail(function () {
            showMsg('<div class="alert alert-danger mb-0">Unable to update product.</div>');
        });
    });
})();
</script>

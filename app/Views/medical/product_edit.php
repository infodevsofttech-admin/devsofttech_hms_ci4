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
                        <option value="generic">Generic</option>
                        <option value="brand" selected>Brand</option>
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
                        $v = (string) ($row->short_formulation ?? ($row->formulation ?? ($row->formulation_length ?? '')));
                        $label = $v;
                        $code = (string) ($row->short_formulation_code ?? ($row->formulation_code ?? ($row->code ?? '')));
                        if ($code === '') {
                            $code = preg_replace('/[^a-z0-9]+/i', '', strtolower($v));
                        }
                        ?>
                        <option value="<?= esc($v) ?>" data-formulation-code="<?= esc($code) ?>" <?= strcasecmp($v, $formulationValue) === 0 ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Company</label>
                <select name="input_company_name" id="input_company_name" class="form-select">
                    <option value="0">Select</option>
                    <?php foreach (($med_company ?? []) as $row): ?>
                        <?php $companyCode = (string) ($row->company_code ?? ($row->manufacturer_code ?? ($row->code ?? ''))); ?>
                        <option value="<?= (int) ($row->id ?? 0) ?>" data-company-code="<?= esc($companyCode) ?>" <?= ((int) ($row->id ?? 0) === $companyId) ? 'selected' : '' ?>><?= esc($row->company_name ?? '') ?></option>
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
    var suggestionItems = [];
    var activeSuggestionIndex = -1;

    if (window.jQuery && $.fn.select2) {
        $('#med_cat_id').select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: 'Select categories'
        });
        $('#input_formulation').select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: 'Select formulation',
            allowClear: true
        });
        $('#input_company_name').select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: 'Select company',
            allowClear: true
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
        var route = '';
        var syncedAt = $('#abdm_drug_last_synced_at').val() || '';

        var payloadObj = parseJsonObject($('#abdm_drug_payload_json').val() || '{}');
        var flatMeta = flattenMeta(payloadObj, {});
        route = getMetaText(flatMeta, ['route', 'administration_route']);
        route = normalizeRoute(route);

        if (!id && !label) {
            $('#abdm-drug-selected').html('');
            return;
        }

        var parts = [];
        if (label) { parts.push(label); }
        if (generic) { parts.push('Generic: ' + generic); }
        if (route) { parts.push('Route: ' + route); }
        if (type) { parts.push('Type: ' + type); }
        if (id) { parts.push('Identifier: ' + id); }
        if (syncedAt) { parts.push('Synced: ' + syncedAt); }

        $('#abdm-drug-selected').text(parts.join(' | '));
    }

    function clearSuggestionBox() {
        suggestionItems = [];
        activeSuggestionIndex = -1;
        $('#abdm-drug-suggest').hide().empty();
    }

    function applyParsedLabelToForm(label) {
        var parsed = parseDrugLabel(label || '');
        if (parsed.productName && !isIdentifierLike(parsed.productName)) {
            $('#input_item_name').val(parsed.productName);
        }
        if (parsed.genericName) {
            $('#input_genericname').val(parsed.genericName);
        }
        if (parsed.formulation) {
            setFormulationValue(parsed.formulation);
        }
    }

    function normalizeSpace(text) {
        return String(text || '').replace(/\s+/g, ' ').trim();
    }

    function compactKey(text) {
        return normalizeSpace(text).toLowerCase().replace(/[^a-z0-9]+/g, '');
    }

    function normalizeFormulationText(text) {
        var raw = normalizeSpace(String(text || '').replace(/[_.]/g, ' '));
        if (!raw) {
            return '';
        }

        var key = compactKey(raw);
        var map = {
            tab: 'Tablet',
            tabs: 'Tablet',
            tablet: 'Tablet',
            tablets: 'Tablet',
            oraltablet: 'Tablet',
            oraltablet: 'Tablet',
            tabletoral: 'Tablet',
            cap: 'Capsule',
            caps: 'Capsule',
            capsule: 'Capsule',
            capsules: 'Capsule',
            oralcapsule: 'Capsule',
            inj: 'Injection',
            injection: 'Injection',
            syp: 'Syrup',
            syrup: 'Syrup',
            susp: 'Suspension',
            suspension: 'Suspension',
            ointment: 'Ointment',
            cream: 'Cream',
            gel: 'Gel',
            drops: 'Drops',
            drop: 'Drops',
            powder: 'Powder',
            sachet: 'Sachet',
            sachets: 'Sachet',
            lotion: 'Lotion',
            spray: 'Spray',
            inhaler: 'Inhaler'
        };
        if (Object.prototype.hasOwnProperty.call(map, key)) {
            return map[key];
        }

        var lower = raw.toLowerCase();
        if (lower.indexOf('tablet') >= 0) {
            return 'Tablet';
        }
        if (lower.indexOf('capsule') >= 0) {
            return 'Capsule';
        }
        if (lower.indexOf('injection') >= 0) {
            return 'Injection';
        }
        if (lower.indexOf('syrup') >= 0) {
            return 'Syrup';
        }
        if (lower.indexOf('suspension') >= 0) {
            return 'Suspension';
        }
        if (lower.indexOf('cream') >= 0) {
            return 'Cream';
        }
        if (lower.indexOf('ointment') >= 0) {
            return 'Ointment';
        }
        if (lower.indexOf('gel') >= 0) {
            return 'Gel';
        }

        return raw.charAt(0).toUpperCase() + raw.slice(1).toLowerCase();
    }

    function normalizeCompanyText(text) {
        var raw = normalizeSpace(text);
        if (!raw) {
            return '';
        }

        var key = compactKey(raw);
        var map = {
            ipca: 'Ipca Laboratories Limited',
            ipcalab: 'Ipca Laboratories Limited',
            ipcalabs: 'Ipca Laboratories Limited',
            ipcalaboratories: 'Ipca Laboratories Limited',
            ipcalaboratoriesltd: 'Ipca Laboratories Limited',
            ipcalaboratorieslimited: 'Ipca Laboratories Limited'
        };
        if (Object.prototype.hasOwnProperty.call(map, key)) {
            return map[key];
        }

        return raw.toLowerCase().replace(/\b\w/g, function (m) { return m.toUpperCase(); }).replace(/\bLtd\.?\b/g, 'Limited');
    }

    function normalizeCode(text) {
        return compactKey(text || '');
    }

    function normalizeRouteText(text) {
        var route = normalizeSpace(text || '');
        if (!route) {
            return '';
        }

        var lower = route.toLowerCase();
        if (lower.indexOf('oral') >= 0) {
            return 'Oral';
        }
        if (lower.indexOf('topical') >= 0) {
            return 'Topical';
        }
        if (lower.indexOf('intravenous') >= 0 || lower === 'iv') {
            return 'Intravenous';
        }
        if (lower.indexOf('intramuscular') >= 0 || lower === 'im') {
            return 'Intramuscular';
        }
        if (lower.indexOf('subcutaneous') >= 0) {
            return 'Subcutaneous';
        }
        if (lower.indexOf('nasal') >= 0) {
            return 'Nasal';
        }
        if (lower.indexOf('ophthalmic') >= 0) {
            return 'Ophthalmic';
        }
        if (lower.indexOf('otic') >= 0) {
            return 'Otic';
        }
        if (lower.indexOf('rectal') >= 0) {
            return 'Rectal';
        }
        if (lower.indexOf('vaginal') >= 0) {
            return 'Vaginal';
        }

        return route.charAt(0).toUpperCase() + route.slice(1).toLowerCase();
    }

    function isIdentifierLike(text) {
        var value = normalizeSpace(text);
        if (!value) {
            return false;
        }

        var compact = value.replace(/[\s\-_.]/g, '');
        if (/^\d{8,}$/.test(compact)) {
            return true;
        }

        if (/^[A-Z0-9]{10,}$/.test(compact) && !/[a-z]/.test(value)) {
            return true;
        }

        return false;
    }

    function parseDrugLabel(rawLabel) {
        var label = normalizeSpace(rawLabel);
        var result = {
            productName: label,
            genericName: '',
            formulation: ''
        };

        if (!label) {
            return result;
        }

        var before = '';
        var inside = '';
        var after = '';
        var m = label.match(/^([^()]+?)\s*\(([^)]+)\)\s*(.*)$/);
        if (m) {
            before = normalizeSpace(m[1]);
            inside = normalizeSpace(m[2]);
            after = normalizeSpace(m[3]);
        }

        if (before) {
            result.productName = before;
        }

        var formRegex = /\b(tablet|capsule|syrup|suspension|injection|cream|ointment|gel|drops?|solution|powder|lotion|spray|respules?|inhaler|patch|suppository|lozenge|granules?)\b/i;
        var formMatch = formRegex.exec(after || label);
        if (formMatch) {
            result.formulation = normalizeSpace(formMatch[1]);
        }

        if (inside) {
            var genericTail = after;
            if (formMatch && after) {
                genericTail = normalizeSpace(after.substring(0, formMatch.index));
            }
            // Remove common dosage-route filler just before formulation.
            genericTail = normalizeSpace(genericTail.replace(/\b(oral|route|for\s+oral\s+use)\b/gi, ''));
            result.genericName = normalizeSpace((inside + ' ' + genericTail).trim());
        }

        return result;
    }

    function parseJsonObject(text) {
        if (!text) {
            return {};
        }
        try {
            var obj = JSON.parse(String(text));
            return (obj && typeof obj === 'object') ? obj : {};
        } catch (e) {
            return {};
        }
    }

    function flattenMeta(input, out) {
        if (!input || typeof input !== 'object') {
            return out;
        }

        Object.keys(input).forEach(function (key) {
            var value = input[key];
            var k = String(key || '').toLowerCase();

            if (value === null || value === undefined) {
                return;
            }

            if (Array.isArray(value)) {
                if (value.length && (typeof value[0] === 'string' || typeof value[0] === 'number' || typeof value[0] === 'boolean')) {
                    out[k] = value.map(String).join(', ');
                }
                value.forEach(function (child) {
                    if (child && typeof child === 'object') {
                        flattenMeta(child, out);
                    }
                });
                return;
            }

            if (typeof value === 'object') {
                flattenMeta(value, out);
                return;
            }

            out[k] = String(value).trim();
        });

        return out;
    }

    function getMetaText(meta, keys) {
        for (var i = 0; i < keys.length; i++) {
            var key = String(keys[i] || '').toLowerCase();
            var value = normalizeSpace(meta[key] || '');
            if (value) {
                return value;
            }
        }
        return '';
    }

    function getMetaNumber(meta, keys) {
        var raw = getMetaText(meta, keys);
        if (!raw) {
            return null;
        }
        var num = parseFloat(String(raw).replace(/[^0-9.\-]/g, ''));
        return isNaN(num) ? null : num;
    }

    function hasMetaKey(meta, keys) {
        for (var i = 0; i < keys.length; i++) {
            if (Object.prototype.hasOwnProperty.call(meta, String(keys[i] || '').toLowerCase())) {
                return true;
            }
        }
        return false;
    }

    function getMetaBool(meta, keys) {
        var raw = getMetaText(meta, keys).toLowerCase();
        if (!raw) {
            return null;
        }
        if (['1', 'true', 'yes', 'y', 'on'].indexOf(raw) >= 0) {
            return true;
        }
        if (['0', 'false', 'no', 'n', 'off'].indexOf(raw) >= 0) {
            return false;
        }
        return null;
    }

    function normalizeRoute(routeText) {
        var route = normalizeRouteText(routeText || '');
        if (!route) {
            return '';
        }
        route = route.replace(/\broute\b/ig, '');
        return normalizeRouteText(route);
    }

    function splitFormulationAndRoute(formulationText, routeText) {
        var formulation = normalizeSpace(formulationText || '');
        var route = normalizeRoute(routeText);

        if (!formulation) {
            return {
                formulation: '',
                route: route
            };
        }

        var knownRoutes = ['oral', 'topical', 'intravenous', 'iv', 'intramuscular', 'im', 'subcutaneous', 'inhalation', 'nasal', 'ophthalmic', 'otic', 'rectal', 'vaginal', 'transdermal'];
        var lower = formulation.toLowerCase();
        for (var i = 0; i < knownRoutes.length; i++) {
            var key = knownRoutes[i];
            var re = new RegExp('\\b' + key.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&') + '\\b', 'i');
            if (re.test(lower)) {
                if (!route) {
                    route = normalizeRoute(key);
                }
                formulation = normalizeSpace(formulation.replace(re, ''));
                break;
            }
        }

        return {
            formulation: formulation,
            route: route
        };
    }

    function deriveDrugName(detail, parsed) {
        var brandName = normalizeSpace(detail.brand_name || '');
        var manufacturerName = normalizeSpace(detail.manufacturer_name || '');
        var label = normalizeSpace(detail.label || '');
        var displayName = normalizeSpace(detail.display_name || '');
        var productName = normalizeSpace(parsed && parsed.productName ? parsed.productName : '');

        if (brandName) {
            return brandName;
        }
        if (manufacturerName && !isIdentifierLike(manufacturerName)) {
            return manufacturerName;
        }
        if (label && !isIdentifierLike(label)) {
            return label;
        }
        if (displayName && !isIdentifierLike(displayName)) {
            return parsed && parsed.productName ? productName : displayName;
        }
        return productName || displayName || label;
    }

    function deriveGenericName(detail, parsed, flatMeta) {
        var generic = normalizeSpace(detail.generic_name || '');
        var metaGeneric = getMetaText(flatMeta, ['generic_name', 'genericname', 'generic', 'salt', 'molecule', 'composition', 'composition_text']);
        var parsedGeneric = normalizeSpace(parsed && parsed.genericName ? parsed.genericName : '');
        return normalizeSpace(generic || metaGeneric || parsedGeneric);
    }

    function setFormulationValue(formulation) {
        var value = normalizeFormulationText(formulation).toLowerCase();
        if (!value) {
            return;
        }
        var $f = $('#input_formulation');
        var matchedVal = '';
        $f.find('option').each(function () {
            var optVal = $(this).val();
            if (!optVal) { return true; } // skip placeholder/empty options
            var v = normalizeFormulationText(optVal).toLowerCase();
            var t = normalizeFormulationText($(this).text()).toLowerCase();
            if (v === value || t === value || v.indexOf(value) >= 0 || t.indexOf(value) >= 0 || (v && value.indexOf(v) >= 0) || (t && value.indexOf(t) >= 0)) {
                matchedVal = optVal;
                return false;
            }
        });
        if (matchedVal !== '') {
            $f.val(matchedVal).trigger('change');
        }
    }

    function setCompanyByName(companyName) {
        var needle = normalizeCompanyText(companyName).toLowerCase();
        if (!needle) {
            return;
        }
        var $c = $('#input_company_name');
        var matchedVal = '';

        $c.find('option').each(function () {
            var text = normalizeCompanyText($(this).text()).toLowerCase();
            if (text === needle) {
                matchedVal = $(this).val();
                return false;
            }
        });
        if (matchedVal !== '') {
            $c.val(matchedVal).trigger('change');
            return;
        }

        $c.find('option').each(function () {
            var text = normalizeCompanyText($(this).text()).toLowerCase();
            if (!text || text === 'select') {
                return;
            }
            if (text === needle || text.indexOf(needle) >= 0 || needle.indexOf(text) >= 0) {
                matchedVal = $(this).val();
                return false;
            }
        });
        if (matchedVal !== '') {
            $c.val(matchedVal).trigger('change');
        }
    }

    function setCompanyByCode(companyCode) {
        var needle = normalizeCode(companyCode);
        if (!needle) {
            return;
        }
        var $c = $('#input_company_name');
        var matchedVal = '';
        $c.find('option').each(function () {
            var code = normalizeCode($(this).data('company-code') || '');
            if (code && code === needle) {
                matchedVal = $(this).val();
                return false;
            }
        });
        if (matchedVal !== '') {
            $c.val(matchedVal).trigger('change');
        }
    }

    function setFormulationByCode(formulationCode) {
        var needle = normalizeCode(formulationCode);
        if (!needle) {
            return;
        }
        var $f = $('#input_formulation');
        var matchedVal = '';
        $f.find('option').each(function () {
            var code = normalizeCode($(this).data('formulation-code') || '');
            if (code && code === needle) {
                matchedVal = $(this).val();
                return false;
            }
        });
        if (matchedVal !== '') {
            $f.val(matchedVal).trigger('change');
        }
    }

    function setCategoryByName(categoryText) {
        var raw = normalizeSpace(categoryText);
        if (!raw) {
            return;
        }
        var tokens = raw.split(/[|,;/]+/).map(function (x) { return normalizeSpace(x).toLowerCase(); }).filter(Boolean);
        if (!tokens.length) {
            return;
        }

        var selected = [];
        $('#med_cat_id option').each(function () {
            var text = normalizeSpace($(this).text()).toLowerCase();
            if (!text) {
                return;
            }
            var matched = tokens.some(function (t) {
                return text === t || text.indexOf(t) >= 0 || t.indexOf(text) >= 0;
            });
            if (matched) {
                selected.push($(this).val());
            }
        });

        if (selected.length) {
            $('#med_cat_id').val(selected).trigger('change');
        }
    }

    function setCheckboxFromMeta(selector, meta, keys) {
        if (!hasMetaKey(meta, keys)) {
            return;
        }
        var boolVal = getMetaBool(meta, keys);
        if (boolVal === null) {
            return;
        }
        $(selector).prop('checked', !!boolVal);
    }

    function applyDrugDetail(detail) {
        if (!detail || typeof detail !== 'object') {
            return;
        }

        var label = normalizeSpace(detail.label || '');
        var brandName = normalizeSpace(detail.brand_name || '');
        var manufacturerName = normalizeSpace(detail.manufacturer_name || '');
        var manufacturerCode = normalizeSpace(detail.manufacturer_code || '');
        var generic = normalizeSpace(detail.generic_name || '');
        var hsnCode = detail.hsn_code || '';
        var formulation = detail.dosage_form || detail.formulation || '';
        var route = detail.route || '';
        var packing = detail.pack_size_text || detail.packing || '';

        var parsed = parseDrugLabel(label);
        var payloadObj = parseJsonObject(detail.payload_json || '{}');
        var flatMeta = flattenMeta(payloadObj, {});

        var metaGeneric = getMetaText(flatMeta, ['generic_name', 'genericname', 'generic', 'salt', 'molecule', 'composition', 'composition_text']);
        var metaCompany = getMetaText(flatMeta, ['company', 'company_name', 'manufacturer', 'manufacturer_name', 'brand_owner', 'marketer']);
        var metaCategory = getMetaText(flatMeta, ['medicine_category', 'category', 'drug_category', 'schedule_category']);
        var metaFormulation = getMetaText(flatMeta, ['formulation', 'dosage_form', 'drug_form', 'short_formulation', 'short_form', 'sf_name']);
        var metaRoute = getMetaText(flatMeta, ['route', 'administration_route']);
        var metaPacking = getMetaText(flatMeta, ['packing', 'pack_size', 'pack_size_text', 'package']);
        var metaHsn = getMetaText(flatMeta, ['hsn_code', 'hsn', 'hscode']);
        var metaStrength = getMetaText(flatMeta, ['strength_text', 'strength', 'dose_strength']);
        var manufacturerNameMeta = getMetaText(flatMeta, ['manufacturer_name', 'manufacturer', 'company_name', 'company']);

        // Nested manufacturer object: payload_json.manufacturer.name / .code
        var manufacturerFromPayload = '';
        var manufacturerCodeFromPayload = '';
        if (payloadObj.manufacturer && typeof payloadObj.manufacturer === 'object') {
            manufacturerFromPayload = normalizeSpace(String(payloadObj.manufacturer.name || ''));
            manufacturerCodeFromPayload = normalizeSpace(String(payloadObj.manufacturer.code || ''));
        }

        var cgstVal = getMetaNumber(flatMeta, ['cgst', 'cgst_per', 'cgst_percent']);
        var sgstVal = getMetaNumber(flatMeta, ['sgst', 'sgst_per', 'sgst_percent']);
        var gstTotal = getMetaNumber(flatMeta, ['gst', 'gst_per', 'gst_percent', 'igst', 'tax_percent']);

        var finalProductName = deriveDrugName(detail, parsed);
        var finalGeneric = deriveGenericName(detail, parsed, flatMeta);
        var split = splitFormulationAndRoute(formulation || metaFormulation || parsed.formulation, route || metaRoute);
        var finalFormulation = normalizeSpace(split.formulation || parsed.formulation);
        var finalRoute = normalizeRouteText(split.route);
        var finalPacking = normalizeSpace(packing || metaPacking);
        var finalHsn = normalizeSpace(hsnCode || metaHsn);

        if (finalProductName) {
            $('#input_item_name').val(finalProductName);
        }
        if (finalGeneric) {
            $('#input_genericname').val(finalGeneric);
        }
        if (finalHsn) {
            $('#input_HSNCODE').val(finalHsn);
        }
        if (finalFormulation) {
            setFormulationValue(finalFormulation);
            setFormulationByCode(finalFormulation);
        }
        if (finalPacking && !$('#input_packing_type').val()) {
            $('#input_packing_type').val(finalPacking);
        }

        if (manufacturerName || manufacturerFromPayload || manufacturerNameMeta || metaCompany) {
            setCompanyByName(manufacturerName || manufacturerFromPayload || manufacturerNameMeta || metaCompany);
        }
        if (manufacturerCode || manufacturerCodeFromPayload) {
            setCompanyByCode(manufacturerCode || manufacturerCodeFromPayload);
        }
        if (metaCategory) {
            setCategoryByName(metaCategory);
        }

        if (!finalPacking && metaStrength) {
            $('#input_packing_type').val(metaStrength);
        }

        if (cgstVal !== null) {
            $('#input_CGST').val(cgstVal.toFixed(2).replace(/\.00$/, ''));
        }
        if (sgstVal !== null) {
            $('#input_SGST').val(sgstVal.toFixed(2).replace(/\.00$/, ''));
        }
        if (gstTotal !== null && (cgstVal === null || sgstVal === null)) {
            var half = (gstTotal / 2);
            if (cgstVal === null) {
                $('#input_CGST').val(half.toFixed(2).replace(/\.00$/, ''));
            }
            if (sgstVal === null) {
                $('#input_SGST').val(half.toFixed(2).replace(/\.00$/, ''));
            }
        }

        setCheckboxFromMeta('#chk_batch_applicable', flatMeta, ['batch_applicable', 'batch_required']);
        setCheckboxFromMeta('#chk_exp_date_applicable', flatMeta, ['exp_date_applicable', 'expiry_applicable']);
        setCheckboxFromMeta('#chk_is_continue', flatMeta, ['is_continue', 'is_continued']);
        setCheckboxFromMeta('#chk_narcotic', flatMeta, ['narcotic']);
        setCheckboxFromMeta('#chk_schedule_h', flatMeta, ['schedule_h']);
        setCheckboxFromMeta('#chk_schedule_h1', flatMeta, ['schedule_h1']);
        setCheckboxFromMeta('#chk_schedule_x', flatMeta, ['schedule_x']);
        setCheckboxFromMeta('#chk_schedule_g', flatMeta, ['schedule_g']);
        setCheckboxFromMeta('#chk_high_risk', flatMeta, ['high_risk']);

        var scheduleRaw = getMetaText(flatMeta, ['schedule', 'schedule_type']);
        if (scheduleRaw) {
            var s = scheduleRaw.toUpperCase();
            if (s.indexOf('H1') >= 0) {
                $('#chk_schedule_h1').prop('checked', true);
            }
            if (s.indexOf('H') >= 0) {
                $('#chk_schedule_h').prop('checked', true);
            }
            if (s.indexOf('X') >= 0) {
                $('#chk_schedule_x').prop('checked', true);
            }
            if (s.indexOf('G') >= 0) {
                $('#chk_schedule_g').prop('checked', true);
            }
        }

        $('#abdm_drug_display').val(finalProductName || brandName || label);

        $('#abdm_drug_type').val(detail.type || '');
        $('#abdm_drug_identifier').val(detail.identifier || '');
        $('#abdm_drug_generic').val(finalGeneric);
        var payloadObjToSave = parseJsonObject(detail.payload_json || '{}');
        if (finalRoute && !payloadObjToSave.route) {
            payloadObjToSave.route = finalRoute;
        }
        if (finalFormulation && !payloadObjToSave.dosage_form) {
            payloadObjToSave.dosage_form = finalFormulation;
        }
        if (manufacturerCode && !payloadObjToSave.manufacturer_code) {
            payloadObjToSave.manufacturer_code = manufacturerCode;
        }
        if (finalFormulation && !payloadObjToSave.formulation_code) {
            payloadObjToSave.formulation_code = normalizeCode(finalFormulation);
        }
        if ((manufacturerName || manufacturerFromPayload || manufacturerNameMeta || metaCompany) && !payloadObjToSave.manufacturer_name) {
            payloadObjToSave.manufacturer_name = manufacturerName || manufacturerFromPayload || manufacturerNameMeta || metaCompany;
        }
        $('#abdm_drug_payload_json').val(JSON.stringify(payloadObjToSave));
        $('#abdm_drug_last_synced_at').val(detail.synced_at || '');
        showSelectedDrugSummary();
    }

    function fetchDrugDetail(type, identifier) {
        if (!identifier) {
            return;
        }

        $.getJSON('<?= base_url('product_master/drug_terminology_detail') ?>', {
            type: type || 'brand',
            identifier: identifier
        }, function (resp) {
            if (!resp || Number(resp.ok || 0) !== 1 || !resp.selected) {
                return;
            }
            applyDrugDetail(resp.selected);
        });
    }

    function setActiveSuggestion(index) {
        var $links = $('#abdm-drug-suggest a.list-group-item');
        if (!$links.length) {
            activeSuggestionIndex = -1;
            return;
        }

        if (index < 0) {
            index = 0;
        }
        if (index >= $links.length) {
            index = $links.length - 1;
        }

        activeSuggestionIndex = index;
        $links.removeClass('active');
        var $active = $links.eq(index).addClass('active');

        var box = document.getElementById('abdm-drug-suggest');
        if (box && $active.length) {
            var node = $active.get(0);
            var top = node.offsetTop;
            var bottom = top + node.offsetHeight;
            if (top < box.scrollTop) {
                box.scrollTop = top;
            } else if (bottom > box.scrollTop + box.clientHeight) {
                box.scrollTop = bottom - box.clientHeight;
            }
        }
    }

    function chooseSuggestion(index) {
        if (!Array.isArray(suggestionItems) || !suggestionItems.length) {
            return;
        }

        if (index < 0 || index >= suggestionItems.length) {
            return;
        }

        var item = suggestionItems[index] || {};
        var label = item.label || '';
        var brandName = item.brand_name || '';
        var displayName = item.display_name || '';
        var genericName = item.generic_name || '';
        var type = item.type || '';
        var identifier = item.identifier || '';

        clearSuggestionBox();

        // Apply parsed fields immediately so user sees direct form fill.
        applyParsedLabelToForm(label);

        if (type === 'brand' && brandName && !isIdentifierLike(brandName)) {
            $('#input_item_name').val(brandName);
        } else if (displayName && !isIdentifierLike(displayName)) {
            $('#input_item_name').val(displayName);
        }
        if (genericName) {
            $('#input_genericname').val(genericName);
        }

        $('#abdm_drug_display').val((type === 'brand' && brandName) ? brandName : label);
        $('#abdm_drug_type').val(type);
        $('#abdm_drug_identifier').val(identifier);
        $('#abdm_drug_last_synced_at').val(new Date().toISOString().slice(0, 19).replace('T', ' '));
        showSelectedDrugSummary();

        // Fetch canonical details for richer metadata autofill.
        fetchDrugDetail(type, identifier);
    }

    function renderSuggestions(items) {
        var $box = $('#abdm-drug-suggest');
        $box.empty();

        if (!Array.isArray(items) || items.length === 0) {
            clearSuggestionBox();
            return;
        }

        suggestionItems = items.slice();
        activeSuggestionIndex = -1;

        items.forEach(function (item, idx) {
            var label = item.label || '';
            var brandName = item.brand_name || '';
            var displayName = item.display_name || '';
            var genericName = item.generic_name || '';
            var type = item.type || '';
            var identifier = item.identifier || '';
            if (!label && !identifier) {
                return;
            }

            var $a = $('<a href="#" class="list-group-item list-group-item-action py-1 px-2"></a>');
            $a.attr('data-idx', String(idx));
            var mainText = (type === 'brand' ? (brandName || label) : '') || displayName || label || identifier;
            var detailParts = [];
            if (genericName && genericName.toLowerCase() !== String(mainText).toLowerCase()) {
                detailParts.push('Generic: ' + genericName);
            }
            if (type) {
                detailParts.push('Type: ' + type);
            }
            if (identifier && identifier !== mainText) {
                detailParts.push('ID: ' + identifier);
            }
            $a.text(mainText + (detailParts.length ? ' | ' + detailParts.join(' | ') : ''));
            $a.on('click', function (e) {
                e.preventDefault();
                chooseSuggestion(idx);
            });
            $box.append($a);
        });

        if ($box.children().length > 0) {
            $box.show();
            setActiveSuggestion(0);
        } else {
            clearSuggestionBox();
        }
    }

    function fetchDrugSuggestions() {
        var q = ($('#input_item_name').val() || '').trim();
        var type = ($('#abdm_drug_search_type').val() || 'brand').trim();

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

    $('#input_item_name').off('keydown.abdmDrug').on('keydown.abdmDrug', function (evt) {
        var $box = $('#abdm-drug-suggest');
        var isOpen = $box.is(':visible') && suggestionItems.length > 0;

        if (evt.key === 'ArrowDown') {
            if (!isOpen) {
                fetchDrugSuggestions();
                return;
            }
            evt.preventDefault();
            setActiveSuggestion(activeSuggestionIndex + 1);
            return;
        }

        if (evt.key === 'ArrowUp') {
            if (!isOpen) {
                return;
            }
            evt.preventDefault();
            setActiveSuggestion(activeSuggestionIndex - 1);
            return;
        }

        if (evt.key === 'Enter') {
            if (!isOpen) {
                return;
            }
            evt.preventDefault();
            var idx = activeSuggestionIndex >= 0 ? activeSuggestionIndex : 0;
            chooseSuggestion(idx);
            return;
        }

        if (evt.key === 'Tab' && !evt.shiftKey) {
            if (!isOpen) {
                return;
            }
            var tabIdx = activeSuggestionIndex >= 0 ? activeSuggestionIndex : 0;
            chooseSuggestion(tabIdx);
            return;
        }

        if (evt.key === 'Escape') {
            if (!isOpen) {
                return;
            }
            evt.preventDefault();
            clearSuggestionBox();
        }
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

<?php
$mstTestKey = 0;
$Test = '';
$TestID = '';
$Result = '';
$Formula = '';
$VRule = '';
$VMsg = '';
$Unit = '';
$FixedNormals = '';
$isGenderSpecific = 0;
$FixedNormalsWomen = '';
$loincCode = '';
$loincProperty = '';
$loincSystem = '';
$loincScale = '';

if (count($lab_test_parameter ?? []) > 0) {
    $mstTestKey = $lab_test_parameter[0]->mstTestKey ?? 0;
    $Test = $lab_test_parameter[0]->Test ?? '';
    $TestID = $lab_test_parameter[0]->TestID ?? '';
    $Result = $lab_test_parameter[0]->Result ?? '';
    $Formula = $lab_test_parameter[0]->Formula ?? '';
    $VRule = $lab_test_parameter[0]->VRule ?? '';
    $VMsg = $lab_test_parameter[0]->VMsg ?? '';
    $Unit = $lab_test_parameter[0]->Unit ?? '';
    $FixedNormals = $lab_test_parameter[0]->FixedNormals ?? '';
    $isGenderSpecific = (int) ($lab_test_parameter[0]->isGenderSpecific ?? 0);
    $FixedNormalsWomen = $lab_test_parameter[0]->FixedNormalsWomen ?? '';
    $loincCode = $lab_test_parameter[0]->loinc_code ?? '';
    $loincProperty = $lab_test_parameter[0]->loinc_property ?? '';
    $loincSystem = $lab_test_parameter[0]->loinc_system ?? '';
    $loincScale = $lab_test_parameter[0]->loinc_scale ?? '';
}

$normalMin = '';
$normalMax = '';
if (strpos((string) $FixedNormals, '-') !== false) {
    [$normalMin, $normalMax] = array_pad(explode('-', (string) $FixedNormals, 2), 2, '');
}

$femaleNormalMin = '';
$femaleNormalMax = '';
if (strpos((string) $FixedNormalsWomen, '-') !== false) {
    [$femaleNormalMin, $femaleNormalMax] = array_pad(explode('-', (string) $FixedNormalsWomen, 2), 2, '');
}

$valueType = 'TEXT';
if ($Formula !== '') {
    $valueType = 'FORMULA';
} elseif ($VRule !== '' || ($normalMin !== '' || $normalMax !== '')) {
    $valueType = 'NUMERIC';
}
?>
<?= form_open() ?>
<div class="card admin-card component-rule-card">
    <style>
        .component-rule-card {
            border: 0;
            box-shadow: none;
        }

        .component-rule-card .card-header {
            padding: 12px 16px;
        }

        .component-rule-card .card-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 500;
            color: #567299;
        }

        .component-rule-card .card-body {
            padding: 16px 18px 12px;
        }

        .component-rule-card .form-label {
            margin-bottom: 6px;
            font-size: 13px;
            font-weight: 600;
            color: #567299;
        }

        .component-rule-card .form-control,
        .component-rule-card .form-select {
            height: 38px;
            font-size: 14px;
        }

        .component-rule-card textarea.form-control {
            height: auto;
            min-height: 64px;
        }

        .component-rule-grid {
            display: grid;
            grid-template-columns: 170px 1fr 170px 1fr;
            gap: 12px 14px;
            align-items: center;
        }

        .component-rule-grid .wide {
            grid-column: span 3;
        }

        .component-rule-preset {
            display: inline-flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .component-rule-preset .btn {
            font-size: 13px;
            padding: 6px 10px;
        }

        .component-rule-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 14px;
            margin-top: 18px;
            border-top: 1px solid #e6edf5;
        }

        .component-rule-hint {
            font-size: 12px;
            color: #7a8797;
        }

        .component-gateway-meta {
            font-size: 12px;
            color: #5d738f;
            margin-top: 4px;
            line-height: 1.5;
        }

        @media (max-width: 991.98px) {
            .component-rule-grid {
                grid-template-columns: 1fr;
            }

            .component-rule-grid .wide {
                grid-column: auto;
            }
        }
    </style>

    <div class="card-header bg-white">
        <h3>Edit Component Rule</h3>
    </div>
    <div class="card-body">
        <input type="hidden" id="mstTestKey" value="<?= esc($mstTestKey) ?>">
        <input type="hidden" id="mstRepoKey" value="<?= esc($mstRepoKey ?? 0) ?>">
        <input type="hidden" id="input_test_code" value="<?= esc($TestID) ?>">
        <input type="hidden" id="input_Formula" value="<?= esc($Formula) ?>">
        <input type="hidden" id="input_loinc_property" value="<?= esc($loincProperty) ?>">
        <input type="hidden" id="input_loinc_system" value="<?= esc($loincSystem) ?>">
        <input type="hidden" id="input_loinc_scale" value="<?= esc($loincScale) ?>">

        <div class="component-rule-grid">
            <label for="input_Test_name" class="form-label text-end">Component</label>
            <input class="form-control wide" id="input_Test_name" value="<?= esc($Test) ?>" type="text" autocomplete="off">

            <label for="input_loinc_code" class="form-label text-end">LOINC Code</label>
            <div>
                <div class="d-flex gap-2">
                    <input class="form-control" id="input_loinc_code" value="<?= esc($loincCode) ?>" type="text" autocomplete="off">
                    <button type="button" class="btn btn-outline-primary" id="btn_load_gateway_component">Load From API</button>
                </div>
                <div id="gateway_meta_text" class="component-gateway-meta">Property: <?= esc($loincProperty ?: '-') ?> | System: <?= esc($loincSystem ?: '-') ?> | Scale: <?= esc($loincScale ?: '-') ?></div>
            </div>

            <label for="input_Unit" class="form-label text-end">Units</label>
            <input class="form-control" id="input_Unit" placeholder="e.g. g/dL" value="<?= esc($Unit) ?>" type="text" autocomplete="off">

            <label for="rule_value_type" class="form-label text-end">Value Type</label>
            <select class="form-select" id="rule_value_type">
                <option value="NUMERIC" <?= $valueType === 'NUMERIC' ? 'selected' : '' ?>>NUMERIC</option>
                <option value="TEXT" <?= $valueType === 'TEXT' ? 'selected' : '' ?>>TEXT</option>
                <option value="FORMULA" <?= $valueType === 'FORMULA' ? 'selected' : '' ?>>FORMULA</option>
            </select>

            <label class="form-label text-end">Gender Specific</label>
            <div>
                <label class="form-check-label"><input id="chk_isGenderSpecific" class="form-check-input me-2" type="checkbox" <?= $isGenderSpecific === 1 ? 'checked' : '' ?>>Use separate female normal range</label>
            </div>

            <label for="input_Default" class="form-label text-end">Default Value</label>
            <input class="form-control wide" id="input_Default" placeholder="Default result value" value="<?= esc($Result) ?>" type="text" autocomplete="off">

            <label for="input_Validation" class="form-label text-end">Validation Rule</label>
            <input class="form-control wide" id="input_Validation" placeholder="e.g. numeric|min:0|max:100" value="<?= esc($VRule) ?>" type="text" autocomplete="off">

            <label class="form-label text-end">Quick Preset</label>
            <div class="wide">
                <div class="component-rule-preset">
                    <button type="button" class="btn btn-outline-secondary js-rule-preset" data-rule="numeric|min:0|max:100">CBC Numeric</button>
                    <button type="button" class="btn btn-outline-secondary js-rule-preset" data-rule="numeric|min:0|max:1000">LFT Numeric</button>
                    <button type="button" class="btn btn-outline-secondary js-rule-preset" data-rule="numeric|min:0|max:10000">Hormone Numeric</button>
                </div>
            </div>

            <label for="normal_min" class="form-label text-end">Normal Min</label>
            <input class="form-control" id="normal_min" placeholder="e.g. 12.0" value="<?= esc($normalMin) ?>" type="text" autocomplete="off">

            <label for="normal_max" class="form-label text-end">Normal Max</label>
            <input class="form-control" id="normal_max" placeholder="e.g. 15.5" value="<?= esc($normalMax) ?>" type="text" autocomplete="off">

            <label for="female_normal_min" class="form-label text-end">Female Normal Min</label>
            <input class="form-control" id="female_normal_min" placeholder="optional" value="<?= esc($femaleNormalMin) ?>" type="text" autocomplete="off">

            <label for="female_normal_max" class="form-label text-end">Female Normal Max</label>
            <input class="form-control" id="female_normal_max" placeholder="optional" value="<?= esc($femaleNormalMax) ?>" type="text" autocomplete="off">

            <label for="input_Message" class="form-label text-end">Notes</label>
            <textarea class="form-control wide" id="input_Message" placeholder="Adult male, fasting, etc."><?= esc($VMsg) ?></textarea>
        </div>

        <div class="component-rule-hint mt-2">Normal range is stored as `min-max`. Female normal range is used only when gender-specific range is enabled.</div>

        <div class="component-rule-actions">
            <button type="button" class="btn btn-primary" id="btn_item_update">Save Rule</button>
            <button type="button" class="btn btn-outline-secondary" id="btn_component_close">Close</button>
        </div>
    </div>
</div>
<?= form_close() ?>
<script>
(function () {
    var GATEWAY_COMPONENT_SEARCH_URL = '<?= base_url('Lab_Admin/pathology_component_masters_search') ?>';

    function buildRangeValue(minValue, maxValue) {
        var min = String(minValue || '').trim();
        var max = String(maxValue || '').trim();
        if (min === '' && max === '') {
            return '';
        }
        return min + '-' + max;
    }

    function setGatewayMeta(item) {
        var p = document.getElementById('input_loinc_property');
        var s = document.getElementById('input_loinc_system');
        var sc = document.getElementById('input_loinc_scale');
        var text = document.getElementById('gateway_meta_text');

        var property = String((item && item.property) || '').trim();
        var system = String((item && item.specimen_system) || '').trim();
        var scale = String((item && item.scale_type) || '').trim();

        if (p) p.value = property;
        if (s) s.value = system;
        if (sc) sc.value = scale;
        if (text) {
            text.textContent = 'Property: ' + (property || '-') + ' | System: ' + (system || '-') + ' | Scale: ' + (scale || '-');
        }
    }

    function normalizeName(v) {
        return String(v || '').toLowerCase().replace(/[^a-z0-9]+/g, '').trim();
    }

    function pickBestMatch(items, targetName, targetCode) {
        if (!Array.isArray(items) || items.length === 0) {
            return null;
        }

        var wantedName = normalizeName(targetName);
        var wantedCode = String(targetCode || '').trim().toUpperCase();
        var first = items[0];

        for (var i = 0; i < items.length; i += 1) {
            var item = items[i] || {};
            var itemCode = String(item.code || '').trim().toUpperCase();
            if (wantedCode !== '' && itemCode === wantedCode) {
                return item;
            }
        }

        for (var j = 0; j < items.length; j += 1) {
            var it = items[j] || {};
            if (normalizeName(it.name || '') === wantedName) {
                return it;
            }
        }

        return first;
    }

    function loadGatewayComponentMeta(silent) {
        var nameInput = document.getElementById('input_Test_name');
        var codeInput = document.getElementById('input_loinc_code');
        var unitInput = document.getElementById('input_Unit');
        var btn = document.getElementById('btn_load_gateway_component');
        var q = String((nameInput && nameInput.value) || '').trim();

        if (q.length < 2) {
            return;
        }

        if (btn) btn.disabled = true;

        fetch(GATEWAY_COMPONENT_SEARCH_URL + '?q=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (resp) {
            if (btn) btn.disabled = false;
            if (!resp || Number(resp.ok || 0) !== 1) {
                if (!silent && typeof notify === 'function') {
                    notify('warning', 'Gateway lookup failed', (resp && resp.error) ? resp.error : 'Unable to fetch component metadata.');
                }
                return;
            }

            var match = pickBestMatch(resp.items || [], q, codeInput ? codeInput.value : '');
            if (!match) {
                if (!silent && typeof notify === 'function') {
                    notify('warning', 'No match found', 'No matching component metadata found in gateway.');
                }
                return;
            }

            if (codeInput && !String(codeInput.value || '').trim() && match.code) {
                codeInput.value = String(match.code || '').trim();
            }
            if (unitInput && !String(unitInput.value || '').trim() && match.unit) {
                unitInput.value = String(match.unit || '').trim();
            }

            setGatewayMeta(match);

            if (!silent && typeof notify === 'function') {
                notify('success', 'Gateway metadata loaded', 'Component rule helper fields filled from API.');
            }
        })
        .catch(function () {
            if (btn) btn.disabled = false;
            if (!silent && typeof notify === 'function') {
                notify('warning', 'Gateway lookup failed', 'Network/server error while fetching metadata.');
            }
        });
    }

    var gatewayBtn = document.getElementById('btn_load_gateway_component');
    if (gatewayBtn) {
        gatewayBtn.addEventListener('click', function () {
            loadGatewayComponentMeta(false);
        });
    }

    // Auto-load helper metadata when opening edit rule.
    loadGatewayComponentMeta(true);

    document.querySelectorAll('.js-rule-preset').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var rule = String(btn.getAttribute('data-rule') || '').trim();
            var ruleInput = document.getElementById('input_Validation');
            var valueType = document.getElementById('rule_value_type');
            if (ruleInput) {
                ruleInput.value = rule;
            }
            if (valueType) {
                valueType.value = 'NUMERIC';
            }
        });
    });

    document.getElementById('btn_component_close').addEventListener('click', function () {
        load_form_div('<?= base_url('Lab_Admin/report_test_list') ?>/<?= esc($mstRepoKey ?? 0) ?>', 'test_div');
    });

    document.getElementById('btn_item_update').addEventListener('click', function () {
        var input_Test_name = document.getElementById('input_Test_name').value;
        var input_test_code = document.getElementById('input_test_code').value;
        var input_loinc_code = document.getElementById('input_loinc_code').value;
        var input_Default = document.getElementById('input_Default').value;
        var input_Formula = document.getElementById('input_Formula').value;
        var input_Validation = document.getElementById('input_Validation').value;
        var input_Unit = document.getElementById('input_Unit').value;
        var input_loinc_property = document.getElementById('input_loinc_property').value;
        var input_loinc_system = document.getElementById('input_loinc_system').value;
        var input_loinc_scale = document.getElementById('input_loinc_scale').value;
        var input_Message = document.getElementById('input_Message').value;
        var input_Fixed = buildRangeValue(document.getElementById('normal_min').value, document.getElementById('normal_max').value);
        var input_FixedNormalsWomen = buildRangeValue(document.getElementById('female_normal_min').value, document.getElementById('female_normal_max').value);
        var mstTestKey = document.getElementById('mstTestKey').value;
        var mstRepoKey = document.getElementById('mstRepoKey').value;
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
        var isChecked = document.getElementById('chk_isGenderSpecific').checked ? 1 : 0;

        var payload = {
            "input_Test_name": input_Test_name,
            "input_test_code": input_test_code,
            "input_loinc_code": input_loinc_code,
            "input_Default": input_Default,
            "input_Formula": input_Formula,
            "input_Validation": input_Validation,
            "input_Unit": input_Unit,
            "input_loinc_property": input_loinc_property,
            "input_loinc_system": input_loinc_system,
            "input_loinc_scale": input_loinc_scale,
            "input_Message": input_Message,
            "input_Fixed": input_Fixed,
            "input_isChecked": isChecked,
            "input_FixedNormalsWomen": input_FixedNormalsWomen,
            "mstTestKey": mstTestKey,
            "mstRepoKey": mstRepoKey,
            "<?= csrf_token() ?>": csrf_value
        };

        if (Number(mstTestKey) > 0) {
            $.post('<?= base_url('Lab_Admin/test_parameter_edit') ?>', payload, function (data) {
                if (data.showcontent && typeof notify === 'function') {
                    notify('success', 'Saved', data.showcontent);
                }
                load_form_div('<?= base_url('Lab_Admin/report_test_list') ?>/<?= esc($mstRepoKey ?? 0) ?>', 'test_div');
            }, 'json');
        } else {
            $.post('<?= base_url('Lab_Admin/test_parameter_add') ?>', payload, function (data) {
                if (Number(data.insert_id || 0) > 0) {
                    if (typeof notify === 'function') {
                        notify('success', 'Saved', data.showcontent || 'Data Saved successfully');
                    }
                    load_form_div('<?= base_url('Lab_Admin/report_test_list') ?>/<?= esc($mstRepoKey ?? 0) ?>', 'test_div');
                }
            }, 'json');
        }
    });
}());
</script>

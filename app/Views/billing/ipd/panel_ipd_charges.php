<?php
$ipd = $ipd_info ?? null;
$ipdId = (int) ($ipd->id ?? 0);
$packages = $ipd_packages ?? [];
$hasPackage = ! empty($packages);
$defaultPackageId = $hasPackage ? (int) ($packages[0]->id ?? 0) : 0;
$csrfName = csrf_token();
$csrfHash = csrf_hash();
$today = date('Y-m-d');
$bedsideItemsByCategory = $bedside_items_by_category ?? [];
$doctorVisitFeeTypes = $doctor_visit_fee_types ?? [];
$doctorVisitFeeMap = $doctor_visit_fee_map ?? [];
?>

<style>
    .ipd-charge-header {
        background: #f59e0b;
        color: #111827;
        font-weight: 600;
    }
    .ipd-charge-subtotal {
        background: #fff7ed;
        font-weight: 600;
    }
    .ipd-charge-actions .btn {
        padding: 4px 8px;
    }
    .ipd-charge-accordion .accordion-button {
        padding: 8px 12px;
        font-weight: 600;
    }
    .ipd-charge-accordion .accordion-body {
        background: #f8fafc;
    }
</style>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card border-top border-3 border-danger">
            <div class="card-header">
                <strong>IPD Charges List</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px">#</th>
                                <th>Charge Name</th>
                                <th style="width: 110px">Rate</th>
                                <th style="width: 110px">Qty</th>
                                <th style="width: 140px" class="text-end">Amount</th>
                                <th style="width: 90px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($ipd_charges_grouped)) : ?>
                                <?php $srNo = 0; ?>
                                <?php $currentGroup = null; ?>
                                <?php $groupTotal = 0.0; ?>
                                <?php foreach ($ipd_charges_grouped as $row) : ?>
                                    <?php
                                    $groupDesc = $row->group_desc ?? '';
                                    if ($currentGroup !== $groupDesc) {
                                        if ($currentGroup !== null) {
                                            echo '<tr class="ipd-charge-subtotal">';
                                            echo '<td>#</td>';
                                            echo '<td colspan="3">Sub Total</td>';
                                            echo '<td class="text-end">' . esc(number_format($groupTotal, 2)) . '</td>';
                                            echo '<td></td>';
                                            echo '</tr>';
                                        }
                                        $currentGroup = $groupDesc;
                                        $groupTotal = 0.0;
                                        echo '<tr class="ipd-charge-header">';
                                        echo '<th>#</th>';
                                        echo '<th colspan="5">' . esc($currentGroup) . '</th>';
                                        echo '</tr>';
                                    }
                                    $srNo++;
                                    $groupTotal += (float) ($row->item_amount ?? 0);
                                    $itemId = (int) ($row->id ?? 0);
                                    $qty = (float) ($row->item_qty ?? 0);
                                    $rate = (float) ($row->item_rate ?? 0);
                                    $docName = (string) ($row->doc_name ?? '');
                                    $comment = (string) ($row->comment ?? '');
                                    $packageChecked = (int) ($row->package_id ?? 0) > 0 ? 'checked' : '';
                                    ?>
                                    <tr>
                                        <td><?= $srNo ?></td>
                                        <td><?= esc($row->item_name ?? '') ?></td>
                                        <td>
                                            <input type="hidden" id="hidden_rate_<?= $itemId ?>" value="<?= esc($rate) ?>" />
                                            <?= esc(number_format($rate, 2)) ?>
                                        </td>
                                        <td>
                                            <input class="form-control form-control-sm" style="width: 90px" id="input_qty_<?= $itemId ?>" value="<?= esc($qty) ?>" type="number" step="0.01" />
                                        </td>
                                        <td class="text-end"><?= esc(number_format((float) ($row->item_amount ?? 0), 2)) ?></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>#</td>
                                        <td colspan="3">
                                            <?php if ($docName !== '') : ?>
                                                <div>Dr. <?= esc($docName) ?></div>
                                            <?php endif; ?>
                                            <?php if ($comment !== '') : ?>
                                                <div><em><?= esc($comment) ?></em></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($hasPackage) : ?>
                                                <label class="form-check-label">
                                                    <input class="form-check-input" type="checkbox" onchange="ipdChargeTogglePackage(this, <?= $itemId ?>)" <?= $packageChecked ?> />
                                                    Inc. In Pkg.
                                                </label>
                                            <?php endif; ?>
                                        </td>
                                        <td class="ipd-charge-actions">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-warning btn-sm" onclick="ipdChargeUpdate(<?= $itemId ?>)"><i class="bi bi-pencil"></i></button>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="ipdChargeDelete(<?= $itemId ?>)"><i class="bi bi-x"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($currentGroup !== null) : ?>
                                    <tr class="ipd-charge-subtotal">
                                        <td>#</td>
                                        <td colspan="3">Sub Total</td>
                                        <td class="text-end"><?= esc(number_format($groupTotal, 2)) ?></td>
                                        <td></td>
                                    </tr>
                                <?php endif; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No IPD charges found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Total</th>
                                <th class="text-end"><?= esc(number_format((float) ($ipd_charges_total ?? 0), 2)) ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-top border-3 border-warning">
            <div class="card-header">
                <strong>Add Charges</strong>
            </div>
            <div class="card-body">
                <div class="card mb-3 border-info">
                    <div class="card-header py-2"><strong>Bedside Clinical / Nursing Charge</strong></div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label">Item Type</label>
                                <select class="form-select form-select-sm" id="bedside_item_type">
                                    <option value="">All</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <select class="form-select form-select-sm" id="bedside_category">
                                    <option value="">Select</option>
                                    <?php foreach (array_keys($bedsideItemsByCategory) as $categoryName) : ?>
                                        <option value="<?= esc($categoryName) ?>"><?= esc($categoryName) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Item</label>
                                <select class="form-select form-select-sm" id="bedside_item_id">
                                    <option value="">Select</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quick Search</label>
                                <input type="text" class="form-control form-control-sm" id="bedside_item_search" placeholder="Search by code or name">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Rate</label>
                                <input class="form-control form-control-sm" id="bedside_item_rate" value="0" type="number" step="0.01" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Qty</label>
                                <input class="form-control form-control-sm" id="bedside_item_qty" value="1" type="number" step="0.01" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date</label>
                                <input class="form-control form-control-sm" id="bedside_item_date" value="<?= esc($today) ?>" type="date" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Doctor</label>
                                <select class="form-select form-select-sm" id="bedside_doc_id">
                                    <option value="0">NONE</option>
                                    <?php foreach ($doc_list ?? [] as $doc) : ?>
                                        <option value="<?= esc($doc->id ?? '') ?>"><?= esc($doc->DocSpecName ?? '') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Comment</label>
                                <input class="form-control form-control-sm" id="bedside_comment" value="" type="text" />
                            </div>
                            <div class="col-md-12">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="ipdBedsideChargeAdd()">Add Bedside Charge</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3 border-primary">
                    <div class="card-header py-2"><strong>Doctor Visit Charge</strong></div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label">Doctor</label>
                                <select class="form-select form-select-sm" id="doctor_visit_doc_id">
                                    <option value="">Select</option>
                                    <?php foreach ($doc_list ?? [] as $doc) : ?>
                                        <option value="<?= esc($doc->id ?? '') ?>"><?= esc($doc->DocSpecName ?? '') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Visit Type</label>
                                <select class="form-select form-select-sm" id="doctor_visit_fee_type">
                                    <option value="">Select</option>
                                    <?php foreach ($doctorVisitFeeTypes as $row) : ?>
                                        <option value="<?= esc($row['id'] ?? 0) ?>"><?= esc($row['fee_type'] ?? '') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Rate</label>
                                <input class="form-control form-control-sm" id="doctor_visit_rate" value="0.00" type="number" step="0.01" />
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Description</label>
                                <input class="form-control form-control-sm" id="doctor_visit_fee_desc" value="" type="text" readonly />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Qty</label>
                                <input class="form-control form-control-sm" id="doctor_visit_qty" value="1" type="number" step="0.01" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date</label>
                                <input class="form-control form-control-sm" id="doctor_visit_date" value="<?= esc($today) ?>" type="date" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Time</label>
                                <input class="form-control form-control-sm" id="doctor_visit_time" type="time" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Duration (min)</label>
                                <input class="form-control form-control-sm" id="doctor_visit_duration" value="0" type="number" min="0" step="1" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Hospital/Clinic</label>
                                <input class="form-control form-control-sm" id="doctor_visit_hospital" value="" type="text" />
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox" id="doctor_visit_outside" value="1">
                                    <label class="form-check-label" for="doctor_visit_outside">Outside Doctor</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Comment</label>
                                <input class="form-control form-control-sm" id="doctor_visit_comment" value="" type="text" />
                            </div>
                            <div class="col-md-12">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="ipdDoctorVisitChargeAdd()">Add Doctor Visit</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="accordion ipd-charge-accordion" id="ipdChargeAccordion">
                    <?php
                    $typeIndex = 0;
                    $colorMap = ['#f8fafc', '#fef3c7', '#e0f2fe', '#fee2e2', '#dbeafe', '#fef9c3', '#fce7f3', '#dcfce7'];
                    foreach ($item_types ?? [] as $type) :
                        $typeId = (int) ($type->itype_id ?? 0);
                        $items = $item_lists[$typeId] ?? [];
                        $collapseId = 'chargeType' . $typeId;
                        $headingId = 'heading' . $typeId;
                        $typeIndex = ($typeIndex + 1) % count($colorMap);
                    ?>
                        <div class="accordion-item" style="background: <?= esc($colorMap[$typeIndex]) ?>">
                            <h2 class="accordion-header" id="<?= esc($headingId) ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= esc($collapseId) ?>" aria-expanded="false" aria-controls="<?= esc($collapseId) ?>">
                                    <?= esc($type->group_desc ?? '') ?>
                                </button>
                            </h2>
                            <div id="<?= esc($collapseId) ?>" class="accordion-collapse collapse" aria-labelledby="<?= esc($headingId) ?>" data-bs-parent="#ipdChargeAccordion">
                                <div class="accordion-body">
                                    <div class="mb-2">
                                        <label class="form-label">Charge Name</label>
                                        <select class="form-select form-select-sm" id="itype_name_id_<?= $typeId ?>" onchange="ipdChargeSetRate(<?= $typeId ?>)">
                                            <option value="">Select</option>
                                            <?php foreach ($items as $item) : ?>
                                                <?php $displayRate = (float) ($item->display_amount ?? $item->amount ?? 0); ?>
                                                <option value="<?= esc($item->id ?? '') ?>" data-rate="<?= esc($displayRate) ?>">
                                                    <?= esc($item->idesc ?? '') ?> [<?= esc(number_format($displayRate, 2)) ?>]
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label">Rate</label>
                                            <input class="form-control form-control-sm" id="input_rate_<?= $typeId ?>" value="0.00" type="number" step="0.01" />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Qty</label>
                                            <input class="form-control form-control-sm" id="input_qty_<?= $typeId ?>" value="1" type="number" step="0.01" />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Date</label>
                                            <input class="form-control form-control-sm" id="datepicker_itemdate_<?= $typeId ?>" value="<?= esc($today) ?>" type="date" />
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <label class="form-label">Doctor</label>
                                            <select class="form-select form-select-sm" id="doc_name_id_<?= $typeId ?>">
                                                <option value="0">NONE</option>
                                                <?php foreach ($doc_list ?? [] as $doc) : ?>
                                                    <option value="<?= esc($doc->id ?? '') ?>"><?= esc($doc->DocSpecName ?? '') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Comment</label>
                                            <input class="form-control form-control-sm" id="input_comment_<?= $typeId ?>" value="" type="text" />
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-primary btn-sm" onclick="ipdChargeAdd(<?= $typeId ?>)">Add in List</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var ipdChargeCsrfName = '<?= esc($csrfName) ?>';
    var ipdChargeCsrfHash = '<?= esc($csrfHash) ?>';
    var ipdChargeBaseUrl = '<?= base_url() ?>';
    var ipdChargeIpdId = <?= (int) $ipdId ?>;
    var ipdChargeTabRefreshUrl = '<?= base_url('billing/ipd/panel/' . $ipdId . '/tab/ipd-charges') ?>';
    var ipdChargeDefaultPackageId = <?= (int) $defaultPackageId ?>;
    var bedsideItemsByCategory = <?= json_encode($bedsideItemsByCategory, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var doctorVisitFeeTypes = <?= json_encode($doctorVisitFeeTypes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var doctorVisitFeeMap = <?= json_encode($doctorVisitFeeMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function getAllBedsideItems() {
        var all = [];
        Object.keys(bedsideItemsByCategory || {}).forEach(function (categoryName) {
            var rows = bedsideItemsByCategory[categoryName] || [];
            rows.forEach(function (row) {
                all.push(row);
            });
        });

        return all;
    }

    function ipdChargeSetRate(typeId) {
        var select = document.getElementById('itype_name_id_' + typeId);
        var rateInput = document.getElementById('input_rate_' + typeId);
        if (!select || !rateInput) {
            return;
        }
        var selected = select.options[select.selectedIndex];
        var rate = selected ? selected.getAttribute('data-rate') : 0;
        rateInput.value = rate || 0;
    }

    function bedsideRefreshTypeOptions() {
        var allItems = getAllBedsideItems();
        var selectedType = $('#bedside_item_type').val() || '';
        var types = {};

        allItems.forEach(function (item) {
            var typeName = String(item.item_type || '').trim();
            if (typeName !== '') {
                types[typeName] = true;
            }
        });

        var typeSelect = $('#bedside_item_type');
        typeSelect.empty().append('<option value="">All</option>');
        Object.keys(types).sort().forEach(function (typeName) {
            typeSelect.append('<option value="' + typeName + '">' + typeName + '</option>');
        });

        if (selectedType !== '' && types[selectedType]) {
            typeSelect.val(selectedType);
        }
    }

    function bedsideRefreshCategories() {
        var selectedType = $('#bedside_item_type').val() || '';
        var selectedCategory = $('#bedside_category').val() || '';
        var allItems = getAllBedsideItems();
        var categories = {};

        allItems.forEach(function (item) {
            var typeName = String(item.item_type || '').trim();
            if (selectedType !== '' && typeName !== selectedType) {
                return;
            }

            var categoryName = String(item.category || '').trim();
            if (categoryName === '') {
                categoryName = 'General';
            }
            categories[categoryName] = true;
        });

        var categorySelect = $('#bedside_category');
        categorySelect.empty().append('<option value="">Select</option>');
        Object.keys(categories).sort().forEach(function (categoryName) {
            categorySelect.append('<option value="' + categoryName + '">' + categoryName + '</option>');
        });

        if (selectedCategory !== '' && categories[selectedCategory]) {
            categorySelect.val(selectedCategory);
        }
    }

    function bedsideRefreshItems() {
        var selectedType = $('#bedside_item_type').val() || '';
        var category = $('#bedside_category').val() || '';
        var search = ($('#bedside_item_search').val() || '').toLowerCase().trim();
        var itemSelect = $('#bedside_item_id');
        itemSelect.empty().append('<option value="">Select</option>');

        var items = (bedsideItemsByCategory[category] || []).filter(function (item) {
            var typeName = String(item.item_type || '').trim();
            var itemName = String(item.item_name || '').toLowerCase();
            var itemCode = String(item.item_code || '').toLowerCase();

            if (search !== '' && itemName.indexOf(search) === -1 && itemCode.indexOf(search) === -1) {
                return false;
            }

            return selectedType === '' || typeName === selectedType;
        });
        items.forEach(function(item) {
            var rate = Number(item.default_rate || 0);
            var label = (item.item_name || '') + ' [' + rate.toFixed(2) + ']';
            var insuranceCode = String(item.insurance_code || '').trim();
            if (insuranceCode !== '') {
                label += ' {' + insuranceCode + '}';
            }
            var option = $('<option></option>')
                .attr('value', item.item_id)
                .attr('data-rate', rate)
                .text(label);
            itemSelect.append(option);
        });

        $('#bedside_item_rate').val(items.length > 0 ? Number(items[0].default_rate || 0).toFixed(2) : '0.00');
    }

    function bedsideSetRate() {
        var selected = $('#bedside_item_id option:selected');
        var rate = selected.data('rate');
        $('#bedside_item_rate').val(rate !== undefined ? Number(rate).toFixed(2) : '0.00');
    }

    function ipdChargeAdd(typeId) {
        var itemId = document.getElementById('itype_name_id_' + typeId).value;
        var rate = document.getElementById('input_rate_' + typeId).value;
        var qty = document.getElementById('input_qty_' + typeId).value;
        var dateItem = document.getElementById('datepicker_itemdate_' + typeId).value;
        var comment = document.getElementById('input_comment_' + typeId).value;
        var docId = document.getElementById('doc_name_id_' + typeId).value;

        if (!itemId) {
            alert('Select a charge item.');
            return;
        }

        var payload = {};
        payload[ipdChargeCsrfName] = ipdChargeCsrfHash;
        payload.item_type = typeId;
        payload.item_id = itemId;
        payload.item_rate = rate;
        payload.item_qty = qty;
        payload.item_date = dateItem;
        payload.comment = comment;
        payload.doc_id = docId;

        $.post(ipdChargeBaseUrl + 'billing/ipd/charge/add/' + ipdChargeIpdId, payload, function() {
            load_form_div(ipdChargeTabRefreshUrl, 'tab_charges_content');
        });
    }

    function ipdBedsideChargeAdd() {
        var bedsideItemId = $('#bedside_item_id').val() || '';
        if (!bedsideItemId) {
            alert('Select bedside item.');
            return;
        }

        var payload = {};
        payload[ipdChargeCsrfName] = ipdChargeCsrfHash;
        payload.bedside_item_id = bedsideItemId;
        payload.item_rate = $('#bedside_item_rate').val() || 0;
        payload.item_qty = $('#bedside_item_qty').val() || 1;
        payload.item_date = $('#bedside_item_date').val() || '<?= esc($today) ?>';
        payload.comment = $('#bedside_comment').val() || '';
        payload.doc_id = $('#bedside_doc_id').val() || 0;

        $.post(ipdChargeBaseUrl + 'billing/ipd/bedside-charge/add/' + ipdChargeIpdId, payload, function(resp) {
            if (resp && resp.ok) {
                load_form_div(ipdChargeTabRefreshUrl, 'tab_charges_content');
                return;
            }
            alert((resp && resp.error) ? resp.error : 'Unable to add bedside charge');
        }, 'json').fail(function(xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Unable to add bedside charge';
            alert(msg);
        });
    }

    function doctorVisitRefreshTypes() {
        var docId = parseInt($('#doctor_visit_doc_id').val() || '0', 10);
        var selectedFeeType = $('#doctor_visit_fee_type').val() || '';
        var feeTypeSelect = $('#doctor_visit_fee_type');
        feeTypeSelect.empty().append('<option value="">Select</option>');

        if (docId > 0 && doctorVisitFeeMap[docId]) {
            var docFees = doctorVisitFeeMap[docId];
            Object.keys(docFees).forEach(function (feeTypeId) {
                var row = docFees[feeTypeId] || {};
                var label = String(row.fee_type || '').trim();
                if (label === '') {
                    label = 'Visit Type ' + feeTypeId;
                }
                feeTypeSelect.append('<option value="' + feeTypeId + '">' + label + '</option>');
            });
        } else {
            (doctorVisitFeeTypes || []).forEach(function (row) {
                feeTypeSelect.append('<option value="' + row.id + '">' + (row.fee_type || '') + '</option>');
            });
        }

        if (selectedFeeType !== '' && feeTypeSelect.find('option[value="' + selectedFeeType + '"]').length > 0) {
            feeTypeSelect.val(selectedFeeType);
        }
    }

    function doctorVisitRefreshRate() {
        var docId = parseInt($('#doctor_visit_doc_id').val() || '0', 10);
        var feeTypeId = parseInt($('#doctor_visit_fee_type').val() || '0', 10);
        var feeDetail = null;

        if (docId > 0 && doctorVisitFeeMap[docId]) {
            var docFees = doctorVisitFeeMap[docId];
            if (feeTypeId > 0 && docFees[feeTypeId]) {
                feeDetail = docFees[feeTypeId];
            } else {
                var firstKey = Object.keys(docFees)[0] || null;
                if (firstKey && docFees[firstKey]) {
                    feeDetail = docFees[firstKey];
                    $('#doctor_visit_fee_type').val(String(firstKey));
                }
            }
        }

        if (feeDetail) {
            $('#doctor_visit_rate').val(Number(feeDetail.amount || 0).toFixed(2));
            $('#doctor_visit_fee_desc').val(feeDetail.fee_desc || feeDetail.fee_type || '');
            return;
        }

        $('#doctor_visit_rate').val('0.00');
        $('#doctor_visit_fee_desc').val('');
    }

    function ipdDoctorVisitChargeAdd() {
        var docId = $('#doctor_visit_doc_id').val() || '';
        if (!docId) {
            alert('Select doctor.');
            return;
        }

        var payload = {};
        payload[ipdChargeCsrfName] = ipdChargeCsrfHash;
        payload.doc_id = docId;
        payload.fee_type_id = $('#doctor_visit_fee_type').val() || 0;
        payload.item_rate = $('#doctor_visit_rate').val() || 0;
        payload.item_qty = $('#doctor_visit_qty').val() || 1;
        payload.item_date = $('#doctor_visit_date').val() || '<?= esc($today) ?>';
        payload.visit_time = $('#doctor_visit_time').val() || '';
        payload.duration = $('#doctor_visit_duration').val() || 0;
        payload.hospital_clinic = $('#doctor_visit_hospital').val() || '';
        payload.is_outside_doctor = $('#doctor_visit_outside').is(':checked') ? 1 : 0;
        payload.comment = $('#doctor_visit_comment').val() || '';

        $.post(ipdChargeBaseUrl + 'billing/ipd/doctor-visit/add/' + ipdChargeIpdId, payload, function(resp) {
            if (resp && resp.ok) {
                load_form_div(ipdChargeTabRefreshUrl, 'tab_charges_content');
                return;
            }
            alert((resp && resp.error) ? resp.error : 'Unable to add doctor visit charge');
        }, 'json').fail(function(xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Unable to add doctor visit charge';
            alert(msg);
        });
    }

    function ipdChargeUpdate(itemId) {
        var qty = document.getElementById('input_qty_' + itemId).value;
        var rate = document.getElementById('hidden_rate_' + itemId).value;

        var payload = {};
        payload[ipdChargeCsrfName] = ipdChargeCsrfHash;
        payload.item_qty = qty;
        payload.item_rate = rate;

        $.post(ipdChargeBaseUrl + 'billing/ipd/charge/update/' + itemId, payload, function() {
            load_form_div(ipdChargeTabRefreshUrl, 'tab_charges_content');
        });
    }

    function ipdChargeDelete(itemId) {
        if (!confirm('Remove this charge item?')) {
            return;
        }

        var payload = {};
        payload[ipdChargeCsrfName] = ipdChargeCsrfHash;

        $.post(ipdChargeBaseUrl + 'billing/ipd/charge/delete/' + itemId, payload, function(resp) {
            if (resp && resp.ok) {
                load_form_div(ipdChargeTabRefreshUrl, 'tab_charges_content');
                return;
            }
            alert((resp && resp.error) ? resp.error : 'Unable to remove charge item');
        }, 'json').fail(function(xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Unable to remove charge item';
            alert(msg);
        });
    }

    function ipdChargeTogglePackage(checkbox, itemId) {
        var payload = {};
        payload[ipdChargeCsrfName] = ipdChargeCsrfHash;
        payload.package_id = checkbox.checked ? ipdChargeDefaultPackageId : 0;

        $.post(ipdChargeBaseUrl + 'billing/ipd/charge/package/' + itemId, payload, function() {
            load_form_div(ipdChargeTabRefreshUrl, 'tab_charges_content');
        });
    }

    $('#bedside_item_type').on('change', function () {
        bedsideRefreshCategories();
        bedsideRefreshItems();
    });
    $('#bedside_category').on('change', bedsideRefreshItems);
    $('#bedside_item_search').on('input', bedsideRefreshItems);
    $('#bedside_item_id').on('change', bedsideSetRate);
    $('#doctor_visit_doc_id').on('change', function () {
        doctorVisitRefreshTypes();
        doctorVisitRefreshRate();
    });
    $('#doctor_visit_fee_type').on('change', doctorVisitRefreshRate);

    bedsideRefreshTypeOptions();
    bedsideRefreshCategories();
    bedsideRefreshItems();
    doctorVisitRefreshTypes();
    doctorVisitRefreshRate();
</script>

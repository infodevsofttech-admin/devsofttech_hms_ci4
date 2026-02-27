<?php
$ipd = $ipd_info ?? null;
$ipdId = (int) ($ipd->id ?? 0);
$packages = $ipd_packages ?? [];
$hasPackage = ! empty($packages);
$defaultPackageId = $hasPackage ? (int) ($packages[0]->id ?? 0) : 0;
$csrfName = csrf_token();
$csrfHash = csrf_hash();
$today = date('Y-m-d');
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
    var ipdChargeDefaultPackageId = <?= (int) $defaultPackageId ?>;

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
            load_form_div('<?= base_url('IpdNew/show_ipd_items/' . $ipdId . '/1') ?>', 'tab_charges_content');
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
            load_form_div('<?= base_url('IpdNew/show_ipd_items/' . $ipdId . '/1') ?>', 'tab_charges_content');
        });
    }

    function ipdChargeDelete(itemId) {
        if (!confirm('Remove this charge item?')) {
            return;
        }

        var payload = {};
        payload[ipdChargeCsrfName] = ipdChargeCsrfHash;

        $.post(ipdChargeBaseUrl + 'billing/ipd/charge/delete/' + itemId, payload, function() {
            load_form_div('<?= base_url('IpdNew/show_ipd_items/' . $ipdId . '/1') ?>', 'tab_charges_content');
        });
    }

    function ipdChargeTogglePackage(checkbox, itemId) {
        var payload = {};
        payload[ipdChargeCsrfName] = ipdChargeCsrfHash;
        payload.package_id = checkbox.checked ? ipdChargeDefaultPackageId : 0;

        $.post(ipdChargeBaseUrl + 'billing/ipd/charge/package/' + itemId, payload, function() {
            load_form_div('<?= base_url('IpdNew/show_ipd_items/' . $ipdId . '/1') ?>', 'tab_charges_content');
        });
    }
</script>

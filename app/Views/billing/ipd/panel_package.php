<?php
$ipd = $ipd_info ?? null;
$ipdId = (int) ($ipd->id ?? 0);
$csrfName = csrf_token();
$csrfHash = csrf_hash();
?>

<div class="card border-top border-3 border-danger">
    <div class="card-header">
        <strong>IPD Package</strong>
    </div>
    <div class="card-body">
        <div class="table-responsive mb-3">
            <table class="table table-striped table-sm align-middle">
                <thead>
                    <tr>
                        <th style="width: 40px">#</th>
                        <th>Package Name</th>
                        <th style="width: 140px">Amount</th>
                        <th style="width: 180px"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($ipd_packages)) : ?>
                        <?php $srNo = 1; ?>
                        <?php foreach ($ipd_packages as $row) : ?>
                            <tr>
                                <td><?= $srNo++ ?></td>
                                <td>
                                    <?= esc($row->package_name ?? '') ?>
                                    <?php if (! empty($row->package_desc ?? $row->comment ?? '')) : ?>
                                        <br><em><?= esc($row->package_desc ?? $row->comment ?? '') ?></em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input class="form-control form-control-sm" style="width: 120px" id="input_amt_<?= (int) ($row->id ?? 0) ?>" value="<?= esc($row->package_Amount ?? 0) ?>" type="number" step="0.01" />
                                </td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="ipdPackageUpdate(<?= (int) ($row->id ?? 0) ?>)">Update</button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="ipdPackageRemove(<?= (int) ($row->id ?? 0) ?>)">-Remove</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No packages found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="accordion" id="accordion_package">
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading_package_manual">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_package_manual" aria-expanded="false">
                        Manual Package
                    </button>
                </h2>
                <div id="collapse_package_manual" class="accordion-collapse collapse" aria-labelledby="heading_package_manual" data-bs-parent="#accordion_package">
                    <div class="accordion-body">
                        <div class="row g-2">
                            <div class="col-md-12">
                                <label class="form-label">Package Name</label>
                                <input class="form-control form-control-sm" id="input_pakage_name_m" value="" type="text" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Comment</label>
                                <input class="form-control form-control-sm" id="input_Package_comment_m" value="" type="text" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Rate</label>
                                <input class="form-control form-control-sm" id="input_amount_m" value="0.00" type="number" step="0.01" />
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary btn-sm" onclick="ipdPackageAddManual()">Add in List</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading_package_list">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_package_list" aria-expanded="false">
                        Package List
                    </button>
                </h2>
                <div id="collapse_package_list" class="accordion-collapse collapse" aria-labelledby="heading_package_list" data-bs-parent="#accordion_package">
                    <div class="accordion-body">
                        <div class="row g-2">
                            <div class="col-md-12">
                                <label class="form-label">Package Name</label>
                                <select class="form-select form-select-sm" id="package_list_id" onchange="ipdPackageSetRate()">
                                    <option value="">Select</option>
                                    <?php foreach ($package_list ?? [] as $row) : ?>
                                        <?php $displayAmount = (float) ($row->amount1 ?? $row->Pakage_Min_Amount ?? 0); ?>
                                        <option value="<?= esc($row->id ?? '') ?>" data-rate="<?= esc($displayAmount) ?>">
                                            <?= esc(($row->ipd_pakage_name ?? '') . '  : ' . ($row->org_code ?? '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Amount</label>
                                <input class="form-control form-control-sm" id="input_amount_p" value="0.00" type="number" step="0.01" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Comment</label>
                                <input class="form-control form-control-sm" id="input_comment_p" value="" type="text" />
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-primary btn-sm" onclick="ipdPackageAddPredefine()">Add in List</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var ipdPackageCsrfName = '<?= esc($csrfName) ?>';
    var ipdPackageCsrfHash = '<?= esc($csrfHash) ?>';
    var ipdPackageBaseUrl = '<?= base_url() ?>';
    var ipdPackageIpdId = <?= (int) $ipdId ?>;

    function ipdPackageReload() {
        load_form_div('<?= base_url('IpdNew/ipd_package/' . $ipdId) ?>', 'tab_package_content');
    }

    function ipdPackageSetRate() {
        var select = document.getElementById('package_list_id');
        var rateInput = document.getElementById('input_amount_p');
        if (!select || !rateInput) {
            return;
        }
        var selected = select.options[select.selectedIndex];
        var rate = selected ? selected.getAttribute('data-rate') : 0;
        rateInput.value = rate || 0;
    }

    function ipdPackageAddManual() {
        var payload = {};
        payload[ipdPackageCsrfName] = ipdPackageCsrfHash;
        payload.package_name = document.getElementById('input_pakage_name_m').value;
        payload.package_id = 1;
        payload.input_amount = document.getElementById('input_amount_m').value;
        payload.comment = document.getElementById('input_Package_comment_m').value;

        $.post(ipdPackageBaseUrl + 'billing/ipd/package/add/' + ipdPackageIpdId, payload, function() {
            ipdPackageReload();
        });
    }

    function ipdPackageAddPredefine() {
        var payload = {};
        payload[ipdPackageCsrfName] = ipdPackageCsrfHash;
        payload.package_name = $('#package_list_id option:selected').text();
        payload.package_id = document.getElementById('package_list_id').value;
        payload.input_amount = document.getElementById('input_amount_p').value;
        payload.comment = document.getElementById('input_comment_p').value;

        if (!payload.package_id) {
            alert('Select a package.');
            return;
        }

        $.post(ipdPackageBaseUrl + 'billing/ipd/package/add/' + ipdPackageIpdId, payload, function() {
            ipdPackageReload();
        });
    }

    function ipdPackageUpdate(itemId) {
        var payload = {};
        payload[ipdPackageCsrfName] = ipdPackageCsrfHash;
        payload.input_amount = document.getElementById('input_amt_' + itemId).value;

        $.post(ipdPackageBaseUrl + 'billing/ipd/package/update/' + itemId, payload, function() {
            ipdPackageReload();
        });
    }

    function ipdPackageRemove(itemId) {
        if (!confirm('Are you sure Remove this item ?')) {
            return;
        }

        var payload = {};
        payload[ipdPackageCsrfName] = ipdPackageCsrfHash;

        $.post(ipdPackageBaseUrl + 'billing/ipd/package/delete/' + itemId, payload, function() {
            ipdPackageReload();
        });
    }
</script>

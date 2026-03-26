<?php
$ipd = $ipd_info ?? null;
$ipdId = (int) ($ipd->id ?? 0);
$csrfName = csrf_token();
$csrfHash = csrf_hash();
$insuranceLabel = trim((string) ($ipd->ins_company_name ?? ''));
$schemeLabel = trim((string) ($ipd->org_insurance_comp ?? ''));
$caseLabel = strtolower(trim($insuranceLabel . ' ' . $schemeLabel));
$isAyushmanCase = strpos($caseLabel, 'ayushman') !== false || strpos($caseLabel, 'pmjay') !== false || strpos($caseLabel, 'bharat') !== false;
$ayushmanCollapseClass = $isAyushmanCase ? 'accordion-collapse collapse show' : 'accordion-collapse collapse';
$ayushmanButtonClass = $isAyushmanCase ? 'accordion-button' : 'accordion-button collapsed';
$ayushmanChecklistItems = $ayushman_checklist_items ?? [];
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
                            <?php
                            $isAyushmanRow = stripos((string) ($row->package_desc ?? ''), 'Ayushman') !== false
                                || stripos((string) ($row->comment ?? ''), 'Ayushman Code:') !== false;
                            ?>
                            <tr>
                                <td><?= $srNo++ ?></td>
                                <td>
                                    <?= esc($row->package_name ?? '') ?>
                                    <?php if ($isAyushmanRow) : ?>
                                        <span class="badge bg-success ms-1">Ayushman</span>
                                    <?php endif; ?>
                                    <?php if (! empty($row->org_code ?? '')) : ?>
                                        <span class="badge bg-info text-dark ms-1"><?= esc($row->org_code ?? '') ?></span>
                                    <?php endif; ?>
                                    <?php if (! empty($row->package_desc ?? $row->comment ?? '')) : ?>
                                        <br><em><?= esc($row->package_desc ?? $row->comment ?? '') ?></em>
                                    <?php endif; ?>
                                    <?php if (! empty($row->comment ?? '') && ($row->comment ?? '') !== ($row->package_desc ?? '')) : ?>
                                        <br><small class="text-muted"><?= esc($row->comment ?? '') ?></small>
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
            <?php if ($isAyushmanCase) : ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading_package_checklist">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_package_checklist" aria-expanded="true">
                        Ayushman Preauth Checklist
                    </button>
                </h2>
                <div id="collapse_package_checklist" class="accordion-collapse collapse show" aria-labelledby="heading_package_checklist" data-bs-parent="#accordion_package">
                    <div class="accordion-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="ayushman_preauth_send" <?= (int) ($ipd->preauth_send ?? 0) === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ayushman_preauth_send">Preauth Sent</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="ayushman_doc_recd" <?= (int) ($ipd->doc_recd ?? 0) === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ayushman_doc_recd">Documents Received</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="ayushman_final_bill_send" <?= (int) ($ipd->final_bill_send ?? 0) === 1 ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ayushman_final_bill_send">Final Bill Sent</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Approval Status</label>
                                <select class="form-select form-select-sm" id="ayushman_org_status">
                                    <?php $currentOrgStatus = trim((string) ($ipd->org_approved_status ?? 'Under Process')); ?>
                                    <?php foreach (['Under Process', 'Query', 'Approved', 'Deny'] as $statusLabel) : ?>
                                        <option value="<?= esc($statusLabel) ?>" <?= $currentOrgStatus === $statusLabel ? 'selected' : '' ?>><?= esc($statusLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Case Remark</label>
                                <textarea class="form-control form-control-sm" rows="2" id="ayushman_case_remark"><?= esc((string) ($ipd->remark ?? '')) ?></textarea>
                            </div>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Selected Procedure</th>
                                        <th>Speciality</th>
                                        <th>Preauth</th>
                                        <th>Pre Investigations</th>
                                        <th>Post Investigations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (! empty($ayushmanChecklistItems)) : ?>
                                        <?php foreach ($ayushmanChecklistItems as $item) : ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?= esc(($item['procedure_name'] ?? '') . ' [' . ($item['procedure_code'] ?? '') . ']') ?></div>
                                                    <div class="small text-muted">Package: <?= esc($item['package_name'] ?? '') ?></div>
                                                </td>
                                                <td><?= esc(($item['speciality_name'] ?? '') . ' [' . ($item['speciality_code'] ?? '') . ']') ?></td>
                                                <td><?= (int) ($item['preauth_required'] ?? 0) === 1 ? '<span class="badge bg-warning text-dark">Required</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                                                <td><?= esc((string) ($item['pre_investigations'] ?? '-')) ?></td>
                                                <td><?= esc((string) ($item['post_investigations'] ?? '-')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No Ayushman procedures have been added to this IPD package list yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-primary btn-sm" onclick="ipdAyushmanSaveChecklist()">Save Checklist</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="ipdAyushmanClaimSheetPreview()">Preview Claim Sheet</button>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="ipdAyushmanClaimSheetExport()">Export Claim Sheet</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading_package_ayushman">
                    <button class="<?= $ayushmanButtonClass ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_package_ayushman" aria-expanded="<?= $isAyushmanCase ? 'true' : 'false' ?>">
                        Ayushman Bharat Treatment Search
                    </button>
                </h2>
                <div id="collapse_package_ayushman" class="<?= $ayushmanCollapseClass ?>" aria-labelledby="heading_package_ayushman" data-bs-parent="#accordion_package">
                    <div class="accordion-body">
                        <div class="alert <?= $isAyushmanCase ? 'alert-success' : 'alert-secondary' ?> py-2 mb-3">
                            <div><strong>Insurance:</strong> <?= esc($insuranceLabel !== '' ? $insuranceLabel : 'Not set') ?></div>
                            <div><strong>Scheme:</strong> <?= esc($schemeLabel !== '' ? $schemeLabel : 'Not set') ?></div>
                            <div><strong>Preauth Status:</strong> <?= esc((string) ($ipd->org_approved_status ?? 'Under Process')) ?></div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Speciality</label>
                                <select class="form-select form-select-sm" id="ayushman_speciality_code">
                                    <option value="">All</option>
                                    <?php foreach ($ayushman_specialities ?? [] as $speciality) : ?>
                                        <option value="<?= esc($speciality['speciality_code'] ?? '') ?>">
                                            <?= esc(($speciality['speciality_name'] ?? '') . ' [' . ($speciality['speciality_code'] ?? '') . ']') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Procedure Search</label>
                                <input class="form-control form-control-sm" id="ayushman_search_query" type="text" placeholder="Procedure name or code" />
                            </div>
                            <div class="col-md-3 d-flex align-items-end gap-2">
                                <button type="button" class="btn btn-success btn-sm" onclick="ipdAyushmanSearch()">Search</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="ipdAyushmanClear()">Clear</button>
                            </div>
                        </div>
                        <div id="ayushman_search_summary" class="small text-muted mb-2">Search imported Ayushman Bharat treatment list and add the selected treatment as an IPD package entry.</div>
                        <div class="table-responsive">
                            <table class="table table-striped table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Procedure</th>
                                        <th>Speciality</th>
                                        <th>Mapped Package</th>
                                        <th>Amount</th>
                                        <th>Preauth</th>
                                        <th style="width: 170px"></th>
                                    </tr>
                                </thead>
                                <tbody id="ayushman_search_results">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Run a search to view Ayushman treatments.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php else : ?>
            <div class="alert alert-light border mt-3 mb-0">
                Ayushman Bharat treatment search is shown automatically for cases where insurance or scheme contains Ayushman or PMJAY.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="ayushmanMappingModal" tabindex="-1" aria-labelledby="ayushmanMappingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ayushmanMappingModalLabel">Map Ayushman Procedure to Internal Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ayushman_mapping_item_id" value="0" />
                <div class="mb-3">
                    <div class="fw-semibold" id="ayushman_mapping_title">Select a procedure</div>
                    <div class="small text-muted" id="ayushman_mapping_meta"></div>
                </div>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Internal Package</label>
                        <select class="form-select" id="ayushman_mapping_package_id">
                            <option value="0">Not linked</option>
                            <?php foreach ($all_package_options ?? [] as $row) : ?>
                                <option value="<?= (int) ($row['id'] ?? 0) ?>">
                                    <?= esc(trim((string) ($row['pakage_group_name'] ?? '')) . ' | ' . trim((string) ($row['ipd_pakage_name'] ?? '')) . ' | Rs. ' . number_format((float) ($row['Pakage_Min_Amount'] ?? 0), 2)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="ipdAyushmanSaveMapping()">Save Mapping</button>
            </div>
        </div>
    </div>
</div>

<script>
    var ipdPackageCsrfName = '<?= esc($csrfName) ?>';
    var ipdPackageCsrfHash = '<?= esc($csrfHash) ?>';
    var ipdPackageBaseUrl = '<?= base_url() ?>';
    var ipdPackageIpdId = <?= (int) $ipdId ?>;
    var ipdAyushmanSearchUrl = '<?= site_url('billing/ipd/panel/' . $ipdId . '/ayushman/search') ?>';
    var ipdAyushmanMapUrlBase = '<?= site_url('billing/ipd/panel/' . $ipdId . '/ayushman/map') ?>';
    var ipdAyushmanChecklistUrl = '<?= site_url('billing/ipd/panel/' . $ipdId . '/ayushman/checklist') ?>';
    var ipdAyushmanClaimSheetUrl = '<?= site_url('billing/ipd/panel/' . $ipdId . '/ayushman/claim-sheet') ?>';
    var ipdAyushmanResults = {};

    function normalizeText(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9\s]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function scorePackageSuggestion(procedureName, packageName) {
        var p = normalizeText(procedureName);
        var q = normalizeText(packageName);
        if (!p || !q) {
            return 0;
        }

        var score = 0;
        if (q.indexOf(p) >= 0 || p.indexOf(q) >= 0) {
            score += 8;
        }

        var pWords = p.split(' ');
        var qWords = q.split(' ');
        pWords.forEach(function(word) {
            if (word.length >= 4 && qWords.indexOf(word) >= 0) {
                score += 2;
            }
        });

        return score;
    }

    function ipdAyushmanSuggestPackageId(procedureName) {
        var select = document.getElementById('ayushman_mapping_package_id');
        if (!select) {
            return 0;
        }

        var bestId = 0;
        var bestScore = 0;
        for (var i = 0; i < select.options.length; i++) {
            var option = select.options[i];
            var id = parseInt(option.value || '0', 10);
            if (id <= 0) {
                continue;
            }
            var score = scorePackageSuggestion(procedureName, option.text || '');
            if (score > bestScore) {
                bestScore = score;
                bestId = id;
            }
        }

        return bestScore >= 4 ? bestId : 0;
    }

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

    function ipdAyushmanRenderResults(items) {
        var tbody = $('#ayushman_search_results');
        tbody.empty();
        ipdAyushmanResults = {};

        if (!items || !items.length) {
            tbody.append('<tr><td colspan="6" class="text-center text-muted">No Ayushman treatment matched the search.</td></tr>');
            $('#ayushman_search_summary').text('No matching treatments found.');
            return;
        }

        $('#ayushman_search_summary').text(items.length + ' treatment(s) found.');

        items.forEach(function(item) {
            ipdAyushmanResults[String(item.id)] = item;

            var procedure = $('<div></div>');
            procedure.append($('<div class="fw-semibold"></div>').text((item.procedure_name || '') + ' [' + (item.procedure_code || '') + ']'));

            var meta = [];
            if (item.procedure_type) {
                meta.push('Type: ' + item.procedure_type);
            }
            if (Number(item.government_reserved || 0) === 1) {
                meta.push('Government Reserved');
            }
            if (item.pre_investigations) {
                meta.push('Pre: ' + item.pre_investigations);
            }
            if (item.post_investigations) {
                meta.push('Post: ' + item.post_investigations);
            }
            if (meta.length > 0) {
                procedure.append($('<div class="small text-muted"></div>').text(meta.join(' | ')));
            }

            var linkedText = item.linked_package_name ? item.linked_package_name : 'Not linked';
            var linkedCell = $('<td></td>');
            linkedCell.append($('<div></div>').text(linkedText));
            linkedCell.append($('<button type="button" class="btn btn-outline-primary btn-sm mt-1">Map</button>').attr('onclick', 'ipdAyushmanOpenMapping(' + Number(item.id || 0) + ')'));

            var row = $('<tr></tr>');
            row.append($('<td></td>').append(procedure));
            row.append($('<td></td>').text((item.speciality_name || '') + ' [' + (item.speciality_code || '') + ']'));
            row.append(linkedCell);
            row.append($('<td class="text-end"></td>').text(Number(item.package_amount || 0).toFixed(2)));
            row.append($('<td></td>').html(Number(item.preauth_required || 0) === 1 ? '<span class="badge bg-warning text-dark">Yes</span>' : '<span class="badge bg-secondary">No</span>'));
            row.append($('<td></td>').html('<button type="button" class="btn btn-success btn-sm">Add</button>').find('button').attr('onclick', 'ipdAyushmanAdd(' + Number(item.id || 0) + ')').end());
            tbody.append(row);
        });
    }

    function ipdAyushmanOpenMapping(itemId) {
        var item = ipdAyushmanResults[String(itemId)] || null;
        if (!item) {
            alert('Ayushman treatment not found in current result list.');
            return;
        }

        $('#ayushman_mapping_item_id').val(itemId);
        $('#ayushman_mapping_title').text((item.procedure_name || '') + ' [' + (item.procedure_code || '') + ']');
        var selectedPackageId = Number(item.linked_package_id || 0);
        var suggestedPackageId = 0;
        if (selectedPackageId <= 0) {
            suggestedPackageId = ipdAyushmanSuggestPackageId(item.procedure_name || '');
            if (suggestedPackageId > 0) {
                selectedPackageId = suggestedPackageId;
            }
        }

        $('#ayushman_mapping_package_id').val(String(selectedPackageId || 0));

        var metaText = (item.speciality_name || '') + ' | Current link: ' + (item.linked_package_name || 'Not linked');
        if (suggestedPackageId > 0) {
            var suggestedText = $('#ayushman_mapping_package_id option:selected').text() || '';
            metaText += ' | Suggested: ' + suggestedText;
        }
        $('#ayushman_mapping_meta').text(metaText);

        var modalEl = document.getElementById('ayushmanMappingModal');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function ipdAyushmanSaveMapping() {
        var itemId = parseInt($('#ayushman_mapping_item_id').val() || '0', 10);
        if (itemId <= 0) {
            alert('Select an Ayushman procedure first.');
            return;
        }

        var payload = {};
        payload[ipdPackageCsrfName] = ipdPackageCsrfHash;
        payload.linked_package_id = $('#ayushman_mapping_package_id').val() || 0;

        $.post(ipdAyushmanMapUrlBase + '/' + itemId, payload, function(resp) {
            if (!resp || !resp.ok) {
                alert((resp && resp.error) ? resp.error : 'Unable to save mapping');
                return;
            }

            var item = ipdAyushmanResults[String(itemId)] || null;
            if (item) {
                item.linked_package_id = Number(resp.linked_package_id || 0);
                item.linked_package_name = resp.linked_package_name || '';
            }

            var modalEl = document.getElementById('ayushmanMappingModal');
            var modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }

            ipdAyushmanSearch();
        }, 'json').fail(function(xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Unable to save mapping';
            alert(msg);
        });
    }

    function ipdAyushmanSaveChecklist() {
        var payload = {};
        payload[ipdPackageCsrfName] = ipdPackageCsrfHash;
        payload.preauth_send = $('#ayushman_preauth_send').is(':checked') ? 1 : 0;
        payload.doc_recd = $('#ayushman_doc_recd').is(':checked') ? 1 : 0;
        payload.final_bill_send = $('#ayushman_final_bill_send').is(':checked') ? 1 : 0;
        payload.org_approved_status = $('#ayushman_org_status').val() || 'Under Process';
        payload.remark = $('#ayushman_case_remark').val() || '';

        $.post(ipdAyushmanChecklistUrl, payload, function(resp) {
            if (!resp || !resp.ok) {
                alert((resp && resp.error) ? resp.error : 'Unable to save Ayushman checklist');
                return;
            }
            alert(resp.message || 'Ayushman checklist updated.');
            ipdPackageReload();
        }, 'json').fail(function(xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Unable to save Ayushman checklist';
            alert(msg);
        });
    }

    function ipdAyushmanClaimSheetPreview() {
        window.open(ipdAyushmanClaimSheetUrl, '_blank');
    }

    function ipdAyushmanClaimSheetExport() {
        window.open(ipdAyushmanClaimSheetUrl + '/1', '_blank');
    }

    function ipdAyushmanSearch() {
        var specialityCode = $('#ayushman_speciality_code').val() || '';
        var query = $('#ayushman_search_query').val() || '';
        $('#ayushman_search_summary').text('Searching...');

        $.getJSON(ipdAyushmanSearchUrl, {
            speciality_code: specialityCode,
            q: query
        }, function(resp) {
            if (!resp || !resp.ok) {
                $('#ayushman_search_summary').text('Unable to load Ayushman treatments.');
                return;
            }
            ipdAyushmanRenderResults(resp.items || []);
        }).fail(function() {
            $('#ayushman_search_summary').text('Unable to load Ayushman treatments.');
            $('#ayushman_search_results').html('<tr><td colspan="6" class="text-center text-danger">Search request failed.</td></tr>');
        });
    }

    function ipdAyushmanClear() {
        $('#ayushman_speciality_code').val('');
        $('#ayushman_search_query').val('');
        $('#ayushman_search_summary').text('Search imported Ayushman Bharat treatment list and add the selected treatment as an IPD package entry.');
        $('#ayushman_search_results').html('<tr><td colspan="6" class="text-center text-muted">Run a search to view Ayushman treatments.</td></tr>');
    }

    function ipdAyushmanAdd(itemId) {
        var item = ipdAyushmanResults[String(itemId)] || null;
        if (!item) {
            alert('Ayushman treatment not found in current result list.');
            return;
        }

        var payload = {};
        payload[ipdPackageCsrfName] = ipdPackageCsrfHash;
        payload.ayushman_package_id = itemId;
        payload.package_id = item.linked_package_id || 0;
        payload.package_name = item.procedure_name || '';
        payload.input_amount = item.package_amount || 0;
        payload.comment = '';

        $.post(ipdPackageBaseUrl + 'billing/ipd/package/add/' + ipdPackageIpdId, payload, function(resp) {
            if (resp && resp.ok) {
                ipdPackageReload();
                return;
            }
            alert((resp && resp.error) ? resp.error : 'Unable to add Ayushman package');
        }, 'json').fail(function(xhr) {
            var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Unable to add Ayushman package';
            alert(msg);
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

    $('#ayushman_search_query').on('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            ipdAyushmanSearch();
        }
    });

    <?php if ($isAyushmanCase) : ?>
    ipdAyushmanSearch();
    <?php endif; ?>
</script>

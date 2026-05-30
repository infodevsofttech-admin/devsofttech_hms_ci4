<?= form_open() ?>
<div class="card admin-card mb-3">
    <div class="card-header bg-white">
        <h3 class="mb-0">Report Edit</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <?php
                $repo_name       = '';
                $repo_id         = '0';
                $HTMLData        = '';
                $repo_loinc_code = $repo_loinc_code ?? '';
                if (count($labReport_master ?? []) > 0) {
                    $repo_name       = $labReport_master[0]->Title ?? '';
                    $repo_id         = $labReport_master[0]->mstRepoKey ?? '0';
                    $HTMLData        = $labReport_master[0]->HTMLData ?? '';
                    $repo_loinc_code = $labReport_master[0]->loinc_code ?? $repo_loinc_code;
                }
                ?>
                <input type="hidden" id="repo_id" value="<?= esc($repo_id) ?>">
            </div>

            <!-- Report Name with Bridge API Autocomplete -->
            <div class="col-md-8">
                <div class="form-group position-relative">
                    <label for="input_Reportname">
                        Report Name
                        <span id="loinc-fetch-spinner" class="spinner-border spinner-border-sm text-primary ms-2 d-none" role="status"></span>
                    </label>
                    <input class="form-control" id="input_Reportname" name="input_Reportname"
                           placeholder="Type to search from Bridge API…"
                           type="text" value="<?= esc($repo_name) ?>"
                           autocomplete="off" />
                    <!-- Autocomplete dropdown -->
                    <div id="report-name-suggestions"
                         class="list-group shadow"
                         style="position:absolute;z-index:1050;width:100%;display:none;max-height:260px;overflow-y:auto;"></div>
                </div>
            </div>

            <!-- LOINC Code (auto-filled on selection, editable) -->
            <div class="col-md-4">
                <div class="form-group">
                    <label for="input_loinc_code">
                        LOINC Code
                        <small class="text-muted">(auto-filled from Bridge API)</small>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-flask"></i></span>
                        <input class="form-control" id="input_loinc_code" name="loinc_code"
                               placeholder="e.g. 58410-2"
                               type="text" value="<?= esc($repo_loinc_code) ?>" />
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btn-load-master-template">Load Master Template</button>
                        <button type="button" class="btn btn-outline-success btn-sm" id="btn-apply-master-panel">Apply Panel Mapping</button>
                        <span id="master-template-spinner" class="spinner-border spinner-border-sm text-primary d-none" role="status"></span>
                    </div>
                    <small id="selected-master-meta" class="text-muted d-block mt-2">Select a Bridge API master row, then click "Load Master Template".</small>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Attach Charge Name</label>
                    <select class="form-select" id="charge_id" name="charge_id">
                        <?php
                        $sel_value = 0;
                        if (count($labReport_master ?? []) > 0) {
                            $sel_value = (int) ($labReport_master[0]->charge_id ?? 0);
                        }
                        echo '<option value="0" ' . combo_checked('0', $sel_value) . '>No Attach</option>';
                        foreach (($hc_items ?? []) as $row) {
                            echo '<option value="' . esc($row->id ?? 0) . '" ' . combo_checked($row->id ?? 0, $sel_value) . '>' . esc($row->idesc ?? '') . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Group</label>
                    <select class="form-select" id="group_id" name="group_id">
                        <?php
                        $sel_value = 0;
                        if (count($labReport_master ?? []) > 0) {
                            $sel_value = (int) ($labReport_master[0]->GrpKey ?? 0);
                        }
                        foreach (($lab_rgroups ?? []) as $row) {
                            echo '<option value="' . esc($row->mstRGrpKey ?? 0) . '" ' . combo_checked($row->mstRGrpKey ?? 0, $sel_value) . '>' . esc($row->RepoGrp ?? '') . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <textarea id="HTMLData" name="HTMLData" placeholder="Place some text here"><?= esc($HTMLData) ?></textarea>
                <script>
                    if (typeof CKEDITOR !== 'undefined') {
                        CKEDITOR.replace('HTMLData');
                    }
                </script>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <button id="updatereport" type="button" class="btn btn-primary">Update</button>
            </div>
        </div>
    </div>
</div>

<div class="card admin-card">
    <div class="card-header bg-white">
        <h3 class="mb-0">Report Parameter</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <?php for ($i = 0; $i < count($lab_Rep_Item_List ?? []); ++$i) {
                    $color = $color_name[($lab_Rep_Item_List[$i]->id ?? 0) % max(1, count($color_name ?? []))]->code_code_2 ?? '#0f172a';
                    echo '<div style="margin:2px;display:inline-block;color:' . esc($color) . ';"><i>' . esc($lab_Rep_Item_List[$i]->Test ?? '') . '</i>[' . esc($lab_Rep_Item_List[$i]->TestID ?? '') . ']</div>';
                } ?>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <button onclick="load_form_div('<?= base_url('Lab_Admin/report_test_list') ?>/<?= esc($repo_id ?? 0) ?>','test_div');" type="button" class="btn btn-outline-primary">Test List</button>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>
<script>
(function () {
    'use strict';

    const SEARCH_URL = '<?= base_url('Lab_Admin/pathology_masters_search') ?>';
    const TEMPLATE_URL = '<?= base_url('Lab_Admin/pathology_master_template') ?>';
    const APPLY_PANEL_URL = '<?= base_url('Lab_Admin/pathology_master_apply_panel') ?>';
    const nameInput  = document.getElementById('input_Reportname');
    const loincInput = document.getElementById('input_loinc_code');
    const groupInput = document.getElementById('group_id');
    const dropdown   = document.getElementById('report-name-suggestions');
    const spinner    = document.getElementById('loinc-fetch-spinner');
    const templateBtn = document.getElementById('btn-load-master-template');
    const applyPanelBtn = document.getElementById('btn-apply-master-panel');
    const templateSpinner = document.getElementById('master-template-spinner');
    const masterMeta = document.getElementById('selected-master-meta');

    let debounceTimer = null;
    let selectedMaster = null;

    // ── Autocomplete on keyup ────────────────────────────────────────────────
    nameInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const q = this.value.trim();

        if (q.length < 2) {
            hideDropdown();
            return;
        }

        spinner.classList.remove('d-none');
        debounceTimer = setTimeout(function () {
            fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(items => {
                spinner.classList.add('d-none');
                renderDropdown(items);
            })
            .catch(() => {
                spinner.classList.add('d-none');
                hideDropdown();
            });
        }, 320);
    });

    function renderDropdown(items) {
        dropdown.innerHTML = '';
        if (! items || items.length === 0) {
            hideDropdown();
            return;
        }

        items.forEach(function (item) {
            const btn = document.createElement('button');
            btn.type      = 'button';
            btn.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';

            const sourceBadge = item.source === 'bridge'
                ? '<span class="badge bg-primary ms-1" title="From Bridge API">API</span>'
                : '<span class="badge bg-secondary ms-1" title="From local database">Local</span>';

            const rateBadge = item.standard_rate
                ? ' <span class="badge bg-warning text-dark ms-1" title="Standard Rate">Rs ' + escHtml(item.standard_rate) + '</span>'
                : '';

            btn.innerHTML =
                '<span>' + escHtml(item.name) + ' ' + sourceBadge +
                (item.sub_category ? ' <span class="badge bg-info text-dark ms-1" title="Sub Category">' + escHtml(item.sub_category) + '</span>' : '') +
                rateBadge +
                '</span>' +
                (item.loinc_code
                    ? '<span class="badge bg-success ms-2" title="LOINC">' + escHtml(item.loinc_code) + '</span>'
                    : '<span class="badge bg-light text-muted border ms-2">No LOINC</span>');

            btn.addEventListener('click', function () {
                nameInput.value  = item.name;
                loincInput.value = item.loinc_code || '';
                applyGroupFromSubCategory(item.sub_category || '');
                selectedMaster = item;
                updateMasterMeta(item);
                hideDropdown();
                nameInput.focus();
            });

            dropdown.appendChild(btn);
        });

        dropdown.style.display = 'block';
    }

    function hideDropdown() {
        dropdown.style.display = 'none';
        dropdown.innerHTML     = '';
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        if (! nameInput.contains(e.target) && ! dropdown.contains(e.target)) {
            hideDropdown();
        }
    });

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function normalizeLabel(str) {
        return String(str || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '')
            .trim();
    }

    function canonicalGroupKey(str) {
        const key = normalizeLabel(str);
        if (!key) return '';

        const aliasMap = {
            hematology: 'haematology',
            haematology: 'haematology',
            biochemistry: 'biochemistry',
            biochem: 'biochemistry',
            endocrinology: 'endocrinology',
            vitaminsminerals: 'vitaminsminerals',
            vitaminsandminerals: 'vitaminsminerals',
            infectiousdisease: 'infectiousdisease',
            microbiology: 'infectiousdisease',
            inflammatorymarkers: 'inflammatorymarkers',
            tumormarkers: 'tumormarkers',
            clinicalpathology: 'clinicalpathology'
        };

        return aliasMap[key] || key;
    }

    function updateMasterMeta(item) {
        if (!masterMeta) return;

        if (!item) {
            masterMeta.textContent = 'Select a Bridge API master row, then click "Load Master Template".';
            return;
        }

        const source = item.source === 'bridge' ? 'Bridge API' : 'Local';
        const name = item.display_name || item.test_name || item.name || '';
        const loinc = item.loinc_code || 'NA';
        const sub = item.sub_category || 'NA';
        const rate = item.standard_rate ? ('Rs ' + item.standard_rate) : 'NA';

        masterMeta.textContent = 'Selected: ' + name + ' | LOINC: ' + loinc + ' | Sub Category: ' + sub + ' | Rate: ' + rate + ' | Source: ' + source;
    }

    function applyGroupFromSubCategory(subCategory) {
        if (!groupInput) return;

        const wanted = canonicalGroupKey(subCategory);
        const options = Array.from(groupInput.options || []);
        let match = null;

        if (wanted) {
            match = options.find(function (opt) {
                return canonicalGroupKey(opt.textContent) === wanted;
            }) || null;

            if (!match) {
                match = options.find(function (opt) {
                    const txt = canonicalGroupKey(opt.textContent);
                    return txt.includes(wanted) || wanted.includes(txt);
                }) || null;
            }
        }

        if (!match) {
            match = options.find(function (opt) {
                return normalizeLabel(opt.textContent).includes('pathology');
            }) || null;
        }

        if (match && groupInput.value !== match.value) {
            groupInput.value = match.value;
            groupInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    templateBtn.addEventListener('click', function () {
        const panelName = (selectedMaster && (selectedMaster.test_name || selectedMaster.name))
            ? String(selectedMaster.test_name || selectedMaster.name).trim()
            : nameInput.value.trim();

        if (!panelName) {
            if (typeof notify === 'function') {
                notify('warning', 'Missing report name', 'Select a pathology master or enter report name first.');
            }
            nameInput.focus();
            return;
        }

        templateBtn.disabled = true;
        templateSpinner.classList.remove('d-none');

        fetch(TEMPLATE_URL + '?parent_test=' + encodeURIComponent(panelName), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(resp => {
            templateBtn.disabled = false;
            templateSpinner.classList.add('d-none');

            if (!resp || Number(resp.ok) !== 1) {
                if (typeof notify === 'function') {
                    notify('warning', 'Master template fetch failed', (resp && resp.error) ? resp.error : 'Unable to fetch template.');
                }
                return;
            }

            const existingHtml = (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.HTMLData)
                ? CKEDITOR.instances.HTMLData.getData().trim()
                : String(document.getElementById('HTMLData').value || '').trim();

            if (existingHtml !== '' && !confirm('Replace existing template content with gateway master template?')) {
                return;
            }

            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.HTMLData) {
                CKEDITOR.instances.HTMLData.setData(resp.template_html || '');
            } else {
                document.getElementById('HTMLData').value = resp.template_html || '';
            }

            if (resp.loinc_code) {
                loincInput.value = resp.loinc_code;
            }
            if (resp.sub_category) {
                applyGroupFromSubCategory(resp.sub_category);
            }

            selectedMaster = Object.assign({}, selectedMaster || {}, {
                name: resp.panel_name || panelName,
                test_name: resp.panel_name || panelName,
                loinc_code: resp.loinc_code || loincInput.value,
                sub_category: resp.sub_category || '',
                standard_rate: resp.standard_rate || '',
                source: 'bridge',
            });
            updateMasterMeta(selectedMaster);

            if (typeof notify === 'function') {
                notify('success', 'Template loaded', 'Loaded ' + String(resp.components_count || 0) + ' component rows from gateway master.');
            }
        })
        .catch(() => {
            templateBtn.disabled = false;
            templateSpinner.classList.add('d-none');
            if (typeof notify === 'function') {
                notify('warning', 'Master template fetch failed', 'Network or server error while loading master template.');
            }
        });
    });

    applyPanelBtn.addEventListener('click', function () {
        const repoId = String(document.getElementById('repo_id').value || '').trim();
        const panelName = (selectedMaster && (selectedMaster.test_name || selectedMaster.name))
            ? String(selectedMaster.test_name || selectedMaster.name).trim()
            : nameInput.value.trim();

        if (!repoId || Number(repoId) <= 0) {
            if (typeof notify === 'function') {
                notify('warning', 'Save report first', 'Create/save report once, then apply full panel mapping.');
            }
            return;
        }

        if (!panelName) {
            if (typeof notify === 'function') {
                notify('warning', 'Missing report name', 'Select a pathology master or enter panel name first.');
            }
            nameInput.focus();
            return;
        }

        if (!confirm('This will replace current panel test mapping and print template with gateway panel mapping. Continue?')) {
            return;
        }

        applyPanelBtn.disabled = true;
        templateSpinner.classList.remove('d-none');

        const csrfTokenName = '<?= csrf_token() ?>';
        const csrfTokenValue = document.querySelector('input[name="<?= csrf_token() ?>"]')?.value || '<?= csrf_hash() ?>';

        const formData = new FormData();
        formData.append('repo_id', repoId);
        formData.append('panel_name', panelName);
        formData.append('replace_existing', '1');
        formData.append(csrfTokenName, csrfTokenValue);

        fetch(APPLY_PANEL_URL, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
        })
        .then(r => r.json())
        .then(resp => {
            applyPanelBtn.disabled = false;
            templateSpinner.classList.add('d-none');

            if (!resp || Number(resp.ok) !== 1) {
                if (typeof notify === 'function') {
                    notify('warning', 'Panel apply failed', (resp && resp.error) ? resp.error : 'Unable to apply panel mapping.');
                }
                return;
            }

            if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.HTMLData) {
                CKEDITOR.instances.HTMLData.setData(resp.template_html || '');
            } else {
                document.getElementById('HTMLData').value = resp.template_html || '';
            }

            if (resp.panel_loinc_code) {
                loincInput.value = resp.panel_loinc_code;
            }

            if (typeof notify === 'function') {
                notify(
                    'success',
                    'Panel mapping applied',
                    'Imported ' + String(resp.components_count || 0) + ' tests (created: ' + String(resp.created_tests || 0) + ', updated: ' + String(resp.updated_tests || 0) + ').'
                );
            }

            // Reload same edit screen so report parameter list reflects imported panel components.
            if (typeof load_form_div === 'function') {
                load_form_div('<?= base_url('Lab_Admin/reportedit_load') ?>/' + encodeURIComponent(repoId), 'test_div');
            }
        })
        .catch(() => {
            applyPanelBtn.disabled = false;
            templateSpinner.classList.add('d-none');
            if (typeof notify === 'function') {
                notify('warning', 'Panel apply failed', 'Network or server error while applying panel mapping.');
            }
        });
    });

    nameInput.addEventListener('change', function () {
        if (selectedMaster && String(selectedMaster.name || '').trim() !== this.value.trim()) {
            selectedMaster = null;
            updateMasterMeta(null);
        }
    });

    // ── Save / Insert ────────────────────────────────────────────────────────
    document.getElementById('updatereport').addEventListener('click', function () {
        const repo_id    = document.getElementById('repo_id').value;
        const name       = nameInput.value.trim();
        const charge_id  = document.getElementById('charge_id').value;
        const group_id   = document.getElementById('group_id').value;
        const loinc_code = loincInput.value.trim();
        const HTMLData   = (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.HTMLData)
                            ? CKEDITOR.instances.HTMLData.getData()
                            : document.getElementById('HTMLData').value;
        const csrf_token = '<?= csrf_token() ?>';
        const csrf_value = document.querySelector('input[name="<?= csrf_token() ?>"]')?.value || '<?= csrf_hash() ?>';

        const payload = {
            repo_id,
            input_Reportname: name,
            charge_id,
            group_id,
            HTMLData,
            loinc_code,
            [csrf_token]: csrf_value
        };

        const url = repo_id > 0
            ? '<?= base_url('Lab_Admin/report_update') ?>'
            : '<?= base_url('Lab_Admin/report_insert') ?>';

        $.post(url, payload, function (data) {
            if (repo_id > 0) {
                if (data.showcontent && typeof notify === 'function') {
                    notify('success', 'Saved', data.showcontent);
                }
            } else {
                if (data.insertid > 0) {
                    load_form('<?= base_url('Lab_Admin/report_list') ?>');
                }
            }
        }, 'json');
    });
}());
</script>

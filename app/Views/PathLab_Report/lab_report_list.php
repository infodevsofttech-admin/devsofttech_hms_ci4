<section class="content">
    <style>
        .panel-map-wrap {
            background: #ffffff;
            border: 1px solid #dbe4ef;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 39, 91, 0.05);
        }

        .panel-type-badge {
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 4px;
            letter-spacing: 0.4px;
            font-weight: 700;
            background: #2f75b5;
            color: #fff;
        }

        .items-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 999px;
            background: #21b29c;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
        }

        .status-pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            color: #fff;
            background: #4caf50;
        }

        .status-pill.inactive {
            background: #9ea7b3;
        }

        .action-inline .btn {
            margin-right: 6px;
            margin-bottom: 4px;
        }

        .action-inline-group {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .action-inline-group .btn {
            margin: 0;
            border-radius: 6px;
            font-weight: 600;
        }

        .panel-map-title {
            font-weight: 600;
            color: #2d3f56;
        }

        @media (max-width: 991.98px) {
            .action-inline .btn {
                width: 100%;
            }
        }

        #addPanelModal .form-label {
            font-weight: 600;
            color: #2d3f56;
            font-size: 12px;
            letter-spacing: 0.2px;
            text-transform: uppercase;
        }

        .add-panel-name-wrap {
            position: relative;
        }

        #add-panel-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1065;
            max-height: 240px;
            overflow-y: auto;
            display: none;
            border: 1px solid #dbe4ef;
            border-radius: 6px;
            box-shadow: 0 8px 20px rgba(0, 39, 91, 0.1);
            background: #fff;
        }

        #add-panel-master-meta {
            min-height: 18px;
        }

        #panelActionModal .modal-dialog {
            max-width: min(1400px, 96vw);
        }

        #panelActionModal .modal-content {
            height: 92vh;
            border: 0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 16px 45px rgba(0, 29, 74, 0.28);
        }

        #panelActionModal .modal-header,
        #panelActionModal .modal-footer {
            position: sticky;
            background: #fff;
            z-index: 2;
        }

        #panelActionModal .modal-header {
            top: 0;
            border-bottom: 1px solid #e7edf5;
        }

        #panelActionModal .modal-footer {
            bottom: 0;
            border-top: 1px solid #e7edf5;
        }

        #panelActionModalBody {
            overflow: auto;
            background: #f8fafc;
            padding: 12px;
        }

        #panelActionOpenPage {
            text-decoration: none;
        }

        .print-format-helper {
            margin-bottom: 12px;
            padding: 12px 14px;
            border-radius: 4px;
            background: #4a9bd5;
            color: #fff;
            font-size: 13px;
            line-height: 1.5;
        }

        .print-format-reference-box {
            padding: 12px;
            border: 1px solid #d8e4ef;
            border-radius: 4px;
            background: #fff;
        }

        .print-format-reference-item {
            display: inline-block;
            margin: 0 10px 6px 0;
            color: #476a94;
            font-size: 13px;
            line-height: 1.5;
        }

        .print-format-reference-item .token {
            font-weight: 700;
        }

        .print-format-actions {
            margin-top: 12px;
            margin-bottom: 12px;
        }

        @media (max-width: 991.98px) {
            #panelActionModal .modal-content {
                height: 96vh;
            }

            #panelActionModalBody {
                padding: 8px;
            }
        }
    </style>
    <div class="card admin-card panel-map-wrap">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <h3 class="mb-0">Pathology Panel Mappings</h3>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPanelModal" data-toggle="modal" data-target="#addPanelModal">+ Add Panel</button>
        </div>
        <div class="card-body p-2 p-md-3">
            <div class="table-responsive">
                <table id="report_list" class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Panel Name</th>
                        <th>Type</th>
                        <th>Test Type</th>
                        <th>Description</th>
                        <th>Items</th>
                        <th>Active</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (($labReport_master ?? []) as $row) { ?>
                        <?php
                        $isActive = (int) ($row->is_active ?? 1) === 1;
                        $updatedRaw = trim((string) ($row->updated_on ?? ''));
                        $updatedStr = '-';
                        if ($updatedRaw !== '') {
                            $ts = strtotime($updatedRaw);
                            $updatedStr = $ts ? date('Y-m-d H:i:s', $ts) : $updatedRaw;
                        }
                        ?>
                        <tr>
                            <td><?= (int) ($row->panel_id ?? 0) ?></td>
                            <td>
                                <span class="panel-map-title"><?= esc($row->panel_name ?? '') ?></span>
                            </td>
                            <td><span class="panel-type-badge">PANEL</span></td>
                            <td><?= esc($row->test_type ?? '') ?></td>
                            <td><?= esc($row->description ?? '') ?></td>
                            <td><span class="items-pill"><?= (int) ($row->items_count ?? 0) ?></span></td>
                            <td>
                                <span class="status-pill <?= $isActive ? '' : 'inactive' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span>
                            </td>
                            <td><?= esc($updatedStr) ?></td>
                            <td class="action-inline">
                                <div class="action-inline-group">
                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm js-panel-action"
                                        data-title="Pathology Template - Edit"
                                        data-mode="edit-meta"
                                        data-url="<?= base_url('Lab_Admin/reportedit_load') ?>/<?= (int) ($row->panel_id ?? 0) ?>"
                                    >Edit</button>
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary btn-sm js-panel-action"
                                        data-title="Pathology Template - Print Format"
                                        data-mode="print-format"
                                        data-url="<?= base_url('Lab_Admin/reportedit_load') ?>/<?= (int) ($row->panel_id ?? 0) ?>"
                                    >Print Format</button>
                                    <button
                                        type="button"
                                        class="btn btn-success btn-sm js-panel-action"
                                        data-title="Pathology Template - Components"
                                        data-mode="components"
                                        data-url="<?= base_url('Lab_Admin/report_test_list') ?>/<?= (int) ($row->panel_id ?? 0) ?>"
                                    >+ Add Component</button>
                                    <button
                                        type="button"
                                        class="btn btn-outline-danger btn-sm js-panel-delete"
                                        data-panel-id="<?= (int) ($row->panel_id ?? 0) ?>"
                                        data-panel-name="<?= esc($row->panel_name ?? '') ?>"
                                    >Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addPanelModal" tabindex="-1" aria-labelledby="addPanelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPanelModalLabel">Add Pathology Panel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addPanelForm" autocomplete="off">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_panel_name" class="form-label">Panel Name</label>
                            <div class="add-panel-name-wrap">
                                <input type="text" class="form-control" id="add_panel_name" name="input_Reportname" maxlength="255" autocomplete="off" required>
                                <div id="add-panel-suggestions" class="list-group"></div>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <span id="add-panel-search-spinner" class="spinner-border spinner-border-sm text-primary d-none" role="status"></span>
                                <small id="add-panel-master-meta" class="text-muted">Type at least 2 letters to live-search panel templates from ABDM Gateway API.</small>
                            </div>
                        </div>
                        <input type="hidden" id="add_panel_group" name="group_id" value="0">
                        <div class="mb-3">
                            <label class="form-label">Test Type</label>
                            <div class="form-control bg-light" style="height:auto;">
                                Auto (not required for panel mapping)
                            </div>
                            <small class="text-muted">Panels can include mixed test types, so manual Test Type selection is intentionally removed here.</small>
                        </div>
                        <div class="mb-3">
                            <label for="add_panel_charge" class="form-label">Attach Charge Name</label>
                            <select class="form-select" id="add_panel_charge" name="charge_id">
                                <option value="0">Not Attached</option>
                                <?php foreach (($charge_items ?? []) as $chargeRow) { ?>
                                    <option value="<?= (int) ($chargeRow->id ?? 0) ?>"><?= esc($chargeRow->idesc ?? '') ?><?= isset($chargeRow->amount) ? ' : [' . esc((string) $chargeRow->amount) . ']' : '' ?></option>
                                <?php } ?>
                            </select>
                            <small class="text-muted">Optional billing linkage for this panel.</small>
                        </div>
                        <div class="mb-0">
                            <label for="add_panel_description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="add_panel_description" placeholder="Optional; defaults to panel name">
                            <small class="text-muted">In HMS, description is mirrored from panel name for list display.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="btnSavePanel" class="btn btn-primary">Save Panel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="panelActionModal" tabindex="-1" aria-labelledby="panelActionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="panelActionModalLabel">Pathology Panel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="panelActionModalBody">
                    <div class="text-muted">Loading...</div>
                </div>
                <div class="modal-footer py-2">
                    <a href="#" id="panelActionOpenPage" class="btn btn-outline-primary btn-sm" target="_self">Open Full Page</a>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</section>
<script>
    (function () {
        const PANEL_INSERT_URL = '<?= base_url('Lab_Admin/report_insert') ?>';
        const PANEL_DELETE_BASE_URL = '<?= base_url('Lab_Admin/report_delete') ?>';
        const PANEL_MASTER_SEARCH_URL = '<?= base_url('Lab_Admin/pathology_masters_search') ?>';
        const PANEL_MASTER_TEMPLATE_URL = '<?= base_url('Lab_Admin/pathology_master_template') ?>';
        const PANEL_APPLY_URL = '<?= base_url('Lab_Admin/pathology_master_apply_panel') ?>';

        if (window.jQuery && $.fn && $.fn.DataTable) {
            if ($.fn.DataTable.isDataTable('#report_list')) {
                $('#report_list').DataTable().destroy();
            }

            $('#report_list').DataTable({
                order: [[0, 'asc']],
                pageLength: 25,
                lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']]
            });
        }

        const addPanelForm = document.getElementById('addPanelForm');
        const saveBtn = document.getElementById('btnSavePanel');
        const panelNameInput = document.getElementById('add_panel_name');
        const panelDescInput = document.getElementById('add_panel_description');
        const panelGroupSelect = document.getElementById('add_panel_group');
        const panelSuggestionBox = document.getElementById('add-panel-suggestions');
        const panelSearchSpinner = document.getElementById('add-panel-search-spinner');
        const panelMasterMeta = document.getElementById('add-panel-master-meta');
        const panelActionModalEl = document.getElementById('panelActionModal');
        const panelActionModalTitleEl = document.getElementById('panelActionModalLabel');
        const panelActionModalBodyEl = document.getElementById('panelActionModalBody');
        const panelActionOpenPageEl = document.getElementById('panelActionOpenPage');

        let panelSearchDebounce = null;
        let selectedGatewayMaster = null;
        let selectedGatewayTemplateHtml = '';
        let selectedGatewayComponentsCount = 0;

        if (!addPanelForm || !saveBtn || !panelNameInput) {
            return;
        }

        const globalScope = window;
        if (!globalScope.__pathologyPanelDocHandlers) {
            globalScope.__pathologyPanelDocHandlers = {};
        }

        function bindDocumentHandler(key, eventName, handler) {
            const bucket = globalScope.__pathologyPanelDocHandlers;
            if (bucket[key]) {
                document.removeEventListener(eventName, bucket[key]);
            }
            bucket[key] = handler;
            document.addEventListener(eventName, handler);
        }

        function executeInlineScripts(containerEl) {
            if (!containerEl) {
                return;
            }

            const scripts = containerEl.querySelectorAll('script');
            scripts.forEach(function (oldScript) {
                const newScript = document.createElement('script');

                Array.from(oldScript.attributes || []).forEach(function (attr) {
                    newScript.setAttribute(attr.name, attr.value);
                });

                if (oldScript.src) {
                    const exists = document.querySelector('script[src="' + oldScript.src + '"]');
                    if (exists) {
                        return;
                    }
                    document.head.appendChild(newScript);
                } else {
                    newScript.text = oldScript.textContent || '';
                    document.head.appendChild(newScript);
                    document.head.removeChild(newScript);
                }
            });
        }

        function applyPanelActionMode(mode) {
            if (!panelActionModalBodyEl) {
                return;
            }

            const m = String(mode || '').trim().toLowerCase();
            if (m !== 'edit-meta' && m !== 'print-format') {
                return;
            }

            const nameInput = panelActionModalBodyEl.querySelector('#input_Reportname');
            const loincInput = panelActionModalBodyEl.querySelector('#input_loinc_code');
            const chargeSelect = panelActionModalBodyEl.querySelector('#charge_id');
            const groupSelect = panelActionModalBodyEl.querySelector('#group_id');
            const htmlArea = panelActionModalBodyEl.querySelector('#HTMLData');
            const updateBtn = panelActionModalBodyEl.querySelector('#updatereport');
            const cards = panelActionModalBodyEl.querySelectorAll('.card.admin-card');
            const firstCardHeader = cards.length > 0 ? cards[0].querySelector('.card-header h3') : null;
            const firstCardBody = cards.length > 0 ? cards[0].querySelector('.card-body') : null;
            const secondCard = cards.length > 1 ? cards[1] : null;

            const nameLabel = panelActionModalBodyEl.querySelector('label[for="input_Reportname"]');
            if (nameLabel && m === 'edit-meta') {
                nameLabel.childNodes[0].nodeValue = 'Description';
            }

            if (groupSelect && m === 'edit-meta') {
                const groupWrap = groupSelect.closest('.form-group');
                const groupLabel = groupWrap ? groupWrap.querySelector('label') : null;
                if (groupLabel) {
                    groupLabel.textContent = 'Test Type';
                }
            }

            const hideBlock = function (el) {
                if (el) {
                    el.style.display = 'none';
                }
            };

            if (m === 'edit-meta') {
                hideBlock(loincInput ? loincInput.closest('.col-md-4') : null);
                hideBlock(htmlArea ? htmlArea.closest('.row') : null);
                if (secondCard) {
                    hideBlock(secondCard);
                }
            }

            if (m === 'print-format') {
                hideBlock(nameInput ? nameInput.closest('.col-md-8') : null);
                hideBlock(loincInput ? loincInput.closest('.col-md-4') : null);
                hideBlock(chargeSelect ? chargeSelect.closest('.col-md-6') : null);
                hideBlock(groupSelect ? groupSelect.closest('.col-md-6') : null);

                if (firstCardHeader) {
                    firstCardHeader.textContent = 'Panel Print Format';
                }

                if (updateBtn) {
                    updateBtn.textContent = 'Save Print Format';
                    updateBtn.classList.remove('btn-primary');
                    updateBtn.classList.add('btn-success');
                }

                if (firstCardBody && !firstCardBody.querySelector('.print-format-helper')) {
                    const helper = document.createElement('div');
                    helper.className = 'print-format-helper';
                    const panelName = nameInput ? String(nameInput.value || '').trim() : '';
                    helper.textContent = (panelName || 'This panel') + ' format for human-readable report print. Use placeholders like {HB}, {TLC}, {MCV} in Result column.';
                    firstCardBody.insertBefore(helper, firstCardBody.firstChild);
                }

                if (secondCard) {
                    secondCard.style.display = '';

                    const secondHeader = secondCard.querySelector('.card-header h3');
                    const secondBody = secondCard.querySelector('.card-body');
                    if (secondHeader) {
                        secondHeader.textContent = 'Component References';
                    }

                    if (secondBody) {
                        hideBlock(secondBody.querySelector('.row.mt-3'));

                        const rawListHost = secondBody.querySelector('.row .col-md-12');
                        if (rawListHost && !secondBody.querySelector('.print-format-reference-box')) {
                            const refs = Array.from(rawListHost.querySelectorAll('div'));
                            const refsData = [];
                            const box = document.createElement('div');
                            box.className = 'print-format-reference-box';

                            if (refs.length === 0) {
                                box.innerHTML = '<span class="text-muted">No component references available.</span>';
                            } else {
                                refs.forEach(function (node) {
                                    const text = String(node.textContent || '').trim();
                                    if (text === '') {
                                        return;
                                    }

                                    const tokenStart = text.lastIndexOf('[');
                                    const tokenEnd = text.lastIndexOf(']');
                                    const label = tokenStart > 0 ? text.substring(0, tokenStart).trim() : text;
                                    const token = tokenStart > -1 && tokenEnd > tokenStart
                                        ? text.substring(tokenStart + 1, tokenEnd).trim()
                                        : '';

                                    refsData.push({
                                        label: label,
                                        token: token,
                                    });

                                    const item = document.createElement('span');
                                    item.className = 'print-format-reference-item';
                                    item.style.color = node.style.color || '';
                                    item.innerHTML = token
                                        ? '<em>' + label + '</em> <span class="token">{' + token + '}</span>'
                                        : '<em>' + label + '</em>';
                                    box.appendChild(item);
                                });
                            }

                            rawListHost.innerHTML = '';
                            rawListHost.appendChild(box);

                            if (!firstCardBody.querySelector('.print-format-actions')) {
                                const actions = document.createElement('div');
                                actions.className = 'print-format-actions';

                                const loadBtn = document.createElement('button');
                                loadBtn.type = 'button';
                                loadBtn.className = 'btn btn-outline-secondary btn-sm';
                                loadBtn.textContent = 'Load Default Table';
                                loadBtn.addEventListener('click', function () {
                                    const panelName = nameInput ? String(nameInput.value || '').trim() : 'Pathology Panel';
                                    let rows = '';

                                    refsData.forEach(function (ref) {
                                        const safeLabel = String(ref.label || '')
                                            .replace(/&/g, '&amp;')
                                            .replace(/</g, '&lt;')
                                            .replace(/>/g, '&gt;')
                                            .replace(/"/g, '&quot;');
                                        const safeToken = String(ref.token || '')
                                            .replace(/&/g, '&amp;')
                                            .replace(/</g, '&lt;')
                                            .replace(/>/g, '&gt;')
                                            .replace(/"/g, '&quot;');

                                        rows += '<tr>'
                                            + '<td>' + (safeLabel || '&nbsp;') + '</td>'
                                            + '<td>' + (safeToken ? '{' + safeToken + '}' : '&nbsp;') + '</td>'
                                            + '<td>&nbsp;</td>'
                                            + '<td>&nbsp;</td>'
                                            + '</tr>';
                                    });

                                    if (rows === '') {
                                        rows = '<tr><td colspan="4"><em>No panel components available.</em></td></tr>';
                                    }

                                    const safePanelName = String(panelName || 'Pathology Panel')
                                        .replace(/&/g, '&amp;')
                                        .replace(/</g, '&lt;')
                                        .replace(/>/g, '&gt;')
                                        .replace(/"/g, '&quot;');

                                    const html = '<p><strong>' + safePanelName + '</strong></p>'
                                        + '<table style="width:100%;border-collapse:collapse;font-size:12px;" border="1" cellpadding="6" cellspacing="0">'
                                        + '<thead>'
                                        + '<tr style="background:#f4f6f8;">'
                                        + '<th style="text-align:left;">Test Name</th>'
                                        + '<th style="text-align:left;">Result</th>'
                                        + '<th style="text-align:left;">Unit</th>'
                                        + '<th style="text-align:left;">Bio. Ref. Interval</th>'
                                        + '</tr>'
                                        + '</thead>'
                                        + '<tbody>' + rows + '</tbody>'
                                        + '</table>';

                                    if (typeof window.CKEDITOR !== 'undefined' && window.CKEDITOR.instances.HTMLData) {
                                        window.CKEDITOR.instances.HTMLData.setData(html);
                                    } else if (htmlArea) {
                                        htmlArea.value = html;
                                    }
                                });

                                actions.appendChild(loadBtn);

                                const updateRow = updateBtn ? updateBtn.closest('.row') : null;
                                if (updateRow && updateRow.parentNode === firstCardBody) {
                                    firstCardBody.insertBefore(actions, updateRow);
                                } else {
                                    firstCardBody.appendChild(actions);
                                }
                            }
                        }
                    }
                }
            }
        }

        function openPanelActionModal(url, title, mode) {
            if (!panelActionModalEl || !panelActionModalTitleEl || !panelActionModalBodyEl) {
                if (typeof load_form === 'function') {
                    load_form(url, title || 'Pathology Template');
                }
                return;
            }

            panelActionModalTitleEl.textContent = title || 'Pathology Panel';
            panelActionModalBodyEl.innerHTML = '<div class="d-flex align-items-center gap-2 text-muted"><span class="spinner-border spinner-border-sm" role="status"></span><span>Loading...</span></div>';

            if (panelActionOpenPageEl) {
                panelActionOpenPageEl.setAttribute('href', url);
                panelActionOpenPageEl.style.display = String(mode || '').trim().toLowerCase() === 'components' ? 'none' : '';
            }

            const showModal = function () {
                if (window.bootstrap && window.bootstrap.Modal) {
                    const modal = window.bootstrap.Modal.getInstance(panelActionModalEl) || new window.bootstrap.Modal(panelActionModalEl);
                    modal.show();
                    return;
                }

                if (window.jQuery && $('#panelActionModal').modal) {
                    $('#panelActionModal').modal('show');
                }
            };

            const handleHtml = function (html) {
                panelActionModalBodyEl.innerHTML = html;
                executeInlineScripts(panelActionModalBodyEl);
                applyPanelActionMode(mode);
                showModal();
            };

            const handleFail = function () {
                panelActionModalBodyEl.innerHTML = '<div class="alert alert-warning mb-0">Unable to load content. Please try again.</div>';
                showModal();
            };

            if (typeof window.fetch === 'function') {
                fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(function (r) { return r.text(); })
                    .then(handleHtml)
                    .catch(handleFail);
                return;
            }

            if (window.jQuery) {
                $.ajax({
                    url: url,
                    method: 'GET',
                    dataType: 'html',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                }).done(handleHtml).fail(handleFail);
                return;
            }

            handleFail();
        }

        bindDocumentHandler('panelActionClick', 'click', function (event) {
            const actionBtn = event.target.closest('.js-panel-action');
            if (!actionBtn) {
                return;
            }

            event.preventDefault();
            const url = String(actionBtn.getAttribute('data-url') || '').trim();
            const title = String(actionBtn.getAttribute('data-title') || 'Pathology Panel').trim();
            const mode = String(actionBtn.getAttribute('data-mode') || '').trim();
            if (url === '') {
                return;
            }

            openPanelActionModal(url, title, mode);
        });

        bindDocumentHandler('panelDeleteClick', 'click', function (event) {
            const deleteBtn = event.target.closest('.js-panel-delete');
            if (!deleteBtn) {
                return;
            }

            event.preventDefault();

            const panelId = Number(deleteBtn.getAttribute('data-panel-id') || 0);
            const panelName = String(deleteBtn.getAttribute('data-panel-name') || 'this panel').trim();
            if (!panelId) {
                return;
            }

            if (!window.confirm('Delete panel "' + panelName + '"? This will remove mapped components from this panel.')) {
                return;
            }

            const csrfTokenName = '<?= csrf_token() ?>';
            const csrfTokenValue = document.querySelector('input[name="<?= csrf_token() ?>"]')?.value || '<?= csrf_hash() ?>';
            const formData = new window.FormData();
            formData.append(csrfTokenName, csrfTokenValue);

            deleteBtn.disabled = true;

            fetch(PANEL_DELETE_BASE_URL + '/' + encodeURIComponent(String(panelId)), {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            })
            .then(function (r) { return r.json(); })
            .then(function (resp) {
                deleteBtn.disabled = false;

                if (!resp || Number(resp.ok || 0) !== 1) {
                    if (typeof notify === 'function') {
                        notify('warning', 'Delete failed', (resp && resp.error) ? resp.error : 'Unable to delete panel.');
                    }
                    return;
                }

                if (typeof notify === 'function') {
                    notify('success', 'Deleted', 'Panel deleted successfully.');
                }

                if (typeof load_form === 'function') {
                    load_form('<?= base_url('Lab_Admin/report_list') ?>', 'Pathology Template');
                }
            })
            .catch(function () {
                deleteBtn.disabled = false;
                if (typeof notify === 'function') {
                    notify('warning', 'Delete failed', 'Network or server error while deleting panel.');
                }
            });
        });

        function hideAddPanelModal() {
            const modalEl = document.getElementById('addPanelModal');
            if (!modalEl) {
                return;
            }

            if (window.bootstrap && window.bootstrap.Modal) {
                const modal = window.bootstrap.Modal.getInstance(modalEl) || new window.bootstrap.Modal(modalEl);
                modal.hide();
                return;
            }

            if (window.jQuery && $('#addPanelModal').modal) {
                $('#addPanelModal').modal('hide');
            }
        }

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
            if (!key) {
                return '';
            }

            const aliasMap = {
                hematology: 'haematology',
                haematology: 'haematology',
                biochemistry: 'biochemistry',
                biochem: 'biochemistry',
                endocrinology: 'endocrinology',
                infectiousdisease: 'infectiousdisease',
                microbiology: 'infectiousdisease',
                serology: 'infectiousdisease',
                pathology: 'general',
                general: 'general'
            };

            return aliasMap[key] || key;
        }

        function applyGroupFromSubCategory(subCategory) {
            if (!panelGroupSelect) {
                return;
            }

            // Panels can include components from multiple lab groups.
            // Keep group_id as General/0 during create; group display is derived later from actual mapping.
            panelGroupSelect.value = '0';
        }

        function hidePanelSuggestions() {
            if (!panelSuggestionBox) {
                return;
            }
            panelSuggestionBox.style.display = 'none';
            panelSuggestionBox.innerHTML = '';
        }

        function updatePanelMasterMeta(item) {
            if (!panelMasterMeta) {
                return;
            }

            if (!item) {
                panelMasterMeta.innerHTML = 'Type at least 2 letters to search panel in ABDM Gateway API.';
                return;
            }

            const src = item.source === 'bridge' ? 'API' : 'Local';
            const loinc = item.loinc_code ? 'LOINC: ' + escHtml(item.loinc_code) : 'LOINC: N/A';
            const subCat = item.sub_category ? 'Type: ' + escHtml(item.sub_category) : 'Type: N/A';
            const rate = item.standard_rate ? 'Rate: Rs ' + escHtml(item.standard_rate) : '';
            const updated = item.updated_at ? 'Updated: ' + escHtml(item.updated_at) : '';
            const components = selectedGatewayComponentsCount > 0 ? ' | Components: ' + String(selectedGatewayComponentsCount) : '';
            panelMasterMeta.innerHTML = '[' + src + '] ' + loinc + ' | ' + subCat + (rate ? ' | ' + rate : '') + (updated ? ' | ' + updated : '') + components;
        }

        function fetchPanelMasterRecord(panelName) {
            const name = String(panelName || '').trim();
            if (!name) {
                selectedGatewayTemplateHtml = '';
                selectedGatewayComponentsCount = 0;
                return;
            }

            if (panelSearchSpinner) {
                panelSearchSpinner.classList.remove('d-none');
            }

            const onSuccess = function (resp) {
                if (panelSearchSpinner) {
                    panelSearchSpinner.classList.add('d-none');
                }
                if (!resp || Number(resp.ok) !== 1) {
                    selectedGatewayTemplateHtml = '';
                    selectedGatewayComponentsCount = 0;
                    updatePanelMasterMeta(selectedGatewayMaster);
                    return;
                }

                selectedGatewayTemplateHtml = String(resp.template_html || '');
                selectedGatewayComponentsCount = Number(resp.components_count || 0);

                if (selectedGatewayMaster && !selectedGatewayMaster.loinc_code && resp.master && resp.master.loinc_code) {
                    selectedGatewayMaster.loinc_code = String(resp.master.loinc_code || '');
                }
                if (selectedGatewayMaster && (!selectedGatewayMaster.sub_category) && resp.master && resp.master.sub_category) {
                    selectedGatewayMaster.sub_category = String(resp.master.sub_category || '');
                    applyGroupFromSubCategory(selectedGatewayMaster.sub_category);
                }

                if (selectedGatewayMaster && resp.panel_name) {
                    selectedGatewayMaster.name = String(resp.panel_name || '').trim();
                    selectedGatewayMaster.test_name = String(resp.panel_name || '').trim();
                }

                updatePanelMasterMeta(selectedGatewayMaster);
            };

            const onFail = function () {
                if (panelSearchSpinner) {
                    panelSearchSpinner.classList.add('d-none');
                }
                selectedGatewayTemplateHtml = '';
                selectedGatewayComponentsCount = 0;
                updatePanelMasterMeta(selectedGatewayMaster);
            };

            if (typeof window.fetch === 'function') {
                fetch(PANEL_MASTER_TEMPLATE_URL + '?parent_test=' + encodeURIComponent(name), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (r) { return r.json(); })
                .then(onSuccess)
                .catch(onFail);
                return;
            }

            if (window.jQuery) {
                $.ajax({
                    url: PANEL_MASTER_TEMPLATE_URL,
                    method: 'GET',
                    dataType: 'json',
                    data: { parent_test: name },
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).done(onSuccess).fail(onFail);
                return;
            }

            onFail();
        }

        function renderPanelSuggestions(items) {
            if (!panelSuggestionBox) {
                return;
            }

            panelSuggestionBox.innerHTML = '';
            if (!items || items.length === 0) {
                hidePanelSuggestions();
                return;
            }

            items.forEach(function (item) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';

                const sourceBadge = item.source === 'bridge'
                    ? '<span class="badge bg-primary ms-1">API</span>'
                    : '<span class="badge bg-secondary ms-1">Local</span>';

                btn.innerHTML =
                    '<span>' + escHtml(item.name || '') + ' ' + sourceBadge +
                    (item.sub_category ? ' <span class="badge bg-info text-dark ms-1">' + escHtml(item.sub_category) + '</span>' : '') +
                    '</span>' +
                    (item.loinc_code
                        ? '<span class="badge bg-success ms-2">' + escHtml(item.loinc_code) + '</span>'
                        : '<span class="badge bg-light text-muted border ms-2">No LOINC</span>');

                btn.addEventListener('click', function () {
                    selectedGatewayMaster = item;
                    selectedGatewayTemplateHtml = '';
                    selectedGatewayComponentsCount = 0;
                    panelNameInput.value = String(item.test_name || item.name || '').trim();
                    if (panelDescInput && !panelDescInput.value.trim()) {
                        panelDescInput.value = String(item.display_name || item.test_name || item.name || '').trim();
                    }
                    applyGroupFromSubCategory(item.sub_category || '');
                    updatePanelMasterMeta(item);
                    hidePanelSuggestions();
                    panelNameInput.focus();
                    fetchPanelMasterRecord(panelNameInput.value);
                });

                panelSuggestionBox.appendChild(btn);
            });

            panelSuggestionBox.style.display = 'block';
        }

        panelNameInput.addEventListener('input', function () {
            clearTimeout(panelSearchDebounce);
            const q = this.value.trim();

            if (selectedGatewayMaster && String(selectedGatewayMaster.name || '').trim() !== q) {
                selectedGatewayMaster = null;
                selectedGatewayTemplateHtml = '';
                selectedGatewayComponentsCount = 0;
                updatePanelMasterMeta(null);
            }

            if (q.length < 2) {
                hidePanelSuggestions();
                if (!selectedGatewayMaster) {
                    updatePanelMasterMeta(null);
                }
                return;
            }

            if (panelSearchSpinner) {
                panelSearchSpinner.classList.remove('d-none');
            }
            if (panelMasterMeta) {
                panelMasterMeta.innerHTML = 'Searching panel in ABDM Gateway API...';
            }

            panelSearchDebounce = setTimeout(function () {
                const searchUrl = PANEL_MASTER_SEARCH_URL
                    + '?q=' + encodeURIComponent(q)
                    + '&source=' + encodeURIComponent('api')
                    + '&panel_only=' + encodeURIComponent('1');

                const handlePayload = function (payload) {
                    if (panelSearchSpinner) {
                        panelSearchSpinner.classList.add('d-none');
                    }

                    if (payload && !Array.isArray(payload) && Number(payload.ok || 0) === 0) {
                        hidePanelSuggestions();
                        if (panelMasterMeta) {
                            const msg = payload.error || 'Gateway API search failed';
                            const code = payload.http_code ? ' (HTTP ' + String(payload.http_code) + ')' : '';
                            panelMasterMeta.innerHTML = 'Search failed: ' + escHtml(msg) + code;
                        }
                        return;
                    }

                    const items = Array.isArray(payload)
                        ? payload
                        : (payload && Array.isArray(payload.items) ? payload.items : []);
                    renderPanelSuggestions(items);

                    if (!items || items.length === 0) {
                        if (panelMasterMeta && q.length >= 2) {
                            panelMasterMeta.innerHTML = 'No matching panel found in ABDM Gateway API for "' + escHtml(q) + '".';
                        }
                    } else if (panelMasterMeta && !selectedGatewayMaster) {
                        panelMasterMeta.innerHTML = 'Select a panel from ABDM Gateway API list.';
                    }
                };

                const handleSearchFail = function () {
                    if (panelSearchSpinner) {
                        panelSearchSpinner.classList.add('d-none');
                    }
                    hidePanelSuggestions();
                    if (panelMasterMeta) {
                        panelMasterMeta.innerHTML = 'Unable to reach ABDM Gateway API for panel search.';
                    }
                };

                if (typeof window.fetch === 'function') {
                    fetch(searchUrl, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function (r) { return r.json(); })
                    .then(handlePayload)
                    .catch(function () {
                        if (window.jQuery) {
                            $.ajax({
                                url: PANEL_MASTER_SEARCH_URL,
                                method: 'GET',
                                dataType: 'json',
                                data: { q: q, source: 'api', panel_only: 1 },
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            }).done(handlePayload).fail(handleSearchFail);
                            return;
                        }
                        handleSearchFail();
                    });
                    return;
                }

                if (window.jQuery) {
                    $.ajax({
                        url: PANEL_MASTER_SEARCH_URL,
                        method: 'GET',
                        dataType: 'json',
                        data: { q: q, source: 'api', panel_only: 1 },
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    }).done(handlePayload).fail(handleSearchFail);
                    return;
                }

                handleSearchFail();
            }, 320);
        });

        bindDocumentHandler('panelSuggestionOutsideClick', 'click', function (event) {
            if (!panelNameInput.contains(event.target) && !panelSuggestionBox.contains(event.target)) {
                hidePanelSuggestions();
            }
        });

        addPanelForm.addEventListener('submit', function (event) {
            event.preventDefault();

            const panelName = panelNameInput.value.trim();
            if (!panelName) {
                panelNameInput.focus();
                return;
            }

            saveBtn.disabled = true;

            const csrfTokenName = '<?= csrf_token() ?>';
            const csrfTokenValue = document.querySelector('input[name="<?= csrf_token() ?>"]')?.value || '<?= csrf_hash() ?>';

            function applySelectedPanelMapping(repoId, panelName) {
                if (!repoId || Number(repoId) <= 0 || !panelName) {
                    return Promise.resolve({ skipped: true });
                }

                const applyData = new window.FormData();
                applyData.append('repo_id', String(repoId));
                applyData.append('panel_name', String(panelName));
                applyData.append('replace_existing', '1');
                applyData.append(csrfTokenName, csrfTokenValue);

                return fetch(PANEL_APPLY_URL, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: applyData,
                }).then(function (r) { return r.json(); });
            }

            const formData = new window.FormData();
            formData.append('input_Reportname', panelName);
            formData.append('group_id', String(document.getElementById('add_panel_group').value || '0'));
            formData.append('charge_id', String(document.getElementById('add_panel_charge').value || '0'));
            formData.append('HTMLData', selectedGatewayTemplateHtml || '');
            formData.append('loinc_code', selectedGatewayMaster && selectedGatewayMaster.loinc_code ? String(selectedGatewayMaster.loinc_code) : '');
            formData.append(csrfTokenName, csrfTokenValue);

            fetch(PANEL_INSERT_URL, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            })
            .then(r => r.json())
            .then(function (resp) {
                saveBtn.disabled = false;

                if (!resp || Number(resp.insertid || 0) <= 0) {
                    if (typeof notify === 'function') {
                        notify('warning', 'Create failed', (resp && resp.showcontent) ? resp.showcontent : 'Unable to create panel.');
                    }
                    return;
                }

                const insertedRepoId = Number(resp.insertid || 0);
                const selectedPanelName = (selectedGatewayMaster && (selectedGatewayMaster.test_name || selectedGatewayMaster.name))
                    ? String(selectedGatewayMaster.test_name || selectedGatewayMaster.name).trim()
                    : '';

                if (!selectedPanelName) {
                    return { __skipApply: true, insertid: insertedRepoId };
                }

                return applySelectedPanelMapping(insertedRepoId, selectedPanelName)
                    .then(function (applyResp) {
                        return {
                            created: resp,
                            apply: applyResp,
                            insertid: insertedRepoId,
                        };
                    })
                    .catch(function (applyErr) {
                        return {
                            created: resp,
                            apply: { ok: 0, error: 'Unable to auto-apply panel mapping.' },
                            applyError: applyErr,
                            insertid: insertedRepoId,
                        };
                    });
            })
            .then(function (state) {
                if (!state) {
                    return;
                }

                if (state.__skipApply) {
                    if (typeof notify === 'function') {
                        notify('success', 'Panel created', 'Panel created successfully.');
                    }

                    hideAddPanelModal();
                    addPanelForm.reset();
                    selectedGatewayMaster = null;
                    selectedGatewayTemplateHtml = '';
                    selectedGatewayComponentsCount = 0;
                    hidePanelSuggestions();
                    updatePanelMasterMeta(null);

                    if (typeof load_form === 'function') {
                        load_form('<?= base_url('Lab_Admin/report_list') ?>', 'Pathology Template');
                    }
                    return;
                }

                const applyOk = state.apply && Number(state.apply.ok || 0) === 1;

                hideAddPanelModal();
                addPanelForm.reset();
                selectedGatewayMaster = null;
                selectedGatewayTemplateHtml = '';
                selectedGatewayComponentsCount = 0;
                hidePanelSuggestions();
                updatePanelMasterMeta(null);

                if (typeof notify === 'function') {
                    if (applyOk) {
                        notify(
                            'success',
                            'Panel created',
                            'Panel created with print format and ' + String(state.apply.components_count || 0) + ' mapped components.'
                        );
                    } else {
                        notify(
                            'warning',
                            'Panel created with warning',
                            'Panel saved, but component/print mapping auto-apply failed: '
                                + ((state.apply && state.apply.error) ? state.apply.error : 'unknown error')
                        );
                    }
                }

                // Refresh panel list to show the newly created row and imported mapping state.
                if (typeof load_form === 'function') {
                    load_form('<?= base_url('Lab_Admin/report_list') ?>', 'Pathology Template');
                }
            })
            .catch(() => {
                saveBtn.disabled = false;
                if (typeof notify === 'function') {
                    notify('warning', 'Create failed', 'Network or server error while creating panel.');
                }
            });
        });

        if (panelDescInput) {
            panelDescInput.addEventListener('input', function () {
                // Placeholder for parity with gateway form; HMS currently stores description as title.
            });
        }
    })();
</script>

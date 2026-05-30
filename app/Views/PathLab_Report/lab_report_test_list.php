<?= form_open() ?>
<div id="test_div">
<div class="card admin-card component-attach-card">
    <style>
        .component-attach-card .card-header h3 {
            font-size: 18px;
            line-height: 1.2;
            font-weight: 500;
            color: #567299;
        }

        .component-panel-pill {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 3px;
            background: #2f72b1;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
        }

        .component-panel-pill .label {
            opacity: 0.95;
            margin-right: 6px;
        }

        .component-attach-toolbar {
            position: relative;
            display: grid;
            grid-template-columns: 1fr 160px;
            gap: 14px;
            align-items: center;
            margin-top: 14px;
            margin-bottom: 14px;
        }

        .component-attach-toolbar .form-control,
        .component-attach-toolbar .btn {
            height: 46px;
            font-size: 14px;
            border-radius: 2px;
        }

        #gatewayComponentSuggestions {
            position: absolute;
            left: 0;
            right: 174px;
            top: 48px;
            z-index: 10;
            display: none;
            max-height: 280px;
            overflow-y: auto;
            border: 1px solid #d5dde8;
            background: #fff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        #gatewayComponentSuggestions .list-group-item {
            cursor: pointer;
            font-size: 13px;
        }

        #gatewayComponentSuggestions .list-group-item.active,
        #gatewayComponentSuggestions .list-group-item:focus {
            background: #e8f1fb;
            border-color: #b8d0ea;
            color: #1f3f66;
            outline: none;
        }

        .component-attach-table th,
        .component-attach-table td {
            font-size: 13px;
            vertical-align: middle;
        }

        .component-attach-table th {
            color: #6c7f96;
        }

        .component-attach-table .col-number {
            width: 64px;
        }

        .component-attach-table .col-action {
            width: 280px;
        }

        .component-action-stack {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap;
        }

        .component-action-stack .btn {
            border-radius: 4px;
            padding: 5px 10px;
            font-size: 12px;
            line-height: 1.2;
        }

        .component-action-stack .btn-arrow {
            min-width: 40px;
            padding: 4px 8px;
            font-size: 10px;
        }

        .component-attach-footer {
            margin-top: 18px;
            text-align: right;
        }

        .component-attach-footer .btn {
            min-width: 90px;
            font-size: 12px;
        }

        .component-attach-card .btn {
            font-size: 12px;
        }

        .component-attach-card .form-control {
            font-size: 14px;
        }

        @media (max-width: 767.98px) {
            .component-attach-card .card-header h3 {
                font-size: 22px;
            }

            .component-panel-pill {
                font-size: 15px;
            }

            .component-attach-toolbar {
                grid-template-columns: 1fr;
            }

            .component-attach-toolbar .form-control,
            .component-attach-toolbar .btn {
                height: auto;
                font-size: 16px;
            }

            #gatewayComponentSuggestions {
                right: 0;
                top: 44px;
            }

            .component-attach-table th,
            .component-attach-table td,
            .component-action-stack .btn {
                font-size: 13px;
            }
        }
    </style>

    <div class="card-header bg-white">
        <h3 class="mb-0">Add Components to Panel</h3>
    </div>
    <div class="card-body">
        <span class="component-panel-pill"><span class="label">Panel:</span> <?= esc($panel_name ?? '') ?></span>

        <div class="component-attach-toolbar">
            <input type="text" class="form-control" id="componentSearchInput" placeholder="Search component (name / short / code)">
            <button id="btnGatewayAdd" type="button" class="btn btn-primary">+ Add</button>
            <div id="gatewayComponentSuggestions" class="list-group"></div>
        </div>

        <div class="table-responsive">
            <table id="componentAttachTable" class="table table-bordered table-hover align-middle component-attach-table mb-0">
                <thead class="table-light">
                <tr>
                    <th class="col-number">#</th>
                    <th>Component Name</th>
                    <th>Short</th>
                    <th>Code</th>
                    <th class="col-action">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php for ($i = 0; $i < count($lab_Rep_Item_List ?? []); ++$i) { ?>
                    <?php
                    $option_current = $lab_Rep_Item_List[$i]->id ?? 0;
                    $sort_current = $lab_Rep_Item_List[$i]->EOrder ?? 0;
                    $shortCode = (string) ($lab_Rep_Item_List[$i]->short_name ?? $lab_Rep_Item_List[$i]->TestID ?? '');
                    $componentCode = (string) ($lab_Rep_Item_List[$i]->component_code ?? '');
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= esc($lab_Rep_Item_List[$i]->Test ?? '') ?></td>
                        <td><?= esc($shortCode) ?></td>
                        <td><?= esc($componentCode) ?></td>
                        <td>
                            <div class="component-action-stack">
                                <?php if ($i > 0) {
                                    $option_prev = $lab_Rep_Item_List[$i - 1]->id ?? 0;
                                    $sort_prev = $lab_Rep_Item_List[$i - 1]->EOrder ?? 0;
                                    echo '<button type="button" class="btn btn-outline-secondary btn-arrow" title="Move Up" onclick="sortchange(' . (int) ($mstRepoKey ?? 0) . ',' . (int) $option_current . ',' . (int) $sort_current . ',' . (int) $option_prev . ',' . (int) $sort_prev . ')">▲</button>';
                                } else {
                                    echo '<button type="button" class="btn btn-outline-secondary btn-arrow disabled" title="Move Up">▲</button>';
                                } ?>

                                <?php if ($i + 1 < count($lab_Rep_Item_List ?? [])) {
                                    $option_next = $lab_Rep_Item_List[$i + 1]->id ?? 0;
                                    $sort_next = $lab_Rep_Item_List[$i + 1]->EOrder ?? 0;
                                    echo '<button type="button" class="btn btn-outline-secondary btn-arrow" title="Move Down" onclick="sortchange(' . (int) ($mstRepoKey ?? 0) . ',' . (int) $option_current . ',' . (int) $sort_current . ',' . (int) $option_next . ',' . (int) $sort_next . ')">▼</button>';
                                } else {
                                    echo '<button type="button" class="btn btn-outline-secondary btn-arrow disabled" title="Move Down">▼</button>';
                                } ?>

                                <button type="button" class="btn btn-warning text-white" onclick="load_form_div('<?= base_url('Lab_Admin/test_parameter_load') ?>/<?= esc($lab_Rep_Item_List[$i]->mstTestKey ?? 0) ?>/<?= esc($mstRepoKey ?? 0) ?>','test_div');">Edit</button>
                                <button type="button" class="btn btn-danger" onclick="remove_item('<?= esc($mstRepoKey ?? 0) ?>','<?= esc($lab_Rep_Item_List[$i]->mstTestKey ?? 0) ?>');">Remove</button>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="component-attach-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>
</div>
<?= form_close() ?>
<script>
    (function () {
        var GATEWAY_COMPONENT_SEARCH_URL = '<?= base_url('Lab_Admin/pathology_component_masters_search') ?>';
        var GATEWAY_COMPONENT_ADD_URL = '<?= base_url('Lab_Admin/pathology_master_add_component') ?>';
        var RELOAD_COMPONENT_LIST_URL = '<?= base_url('Lab_Admin/report_test_list') ?>/';
        var globalScope = window;

        window.sortchange = function (mstRepoKey, option_current, sort_current, option_prev, sort_prev) {
            var postStr = '<?= base_url('Lab_Admin/change_sort_item') ?>/' + mstRepoKey + '/' + option_current + '/' + sort_current + '/' + option_prev + '/' + sort_prev;
            var csrfValue = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
            $.post(postStr, {
                "mstRepoKey": mstRepoKey,
                "<?= csrf_token() ?>": csrfValue
            }, function(data) {
                $('#test_div').html(data);
            });
        };

        window.remove_item = function (mstRepoKey, mstTestKey) {
            var postStr = '<?= base_url('Lab_Admin/remove_test_item') ?>/' + mstRepoKey + '/' + mstTestKey;
            var csrfValue = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
            $.post(postStr, {
                "mstRepoKey": mstRepoKey,
                "<?= csrf_token() ?>": csrfValue
            }, function() {
                load_form_div(RELOAD_COMPONENT_LIST_URL + mstRepoKey, 'test_div');
            });
        };

        var input = document.getElementById('componentSearchInput');
        var addBtn = document.getElementById('btnGatewayAdd');
        var box = document.getElementById('gatewayComponentSuggestions');
        if (!input || !addBtn || !box) {
            return;
        }

        var selected = null;
        var searchTimer = null;
        var suggestionItems = [];
        var activeIndex = -1;

        function escHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function hideSuggestionBox() {
            box.style.display = 'none';
            box.innerHTML = '';
            suggestionItems = [];
            activeIndex = -1;
        }

        function setActiveSuggestion(nextIndex) {
            if (!suggestionItems.length) {
                activeIndex = -1;
                return;
            }

            if (nextIndex < 0) {
                nextIndex = suggestionItems.length - 1;
            }
            if (nextIndex >= suggestionItems.length) {
                nextIndex = 0;
            }

            suggestionItems.forEach(function (btn, index) {
                btn.classList.toggle('active', index === nextIndex);
            });

            activeIndex = nextIndex;
            if (suggestionItems[activeIndex] && typeof suggestionItems[activeIndex].scrollIntoView === 'function') {
                suggestionItems[activeIndex].scrollIntoView({ block: 'nearest' });
            }
        }

        function chooseSuggestion(item) {
            selected = item;
            input.value = String(item.name || '').trim();
            hideSuggestionBox();
        }

        function renderSuggestionBox(items) {
            box.innerHTML = '';
            suggestionItems = [];
            activeIndex = -1;
            if (!items || items.length === 0) {
                hideSuggestionBox();
                return;
            }

            items.forEach(function (item) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'list-group-item list-group-item-action';
                btn.innerHTML =
                    '<div><strong>' + escHtml(item.name || '') + '</strong></div>'
                    + '<div class="text-muted">Short: ' + escHtml(item.short_name || '-')
                    + ' | Code: ' + escHtml(item.code || '-') + '</div>';

                btn.addEventListener('click', function () {
                    chooseSuggestion(item);
                });

                box.appendChild(btn);
                suggestionItems.push(btn);
            });

            box.style.display = 'block';
            setActiveSuggestion(0);
        }

        if (!globalScope.__pathologyComponentModalHandlers) {
            globalScope.__pathologyComponentModalHandlers = {};
        }

        function bindDocumentHandler(key, handler) {
            var bucket = globalScope.__pathologyComponentModalHandlers;
            if (bucket[key]) {
                document.removeEventListener('click', bucket[key]);
            }
            bucket[key] = handler;
            document.addEventListener('click', handler);
        }

        input.addEventListener('input', function () {
            var q = String(this.value || '').trim();
            selected = null;

            clearTimeout(searchTimer);
            if (q.length < 2) {
                hideSuggestionBox();
                return;
            }

            searchTimer = setTimeout(function () {
                fetch(GATEWAY_COMPONENT_SEARCH_URL + '?q=' + encodeURIComponent(q), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function (r) { return r.json(); })
                .then(function (resp) {
                    if (!resp || Number(resp.ok || 0) !== 1) {
                        hideSuggestionBox();
                        return;
                    }
                    renderSuggestionBox(Array.isArray(resp.items) ? resp.items : []);
                })
                .catch(function () {
                    hideSuggestionBox();
                });
            }, 260);
        });

        input.addEventListener('keydown', function (event) {
            if (box.style.display !== 'block' || !suggestionItems.length) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                setActiveSuggestion(activeIndex + 1);
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                setActiveSuggestion(activeIndex - 1);
                return;
            }

            if (event.key === 'Enter') {
                if (activeIndex >= 0) {
                    event.preventDefault();
                    suggestionItems[activeIndex].click();
                }
                return;
            }

            if (event.key === 'Escape') {
                hideSuggestionBox();
            }
        });

        addBtn.addEventListener('click', function () {
            var repoId = <?= (int) ($mstRepoKey ?? 0) ?>;
            if (!selected || !selected.name) {
                if (typeof notify === 'function') {
                    notify('warning', 'Select component', 'Search and select a gateway component first.');
                }
                input.focus();
                return;
            }

            var csrfValue = (window.jQuery && $('input[name="<?= csrf_token() ?>"]').first().val()) || '<?= csrf_hash() ?>';
            var fd = new FormData();
            fd.append('repo_id', String(repoId));
            fd.append('component_name', String(selected.name || ''));
            fd.append('short_name', String(selected.short_name || ''));
            fd.append('code', String(selected.code || ''));
            fd.append('unit', String(selected.unit || ''));
            fd.append('property', String(selected.property || ''));
            fd.append('specimen_system', String(selected.specimen_system || ''));
            fd.append('scale_type', String(selected.scale_type || ''));
            fd.append('<?= csrf_token() ?>', csrfValue);

            addBtn.disabled = true;
            fetch(GATEWAY_COMPONENT_ADD_URL, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            })
            .then(function (r) { return r.json(); })
            .then(function (resp) {
                addBtn.disabled = false;
                if (!resp || Number(resp.ok || 0) !== 1) {
                    if (typeof notify === 'function') {
                        notify('warning', 'Add failed', (resp && resp.error) ? resp.error : 'Unable to add gateway component.');
                    }
                    return;
                }

                if (typeof notify === 'function') {
                    notify('success', 'Component attached', String(resp.message || 'Added successfully.'));
                }

                load_form_div(RELOAD_COMPONENT_LIST_URL + repoId, 'test_div');
            })
            .catch(function () {
                addBtn.disabled = false;
                if (typeof notify === 'function') {
                    notify('warning', 'Add failed', 'Network/server error while adding gateway component.');
                }
            });
        });

        bindDocumentHandler('componentSuggestionOutsideClick', function (event) {
            if (!input.contains(event.target) && !box.contains(event.target)) {
                hideSuggestionBox();
            }
        });
    }());
</script>

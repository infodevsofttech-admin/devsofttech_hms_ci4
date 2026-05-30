<?= form_open() ?>
<?php
$rows = (isset($labReport_master) && is_array($labReport_master)) ? $labReport_master : [];
$selectedModality = (int) ($modality ?? 2);
?>
<section class="content">
    <style>
        .radiology-template-wrap {
            background: #ffffff;
            border: 1px solid #dbe4ef;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 39, 91, 0.05);
            padding: 10px;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .radiology-template-wrap .card.admin-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .radiology-template-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }
        .radiology-template-table th,
        .radiology-template-table td {
            vertical-align: middle;
            font-size: 13px;
        }
        .radiology-template-table .template-name {
            font-weight: 600;
            color: #2d3f56;
        }
        .radiology-template-table .category-pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            background: #eef5ff;
            border: 1px solid #d5e5ff;
            color: #29568f;
            font-size: 11px;
            font-weight: 700;
        }
        .radiology-template-table .tag-cell {
            color: #4d627a;
        }

        #radiologyActionModal .modal-dialog {
            max-width: min(1320px, 96vw);
        }

        #radiologyActionModal .modal-content {
            height: 92vh;
            border: 0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 16px 45px rgba(0, 29, 74, 0.28);
        }

        #radiologyActionModal .modal-header,
        #radiologyActionModal .modal-footer {
            position: sticky;
            background: #fff;
            z-index: 2;
        }

        #radiologyActionModal .modal-header {
            top: 0;
            border-bottom: 1px solid #e7edf5;
        }

        #radiologyActionModal .modal-footer {
            bottom: 0;
            border-top: 1px solid #e7edf5;
        }

        #radiologyActionModalBody {
            overflow: auto;
            background: #f8fafc;
            padding: 12px;
        }

        @media (max-width: 991.98px) {
            .radiology-template-wrap {
                height: auto;
                min-height: calc(100vh - 120px);
            }

            #radiologyActionModal .modal-content {
                height: 96vh;
            }

            #radiologyActionModalBody {
                padding: 8px;
            }
        }
    </style>
    <div class="radiology-template-wrap">
            <div class="card admin-card">
                <div class="card-header bg-white d-flex align-items-center justify-content-between">
                    <h3 class="mb-0">Radiology Template Mappings</h3>
                    <button
                        type="button"
                        class="btn btn-primary btn-sm js-radiology-action"
                        data-title="Radiology Template - Add"
                        data-url="<?= base_url('Lab_Admin/reportedit_ultrasound_load') ?>/<?= $selectedModality ?>/0"
                    >Add New Report</button>
                </div>
                <div class="card-body radiology-template-scroll">
                    <table id="report_list" class="table table-bordered table-hover align-middle radiology-template-table mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Report Title</th>
                            <th>Group</th>
                            <th>Category</th>
                            <th>Keywords</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php for ($i = 0; $i < count($rows); ++$i) { ?>
                            <tr>
                                <td><span class="template-name"><?= esc($rows[$i]->template_name ?? '') ?></span></td>
                                <td><?= esc($rows[$i]->title ?? '') ?></td>
                                <td>
                                    <?php if (trim((string) ($rows[$i]->impression_cat ?? '')) !== ''): ?>
                                        <span class="category-pill"><?= esc($rows[$i]->impression_cat ?? '') ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="tag-cell"><?= esc($rows[$i]->keywords ?? '') ?></td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary btn-sm js-radiology-action"
                                        data-title="Radiology Template - Edit"
                                        data-url="<?= base_url('Lab_Admin/reportedit_ultrasound_load') ?>/<?= $selectedModality ?>/<?= (int) ($rows[$i]->id ?? 0) ?>"
                                    >Edit</button>
                                    <button
                                        type="button"
                                        class="btn btn-outline-danger btn-sm js-radiology-delete"
                                        data-template-id="<?= (int) ($rows[$i]->id ?? 0) ?>"
                                        data-template-name="<?= esc($rows[$i]->template_name ?? '') ?>"
                                    >Delete</button>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th>Report Title</th>
                            <th>Group</th>
                            <th>Category</th>
                            <th>Keywords</th>
                            <th>Action</th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

    <div class="modal fade" id="radiologyActionModal" tabindex="-1" aria-labelledby="radiologyActionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="radiologyActionModalLabel">Radiology Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="radiologyActionModalBody">
                    <div class="text-muted">Loading...</div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    </div>
</section>
<?= form_close() ?>
<script>
    if (window.jQuery && $.fn && $.fn.DataTable) {
        if ($.fn.DataTable.isDataTable('#report_list')) {
            $('#report_list').DataTable().destroy();
        }
        $('#report_list').DataTable();
    }
</script>
<script>
    (function() {
        var deleteBaseUrl = '<?= base_url('Lab_Admin/report_ultrasound_delete') ?>/<?= $selectedModality ?>';
        var csrfTokenName = '<?= csrf_token() ?>';
        var fallbackCsrfHash = '<?= csrf_hash() ?>';
        var actionModalEl = document.getElementById('radiologyActionModal');
        var actionModalTitleEl = document.getElementById('radiologyActionModalLabel');
        var actionModalBodyEl = document.getElementById('radiologyActionModalBody');
        var activeUrl = '';
        var globalScope = window;

        if (!globalScope.__radiologyTemplateDocHandlers) {
            globalScope.__radiologyTemplateDocHandlers = {};
        }

        function bindDocumentHandler(key, eventName, handler) {
            var bucket = globalScope.__radiologyTemplateDocHandlers;
            if (bucket[key]) {
                document.removeEventListener(eventName, bucket[key]);
            }
            bucket[key] = handler;
            document.addEventListener(eventName, handler);
        }

        function destroyRadiologyEditors() {
            if (typeof CKEDITOR === 'undefined' || !CKEDITOR.instances) {
                return;
            }

            ['HTMLData', 'Impression'].forEach(function(editorId) {
                if (!CKEDITOR.instances[editorId]) {
                    return;
                }

                try {
                    CKEDITOR.instances[editorId].destroy(true);
                } catch (e) {
                    console.warn('Unable to destroy CKEditor instance', editorId, e);
                }
            });
        }

        function getCsrfHash() {
            var input = document.querySelector('input[name="' + csrfTokenName + '"]');
            return input ? (input.value || fallbackCsrfHash) : fallbackCsrfHash;
        }

        function executeInlineScripts(containerEl) {
            if (!containerEl) {
                return;
            }

            var scripts = containerEl.querySelectorAll('script');
            scripts.forEach(function(oldScript) {
                var newScript = document.createElement('script');

                Array.from(oldScript.attributes || []).forEach(function(attr) {
                    newScript.setAttribute(attr.name, attr.value);
                });

                if (oldScript.src) {
                    var exists = document.querySelector('script[src="' + oldScript.src + '"]');
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

        function openRadiologyActionModal(url, title) {
            if (!actionModalEl || !actionModalTitleEl || !actionModalBodyEl || !url) {
                return;
            }

            destroyRadiologyEditors();
            activeUrl = url;
            actionModalTitleEl.textContent = title || 'Radiology Template';
            actionModalBodyEl.innerHTML = '<div class="d-flex align-items-center gap-2 text-muted"><span class="spinner-border spinner-border-sm" role="status"></span><span>Loading...</span></div>';

            if (window.bootstrap && window.bootstrap.Modal) {
                var modal = window.bootstrap.Modal.getInstance(actionModalEl) || new window.bootstrap.Modal(actionModalEl);
                modal.show();
            } else if (window.jQuery && $('#radiologyActionModal').modal) {
                $('#radiologyActionModal').modal('show');
            }

            fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                return response.text();
            })
            .then(function(html) {
                actionModalBodyEl.innerHTML = html;
                executeInlineScripts(actionModalBodyEl);
            })
            .catch(function(error) {
                console.error('Failed to load modal content', error);
                actionModalBodyEl.innerHTML = '<div class="alert alert-warning mb-0">Unable to load content. Please try again.</div>';
            });
        }

        bindDocumentHandler('radiologyActionClick', 'click', function(event) {
            var actionBtn = event.target.closest('.js-radiology-action');
            if (!actionBtn) {
                return;
            }

            event.preventDefault();
            var url = actionBtn.getAttribute('data-url') || '';
            var title = actionBtn.getAttribute('data-title') || 'Radiology Template';
            openRadiologyActionModal(url, title);
        });

        bindDocumentHandler('radiologyDeleteClick', 'click', function(event) {
            var deleteBtn = event.target.closest('.js-radiology-delete');
            if (!deleteBtn) {
                return;
            }

            event.preventDefault();
            var templateId = Number(deleteBtn.getAttribute('data-template-id') || 0);
            var templateName = deleteBtn.getAttribute('data-template-name') || 'this template';

            if (!templateId) {
                alert('Invalid template ID.');
                return;
            }

            if (!window.confirm('Delete radiology template "' + templateName + '"?')) {
                return;
            }

            var bodyData = encodeURIComponent(csrfTokenName) + '=' + encodeURIComponent(getCsrfHash());

            fetch(deleteBaseUrl + '/' + templateId, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: bodyData
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return { ok: response.ok, data: data };
                }).catch(function() {
                    return { ok: response.ok, data: {} };
                });
            })
            .then(function(payload) {
                if (!payload.ok || Number(payload.data.ok || 0) !== 1) {
                    var err = payload.data.error || payload.data.message || 'Failed to delete template';
                    alert(err);
                    return;
                }

                if (typeof window.refreshRadiologyTemplateList === 'function') {
                    window.refreshRadiologyTemplateList();
                    return;
                }

                window.location.reload();
            })
            .catch(function(error) {
                console.error('Delete failed', error);
                alert('Unable to delete template. Please try again.');
            });
        });

        if (actionModalEl) {
            actionModalEl.addEventListener('hidden.bs.modal', function() {
                destroyRadiologyEditors();
                activeUrl = '';
                if (actionModalBodyEl) {
                    actionModalBodyEl.innerHTML = '<div class="text-muted">Loading...</div>';
                }
            });
        } else if (window.jQuery && $('#radiologyActionModal').on) {
            $('#radiologyActionModal').on('hidden.bs.modal', function() {
                destroyRadiologyEditors();
                activeUrl = '';
                if (actionModalBodyEl) {
                    actionModalBodyEl.innerHTML = '<div class="text-muted">Loading...</div>';
                }
            });
        }

        window.refreshRadiologyTemplateList = function() {
            if (typeof load_form === 'function') {
                load_form('<?= base_url('Lab_Admin/report_ultrasound_list') ?>/<?= $selectedModality ?>', 'Diagnosis Template');
                return;
            }
            window.location.reload();
        };
    })();
</script>

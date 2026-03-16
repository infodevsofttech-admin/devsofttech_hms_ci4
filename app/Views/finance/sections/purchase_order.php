<div class="card">
    <div class="card-header"><strong>2) Purchase Order (PO)</strong></div>
    <div class="card-body">
        <div id="finance_section_alert"></div>
        <form id="po_form" class="row g-2">
            <input type="hidden" name="po_id" id="po_id" value="">
            <div class="col-md-4"><input type="text" class="form-control" name="po_no" placeholder="PO No" required></div>
            <div class="col-md-4"><input type="date" class="form-control" name="po_date" required></div>
            <div class="col-md-4">
                <select class="form-select" name="vendor_id" required>
                    <option value="">Vendor</option>
                    <?php foreach (($vendor_options ?? []) as $v): ?>
                        <option value="<?= (int) ($v['id'] ?? 0) ?>"><?= esc((string) ($v['vendor_code'] ?? '') . ' - ' . (string) ($v['vendor_name'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4"><input type="text" class="form-control" name="department" placeholder="Department"></div>
            <div class="col-md-4"><input type="number" step="0.01" class="form-control" name="amount" placeholder="Amount"></div>
            <div class="col-md-4">
                <select class="form-select" name="approval_status">
                    <option value="draft">Draft</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label mb-1">PO Scan / Upload (PDF/JPG/PNG)</label>
                <input type="file" class="form-control" name="po_documents[]" accept=".pdf,.jpg,.jpeg,.png" multiple>
                <small class="text-muted">Optional. You can upload multiple PO documents (max 5 MB each).</small>
            </div>
            <div class="col-12 d-none" id="po_docs_manage">
                <div class="border rounded p-2">
                    <strong class="d-block mb-2">Existing Documents</strong>
                    <div id="po_docs_list" class="small text-muted">No documents.</div>
                    <small class="text-muted">Tick checkbox to remove document when you click Update PO.</small>
                </div>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary btn-sm" id="po_submit_btn" type="submit">Add PO</button>
                <button class="btn btn-outline-secondary btn-sm d-none" id="po_cancel_edit_btn" type="button">Cancel Edit</button>
            </div>
        </form>
        <hr>
        <div id="po_table_wrap"><?= view('finance/partials/po_table', ['purchase_orders' => $purchase_orders ?? []]) ?></div>
    </div>
</div>

<script>
(function() {
    function showAlert(message, ok) {
        var cls = ok ? 'alert-success' : 'alert-danger';
        var html = '<div class="alert ' + cls + ' alert-dismissible fade show" role="alert">'
            + message
            + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            + '</div>';
        var box = document.getElementById('finance_section_alert');
        if (box) {
            box.innerHTML = html;
        }
    }

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    var form = document.getElementById('po_form');
    var tableWrap = document.getElementById('po_table_wrap');
    var submitBtn = document.getElementById('po_submit_btn');
    var cancelEditBtn = document.getElementById('po_cancel_edit_btn');
    var poIdInput = document.getElementById('po_id');
    var docsManage = document.getElementById('po_docs_manage');
    var docsList = document.getElementById('po_docs_list');
    if (!form) {
        return;
    }

    function clearDocsUi() {
        if (docsList) {
            docsList.innerHTML = 'No documents.';
        }
        if (docsManage) {
            docsManage.classList.add('d-none');
        }
    }

    function renderDocs(docs) {
        if (!docsList) {
            return;
        }
        if (!docs || !docs.length) {
            docsList.innerHTML = '<span class="text-muted">No documents uploaded for this PO.</span>';
            return;
        }

        var html = '';
        docs.forEach(function(doc) {
            var id = Number(doc.id || 0);
            var removable = !!doc.removable;
            var name = escapeHtml(doc.file_name || 'Document');
            var path = String(doc.url || '#');
            html += '<div class="d-flex align-items-center justify-content-between mb-1">';
            html += '<div><a href="' + path + '" target="_blank" rel="noopener">' + name + '</a></div>';
            html += '<div>';
            if (removable && id > 0) {
                html += '<label class="form-check-label">';
                html += '<input class="form-check-input me-1" type="checkbox" name="remove_document_ids[]" value="' + id + '"> Remove';
                html += '</label>';
            } else {
                html += '<span class="text-muted">legacy</span>';
            }
            html += '</div></div>';
        });
        docsList.innerHTML = html;
    }

    function loadPoDocuments(poId) {
        if (!poId) {
            clearDocsUi();
            return;
        }
        fetch('<?= base_url('Finance/po_documents') ?>/' + poId, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data || data.status !== 1) {
                clearDocsUi();
                return;
            }
            if (docsManage) {
                docsManage.classList.remove('d-none');
            }
            renderDocs(data.documents || []);
        })
        .catch(function() {
            clearDocsUi();
        });
    }

    function resetEditMode() {
        if (poIdInput) {
            poIdInput.value = '';
        }
        form.reset();
        clearDocsUi();
        if (submitBtn) {
            submitBtn.textContent = 'Add PO';
        }
        if (cancelEditBtn) {
            cancelEditBtn.classList.add('d-none');
        }
    }

    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function() {
            resetEditMode();
        });
    }

    if (tableWrap) {
        tableWrap.addEventListener('click', function(e) {
            var btn = e.target.closest('.js-po-edit');
            if (!btn) {
                return;
            }

            if (poIdInput) {
                poIdInput.value = btn.getAttribute('data-id') || '';
            }

            var poNoEl = form.querySelector('[name="po_no"]');
            var poDateEl = form.querySelector('[name="po_date"]');
            var vendorEl = form.querySelector('[name="vendor_id"]');
            var deptEl = form.querySelector('[name="department"]');
            var amountEl = form.querySelector('[name="amount"]');
            var statusEl = form.querySelector('[name="approval_status"]');

            if (poNoEl) poNoEl.value = btn.getAttribute('data-po-no') || '';
            if (poDateEl) poDateEl.value = btn.getAttribute('data-po-date') || '';
            if (vendorEl) vendorEl.value = btn.getAttribute('data-vendor-id') || '';
            if (deptEl) deptEl.value = btn.getAttribute('data-department') || '';
            if (amountEl) amountEl.value = btn.getAttribute('data-amount') || '';
            if (statusEl) statusEl.value = btn.getAttribute('data-status') || 'draft';

            if (submitBtn) {
                submitBtn.textContent = 'Update PO';
            }
            if (cancelEditBtn) {
                cancelEditBtn.classList.remove('d-none');
            }

            loadPoDocuments(poIdInput ? poIdInput.value : '');

            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new window.FormData(form);
        var isEdit = !!(poIdInput && poIdInput.value);
        var endpoint = isEdit
            ? '<?= base_url('Finance/po_update') ?>'
            : '<?= base_url('Finance/po_create') ?>';

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(function(res) {
            return res.json().then(function(data) {
                return { ok: res.ok, data: data };
            });
        })
        .then(function(result) {
            if (!result.ok || !result.data || result.data.status !== 1) {
                showAlert((result.data && result.data.message) ? result.data.message : 'Request failed', false);
                return;
            }

            showAlert(result.data.message || 'Saved successfully', true);
            resetEditMode();
            load_form_div('<?= base_url('Finance/po_table') ?>', 'po_table_wrap');
        })
        .catch(function() {
            showAlert('Network or server error.', false);
        });
    });
})();
</script>

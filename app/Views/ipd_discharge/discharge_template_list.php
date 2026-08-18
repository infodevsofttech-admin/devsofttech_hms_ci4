<?php $rows = $rows ?? []; ?>
<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="mb-0">IPD Discharge Template Builder</h1>
        <button type="button" class="btn btn-success btn-sm" id="btn_new_tpl">+ Add New Template</button>
    </div>
</section>

<section class="content mt-3">
    <div class="card">
        <div class="card-body">
            <?= csrf_field() ?>
            <div id="tpl_list_msg" class="mb-2"></div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th>Template Name</th>
                            <th style="width:80px;">Default</th>
                            <th style="width:80px;">Audit</th>
                            <th style="width:200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No templates. Click Add New.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $i => $row): ?>
                                <tr>
                                    <td><?= (int) $i + 1 ?></td>
                                    <td><?= esc((string) ($row['template_name'] ?? '')) ?></td>
                                    <td><?= (int) ($row['is_default'] ?? 0) ? '<span class="badge bg-success">Yes</span>' : '' ?></td>
                                    <td><?= (int) ($row['is_audit_only'] ?? 0) ? '<span class="badge bg-warning text-dark">Audit</span>' : '' ?></td>
                                    <td>
                                        <a class="btn btn-primary btn-sm"
                                           href="javascript:load_form('<?= base_url('Ipd_discharge/print_template_builder') ?>?mode=edit&edit=<?= (int) ($row['id'] ?? 0) ?>','IPD Discharge Template Edit');">Edit</a>
                                        <button type="button" class="btn btn-outline-secondary btn-sm btn_rename" data-id="<?= (int) ($row['id'] ?? 0) ?>" data-name="<?= esc((string) ($row['template_name'] ?? '')) ?>">Rename</button>
                                        <button type="button" class="btn btn-danger btn-sm btn_delete" data-id="<?= (int) ($row['id'] ?? 0) ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var listMsg = document.getElementById('tpl_list_msg');

    function getCsrf() {
        var el = document.querySelector('input[name^="csrf_"]');
        return el ? {name: el.name, value: el.value} : {name: 'csrf_test_name', value: ''};
    }

    function showMsg(msg, ok) {
        if (!listMsg) return;
        listMsg.className = 'mb-2';
        listMsg.innerHTML = '<div class="alert alert-' + (ok ? 'success' : 'danger') + ' py-2 mb-0">' + msg + '</div>';
    }

    function updateCsrf(data) {
        if (!data || !data.csrfName) return;
        var el = document.querySelector('input[name="' + data.csrfName + '"]');
        if (el) el.value = data.csrfHash || '';
    }

    document.getElementById('btn_new_tpl').addEventListener('click', function () {
        var name = window.prompt('New template name (letters, numbers, spaces):', '');
        if (!name) return;
        name = name.trim();
        if (!name) { showMsg('Name is required.', false); return; }

        var csrf = getCsrf();
        var fd = new FormData();
        fd.append(csrf.name, csrf.value);
        fd.append('template_name', name);
        fd.append('template_html', '{{CONTENT}}');
        fd.append('page_size', 'A4');
        fd.append('status', '1');

        fetch('<?= base_url('setting/template/discharge_templates') ?>', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: fd
        }).then(function (r) { return r.json(); }).then(function (data) {
            updateCsrf(data);
            if (data.update == 1) {
                load_form('<?= base_url('Ipd_discharge/print_template_builder') ?>?mode=edit&edit=' + encodeURIComponent(data.id || 0), 'IPD Discharge Template Edit');
            } else {
                showMsg(data.error_text || 'Unable to create template.', false);
            }
        }).catch(function () { showMsg('Request failed.', false); });
    });

    document.querySelectorAll('.btn_rename').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-id');
            var oldName = btn.getAttribute('data-name');
            var newName = window.prompt('Rename template:', oldName);
            if (!newName || newName.trim() === oldName) return;
            newName = newName.trim();

            var csrf = getCsrf();
            var fd = new FormData();
            fd.append(csrf.name, csrf.value);
            fd.append('id', id);
            fd.append('template_name', newName);

            fetch('<?= base_url('Ipd_discharge/discharge_template_rename_ajax') ?>', {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: fd
            }).then(function (r) { return r.json(); }).then(function (data) {
                updateCsrf(data);
                if (data.update == 1) {
                    load_form('<?= base_url('Ipd_discharge/print_template_builder') ?>?mode=list', 'IPD Discharge Templates');
                } else {
                    showMsg(data.error_text || 'Unable to rename.', false);
                }
            }).catch(function () { showMsg('Request failed.', false); });
        });
    });

    document.querySelectorAll('.btn_delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('Delete this template?')) return;
            var id = btn.getAttribute('data-id');
            var csrf = getCsrf();
            var fd = new FormData();
            fd.append(csrf.name, csrf.value);

            fetch('<?= base_url('setting/template/discharge_templates/delete') ?>/' + id, {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: fd
            }).then(function (r) { return r.json(); }).then(function (data) {
                updateCsrf(data);
                if (data.update == 1) {
                    load_form('<?= base_url('Ipd_discharge/print_template_builder') ?>?mode=list', 'IPD Discharge Templates');
                } else {
                    showMsg(data.error_text || 'Unable to delete.', false);
                }
            }).catch(function () { showMsg('Request failed.', false); });
        });
    });
})();
</script>

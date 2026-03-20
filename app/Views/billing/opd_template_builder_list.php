<section class="content-header">
    <div class="clearfix">
        <div style="float:left;">
            <h1>OPD Print Template Builder</h1>
        </div>
        <div style="float:right; margin-top:8px;">
            <?php $firstTemplate = !empty($template_names ?? []) ? (string) $template_names[0] : 'default'; ?>
            <div class="btn-group btn-group-sm" role="group" aria-label="Template Navigation">
                <button type="button" class="btn btn-primary" disabled>Template List</button>
                <a class="btn btn-default" href="javascript:load_form_div('<?= base_url('Opd/print_template_builder') ?>?mode=edit&name=<?= esc($firstTemplate) ?>','maindiv','OPD Template Edit');">Edit Template</a>
            </div>
        </div>
    </div>
    <ol class="breadcrumb">
        <li><a href="javascript:load_form_div('<?= base_url('opd/appointment') ?>','maindiv','OPD Appointment');">OPD</a></li>
        <li class="active">Template List</li>
    </ol>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Templates</h3>
            <div class="box-tools">
                <button type="button" class="btn btn-success btn-sm" id="btn_new_template">+ Add New Template</button>
            </div>
        </div>
        <div class="box-body">
            <?= csrf_field() ?>
            <div id="tmpl_list_msg" class="text-muted" style="margin-bottom:8px;"></div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th style="width: 70px;">#</th>
                            <th>Template Name</th>
                            <th style="width: 220px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($template_names ?? [])) : ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">No templates found. Click "Add New Template".</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach (($template_names ?? []) as $idx => $nm) : ?>
                                <tr>
                                    <td><?= (int) $idx + 1 ?></td>
                                    <td><?= esc($nm) ?></td>
                                    <td>
                                        <a class="btn btn-primary btn-xs" href="javascript:load_form_div('<?= base_url('Opd/print_template_builder') ?>?mode=edit&name=<?= esc($nm) ?>','maindiv','OPD Template Edit');">Edit</a>
                                        <button type="button" class="btn btn-default btn-xs btn_rename_template" data-name="<?= esc($nm) ?>">Rename</button>
                                        <button type="button" class="btn btn-danger btn-xs btn_delete_template" data-name="<?= esc($nm) ?>">Delete</button>
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
    function normalizeTemplateId(value) {
        value = (value || '').trim().toLowerCase().replace(/[ .]+/g, '_').replace(/[^a-z0-9_\-]+/g, '_').replace(/_+/g, '_');
        return value.replace(/^[_-]+|[_-]+$/g, '');
    }

    function getCsrfPair() {
        var input = document.querySelector('input[name^="csrf_"]');
        if (!input) {
            return { name: 'csrf_test_name', value: '' };
        }
        return { name: input.getAttribute('name'), value: input.value };
    }

    function setListMsg(msg, ok) {
        var el = document.getElementById('tmpl_list_msg');
        if (!el) {
            return;
        }
        el.className = ok ? 'text-success' : 'text-danger';
        el.textContent = msg || '';
    }

    var btn = document.getElementById('btn_new_template');
    if (!btn) {
        return;
    }

    btn.addEventListener('click', function () {
        var name = window.prompt('Enter new template name (letters, numbers, _ or -):', '');
        if (name === null) {
            return;
        }
        name = normalizeTemplateId(name);
        if (!name) {
            setListMsg('Valid template name required.', false);
            return;
        }

        load_form_div('<?= base_url('Opd/print_template_builder') ?>?mode=edit&name=' + encodeURIComponent(name), 'maindiv', 'OPD Template Edit');
    });

    var renameButtons = document.querySelectorAll('.btn_rename_template');
    renameButtons.forEach(function (renameBtn) {
        renameBtn.addEventListener('click', function () {
            var oldName = (renameBtn.getAttribute('data-name') || '').trim();
            if (!oldName) {
                return;
            }

            var newName = window.prompt('Enter new template name (letters, numbers, _ or -):', oldName);
            if (newName === null) {
                return;
            }
            newName = normalizeTemplateId(newName);
            if (!newName) {
                setListMsg('Valid new template name required.', false);
                return;
            }
            if (newName === oldName) {
                setListMsg('New template name is same as current name.', false);
                return;
            }

            var csrf = getCsrfPair();
            var payload = {
                old_name: oldName,
                new_name: newName
            };
            payload[csrf.name] = csrf.value;

            $.post('<?= base_url('Opd/print_template_rename') ?>', payload, function (res) {
                if (res && res.csrfName && res.csrfHash) {
                    var tokenInput = document.querySelector('input[name="' + res.csrfName + '"]');
                    if (tokenInput) {
                        tokenInput.value = res.csrfHash;
                    }
                }

                if (!res || Number(res.update || 0) !== 1) {
                    setListMsg((res && res.error_text) ? res.error_text : 'Unable to rename template', false);
                    return;
                }

                setListMsg('Template renamed: ' + oldName + ' -> ' + (res.new_name || newName), true);
                load_form_div('<?= base_url('Opd/print_template_builder') ?>?mode=list', 'maindiv', 'OPD Template List');
            }, 'json').fail(function () {
                setListMsg('Unable to rename template', false);
            });
        });
    });

    var deleteButtons = document.querySelectorAll('.btn_delete_template');
    deleteButtons.forEach(function (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            var templateName = (deleteBtn.getAttribute('data-name') || '').trim();
            if (!templateName) {
                return;
            }

            if (!confirm('Are you sure you want to delete template "' + templateName + '"? This action cannot be undone.')) {
                return;
            }

            var csrf = getCsrfPair();
            var payload = {
                template_name: templateName
            };
            payload[csrf.name] = csrf.value;

            $.post('<?= base_url('Opd/print_template_delete') ?>', payload, function (res) {
                if (res && res.csrfName && res.csrfHash) {
                    var tokenInput = document.querySelector('input[name="' + res.csrfName + '"]');
                    if (tokenInput) {
                        tokenInput.value = res.csrfHash;
                    }
                }

                if (!res || Number(res.update || 0) !== 1) {
                    setListMsg((res && res.error_text) ? res.error_text : 'Unable to delete template', false);
                    return;
                }

                setListMsg('Template deleted: ' + templateName, true);
                load_form_div('<?= base_url('Opd/print_template_builder') ?>?mode=list', 'maindiv', 'OPD Template List');
            }, 'json').fail(function () {
                setListMsg('Unable to delete template', false);
            });
        });
    });
})();
</script>

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
            <div id="tmpl_list_msg" class="text-muted" style="margin-bottom:8px;"></div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th style="width: 70px;">#</th>
                            <th>Template Name</th>
                            <th style="width: 140px;">Action</th>
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
    var btn = document.getElementById('btn_new_template');
    if (!btn) {
        return;
    }

    btn.addEventListener('click', function () {
        var name = window.prompt('Enter new template name (letters, numbers, _ or -):', '');
        if (name === null) {
            return;
        }
        name = (name || '').trim().toLowerCase().replace(/[^a-z0-9_\-]/g, '');
        if (!name) {
            var msg = document.getElementById('tmpl_list_msg');
            if (msg) {
                msg.className = 'text-danger';
                msg.textContent = 'Valid template name required.';
            }
            return;
        }

        load_form_div('<?= base_url('Opd/print_template_builder') ?>?mode=edit&name=' + encodeURIComponent(name), 'maindiv', 'OPD Template Edit');
    });
})();
</script>

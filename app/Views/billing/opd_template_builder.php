<section class="content-header">
    <div class="clearfix">
        <div style="float:left;">
            <h1>OPD Print Template Builder</h1>
        </div>
        <div style="float:right; margin-top:8px;">
            <div class="btn-group btn-group-sm" role="group" aria-label="Template Navigation">
                <a class="btn btn-info" href="javascript:load_form_div('<?= base_url('Opd/print_template_builder') ?>?mode=list','maindiv','OPD Template List');">Template List</a>
                <button type="button" class="btn btn-primary" disabled>Edit Template</button>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Edit Template Content</h3>
                    <div class="box-tools">
                        <button type="button" class="btn btn-default btn-sm" id="btn_back_to_list">Back to List</button>
                    </div>
                </div>
                <div class="box-body">
                    <?= csrf_field() ?>
                    <div class="row">
                        <div class="col-sm-6">
                            <label>Template Name</label>
                            <input type="text" id="tmpl_name" class="form-control" value="<?= esc($selected_name ?? 'default') ?>" placeholder="default">
                        </div>
                        <div class="col-sm-6" style="padding-top:25px;">
                            <button type="button" class="btn btn-default" id="btn_new_part">New</button>
                            <button type="button" class="btn btn-default" id="btn_load_part">Edit</button>
                            <button type="button" class="btn btn-primary" id="btn_save_part">Save</button>
                        </div>
                    </div>
                    <div class="row" style="margin-top:8px;">
                        <div class="col-sm-8">
                            <label>Existing Templates</label>
                            <select id="tmpl_existing" class="form-control">
                                <option value="">-- Select existing template --</option>
                                <?php foreach (($template_names ?? []) as $nm) : ?>
                                    <option value="<?= esc($nm) ?>" <?= ($selected_name ?? '') === $nm ? 'selected' : '' ?>><?= esc($nm) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-4" style="padding-top:25px;">
                            <button type="button" class="btn btn-info" id="btn_use_existing">Use Selected</button>
                        </div>
                    </div>
                    <hr>
                    <div class="form-group">
                        <label>HTML Content</label>
                        <textarea id="tmpl_content" class="form-control" rows="20" style="font-family:Consolas,monospace;"><?= esc($template_content ?? '') ?></textarea>
                    </div>
                    <div id="tmpl_msg" class="text-muted"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">Placeholders</h3>
                </div>
                <div class="box-body" style="max-height:280px; overflow:auto;">
                    <?php foreach (($placeholders ?? []) as $ph) : ?>
                        <span class="label label-default" style="display:inline-block; margin:2px;">{{<?= esc($ph) ?>}}</span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="alert alert-info">
                <strong>Old-style (recommended):</strong> save a name (example: <strong>opd_print_parcha_chamunda_hospital_ksp</strong>), then set that name in doctor master fields:<br>
                <code>opd_print_format</code>, <code>opd_blank_print</code>, <code>rx_pre_print_letter_head_format</code>, <code>rx_blank_letter_head</code>, <code>rx_plain_paper</code>.<br><br>
                <strong>Optional composed mode:</strong> use <code>compose_default</code> with type-specific parts.
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var btnBack = document.getElementById('btn_back_to_list');
    if (btnBack) {
        btnBack.addEventListener('click', function () {
            load_form_div('<?= base_url('Opd/print_template_builder') ?>?mode=list', 'maindiv', 'OPD Template List');
        });
    }

    function getCsrfPair() {
        var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
        if (!input) {
            return { name: '<?= csrf_token() ?>', value: '<?= csrf_hash() ?>' };
        }
        return { name: input.getAttribute('name'), value: input.value };
    }

    function setMsg(msg, ok) {
        var el = document.getElementById('tmpl_msg');
        el.className = ok ? 'text-success' : 'text-danger';
        el.textContent = msg || '';
    }

    document.getElementById('btn_load_part').addEventListener('click', function () {
        var name = (document.getElementById('tmpl_name').value || 'default').trim();
        if (!name) { name = 'default'; }
        load_form_div('<?= base_url('Opd/print_template_builder') ?>?mode=edit&name=' + encodeURIComponent(name), 'maindiv', 'OPD Template Edit');
    });

    document.getElementById('btn_new_part').addEventListener('click', function () {
        document.getElementById('tmpl_name').value = '';
        document.getElementById('tmpl_content').value = '';
        setMsg('Enter new template name, then write HTML and click Save.', true);
    });

    document.getElementById('btn_use_existing').addEventListener('click', function () {
        var selected = document.getElementById('tmpl_existing').value || '';
        if (!selected) {
            setMsg('Please select an existing template.', false);
            return;
        }
        document.getElementById('tmpl_name').value = selected;
        load_form_div('<?= base_url('Opd/print_template_builder') ?>?mode=edit&name=' + encodeURIComponent(selected), 'maindiv', 'OPD Template Edit');
    });

    document.getElementById('btn_save_part').addEventListener('click', function () {
        var name = (document.getElementById('tmpl_name').value || '').trim();
        var content = document.getElementById('tmpl_content').value || '';
        if (!name) {
            setMsg('Template name required', false);
            return;
        }
        var csrf = getCsrfPair();
        var payload = {
            section: 'full',
            name: name,
            content: content
        };
        payload[csrf.name] = csrf.value;

        $.post('<?= base_url('Opd/print_template_save') ?>', payload, function (res) {
            if (res && res.csrfName && res.csrfHash) {
                var tokenInput = document.querySelector('input[name="' + res.csrfName + '"]');
                if (tokenInput) tokenInput.value = res.csrfHash;
            }
            if (!res || Number(res.update || 0) !== 1) {
                setMsg((res && res.error_text) ? res.error_text : 'Unable to save template', false);
                return;
            }
            setMsg('Saved: ' + (res.section || 'full') + '/' + (res.name || name), true);
            setTimeout(function () {
                load_form_div('<?= base_url('Opd/print_template_builder') ?>?mode=edit&name=' + encodeURIComponent(res.name || name), 'maindiv', 'OPD Template Edit');
            }, 250);
        }, 'json').fail(function () {
            setMsg('Unable to save template', false);
        });
    });
})();
</script>

<?php
$settings = $settings ?? [];
$notice = (string) ($notice ?? '');
$noticeType = (string) ($notice_type ?? 'success');
$pageSize = strtoupper((string) ($settings['page_size'] ?? 'A4'));
?>

<section class="content-header">
    <div class="clearfix">
        <div style="float:left;">
            <h1>OPD Paper Print Settings</h1>
        </div>
        <div style="float:right; margin-top:8px;">
            <a class="btn btn-sm btn-info" href="javascript:load_form_div('<?= base_url('Opd/print_template_builder') ?>?mode=list','maindiv','OPD Template List');">Template Builder</a>
        </div>
    </div>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Dedicated OPD PDF page/header/footer settings</h3>
        </div>
        <div class="box-body">
            <div id="opd_paper_notice">
                <?php if ($notice !== ''): ?>
                    <div class="alert alert-<?= esc($noticeType) ?> py-2 mb-3"><?= esc($notice) ?></div>
                <?php endif; ?>
            </div>

            <form method="post" action="<?= base_url('Opd/paper_print_settings') ?>" id="opd_paper_print_form">
                <?= csrf_field() ?>

                <div class="row">
                    <div class="col-md-3">
                        <label>Page Size</label>
                        <select class="form-control" name="page_size">
                            <option value="A4" <?= $pageSize === 'A4' ? 'selected' : '' ?>>A4</option>
                            <option value="A4-L" <?= $pageSize === 'A4-L' ? 'selected' : '' ?>>A4 Landscape</option>
                            <option value="A5" <?= $pageSize === 'A5' ? 'selected' : '' ?>>A5</option>
                            <option value="A6" <?= $pageSize === 'A6' ? 'selected' : '' ?>>A6</option>
                            <option value="LETTER" <?= $pageSize === 'LETTER' ? 'selected' : '' ?>>Letter</option>
                            <option value="LEGAL" <?= $pageSize === 'LEGAL' ? 'selected' : '' ?>>Legal</option>
                        </select>
                    </div>
                </div>

                <hr>
                <h4 style="margin-top:0;">Page & Margin (cm)</h4>
                <div class="row">
                    <div class="col-md-2">
                        <label>Top</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control" name="page_margin_top_cm" value="<?= esc((string) ($settings['page_margin_top_cm'] ?? '6.1')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Bottom</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control" name="page_margin_bottom_cm" value="<?= esc((string) ($settings['page_margin_bottom_cm'] ?? '2.5')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Left</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control" name="page_margin_left_cm" value="<?= esc((string) ($settings['page_margin_left_cm'] ?? '0.7')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Right</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control" name="page_margin_right_cm" value="<?= esc((string) ($settings['page_margin_right_cm'] ?? '0.7')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Header</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control" name="margin_header_cm" value="<?= esc((string) ($settings['margin_header_cm'] ?? '0.5')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Footer</label>
                        <input type="number" step="0.1" min="0" max="25" class="form-control" name="margin_footer_cm" value="<?= esc((string) ($settings['margin_footer_cm'] ?? '1.5')) ?>">
                    </div>
                </div>

                <hr>
                <h4 style="margin-top:0;">Header / Footer HTML</h4>
                <div class="alert alert-info" style="padding:8px 10px; font-size:12px;">
                    Use variables with <b>{{name}}</b> or <b>{name}</b>.<br>
                    Hospital: <code>H_Name</code>, <code>H_address_1</code>, <code>H_address_2</code>, <code>H_phone_No</code>, <code>H_Email</code>, <code>H_logo</code>.<br>
                    Common: <code>print_time</code>, <code>current_date</code>, <code>doctor_name</code>.<br>
                    OPD: <code>pName</code>, <code>age_sex</code>, <code>phoneno</code>, <code>uhid_no</code>, <code>opd_no</code>, <code>opd_date</code>, <code>Complaint</code>, <code>diagnosis</code>, <code>medical</code>, <code>advice</code>.<br>
                    mPDF page tokens like <code>{PAGENO}</code> and <code>{nbpg}</code> are supported.
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <label>Header HTML / mPDF tags</label>
                        <textarea class="form-control" name="header_html" rows="7" style="font-family:Consolas,Monaco,monospace;"><?= esc((string) ($settings['header_html'] ?? '')) ?></textarea>
                    </div>
                    <div class="col-md-12" style="margin-top:10px;">
                        <label>Footer HTML / mPDF tags</label>
                        <textarea class="form-control" name="footer_html" rows="5" style="font-family:Consolas,Monaco,monospace;"><?= esc((string) ($settings['footer_html'] ?? '')) ?></textarea>
                    </div>
                </div>

                <div style="margin-top:12px;">
                    <button type="submit" class="btn btn-primary btn-sm">Save OPD Print Settings</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
(function () {
    var form = document.getElementById('opd_paper_print_form');
    var noticeBox = document.getElementById('opd_paper_notice');

    function showNotice(type, message) {
        if (!noticeBox) {
            return;
        }
        var safeType = (type === 'success') ? 'success' : 'danger';
        var safeText = String(message || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        noticeBox.innerHTML = '<div class="alert alert-' + safeType + ' py-2 mb-3">' + safeText + '</div>';
    }

    if (!form) {
        return;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
        }

        $.ajax({
            url: form.action,
            method: 'POST',
            data: $(form).serialize(),
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .done(function (result) {
            var status = (result && result.status) ? result.status : 'error';
            showNotice(status === 'success' ? 'success' : 'danger', (result && result.notice) ? result.notice : 'Save failed.');

            if (result && result.csrfName && result.csrfHash) {
                var csrfInput = form.querySelector('input[name="' + result.csrfName + '"]');
                if (csrfInput) {
                    csrfInput.value = result.csrfHash;
                }
            }
        })
        .fail(function () {
            showNotice('danger', 'Save failed.');
        })
        .always(function () {
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        });
    });
})();
</script>

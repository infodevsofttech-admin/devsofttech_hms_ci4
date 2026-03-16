<?php
$doc = $doc_master ?? [];
$docId = (int) ($doc['df_id'] ?? 0);
$docName = (string) ($doc['doc_name'] ?? '');
$docDesc = (string) ($doc['doc_desc'] ?? '');
$docHtml = (string) ($doc['doc_raw_format'] ?? '');
$defaultPrintType = (int) ($doc['default_print_type'] ?? 0);
$printTopMargin = (float) ($doc['print_top_margin'] ?? 6.10);
$printBottomMargin = (float) ($doc['print_bottom_margin'] ?? 2.50);
$printLeftMargin = (float) ($doc['print_left_margin'] ?? 0.70);
$printRightMargin = (float) ($doc['print_right_margin'] ?? 0.70);
$printHeaderMargin = (float) ($doc['print_header_margin'] ?? 0.50);
$printFooterMargin = (float) ($doc['print_footer_margin'] ?? 1.50);
?>
<form method="post" onsubmit="return false;">
    <?= csrf_field() ?>
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">Template Editor</h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Document Name</label>
                        <input class="form-control" id="input_docname" placeholder="Document Name" type="text" value="<?= esc($docName) ?>">
                        <input type="hidden" id="df_id" value="<?= $docId ?>">
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input class="form-control" id="input_doc_desc" placeholder="Description" type="text" value="<?= esc($docDesc) ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Default Print Template</label>
                        <select class="form-control" id="default_print_type">
                            <option value="0" <?= $defaultPrintType === 0 ? 'selected' : '' ?>>Letter Head</option>
                            <option value="1" <?= $defaultPrintType === 1 ? 'selected' : '' ?>>Plain Paper</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Top Margin (cm)</label>
                        <input class="form-control" id="print_top_margin" type="number" min="0.1" max="20" step="0.10" value="<?= number_format($printTopMargin, 2, '.', '') ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Bottom Margin (cm)</label>
                        <input class="form-control" id="print_bottom_margin" type="number" min="0.1" max="20" step="0.10" value="<?= number_format($printBottomMargin, 2, '.', '') ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Left Margin (cm)</label>
                        <input class="form-control" id="print_left_margin" type="number" min="0.1" max="20" step="0.10" value="<?= number_format($printLeftMargin, 2, '.', '') ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Right Margin (cm)</label>
                        <input class="form-control" id="print_right_margin" type="number" min="0.1" max="20" step="0.10" value="<?= number_format($printRightMargin, 2, '.', '') ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Header Margin (cm)</label>
                        <input class="form-control" id="print_header_margin" type="number" min="0.1" max="20" step="0.10" value="<?= number_format($printHeaderMargin, 2, '.', '') ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label">Footer Margin (cm)</label>
                        <input class="form-control" id="print_footer_margin" type="number" min="0.1" max="20" step="0.10" value="<?= number_format($printFooterMargin, 2, '.', '') ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <textarea id="HTMLData" placeholder="Place text here"><?= esc($docHtml) ?></textarea>
                </div>
            </div>
            <div class="mt-3">
                <button id="updatereport" type="button" class="btn btn-primary btn-sm">Save Template</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Document Parameters</h5>
            <p><b>Pre-Define</b></p>
            <p style="line-height:1.8;">
                <i>UHID</i>[p_code] <i>Patient Name</i>[p_fname] <i>Relative Name</i>[p_rname]
                <i>Patient Age</i>[str_age] <i>Relation</i>[p_relative] <i>Gender</i>[gender]
                <i>Title</i>[p_title] <i>Patient Address</i>[p_address] <i>HE_SHE</i>[p_he_she]
                <i>HIS_HER</i>[p_his_her] <i>Doctor Name</i>[dr_name] <i>Doctor Sign</i>[dr_sign]
                <i>Current Date</i>[current_date] <i>Issue Date</i>[issue_date]
            </p>
            <hr>
            <p><b>Custom Define Input Parameter</b></p>
            <p style="line-height:1.8;">
                <?php foreach (($doc_Item_List ?? []) as $item): ?>
                    <i><?= esc((string) ($item['input_name'] ?? '')) ?></i>[<?= esc((string) ($item['input_code'] ?? '')) ?>]
                <?php endforeach; ?>
            </p>
            <?php if ($docId > 0): ?>
                <button onclick="load_form_div('<?= base_url('Doc_Admin/doc_input_list/' . $docId) ?>','test_div');" type="button" class="btn btn-outline-primary btn-sm">Document Input List</button>
            <?php endif; ?>
        </div>
    </div>
</form>

<script>
if (window.CKEDITOR) {
    if (CKEDITOR.instances.HTMLData) {
        try {
            CKEDITOR.instances.HTMLData.destroy(true);
        } catch (e) {
            console.warn('Unable to destroy stale HTMLData editor instance', e);
        }
    }
    CKEDITOR.replace('HTMLData');
}

$('#updatereport').off('click').on('click', function() {
    var df_id = $('#df_id').val();
    var input_docname = $('#input_docname').val();
    var input_doc_desc = $('#input_doc_desc').val();
    var htmlData = (window.CKEDITOR && CKEDITOR.instances.HTMLData) ? CKEDITOR.instances.HTMLData.getData() : $('#HTMLData').val();
    var csrfName = $('input[name="<?= csrf_token() ?>"]').attr('name');
    var csrfHash = $('input[name="<?= csrf_token() ?>"]').val();

    var url = (parseInt(df_id || '0', 10) > 0)
        ? '<?= base_url('Doc_Admin/report_update') ?>'
        : '<?= base_url('Doc_Admin/report_insert') ?>';

    $.post(url, {
        df_id: df_id,
        input_docname: input_docname,
        input_doc_desc: input_doc_desc,
        default_print_type: $('#default_print_type').val(),
        print_top_margin: $('#print_top_margin').val(),
        print_bottom_margin: $('#print_bottom_margin').val(),
        print_left_margin: $('#print_left_margin').val(),
        print_right_margin: $('#print_right_margin').val(),
        print_header_margin: $('#print_header_margin').val(),
        print_footer_margin: $('#print_footer_margin').val(),
        HTMLData: htmlData,
        [csrfName]: csrfHash
    }, function(data) {
        alert(data.showcontent || 'Saved');
        if (data.csrfName && data.csrfHash) {
            $('input[name="' + data.csrfName + '"]').val(data.csrfHash);
        }
        if (data.insertid && parseInt(data.insertid, 10) > 0) {
            load_form('<?= base_url('Doc_Admin/doc_list') ?>');
        }
    }, 'json');
});
</script>

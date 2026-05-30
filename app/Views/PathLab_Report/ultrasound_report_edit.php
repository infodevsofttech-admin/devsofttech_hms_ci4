<?= form_open() ?>
<style>
    .radiology-edit-shell {
        background: #ffffff;
        border: 1px solid #dbe4ef;
        border-radius: 10px;
        box-shadow: 0 8px 20px rgba(0, 39, 91, 0.05);
    }
    .radiology-edit-shell .card-header h3 {
        color: #2d3f56;
    }
    .radiology-meta-badge {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 4px;
        background: #2f72b1;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 10px;
    }
    .radiology-edit-shell .form-group label {
        font-weight: 600;
        color: #2d3f56;
        font-size: 12px;
        letter-spacing: 0.2px;
        text-transform: uppercase;
    }
    .radiology-editor-card .card-header h4 {
        font-size: 15px;
        font-weight: 700;
        margin: 0;
        color: #2d3f56;
    }
</style>
<div class="card admin-card">
    <div class="card-header bg-white">
        <h3 class="mb-0">Radiology Template Edit</h3>
    </div>
    <div class="card-body radiology-edit-shell">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Template Name</label>
                    <?php
                    $repo_name = '';
                    $repo_id = '0';
                    $HTMLData = '';
                    $repo_title = '';
                    $Impression = '';
                    $keywords = '';
                    $impressionCat = '';
                    if (count($labReport_master ?? []) > 0) {
                        $repo_id = $labReport_master[0]->id ?? '0';
                        $repo_name = $labReport_master[0]->template_name ?? '';
                        $repo_title = $labReport_master[0]->title ?? '';
                        $HTMLData = $labReport_master[0]->Findings ?? '';
                        $Impression = $labReport_master[0]->Impression ?? '';
                        $keywords = $labReport_master[0]->keywords ?? '';
                        $impressionCat = $labReport_master[0]->impression_cat ?? '';
                    }
                    ?>
                    <div class="radiology-meta-badge">Template ID: <?= esc($repo_id) ?></div>
                    <input class="form-control" id="input_Reportname" name="input_Reportname" placeholder="Report Name" type="text" value="<?= esc($repo_name) ?>" />
                    <input type="hidden" id="repo_id" value="<?= esc($repo_id) ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Attach Charge Name</label>
                    <select class="form-select" id="charge_id" name="charge_id">
                        <?php
                        $sel_value = 0;
                        if (count($labReport_master ?? []) > 0) {
                            $sel_value = (int) ($labReport_master[0]->charge_id ?? 0);
                        }
                        echo '<option value="0" ' . combo_checked('0', $sel_value) . '>No Attach</option>';
                        foreach (($hc_items ?? []) as $row) {
                            echo '<option value="' . esc($row->id ?? 0) . '" ' . combo_checked($row->id ?? 0, $sel_value) . '>' . esc($row->idesc ?? '') . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Print Report Name</label>
                    <input class="form-control" id="input_Reporttitle" name="input_Reporttitle" placeholder="Report Title" type="text" value="<?= esc($repo_title) ?>" />
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Search Keywords (comma separated)</label>
                    <input class="form-control" id="keywords" name="keywords" placeholder="e.g. chest, pleural effusion, pa view" type="text" value="<?= esc($keywords) ?>" />
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Impression Category</label>
                    <input class="form-control" id="impression_cat" name="impression_cat" list="impression_category_list" placeholder="e.g. Normal / Abnormal / Urgent" type="text" value="<?= esc($impressionCat) ?>" />
                    <datalist id="impression_category_list">
                        <option value="Normal"></option>
                        <option value="Abnormal"></option>
                        <option value="Borderline"></option>
                        <option value="Urgent"></option>
                        <option value="Follow-up"></option>
                    </datalist>
                </div>
            </div>
        </div>
        <div class="card mt-3 radiology-editor-card">
            <div class="card-header bg-white">
                <h4>Findings Template</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <textarea id="HTMLData" name="HTMLData" placeholder="Place some text here"><?= esc($HTMLData) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-3 radiology-editor-card">
            <div class="card-header bg-white">
                <h4>Impression Template</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <textarea id="Impression" name="Impression" class="form-control" placeholder="Place some text here"><?= esc($Impression) ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        <hr/>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Template Action</label>
                    <button id="updatereport" type="button" class="btn btn-primary">Update</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>
<script>
    (function() {
        if (typeof CKEDITOR === 'undefined') {
            return;
        }

        function safeReplace(editorId, config) {
            if (!document.getElementById(editorId)) {
                return;
            }

            if (CKEDITOR.instances && CKEDITOR.instances[editorId]) {
                try {
                    CKEDITOR.instances[editorId].destroy(true);
                } catch (e) {
                    console.warn('CKEditor destroy failed for', editorId, e);
                }
            }

            CKEDITOR.replace(editorId, config || {});
        }

        safeReplace('HTMLData');
        safeReplace('Impression', {
            toolbar: [
                ['Bold', 'Italic', 'Underline', '-', 'FontSize', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight'],
                ['NumberedList', 'BulletedList', '-', 'Link', 'Unlink', '-', 'Source']
            ]
        });
    })();

    $('#updatereport').click(function() {
        var repo_id = $('#repo_id').val();
        var input_Reportname = $('#input_Reportname').val();
        var charge_id = $('#charge_id').val();
        var group_id = $('#input_Reporttitle').val();
        var keywords = $('#keywords').val();
        var impression_cat = $('#impression_cat').val();
        var HTMLData = (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.HTMLData) ? CKEDITOR.instances.HTMLData.getData() : $('#HTMLData').val();
        var Impression = (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.Impression) ? CKEDITOR.instances.Impression.getData() : $('#Impression').val();
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
        var modality = <?= (int) ($modality ?? 2) ?>;

        if (repo_id > 0) {
            $.post('<?= base_url('Lab_Admin/report_ultrasound_update') ?>/' + modality, {
                "repo_id": repo_id,
                "input_Reportname": input_Reportname,
                "charge_id": charge_id,
                "group_id": group_id,
                "keywords": keywords,
                "impression_cat": impression_cat,
                "HTMLData": HTMLData,
                "Impression": Impression,
                "<?= csrf_token() ?>": csrf_value
            }, function(data) {
                if (data && Number(data.update_record || 0) === 1 && typeof window.refreshRadiologyTemplateList === 'function') {
                    window.refreshRadiologyTemplateList();
                    return;
                }

                if (typeof notify === 'function') {
                    notify('success', 'Saved', data.showcontent || 'Saved');
                }
            }, 'json');
        } else {
            $.post('<?= base_url('Lab_Admin/report_ultrasound_insert') ?>/' + modality, {
                "repo_id": repo_id,
                "input_Reportname": input_Reportname,
                "charge_id": charge_id,
                "group_id": group_id,
                "keywords": keywords,
                "impression_cat": impression_cat,
                "HTMLData": HTMLData,
                "Impression": Impression,
                "<?= csrf_token() ?>": csrf_value
            }, function(data) {
                if (data.insertid > 0) {
                    if (typeof load_form === 'function') {
                        load_form('<?= base_url('Lab_Admin/report_ultrasound_list') ?>/' + modality, 'Diagnosis Template');
                    } else {
                        window.location.href = '<?= base_url('Lab_Admin/report_ultrasound_list') ?>/' + modality;
                    }
                }
            }, 'json');
        }
    });
</script>

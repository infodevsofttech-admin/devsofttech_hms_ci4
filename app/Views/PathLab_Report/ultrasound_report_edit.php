<?= form_open() ?>
<div class="card admin-card">
    <div class="card-header bg-white">
        <h3 class="mb-0">Radiology Report Edit</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Report Name</label>
                    <?php
                    $repo_name = '';
                    $repo_id = '0';
                    $HTMLData = '';
                    $repo_title = '';
                    $Impression = '';
                    if (count($labReport_master ?? []) > 0) {
                        $repo_id = $labReport_master[0]->id ?? '0';
                        $repo_name = $labReport_master[0]->template_name ?? '';
                        $repo_title = $labReport_master[0]->title ?? '';
                        $HTMLData = $labReport_master[0]->Findings ?? '';
                        $Impression = $labReport_master[0]->Impression ?? '';
                    }
                    ?>
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
        </div>
        <hr/>
        <div class="row">
            <div class="col-md-12">
                <label>Findings</label>
                <textarea id="HTMLData" name="HTMLData" placeholder="Place some text here"><?= esc($HTMLData) ?></textarea>
                <script>
                    if (typeof CKEDITOR !== 'undefined') {
                        CKEDITOR.replace('HTMLData');
                    }
                </script>
            </div>
        </div>
        <hr/>
        <div class="row">
            <div class="col-md-12">
                <label>Impression</label>
                <textarea id="Impression" name="Impression" class="form-control" placeholder="Place some text here"><?= esc($Impression) ?></textarea>
                <script>
                    if (typeof CKEDITOR !== 'undefined') {
                        CKEDITOR.replace('Impression', {
                            toolbar: [
                                ['Bold', 'Italic', 'Underline', '-', 'FontSize', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight'],
                                ['NumberedList', 'BulletedList', '-', 'Link', 'Unlink', '-', 'Source']
                            ]
                        });
                    }
                </script>
            </div>
        </div>
        <hr/>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Press Here to Save Data</label>
                    <button id="updatereport" type="button" class="btn btn-primary">Update</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>
<script>
    $('#updatereport').click(function() {
        var repo_id = $('#repo_id').val();
        var input_Reportname = $('#input_Reportname').val();
        var charge_id = $('#charge_id').val();
        var group_id = $('#input_Reporttitle').val();
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
                "HTMLData": HTMLData,
                "Impression": Impression,
                "<?= csrf_token() ?>": csrf_value
            }, function(data) {
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
                "HTMLData": HTMLData,
                "Impression": Impression,
                "<?= csrf_token() ?>": csrf_value
            }, function(data) {
                if (data.insertid > 0) {
                    load_form_div('<?= base_url('Lab_Admin/report_ultrasound_list') ?>/' + modality, 'maindiv', 'Diagnosis Template');
                }
            }, 'json');
        }
    });
</script>

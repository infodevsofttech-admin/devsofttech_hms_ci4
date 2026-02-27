<?= form_open() ?>
<div class="card admin-card mb-3">
    <div class="card-header bg-white">
        <h3 class="mb-0">Report Edit</h3>
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
                    if (count($labReport_master ?? []) > 0) {
                        $repo_name = $labReport_master[0]->Title ?? '';
                        $repo_id = $labReport_master[0]->mstRepoKey ?? '0';
                        $HTMLData = $labReport_master[0]->HTMLData ?? '';
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
                    <label>Group</label>
                    <select class="form-select" id="group_id" name="group_id">
                        <?php
                        $sel_value = 0;
                        if (count($labReport_master ?? []) > 0) {
                            $sel_value = (int) ($labReport_master[0]->GrpKey ?? 0);
                        }
                        foreach (($lab_rgroups ?? []) as $row) {
                            echo '<option value="' . esc($row->mstRGrpKey ?? 0) . '" ' . combo_checked($row->mstRGrpKey ?? 0, $sel_value) . '>' . esc($row->RepoGrp ?? '') . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <textarea id="HTMLData" name="HTMLData" placeholder="Place some text here"><?= esc($HTMLData) ?></textarea>
                <script>
                    if (typeof CKEDITOR !== 'undefined') {
                        CKEDITOR.replace('HTMLData');
                    }
                </script>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-6">
                <button id="updatereport" type="button" class="btn btn-primary">Update</button>
            </div>
        </div>
    </div>
</div>

<div class="card admin-card">
    <div class="card-header bg-white">
        <h3 class="mb-0">Report Parameter</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <?php for ($i = 0; $i < count($lab_Rep_Item_List ?? []); ++$i) {
                    $color = $color_name[($lab_Rep_Item_List[$i]->id ?? 0) % max(1, count($color_name ?? []))]->code_code_2 ?? '#0f172a';
                    echo '<div style="margin:2px;display:inline-block;color:' . esc($color) . ';"><i>' . esc($lab_Rep_Item_List[$i]->Test ?? '') . '</i>[' . esc($lab_Rep_Item_List[$i]->TestID ?? '') . ']</div>';
                } ?>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <button onclick="load_form_div('<?= base_url('Lab_Admin/report_test_list') ?>/<?= esc($repo_id ?? 0) ?>','test_div');" type="button" class="btn btn-outline-primary">Test List</button>
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
        var group_id = $('#group_id').val();
        var HTMLData = (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.HTMLData) ? CKEDITOR.instances.HTMLData.getData() : $('#HTMLData').val();
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

        if (repo_id > 0) {
            $.post('<?= base_url('Lab_Admin/report_update') ?>', {
                "repo_id": repo_id,
                "input_Reportname": input_Reportname,
                "charge_id": charge_id,
                "group_id": group_id,
                "HTMLData": HTMLData,
                "<?= csrf_token() ?>": csrf_value
            }, function(data) {
                if (data.showcontent && typeof notify === 'function') {
                    notify('success', 'Saved', data.showcontent);
                }
            }, 'json');
        } else {
            $.post('<?= base_url('Lab_Admin/report_insert') ?>', {
                "repo_id": repo_id,
                "input_Reportname": input_Reportname,
                "charge_id": charge_id,
                "group_id": group_id,
                "HTMLData": HTMLData,
                "<?= csrf_token() ?>": csrf_value
            }, function(data) {
                if (data.insertid > 0) {
                    load_form('<?= base_url('Lab_Admin/report_list') ?>');
                }
            }, 'json');
        }
    });
</script>

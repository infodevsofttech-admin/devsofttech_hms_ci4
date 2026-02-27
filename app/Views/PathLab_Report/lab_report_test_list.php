<?= form_open() ?>
<div class="card admin-card">
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
        <h3 class="mb-0">Test List</h3>
        <button onclick="load_form_div('<?= base_url('Lab_Admin/test_search_page') ?>/<?= esc($mstRepoKey ?? 0) ?>','test_div');" type="button" class="btn btn-primary btn-sm">Add New Test</button>
    </div>
    <div class="card-body">
        <table id="example2" class="table table-striped table-hover align-middle TableData">
            <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Test Name</th>
                <th>Test Code</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php for ($i = 0; $i < count($lab_Rep_Item_List ?? []); ++$i) { ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= esc($lab_Rep_Item_List[$i]->Test ?? '') ?></td>
                    <td><?= esc($lab_Rep_Item_List[$i]->TestID ?? '') ?></td>
                    <td>
                        <div class="btn-group-horizontal">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="load_form_div('<?= base_url('Lab_Admin/test_parameter_load') ?>/<?= esc($lab_Rep_Item_List[$i]->mstTestKey ?? 0) ?>/<?= esc($mstRepoKey ?? 0) ?>','test_div');">
                                Edit
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="remove_item('<?= esc($mstRepoKey ?? 0) ?>','<?= esc($lab_Rep_Item_List[$i]->mstTestKey ?? 0) ?>');">
                                Remove
                            </button>
                            <?php
                            $option_current = $lab_Rep_Item_List[$i]->id ?? 0;
                            $sort_current = $lab_Rep_Item_List[$i]->EOrder ?? 0;
                            if ($i + 1 < count($lab_Rep_Item_List ?? [])) {
                                $option_next = $lab_Rep_Item_List[$i + 1]->id ?? 0;
                                $sort_next = $lab_Rep_Item_List[$i + 1]->EOrder ?? 0;
                                echo '<button type="button" class="btn btn-outline-primary btn-sm" onclick="sortchange(' . (int) ($mstRepoKey ?? 0) . ',' . (int) $option_current . ',' . (int) $sort_current . ',' . (int) $option_next . ',' . (int) $sort_next . ')">Down</button>';
                            }
                            if ($i > 0) {
                                $option_prev = $lab_Rep_Item_List[$i - 1]->id ?? 0;
                                $sort_prev = $lab_Rep_Item_List[$i - 1]->EOrder ?? 0;
                                echo '<button type="button" class="btn btn-outline-primary btn-sm" onclick="sortchange(' . (int) ($mstRepoKey ?? 0) . ',' . (int) $option_current . ',' . (int) $sort_current . ',' . (int) $option_prev . ',' . (int) $sort_prev . ')">Up</button>';
                            }
                            ?>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?= form_close() ?>
<script>
    function sortchange(mstRepoKey, option_current, sort_current, option_prev, sort_prev) {
        var postStr = '<?= base_url('Lab_Admin/change_sort_item') ?>/' + mstRepoKey + '/' + option_current + '/' + sort_current + '/' + option_prev + '/' + sort_prev;
        var csrfValue = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
        $.post(postStr, {
            "mstRepoKey": mstRepoKey,
            "<?= csrf_token() ?>": csrfValue
        }, function(data) {
            $('#test_div').html(data);
        });
    }

    function remove_item(mstRepoKey, mstTestKey) {
        var postStr = '<?= base_url('Lab_Admin/remove_test_item') ?>/' + mstRepoKey + '/' + mstTestKey;
        var csrfValue = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
        $.post(postStr, {
            "mstRepoKey": mstRepoKey,
            "<?= csrf_token() ?>": csrfValue
        }, function() {
            load_form_div('<?= base_url('Lab_Admin/report_test_list') ?>/' + mstRepoKey, 'test_div');
        });
    }

    if (window.jQuery && $.fn && $.fn.DataTable) {
        $('#example2').DataTable();
    }
</script>

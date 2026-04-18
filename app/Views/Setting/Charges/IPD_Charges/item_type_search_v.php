<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">IPD Charges Groups</h3>
        <div class="card-tools ms-auto">
            <button onclick="load_form_div('<?= base_url('item-ipd/add-type') ?>','maindiv','IPD Charge Master');" type="button" class="btn btn-primary">Add New</button>
            <button onclick="load_form_div('<?= base_url('item-ipd/search') ?>','maindiv','IPD Charge Master');" type="button" class="btn btn-light">Charges List</button>
        </div>
    </div>
    <div class="card-body">
        <input type="hidden" id="csrf_name" value="<?= csrf_token() ?>">
        <input type="hidden" id="csrf_val"  value="<?= csrf_hash() ?>">
        <table id="example1" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Charges Group Name</th>
                    <th style="width: 120px;">Sort Order</th>
                    <th style="width: 280px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rows = is_array($data) ? array_values($data) : [];
                foreach ($rows as $index => $row) :
                    $currentId = (int) ($row->itype_id ?? 0);
                    $currentSort = (int) ($row->sort_order ?? 0);
                    $prevRow = $rows[$index - 1] ?? null;
                    $nextRow = $rows[$index + 1] ?? null;
                ?>
                    <tr>
                        <td><?= esc($row->group_desc ?? '') ?></td>
                        <td><?= esc((string) ((int) ($row->sort_order ?? 0))) ?></td>
                        <td>
                            <button onclick="load_form_div('<?= base_url('item-ipd/itemtype-record') ?>/' + <?= (int) ($row->itype_id ?? 0) ?>, 'maindiv', 'IPD Charge Master');" type="button" class="btn btn-primary btn-sm">Edit</button>
                            <?php if ((int)($row->item_count ?? 0) === 0): ?>
                            <button onclick="deleteItemTypeGroup(<?= (int)($row->itype_id ?? 0) ?>, this);" type="button" class="btn btn-danger btn-sm ms-1">Delete</button>
                            <?php endif ?>
                            <?php if (($supportsSortOrder ?? false) && $nextRow !== null): ?>
                            <button onclick="moveItemTypeGroup(<?= $currentId ?>, <?= $currentSort ?>, <?= (int) ($nextRow->itype_id ?? 0) ?>, <?= (int) ($nextRow->sort_order ?? 0) ?>, this);" type="button" class="btn btn-outline-primary btn-sm ms-1">Down</button>
                            <?php endif ?>
                            <?php if (($supportsSortOrder ?? false) && $prevRow !== null): ?>
                            <button onclick="moveItemTypeGroup(<?= $currentId ?>, <?= $currentSort ?>, <?= (int) ($prevRow->itype_id ?? 0) ?>, <?= (int) ($prevRow->sort_order ?? 0) ?>, this);" type="button" class="btn btn-outline-primary btn-sm ms-1">Up</button>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Charges Group Name</th>
                    <th>Sort Order</th>
                    <th>Action</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
    (function() {
        var table = document.getElementById('example1');
        if (!table || !window.simpleDatatables || !window.simpleDatatables.DataTable) {
            return;
        }

        new simpleDatatables.DataTable(table);
    })();

    function deleteItemTypeGroup(itypeId, btn) {
        if (!confirm('Delete this empty charges group? This cannot be undone.')) {
            return;
        }
        btn.disabled = true;
        var csrfName = $('#csrf_name').val();
        var csrfVal  = $('#csrf_val').val();
        var payload  = {itype_id: itypeId};
        payload[csrfName] = csrfVal;

        $.post('<?= base_url('item-ipd/delete-type') ?>', payload, function(res) {
            if (res.deleted) {
                $(btn).closest('tr').remove();
            } else {
                alert(res.error_text || 'Could not delete group.');
                btn.disabled = false;
            }
            if (res.csrfName && res.csrfHash) {
                $('#csrf_name').val(res.csrfName);
                $('#csrf_val').val(res.csrfHash);
            }
        }, 'json').fail(function() {
            alert('Request failed.');
            btn.disabled = false;
        });
    }

    function moveItemTypeGroup(currentId, currentSort, targetId, targetSort, btn) {
        btn.disabled = true;

        var csrfName = $('#csrf_name').val();
        var csrfVal = $('#csrf_val').val();
        var payload = {
            current_id: currentId,
            current_sort: currentSort,
            target_id: targetId,
            target_sort: targetSort
        };

        payload[csrfName] = csrfVal;

        $.post('<?= base_url('item-ipd/change-sort-type') ?>', payload, function(res) {
            if (res.csrfName && res.csrfHash) {
                $('#csrf_name').val(res.csrfName);
                $('#csrf_val').val(res.csrfHash);
            }

            if (!res.moved) {
                alert(res.error_text || 'Could not update sort order.');
                btn.disabled = false;
                return;
            }

            if (typeof load_form_div === 'function') {
                load_form_div('<?= base_url('item-ipd/search-itemtype') ?>', 'maindiv', 'IPD Charge Master');
            } else {
                window.location.reload();
            }
        }, 'json').fail(function() {
            alert('Request failed.');
            btn.disabled = false;
        });
    }
</script>

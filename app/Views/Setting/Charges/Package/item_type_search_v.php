<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Package Groups</h3>
        <div class="card-tools ms-auto">
            <button onclick="load_form_div('<?= base_url('package/add-type') ?>','maindiv','Package Groups');" type="button" class="btn btn-primary">Add New</button>
            <button onclick="load_form_div('<?= base_url('package/search') ?>','maindiv','IPD Package');" type="button" class="btn btn-light">Package List</button>
        </div>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Package Group Name</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row) : ?>
                    <tr>
                        <td><?= esc($row->pakage_group_name ?? '') ?></td>
                        <td>
                            <button onclick="load_form_div('<?= base_url('package/itemtype-record') ?>/' + <?= (int) ($row->pak_id ?? 0) ?>, 'maindiv', 'Package Groups');" type="button" class="btn btn-primary btn-sm">Edit</button>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Package Group Name</th>
                    <th></th>
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
</script>

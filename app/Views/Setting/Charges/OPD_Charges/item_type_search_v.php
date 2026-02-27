<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">OPD Charges Groups</h3>
        <div class="card-tools ms-auto">
            <button onclick="load_form_div('<?= base_url('item/add-type') ?>','maindiv','OPD Charge Master');" type="button" class="btn btn-primary">Add New</button>
            <button onclick="load_form_div('<?= base_url('item/search') ?>','maindiv','OPD Charge Master');" type="button" class="btn btn-light">Charges List</button>
        </div>
    </div>
    <div class="card-body">
        <table id="example1" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>OPD or IPD</th>
                    <th>Charges Group Name</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row) : ?>
                    <tr>
                        <td><?= esc($row->isIPD_OPD ?? '') ?></td>
                        <td><?= esc($row->group_desc ?? '') ?></td>
                        <td>
                            <button onclick="load_form_div('<?= base_url('item/itemtype-record') ?>/' + <?= (int) ($row->itype_id ?? 0) ?>, 'maindiv', 'OPD Charge Master');" type="button" class="btn btn-primary btn-sm">Edit</button>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>OPD or IPD</th>
                    <th>Charges Group Name</th>
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

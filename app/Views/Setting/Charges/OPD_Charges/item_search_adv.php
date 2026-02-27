<table class="table table-bordered table-striped" id="datashow1">
    <thead>
        <tr>
            <th>OPD or IPD</th>
            <th>Group</th>
            <th>Charge Name</th>
            <th>Amount</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row) : ?>
            <tr>
                <td><?= esc($row->isIPD_OPD ?? '') ?></td>
                <td><?= esc($row->group_desc ?? '') ?></td>
                <td><?= esc($row->idesc ?? '') ?></td>
                <td><?= esc($row->amount ?? '') ?></td>
                <td>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#tallModal_item" data-testid="<?= esc($row->id ?? '') ?>" data-testname="<?= esc($row->idesc ?? '') ?>">
                        Edit
                    </button>
                </td>
            </tr>
        <?php endforeach ?>
    </tbody>
    <tfoot>
        <tr>
            <th>OPD or IPD</th>
            <th>Group</th>
            <th>Charge Name</th>
            <th>Amount</th>
            <th></th>
        </tr>
    </tfoot>
</table>

<script>
    (function() {
        var table = document.getElementById('datashow1');
        if (!table || !window.simpleDatatables || !window.simpleDatatables.DataTable) {
            return;
        }

        new simpleDatatables.DataTable(table);
    })();
</script>

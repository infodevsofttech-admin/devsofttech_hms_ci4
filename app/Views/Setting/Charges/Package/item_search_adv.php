<table class="table table-bordered table-striped" id="datashow1">
    <thead>
        <tr>
            <th>Package Name</th>
            <th>Charge Name</th>
            <th>Amount</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row) : ?>
            <tr>
                <td><?= esc($row->ipd_pakage_name ?? '') ?></td>
                <td><?= esc($row->Pakage_description ?? '') ?></td>
                <td><?= esc($row->Pakage_Min_Amount ?? '') ?></td>
                <td>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#tallModal_item" data-testid="<?= esc($row->id ?? '') ?>" data-testname="<?= esc($row->ipd_pakage_name ?? '') ?>">
                        Edit
                    </button>
                </td>
            </tr>
        <?php endforeach ?>
    </tbody>
    <tfoot>
        <tr>
            <th>Package Name</th>
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

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Company Name</th>
            <th>Amount</th>
            <th>Code</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (! empty($data_insurance_item)) : ?>
            <?php foreach ($data_insurance_item as $row) : ?>
                <tr>
                    <td><?= esc($row->ins_company_name ?? '') ?></td>
                    <td><?= esc($row->i_amount ?? '') ?></td>
                    <td><?= esc($row->code ?? '') ?></td>
                    <td>
                        <button onclick="remove_item_spec('<?= esc($row->i_item_id ?? '') ?>')" type="button" class="btn btn-primary btn-sm">Delete</button>
                    </td>
                </tr>
            <?php endforeach ?>
        <?php endif ?>
    </tbody>
</table>

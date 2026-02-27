<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>#</th>
                <th>Invoice Code</th>
                <th>Date</th>
                <th>Charge Type</th>
                <th class="text-end">Amount</th>
                <th>Include</th>
            </tr>
        </thead>
        <tbody>
            <?php if (! empty($diagnosis_charges)) : ?>
                <?php $srNo = 1; ?>
                <?php foreach ($diagnosis_charges as $row) : ?>
                    <tr>
                        <td><?= $srNo++ ?></td>
                        <td><?= esc($row->invoice_code ?? $row->inv_id ?? '') ?></td>
                        <td><?= esc($row->str_date ?? '') ?></td>
                        <td><?= esc($row->charge_list ?? '') ?></td>
                        <td class="text-end"><?= esc($row->amount ?? '') ?></td>
                        <td><?= ((int) ($row->ipd_include ?? 0) > 0) ? 'Yes' : 'No' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">No diagnosis charges found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

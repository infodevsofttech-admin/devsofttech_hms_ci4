<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>#</th>
                <th>Invoice Code</th>
                <th>Date</th>
                <th class="text-end">Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (! empty($medical_bills)) : ?>
                <?php $srNo = 1; ?>
                <?php foreach ($medical_bills as $row) : ?>
                    <tr>
                        <td><?= $srNo++ ?></td>
                        <td><?= esc($row->inv_med_code ?? $row->id ?? '') ?></td>
                        <td><?= esc($row->inv_date ?? '') ?></td>
                        <td class="text-end"><?= esc($row->net_amount ?? '') ?></td>
                        <td><?= ((int) ($row->payment_status ?? 0) > 0) ? 'Paid' : 'Pending' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">No medical bills found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

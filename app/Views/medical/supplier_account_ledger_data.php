<div class="alert alert-light border mb-2">
    Opening Balance: <strong><?= esc(number_format((float) ($balance_till_date ?? 0), 2)) ?></strong>
    | Total Credit: <strong><?= esc(number_format((float) ($cr_total ?? 0), 2)) ?></strong>
    | Total Debit: <strong><?= esc(number_format((float) ($dr_total ?? 0), 2)) ?></strong>
    | Closing Balance: <strong><?= esc(number_format((float) ($balance_till_date_close ?? 0), 2)) ?></strong>
</div>

<div class="table-responsive">
    <table class="table table-striped table-bordered table-sm align-middle">
        <thead>
        <tr>
            <th>ID</th>
            <th>Date Tran</th>
            <th>Description</th>
            <th class="text-end">Credit</th>
            <th class="text-end">Debit</th>
            <th class="text-center">Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php $i = 0; foreach (($med_supplier_ledger ?? []) as $row): $i++; ?>
            <tr>
                <td><?= esc((string) $i) ?></td>
                <td><?= esc(!empty($row->tran_date) ? date('d-m-Y', strtotime((string) $row->tran_date)) : '') ?></td>
                <td><?= esc(trim((string) (($row->mode_desc ?? '') . ' ' . ($row->tran_desc ?? '')))) ?></td>
                <td class="text-end"><?= (int) ($row->credit_debit ?? 0) === 0 ? esc(number_format((float) ($row->amount ?? 0), 2)) : '' ?></td>
                <td class="text-end"><?= (int) ($row->credit_debit ?? 0) === 1 ? esc(number_format((float) ($row->amount ?? 0), 2)) : '' ?></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-primary btn-edit-entry" data-tran-id="<?= esc((string) ((int) ($row->id ?? 0))) ?>">Edit Entry</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

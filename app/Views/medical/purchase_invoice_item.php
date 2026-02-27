<?php $invoice = $purchase_invoice[0] ?? null; ?>

<table class="table table-striped table-sm align-middle">
    <thead>
    <tr>
        <th>#</th>
        <th>RecNo</th>
        <th>Item Name</th>
        <th>Batch No</th>
        <th>Exp.</th>
        <th>MRP</th>
        <th>Qty.</th>
        <th>Rate</th>
        <th>Amount</th>
        <th>Disc.</th>
        <th>Tax Amount</th>
        <th>CGST</th>
        <th>SGST</th>
        <th>Net Amount</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php $srno = 0; foreach (($purchase_item ?? []) as $row): $srno++; ?>
        <tr <?= ((int) ($row->item_return ?? 0) === 1) ? 'style="color:red;"' : '' ?>>
            <td><?= $srno ?></td>
            <td><?= (int) ($row->id ?? 0) ?></td>
            <td><?= esc($row->Item_name ?? '') ?></td>
            <td><?= esc($row->batch_no ?? '') ?></td>
            <td><?= esc($row->exp_date_str ?? '') ?></td>
            <td><?= esc((string) ($row->mrp ?? 0)) ?></td>
            <td><?= esc((string) ($row->qty ?? 0)) ?> + <?= esc((string) ($row->qty_free ?? 0)) ?></td>
            <td><?= esc((string) ($row->purchase_price ?? 0)) ?></td>
            <td><?= esc((string) ($row->amount ?? 0)) ?></td>
            <td><?= esc((string) ($row->discount ?? 0)) ?></td>
            <td><?= esc((string) ($row->taxable_amount ?? 0)) ?></td>
            <td><?= esc((string) ($row->CGST_per ?? 0)) ?></td>
            <td><?= esc((string) ($row->SGST_per ?? 0)) ?></td>
            <td><?= esc((string) ($row->net_amount ?? 0)) ?></td>
            <td>
                <?php if ($invoice && (int) ($invoice->inv_status ?? 0) === 0): ?>
                    <button type="button" class="btn btn-primary btn-sm" onclick="edit_item_invoice(<?= (int) ($row->id ?? 0) ?>)"><i class="fa fa-edit"></i></button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="remove_item_invoice(<?= (int) ($row->id ?? 0) ?>)"><i class="fa fa-remove"></i></button>
                    <?php if ((int) ($row->old_purchase_id ?? 0) > 0): ?>
                        <button type="button" class="btn btn-warning btn-sm" onclick="return_to_challan(<?= (int) ($row->id ?? 0) ?>)"><i class="fa fa-undo"></i></button>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if ($invoice): ?>
        <tr>
            <th colspan="10" class="text-end">Total</th>
            <th><?= esc((string) ($invoice->Taxable_Amt ?? 0)) ?></th>
            <th><?= esc((string) ($invoice->CGST_Amt ?? 0)) ?></th>
            <th><?= esc((string) ($invoice->SGST_Amt ?? 0)) ?></th>
            <th><?= esc((string) ($invoice->T_Net_Amount ?? 0)) ?></th>
            <th></th>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

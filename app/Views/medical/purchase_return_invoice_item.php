<?php $rows = $purchase_return_invoice_item ?? []; ?>

<div class="table-responsive">
    <table class="table table-striped table-sm align-middle mb-0">
        <thead>
        <tr>
            <th>#</th>
            <th>RecNo</th>
            <th>Item Name</th>
            <th>Batch No</th>
            <th>Exp.</th>
            <th>MRP / P. Rate</th>
            <th>Qty (Unit / Pack)</th>
            <th>GST %</th>
            <th>Amount</th>
            <th>GST Amount</th>
            <th>Net Amount</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php $srno = 0; ?>
        <?php foreach ($rows as $row): ?>
            <?php $srno++; ?>
            <?php
                $gstPer = (float) ($row->gst_per ?? 0);
                $amount = (float) ($row->r_amount ?? 0);
                $gstAmount = round(($gstPer * $amount) / 100, 2);
                $netAmount = round($amount + $gstAmount, 2);
            ?>
            <tr>
                <td><?= $srno ?></td>
                <td><?= (int) ($row->r_id ?? 0) ?></td>
                <td><?= esc((string) ($row->Item_name ?? '')) ?></td>
                <td><?= esc((string) ($row->batch_no_r_s ?? '')) ?></td>
                <td><?= esc((string) ($row->exp_date_str ?? '')) ?></td>
                <td><?= esc((string) ($row->mrp ?? 0)) ?> / <?= esc((string) ($row->purchase_unit_rate ?? 0)) ?></td>
                <td><?= esc((string) (floatval($row->r_qty ?? 0))) ?> / <?= esc((string) (floatval($row->qty_pak ?? 0))) ?></td>
                <td><?= esc((string) $gstPer) ?></td>
                <td><?= esc((string) $amount) ?></td>
                <td><?= esc((string) $gstAmount) ?></td>
                <td><?= esc((string) $netAmount) ?></td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="remove_item_invoice(<?= (int) ($row->r_id ?? 0) ?>)">
                        <i class="fa fa-remove"></i>
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($srno === 0): ?>
            <tr>
                <td colspan="12" class="text-muted text-center">No return items added yet.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

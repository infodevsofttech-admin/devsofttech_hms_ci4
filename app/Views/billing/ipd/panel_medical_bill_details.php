<?php
/** @var object $invoice */
/** @var array<int, object> $items */
$invoiceCode = (string) ($invoice->inv_med_code ?? $invoice->id ?? '');
?>

<div class="card mb-0">
    <div class="card-header d-flex align-items-center justify-content-between">
        <strong>Invoice ID: <?= esc($invoiceCode) ?></strong>
        <a href="<?= site_url('Medical/invoice_print/' . (int) $invoice->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer"></i> Print
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0 align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Name</th>
                        <th class="text-end">Qty.</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($items)) : ?>
                        <?php foreach ($items as $index => $item) : ?>
                            <?php
                            $itemName = trim((string) ($item->item_Name ?? $item->item_name ?? ''));
                            $formulation = trim((string) ($item->formulation ?? ''));
                            ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= esc($itemName . ($formulation !== '' ? ' [' . $formulation . ']' : '')) ?></td>
                                <td class="text-end"><?= esc($item->qty ?? '') ?></td>
                                <td class="text-end"><?= number_format((float) ($item->price ?? 0), 2) ?></td>
                                <td class="text-end"><?= number_format((float) ($item->tamount ?? 0), 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No invoice items found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Total</th>
                        <th class="text-end"><?= number_format((float) ($invoice->net_amount ?? 0), 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table id="Invoice_history_log" class="table table-striped table-bordered table-sm align-middle">
        <thead>
        <tr>
            <th>#</th>
            <th>Invoice Code</th>
            <th>Invoice Date</th>
            <th>Name</th>
            <th>Item Deleted</th>
            <th>Payment Log</th>
            <th>Update Log</th>
        </tr>
        </thead>
        <tbody>
        <?php $i = 1; foreach (($Invoice_history_log ?? []) as $row): ?>
            <tr>
                <td><?= esc((string) $i++) ?></td>
                <td><?= esc($row->inv_med_code ?? '') ?></td>
                <td><?= esc($row->inv_date ?? '') ?></td>
                <td><?= esc($row->inv_name ?? '') ?></td>
                <td>
                    <?php
                    $itemRemovedLog = array_filter(explode(';', (string) ($row->del_item_list ?? '')));
                    $itemAmountTotal = 0.0;
                    ?>
                    <?php if (!empty($itemRemovedLog)): ?>
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                            <tr>
                                <th>Item Name</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Amount</th>
                                <th>Delete By</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($itemRemovedLog as $rowItem):
                                $item = explode('#', $rowItem);
                                $amount = (float) ($item[3] ?? 0);
                                $itemAmountTotal += $amount;
                            ?>
                                <tr>
                                    <td><?= esc($item[0] ?? '') ?></td>
                                    <td class="text-end"><?= esc(number_format((float) ($item[1] ?? 0), 2)) ?></td>
                                    <td class="text-end"><?= esc(number_format((float) ($item[2] ?? 0), 2)) ?></td>
                                    <td class="text-end"><?= esc(number_format($amount, 2)) ?></td>
                                    <td><?= esc($item[5] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total Amount</strong></td>
                                <td class="text-end"><strong><?= esc(number_format($itemAmountTotal, 2)) ?></strong></td>
                                <td></td>
                            </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        No Item Deleted
                    <?php endif; ?>
                </td>
                <td>
                    <?php $paymentLogList = array_filter(explode(';', (string) ($row->payment_log ?? ''))); ?>
                    <?php if (!empty($paymentLogList)): ?>
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                            <tr>
                                <th>Payment Type</th>
                                <th class="text-end">Amount</th>
                                <th>Payment By</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($paymentLogList as $rowItem):
                                $item = explode('#', $rowItem);
                                $payType = ((string) ($item[0] ?? '') === '0') ? 'Credit' : (((string) ($item[0] ?? '') === '1') ? 'Return' : '-');
                            ?>
                                <tr>
                                    <td><?= esc($payType) ?></td>
                                    <td class="text-end"><?= esc(number_format((float) ($item[1] ?? 0), 2)) ?></td>
                                    <td><?= esc($item[2] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        No Payment Log
                    <?php endif; ?>
                </td>
                <td><pre class="mb-0"><?= esc((string) ($row->log ?? '')) ?></pre></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
(function () {
    if (window.jQuery && typeof window.jQuery.fn.DataTable === 'function') {
        window.jQuery('#Invoice_history_log').DataTable({
            responsive: true,
            order: [[0, 'desc']],
            bDestroy: true,
            bPaginate: true,
            bLengthChange: false,
            bFilter: true,
            bInfo: true,
            bAutoWidth: false
        });
    }
})();
</script>

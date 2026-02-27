<?php
$rows = $rows ?? [];
$dateFrom = (string) ($dateFrom ?? '');
$dateTo = (string) ($dateTo ?? '');
?>

<div class="alert alert-light border">
    <strong>Date Range:</strong> <?= esc($dateFrom) ?> to <?= esc($dateTo) ?>
    <span class="ms-3"><strong>Total Logs:</strong> <?= esc((string) count($rows)) ?></span>
</div>

<?php if ($rows === []): ?>
    <div class="alert alert-warning mb-0">No payment logs found for selected date range.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover" id="paymentmedical_history_log">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Bill No</th>
                    <th>Invoice Code</th>
                    <th>Bill Type</th>
                    <th>Insert Date Time</th>
                    <th>Log Insert</th>
                    <th>Update Log</th>
                    <th>Amount / New Amount</th>
                    <th>Log Type</th>
                    <th>Update By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td><?= esc((string) ($index + 1)) ?></td>
                        <td><?= esc((string) ($row['id'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['invoice_code'] ?? ($row['Inv_code'] ?? '-'))) ?></td>
                        <td><?= esc((string) ($row['bill_type'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['insert_time'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['log_insert'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['update_log'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['amount'] ?? '0')) ?></td>
                        <td><?= esc((string) ($row['log_type'] ?? '-')) ?></td>
                        <td><?= esc((string) ($row['update_by'] ?? '-')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    (function () {
        if (window.jQuery && typeof window.jQuery.fn.DataTable === 'function') {
            window.jQuery('#paymentmedical_history_log').DataTable({
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
<?php endif; ?>

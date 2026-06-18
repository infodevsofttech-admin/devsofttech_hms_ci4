<?php $rows = $purchase_list ?? []; ?>
<div class="table-responsive">
    <table id="purchase-report-list" class="table table-bordered table-striped table-sm align-middle">
        <thead>
        <tr>
            <th>Invoice ID</th>
            <th>Supplier</th>
            <th>Date</th>
            <th>Amount</th>
            <th style="width:220px;">Action</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr>
                <td class="text-center text-muted">No purchase invoices found.</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <tr class="<?= ((int) ($row->ischallan ?? 0)) === 1 ? 'text-danger' : '' ?>">
                <td><?= esc((string) ($row->Invoice_no ?? '')) ?></td>
                <td><?= esc((string) ($row->name_supplier ?? '')) ?></td>
                <td><?= esc((string) ($row->str_date_of_invoice ?? $row->date_of_invoice ?? '')) ?></td>
                <td><?= esc((string) number_format((float) ($row->tamount ?? 0), 2)) ?></td>
                <td>
                    <button type="button" class="btn btn-warning btn-sm" onclick="openStorestockPurchaseSubView('<?= base_url('Storestock/PurchaseMasterEdit/' . (int) ($row->id ?? 0)) ?>','Purchase : Edit');">View &amp; Edit</button>
                    <a href="<?= base_url('Storestock/print_purchase/' . (int) ($row->id ?? 0)) ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fa fa-print"></i> Print</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
(function () {
    if (window.jQuery && $.fn.DataTable) {
        if ($.fn.DataTable.isDataTable('#purchase-report-list')) {
            $('#purchase-report-list').DataTable().destroy();
        }
        $('#purchase-report-list').DataTable({
            pageLength: 25,
            order: [[0, 'desc']]
        });
    }
})();
</script>
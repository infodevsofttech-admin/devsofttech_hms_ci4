<div class="table-responsive">
    <table id="purchase_return_report_list" class="table table-bordered table-striped table-sm align-middle">
        <thead>
        <tr>
            <th>Invoice ID</th>
            <th>Supplier</th>
            <th>Date</th>
            <th style="width:220px;">Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (($purchase_return_invoice ?? []) as $row): ?>
            <tr>
                <td><?= esc((string) ($row->p_r_invoice_no ?? '')) ?></td>
                <td><?= esc((string) ($row->name_supplier ?? '')) ?></td>
                <td><?= esc((string) ($row->str_date_of_invoice ?? $row->date_of_invoice ?? '')) ?></td>
                <td>
                    <button onclick="load_form_div('<?= base_url('Medical_backpanel/PurchaseReturnInvoiceEdit/' . (int) ($row->id ?? 0)) ?>','searchresult','Purchase Return Invoice Edit');" type="button" class="btn btn-warning btn-sm">View & Edit</button>
                    <a href="<?= base_url('Medical_Print/print_purchase_return/' . (int) ($row->id ?? 0)) ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fa fa-print"></i> Print</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
(function () {
    if (window.jQuery && $.fn.DataTable) {
        if ($.fn.DataTable.isDataTable('#purchase_return_report_list')) {
            $('#purchase_return_report_list').DataTable().destroy();
        }
        $('#purchase_return_report_list').DataTable({
            pageLength: 25,
            order: [[0, 'desc']]
        });
    }
})();
</script>

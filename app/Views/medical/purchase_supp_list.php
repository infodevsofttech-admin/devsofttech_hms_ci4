<div class="table-responsive" style="max-height:500px;overflow-y:auto;">
    <table id="purchase_report_list" class="table table-bordered table-striped table-sm align-middle">
        <thead>
        <tr>
            <th>Invoice ID</th>
            <th>Supplier</th>
            <th>Date</th>
            <th>Amount</th>
            <th style="width:130px;">Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (($purchase_list ?? []) as $row): ?>
            <tr <?= ((int) ($row->ischallan ?? 0) === 1) ? 'style="color:red;"' : '' ?>>
                <td><?= esc((string) ($row->Invoice_no ?? '')) ?></td>
                <td><?= esc($row->name_supplier ?? '') ?></td>
                <td><?= esc((string) ($row->str_date_of_invoice ?? $row->date_of_invoice ?? '')) ?></td>
                <td><?= esc((string) ($row->tamount ?? 0)) ?></td>
                <td>
                    <button onclick="load_form_div('<?= base_url('Medical/PurchaseMasterEdit/' . (int) ($row->id ?? 0)) ?>','searchresult','Purchase Master Edit');" type="button" class="btn btn-warning btn-sm">View & Edit</button>
                    <?php if ((float) ($row->tamount ?? 0) == 0.0): ?>
                        <button onclick="delete_invoice(<?= (int) ($row->id ?? 0) ?>)" type="button" class="btn btn-danger btn-sm">Delete</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
(function () {
    if (window.jQuery && $.fn.DataTable) {
        if ($.fn.DataTable.isDataTable('#purchase_report_list')) {
            $('#purchase_report_list').DataTable().destroy();
        }
        $('#purchase_report_list').DataTable({
            pageLength: 25,
            order: [[0, 'desc']]
        });
    }

    window.delete_invoice = function (invId) {
        if (!confirm('Are You sure ,Delete this Invoice')) {
            return;
        }
        $.post('<?= base_url('Medical/purchase_invoice_delete') ?>/' + invId, {}, function (data) {
            if (!data || (data.is_delete || 0) === 0) {
                notify('error', 'Please Attention', (data && data.show_text) ? data.show_text : 'Unable to delete');
                return;
            }
            notify('success', 'Please Attention', data.show_text || 'Delete Successfully');
            $('#purchase-search-form').trigger('submit');
        }, 'json');
    };
})();
</script>

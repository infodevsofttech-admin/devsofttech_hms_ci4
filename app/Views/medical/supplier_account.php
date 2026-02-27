<div class="card">
    <div class="card-body pt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="card-title mb-0">Supplier List</h5>
            <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('Medical/master') ?>','medical-main','Master :Pharmacy');">Back to Master</a>
        </div>

        <div class="table-responsive">
            <table id="supplier-account-table" class="table table-striped table-bordered table-sm align-middle">
                <thead>
                <tr>
                    <th>Name</th>
                    <th class="text-end">Balance</th>
                    <th>Last Inv. Date</th>
                    <th>Last Payment Date</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (($supplier_data ?? []) as $row): ?>
                    <?php $balance = (float) ($row->Tot_Balance ?? 0); ?>
                    <tr>
                        <td><?= esc($row->name_supplier ?? '') ?></td>
                        <td class="text-end" data-order="<?= esc((string) abs($balance)) ?>"><?= esc(number_format(abs($balance), 2)) ?> <?= $balance < 0 ? '<span class="text-danger">Dr</span>' : '<span class="text-success">Cr</span>' ?></td>
                        <td><?= esc($row->Last_InvDate ?? '') ?></td>
                        <td><?= esc($row->Last_Payment ?? '') ?></td>
                        <td>
                            <a class="btn btn-primary btn-sm" href="javascript:load_form_div('<?= base_url('Medical/supplier_account_led/' . (int) ($row->sid ?? 0)) ?>','medical-main','Supplier Ledger :Pharmacy');">Open</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    if (window.jQuery && $.fn && $.fn.DataTable) {
        const table = $('#supplier-account-table');
        if (table.length && !$.fn.DataTable.isDataTable(table)) {
            table.DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                order: [[1, 'desc']]
            });
        }
    }
})();
</script>

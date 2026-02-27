<table id="supplier_report_list" class="table table-bordered table-striped table-sm align-middle">
    <thead>
    <tr>
        <th>Name</th>
        <th>GST</th>
        <th style="width:90px;">Action</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach (($supplier_data ?? []) as $row): ?>
        <tr>
            <td><?= esc($row->name_supplier ?? '') ?></td>
            <td><?= esc($row->gst_no ?? '') ?></td>
            <td>
                <button onclick="load_form_div('<?= base_url('Medical/SupplierEdit/' . (int) ($row->sid ?? 0)) ?>','test_div','Supplier :<?= esc(($row->name_supplier ?? '')) ?>:Pharmacy');" type="button" class="btn btn-primary btn-sm">Edit</button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
(function () {
    if (window.jQuery && $.fn.DataTable) {
        if ($.fn.DataTable.isDataTable('#supplier_report_list')) {
            $('#supplier_report_list').DataTable().destroy();
        }
        $('#supplier_report_list').DataTable({
            pageLength: 25,
            order: [[0, 'asc']]
        });
    }
})();
</script>

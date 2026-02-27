<table id="medicine_category_report_list" class="table table-bordered table-striped table-sm align-middle">
    <thead>
    <tr>
        <th>Category Name</th>
        <th style="width:90px;">Action</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach (($med_product_cat_master ?? []) as $row): ?>
        <tr>
            <td><?= esc($row->med_cat_desc ?? '') ?></td>
            <td>
                <button onclick="load_form_div('<?= base_url('Product_master/medicine_category_edit/' . (int) ($row->id ?? 0)) ?>','test_div','Medicine Category :<?= esc(($row->med_cat_desc ?? '')) ?>:Pharmacy');" type="button" class="btn btn-primary btn-sm">Edit</button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
(function () {
    if (window.jQuery && $.fn.DataTable) {
        if ($.fn.DataTable.isDataTable('#medicine_category_report_list')) {
            $('#medicine_category_report_list').DataTable().destroy();
        }
        $('#medicine_category_report_list').DataTable({
            pageLength: 25,
            order: [[0, 'asc']]
        });
    }
})();
</script>

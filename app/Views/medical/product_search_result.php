<div class="table-responsive" style="max-height:500px;overflow-y:auto;">
    <table id="product_report_list" class="table table-bordered table-striped table-sm align-middle">
        <thead>
        <tr>
            <th>Prod. ID</th>
            <th>Name</th>
            <th>Formulation</th>
            <th>Generic Name</th>
            <th>Packing Type</th>
            <th style="width:110px;">Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (($product_list ?? []) as $row): ?>
            <tr>
                <td><?= (int) ($row->id ?? 0) ?></td>
                <td><?= esc($row->item_name ?? '') ?></td>
                <td><?= esc($row->formulation ?? '') ?></td>
                <td><?= esc($row->genericname ?? '') ?></td>
                <td><?= esc($row->packing ?? '') ?></td>
                <td>
                    <button onclick="load_form_div('<?= base_url('Product_master/Product_edit/' . (int) ($row->id ?? 0)) ?>','searchresult','Drug Master : Edit :Pharmacy');" type="button" class="btn btn-warning btn-sm">Edit</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
(function () {
    if (window.jQuery && $.fn.DataTable) {
        if ($.fn.DataTable.isDataTable('#product_report_list')) {
            $('#product_report_list').DataTable().destroy();
        }
        $('#product_report_list').DataTable({
            pageLength: 25,
            order: [[0, 'desc']]
        });
    }
})();
</script>

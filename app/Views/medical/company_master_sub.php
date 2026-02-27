<table id="company_report_list" class="table table-bordered table-striped table-sm align-middle">
    <thead>
    <tr>
        <th>Company Name</th>
        <th>Person/Phone</th>
        <th style="width:90px;">Action</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach (($med_company ?? []) as $row): ?>
        <tr>
            <td><?= esc($row->company_name ?? '') ?></td>
            <td><?= esc(($row->contact_person_name ?? '') . '/' . ($row->contact_phone_no ?? '')) ?></td>
            <td>
                <button onclick="load_form_div('<?= base_url('Product_master/CompanyEdit/' . (int) ($row->id ?? 0)) ?>','test_div','Company :<?= esc(($row->company_name ?? '')) ?>:Pharmacy');" type="button" class="btn btn-primary btn-sm">Edit</button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
(function () {
    if (window.jQuery && $.fn.DataTable) {
        if ($.fn.DataTable.isDataTable('#company_report_list')) {
            $('#company_report_list').DataTable().destroy();
        }
        $('#company_report_list').DataTable({
            pageLength: 25,
            order: [[0, 'asc']]
        });
    }
})();
</script>

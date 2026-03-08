<section class="content">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Admitted IPD Patient List (Nursing)</h5>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped table-bordered" id="ipdNursingListTable">
                <thead>
                <tr>
                    <th>IPD Code</th>
                    <th>UHID</th>
                    <th>Patient</th>
                    <th>Bed</th>
                    <th>Doctor</th>
                    <th>Admit Date</th>
                    <th>Days</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (($records ?? []) as $row) : ?>
                    <tr>
                        <td><?= esc($row->ipd_code ?? '') ?></td>
                        <td><?= esc($row->p_code ?? '') ?></td>
                        <td><?= esc(trim((string) (($row->p_fname ?? '') . ' ' . ($row->p_rname ?? '')))) ?></td>
                        <td><?= esc($row->Bed_Desc ?? '') ?></td>
                        <td><?= esc($row->doc_name ?? '') ?></td>
                        <td><?= esc($row->str_register_date ?? '') ?></td>
                        <td><?= esc($row->no_days ?? '') ?></td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm" onclick="load_form('<?= base_url('ipd/patient/workspace/' . (int) ($row->id ?? 0)) ?>','Nursing Workspace');">Open</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
(function () {
    if (window.jQuery && $.fn.DataTable) {
        $('#ipdNursingListTable').DataTable();
    }
})();
</script>

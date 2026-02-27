<br /><br />
<div class="box">
    <div class="box-header">
        <h3 class="box-title">Last OPDs</h3>
    </div>
    <div class="box-body">
        <table class="table table-bordered table-striped datatable" id="opd_list_last">
            <thead>
                <tr>
                    <th>Sr.No.</th>
                    <th>OPD Code</th>
                    <th>Patient/UHID Code</th>
                    <th>Name {Relative Name}</th>
                    <th>Age</th>
                    <th>Visit Date</th>
                    <th>Doctor</th>
                    <th>Insurance</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data)) : ?>
                    <?php foreach ($data as $i => $row) : ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= esc($row->opd_code ?? '') ?></td>
                            <td><a href="javascript:load_form('<?= base_url('billing/patient/person_record') ?>/<?=$row->id ?>');"><?=$row->p_code ?></a></td>
                            <td><?= esc(($row->p_fname ?? '') . ' {' . ($row->p_rname ?? '') . '}') ?></td>
                            <td><?= esc(get_age_1($row->dob ?? null, $row->age ?? '', $row->age_in_month ?? '', $row->estimate_dob ?? '', $row->opd_Visit ?? null)) ?></td>
                            <td><?= esc($row->opd_Visit ?? '') ?></td>
                            <td><?= esc($row->doc_name ?? '') ?></td>
                            <td><?= esc($row->short_name ?? '') ?></td>
                        </tr>
                    <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    (function() {
        var table = document.getElementById('opd_list_last');
        if (!table || !window.simpleDatatables || !window.simpleDatatables.DataTable) {
            return;
        }

        new simpleDatatables.DataTable(table);
    })();
</script>


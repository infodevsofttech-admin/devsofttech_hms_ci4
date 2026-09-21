

<?php
$allCount = count($records ?? []);
$ipdCount = 0;
$dcCount = 0;
$erCount = 0;
foreach ($records ?? [] as $r) {
    $t = $r->admission_type ?? 'ipd';
    if ($t === 'daycare') $dcCount++;
    elseif ($t === 'emergency') $erCount++;
    else $ipdCount++;
}
?>

<div class="col-md-12">
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h3 class="card-title mb-0">Current Admissions</h3>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="btn-group btn-group-sm" role="group" id="admTypeFilterGroup">
                    <button type="button" class="btn btn-outline-primary active" data-filter="all">All (<?= $allCount ?>)</button>
                    <button type="button" class="btn btn-outline-primary" data-filter="ipd">Inpatient IPD (<?= $ipdCount ?>)</button>
                    <button type="button" class="btn btn-outline-warning text-dark" data-filter="daycare"><i class="bi bi-clock-history"></i> Day Care (<?= $dcCount ?>)</button>
                    <button type="button" class="btn btn-outline-danger" data-filter="emergency"><i class="bi bi-ambulance"></i> Emergency (<?= $erCount ?>)</button>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-plus-circle me-1"></i> New Admission
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item text-danger fw-semibold" href="javascript:load_form_div('<?= base_url('billing/ipd/admit?type=emergency') ?>','maindiv','Emergency Admission')"><i class="bi bi-ambulance me-2"></i>Emergency (Casualty)</a></li>
                        <li><a class="dropdown-item text-warning fw-semibold" href="javascript:load_form_div('<?= base_url('billing/ipd/admit?type=daycare') ?>','maindiv','Day Care Admission')"><i class="bi bi-clock-history me-2"></i>Day Care (&lt; 24 Hrs)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-primary fw-semibold" href="javascript:load_form_div('<?= base_url('billing/ipd/admit?type=ipd') ?>','maindiv','Inpatient Admission')"><i class="bi bi-hospital me-2"></i>Regular Inpatient (IPD)</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card-body">

            <div class="alert alert-warning d-none" id="datatable-missing">
                DataTable plugin is not loaded. Please include jQuery DataTables to enable filtering.
            </div>
            <div class="table-responsive">
                <table id="ipd-current-table" class="table table-striped table-hover align-middle TableData">
                    <thead>
                        <tr>
                            <th>Admission Code</th>
                            <th>Name/Patient Code</th>
                            <th>Bed No. [Ward]</th>
                            <th>Register Date</th>
                            <th>No. of Days</th>
                            <th>Doctor Name</th>
                            <th>Admit Type</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($records ?? []) as $row) : 
                            $admType = $row->admission_type ?? 'ipd';
                        ?>
                            <tr style="color:<?= esc($row->color ?? '') ?>;" data-adm-type="<?= esc($admType) ?>">
                                <td>
                                    <strong><?= esc($row->ipd_code ?? '') ?></strong>
                                    <?php if ($admType === 'daycare'): ?>
                                        <br><span class="badge bg-warning text-dark" style="font-size:11px;"><i class="bi bi-clock-history"></i> Day Care</span>
                                    <?php elseif ($admType === 'emergency'): ?>
                                        <?php 
                                            $tLvl = (int) ($row->triage_level ?? 3); 
                                            $tColor = $tLvl === 1 ? 'danger' : ($tLvl === 2 ? 'warning text-dark' : ($tLvl === 3 ? 'warning text-dark' : 'info text-dark'));
                                        ?>
                                        <br><span class="badge bg-<?= $tColor ?>" style="font-size:11px;"><i class="bi bi-ambulance"></i> ER ESI-<?= $tLvl ?></span>
                                    <?php endif; ?>
                                    <?php if (! empty($row->converted_from_type)): ?>
                                        <br><span class="badge bg-info text-dark" style="font-size:10px;"><i class="bi bi-arrow-repeat"></i> from <?= ucfirst(esc($row->converted_from_type)) ?></span>
                                    <?php endif; ?>
                                    <?php if (! empty($row->is_mlc)): ?>
                                        <br><span class="badge bg-danger" style="font-size:10px;">MLC</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong>[<?= esc($row->p_code ?? '') ?>]</strong> <?= esc($row->p_fname ?? '') ?><br />
                                    <small class="text-muted"><?= esc($row->p_rname ?? '') ?></small>
                                    <?php if (! empty($row->daycare_procedure_name)): ?>
                                        <br><small class="text-primary font-monospace"><?= esc($row->daycare_procedure_name) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($row->Bed_Desc ?? '') ?></td>
                                <td><?= esc($row->str_register_date ?? '') ?></td>
                                <td><?= esc($row->no_days ?? '') ?></td>
                                <td><?= esc($row->doc_name ?? '') ?></td>
                                <td><?= esc($row->admit_type ?? '') ?><br><?= esc($row->Org_Status ?? '') ?><br><?= esc($row->insurance_no_1 ?? '') ?></td>
                                <td>C:<?= esc($row->charge_amount ?? '') ?>/M:<?= esc($row->med_amount ?? '') ?>/P:<?= esc($row->paid_amount ?? '') ?>/B:<?= esc($row->balance ?? '') ?></td>
                                <td>
                                    <?php if (! empty($row->id)) : ?>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="load_form_div('<?= base_url('billing/ipd/panel') ?>/<?= (int) ($row->id ?? 0) ?>','maindiv','IPD Panel');">View Panel</button>
                                    <?php else : ?>
                                        <button type="button" class="btn btn-secondary btn-sm" disabled>View Panel</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Admission Code</th>
                            <th>Name/Patient Code</th>
                            <th>Bed No. [Ward]</th>
                            <th>Register Date</th>
                            <th>No. of Days</th>
                            <th>Doctor Name</th>
                            <th>Admit Type</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        if (!$.fn || !$.fn.DataTable) {
            $('#datatable-missing').removeClass('d-none');
            return;
        }

        var table = $('#ipd-current-table').DataTable({
            pageLength: 25,
            order: [[0, 'desc']]
        });

        $('#admTypeFilterGroup button').on('click', function() {
            $('#admTypeFilterGroup button').removeClass('active');
            $(this).addClass('active');
            var filter = $(this).data('filter');

            if (filter === 'all') {
                table.search('').columns().search('').draw();
            } else if (filter === 'daycare') {
                table.search('Day Care').draw();
            } else if (filter === 'emergency') {
                table.search('ER').draw();
            } else if (filter === 'ipd') {
                table.search('A2').draw();
            }
        });
    });
</script>


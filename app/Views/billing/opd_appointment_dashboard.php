<section class="content-header">
    <h3>OPD Appointments</h3>
</section>

<section class="content">
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar2-week"></i></span>
                <input type="date" class="form-control" id="opd_date" value="<?= esc($opd_date ?? date('Y-m-d')) ?>">
                <button class="btn btn-outline-secondary" type="button" id="btn_opd_date_go"><i class="bi bi-arrow-repeat"></i></button>
            </div>
        </div>
    </div>

    <div class="row">
        <?php if (!empty($doc_master)) : ?>
            <?php foreach ($doc_master as $row) : ?>
                <?php
                    $colorClass = $color1[(string) (($row->color_code ?? 0) % 6)] ?? 'bg-primary';
                    $total = (int) ($row->No_opd ?? 0);
                    $textClass = in_array($colorClass, ['bg-warning', 'bg-info', 'bg-light'], true) ? 'text-dark' : 'text-white';
                ?>
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-header <?= esc($textClass) ?> <?= esc($colorClass) ?>">
                            <h4 class="mb-0">Dr. <?= esc($row->p_fname ?? '') ?></h4>
                            <small><?= esc($row->Spec ?? '') ?></small>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <tr><td>Booked</td><td class="text-end"><span class="badge bg-secondary\"><?= esc((int) ($row->count_booking ?? 0)) ?></span></td></tr>
                                <tr><td>Waiting</td><td class="text-end"><span class="badge bg-primary\"><?= esc((int) ($row->count_wait ?? 0)) ?></span></td></tr>
                                <tr><td>Visited</td><td class="text-end"><span class="badge bg-info\"><?= esc((int) ($row->count_visit ?? 0)) ?></span></td></tr>
                                <tr><td>Total</td><td class="text-end"><span class="badge bg-danger"><?= esc($total) ?></span></td></tr>
                            </table>
                        </div>
                        <div class="card-footer p-0">
                            <a class="btn btn-primary w-100" href="javascript:load_form('<?= base_url('Opd/get_appointment_list') ?>/<?= esc((int) ($row->doc_id ?? 0)) ?>/<?= esc($opd_date) ?>','OPD Appointment List');">Show List</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="col-12"><div class="alert alert-warning mb-0">No OPD appointment records found for selected date.</div></div>
        <?php endif; ?>
    </div>
</section>

<script>
(function() {
    $('#btn_opd_date_go').on('click', function() {
        var val = ($('#opd_date').val() || '').trim();
        if (!val) {
            return;
        }
        load_form('<?= base_url('Opd/get_appointment_data') ?>/' + val, 'OPD Appointment List');
    });
})();
</script>

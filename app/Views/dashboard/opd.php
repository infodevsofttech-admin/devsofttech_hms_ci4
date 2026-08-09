<?php
$totalVisits = (int) ($totals['total_visits'] ?? 0);
$uniquePatients = (int) ($totals['unique_patients'] ?? 0);
$completedVisits = (int) ($totals['completed_visits'] ?? 0);
$cancelledVisits = (int) ($totals['cancelled_visits'] ?? 0);
$maxDoctor = max(array_merge([1], array_map(static fn (array $row): int => (int) $row['total'], $doctorRows ?? [])));
?>
<div class="pagetitle d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div><h1>OPD Dashboard</h1><div class="text-muted small">Visit activity, clinical workload, and referral sources</div></div>
    <button class="btn btn-light" type="button" onclick="load_form('<?= base_url('dashboard') ?>','Dashboard');"><i class="bi bi-arrow-left"></i> Main Dashboard</button>
</div>

<section class="section dashboard dashboard-detail">
    <div class="card dashboard-filter-card">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-sm-4 col-lg-3"><label class="form-label" for="opd_from_date">From date</label><input class="form-control" id="opd_from_date" type="date" value="<?= esc($fromDate) ?>"></div>
                <div class="col-sm-4 col-lg-3"><label class="form-label" for="opd_to_date">To date</label><input class="form-control" id="opd_to_date" type="date" value="<?= esc($toDate) ?>"></div>
                <div class="col-sm-4 col-lg-2"><button class="btn btn-primary w-100" id="opd_apply_range" type="button"><i class="bi bi-funnel"></i> Apply</button></div>
                <div class="col-lg-4"><div class="btn-group w-100" role="group" aria-label="OPD quick date range"><button class="btn btn-outline-secondary opd-range" data-days="0" type="button">Today</button><button class="btn btn-outline-secondary opd-range" data-days="6" type="button">7 days</button><button class="btn btn-outline-secondary opd-range" data-days="29" type="button">30 days</button></div></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <?php foreach ([['Visits', $totalVisits, 'bi-people', 'primary'], ['Unique patients', $uniquePatients, 'bi-person-vcard', 'info'], ['Completed visits', $completedVisits, 'bi-check2-circle', 'success'], ['Cancelled', $cancelledVisits, 'bi-x-circle', 'danger']] as [$label, $value, $icon, $tone]) : ?>
            <div class="col-6 col-xl-3"><div class="card h-100 dashboard-kpi"><div class="card-body"><div class="dashboard-kpi-icon text-bg-<?= $tone ?>"><i class="bi <?= $icon ?>"></i></div><div><div class="dashboard-kpi-value"><?= $value ?></div><div class="text-muted small"><?= esc($label) ?></div></div></div></div></div>
        <?php endforeach ?>
    </div>

    <div class="row g-3">
        <div class="col-xl-7"><div class="card h-100"><div class="card-body"><h5 class="card-title">Doctor workload <span>| <?= esc(date('d M', strtotime($fromDate))) ?> - <?= esc(date('d M Y', strtotime($toDate))) ?></span></h5>
            <?php if (empty($doctorRows)) : ?><div class="dashboard-empty">No OPD visits in this period.</div><?php else : ?>
                <div class="dashboard-bars"><?php foreach ($doctorRows as $row) : ?><div class="dashboard-bar-row"><div class="d-flex justify-content-between gap-3"><span><?= esc($row['label']) ?></span><strong><?= (int) $row['total'] ?></strong></div><div class="progress"><div class="progress-bar" style="width:<?= round(((int) $row['total'] / $maxDoctor) * 100, 1) ?>%"></div></div><div class="small text-muted"><?= (int) $row['patients'] ?> unique patient<?= (int) $row['patients'] === 1 ? '' : 's' ?></div></div><?php endforeach ?></div>
            <?php endif ?></div></div></div>
        <div class="col-xl-5"><div class="card h-100"><div class="card-body"><h5 class="card-title">Department distribution</h5><?= view('dashboard/partials/ranked_table', ['rows' => $departmentRows, 'labelHeading' => 'Department', 'showPatients' => true]) ?></div></div></div>
        <div class="col-xl-6"><div class="card h-100"><div class="card-body"><h5 class="card-title">Referral source</h5><div class="text-muted small mb-2">Unique patients and visits, derived from the billing referral recorded against each OPD code.</div><?= view('dashboard/partials/ranked_table', ['rows' => $referralRows, 'labelHeading' => 'Referral', 'showPatients' => true]) ?></div></div></div>
        <div class="col-xl-6"><div class="card h-100"><div class="card-body"><h5 class="card-title">Patient category</h5><?= view('dashboard/partials/ranked_table', ['rows' => $organizationRows, 'labelHeading' => 'Category', 'showPatients' => false]) ?></div></div></div>
    </div>
</section>
<?= view('dashboard/partials/detail_assets') ?>
<script>(function(){function loadRange(from,to){load_form('<?= base_url('dashboard/opd') ?>?from_date='+encodeURIComponent(from)+'&to_date='+encodeURIComponent(to),'OPD Dashboard');}$('#opd_apply_range').off('click.opdDash').on('click.opdDash',function(){loadRange($('#opd_from_date').val(),$('#opd_to_date').val());});$('.opd-range').off('click.opdDash').on('click.opdDash',function(){var end=new Date(),start=new Date();start.setDate(end.getDate()-Number($(this).data('days')||0));function ymd(d){return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');}loadRange(ymd(start),ymd(end));});})();</script>
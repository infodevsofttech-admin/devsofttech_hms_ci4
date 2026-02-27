<?php
$opdToday = $opd_today ?? 0;
$opdLast7Days = $opd_last_7_days ?? 0;
$admitToday = $admit_today ?? 0;
$dischargeToday = $discharge_today ?? 0;
$currentIpd = $current_ipd ?? 0;
$currentOrgIpd = $current_org_ipd ?? 0;
$opdOrgList = $opd_org_list ?? [];
$opdDoctorList = $opd_doctor_list ?? [];
$ipdDoctorList = $ipd_doctor_list ?? [];
$ipdOrgList = $ipd_org_list ?? [];
$trendLabels = $trend_labels ?? [];
$trendOpd = $trend_opd ?? [];
$trendIpd = $trend_ipd ?? [];
?>

<div class="pagetitle">
    <h1>Dashboard</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">
    <div class="row">
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">OPD <span>| Today</span></h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <div class="ps-3">
                            <h6><?= esc((string) $opdToday) ?></h6>
                            <span class="text-muted small pt-1">Last 7 days: <?= esc((string) $opdLast7Days) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Admit & Discharge <span>| Today</span></h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-clipboard2-pulse"></i>
                        </div>
                        <div class="ps-3">
                            <h6><?= esc((string) $admitToday) ?> / <?= esc((string) $dischargeToday) ?></h6>
                            <span class="text-muted small pt-1">Admit / Discharge</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card customers-card">
                <div class="card-body">
                    <h5 class="card-title">Current IPD <span>| In-house</span></h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-hospital"></i>
                        </div>
                        <div class="ps-3">
                            <h6><?= esc((string) $currentIpd) ?></h6>
                            <span class="text-muted small pt-1">Org cases: <?= esc((string) $currentOrgIpd) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card">
                <div class="card-body">
                    <h5 class="card-title">Daily Trend <span>| Current Month</span></h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-bar-chart"></i>
                        </div>
                        <div class="ps-3">
                            <h6><?= esc((string) array_sum($trendOpd)) ?> / <?= esc((string) array_sum($trendIpd)) ?></h6>
                            <span class="text-muted small pt-1">OPD / IPD total (this month)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Daily Case Trend (Current Month)</h5>
                    <div style="height: 260px;">
                        <canvas id="caseTrendChart" style="height: 260px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">OPD by Organization</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Organization</th>
                                    <th class="text-end">Cases</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($opdOrgList)) : ?>
                                    <?php foreach ($opdOrgList as $row) : ?>
                                        <tr>
                                            <td><?= esc($row->org_name ?? 'Direct') ?></td>
                                            <td class="text-end"><?= esc((string) $row->total_cases) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">No data</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">OPD by Doctor</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Direct</th>
                                    <th class="text-end">Org</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($opdDoctorList)) : ?>
                                    <?php foreach ($opdDoctorList as $row) : ?>
                                        <tr>
                                            <td><?= esc($row->doc_name) ?></td>
                                            <td class="text-end"><?= esc((string) $row->total_cases) ?></td>
                                            <td class="text-end"><?= esc((string) $row->direct_cases) ?></td>
                                            <td class="text-end"><?= esc((string) $row->org_cases) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No data</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">IPD Doctors</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th class="text-end">Cases</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ipdDoctorList)) : ?>
                                    <?php foreach ($ipdDoctorList as $row) : ?>
                                        <tr>
                                            <td><?= esc($row->doc_name) ?></td>
                                            <td class="text-end"><?= esc((string) $row->total_cases) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">No data</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">IPD by Organization</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Organization</th>
                                    <th class="text-end">Cases</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ipdOrgList)) : ?>
                                    <?php foreach ($ipdOrgList as $row) : ?>
                                        <tr>
                                            <td><?= esc($row->org_name ?? 'Direct') ?></td>
                                            <td class="text-end"><?= esc((string) $row->total_cases) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">No data</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="<?= base_url('assets/vendor/chart.js/chart.umd.js') ?>"></script>
<script>
    (function () {
        const labels = <?= json_encode($trendLabels) ?>;
        const opdData = <?= json_encode($trendOpd) ?>;
        const ipdData = <?= json_encode($trendIpd) ?>;
        const ctx = document.getElementById('caseTrendChart');

        if (!ctx) {
            return;
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'OPD Cases',
                        data: opdData,
                        borderColor: '#2c7be5',
                        backgroundColor: 'rgba(44, 123, 229, 0.1)',
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'IPD Cases',
                        data: ipdData,
                        borderColor: '#00a65a',
                        backgroundColor: 'rgba(0, 166, 90, 0.1)',
                        tension: 0.35,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Day of current month'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Total cases per day'
                        },
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y;
                            }
                        }
                    }
                }
            }
        });
    })();
</script>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card ops-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Server Overview</h5>
                <div class="d-flex gap-2 align-items-center">
                    <span class="ops-health" id="serverHealthBadge">Healthy</span>
                    <span class="ops-pill success">Live</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Host</div>
                            <div class="fs-5 fw-semibold"><?= esc($status['hostname'] ?? 'Unknown') ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">OS</div>
                            <div class="fs-6 fw-semibold"><?= esc($status['os'] ?? 'Unknown') ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Uptime</div>
                            <div class="fs-5 fw-semibold"><?= esc($status['uptime'] ?? 'Unknown') ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">Network</div>
                            <div class="fs-5 fw-semibold"><?= esc($status['network'] ?? 'Unavailable') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card ops-card">
            <div class="card-header">
                <h5 class="mb-0">Maintenance Actions</h5>
            </div>
            <div class="card-body d-grid gap-2">
                <button class="btn btn-primary" id="btnUpdateSystem">Update System</button>
                <button class="btn btn-outline-warning" id="btnRestartWeb">Restart Web Server</button>
                <button class="btn btn-outline-info" id="btnRestartPhp">Restart PHP-FPM</button>
                <button class="btn btn-outline-danger" id="btnShutdown">Shutdown Server</button>
                <button class="btn btn-outline-secondary" id="btnReboot">Reboot Server</button>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card ops-card">
            <div class="card-header"><h5 class="mb-0">Resource Usage</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between small text-muted"><span>CPU</span><span><?= esc((string) ($status['cpu']['used_percent'] ?? 'N/A')) ?>%</span></div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small text-muted"><span>RAM</span><span><?= esc((string) ($status['memory']['used_mb'] ?? 'N/A')) ?> / <?= esc((string) ($status['memory']['total_mb'] ?? 'N/A')) ?> MB</span></div>
                </div>
                <div>
                    <div class="d-flex justify-content-between small text-muted"><span>Disk</span><span><?= esc((string) ($status['disk']['used_gb'] ?? 'N/A')) ?> / <?= esc((string) ($status['disk']['total_gb'] ?? 'N/A')) ?> GB</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card ops-card">
            <div class="card-header"><h5 class="mb-0">Services & RAID</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="small text-muted">Internet</div>
                    <div class="fw-semibold"><?= esc($status['internet'] ?? 'Unavailable') ?></div>
                </div>
                <div class="mb-3">
                    <div class="small text-muted">Services</div>
                    <div class="small"><?= esc(implode(', ', array_keys($status['services'] ?? []))) ?></div>
                </div>
                <div>
                    <div class="small text-muted">RAID</div>
                    <div class="small"><?= esc($status['raid'] ?? 'Unavailable') ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card ops-card">
            <div class="card-header"><h5 class="mb-0">Latest Events</h5></div>
            <div class="card-body">
                <ul class="ops-timeline">
                    <?php foreach (array_slice($history, 0, 6) as $entry): ?>
                        <li>
                            <div class="fw-semibold"><?= esc((string) ($entry['type'] ?? '')) ?></div>
                            <div class="small text-muted"><?= esc((string) ($entry['timestamp'] ?? '')) ?></div>
                            <div class="small"><?= esc((string) ($entry['message'] ?? '')) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card ops-card">
            <div class="card-header"><h5 class="mb-0">Update History</h5></div>
            <div class="card-body">
                <?php if (empty($history)) : ?>
                    <div class="text-muted">No history yet.</div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $entry): ?>
                                    <tr>
                                        <td><?= esc((string) ($entry['timestamp'] ?? '')) ?></td>
                                        <td><?= esc((string) ($entry['type'] ?? '')) ?></td>
                                        <td>
                                            <?php $st = (string) ($entry['status'] ?? ''); ?>
                                            <span class="ops-pill <?= $st === 'success' ? 'success' : ($st === 'failed' ? 'danger' : 'warning') ?>"><?= esc($st) ?></span>
                                        </td>
                                        <td><?= esc((string) ($entry['message'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

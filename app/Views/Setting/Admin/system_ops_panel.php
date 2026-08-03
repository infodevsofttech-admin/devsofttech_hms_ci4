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
                <button class="btn btn-success" id="btnUpdateDirectGithub" title="Download and deploy latest from GitHub">Deploy from GitHub</button>
                <hr class="my-2">
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
                <div class="mb-4">
                    <div class="d-flex justify-content-between small text-muted mb-1"><span>CPU</span><span id="cpuPercent"><?= esc((string) ($status['cpu']['used_percent'] ?? 'N/A')) ?>%</span></div>
                    <div class="progress" style="height: 20px; border-radius: 4px; overflow: hidden;">
                        <div class="progress-bar bg-success" id="cpuBar" role="progressbar" style="width: <?= (float) ($status['cpu']['used_percent'] ?? 0) ?>%; transition: width 0.3s ease;" aria-valuenow="<?= (float) ($status['cpu']['used_percent'] ?? 0) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>RAM</span>
                        <span id="ramPercent">
                            <?php 
                            $ramTotal = (int) ($status['memory']['total_mb'] ?? 0);
                            $ramUsed = (int) ($status['memory']['used_mb'] ?? 0);
                            $ramPercent = $ramTotal > 0 ? round(($ramUsed / $ramTotal) * 100) : 0;
                            echo esc((string) $ramUsed) . ' / ' . esc((string) $ramTotal) . ' MB (' . $ramPercent . '%)';
                            ?>
                        </span>
                    </div>
                    <div class="progress" style="height: 20px; border-radius: 4px; overflow: hidden;">
                        <div class="progress-bar bg-info" id="ramBar" role="progressbar" style="width: <?= $ramPercent ?>%; transition: width 0.3s ease;" aria-valuenow="<?= $ramPercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Disk</span>
                        <span id="diskPercent">
                            <?php 
                            $diskTotal = (float) ($status['disk']['total_gb'] ?? 0);
                            $diskUsed = (float) ($status['disk']['used_gb'] ?? 0);
                            $diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100) : 0;
                            echo esc((string) $diskUsed) . ' / ' . esc((string) $diskTotal) . ' GB (' . $diskPercent . '%)';
                            ?>
                        </span>
                    </div>
                    <div class="progress" style="height: 20px; border-radius: 4px; overflow: hidden;">
                        <div class="progress-bar bg-warning" id="diskBar" role="progressbar" style="width: <?= $diskPercent ?>%; transition: width 0.3s ease;" aria-valuenow="<?= $diskPercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
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
                                    <th>Details</th>
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
                                        <td>
                                            <?php if (!empty($entry['detail'])) : ?>
                                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#detailModal" data-detail="<?= esc((string) ($entry['detail'] ?? '')) ?>" title="Show details">
                                                    <i class="bi bi-info-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
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

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Operation Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="detailContent" style="max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 12px; border-radius: 4px;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Handle detail modal
    document.addEventListener('show.bs.modal', function(e) {
        if (e.relatedTarget && e.relatedTarget.id === 'detailModal' || (e.relatedTarget && e.relatedTarget.closest('[data-bs-target="#detailModal"]'))) {
            var btn = e.relatedTarget;
            if (!btn) btn = event.target;
            var detail = btn.getAttribute('data-detail') || '';
            document.getElementById('detailContent').textContent = detail;
        }
    });
</script>

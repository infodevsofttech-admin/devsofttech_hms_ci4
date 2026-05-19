<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($page_title) ?> — ABDM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .wrap { max-width: 1280px; margin: 24px auto; }
        .badge-pending { background:#fff3cd; color:#856404; }
        .badge-done    { background:#d1e7dd; color:#0a3622; }
        .badge-failed  { background:#f8d7da; color:#842029; }
        .tbl td, .tbl th { vertical-align: middle; }
    </style>
</head>
<body>
<div class="wrap px-3">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">SNOMED Coding Panel</h4>
            <div class="small text-muted">Review AI-matched SNOMED codes before FHIR submission</div>
        </div>
        <a href="<?= site_url('AbdmTaskBoard') ?>" class="btn btn-sm btn-outline-secondary">← Task Board</a>
    </div>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-info py-2"><?= esc(session()->getFlashdata('message')) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover tbl mb-0">
                <thead class="table-light">
                    <tr>
                        <th>OPD Date</th>
                        <th>OPD No</th>
                        <th>Patient</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Pending</th>
                        <th class="text-center">Confirmed</th>
                        <th>Processed At</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($sessions)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No coding sessions ready for review.</td></tr>
                <?php else: ?>
                    <?php foreach ($sessions as $s): ?>
                    <tr>
                        <td><?= esc($s['opd_date'] ?? '') ?></td>
                        <td><?= esc($s['opd_no'] ?? $s['opd_id']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= esc($s['patient_name'] ?? '—') ?></div>
                            <div class="small text-muted"><?= esc($s['patient_mobile'] ?? '') ?></div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary"><?= (int)$s['total_suggestions'] ?></span>
                        </td>
                        <td class="text-center">
                            <?php if ((int)$s['pending_count'] > 0): ?>
                                <span class="badge badge-pending px-2 py-1 rounded-pill"><?= (int)$s['pending_count'] ?> pending</span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ((int)$s['confirmed_count'] > 0): ?>
                                <span class="badge badge-done px-2 py-1 rounded-pill"><?= (int)$s['confirmed_count'] ?> done</span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= esc($s['processed_at'] ?? '') ?></td>
                        <td>
                            <a href="<?= site_url('AbdmCodingPanel/review/' . (int)$s['opd_session_id']) ?>"
                               class="btn btn-sm btn-outline-primary">Review</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

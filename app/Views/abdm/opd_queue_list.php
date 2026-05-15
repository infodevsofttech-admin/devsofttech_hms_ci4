<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ABDM OPD List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; }
        .wrap { max-width: 1280px; margin: 20px auto; }
        .status-PENDING   { background:#ffc107; color:#000; }
        .status-CALLED    { background:#0d6efd; color:#fff; }
        .status-COMPLETED { background:#198754; color:#fff; }
        .status-CANCELLED { background:#dc3545; color:#fff; }
        .badge-scan  { background:#0d6efd; }
        .badge-manual{ background:#6c757d; }
        .abha-num { font-family: monospace; font-size: .85rem; }
    </style>
</head>
<body>
<div class="wrap px-3 pb-5">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 pt-3">
        <div>
            <h4 class="mb-0">ABDM OPD List</h4>
            <div class="small text-muted">
                Local record of all ABDM queue tokens for date
                <strong><?= esc($date) ?></strong>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="get" class="d-flex gap-2">
                <input type="date" name="date" class="form-control form-control-sm" value="<?= esc($date) ?>">
                <button type="submit" class="btn btn-sm btn-outline-primary">Go</button>
            </form>
            <a href="javascript:load_form('<?= base_url('AbdmOpdQueue') ?>','ABDM OPD Queue')"
               class="btn btn-sm btn-primary">Live Queue ↗</a>
        </div>
    </div>

    <!-- Summary badges -->
    <?php
    $c = $counts ?? [];
    $total       = (int) ($c['total'] ?? 0);
    $processed   = (int) ($c['processed'] ?? 0);
    $unprocessed = (int) ($c['unprocessed'] ?? 0);
    $scanTotal   = (int) ($c['scan_total'] ?? 0);
    $pending     = (int) ($c['pending'] ?? 0);
    $completed   = (int) ($c['completed'] ?? 0);
    ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge rounded-pill bg-secondary px-3 py-2"><?= $total ?> Total</span>
        <span class="badge rounded-pill bg-primary px-3 py-2"><?= $scanTotal ?> ABHA Scan</span>
        <span class="badge rounded-pill bg-success px-3 py-2"><?= $processed ?> Processed (Patient Linked)</span>
        <span class="badge rounded-pill bg-warning text-dark px-3 py-2"><?= $unprocessed ?> Unprocessed</span>
        <span class="badge rounded-pill bg-info text-dark px-3 py-2"><?= $completed ?> Completed</span>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-3" id="listTabs">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabAll">
                All <span class="badge bg-secondary ms-1"><?= $total ?></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabScan">
                ABHA Scan <span class="badge bg-primary ms-1"><?= $scanTotal ?></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabProcessed">
                Processed <span class="badge bg-success ms-1"><?= $processed ?></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabUnprocessed">
                Unprocessed <span class="badge bg-warning text-dark ms-1"><?= $unprocessed ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- All -->
        <div class="tab-pane fade show active" id="tabAll">
            <?= renderTokenTable($tokens ?? [], 'all') ?>
        </div>
        <!-- ABHA Scan only -->
        <div class="tab-pane fade" id="tabScan">
            <?= renderTokenTable(array_filter($tokens ?? [], fn($t) => ($t['source'] ?? '') === 'scan_share'), 'scan') ?>
        </div>
        <!-- Processed (patient_id not null) -->
        <div class="tab-pane fade" id="tabProcessed">
            <?= renderTokenTable(array_filter($tokens ?? [], fn($t) => ! empty($t['patient_id'])), 'processed') ?>
        </div>
        <!-- Unprocessed (patient_id null) -->
        <div class="tab-pane fade" id="tabUnprocessed">
            <?= renderTokenTable(array_filter($tokens ?? [], fn($t) => empty($t['patient_id'])), 'unprocessed') ?>
        </div>
    </div>

</div>

<?php
function renderTokenTable(array $rows, string $ctx): string
{
    if (count($rows) === 0) {
        return '<div class="text-center py-4 text-muted">No tokens in this category.</div>';
    }

    $html  = '<div class="card shadow-sm"><div class="card-body p-0">';
    $html .= '<div class="table-responsive">';
    $html .= '<table class="table table-sm table-hover align-middle mb-0">';
    $html .= '<thead class="table-light"><tr>
        <th>#</th><th>Token</th><th>Patient Name</th>
        <th>ABHA</th><th>Dept</th><th>Source</th><th>Status</th>
        <th>HMS Patient</th><th>Processed At</th>
    </tr></thead><tbody>';

    $i = 1;
    foreach ($rows as $t) {
        $status  = strtoupper((string) ($t['status'] ?? 'PENDING'));
        $source  = (string) ($t['source'] ?? 'manual');
        $pid     = (int) ($t['patient_id'] ?? 0);
        $abha    = (string) ($t['abha_number'] ?? '');
        $abhaFmt = '';
        if (strlen($abha) === 14) {
            $abhaFmt = substr($abha, 0, 2) . '-' . substr($abha, 2, 4) . '-' . substr($abha, 6, 4) . '-' . substr($abha, 10);
        } else {
            $abhaFmt = $abha ?: ($t['abha_address'] ?? '—');
        }

        $srcBadge = $source === 'scan_share'
            ? '<span class="badge badge-scan px-2">ABHA Scan</span>'
            : '<span class="badge badge-manual px-2">Walk-in</span>';

        $statusBadge = '<span class="badge status-' . esc($status) . ' px-2 rounded-pill">' . esc($status) . '</span>';

        if ($pid > 0) {
            $pCode = esc((string) ($t['p_code'] ?? ''));
            $pName = esc((string) ($t['p_fname'] ?? $t['patient_name'] ?? '—'));
            $hmsLink = '<a href="javascript:load_form(\'' . base_url('Patient/person_profile/' . $pid) . '\',\'Patient Profile\')">'
                     . '<strong>' . $pCode . '</strong> ' . $pName . '</a>';
        } else {
            $hmsLink = '<span class="text-muted small">Not linked</span>';
        }

        $procAt = ! empty($t['processed_at'])
            ? date('d/m/y H:i', strtotime($t['processed_at']))
            : '—';

        $html .= "<tr>
            <td>{$i}</td>
            <td><strong>" . esc((string) ($t['token_number'] ?? $t['gateway_token_id'])) . "</strong></td>
            <td>" . esc((string) ($t['patient_name'] ?? '—')) . "<br>
                <span class='small text-muted'>" . esc((string) ($t['phone'] ?? '')) . "</span></td>
            <td><span class='abha-num'>{$abhaFmt}</span></td>
            <td>" . esc((string) ($t['department'] ?? 'General OPD')) . "</td>
            <td>{$srcBadge}</td>
            <td>{$statusBadge}</td>
            <td>{$hmsLink}</td>
            <td class='small text-muted'>{$procAt}</td>
        </tr>";
        $i++;
    }

    $html .= '</tbody></table></div></div></div>';
    return $html;
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

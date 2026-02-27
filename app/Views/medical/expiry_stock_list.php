<?php
$rows = $purchase_list ?? [];
$todayTs = strtotime(date('Y-m-d'));
$expirySummary = [
    'expired' => 0,
    'd30' => 0,
    'd60' => 0,
    'later' => 0,
];

foreach ($rows as $summaryRow) {
    $expiryTs = strtotime((string) ($summaryRow->expiry_date ?? ''));
    if ($expiryTs === false) {
        $expirySummary['later']++;
        continue;
    }

    $daysToExpiry = (int) floor(($expiryTs - $todayTs) / 86400);
    if ($daysToExpiry < 0) {
        $expirySummary['expired']++;
    } elseif ($daysToExpiry <= 30) {
        $expirySummary['d30']++;
    } elseif ($daysToExpiry <= 60) {
        $expirySummary['d60']++;
    } else {
        $expirySummary['later']++;
    }
}
?>

<div class="card">
    <div class="card-body pt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="card-title mb-0">Nearest Expiry / Expired Medicine Stock</h5>
            <div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= base_url('Medical/expire_stock_pdf') ?>">Print (PDF)</a>
                <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('Medical/master') ?>','medical-main','Master :Pharmacy');">Back to Master</a>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge text-bg-danger">Expired</span>
            <span class="badge text-bg-warning">Expiring in 30 days</span>
            <span class="badge text-bg-info">Expiring in 2 months</span>
            <span class="badge text-bg-light border">Above 2 months</span>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-3 col-6">
                <div class="border rounded p-2 h-100">
                    <div class="small text-muted">Expired</div>
                    <div class="h6 mb-0 text-danger"><?= esc((string) ($expirySummary['expired'] ?? 0)) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="border rounded p-2 h-100">
                    <div class="small text-muted">Expiring in 30 days</div>
                    <div class="h6 mb-0 text-warning"><?= esc((string) ($expirySummary['d30'] ?? 0)) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="border rounded p-2 h-100">
                    <div class="small text-muted">Expiring in 2 months</div>
                    <div class="h6 mb-0 text-info"><?= esc((string) ($expirySummary['d60'] ?? 0)) ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="border rounded p-2 h-100">
                    <div class="small text-muted">Above 2 months</div>
                    <div class="h6 mb-0"><?= esc((string) ($expirySummary['later'] ?? 0)) ?></div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered table-sm align-middle">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>Supplier</th>
                    <th>Pur. Date</th>
                    <th>Batch</th>
                    <th>Exp. Dt</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">C.Qty</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $srno = 1;
                foreach ($rows as $row):
                    $statusLabel = 'Above 2 months';
                    $statusClass = '';

                    $expiryDateRaw = (string) ($row->expiry_date ?? '');
                    $expiryTs = strtotime($expiryDateRaw);
                    $daysToExpiry = null;

                    if ($expiryTs !== false) {
                        $daysToExpiry = (int) floor(($expiryTs - $todayTs) / 86400);

                        if ($daysToExpiry < 0) {
                            $statusLabel = 'Expired';
                            $statusClass = 'table-danger';
                        } elseif ($daysToExpiry <= 30) {
                            $statusLabel = 'Expiring in 30 days';
                            $statusClass = 'table-warning';
                        } elseif ($daysToExpiry <= 60) {
                            $statusLabel = 'Expiring in 2 months';
                            $statusClass = 'table-info';
                        }
                    }
                ?>
                    <tr class="<?= esc($statusClass) ?>">
                        <td><?= esc((string) $srno++) ?></td>
                        <td><?= esc($row->item_name ?? $row->Item_name ?? '') ?></td>
                        <td><?= esc($row->name_supplier ?? '') ?></td>
                        <td><?= esc($row->str_date_of_invoice ?? '') ?></td>
                        <td><?= esc($row->batch_no ?? '') ?></td>
                        <td><?= esc($row->exp_date ?? '') ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($row->mrp ?? 0), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($row->tqty ?? 0), 2)) ?></td>
                        <td class="text-end"><?= esc(number_format((float) ($row->cur_qty ?? 0), 2)) ?></td>
                        <td>
                            <?= esc($statusLabel) ?>
                            <?php if ($daysToExpiry !== null): ?>
                                <span class="text-muted small">(<?= esc((string) $daysToExpiry) ?> days)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

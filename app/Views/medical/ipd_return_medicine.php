<?php
    $ipdId = (int) ($ipd->id ?? 0);
    $ipdCode = (string) ($ipd->ipd_code ?? ('IPD-' . $ipdId));
    $patientName = (string) ($patient->p_fname ?? '-');
    $patientCode = (string) ($patient->p_code ?? '-');
    $modeLabel = (string) ($modeLabel ?? 'Medicine Reurn');

    $totalInvoices = !empty($invoices) ? count($invoices) : 0;
    $totalItems = 0;
    $totalReturnedItems = 0;
    foreach (($invoices ?? []) as $invRow) {
        $invId = (int) ($invRow->id ?? 0);
        $totalItems += (int) ($itemCountMap[$invId] ?? 0);
        $totalReturnedItems += (int) ($returnCountMap[$invId] ?? 0);
    }
    $pendingItems = max(0, $totalItems - $totalReturnedItems);
?>

<div class="card border-0">
    <div class="card-header bg-light border-bottom border-danger border-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Medical Return Items <small class="text-muted"><?= esc($modeLabel) ?></small></h5>
        <div class="d-flex gap-2">
            <a class="btn btn-success btn-sm" href="javascript:load_form_div('<?= base_url('Medical/list_med_inv/' . $ipdId) ?>','medical-main');">Back to IPD Invoices</a>
            <a class="btn btn-outline-secondary btn-sm" target="_blank" href="<?= base_url('Medical/ipd_print/' . $ipdId . '/return-list') ?>">Print Return List</a>
        </div>
    </div>

    <div class="card-body">
        <div class="mb-3">
            <strong>Patient Code :</strong> <?= esc($patientCode) ?> /
            <strong>Customer Name :</strong> <?= esc($patientName) ?> /
            <strong>IPD Code :</strong>
            <a href="javascript:load_form_div('<?= base_url('Medical/list_med_inv/' . $ipdId) ?>','medical-main');"><?= esc($ipdCode) ?></a>
        </div>

        <div class="mb-3" style="max-width:420px;">
            <label class="form-label form-label-sm">Search Invoice / Code</label>
            <input type="text" id="return-invoice-filter" class="form-control form-control-sm" placeholder="Type invoice code or date">
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <div class="border rounded p-2 bg-light">
                    <div class="small text-muted">Invoices</div>
                    <div class="fw-bold"><?= esc((string) $totalInvoices) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-2 bg-light">
                    <div class="small text-muted">Total Items</div>
                    <div class="fw-bold"><?= esc((string) $totalItems) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-2 bg-light">
                    <div class="small text-muted">Returned Items</div>
                    <div class="fw-bold text-success"><?= esc((string) $totalReturnedItems) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-2 <?= $pendingItems > 0 ? 'bg-danger-subtle border-danger' : 'bg-success-subtle border-success' ?>">
                    <div class="small text-muted">Pending</div>
                    <div class="fw-bold <?= $pendingItems > 0 ? 'text-danger' : 'text-success' ?>"><?= esc((string) $pendingItems) ?></div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle" id="return-invoice-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Invoice ID</th>
                        <th>Date</th>
                        <th>Total Items</th>
                        <th>Returned Items</th>
                        <th>Status</th>
                        <th>Net Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($invoices)): ?>
                        <?php foreach ($invoices as $i => $row): ?>
                            <?php
                                $invId = (int) ($row->id ?? 0);
                                $totalItems = (int) ($itemCountMap[$invId] ?? 0);
                                $returnedItems = (int) ($returnCountMap[$invId] ?? 0);
                                $pendingForInvoice = max(0, $totalItems - $returnedItems);
                            ?>
                            <tr class="<?= $pendingForInvoice > 0 ? 'table-danger' : 'table-success' ?>">
                                <td><?= $i + 1 ?></td>
                                <td><?= esc($row->inv_med_code ?? ('M' . date('ym') . str_pad(substr((string) $invId, -7, 7), 7, '0', STR_PAD_LEFT))) ?></td>
                                <td><?= esc(!empty($row->inv_date) ? date('Y-m-d', strtotime((string)$row->inv_date)) : '-') ?></td>
                                <td><?= esc((string) $totalItems) ?></td>
                                <td><?= esc((string) $returnedItems) ?></td>
                                <td>
                                    <?php if ($pendingForInvoice > 0): ?>
                                        <span class="badge bg-danger">Pending <?= esc((string) $pendingForInvoice) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc(number_format((float)($row->net_amount ?? 0), 2)) ?></td>
                                <td>
                                    <a class="btn btn-sm btn-primary" href="javascript:load_form_div('<?= base_url('Medical/invoice_edit/' . $invId) ?>','medical-main');">Open Bill For Return</a>
                                    <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('Medical/final_invoice/' . $invId) ?>','medical-main');">Final View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No invoices found for this IPD.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="small text-muted mt-2">
            Open bill for return, then use right-side R.Qty flow to add return items.
        </div>
    </div>
</div>

<script>
    (function () {
        var input = document.getElementById('return-invoice-filter');
        var table = document.getElementById('return-invoice-table');
        if (!input || !table) {
            return;
        }

        input.addEventListener('input', function () {
            var q = (input.value || '').toLowerCase();
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function (tr) {
                var text = (tr.textContent || '').toLowerCase();
                tr.style.display = text.indexOf(q) >= 0 ? '' : 'none';
            });
        });
    })();
</script>

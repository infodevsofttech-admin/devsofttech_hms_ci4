<section class="content finance-compliance-report">
    <div class="mb-3">
        <h2 class="mb-1">Finance Compliance Report</h2>
        <p class="text-muted mb-0">Consolidated risk view across invoice matching, cash compliance, deposits, and doctor payouts.</p>
    </div>

    <div class="card mb-3">
        <div class="card-body py-3">
            <form method="get" action="<?= base_url('Finance/compliance_report') ?>" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">From Date</label>
                    <input type="date" class="form-control" name="from" value="<?= esc((string) ($from ?? '')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">To Date</label>
                    <input type="date" class="form-control" name="to" value="<?= esc((string) ($to ?? '')) ?>">
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary btn-sm">Apply Filter</button>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= base_url('Finance/compliance_report') ?>">Reset</a>
                    <a class="btn btn-outline-success btn-sm" href="<?= base_url('Finance/compliance_report') . '?from=' . urlencode((string) ($from ?? '')) . '&to=' . urlencode((string) ($to ?? '')) . '&export=1' ?>">Export CSV</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-2 mb-2">
        <div class="col-md-3 col-6"><div class="card border-danger"><div class="card-body py-2"><div class="small text-muted">Invoice Holds</div><div class="h5 mb-0 text-danger"><?= (int) ($summary['invoice_holds'] ?? 0) ?></div></div></div></div>
        <div class="col-md-3 col-6"><div class="card border-danger"><div class="card-body py-2"><div class="small text-muted">Cash Holds</div><div class="h5 mb-0 text-danger"><?= (int) ($summary['cash_holds'] ?? 0) ?></div></div></div></div>
        <div class="col-md-3 col-6"><div class="card border-warning"><div class="card-body py-2"><div class="small text-muted">Pending Deposits</div><div class="h5 mb-0 text-warning"><?= (int) ($summary['pending_deposits'] ?? 0) ?></div></div></div></div>
        <div class="col-md-3 col-6"><div class="card border-info"><div class="card-body py-2"><div class="small text-muted">Draft/Unpaid Payouts</div><div class="h5 mb-0 text-info"><?= (int) ($summary['pending_payouts'] ?? 0) ?></div></div></div></div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Cash Compliance Alerts (Latest 20)</strong></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead><tr><th>Date</th><th>Type</th><th>Department</th><th>Amount</th><th>Note</th></tr></thead>
                    <tbody>
                        <?php if (empty($cash_alerts ?? [])): ?>
                            <tr><td colspan="5" class="text-center text-muted">No cash alerts.</td></tr>
                        <?php else: foreach (($cash_alerts ?? []) as $row): ?>
                            <tr>
                                <td><?= esc((string) ($row['txn_date'] ?? '')) ?></td>
                                <td><?= esc((string) ($row['txn_type'] ?? '')) ?></td>
                                <td><?= esc((string) ($row['department'] ?? '')) ?></td>
                                <td><?= number_format((float) ($row['amount'] ?? 0), 2) ?></td>
                                <td><?= esc((string) ($row['compliance_note'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Invoice Match Exceptions (Latest 20)</strong></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead><tr><th>Date</th><th>Invoice</th><th>Vendor</th><th>Status</th><th>Variance</th><th>Note</th></tr></thead>
                    <tbody>
                        <?php if (empty($invoice_exceptions ?? [])): ?>
                            <tr><td colspan="6" class="text-center text-muted">No invoice exceptions.</td></tr>
                        <?php else: foreach (($invoice_exceptions ?? []) as $row): ?>
                            <tr>
                                <td><?= esc((string) ($row['invoice_date'] ?? '')) ?></td>
                                <td><?= esc((string) ($row['invoice_no'] ?? '')) ?></td>
                                <td><?= esc((string) ($row['vendor_name'] ?? '')) ?></td>
                                <td><?= esc((string) ($row['match_status'] ?? '')) ?></td>
                                <td><?= number_format((float) ($row['variance_amount'] ?? 0), 2) ?></td>
                                <td><?= esc((string) ($row['match_note'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<section class="content finance-phase2">
    <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h2 class="mb-1">Finance &amp; Accounting - Phase 2</h2>
            <p class="text-muted mb-0">Consolidation layer for receivables, refunds, pharmacy purchase finance, and organization credit reporting.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-primary btn-sm js-p2-launch active"
                    data-target="p2-dashboard" data-title="Phase 2 Dashboard">
                <i class="bi bi-speedometer2"></i> Dashboard
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm js-p2-launch"
                    data-url="<?= base_url('Finance/section/pharmacy_bills') ?>"
                    data-target="p2-section"
                    data-title="Pharmacy Bills">
                <i class="bi bi-capsule"></i> Pharmacy Bills (Payable)
            </button>
        </div>
    </div>

    <!-- â”€â”€ Dashboard panel â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div id="p2-dashboard">

        <div class="alert alert-info py-2">
            Phase 2 started on <strong><?= esc((string) ($phase_started_at ?? '')) ?></strong>.
        </div>

        <!-- Pharmacy Bills Payable KPIs -->
        <?php $pb = $pharm_bill_summary ?? []; ?>
        <?php if (($pb['total_bills'] ?? 0) > 0): ?>
        <div class="card border-danger mb-3">
            <div class="card-header py-2 bg-danger text-white">
                <strong><i class="bi bi-capsule"></i> Pharmacy Bills &mdash; Payable to Pharmacy (Cr. to Hospital)</strong>
            </div>
            <div class="card-body py-2">
                <div class="row g-2">
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-2 text-center">
                            <div class="small text-muted">Total Bills</div>
                            <div class="h5 mb-0"><?= (int) ($pb['total_bills'] ?? 0) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-2 text-center">
                            <div class="small text-muted">Net Payable (Total)</div>
                            <div class="h5 mb-0 text-danger">Rs. <?= number_format((float) ($pb['total_net'] ?? 0), 2) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-2 text-center">
                            <div class="small text-muted">Total Paid</div>
                            <div class="h5 mb-0 text-success">Rs. <?= number_format((float) ($pb['total_paid'] ?? 0), 2) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-2 text-center">
                            <div class="small text-muted">Outstanding Balance</div>
                            <div class="h5 mb-0 text-warning">Rs. <?= number_format((float) ($pb['total_pending'] ?? 0), 2) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-header"><strong>Finance Phase Design</strong></div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th style="width: 90px;">Phase</th>
                            <th>Scope</th>
                            <th style="width: 120px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($phase_plan ?? []) as $row): ?>
                            <?php $status = (string) ($row['status'] ?? 'planned'); ?>
                            <tr>
                                <td><?= (int) ($row['phase'] ?? 0) ?></td>
                                <td><?= esc((string) ($row['name'] ?? '')) ?></td>
                                <td>
                                    <?php if ($status === 'live'): ?>
                                        <span class="badge bg-success">Live</span>
                                    <?php elseif ($status === 'started'): ?>
                                        <span class="badge bg-primary">Started</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Planned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-4 col-6">
                <div class="card border-primary"><div class="card-body py-2"><div class="small text-muted">Total Billed (AR)</div><div class="h5 mb-0 text-primary"><?= esc(number_format((float) ($receivable_summary['total_billed'] ?? 0), 2)) ?></div></div></div>
            </div>
            <div class="col-md-4 col-6">
                <div class="card border-warning"><div class="card-body py-2"><div class="small text-muted">Total Outstanding</div><div class="h5 mb-0 text-warning"><?= esc(number_format((float) ($receivable_summary['total_outstanding'] ?? 0), 2)) ?></div></div></div>
            </div>
            <div class="col-md-4 col-6">
                <div class="card border-info"><div class="card-body py-2"><div class="small text-muted">Total Collected</div><div class="h5 mb-0 text-info"><?= esc(number_format((float) ($receivable_summary['total_collected'] ?? 0), 2)) ?></div></div></div>
            </div>
            <div class="col-md-4 col-6">
                <div class="card border-secondary"><div class="card-body py-2"><div class="small text-muted">Pending Invoices</div><div class="h5 mb-0"><?= (int) ($receivable_summary['pending_invoice_count'] ?? 0) ?></div></div></div>
            </div>
            <div class="col-md-4 col-6">
                <div class="card border-dark"><div class="card-body py-2"><div class="small text-muted">Organization Cases</div><div class="h5 mb-0"><?= (int) ($organization_summary['case_count'] ?? 0) ?></div></div></div>
            </div>
            <div class="col-md-4 col-6">
                <div class="card border-danger"><div class="card-body py-2"><div class="small text-muted">Org Outstanding Exposure</div><div class="h5 mb-0 text-danger"><?= esc(number_format((float) ($organization_summary['total_outstanding'] ?? 0), 2)) ?></div></div></div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Receivable Stream Summary (Phase 2 Bridge)</strong></div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Stream</th>
                            <th class="text-end">Invoices</th>
                            <th class="text-end">Pending</th>
                            <th class="text-end">Billed</th>
                            <th class="text-end">Collected</th>
                            <th class="text-end">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($receivable_summary['streams'] ?? []) as $stream): ?>
                            <tr>
                                <td><?= esc((string) ($stream['label'] ?? '')) ?></td>
                                <td class="text-end"><?= (int) ($stream['invoice_count'] ?? 0) ?></td>
                                <td class="text-end"><?= (int) ($stream['pending_count'] ?? 0) ?></td>
                                <td class="text-end"><?= esc(number_format((float) ($stream['billed_total'] ?? 0), 2)) ?></td>
                                <td class="text-end"><?= esc(number_format((float) ($stream['collected_total'] ?? 0), 2)) ?></td>
                                <td class="text-end"><?= esc(number_format((float) ($stream['outstanding_total'] ?? 0), 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body py-2"><div class="small text-muted">Refund Requests</div><div class="h5 mb-0"><?= (int) ($bridge_metrics['refund_requests_total'] ?? 0) ?></div></div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body py-2"><div class="small text-muted">Medical Purchase Invoices</div><div class="h5 mb-0"><?= (int) ($bridge_metrics['medical_purchase_invoices_total'] ?? 0) ?></div></div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body py-2"><div class="small text-muted">Supplier Ledger Entries</div><div class="h5 mb-0"><?= (int) ($bridge_metrics['supplier_ledger_entries_total'] ?? 0) ?></div></div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card"><div class="card-body py-2"><div class="small text-muted">Charge Invoices (OPD)</div><div class="h5 mb-0"><?= (int) ($bridge_metrics['charge_invoices_total'] ?? 0) ?></div></div></div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Refund Lifecycle Bridge (Phase 2)</strong></div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted">Requested</div>
                            <div class="fw-semibold"><?= (int) ($refund_summary['total_requests'] ?? 0) ?> / Rs. <?= esc(number_format((float) ($refund_summary['total_requested_amount'] ?? 0), 2)) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted">Pending</div>
                            <div class="fw-semibold text-warning"><?= (int) ($refund_summary['pending_count'] ?? 0) ?> / Rs. <?= esc(number_format((float) ($refund_summary['pending_amount'] ?? 0), 2)) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted">Completed (Cash Impact)</div>
                            <div class="fw-semibold text-success"><?= (int) ($refund_summary['completed_count'] ?? 0) ?> / Rs. <?= esc(number_format((float) ($refund_summary['completed_amount'] ?? 0), 2)) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted">Cancelled</div>
                            <div class="fw-semibold text-secondary"><?= (int) ($refund_summary['cancelled_count'] ?? 0) ?> / Rs. <?= esc(number_format((float) ($refund_summary['cancelled_amount'] ?? 0), 2)) ?></div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Refund Type</th>
                                <th class="text-end">Requests</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-end">Pending</th>
                                <th class="text-end">Pending Amount</th>
                                <th class="text-end">Completed Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($refund_summary['by_type'] ?? []) as $row): ?>
                                <tr>
                                    <td><?= esc((string) ($row['type_label'] ?? 'Other')) ?></td>
                                    <td class="text-end"><?= (int) ($row['total_requests'] ?? 0) ?></td>
                                    <td class="text-end"><?= esc(number_format((float) ($row['total_amount'] ?? 0), 2)) ?></td>
                                    <td class="text-end"><?= (int) ($row['pending_count'] ?? 0) ?></td>
                                    <td class="text-end"><?= esc(number_format((float) ($row['pending_amount'] ?? 0), 2)) ?></td>
                                    <td class="text-end"><?= esc(number_format((float) ($row['completed_amount'] ?? 0), 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Phase 2 Workstream Start</strong></div>
            <div class="card-body">
                <ol class="mb-0">
                    <li>Receivable summary bridge from patient billing (OPD/IPD/Org) into Finance reporting.</li>
                    <li>Refund lifecycle bridge into Finance compliance and cash register reporting.</li>
                    <li><strong>Pharmacy purchase bills (payable) from the Pharmacy entity â€” tracked here as Cr. to Hospital.</strong></li>
                    <li>Organization credit exposure summary for insurance and packaged billing.</li>
                </ol>
            </div>
        </div>

    </div>
    <!-- â”€â”€ end Dashboard panel â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->

    <!-- â”€â”€ Dynamic section panel (Pharmacy Bills etc.) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
    <div id="p2-section" class="d-none"></div>
    <!-- â”€â”€ end section panel â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->

</section>

<script>
(function () {
    var launchers  = Array.prototype.slice.call(document.querySelectorAll('.js-p2-launch'));
    var dashboard  = document.getElementById('p2-dashboard');
    var sectionDiv = document.getElementById('p2-section');

    launchers.forEach(function (btn) {
        btn.addEventListener('click', function () {
            launchers.forEach(function (b) {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-primary');
            });
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-primary');

            var url = btn.getAttribute('data-url') || '';
            if (!url) {
                // Dashboard toggle
                dashboard.classList.remove('d-none');
                sectionDiv.classList.add('d-none');
                sectionDiv.innerHTML = '';
                return;
            }

            dashboard.classList.add('d-none');
            sectionDiv.classList.remove('d-none');
            load_form_div(url, 'p2-section', btn.getAttribute('data-title') || 'Section');
        });
    });
})();
</script>

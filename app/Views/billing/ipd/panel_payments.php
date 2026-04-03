<div class="row g-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">IPD Payments</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ID</th>
                                <th>Date</th>
                                <th class="text-end">Amount</th>
                                <th>Mode</th>
                                <th>Print</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($payments)) : ?>
                                <?php $srNo = 1; ?>
                                <?php foreach ($payments as $row) : ?>
                                    <tr>
                                        <td><?= $srNo++ ?></td>
                                        <td><?= esc($row->id ?? '') ?></td>
                                        <td><?= esc($row->pay_date_str ?? '') ?></td>
                                        <td class="text-end"><?= esc($row->amount ?? '') ?></td>
                                        <td><?= esc($row->pay_mode ?? '') ?></td>
                                        <td>
                                            <a href="<?= site_url('billing/ipd/payment/pdf-receipt/' . (int)($ipd_info->id ?? 0) . '/' . (int)($row->id ?? 0)) ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Print Receipt">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No payments found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Inventory Dashboard</h5>
        <span class="badge bg-primary">Focus: Expiry, Short Item, New Request</span>
    </div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-2"><div class="p-2 border rounded"><small>Items</small><h5 class="mb-0"><?= (int) ($stats['total_items'] ?? 0) ?></h5></div></div>
            <div class="col-6 col-md-2"><div class="p-2 border rounded"><small>Low Stock</small><h5 class="mb-0 text-danger"><?= (int) ($stats['low_stock'] ?? 0) ?></h5></div></div>
            <div class="col-6 col-md-2"><div class="p-2 border rounded"><small>Pending Indents</small><h5 class="mb-0 text-warning"><?= (int) ($stats['pending_indents'] ?? 0) ?></h5></div></div>
            <div class="col-6 col-md-2"><div class="p-2 border rounded"><small>Open PO</small><h5 class="mb-0 text-info"><?= (int) ($stats['open_po'] ?? 0) ?></h5></div></div>
            <div class="col-12 col-md-4"><div class="p-2 border rounded"><small>Stock Value</small><h5 class="mb-0 text-success">₹<?= number_format((float) ($stats['stock_value'] ?? 0), 2) ?></h5></div></div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <h6>New Request (Pending Indent)</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped" id="tblNewReq">
                        <thead><tr><th>Indent</th><th>Department</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach (($newRequests ?? []) as $r): ?>
                            <tr>
                                <td><?= esc((string) ($r['indent_code'] ?? '')) ?></td>
                                <td><?= esc((string) ($r['department_name'] ?? '')) ?></td>
                                <td><?= esc((string) ($r['created_at'] ?? '')) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <h6>Regularly Used Items Near Expiry</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped" id="tblNearExpiryUsed">
                        <thead><tr><th>Item</th><th>Used Qty</th><th>Stock</th><th>Expiry</th></tr></thead>
                        <tbody>
                            <?php foreach (($nearExpiryFrequent ?? []) as $r): ?>
                            <tr>
                                <td><?= esc((string) ($r['item_code'] ?? '')) ?> - <?= esc((string) ($r['name'] ?? '')) ?></td>
                                <td><?= esc((string) ($r['used_qty'] ?? '0')) ?></td>
                                <td><?= esc((string) ($r['current_stock'] ?? '0')) ?></td>
                                <td><?= esc((string) ($r['expiry_date'] ?? '')) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <h6>Short Item with No Replenishment Request</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped" id="tblShortNoReq">
                        <thead><tr><th>Item</th><th>Stock</th><th>Reorder</th></tr></thead>
                        <tbody>
                            <?php foreach (($lowStockNoRequest ?? []) as $r): ?>
                            <tr>
                                <td><?= esc((string) ($r['item_code'] ?? '')) ?> - <?= esc((string) ($r['name'] ?? '')) ?></td>
                                <td class="text-danger"><?= esc((string) ($r['current_stock'] ?? '0')) ?></td>
                                <td><?= esc((string) ($r['reorder_level'] ?? '0')) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <h6>Daily Use Item Status</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped" id="tblDailyStatus">
                        <thead><tr><th>Item</th><th>Today</th><th>7d Use</th><th>Stock</th></tr></thead>
                        <tbody>
                            <?php foreach (($dailyUseStatus ?? []) as $r): ?>
                            <tr>
                                <td><?= esc((string) ($r['item_code'] ?? '')) ?> - <?= esc((string) ($r['name'] ?? '')) ?></td>
                                <td><?= esc((string) ($r['issued_today'] ?? '0')) ?></td>
                                <td><?= esc((string) ($r['issued_7d'] ?? '0')) ?></td>
                                <td><?= esc((string) ($r['current_stock'] ?? '0')) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function(){
    if ($.fn && $.fn.DataTable) {
        $('#tblNewReq').DataTable({pageLength:5, order:[[2,'desc']]});
        $('#tblNearExpiryUsed').DataTable({pageLength:5, order:[[3,'asc']]});
        $('#tblShortNoReq').DataTable({pageLength:5, order:[[1,'asc']]});
        $('#tblDailyStatus').DataTable({pageLength:5, order:[[0,'asc']]});
    }
})();
</script>

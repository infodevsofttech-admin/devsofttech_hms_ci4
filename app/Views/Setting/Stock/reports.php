<div class="card">
    <div class="card-header"><h5 class="mb-0">Stock Reports & Alerts</h5></div>
    <div class="card-body">
        <div class="row g-2 align-items-end mb-3" id="reportFilterForm">
            <div class="col-md-2">
                <label class="form-label">From</label>
                <input class="form-control" type="date" id="dateFrom" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">To</label>
                <input class="form-control" type="date" id="dateTo" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Near Expiry (Days)</label>
                <input class="form-control" type="number" id="expiryDays" min="1" max="3650" value="60">
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm" type="button" id="btnDeptConsumption">Department Consumption</button>
                <button class="btn btn-outline-secondary btn-sm" type="button" id="btnMonthlyIssue">Monthly Issue</button>
                <button class="btn btn-outline-danger btn-sm" type="button" id="btnNearExpiry">Near Expiry</button>
            </div>
        </div>

        <div class="alert alert-info mb-3">
            Reports open in print-ready format in a new tab.
        </div>

        <h6>Current Alert Items (Low / Reorder)</h6>
        <table class="table table-sm table-striped" id="stockAlertsTable">
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Current Stock</th>
                    <th>Min Stock</th>
                    <th>Reorder Level</th>
                    <th>Expiry Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach (($alerts ?? []) as $r): ?>
                <tr>
                    <td><?= esc((string) ($r['item_code'] ?? '')) ?></td>
                    <td><?= esc((string) ($r['name'] ?? '')) ?></td>
                    <td><?= esc((string) ($r['current_stock'] ?? '0')) ?></td>
                    <td><?= esc((string) ($r['min_stock_level'] ?? '0')) ?></td>
                    <td><?= esc((string) ($r['reorder_level'] ?? '0')) ?></td>
                    <td><?= esc((string) ($r['expiry_date'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function(){
    function q(v){ return encodeURIComponent(v || ''); }

    $('#btnDeptConsumption').on('click', function(){
        var from = $('#dateFrom').val();
        var to = $('#dateTo').val();
        var url = '<?= base_url('setting/admin/hospital-stock/report/department-consumption') ?>' + '?from=' + q(from) + '&to=' + q(to);
        window.open(url, '_blank');
    });

    $('#btnMonthlyIssue').on('click', function(){
        var year = (new Date($('#dateFrom').val() || new Date())).getFullYear();
        var url = '<?= base_url('setting/admin/hospital-stock/report/monthly-issue') ?>' + '?year=' + q(year);
        window.open(url, '_blank');
    });

    $('#btnNearExpiry').on('click', function(){
        var days = $('#expiryDays').val() || 60;
        var url = '<?= base_url('setting/admin/hospital-stock/report/near-expiry') ?>' + '?days=' + q(days);
        window.open(url, '_blank');
    });

    if($.fn && $.fn.DataTable){
        $('#stockAlertsTable').DataTable({pageLength:15, order:[[2, 'asc']]});
    }
})();
</script>

<section class="content finance-bank-deposit">
    <div class="mb-3">
        <h2 class="mb-1">Bank Deposit Register</h2>
        <p class="text-muted mb-0">Track cash to bank deposits and reconcile with submitted scrolls.</p>
    </div>

    <div id="deposit_alert"></div>

    <div class="row g-2 mb-2">
        <div class="col-md-4 col-6"><div class="card border-primary"><div class="card-body py-2"><div class="small text-muted">Today Deposited</div><div class="h5 mb-0 text-primary"><?= number_format((float) ($summary['today_deposit'] ?? 0), 2) ?></div></div></div></div>
        <div class="col-md-4 col-6"><div class="card border-warning"><div class="card-body py-2"><div class="small text-muted">Pending Reconciliation</div><div class="h5 mb-0 text-warning"><?= (int) ($summary['pending_count'] ?? 0) ?></div></div></div></div>
        <div class="col-md-4 col-12"><div class="card border-success"><div class="card-body py-2"><div class="small text-muted">Matched Deposits</div><div class="h5 mb-0 text-success"><?= (int) ($summary['matched_count'] ?? 0) ?></div></div></div></div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Deposit Entry</strong></div>
        <div class="card-body">
            <form id="bank_deposit_form" class="row g-2">
                <div class="col-md-3"><input type="date" class="form-control" name="deposit_date" value="<?= date('Y-m-d') ?>" required></div>
                <div class="col-md-3"><input type="text" class="form-control" name="department" placeholder="Department" required></div>
                <div class="col-md-3"><input type="text" class="form-control" name="bank_name" placeholder="Bank Name" required></div>
                <div class="col-md-3"><input type="text" class="form-control" name="slip_no" placeholder="Slip No"></div>
                <div class="col-md-3"><input type="number" step="0.01" class="form-control" name="deposited_amount" placeholder="Deposited Amount" required></div>
                <div class="col-md-3">
                    <select class="form-select" name="related_scroll_id">
                        <option value="">Related Scroll (optional)</option>
                        <?php foreach (($scroll_options ?? []) as $s): ?>
                            <option value="<?= (int) ($s['id'] ?? 0) ?>">#<?= (int) ($s['id'] ?? 0) ?> - <?= esc((string) ($s['department'] ?? '')) ?> (<?= esc((string) ($s['scroll_date'] ?? '')) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6"><input type="text" class="form-control" name="remarks" placeholder="Remarks"></div>
                <div class="col-12"><button type="submit" class="btn btn-primary btn-sm">Save Deposit</button></div>
            </form>
            <hr>
            <div id="bank_deposit_table_wrap"><?= view('finance/partials/bank_deposits_table', ['deposits' => $deposits ?? []]) ?></div>
        </div>
    </div>
</section>

<script>
(function() {
    function showAlert(message, ok) {
        var cls = ok ? 'alert-success' : 'alert-danger';
        var html = '<div class="alert ' + cls + ' alert-dismissible fade show" role="alert">' + message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        var box = document.getElementById('deposit_alert');
        if (box) box.innerHTML = html;
    }

    function refreshTable() {
        load_form_div('<?= base_url('Finance/bank_deposits_table') ?>', 'bank_deposit_table_wrap');
    }

    var form = document.getElementById('bank_deposit_form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var fd = new window.FormData(form);
            fetch('<?= base_url('Finance/bank_deposit_create') ?>', {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: fd
            })
            .then(function(res) { return res.json().then(function(data) { return { ok: res.ok, data: data }; }); })
            .then(function(result) {
                if (!result.ok || !result.data || result.data.status !== 1) {
                    showAlert((result.data && result.data.message) ? result.data.message : 'Request failed', false);
                    return;
                }
                showAlert(result.data.message || 'Saved successfully', true);
                form.reset();
                refreshTable();
            })
            .catch(function() { showAlert('Network or server error.', false); });
        });
    }
})();
</script>

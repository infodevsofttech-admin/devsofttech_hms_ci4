<div class="card">
    <div class="card-header"><strong>4) Vendor Invoice Booking</strong></div>
    <div class="card-body">
        <div id="finance_section_alert"></div>
        <form id="invoice_form" class="row g-2">
            <div class="col-md-4"><input type="text" class="form-control" name="invoice_no" placeholder="Invoice No" required></div>
            <div class="col-md-4"><input type="date" class="form-control" name="invoice_date" required></div>
            <div class="col-md-4">
                <select class="form-select" name="vendor_id" required>
                    <option value="">Vendor</option>
                    <?php foreach (($vendor_options ?? []) as $v): ?>
                        <option value="<?= (int) ($v['id'] ?? 0) ?>"><?= esc((string) ($v['vendor_name'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="po_id">
                    <option value="">PO (optional)</option>
                    <?php foreach (($po_options ?? []) as $po): ?>
                        <option value="<?= (int) ($po['id'] ?? 0) ?>"><?= esc((string) ($po['po_no'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="grn_id">
                    <option value="">GRN (optional)</option>
                    <?php foreach (($grn_options ?? []) as $grn): ?>
                        <option value="<?= (int) ($grn['id'] ?? 0) ?>"><?= esc((string) ($grn['grn_no'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4"><input type="number" step="0.01" class="form-control" name="invoice_amount" placeholder="Invoice Amount"></div>
            <div class="col-md-4">
                <select class="form-select" name="payment_status">
                    <option value="pending">Pending</option>
                    <option value="part_paid">Part Paid</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
            <div class="col-12"><button class="btn btn-primary btn-sm" type="submit">Add Invoice</button></div>
        </form>
        <hr>
        <div id="invoice_table_wrap"><?= view('finance/partials/invoice_table', ['vendor_invoices' => $vendor_invoices ?? []]) ?></div>
    </div>
</div>

<script>
(function() {
    function showAlert(message, ok) {
        var cls = ok ? 'alert-success' : 'alert-danger';
        var html = '<div class="alert ' + cls + ' alert-dismissible fade show" role="alert">'
            + message
            + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            + '</div>';
        var box = document.getElementById('finance_section_alert');
        if (box) {
            box.innerHTML = html;
        }
    }

    var form = document.getElementById('invoice_form');
    if (!form) {
        return;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new window.FormData(form);

        fetch('<?= base_url('Finance/invoice_create') ?>', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(function(res) {
            return res.json().then(function(data) {
                return { ok: res.ok, data: data };
            });
        })
        .then(function(result) {
            if (!result.ok || !result.data || result.data.status !== 1) {
                showAlert((result.data && result.data.message) ? result.data.message : 'Request failed', false);
                return;
            }

            showAlert(result.data.message || 'Saved successfully', true);
            form.reset();
            load_form_div('<?= base_url('Finance/invoice_table') ?>', 'invoice_table_wrap');
        })
        .catch(function() {
            showAlert('Network or server error.', false);
        });
    });
})();
</script>

<div class="card">
    <div class="card-header"><strong>3) GRN Entry</strong></div>
    <div class="card-body">
        <div id="finance_section_alert"></div>
        <form id="grn_form" class="row g-2">
            <div class="col-md-4"><input type="text" class="form-control" name="grn_no" placeholder="GRN No" required></div>
            <div class="col-md-4"><input type="date" class="form-control" name="grn_date" required></div>
            <div class="col-md-4">
                <select class="form-select" name="po_id" required>
                    <option value="">PO No</option>
                    <?php foreach (($po_options ?? []) as $po): ?>
                        <option value="<?= (int) ($po['id'] ?? 0) ?>"><?= esc((string) ($po['po_no'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6"><input type="number" step="0.01" class="form-control" name="received_amount" placeholder="Received Amount"></div>
            <div class="col-md-6"><input type="text" class="form-control" name="received_by" placeholder="Received By"></div>
            <div class="col-12">
                <textarea class="form-control" name="remarks" rows="2" placeholder="Notes / Remarks (optional)"></textarea>
            </div>
            <div class="col-12"><button class="btn btn-primary btn-sm" type="submit">Add GRN</button></div>
        </form>
        <hr>
        <div id="grn_table_wrap"><?= view('finance/partials/grn_table', ['grns' => $grns ?? []]) ?></div>
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

    var form = document.getElementById('grn_form');
    if (!form) {
        return;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new window.FormData(form);

        fetch('<?= base_url('Finance/grn_create') ?>', {
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
            load_form_div('<?= base_url('Finance/grn_table') ?>', 'grn_table_wrap');
        })
        .catch(function() {
            showAlert('Network or server error.', false);
        });
    });
})();
</script>

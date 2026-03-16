<div class="card">
    <div class="card-header"><strong>1) Vendor Master</strong></div>
    <div class="card-body">
        <div id="finance_section_alert"></div>
        <form id="vendor_form" class="row g-2">
            <div class="col-md-4"><input type="text" class="form-control" name="vendor_code" placeholder="Vendor Code" required></div>
            <div class="col-md-8"><input type="text" class="form-control" name="vendor_name" placeholder="Vendor Name" required></div>
            <div class="col-md-6"><input type="text" class="form-control" name="contact_person" placeholder="Contact Person"></div>
            <div class="col-md-6"><input type="text" class="form-control" name="phone" placeholder="Phone"></div>
            <div class="col-12"><button class="btn btn-primary btn-sm" type="submit">Add Vendor</button></div>
        </form>
        <hr>
        <div id="vendors_table_wrap"><?= view('finance/partials/vendors_table', ['vendors' => $vendors ?? []]) ?></div>
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

    var form = document.getElementById('vendor_form');
    if (!form) {
        return;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new window.FormData(form);

        fetch('<?= base_url('Finance/vendor_create') ?>', {
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
            load_form_div('<?= base_url('Finance/vendors_table') ?>', 'vendors_table_wrap');
        })
        .catch(function() {
            showAlert('Network or server error.', false);
        });
    });
})();
</script>

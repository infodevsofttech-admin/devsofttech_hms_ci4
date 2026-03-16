<section class="content finance-doctor-payout">
    <div class="mb-3">
        <h2 class="mb-1">Doctor Payout Workflow</h2>
        <p class="text-muted mb-0">Agreement rates, payout drafting, and HR -> Finance -> CEO approval flow.</p>
    </div>

    <div id="doctor_payout_alert"></div>

    <div class="row g-2 mb-2">
        <div class="col-md-3 col-6"><div class="card border-secondary"><div class="card-body py-2"><div class="small text-muted">Draft</div><div class="h5 mb-0"><?= (int) ($summary['draft'] ?? 0) ?></div></div></div></div>
        <div class="col-md-3 col-6"><div class="card border-info"><div class="card-body py-2"><div class="small text-muted">Finance Approved</div><div class="h5 mb-0 text-info"><?= (int) ($summary['finance_approved'] ?? 0) ?></div></div></div></div>
        <div class="col-md-3 col-6"><div class="card border-primary"><div class="card-body py-2"><div class="small text-muted">CEO Approved</div><div class="h5 mb-0 text-primary"><?= (int) ($summary['ceo_approved'] ?? 0) ?></div></div></div></div>
        <div class="col-md-3 col-6"><div class="card border-success"><div class="card-body py-2"><div class="small text-muted">Paid</div><div class="h5 mb-0 text-success"><?= (int) ($summary['paid'] ?? 0) ?></div></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header"><strong>1) Doctor Agreement</strong></div>
                <div class="card-body">
                    <form id="doctor_agreement_form" class="row g-2">
                        <div class="col-md-4"><input type="text" class="form-control" name="doctor_code" placeholder="Doctor Code" required></div>
                        <div class="col-md-8"><input type="text" class="form-control" name="doctor_name" placeholder="Doctor Name" required></div>
                        <div class="col-md-6"><input type="text" class="form-control" name="specialization" placeholder="Specialization"></div>
                        <div class="col-md-3"><input type="number" step="0.01" class="form-control" name="consultation_rate" placeholder="Consult. Rate"></div>
                        <div class="col-md-3"><input type="number" step="0.01" class="form-control" name="surgery_rate" placeholder="Surgery Rate"></div>
                        <div class="col-md-6"><input type="date" class="form-control" name="agreement_start_date"></div>
                        <div class="col-md-6"><input type="date" class="form-control" name="agreement_end_date"></div>
                        <div class="col-12"><button type="submit" class="btn btn-primary btn-sm">Save Agreement</button></div>
                    </form>
                    <hr>
                    <div id="doctor_agreement_table_wrap"><?= view('finance/partials/doctor_agreements_table', ['agreements' => $agreements ?? []]) ?></div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header"><strong>2) Payout Entry & Approval</strong></div>
                <div class="card-body">
                    <form id="doctor_payout_form" class="row g-2">
                        <div class="col-md-4"><input type="date" class="form-control" name="payout_date" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-md-8">
                            <select class="form-select" name="doctor_id" required>
                                <option value="">Select Doctor</option>
                                <?php foreach (($doctor_options ?? []) as $d): ?>
                                    <option value="<?= (int) ($d['id'] ?? 0) ?>"><?= esc((string) ($d['doctor_code'] ?? '') . ' - ' . (string) ($d['doctor_name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" name="payout_type" required>
                                <option value="consultation">Consultation</option>
                                <option value="surgery">Surgery</option>
                            </select>
                        </div>
                        <div class="col-md-4"><input type="number" class="form-control" name="units" placeholder="Units" value="1" min="1"></div>
                        <div class="col-md-4"><input type="text" class="form-control" name="case_reference" placeholder="Case Ref"></div>
                        <div class="col-12"><textarea class="form-control" name="remarks" rows="2" placeholder="Remarks"></textarea></div>
                        <div class="col-12"><button type="submit" class="btn btn-primary btn-sm">Create Payout Draft</button></div>
                    </form>
                    <hr>
                    <div id="doctor_payout_table_wrap"><?= view('finance/partials/doctor_payouts_table', ['payouts' => $payouts ?? []]) ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    function showAlert(message, ok) {
        var cls = ok ? 'alert-success' : 'alert-danger';
        var html = '<div class="alert ' + cls + ' alert-dismissible fade show" role="alert">'
            + message
            + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
            + '</div>';
        var box = document.getElementById('doctor_payout_alert');
        if (box) {
            box.innerHTML = html;
        }
    }

    function refreshTables() {
        load_form_div('<?= base_url('Finance/doctor_agreements_table') ?>', 'doctor_agreement_table_wrap');
        load_form_div('<?= base_url('Finance/doctor_payouts_table') ?>', 'doctor_payout_table_wrap');
    }

    function wireForm(formId, endpoint) {
        var form = document.getElementById(formId);
        if (!form) {
            return;
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new window.FormData(form);
            fetch(endpoint, {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: formData
            })
            .then(function(res) { return res.json().then(function(data) { return { ok: res.ok, data: data }; }); })
            .then(function(result) {
                if (!result.ok || !result.data || result.data.status !== 1) {
                    showAlert((result.data && result.data.message) ? result.data.message : 'Request failed', false);
                    return;
                }
                showAlert(result.data.message || 'Saved successfully', true);
                form.reset();
                refreshTables();
            })
            .catch(function() { showAlert('Network or server error.', false); });
        });
    }

    window.financePayoutApprove = function(id, level) {
        var fd = new window.FormData();
        fd.append('payout_id', String(id));
        fd.append('level', level);

        fetch('<?= base_url('Finance/doctor_payout_approve') ?>', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            body: fd
        })
        .then(function(res) { return res.json().then(function(data) { return { ok: res.ok, data: data }; }); })
        .then(function(result) {
            if (!result.ok || !result.data || result.data.status !== 1) {
                showAlert((result.data && result.data.message) ? result.data.message : 'Approval failed', false);
                return;
            }
            showAlert(result.data.message || 'Updated successfully', true);
            refreshTables();
        })
        .catch(function() { showAlert('Network or server error.', false); });
    };

    wireForm('doctor_agreement_form', '<?= base_url('Finance/doctor_agreement_create') ?>');
    wireForm('doctor_payout_form', '<?= base_url('Finance/doctor_payout_create') ?>');
})();
</script>

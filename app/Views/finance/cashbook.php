<section class="content finance-cashbook">
    <?php
    $queue = $queue_summary ?? ['all' => 0, 'pending' => 0, 'received' => 0, 'deposited' => 0];
    $collectorOptions = $collector_options ?? [];
    $showBillingPanel = isset($show_billing_panel) ? (bool) $show_billing_panel : true;
    $showAccountsPanel = isset($show_accounts_panel) ? (bool) $show_accounts_panel : true;

    if (! $showBillingPanel && ! $showAccountsPanel) {
        $showAccountsPanel = true;
    }

    $pageTitle = 'Cash Submission';
    $pageSubtitle = 'Select patient payment rows from Payment History, submit exact totals to Accounts, and verify with full payment-level audit.';
    if ($showBillingPanel && ! $showAccountsPanel) {
        $pageTitle = 'Billing Cash Submission';
        $pageSubtitle = 'Select cash payments and submit to Accounts Department.';
    } elseif (! $showBillingPanel && $showAccountsPanel) {
        $pageTitle = 'Accounts Cash Submission Verification';
        $pageSubtitle = 'Accept submitted cash statements and mark them deposited after verification.';
    }
    ?>

    <div class="mb-3">
        <h2 class="mb-1"><?= esc($pageTitle) ?></h2>
        <p class="text-muted mb-0"><?= esc($pageSubtitle) ?></p>
    </div>

    <div id="cash_alert"></div>

    <div class="row g-2 mb-2">
        <div class="col-md-3 col-6"><div class="card border-secondary"><div class="card-body py-2"><div class="small text-muted">All</div><div class="h5 mb-0"><?= (int) ($queue['all'] ?? 0) ?></div></div></div></div>
        <div class="col-md-3 col-6"><div class="card border-warning"><div class="card-body py-2"><div class="small text-muted">Pending</div><div class="h5 mb-0 text-warning"><?= (int) ($queue['pending'] ?? 0) ?></div></div></div></div>
        <div class="col-md-3 col-6"><div class="card border-primary"><div class="card-body py-2"><div class="small text-muted">Received</div><div class="h5 mb-0 text-primary"><?= (int) ($queue['received'] ?? 0) ?></div></div></div></div>
        <div class="col-md-3 col-6"><div class="card border-success"><div class="card-body py-2"><div class="small text-muted">Deposited</div><div class="h5 mb-0 text-success"><?= (int) ($queue['deposited'] ?? 0) ?></div></div></div></div>
    </div>

    <div class="row g-3">
        <?php if ($showBillingPanel): ?>
        <div class="<?= $showAccountsPanel ? 'col-xl-7' : 'col-12' ?>">
            <div class="card h-100">
                <div class="card-header"><strong>1) Select Payments For Submission</strong></div>
                <div class="card-body">
                    <form id="scroll_filter_form" class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1">Start Date &amp; Time</label>
                            <input type="datetime-local" name="start_datetime" class="form-control" value="<?= date('Y-m-d\\T00:00') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1">End Date &amp; Time</label>
                            <input type="datetime-local" name="end_datetime" class="form-control" value="<?= date('Y-m-d\\TH:i') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1">Collected By</label>
                            <select class="form-select" name="collected_by">
                                <option value="">All Users</option>
                                <?php foreach ($collectorOptions as $opt): ?>
                                    <?php $oid = (int) ($opt['user_id'] ?? 0); ?>
                                    <?php $oname = trim((string) ($opt['user_name'] ?? '')); ?>
                                    <?php if ($oname !== ''): ?>
                                        <option value="<?= $oid > 0 ? $oid : esc($oname) ?>"><?= esc($oname) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1">Payment Mode</label>
                            <input type="text" class="form-control" value="Cash" readonly>
                            <input type="hidden" name="payment_mode" value="cash">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1">Department</label>
                            <input type="text" class="form-control" name="department" value="Billing" required>
                        </div>
                        <div class="col-12">
                            <button type="button" id="load_payments_btn" class="btn btn-outline-secondary btn-sm">Load Payments</button>
                        </div>
                    </form>

                    <hr>

                    <form id="scroll_form" class="row g-2">
                        <input type="hidden" name="start_datetime" value="">
                        <input type="hidden" name="end_datetime" value="">
                        <input type="hidden" name="collected_by" value="">
                        <input type="hidden" name="payment_mode" value="cash">
                        <input type="hidden" name="department" value="Billing">

                        <div id="payment_selection_wrap"><?= view('finance/partials/payment_selection_table', ['rows' => [], 'totals' => ['payment_count' => 0, 'total_receipts' => 0]]) ?></div>

                        <div class="col-md-6"><input type="text" name="submitted_by" class="form-control" placeholder="Submitted By"></div>
                        <div class="col-md-6"><input type="text" name="remarks" class="form-control" placeholder="Remarks"></div>
                        <div class="col-md-6"><input type="number" min="0" step="0.01" name="submitted_amount" class="form-control" placeholder="Calculated Total" readonly></div>
                        <div class="col-md-6"><input type="text" id="selection_info" class="form-control" value="Selected payments: 0" readonly></div>
                        <div class="col-12"><button type="submit" class="btn btn-primary btn-sm">Submit Selected Payments To Accounts</button></div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($showAccountsPanel): ?>
        <div class="<?= $showBillingPanel ? 'col-xl-5' : 'col-12' ?>">
            <div class="card h-100">
                <div class="card-header"><strong>2) Accounts Acceptance And Verification Queue</strong></div>
                <div class="card-body">
                    <div id="scroll_table_wrap"><?= view('finance/partials/scroll_table', ['scrolls' => $scrolls ?? []]) ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="scrollItemsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Submitted Payment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="scroll_items_body">
                    <div class="text-muted">Loading...</div>
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
        var box = document.getElementById('cash_alert');
        if (box) {
            box.innerHTML = html;
        }
    }

    function refreshScrollTable() {
        if (document.getElementById('scroll_table_wrap')) {
            load_form_div('<?= base_url('Finance/scroll_table') ?>', 'scroll_table_wrap');
        }
    }

    function copyFiltersToSubmitForm() {
        var filterForm = document.getElementById('scroll_filter_form');
        var submitForm = document.getElementById('scroll_form');
        if (!filterForm || !submitForm) {
            return;
        }

        ['start_datetime', 'end_datetime', 'collected_by', 'payment_mode', 'department'].forEach(function(name) {
            var src = filterForm.querySelector('[name="' + name + '"]');
            var dst = submitForm.querySelector('[name="' + name + '"]');
            if (src && dst) {
                dst.value = src.value;
            }
        });
    }

    function updateSelectionSummary() {
        var wrap = document.getElementById('payment_selection_wrap');
        var selected = 0;
        var total = 0;

        if (wrap) {
            wrap.querySelectorAll('.payment-checkbox:checked').forEach(function(chk) {
                selected++;
                var row = chk.closest('tr');
                if (row) {
                    var amountCell = row.querySelector('.payment-amount');
                    if (amountCell) {
                        total += Number(amountCell.getAttribute('data-amount') || '0');
                    }
                }
            });
        }

        var amountInput = document.querySelector('#scroll_form [name="submitted_amount"]');
        if (amountInput) {
            amountInput.value = total.toFixed(2);
        }

        var info = document.getElementById('selection_info');
        if (info) {
            info.value = 'Selected payments: ' + String(selected);
        }
    }

    function wirePaymentSelectionEvents() {
        var wrap = document.getElementById('payment_selection_wrap');
        if (!wrap) {
            return;
        }

        var selectAll = wrap.querySelector('#payment_select_all');
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                var checked = !!selectAll.checked;
                wrap.querySelectorAll('.payment-checkbox').forEach(function(chk) {
                    chk.checked = checked;
                });
                updateSelectionSummary();
            });
        }

        wrap.querySelectorAll('.payment-checkbox').forEach(function(chk) {
            chk.addEventListener('change', updateSelectionSummary);
        });

        updateSelectionSummary();
    }

    function loadPayments() {
        var form = document.getElementById('scroll_filter_form');
        if (!form) {
            return;
        }

        copyFiltersToSubmitForm();

        var params = new window.URLSearchParams(new window.FormData(form));
        fetch('<?= base_url('Finance/payment_selection_table') ?>?' + params.toString(), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(res) {
            return res.text().then(function(html) {
                return { ok: res.ok, html: html };
            });
        })
        .then(function(result) {
            if (!result.ok) {
                showAlert('Unable to load payment list.', false);
                return;
            }

            var wrap = document.getElementById('payment_selection_wrap');
            if (wrap) {
                wrap.innerHTML = result.html;
            }
            wirePaymentSelectionEvents();
            showAlert('Payments loaded. Select rows to submit.', true);
        })
        .catch(function() {
            showAlert('Network or server error while loading payments.', false);
        });
    }

    window.financeScrollAction = function(scrollId, action) {
        var endpoint = action === 'accept'
            ? '<?= base_url('Finance/scroll_accept') ?>'
            : '<?= base_url('Finance/scroll_verify') ?>';

        var fd = new window.FormData();
        fd.append('scroll_id', String(scrollId));

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: fd
        })
        .then(function(res) {
            return res.json().then(function(data) {
                return { ok: res.ok, data: data };
            });
        })
        .then(function(result) {
            if (!result.ok || !result.data || result.data.status !== 1) {
                showAlert((result.data && result.data.message) ? result.data.message : 'Action failed', false);
                return;
            }

            showAlert(result.data.message || 'Updated successfully', true);
            refreshScrollTable();
        })
        .catch(function() {
            showAlert('Network or server error.', false);
        });
    };

    window.financeViewScrollItems = function(scrollId) {
        var target = document.getElementById('scroll_items_body');
        if (target) {
            target.innerHTML = '<div class="text-muted">Loading...</div>';
        }

        fetch('<?= base_url('Finance/scroll_items_table') ?>?scroll_id=' + encodeURIComponent(String(scrollId)), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(res) {
            return res.text().then(function(html) {
                return { ok: res.ok, html: html };
            });
        })
        .then(function(result) {
            if (!target) {
                return;
            }
            target.innerHTML = result.ok ? result.html : '<div class="text-danger">Unable to load submission items.</div>';
        })
        .catch(function() {
            if (target) {
                target.innerHTML = '<div class="text-danger">Network or server error.</div>';
            }
        });

        var modalEl = document.getElementById('scrollItemsModal');
        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
            var modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    };

    var loadBtn = document.getElementById('load_payments_btn');
    if (loadBtn) {
        loadBtn.addEventListener('click', loadPayments);
    }

    var submitForm = document.getElementById('scroll_form');
    if (submitForm) {
        submitForm.addEventListener('submit', function(e) {
            e.preventDefault();
            copyFiltersToSubmitForm();

            var payload = new window.FormData(submitForm);
            var wrap = document.getElementById('payment_selection_wrap');
            if (wrap) {
                wrap.querySelectorAll('.payment-checkbox:checked').forEach(function(chk) {
                    payload.append('payment_ids[]', chk.value);
                });
            }

            fetch('<?= base_url('Finance/scroll_create') ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: payload
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

                showAlert(result.data.message || 'Submission sent.', true);
                refreshScrollTable();
                loadPayments();
            })
            .catch(function() {
                showAlert('Network or server error.', false);
            });
        });
    }

    wirePaymentSelectionEvents();
})();
</script>

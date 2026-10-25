<?php
$ipdId = (int) ($ipd_info->id ?? 0);
$csrfName = csrf_token();
$csrfHash = csrf_hash();
?>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>#</th>
                <th>Invoice Code</th>
                <th>Date</th>
                <th class="text-end">Amount</th>
                <th>Bill Type</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (! empty($medical_bills)) : ?>
                <?php $srNo = 1; ?>
                <?php foreach ($medical_bills as $row) : ?>
                    <tr>
                        <td><?= $srNo++ ?></td>
                        <td><?= esc($row->inv_med_code ?? $row->id ?? '') ?></td>
                        <td><?= esc($row->inv_date ?? '') ?></td>
                        <td class="text-end"><?= number_format((float) ($row->net_amount ?? 0), 2) ?></td>
                        <td>
                            <?php $includeInBill = (int) ($row->ipd_credit_type ?? 1) === 1; ?>
                            <div class="d-flex align-items-center gap-2">
                                <select
                                    class="form-select form-select-sm"
                                    id="medical-bill-type-<?= (int) $row->id ?>"
                                    style="width: auto; min-width: 210px; font-weight: 600;"
                                    onchange="ipdMedicalBillsChangeType(this, <?= (int) $row->id ?>)">
                                    <option value="1" <?= $includeInBill ? 'selected' : '' ?>>✓ Include in IPD Bill (Normal)</option>
                                    <option value="0" <?= ! $includeInBill ? 'selected' : '' ?>>📦 In Package (Covered)</option>
                                </select>
                                <span id="badge-bill-type-<?= (int) $row->id ?>" class="badge <?= $includeInBill ? 'bg-success-subtle text-success border border-success' : 'bg-warning-subtle text-warning border border-warning' ?>">
                                    <?= $includeInBill ? 'Include in Bill' : 'In Package' ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" onclick="ipdMedicalBillsShowInvoice(this, <?= (int) $row->id ?>)">
                                Show Invoice
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">No IPD credit medical bills found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="ipd-medical-bill-details" class="mt-3"></div>

<script>
    var ipdMedicalBillsIpdId = <?= $ipdId ?>;
    var ipdMedicalBillsCsrfName = '<?= esc($csrfName) ?>';
    var ipdMedicalBillsCsrfHash = '<?= esc($csrfHash) ?>';
    var ipdMedicalBillsUpdateUrl = '<?= site_url('billing/ipd/medical-bill/credit-type') ?>';
    var ipdMedicalBillsDetailsUrl = '<?= site_url('billing/ipd/medical-bill') ?>';

    function ipdMedicalBillsChangeType(select, invoiceId) {
        var val = parseInt(select.value, 10);
        var badge = document.getElementById('badge-bill-type-' + invoiceId);
        var payload = {
            ipd_id: ipdMedicalBillsIpdId,
            ipd_credit_type: val
        };
        payload[ipdMedicalBillsCsrfName] = ipdMedicalBillsCsrfHash;
        select.disabled = true;

        if (badge) {
            badge.className = 'badge bg-secondary-subtle text-secondary border border-secondary';
            badge.textContent = 'Updating...';
        }

        $.post(ipdMedicalBillsUpdateUrl + '/' + invoiceId, payload, function(resp) {
            if (!resp || !resp.ok) {
                select.value = val === 1 ? '0' : '1';
                alert((resp && resp.error) ? resp.error : 'Unable to update medical bill type.');
                return;
            }

            var isIncluded = val === 1;
            if (badge) {
                badge.className = isIncluded
                    ? 'badge bg-success-subtle text-success border border-success'
                    : 'badge bg-warning-subtle text-warning border border-warning';
                badge.textContent = isIncluded ? 'Include in Bill' : 'In Package';
            }

            // Real-time update top dashboard summary numbers
            if (resp.summary) {
                ipdMedicalBillsUpdateDashboardCards(resp.summary);
            }

            // Invalidate Bill Details tab so it loads fresh calculated data when clicked
            var billPanel = document.getElementById('tab_bill_content');
            if (billPanel) {
                billPanel.innerHTML = 'Loading...';
            }

            if (typeof window.notify === 'function') {
                window.notify('success', 'Medical Bill', isIncluded ? 'Bill marked as Include in IPD Bill' : 'Bill marked as In Package');
            }
        }).fail(function(xhr) {
            select.value = val === 1 ? '0' : '1';
            var message = xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error
                : 'Unable to update medical bill type.';
            alert(message);
        }).always(function() {
            select.disabled = false;
        });
    }

    function ipdMedicalBillsUpdateDashboardCards(s) {
        var orangeCard = document.querySelector('.ipd-summary-card.orange');
        if (orangeCard) {
            var spans = orangeCard.querySelectorAll('span');
            spans.forEach(function(sp) {
                var txt = sp.textContent || '';
                if (txt.indexOf('Charges') >= 0 && s.charge_amount !== undefined) {
                    sp.textContent = 'Charges : ' + s.charge_amount;
                } else if (txt.indexOf('Pharmacy Cr. IPD') >= 0 && s.med_amount !== undefined) {
                    sp.textContent = 'Pharmacy Cr. IPD : ' + s.med_amount;
                } else if (txt.indexOf('Net Amount') >= 0 && s.net_amount !== undefined) {
                    sp.textContent = 'Net Amount : ' + s.net_amount;
                }
            });
        }

        var greenCard = document.querySelector('.ipd-summary-card.green');
        if (greenCard) {
            var gSpans = greenCard.querySelectorAll('span');
            gSpans.forEach(function(sp) {
                var txt = sp.textContent || '';
                if (txt.indexOf('Total Paid') >= 0 && s.total_paid_amount !== undefined) {
                    sp.textContent = 'Total Paid : ' + s.total_paid_amount;
                } else if (txt.indexOf('Balance') >= 0 && s.balance_amount !== undefined) {
                    sp.textContent = 'Balance : ' + s.balance_amount;
                }
            });
        }

        var blueCard = document.querySelector('.ipd-summary-card.blue');
        if (blueCard) {
            var bSpans = blueCard.querySelectorAll('span');
            bSpans.forEach(function(sp) {
                var txt = sp.textContent || '';
                if (txt.indexOf('Pharmacy Bill') >= 0 && s.cash_med_amount !== undefined) {
                    sp.textContent = 'Pharmacy Bill : ' + s.cash_med_amount;
                } else if (txt.indexOf('Paid Amount') >= 0 && s.med_paid !== undefined) {
                    sp.textContent = 'Paid Amount : ' + s.med_paid;
                }
            });
        }
    }

    function ipdMedicalBillsShowInvoice(button, invoiceId) {
        var details = $('#ipd-medical-bill-details');
        button.disabled = true;
        details.html('<div class="text-muted p-3">Loading invoice...</div>');

        $.get(ipdMedicalBillsDetailsUrl + '/' + ipdMedicalBillsIpdId + '/' + invoiceId, function(html) {
            details.html(html);
        }).fail(function(xhr) {
            details.html('<div class="alert alert-danger">' + (xhr.responseText || 'Unable to load invoice details.') + '</div>');
        }).always(function() {
            button.disabled = false;
        });
    }
</script>

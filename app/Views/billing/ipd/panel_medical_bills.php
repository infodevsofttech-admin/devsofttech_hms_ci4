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
                            <?php $includeInBill = (int) ($row->ipd_credit_type ?? 0) === 1; ?>
                            <div class="form-check form-switch mb-0">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    id="medical-bill-type-<?= (int) $row->id ?>"
                                    <?= $includeInBill ? 'checked' : '' ?>
                                    onchange="ipdMedicalBillsToggleType(this, <?= (int) $row->id ?>)">
                                <label class="form-check-label" for="medical-bill-type-<?= (int) $row->id ?>">
                                    <?= $includeInBill ? 'Include in Bill' : 'In Package' ?>
                                </label>
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

    function ipdMedicalBillsToggleType(checkbox, invoiceId) {
        var label = document.querySelector('label[for="' + checkbox.id + '"]');
        var payload = {
            ipd_id: ipdMedicalBillsIpdId,
            ipd_credit_type: checkbox.checked ? 1 : 0
        };
        payload[ipdMedicalBillsCsrfName] = ipdMedicalBillsCsrfHash;
        checkbox.disabled = true;

        $.post(ipdMedicalBillsUpdateUrl + '/' + invoiceId, payload, function(resp) {
            if (!resp || !resp.ok) {
                checkbox.checked = !checkbox.checked;
                alert((resp && resp.error) ? resp.error : 'Unable to update medical bill type.');
                return;
            }
            label.textContent = checkbox.checked ? 'Include in Bill' : 'In Package';
        }).fail(function(xhr) {
            checkbox.checked = !checkbox.checked;
            var message = xhr.responseJSON && xhr.responseJSON.error
                ? xhr.responseJSON.error
                : 'Unable to update medical bill type.';
            alert(message);
        }).always(function() {
            checkbox.disabled = false;
        });
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

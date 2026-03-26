<?php
$ipdId = (int) (($ipd_info->id ?? 0));
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
                <th>Charge Type</th>
                <th class="text-end">Amount</th>
                <th>Include</th>
            </tr>
        </thead>
        <tbody>
            <?php if (! empty($diagnosis_charges)) : ?>
                <?php $srNo = 1; ?>
                <?php foreach ($diagnosis_charges as $row) : ?>
                    <tr>
                        <td><?= $srNo++ ?></td>
                        <td><?= esc($row->invoice_code ?? $row->inv_id ?? '') ?></td>
                        <td><?= esc($row->str_date ?? '') ?></td>
                        <td><?= esc($row->charge_list ?? '') ?></td>
                        <td class="text-end"><?= esc($row->amount ?? '') ?></td>
                        <td>
                            <div class="form-check m-0">
                                <input
                                    class="form-check-input ipd-diagnosis-include"
                                    type="checkbox"
                                    value="1"
                                    data-invoice-id="<?= (int) ($row->inv_id ?? 0) ?>"
                                    <?= ((int) ($row->ipd_include ?? 0) > 0) ? 'checked' : '' ?>
                                >
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">No diagnosis charges found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    (function () {
        var diagnosisIncludeUrlBase = '<?= site_url('billing/ipd/panel/' . $ipdId . '/diagnosis/include') ?>';
        var diagnosisCsrfName = '<?= esc($csrfName) ?>';
        var diagnosisCsrfHash = '<?= esc($csrfHash) ?>';

        $(document).off('change', '.ipd-diagnosis-include').on('change', '.ipd-diagnosis-include', function () {
            var checkbox = $(this);
            var invoiceId = parseInt(checkbox.data('invoice-id') || 0, 10);
            if (invoiceId <= 0) {
                checkbox.prop('checked', !checkbox.is(':checked'));
                return;
            }

            var payload = {};
            payload[diagnosisCsrfName] = diagnosisCsrfHash;
            payload.ipd_include = checkbox.is(':checked') ? 1 : 0;

            checkbox.prop('disabled', true);

            $.post(diagnosisIncludeUrlBase + '/' + invoiceId, payload, function (resp) {
                diagnosisCsrfHash = (resp && resp.csrfHash) ? resp.csrfHash : diagnosisCsrfHash;
                if (!resp || !resp.ok) {
                    checkbox.prop('checked', !checkbox.is(':checked'));
                    alert((resp && resp.error) ? resp.error : 'Unable to update include status');
                }
            }, 'json').fail(function (xhr) {
                checkbox.prop('checked', !checkbox.is(':checked'));
                var msg = (xhr && xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Unable to update include status';
                alert(msg);
            }).always(function () {
                checkbox.prop('disabled', false);
            });
        });
    })();
</script>

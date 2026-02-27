<?php
$orgId = (int) ($orgcase->id ?? 0);
$orgCode = (string) ($orgcase->case_id_code ?? ('ORG-' . $orgId));
$patientName = (string) ($orgcase->p_fname ?? '-');
$patientRel = (string) ($orgcase->p_rname ?? '');
$patientCode = (string) ($orgcase->p_code ?? '-');
$insuranceName = (string) ($orgcase->insurance_company_name ?? '-');
$amountTotal = (float) ($totals['amount_total'] ?? 0);
$balanceTotal = (float) ($totals['balance_total'] ?? 0);
$hasRows = !empty($invoices);
?>

<div class="card border-0">
    <div class="card-header bg-light border-bottom border-primary border-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0"><strong>Org Case:</strong> <?= esc($orgCode) ?></h5>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-primary btn-sm" href="javascript:load_form_div('<?= base_url('Medical/Invoice_counter_new/' . (int)($orgcase->p_id ?? 0) . '/0/' . $orgId) ?>','medical-main','Org Credit Invoice :Pharmacy');">Add New</a>
            <a class="btn btn-outline-secondary btn-sm" href="javascript:load_form_div('<?= base_url('Medical/list_med_orginv/' . $orgId) ?>','medical-main','Org Credit Invoice :Pharmacy');">Refresh</a>
            <a class="btn btn-outline-secondary btn-sm" href="javascript:load_form_div('<?= base_url('Medical/list_org') ?>','medical-main','OrgCr. List :Pharmacy');">Back to Org List</a>
        </div>
    </div>

    <div class="card-body">
        <div class="mb-3 fw-semibold">
            Name : <?= esc($patientName) ?><?= $patientRel !== '' ? ' {' . esc($patientRel) . '}' : '' ?>
            / P Code : <?= esc($patientCode) ?>
            / Insurance : <?= esc($insuranceName) ?>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-2" id="org-credit-invoice-grid">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Invoice ID</th>
                        <th>Inv.Date</th>
                        <th>Inv.Desc</th>
                        <th>Credit/Cash</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($hasRows): ?>
                        <?php foreach ($invoices as $i => $row): ?>
                            <?php $invoiceId = (int) ($row->id ?? 0); ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><strong><?= esc((string) ($row->inv_med_code ?? ('M' . date('ym') . str_pad((string) $invoiceId, 7, '0', STR_PAD_LEFT)))) ?></strong></td>
                                <td><?= esc(!empty($row->inv_date) ? date('d-m-Y', strtotime((string) $row->inv_date)) : '-') ?></td>
                                <td><?= esc((string) ($row->remark_ipd ?? '-')) ?></td>
                                <td><?= ((int) ($row->case_credit ?? 0) > 0) ? 'CREDIT' : 'CASH' ?></td>
                                <td><?= esc(number_format((float) ($row->net_amount ?? 0), 2)) ?></td>
                                <td><?= esc(number_format((float) ($row->payment_balance ?? 0), 2)) ?></td>
                                <td>
                                    <a class="btn btn-sm btn-primary" href="javascript:load_form_div('<?= base_url('Medical/open_invoice_edit/' . $invoiceId) ?>','medical-main','Invoice Edit :Pharmacy');">Edit</a>
                                    <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= base_url('Medical/invoice_print/' . $invoiceId) ?>">PDF</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-muted">No org credit invoices found for this case.</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-2 fw-semibold">
            Total Amount: <?= esc(number_format($amountTotal, 2)) ?>
            / Total Balance: <?= esc(number_format($balanceTotal, 2)) ?>
        </div>
    </div>
</div>

<script>
    (function () {
        var hasRows = <?= $hasRows ? 'true' : 'false' ?>;
        if (!hasRows) {
            return;
        }

        if (!window.jQuery || !jQuery.fn || typeof jQuery.fn.DataTable !== 'function') {
            return;
        }

        var tableId = '#org-credit-invoice-grid';
        if (jQuery.fn.dataTable.isDataTable(tableId)) {
            jQuery(tableId).DataTable().destroy();
        }

        jQuery(tableId).DataTable({
            order: [[2, 'desc']],
            pageLength: 25
        });
    })();
</script>

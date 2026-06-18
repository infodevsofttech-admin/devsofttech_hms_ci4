<?php $invoice = $purchase_return_invoice ?? null; ?>
<?php if (! $invoice): ?>
    <div class="alert alert-warning mb-0">Purchase return invoice not found.</div>
<?php else: ?>
<div class="box">
    <div class="box-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h3 class="box-title mb-0">Purchase Return Invoice / Supplier : <?= esc((string) ($invoice->name_supplier ?? '-')) ?></h3>
            <small class="text-muted">
                Invoice No.: <?= esc((string) ($invoice->Invoice_no ?? '')) ?> |
                Invoice Date: <?= esc((string) ($invoice->str_date_of_invoice ?? '')) ?>
            </small>
        </div>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary" onclick="load_form_div('<?= base_url('Storestock/Purchase_return') ?>','searchresult','Purchase Return');">Back</button>
            <button type="button" class="btn btn-warning" onclick="load_form_div('<?= base_url('Storestock/PurchaseReturnInvoiceEdit/' . (int) ($invoice->id ?? 0)) ?>','searchresult','Purchase Return Invoice Edit');">Reload Invoice</button>
            <a href="<?= base_url('Storestock/print_purchase_return/' . (int) ($invoice->id ?? 0)) ?>" target="_blank" class="btn btn-secondary"><i class="fa fa-print"></i> Print</a>
        </div>
    </div>
    <div class="box-body pt-3">
        <?= $content ?? '' ?>
    </div>
</div>
<?php endif; ?>
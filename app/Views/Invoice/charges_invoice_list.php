<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Charges Invoice List</h3>
    </div>
    <div class="card-body">
        <form class="row g-2 mb-3" onsubmit="event.preventDefault(); chargesInvoiceSearch();">
            <div class="col-md-6">
                <input type="text" class="form-control" id="charges_invoice_search" value="<?= esc($search ?? '') ?>" placeholder="Search by Invoice Code, Patient Code, Name, Mobile, Email">
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button type="button" class="btn btn-primary" onclick="chargesInvoiceSearch()">Search</button>
                <button type="button" class="btn btn-light" onclick="load_form('<?= base_url('Invoice/chargeslist') ?>','Charges Invoice')">Reset</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="chargesInvoiceTable">
                <thead class="table-light">
                    <tr>
                        <th>Invoice Code</th>
                        <th>Patient</th>
                        <th>Patient Code</th>
                        <th>Date</th>
                        <th>Mobile</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($rows ?? []) as $row): ?>
                        <?php
                            $invType = ((int) ($row->insurance_id ?? 0) > 1) ? 'Org.' : 'Direct';
                            $invDate = ! empty($row->inv_date) ? date('d-m-Y', strtotime((string) $row->inv_date)) : '-';
                        ?>
                        <tr>
                            <td><?= esc((string) ($row->invoice_code ?? '-')) ?></td>
                            <td><?= esc((string) ($row->p_fname ?? '-')) ?></td>
                            <td><?= esc((string) ($row->p_code ?? '-')) ?></td>
                            <td><?= esc($invDate) ?></td>
                            <td><?= esc((string) ($row->mphone1 ?? '-')) ?></td>
                            <td><?= esc($invType) ?></td>
                            <td><?= esc(number_format((float) ($row->net_amount ?? 0), 2, '.', '')) ?></td>
                            <td>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="load_form('<?= base_url('billing/charges/show') ?>/<?= (int) ($row->inv_id ?? 0) ?>','Charges Invoice');">Open</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function chargesInvoiceSearch() {
    var q = (document.getElementById('charges_invoice_search') || {}).value || '';
    load_form('<?= base_url('Invoice/chargeslist') ?>?q=' + encodeURIComponent(q), 'Charges Invoice');
}

(function() {
    if (window.jQuery && $.fn && $.fn.DataTable) {
        $('#chargesInvoiceTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc']]
        });
    }
})();
</script>

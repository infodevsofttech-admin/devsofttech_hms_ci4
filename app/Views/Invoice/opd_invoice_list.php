<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">OPD Invoice List</h3>
    </div>
    <div class="card-body">
        <form class="row g-2 mb-3" onsubmit="event.preventDefault(); opdInvoiceSearch();">
            <div class="col-md-6">
                <input type="text" class="form-control" id="opd_invoice_search" value="<?= esc($search ?? '') ?>" placeholder="Search by OPD Code, Patient Code, Name, Mobile, Email">
            </div>
            <div class="col-md-6 d-flex gap-2">
                <button type="button" class="btn btn-primary" onclick="opdInvoiceSearch()">Search</button>
                <button type="button" class="btn btn-light" onclick="load_form('<?= base_url('Invoice/opdlist') ?>','OPD Invoice')">Reset</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="opdInvoiceTable">
                <thead class="table-light">
                    <tr>
                        <th>OPD Code</th>
                        <th>Patient</th>
                        <th>Patient Code</th>
                        <th>Date</th>
                        <th>Doctor</th>
                        <th>Mobile</th>
                        <th>Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($rows ?? []) as $row): ?>
                        <?php
                            $invType = ((int) ($row->insurance_id ?? 0) > 1) ? 'Org.' : 'Direct';
                            $opdDate = ! empty($row->apointment_date) ? date('d-m-Y', strtotime((string) $row->apointment_date)) : '-';
                        ?>
                        <tr>
                            <td><?= esc((string) ($row->opd_code ?? '-')) ?></td>
                            <td><?= esc((string) ($row->P_name ?? '-')) ?></td>
                            <td><?= esc((string) ($row->p_code ?? '-')) ?></td>
                            <td><?= esc($opdDate) ?></td>
                            <td><?= esc((string) ($row->doc_name ?? '-')) ?></td>
                            <td><?= esc((string) ($row->mphone1 ?? '-')) ?></td>
                            <td><?= esc($invType) ?></td>
                            <td>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="load_form('<?= base_url('Opd/invoice') ?>/<?= (int) ($row->opd_id ?? 0) ?>','OPD Invoice');">Open</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function opdInvoiceSearch() {
    var q = (document.getElementById('opd_invoice_search') || {}).value || '';
    load_form('<?= base_url('Invoice/opdlist') ?>?q=' + encodeURIComponent(q), 'OPD Invoice');
}

(function() {
    if (window.jQuery && $.fn && $.fn.DataTable) {
        $('#opdInvoiceTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc']]
        });
    }
})();
</script>

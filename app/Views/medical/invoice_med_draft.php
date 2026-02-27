<div class="card">
    <div class="card-body pt-3">
        <h5 class="card-title">Invoice Draft List</h5>

        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?= esc($message) ?></div>
        <?php endif; ?>

        <div class="d-flex gap-2 mb-3 flex-wrap">
            <a class="btn btn-sm <?= ($status ?? 'draft') === 'draft' ? 'btn-primary' : 'btn-outline-primary' ?>" href="javascript:load_form('<?= base_url('Medical/Invoice_Med_Draft?status=draft') ?>','Invoice List :Pharmacy');">Draft</a>
            <a class="btn btn-sm <?= ($status ?? '') === 'all' ? 'btn-dark' : 'btn-outline-dark' ?>" href="javascript:load_form('<?= base_url('Medical/Invoice_Med_Draft?status=all') ?>','Invoice List :Pharmacy');">All</a>
        </div>

        <form method="get" action="<?= base_url(($status ?? 'draft') === 'final' ? 'Medical/Invoice_Med_Final' : 'Medical/Invoice_Med_Draft') ?>" class="row g-2 mb-3">
            <?php if (($status ?? 'draft') !== 'final'): ?>
                <input type="hidden" name="status" value="<?= esc($status ?? 'draft') ?>">
            <?php endif; ?>
            <?php if (!empty($caseId)): ?>
                <input type="hidden" name="case_id" value="<?= (int) $caseId ?>">
            <?php endif; ?>
            <div class="col-md-3">
                <label class="form-label form-label-sm mb-1">From Date</label>
                <input type="date" name="from" value="<?= esc($fromDate ?? '') ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label form-label-sm mb-1">To Date</label>
                <input type="date" name="to" value="<?= esc($toDate ?? '') ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-4">
                <label class="form-label form-label-sm mb-1">Search</label>
                <input type="text" name="q" value="<?= esc($search ?? '') ?>" class="form-control form-control-sm" placeholder="Invoice code / patient code / name">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button class="btn btn-sm btn-primary" type="submit">Apply</button>
                <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form('<?= base_url(($status ?? 'draft') === 'final' ? 'Medical/Invoice_Med_Final' : 'Medical/Invoice_Med_Draft' . (($status ?? 'draft') !== 'final' ? '?status=' . ($status ?? 'draft') : '')) ?>','Invoice List :Pharmacy');">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle" id="medical-invoice-grid" width="100%">
                <thead>
                    <tr>
                        <th>Invoice Code</th>
                        <th>Name</th>
                        <th>UHID / Patient ID</th>
                        <th>IPD No.</th>
                        <th>Date</th>
                        <th>Net Amount</th>
                        <th>Amt.Paid</th>
                        <th>Balance / Status</th>
                    </tr>
                </thead>
                <thead>
                    <tr>
                        <th><input type="text" class="form-control form-control-sm column-search" data-column="0"></th>
                        <th><input type="text" class="form-control form-control-sm column-search" data-column="1"></th>
                        <th><input type="text" class="form-control form-control-sm column-search" data-column="2"></th>
                        <th><input type="text" class="form-control form-control-sm column-search" data-column="3"></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    (function () {
        if (!window.jQuery || !jQuery.fn || typeof jQuery.fn.DataTable !== 'function') {
            return;
        }

        var tableId = '#medical-invoice-grid';
        if (jQuery.fn.dataTable.isDataTable(tableId)) {
            jQuery(tableId).DataTable().destroy();
        }

        var table = jQuery(tableId).DataTable({
            order: [[0, 'desc']],
            processing: true,
            serverSide: true,
            pageLength: 25,
            ajax: {
                url: '<?= base_url('Medical/getInvoiceTable') ?>',
                type: 'POST',
                data: function (data) {
                    data.status = '<?= esc($status ?? 'draft') ?>';
                    data.from = '<?= esc($fromDate ?? '') ?>';
                    data.to = '<?= esc($toDate ?? '') ?>';
                    data.q = '<?= esc($search ?? '') ?>';
                    data.case_id = '<?= (int)($caseId ?? 0) ?>';
                    <?php if (function_exists('csrf_token') && function_exists('csrf_hash')): ?>
                    data['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';
                    <?php endif; ?>
                },
                error: function () {
                    jQuery(tableId + ' tbody').html('<tr><td colspan="8" class="text-center text-danger">No data found in server.</td></tr>');
                }
            }
        });

        jQuery(tableId + '_filter').hide();

        jQuery(tableId + ' .column-search').on('input', function () {
            var col = jQuery(this).data('column');
            var val = jQuery(this).val();
            table.columns(col).search(val).draw();
        });
    })();
</script>

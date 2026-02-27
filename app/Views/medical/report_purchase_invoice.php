<div class="pagetitle">
    <h1>Report Purchase Invoice <small class="text-muted">Panel</small></h1>
</div>

<section class="section">
    <div class="card">
        <div class="card-body pt-3">
            <?php if (empty($med_gstin ?? '')): ?>
                <div class="alert alert-danger py-2 mb-3">
                    `H_Med_GST` is empty. Please set GSTIN in constants before using GSTR-2B/GSTR-3B export.
                </div>
            <?php endif; ?>

            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= esc(date('Y-m-d')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= esc(date('Y-m-d')) ?>">
                    <input type="hidden" id="inv_date_range" name="inv_date_range" />
                </div>
                <div class="col-md-2">
                    <label class="form-label">Supplier</label>
                    <select class="form-select" id="input_supplier" name="input_supplier">
                        <option value="0">ALL</option>
                        <?php foreach (($supplier_data ?? []) as $row): ?>
                            <option value="<?= esc((string) ($row->sid ?? 0)) ?>"><?= esc($row->name_supplier ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">GSTIN</label>
                    <input type="text" class="form-control" value="<?= esc($med_gstin ?? '') ?>" readonly>
                </div>
                <div class="col-md-12">
                    <label class="form-label d-block">&nbsp;</label>
                    <div class="d-flex justify-content-md-end">
                        <div class="btn-toolbar gap-2 flex-wrap" role="toolbar" aria-label="Purchase invoice actions">
                            <div class="btn-group" role="group" aria-label="Main actions">
                                <button type="button" class="btn btn-primary" id="showreport">Show</button>
                                <button type="button" class="btn btn-warning" id="showreportexport">Export</button>
                                <button type="button" class="btn btn-danger" id="showreportpdf">PDF</button>
                            </div>
                            <div class="btn-group" role="group" aria-label="GST actions">
                                <button id="gstActionsDropdown" type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    GST Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="gstActionsDropdown">
                                    <li><button type="button" class="dropdown-item" id="showreport_gstrate">Show with GST Rate</button></li>
                                    <li><button type="button" class="dropdown-item" id="showreportexport_gstrate">Export with GST Rate</button></li>
                                    <li><button type="button" class="dropdown-item" id="showreportpdf_gstrate">PDF with GST Rate</button></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><button type="button" class="dropdown-item" id="showreport_gstr2b">Export GSTR-2B JSON</button></li>
                                    <li><button type="button" class="dropdown-item" id="showreport_gstr3b">Export GSTR-3B JSON</button></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <div id="show_report"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
$(function () {
    var hasMedGstin = <?= !empty($med_gstin ?? '') ? 'true' : 'false' ?>;

    function renderReport(url) {
        if (typeof load_report_div === 'function') {
            load_report_div(url, 'show_report');
            return;
        }

        $('#show_report').html('Loading...');
        $.get(url, function (html) {
            $('#show_report').html(html);
        }).fail(function () {
            $('#show_report').html('<div class="alert alert-danger">Unable to load report data.</div>');
        });
    }

    function printReportA4() {
        var reportHtml = $('#show_report').html();
        if (!reportHtml || $.trim(reportHtml) === '') {
            alert('Please click Show first, then Print.');
            return;
        }

        var printWindow = window.open('', '_blank');
        if (!printWindow) {
            alert('Popup blocked. Please allow popups for printing.');
            return;
        }

        var html = '';
        html += '<!doctype html><html><head><meta charset="utf-8">';
        html += '<title>Purchase Invoice Report</title>';
        html += '<style>';
        html += '@page { size: A4 portrait; margin: 10mm; }';
        html += 'body { font-family: Arial, sans-serif; font-size: 12px; color: #000; margin: 0; }';
        html += 'table { width: 100%; border-collapse: collapse; }';
        html += 'th, td { border: 1px solid #222; padding: 4px 6px; vertical-align: top; }';
        html += '.text-end { text-align: right; }';
        html += '.alert { border: 1px solid #ccc; padding: 6px 8px; margin-bottom: 8px; }';
        html += '</style></head><body>';
        html += reportHtml;
        html += '</body></html>';

        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(function () {
            printWindow.print();
        }, 200);
    }

    function syncDateRange() {
        var dateFrom = $('#date_from').val();
        var dateTo = $('#date_to').val();
        if (!dateFrom || !dateTo) {
            $('#inv_date_range').val('');
            return false;
        }
        $('#inv_date_range').val(dateFrom + 'S' + dateTo);
        return true;
    }

    syncDateRange();
    $('#date_from, #date_to').on('change', syncDateRange);

    $('#showreport').on('click', function () {
        if (!syncDateRange()) {
            alert('Please select From and To date');
            return;
        }
        var supplierId = $('#input_supplier').val();
        var query = '<?= base_url('Medical_Report/purchase_invoice_data_pdf') ?>/' + $('#inv_date_range').val() + '/' + supplierId;
        renderReport(query);
    });

    $('#showreportexport').on('click', function () {
        if (!syncDateRange()) {
            alert('Please select From and To date');
            return;
        }
        var supplierId = $('#input_supplier').val();
        var query = '<?= base_url('Medical_Report/purchase_invoice_data_pdf') ?>/' + $('#inv_date_range').val() + '/' + supplierId + '/1';
        window.open(query, '_blank');
    });

    $('#showreport_gstrate').on('click', function () {
        if (!syncDateRange()) {
            alert('Please select From and To date');
            return;
        }
        var supplierId = $('#input_supplier').val();
        var query = '<?= base_url('Medical_Report/purchase_invoice_data') ?>/' + $('#inv_date_range').val() + '/' + supplierId;
        renderReport(query);
    });

    $('#showreportexport_gstrate').on('click', function () {
        if (!syncDateRange()) {
            alert('Please select From and To date');
            return;
        }
        var supplierId = $('#input_supplier').val();
        var query = '<?= base_url('Medical_Report/purchase_invoice_data') ?>/' + $('#inv_date_range').val() + '/' + supplierId + '/1';
        window.open(query, '_blank');
    });

    $('#showreportprint').on('click', function () {
        printReportA4();
    });

    $('#showreportpdf').on('click', function () {
        if (!syncDateRange()) {
            alert('Please select From and To date');
            return;
        }
        var supplierId = $('#input_supplier').val();
        var query = '<?= base_url('Medical_Report/purchase_invoice_pdf') ?>/' + $('#inv_date_range').val() + '/' + supplierId;
        window.open(query, '_blank');
    });

    $('#showreportpdf_gstrate').on('click', function () {
        if (!syncDateRange()) {
            alert('Please select From and To date');
            return;
        }
        var supplierId = $('#input_supplier').val();
        var query = '<?= base_url('Medical_Report/purchase_invoice_pdf_gst') ?>/' + $('#inv_date_range').val() + '/' + supplierId;
        window.open(query, '_blank');
    });

    $('#showreport_gstr2b').on('click', function () {
        if (!hasMedGstin) {
            alert('H_Med_GST is empty. Please configure GSTIN in constants first.');
            return;
        }
        if (!syncDateRange()) {
            alert('Please select From and To date');
            return;
        }
        var supplierId = $('#input_supplier').val();
        var query = '<?= base_url('Medical_Report/purchase_gstr2b') ?>/' + $('#inv_date_range').val() + '/' + supplierId;
        window.open(query, '_blank');
    });

    $('#showreport_gstr3b').on('click', function () {
        if (!hasMedGstin) {
            alert('H_Med_GST is empty. Please configure GSTIN in constants first.');
            return;
        }
        if (!syncDateRange()) {
            alert('Please select From and To date');
            return;
        }
        var supplierId = $('#input_supplier').val();
        var query = '<?= base_url('Medical_Report/purchase_gstr3b') ?>/' + $('#inv_date_range').val() + '/' + supplierId;
        window.open(query, '_blank');
    });
});
</script>

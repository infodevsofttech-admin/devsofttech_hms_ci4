<?php
$doclist = $doclist ?? [];
?>
<section class="content">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Document Issue Report</h5>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Date Range</label>
                    <div class="d-flex gap-2">
                        <input type="date" class="form-control" id="report_start_date">
                        <input type="date" class="form-control" id="report_end_date">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Doctor Name</label>
                    <select class="form-control" id="doc_name_id" name="doc_name_id">
                        <option value="0">All Doctors</option>
                        <?php foreach ($doclist as $row) : ?>
                            <option value="<?= (int) ($row->id ?? 0) ?>"><?= esc($row->p_fname ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">UHID</label>
                    <input type="text" class="form-control" id="uhid_filter" placeholder="UHID / PCode">
                </div>
                <div class="col-md-3">
                    <div class="btn-group" role="group" aria-label="Document report actions">
                        <button type="button" class="btn btn-primary" id="showreport">Show</button>
                        <button type="button" class="btn btn-outline-primary" id="showreportexport">Export</button>
                        <button type="button" class="btn btn-outline-danger" id="showreportpdf">PDF</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div id="show_report" class="table-responsive">Select filters and click Show.</div>
        </div>
    </div>
</section>

<script>
    (function() {
        var today = new Date();
        var yyyy = today.getFullYear();
        var mm = String(today.getMonth() + 1).padStart(2, '0');
        var dd = String(today.getDate()).padStart(2, '0');
        var todayValue = yyyy + '-' + mm + '-' + dd;

        document.getElementById('report_start_date').value = todayValue;
        document.getElementById('report_end_date').value = todayValue;

        function buildUrl(isExport) {
            var start = document.getElementById('report_start_date').value || todayValue;
            var end = document.getElementById('report_end_date').value || todayValue;
            var docId = document.getElementById('doc_name_id').value || '0';
            var uhid = (document.getElementById('uhid_filter').value || '').trim();
            var dateRange = start + 'S' + end;

            var url = '<?= base_url('Report/document_list_data') ?>/' + encodeURIComponent(dateRange) + '/' + docId;
            if (isExport) {
                url += '/1';
            }
            if (uhid !== '') {
                url += '?uhid=' + encodeURIComponent(uhid);
            }

            return url;
        }

        document.getElementById('showreport').addEventListener('click', function() {
            load_form_div(buildUrl(false), 'show_report');
        });

        document.getElementById('showreportexport').addEventListener('click', function() {
            window.open(buildUrl(true), '_blank');
        });

        document.getElementById('showreportpdf').addEventListener('click', function() {
            var pdfUrl = buildUrl(false) + '/2';
            window.open(pdfUrl, '_blank');
        });
    })();
</script>

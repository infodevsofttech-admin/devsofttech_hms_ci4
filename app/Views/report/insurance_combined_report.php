<?php
$insuranceGroups = $insurance_groups ?? [];
$insuranceCompanies = $insurance_companies ?? [];
?>
<section class="content">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-graph-up-arrow me-2"></i>Combined Insurance Summary Report
            </h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="load_form('<?= base_url('Report/insurance_credit_main') ?>', 'Insurance Credit')">
                <i class="bi bi-arrow-left me-1"></i>Back to Main
            </button>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Date Range</label>
                    <div class="d-flex gap-2">
                        <input type="date" class="form-control" id="report_start">
                        <input type="date" class="form-control" id="report_end">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Case Type</label>
                    <select class="form-control" id="case_type" name="case_type">
                        <option value="-1">All Types</option>
                        <option value="0">OPD Only</option>
                        <option value="1">IPD Only</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Insurance Company</label>
                    <select class="form-control select2" id="insurance_id" name="insurance_id">
                        <option value="0">All Companies</option>
                        <optgroup label="Insurance Groups">
                            <?php foreach ($insuranceGroups as $row) : ?>
                                <option value="<?= 'G' . esc($row->id ?? '') ?>"><?= esc($row->tpa_group ?? '') ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Individual Companies">
                            <?php foreach ($insuranceCompanies as $row) : ?>
                                <option value="<?= esc($row->id ?? '') ?>"><?= esc($row->short_name ?? '') ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary w-100" id="show_report">
                        <i class="bi bi-search me-1"></i>Show
                    </button>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="button" class="btn btn-outline-success" id="export_report">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
                </button>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div id="report_result" class="table-responsive">
                <div class="text-center text-muted py-4">
                    <i class="bi bi-filter-circle" style="font-size: 2rem;"></i>
                    <p class="mt-2">Select filters and click Show to generate report</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    (function() {
        // Set default dates
        var today = new Date();
        var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        
        document.getElementById('report_start').value = firstDay.toISOString().split('T')[0];
        document.getElementById('report_end').value = today.toISOString().split('T')[0];

        function buildQuery() {
            var startDate = document.getElementById('report_start').value;
            var endDate = document.getElementById('report_end').value;
            var dateRange = startDate + 'S' + endDate;
            var caseType = document.getElementById('case_type').value || '-1';
            var insuranceId = document.getElementById('insurance_id').value || '0';

            return '<?= base_url('Report/insurance_combined_report_data') ?>/' 
                + encodeURIComponent(dateRange) + '/' + caseType + '/' + insuranceId;
        }

        document.getElementById('show_report').addEventListener('click', function() {
            var url = buildQuery();
            load_form_div(url, 'report_result');
        });

        document.getElementById('export_report').addEventListener('click', function() {
            var url = buildQuery() + '/1';
            window.open(url, '_blank');
        });

        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    })();
</script>

<?php
$specialities = $specialities ?? [];
?>
<section class="content">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-link-45deg me-2"></i>Ayushman Unmapped Procedures
            </h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="load_form('<?= base_url('Report/insurance_credit_main') ?>', 'Insurance Credit')">
                <i class="bi bi-arrow-left me-1"></i>Back to Main
            </button>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Speciality</label>
                    <select class="form-select" id="ayushman_speciality_filter">
                        <option value="0">All Specialities</option>
                        <?php foreach ($specialities as $row) : ?>
                            <option value="<?= esc($row->speciality_code ?? '') ?>">
                                <?= esc(($row->speciality_name ?? '') . ' [' . ($row->speciality_code ?? '') . ']') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-primary w-100" id="show_ayushman_unmapped_report">
                        <i class="bi bi-search me-1"></i>Show
                    </button>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-outline-success w-100" id="export_ayushman_unmapped_report">
                        <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div id="ayushman_unmapped_result" class="table-responsive">
                <div class="text-center text-muted py-4">
                    <i class="bi bi-filter-circle" style="font-size: 2rem;"></i>
                    <p class="mt-2">Select a speciality filter and click Show to generate report</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    (function() {
        function buildAyushmanReportUrl(output) {
            var specialityCode = document.getElementById('ayushman_speciality_filter').value || '0';
            var url = '<?= base_url('Report/ayushman_unmapped_report_data') ?>/' + encodeURIComponent(specialityCode);
            if (output === 1) {
                url += '/1';
            }
            return url;
        }

        document.getElementById('show_ayushman_unmapped_report').addEventListener('click', function() {
            load_form_div(buildAyushmanReportUrl(0), 'ayushman_unmapped_result');
        });

        document.getElementById('export_ayushman_unmapped_report').addEventListener('click', function() {
            window.open(buildAyushmanReportUrl(1), '_blank');
        });
    })();
</script>
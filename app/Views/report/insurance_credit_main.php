<section class="content">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Insurance Credit Reports</h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-4">Select a report type to view insurance credit details</p>
            
            <div class="row g-4">
                <!-- OPD Organization Cases Report -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-primary shadow-sm hover-card">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="bi bi-clipboard2-pulse text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">OPD Organization Cases</h5>
                            <p class="card-text text-muted">
                                View OPD cases submitted to insurance organizations
                            </p>
                            <button type="button" class="btn btn-primary" onclick="load_form('<?= base_url('Report/insurance_opd_report') ?>', 'OPD Insurance Report')">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Open Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- IPD TPA Cases Report -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-success shadow-sm hover-card">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="bi bi-hospital text-success" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">IPD TPA Cases</h5>
                            <p class="card-text text-muted">
                                View IPD cases submitted to TPA/Insurance companies
                            </p>
                            <button type="button" class="btn btn-success" onclick="load_form('<?= base_url('Report/insurance_ipd_report') ?>', 'IPD Insurance Report')">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Open Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Combined Insurance Summary -->
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-info shadow-sm hover-card">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="bi bi-graph-up-arrow text-info" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Combined Summary</h5>
                            <p class="card-text text-muted">
                                View combined OPD & IPD insurance summary
                            </p>
                            <button type="button" class="btn btn-info" onclick="load_form('<?= base_url('Report/insurance_combined_report') ?>', 'Insurance Summary')">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Open Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="bi bi-info-circle text-warning me-2"></i>Report Information
                            </h6>
                            <ul class="mb-0 small">
                                <li><strong>OPD Organization Cases:</strong> Shows outpatient cases with insurance coverage (case_type = 0)</li>
                                <li><strong>IPD TPA Cases:</strong> Shows inpatient cases with TPA/insurance coverage (case_type = 1)</li>
                                <li><strong>Combined Summary:</strong> Consolidated view of all insurance cases with totals</li>
                                <li><strong>Status Types:</strong> Pending (0), Bill Complete (1), Submitted to Organization (2)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.hover-card {
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.hover-card .card-body {
    padding: 2rem;
}
</style>

<?php
$labInvoiceData = [];
$labReqId = '';
$labTestNo = '';
$currentLabType = (int) ($lab_type ?? $labType ?? 0);
$isRadiologyFlow = !in_array($currentLabType, [5, 30], true);
$flowTitle = $isRadiologyFlow ? 'Imaging Worklist' : 'Pathology Worklist';
$statusPendingLabel = $isRadiologyFlow ? 'Report Pending' : 'Data Pending';
$printTemplates = $print_templates ?? [];

if (!empty($lab_invoice_request) && count($lab_invoice_request) > 0) {
    $labInvoiceData = $lab_invoice_request[0];
    $labReqId = $labInvoiceData->id ?? '';
    $labTestNo = $labInvoiceData->lab_test_no ?? '';
}
?>

<?php if ($isRadiologyFlow): ?>
<style>
.diag-worklist-title {
    font-weight: 700;
    color: #0f2e62;
}
.diag-test-card {
    border: 1px solid #d8e3f2;
    border-left: 4px solid #188a58;
    border-radius: 10px;
    box-shadow: 0 4px 14px rgba(16, 24, 40, 0.06);
}
.diag-test-card .card-body {
    padding: 12px 14px;
}
.diag-more-box {
    background: #f7fafd;
}
</style>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0 diag-worklist-title"><?php echo htmlspecialchars($flowTitle); ?></h6>
</div>

<?php if (!empty($lab_invoice_request) && count($lab_invoice_request) > 0): ?>
<div class="row g-2 mb-3 align-items-center">
    <div class="col-md-3 col-sm-6">
        <div class="small text-muted">Sr. No.</div>
        <div class="fw-semibold"><?php echo htmlspecialchars($lab_invoice_request[0]->daily_sr_no ?? ''); ?></div>
    </div>
    <div class="col-md-5 col-sm-6">
        <div class="input-group input-group-sm">
            <input type="hidden" id="lab_req_id" name="lab_req_id" value="<?php echo htmlspecialchars($labReqId); ?>">
            <input type="text" class="form-control" id="inputLabNo" name="inputLabNo" placeholder="Lab Test No." 
                value="<?php echo htmlspecialchars($labTestNo); ?>">
            <button type="button" class="btn btn-info" onclick="updateLabNo()">Update Lab No.</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($testlist) && count($testlist) > 0): ?>
    <?php foreach ($testlist as $test): ?>
    <?php $status = (int) ($test->status ?? 0); ?>
    <div class="card mb-3 diag-test-card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <div class="fw-semibold"><?php echo htmlspecialchars($test->item_name ?? ''); ?></div>

                <?php if (empty($test->check_sample) || $test->check_sample < 1): ?>
                    <span class="badge bg-danger">Sample Collection Pending</span>
                <?php else: ?>
                    <?php if ($status === 0): ?>
                        <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($statusPendingLabel); ?></span>
                    <?php elseif ($status === 1): ?>
                        <span class="badge bg-info">In Progress</span>
                    <?php else: ?>
                        <span class="badge bg-success">Completed</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        
            <?php if (empty($test->check_sample) || $test->check_sample < 1): ?>
                <button type="button" class="btn btn-danger btn-sm"
                    onclick="updateSampleCollection(<?php echo htmlspecialchars($test->test_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>')">
                    <i class="bi bi-flask"></i> Sample Collection
                </button>
            <?php else: ?>
                <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="CHK_<?php echo htmlspecialchars($test->req_id ?? '0'); ?>"
                            onchange="onChangeUpdate(this, <?php echo htmlspecialchars($test->req_id ?? '0'); ?>)"
                            <?php echo (!empty($test->print_combine) && $test->print_combine > 0) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="CHK_<?php echo htmlspecialchars($test->req_id ?? '0'); ?>">
                            Print Combine
                        </label>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-2">
                    <?php if ($status == 2): ?>
                        <?php if ($isRadiologyFlow): ?>
                            <?php if (! empty($printTemplates)): ?>
                                <?php foreach ($printTemplates as $tpl): ?>
                                    <?php
                                    $tplId = (int) ($tpl['id'] ?? 0);
                                    $tplName = (string) ($tpl['template_name'] ?? ('Template ' . $tplId));
                                    $isDefaultTpl = (int) ($tpl['is_default'] ?? 0) === 1;
                                    ?>
                                    <button type="button" class="btn <?= $isDefaultTpl ? 'btn-success' : 'btn-outline-success' ?> btn-sm"
                                        onclick="printSingleReport(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>', <?= $tplId ?>)">
                                        <i class="bi bi-printer"></i> <?= esc($tplName) ?>
                                    </button>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <button type="button" class="btn btn-success btn-sm"
                                    onclick="printSingleReport(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>', 0)">
                                    <i class="bi bi-printer"></i> Print Report
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="openReportEditor(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>')">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-success btn-sm"
                                onclick="printSingleReport(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>')">
                                <i class="bi bi-printer"></i> Print Single Report
                            </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-outline-danger btn-sm"
                            onclick="removeTest(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>)">
                            <i class="bi bi-trash"></i> Remove
                        </button>
                    <?php else: ?>
                        <?php if ($isRadiologyFlow): ?>
                            <button type="button" class="btn btn-warning btn-sm"
                                onclick="createReportXray(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>')">
                                <i class="bi bi-file-earmark-text"></i> Open Report Editor
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-primary btn-sm"
                                onclick="editTestData(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>')">
                                <i class="bi bi-pencil"></i> Data Pending
                            </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                            onclick="editTestDataDetailed(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>')">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm"
                            onclick="removeTest(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>)">
                            <i class="bi bi-trash"></i> Remove
                        </button>
                        <?php if ($isRadiologyFlow): ?>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="uploadFiles(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>')">
                                <i class="bi bi-upload"></i> Upload Files
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="scanReportFile(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>')">
                                <i class="bi bi-camera"></i> Webcam Scan
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="showFiles(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>')">
                                <i class="bi bi-images"></i> Show Upload Images
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if (! $isRadiologyFlow): ?>
                    <button type="button" class="btn btn-light btn-sm"
                        onclick="toggleTestDetails(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>)">
                        <i class="bi bi-three-dots"></i> More
                    </button>

                    <div id="details_<?php echo htmlspecialchars($test->req_id ?? '0'); ?>" class="mt-2 p-2 border rounded diag-more-box" style="display:none;">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="uploadFiles(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>')">
                                <i class="bi bi-upload"></i> Upload Files
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="scanReportFile(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>')">
                                <i class="bi bi-camera"></i> Webcam Scan
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                onclick="showFiles(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>')">
                                <i class="bi bi-files"></i> Show Files
                            </button>
                            <?php if ($status == 2): ?>
                                <button type="button" class="btn btn-outline-success btn-sm"
                                    onclick="openForEdit(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>')">
                                    <i class="bi bi-pencil"></i> Open for Edit
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-info-circle"></i> No tests found for this invoice.
    </div>
<?php endif; ?>

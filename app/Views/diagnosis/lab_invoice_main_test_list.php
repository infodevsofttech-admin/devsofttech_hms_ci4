<?php
$labInvoiceData = [];
$labReqId = '';
$labTestNo = '';
$currentLabType = (int) ($lab_type ?? $labType ?? 0);
$isRadiologyFlow = !in_array($currentLabType, [5, 30], true);
$flowTitle = $isRadiologyFlow ? 'Imaging Worklist' : 'Pathology Worklist';
$statusPendingLabel = $isRadiologyFlow ? 'Report Pending' : 'Data Pending';

if (!empty($lab_invoice_request) && count($lab_invoice_request) > 0) {
    $labInvoiceData = $lab_invoice_request[0];
    $labReqId = $labInvoiceData->id ?? '';
    $labTestNo = $labInvoiceData->lab_test_no ?? '';
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0"><?php echo htmlspecialchars($flowTitle); ?></h6>
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
    <div class="card mb-3">
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
                        <button type="button" class="btn btn-success btn-sm"
                            onclick="printSingleReport(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>')">
                            <i class="bi bi-printer"></i> Print Single Report
                        </button>
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
                    <?php endif; ?>
                </div>

                <button type="button" class="btn btn-light btn-sm"
                    onclick="toggleTestDetails(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>)">
                    <i class="bi bi-three-dots"></i> More
                </button>

                <div id="details_<?php echo htmlspecialchars($test->req_id ?? '0'); ?>" class="mt-2 p-2 border rounded" style="display:none;">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm"
                            onclick="uploadFiles(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>')">
                            <i class="bi bi-upload"></i> Upload Files
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm"
                            onclick="scanReport(<?php echo htmlspecialchars($test->req_id ?? '0'); ?>, '<?php echo htmlspecialchars($test->item_name ?? ''); ?>')">
                            <i class="bi bi-scanner"></i> Scan
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

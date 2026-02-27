<?php
$testName = $test->item_name ?? '';
$patientName = $test->p_fname ?? '';
$patientCode = $test->p_code ?? '';
$invoiceCode = $test->invoice_code ?? '';
$status = $test->status ?? 0;
?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h6 class="card-title mb-0">Test Data Entry - <?php echo htmlspecialchars($testName); ?></h6>
    </div>
    <div class="card-body">
        <!-- Patient Information -->
        <div class="row mb-3">
            <div class="col-md-6">
                <p class="mb-1"><small class="text-muted">Patient:</small></p>
                <p class="mb-3"><strong><?php echo htmlspecialchars($patientName . ' (' . $patientCode . ')'); ?></strong></p>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><small class="text-muted">Invoice:</small></p>
                <p class="mb-3"><strong><?php echo htmlspecialchars($invoiceCode); ?></strong></p>
            </div>
        </div>

        <!-- Test Data Entry Form -->
        <div class="mb-3">
            <label for="testRemarks" class="form-label">Test Remarks / Findings</label>
            <textarea class="form-control" id="testRemarks" name="testRemarks" rows="4" placeholder="Enter test findings, observations, or remarks..."></textarea>
            <small class="text-muted">Enter the laboratory findings and observations for this test.</small>
        </div>

        <!-- Status Selection -->
        <div class="mb-3">
            <label for="testStatus" class="form-label">Status</label>
            <select class="form-control" id="testStatus" name="testStatus">
                <option value="0" <?php echo ($status == 0) ? 'selected' : ''; ?>>Pending</option>
                <option value="1" <?php echo ($status == 1) ? 'selected' : ''; ?>>In Progress</option>
                <option value="2" <?php echo ($status == 2) ? 'selected' : ''; ?>>Completed</option>
            </select>
        </div>

        <!-- Action Buttons -->
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="saveTestData(<?php echo htmlspecialchars($labReqId); ?>, '<?php echo htmlspecialchars($testName); ?>')">
                <i class="bi bi-check-circle"></i> Save Test Data
            </button>
        </div>
    </div>
</div>

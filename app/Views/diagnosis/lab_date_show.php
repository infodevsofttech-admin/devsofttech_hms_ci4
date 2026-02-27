<?php
$collectedTime = '';
$reportedTime = '';

if (!empty($lab_invoice_request) && count($lab_invoice_request) > 0) {
    $collectedTime = $lab_invoice_request[0]->collected_time ?? '';
    $reportedTime = $lab_invoice_request[0]->reported_time ?? '';
}
?>

<form id="labTimingForm" class="row g-3">
    <input type="hidden" id="invoiceId" value="<?php echo htmlspecialchars($invoiceId ?? ''); ?>">
    <input type="hidden" id="labType" value="<?php echo htmlspecialchars($labType ?? '5'); ?>">

    <div class="col-md-6">
        <label for="collectedTime" class="form-label">Sample Collection Time</label>
        <input type="datetime-local" class="form-control" id="collectedTime" 
            value="<?php 
                if (!empty($collectedTime)) {
                    echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($collectedTime)));
                }
            ?>">
    </div>

    <div class="col-md-6">
        <label for="reportedTime" class="form-label">Report Time</label>
        <input type="datetime-local" class="form-control" id="reportedTime"
            value="<?php 
                if (!empty($reportedTime)) {
                    echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($reportedTime)));
                }
            ?>">
    </div>

    <div class="col-12">
        <button type="button" class="btn btn-primary" id="updateTimeBtn" onclick="updateLabTiming()">
            <i class="bi bi-clock-history"></i> Update Time
        </button>
    </div>
</form>

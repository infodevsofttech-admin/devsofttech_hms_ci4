<?php
$currentLabType = (int) ($lab_type ?? 0);
$currentLabTypeName = (string) ($lab_type_name ?? 'Diagnosis');
$currentLabRoute = (string) ($lab_type_route ?? 'diagnosis');
$isPathologyFlow = in_array($currentLabType, [5, 30], true);
$flowTypeLabel = $isPathologyFlow ? 'Lab' : 'Imaging';
$timingTitle = $isPathologyFlow ? 'Lab Timing Information' : 'Imaging Workflow Timing';
$testListTitle = $isPathologyFlow ? 'Test List' : 'Imaging Study List';
$collectedTimeLabel = $isPathologyFlow ? 'Sample Collection Time' : 'Request Collection Time';
$isImagingFlow = !$isPathologyFlow;
$printTemplates = $print_templates ?? [];
?>

<?php if ($isImagingFlow): ?>
<style>
.diagnosis-ui {
    background: linear-gradient(180deg, #f7f9fd 0%, #eef3f9 100%);
    border: 1px solid #dbe4f0;
    border-radius: 14px;
    padding: 14px;
}
.diagnosis-ui .card {
    border: 1px solid #dfe7f3;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(18, 38, 63, 0.06);
}
.diagnosis-ui .card-header {
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
}
.diagnosis-ui .card-title {
    letter-spacing: 0.2px;
    font-weight: 700;
}
.diagnosis-ui .table > tbody > tr > th {
    white-space: nowrap;
    width: 32%;
}
.diagnosis-ui .btn {
    border-radius: 8px;
}
#testDataEntryModal .modal-content {
    border-radius: 14px;
    border: 1px solid #dfe7f3;
}
#testDataEntryModal .modal-header {
    background: linear-gradient(90deg, #0a3c8f 0%, #165cb8 100%);
    color: #fff;
}
#testDataEntryModal .btn-close {
    filter: invert(1);
}
</style>
<?php endif; ?>

<div class="pagetitle">
    <h1><?= esc($currentLabTypeName) ?> - <?= esc($flowTypeLabel) ?> Invoice Details</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= base_url('diagnosis') ?>','Diagnosis');">Diagnosis</a></li>
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= base_url('diagnosis/' . $currentLabRoute) ?>','<?= esc($currentLabTypeName) ?>');"><?= esc($currentLabTypeName) ?></a></li>
            <li class="breadcrumb-item active">Invoice #<?php echo htmlspecialchars($invoice->invoice_code ?? ''); ?></li>
        </ol>
    </nav>
</div>

<section class="section<?= $isImagingFlow ? ' diagnosis-ui' : '' ?>">
    <div class="row">
        <!-- Person Profile Card -->
        <div class="col-lg-4">
            <div class="card profile-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Person Profile</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($invoice)): ?>
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <th>Patient Name:</th>
                                <td><?php echo htmlspecialchars($invoice->p_fname . ' ' . ($invoice->p_rname ?? '')); ?></td>
                            </tr>
                            <tr>
                                <th>Patient Code:</th>
                                <td><?php echo htmlspecialchars($invoice->p_code ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Gender:</th>
                                <td><?php echo htmlspecialchars($invoice->gender ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Age:</th>
                                <td>
                                    <?php 
                                    if (!empty($invoice->age_in_month) && intval($invoice->age_in_month) > 0) {
                                        echo htmlspecialchars($invoice->age_in_month . ' months');
                                    } elseif (!empty($invoice->age)) {
                                        echo htmlspecialchars($invoice->age . ' years');
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Aadhar:</th>
                                <td><?php echo htmlspecialchars($invoice->udai ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td><?php echo htmlspecialchars($invoice->phone_number ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($invoice->email ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Address:</th>
                                <td>
                                    <?php 
                                    $address = [];
                                    if (!empty($invoice->address_line1)) $address[] = $invoice->address_line1;
                                    if (!empty($invoice->city)) $address[] = $invoice->city;
                                    if (!empty($invoice->state)) $address[] = $invoice->state;
                                    echo htmlspecialchars(implode(', ', $address) ?: 'N/A');
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Invoice Date:</th>
                                <td><?php echo htmlspecialchars(date('d-m-Y', strtotime($invoice->inv_date ?? ''))); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="alert alert-warning" role="alert">
                        No patient information found.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Lab Timing & Tests Section -->
        <div class="col-lg-8">
            <!-- Lab Timing Card -->
            <div class="card mb-3 diag-panel" id="labTimingCard">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0"><?= esc($timingTitle) ?></h5>
                </div>
                <div class="card-body" id="labTimingCardBody">
                    <form id="labTimingForm" class="row g-3">
                        <input type="hidden" id="invoiceId" value="<?php echo htmlspecialchars($invoice->inv_id ?? $invoice->id ?? '0'); ?>">
                        <input type="hidden" id="labType" value="<?php echo htmlspecialchars($lab_type ?? '5'); ?>">

                        <div class="col-md-6">
                            <label for="collectedTime" class="form-label"><?= esc($collectedTimeLabel) ?></label>
                            <input type="datetime-local" class="form-control" id="collectedTime" 
                                value="<?php 
                                    if (!empty($lab_invoice->collected_time)) {
                                        echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($lab_invoice->collected_time)));
                                    }
                                ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="reportedTime" class="form-label">Report Time</label>
                            <input type="datetime-local" class="form-control" id="reportedTime"
                                value="<?php 
                                    if (!empty($lab_invoice->reported_time)) {
                                        echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($lab_invoice->reported_time)));
                                    }
                                ?>">
                        </div>

                        <div class="col-12">
                            <button type="button" class="btn btn-primary" id="updateTimeBtn" onclick="updateLabTiming()">
                                <i class="bi bi-clock-history"></i> Update Time
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Test List Card -->
            <div class="card diag-panel" id="testListCard">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0"><?= esc($testListTitle) ?></h5>
                </div>
                <div class="card-body" id="testListCardBody">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isPathologyFlow): ?>
        <!-- Report Actions Card -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">Report Operations</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-auto">
                                <button type="button" class="btn btn-primary" id="compileBtn" onclick="compileReport()">
                                    <i class="bi bi-file-earmark-pdf"></i> Compile Report
                                </button>
                            </div>
                            <div class="col-auto">
                                <?php if (! empty($printTemplates)): ?>
                                    <div class="btn-group" role="group" aria-label="Template Print Group">
                                        <?php foreach ($printTemplates as $tpl): ?>
                                            <?php
                                            $tplId = (int) ($tpl['id'] ?? 0);
                                            $tplName = (string) ($tpl['template_name'] ?? ('Template ' . $tplId));
                                            $isDefaultTpl = (int) ($tpl['is_default'] ?? 0) === 1;
                                            ?>
                                            <button type="button" class="btn <?= $isDefaultTpl ? 'btn-info' : 'btn-outline-info' ?>" onclick="generateReportByTemplate(<?= $tplId ?>)">
                                                <i class="bi bi-file-earmark-pdf"></i> <?= esc($tplName) ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <button type="button" class="btn btn-info" onclick="generateReportByTemplate(0)">
                                        <i class="bi bi-file-earmark-pdf"></i> Print Report
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-success" id="uploadBtn" onclick="uploadReport()">
                                    <i class="bi bi-cloud-upload"></i> Upload PDF
                                </button>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-warning" id="scanBtn" onclick="scanReport()">
                                    <i class="bi bi-scanner"></i> Scan Report
                                </button>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-dark" id="showBtn" onclick="showReports()">
                                    <i class="bi bi-eye"></i> Show Reports
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

<!-- Modal for Sample Collection Update -->
<div class="modal fade" id="sampleCollectionModal" tabindex="-1" aria-labelledby="sampleCollectionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sampleCollectionLabel">Update Sample Collection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="testNameDisplay" class="form-label">Test Name</label>
                    <input type="text" class="form-control" id="testNameDisplay" readonly>
                </div>
                <div class="mb-3">
                    <label for="collectionTimeUpdate" class="form-label">Collection Time</label>
                    <input type="datetime-local" class="form-control" id="collectionTimeUpdate">
                </div>
                <input type="hidden" id="testIdUpdate">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveCollectionTime()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Test Data Entry Modal -->
<div class="modal fade" id="testDataEntryModal" tabindex="-1" role="dialog" aria-labelledby="testDataEntryLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testDataEntryLabel">Test Data Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="testDataEntryBody">
                <!-- Form will be loaded here via AJAX -->
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="imagingSupportModal" tabindex="-1" aria-labelledby="imagingSupportLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="imagingSupportLabel">Imaging Support</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="imagingSupportBody">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var baseUrl = '<?php echo rtrim(base_url(), '/'); ?>/';
var invoiceId = '<?php echo htmlspecialchars($invoice->inv_id ?? $invoice->id ?? '0'); ?>';
var labType = '<?php echo htmlspecialchars($lab_type ?? '5'); ?>';

console.log('Page loaded - Invoice ID:', invoiceId, 'Lab Type:', labType);

function updateLabTiming() {
    const collectedTime = document.getElementById('collectedTime').value;
    const reportedTime = document.getElementById('reportedTime').value;

    if (!collectedTime && !reportedTime) {
        alert('Please fill in at least one time field');
        return;
    }

    const data = new FormData();
    data.append('invoice_id', invoiceId);
    data.append('lab_type', labType);
    if (collectedTime) data.append('collected_time', collectedTime);
    if (reportedTime) data.append('reported_time', reportedTime);

    fetch(baseUrl + 'diagnosis/update-lab-timing', {
        method: 'POST',
        body: data,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            alert('Lab timing updated successfully');
            // Refresh the lab date show section
            refreshLabDateShow();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating lab timing');
    });
}

function updateSampleCollection(testId, testName) {
    document.getElementById('testIdUpdate').value = testId;
    document.getElementById('testNameDisplay').value = testName;
    document.getElementById('collectionTimeUpdate').value = new Date().toISOString().slice(0, 16);
    
    const modal = new bootstrap.Modal(document.getElementById('sampleCollectionModal'));
    modal.show();
}

function saveCollectionTime() {
    const testId = document.getElementById('testIdUpdate').value;
    const collectionTime = document.getElementById('collectionTimeUpdate').value;

    if (!testId || !collectionTime) {
        alert('Please fill in all fields');
        return;
    }

    const data = new FormData();
    data.append('invoice_id', invoiceId);

    fetch(baseUrl + 'diagnosis/sample-collection/' + testId + '/' + labType, {
        method: 'POST',
        body: data,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('sampleCollectionModal'));
            if (modal) modal.hide();
            
            alert('Sample collection recorded successfully');
            
            // Refresh test list to update status
            refreshTestList();
            
            // Also refresh lab date show section
            refreshLabDateShow();
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving sample collection time');
    });
}

function refreshTestList() {
    const url = baseUrl + 'diagnosis/test-list/' + invoiceId + '/' + labType;
    console.log('Calling refreshTestList with URL:', url);
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout
    
    fetch(url, {
        method: 'GET',
        signal: controller.signal,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        clearTimeout(timeoutId);
        console.log('Test list response status:', response.status);
        if (!response.ok) {
            console.error('Test list response error, status:', response.status, response.statusText);
        }
        return response.text();
    })
    .then(html => {
        console.log('Test list HTML received, length:', html.length);
        if (html.length === 0) {
            console.warn('Test list returned empty HTML');
            html = '<div class="alert alert-warning">No test data found</div>';
        }
        const cardBody = document.getElementById('testListCardBody');
        if (cardBody) {
            cardBody.innerHTML = html;
            console.log('✓ Test list inserted into DOM');
        } else {
            console.error('✗ Element testListCardBody not found in DOM');
            document.body.innerHTML += '<div class="alert alert-danger">ERROR: testListCardBody element missing</div>';
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        if (error.name === 'AbortError') {
            console.error('✗ Test list fetch timeout after 15 seconds');
        } else {
            console.error('✗ Error refreshing test list:', error.message, error);
        }
        const cardBody = document.getElementById('testListCardBody');
        if (cardBody) {
            cardBody.innerHTML = '<div class="alert alert-danger"><strong>Error loading test list:</strong> ' + (error.message || error) + '</div>';
        }
    });
}

function refreshLabDateShow() {
    const url = baseUrl + 'diagnosis/lab-date-show/' + invoiceId + '/' + labType;
    console.log('Calling refreshLabDateShow with URL:', url);
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout
    
    fetch(url, {
        method: 'GET',
        signal: controller.signal,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        clearTimeout(timeoutId);
        console.log('Lab date show response status:', response.status);
        if (!response.ok) {
            console.error('Lab date show response error, status:', response.status, response.statusText);
        }
        return response.text();
    })
    .then(html => {
        console.log('Lab date show HTML received, length:', html.length);
        if (html.length === 0) {
            console.warn('Lab date show returned empty HTML');
            html = '<div class="alert alert-warning">No lab timing data found</div>';
        }
        const cardBody = document.getElementById('labTimingCardBody');
        if (cardBody) {
            cardBody.innerHTML = html;
            console.log('✓ Lab date show inserted into DOM');
        } else {
            console.error('✗ Element labTimingCardBody not found in DOM');
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        if (error.name === 'AbortError') {
            console.error('✗ Lab date show fetch timeout after 15 seconds');
        } else {
            console.error('✗ Error refreshing lab date show:', error.message, error);
        }
        const cardBody = document.getElementById('labTimingCardBody');
        if (cardBody) {
            cardBody.innerHTML = '<div class="alert alert-danger"><strong>Error loading lab timing:</strong> ' + (error.message || error) + '</div>';
        }
    });
}

function editTestData(reqId, testName) {
    const url = baseUrl + 'diagnosis/edit-test-data/' + reqId;
    console.log('Opening test data entry form:', url);
    
    // Load the form into the modal
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        destroyReportEditor();
        document.getElementById('testDataEntryBody').innerHTML = html;
        const modal = new bootstrap.Modal(document.getElementById('testDataEntryModal'));
        modal.show();
    })
    .catch(error => {
        console.error('Error loading test data form:', error);
        alert('Error loading test data form: ' + error);
    });
}

function initializeReportEditor() {
    if (typeof CKEDITOR === 'undefined') {
        return;
    }

    // Re-enable notification plugins (may be disabled globally in welcome_message.php)
    CKEDITOR.config.removePlugins = '';

    if (CKEDITOR.instances.HTMLShow) {
        CKEDITOR.instances.HTMLShow.destroy(true);
    }

    if (CKEDITOR.instances.report_data_Impression) {
        CKEDITOR.instances.report_data_Impression.destroy(true);
    }

    const textarea = document.getElementById('HTMLShow');
    if (textarea) {
        CKEDITOR.replace('HTMLShow');
    }

    const impression = document.getElementById('report_data_Impression');
    if (impression) {
        CKEDITOR.replace('report_data_Impression', {
            toolbar: [
                { name: 'basicstyles', items: ['Bold', 'Italic'] }
            ]
        });
    }
}

function destroyReportEditor() {
    if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.HTMLShow) {
        CKEDITOR.instances.HTMLShow.destroy(true);
    }

    if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.report_data_Impression) {
        CKEDITOR.instances.report_data_Impression.destroy(true);
    }
}

function update_test_value(testId, testValue) {
    const modalBody = document.getElementById('testDataEntryBody');
    const csrfField = modalBody ? modalBody.querySelector('input[name]') : null;

    const formData = new FormData();
    formData.append('test_id', testId);
    formData.append('test_value', testValue);

    if (csrfField) {
        formData.append(csrfField.name, csrfField.value);
    }

    fetch(baseUrl + 'diagnosis/update-test-value', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            const target = document.getElementById('update_value_' + testId);
            if (target) {
                target.textContent = result.value;
            }
        } else {
            alert(result.message || 'Unable to save test value');
        }
    })
    .catch(error => {
        console.error('update_test_value error:', error);
        alert('Error updating value: ' + error);
    });
}

function report_create() {
    const reqInput = document.getElementById('hid_value_req_id');
    if (!reqInput || !reqInput.value) {
        alert('Request id missing');
        return;
    }

    const reqId = reqInput.value;
    const modalBody = document.getElementById('testDataEntryBody');
    const csrfField = modalBody ? modalBody.querySelector('input[name]') : null;
    const formData = new FormData();

    if (csrfField) {
        formData.append(csrfField.name, csrfField.value);
    }

    fetch(baseUrl + 'diagnosis/create-report/' + reqId, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        destroyReportEditor();
        document.getElementById('testDataEntryBody').innerHTML = html;
        const label = document.getElementById('testDataEntryLabel');
        if (label) {
            label.textContent = 'Final Report';
        }

        initializeReportEditor();

        refreshTestList();
        refreshLabDateShow();
    })
    .catch(error => {
        console.error('report_create error:', error);
        alert('Error in Save & Next: ' + error);
    });
}

function createReportXray(reqId, testName) {
    // Create report, then load full-page editor
    const modalBody = document.getElementById('testDataEntryBody');
    const csrfField = modalBody ? modalBody.querySelector('input[name]') : null;
    const data = new FormData();

    if (csrfField) {
        data.append(csrfField.name, csrfField.value);
    }

    // Create/initialize report first
    fetch(baseUrl + 'diagnosis/create-report-xray/' + reqId, {
        method: 'POST',
        body: data,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        // Load full-page editor using load_form
        load_form(baseUrl + 'diagnosis/open-report-editor/' + reqId, testName + ' - Report Editor');
    })
    .catch(error => {
        console.error('createReportXray error:', error);
        alert('Error preparing report editor: ' + error.message);
    });
}


function set_template(templateId) {
    const modalBody = document.getElementById('testDataEntryBody');
    const csrfField = modalBody ? modalBody.querySelector('input[name]') : null;
    const formData = new FormData();

    if (csrfField) {
        formData.append(csrfField.name, csrfField.value);
    }

    fetch(baseUrl + 'diagnosis/get-template-xray/' + templateId, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.HTMLShow) {
            CKEDITOR.instances.HTMLShow.setData(data.Findings || '');
        } else {
            const htmlField = document.getElementById('HTMLShow');
            if (htmlField) {
                htmlField.value = data.Findings || '';
            }
        }

        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.report_data_Impression) {
            CKEDITOR.instances.report_data_Impression.setData(data.Impression || '');
        } else {
            const impressionField = document.getElementById('report_data_Impression');
            if (impressionField) {
                impressionField.value = data.Impression || '';
            }
        }
    })
    .catch(error => {
        alert('Error loading template: ' + error);
    });
}

function update_report() {
    const reqInput = document.getElementById('hid_value_req_id');
    const htmlField = document.getElementById('HTMLShow');
    const modalBody = document.getElementById('testDataEntryBody');
    const csrfField = modalBody ? modalBody.querySelector('input[name]') : null;

    if (!reqInput || !htmlField) {
        alert('Report data not found');
        return;
    }

    const modeInput = document.getElementById('report_mode');
    const isXrayMode = modeInput && modeInput.value === 'xray';

    const formData = new FormData();
    const htmlData = (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.HTMLShow)
        ? CKEDITOR.instances.HTMLShow.getData()
        : htmlField.value;
    
    // Send dual keys for backward compatibility (legacy + current)
    formData.append('HTMLData', htmlData);
    formData.append('report_data', htmlData);

    if (isXrayMode) {
        const impressionField = document.getElementById('report_data_Impression');
        const impressionData = (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.report_data_Impression)
            ? CKEDITOR.instances.report_data_Impression.getData()
            : (impressionField ? impressionField.value : '');
        formData.append('report_data_Impression', impressionData);
        formData.append('report_data_impression', impressionData);  // lowercase variant for compatibility
    }

    if (csrfField) {
        formData.append(csrfField.name, csrfField.value);
    }

    const updateEndpoint = isXrayMode
        ? (baseUrl + 'diagnosis/final-update-xray/' + reqInput.value)
        : (baseUrl + 'diagnosis/final-update/' + reqInput.value);

    fetch(updateEndpoint, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            alert('Report updated');
        } else {
            alert(result.message || 'Unable to update report');
        }
    })
    .catch(error => {
        alert('Error updating report: ' + error);
    });
}

function report_final() {
    const reqInput = document.getElementById('hid_value_req_id');
    const modalBody = document.getElementById('testDataEntryBody');
    const csrfField = modalBody ? modalBody.querySelector('input[name]') : null;

    if (!reqInput) {
        alert('Request id not found');
        return;
    }

    if (!confirm('Are you sure you want to Confirm?')) {
        return;
    }

    const formData = new FormData();
    if (csrfField) {
        formData.append(csrfField.name, csrfField.value);
    }

    const modeInput = document.getElementById('report_mode');
    const isXrayMode = modeInput && modeInput.value === 'xray';
    const verifyEndpoint = isXrayMode
        ? (baseUrl + 'diagnosis/confirm-report-xray/' + reqInput.value)
        : (baseUrl + 'diagnosis/confirm-report/' + reqInput.value);

    fetch(verifyEndpoint, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            alert(result.message || 'Verified');
            refreshTestList();
            refreshLabDateShow();
        } else {
            alert(result.message || 'Unable to verify report');
        }
    })
    .catch(error => {
        alert('Error verifying report: ' + error);
    });
}

function saveTestData(labReqId, testName) {
    const remarks = document.getElementById('testRemarks').value;
    const status = document.getElementById('testStatus').value;
    
    if (!remarks.trim()) {
        alert('Please enter test remarks/findings');
        return;
    }
    
    console.log('Saving test data: labReqId=' + labReqId + ', status=' + status);
    alert('Test data saved successfully!\n\nTest: ' + testName + '\nStatus: ' + (status == 2 ? 'Completed' : status == 1 ? 'In Progress' : 'Pending'));
    
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('testDataEntryModal'));
    if (modal) modal.hide();
    
    // Refresh test list to show updated status
    setTimeout(() => {
        refreshTestList();
    }, 500);
}

function editTestDataDetailed(reqId, testName) {
    const url = baseUrl + 'diagnosis/edit-test-data/' + reqId;
    console.log('Opening detailed edit form:', url);

    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        destroyReportEditor();
        document.getElementById('testDataEntryBody').innerHTML = html;
        const label = document.getElementById('testDataEntryLabel');
        if (label) {
            label.textContent = 'Edit Test Data';
        }
        const modal = new bootstrap.Modal(document.getElementById('testDataEntryModal'));
        modal.show();
    })
    .catch(error => {
        console.error('Error loading detailed edit form:', error);
        alert('Error loading edit form: ' + error);
    });
}

var testDataEntryModalEl = document.getElementById('testDataEntryModal');
if (testDataEntryModalEl) {
    testDataEntryModalEl.addEventListener('hidden.bs.modal', function () {
        destroyReportEditor();
    });
}

function uploadFiles(reqId, testName) {
    diagnosisUploadForReq(reqId, testName, 'file');
}

function scanReportFile(reqId, testName) {
    diagnosisUploadForReq(reqId, testName, 'camera');
}

function diagnosisUploadFromEditor(mode) {
    const reqInput = document.getElementById('hid_value_req_id');
    const title = (document.getElementById('testDataEntryLabel') || {}).textContent || 'Radiology';
    const reqId = reqInput ? parseInt(reqInput.value || '0', 10) : 0;
    diagnosisUploadForReq(reqId, title, mode || 'file');
}

function diagnosisUploadForReq(reqId, testName, mode) {
    if (!invoiceId || !labType) {
        alert('Invoice context missing');
        return;
    }

    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*,application/pdf,.dcm,application/dicom,application/dicom+json';
    if ((mode || '').toLowerCase() === 'camera') {
        input.accept = 'image/*';
        input.setAttribute('capture', 'environment');
    }

    input.onchange = function () {
        const file = input.files && input.files[0] ? input.files[0] : null;
        if (!file) {
            return;
        }

        const modalBody = document.getElementById('testDataEntryBody');
        const csrfField = modalBody ? modalBody.querySelector('input[name]') : null;

        const formData = new FormData();
        formData.append('report_file', file);
        formData.append('invoice_id', String(invoiceId));
        formData.append('lab_type', String(labType));
        formData.append('req_id', String(reqId || 0));
        formData.append('file_desc', (testName || 'Imaging Report') + ((mode === 'camera') ? ' (Camera)' : ' (Upload)'));
        formData.append('scan_type', (mode === 'camera') ? 'camera' : 'upload');
        if (csrfField) {
            formData.append(csrfField.name, csrfField.value);
        }

        fetch(baseUrl + 'diagnosis/upload-report-file', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(result => {
            if ((result.update || 0) !== 1) {
                alert(result.error_text || 'Upload failed');
                return;
            }

            alert((mode === 'camera' ? 'Webcam image saved successfully.' : 'File uploaded successfully.') + ' Use Show Upload Images or AI Diagnosis in the report editor.');
        })
        .catch(error => {
            console.error('Upload error:', error);
            alert('Upload request failed');
        });
    };

    input.click();
}

function showFiles(reqId, testName) {
    if (parseInt(labType || '0', 10) === 5 || parseInt(labType || '0', 10) === 30) {
        const url = baseUrl + 'Lab_Report/report_file_list/' + invoiceId + '/' + labType;
        window.open(url, '_blank');
        return;
    }

    showImagingUploads(reqId, testName);
}

function getEditorImagingContext() {
    const reqInput = document.getElementById('hid_value_req_id');
    const reportNameInput = document.getElementById('hid_value_report_name');
    const modalLabel = document.getElementById('testDataEntryLabel');

    return {
        reqId: reqInput ? parseInt(reqInput.value || '0', 10) : 0,
        testName: (reportNameInput && reportNameInput.value)
            ? reportNameInput.value
            : ((modalLabel && modalLabel.textContent) ? modalLabel.textContent.trim() : 'Imaging Study')
    };
}

window._imagingSupportModalInstance = window._imagingSupportModalInstance || null;

function openImagingSupportModal(title, html) {
    const titleEl = document.getElementById('imagingSupportLabel');
    const bodyEl = document.getElementById('imagingSupportBody');
    if (titleEl) {
        titleEl.textContent = title || 'Imaging Support';
    }
    if (bodyEl) {
        bodyEl.innerHTML = html || '<div class="alert alert-warning mb-0">No content found.</div>';
    }

    const el = document.getElementById('imagingSupportModal');
    if (el) {
        window._imagingSupportModalInstance = bootstrap.Modal.getOrCreateInstance(el);
        window._imagingSupportModalInstance.show();
    }
}

function showImagingUploads(reqId, testName) {
    const studyReqId = parseInt(reqId || '0', 10);
    if (!studyReqId) {
        alert('Study context missing');
        return;
    }

    openImagingSupportModal((testName || 'Imaging Study') + ' Uploads', '<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>');

    fetch(baseUrl + 'diagnosis/imaging-upload-gallery/' + invoiceId + '/' + labType + '/' + studyReqId, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        openImagingSupportModal((testName || 'Imaging Study') + ' Uploads', html);
    })
    .catch(error => {
        console.error('showImagingUploads error:', error);
        openImagingSupportModal((testName || 'Imaging Study') + ' Uploads', '<div class="alert alert-danger mb-0">Unable to load uploaded images.</div>');
    });
}

function showImagingUploadsFromEditor() {
    const context = getEditorImagingContext();
    showImagingUploads(context.reqId, context.testName);
}

function runImagingAiDiagnosis(reqId, testName) {
    const studyReqId = parseInt(reqId || '0', 10);
    if (!studyReqId) {
        alert('Study context missing');
        return;
    }

    const modalBody = document.getElementById('testDataEntryBody');
    const csrfField = modalBody ? modalBody.querySelector('input[name]') : null;
    const formData = new FormData();
    if (csrfField) {
        formData.append(csrfField.name, csrfField.value);
    }

    openImagingSupportModal((testName || 'Imaging Study') + ' AI Diagnosis', '<div class="text-center py-4"><div class="spinner-border" role="status"></div><div class="mt-2 text-muted">AI is reviewing uploaded images...</div></div>');

    fetch(baseUrl + 'diagnosis/imaging-ai-diagnosis/' + studyReqId, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(result => {
        if ((result.update || 0) !== 1) {
            openImagingSupportModal((testName || 'Imaging Study') + ' AI Diagnosis', '<div class="alert alert-danger mb-0">' + (result.error_text || 'AI diagnosis failed') + '</div>');
            return;
        }

        openImagingSupportModal((testName || 'Imaging Study') + ' AI Diagnosis', result.html || '<div class="alert alert-warning mb-0">AI result not available.</div>');
    })
    .catch(error => {
        console.error('runImagingAiDiagnosis error:', error);
        openImagingSupportModal((testName || 'Imaging Study') + ' AI Diagnosis', '<div class="alert alert-danger mb-0">AI diagnosis request failed.</div>');
    });
}

function runImagingAiDiagnosisFromEditor() {
    const context = getEditorImagingContext();
    runImagingAiDiagnosis(context.reqId, context.testName);
}

function applyAiDiagnosisDraftToEditor(button, autoSave) {
    if (!button) {
        return;
    }

    const findingsTarget = button.getAttribute('data-findings-target');
    const impressionTarget = button.getAttribute('data-impression-target');
    const findingsInput = findingsTarget ? document.querySelector(findingsTarget) : null;
    const impressionInput = impressionTarget ? document.querySelector(impressionTarget) : null;
    const findingsHtml = findingsInput ? findingsInput.value : '';
    const impressionHtml = impressionInput ? impressionInput.value : '';

    if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.HTMLShow) {
        CKEDITOR.instances.HTMLShow.setData(findingsHtml || '');
    } else {
        const htmlField = document.getElementById('HTMLShow');
        if (htmlField) {
            htmlField.value = findingsHtml || '';
        }
    }

    if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.report_data_Impression) {
        CKEDITOR.instances.report_data_Impression.setData(impressionHtml || '');
    } else {
        const impressionField = document.getElementById('report_data_Impression');
        if (impressionField) {
            impressionField.value = impressionHtml || '';
        }
    }

    const modalEl = document.getElementById('imagingSupportModal');
    if (window._imagingSupportModalInstance) {
        window._imagingSupportModalInstance.hide();
    }

    if (autoSave === true) {
        setTimeout(function () {
            update_report();
        }, 180);
        return;
    }

    alert('AI draft pasted into the report editor. Review before saving.');
}

function pasteAiDiagnosisDraft(button) {
    applyAiDiagnosisDraftToEditor(button, false);
}

function pasteAiDiagnosisDraftAndSave(button) {
    applyAiDiagnosisDraftToEditor(button, true);
}

function openForEdit(reqId, testName) {
    alert('Open for edit not yet implemented for req ID: ' + reqId);
}

function printSingleReport(reqId, testName, templateId) {
    const tplId = Number(templateId || 0);
    let printUrl = baseUrl + 'diagnosis/print-single-report/' + reqId;
    if (tplId > 0) {
        printUrl += '?template_id=' + encodeURIComponent(String(tplId));
    }
    window.open(printUrl, '_blank');
}

function openReportEditor(reqId, testName) {
    const reason = prompt('Reason for editing verified report (NABH log):');
    if (reason === null) {
        return;
    }

    const cleanReason = String(reason || '').trim();
    if (!cleanReason) {
        alert('Edit reason is required for NABH audit log.');
        return;
    }

    const editorUrl = baseUrl + 'diagnosis/open-report-editor/' + reqId + '?edit_reason=' + encodeURIComponent(cleanReason);
    load_form(editorUrl, (testName || 'Radiology') + ' - Report Editor');
}

function toggleTestDetails(reqId) {
    const detailsDiv = document.getElementById('details_' + reqId);
    if (detailsDiv) {
        detailsDiv.style.display = (detailsDiv.style.display === 'none') ? 'block' : 'none';
    }
}

function onChangeUpdate(cb, itemId) {
    const checkValue = cb.checked ? 1 : 0;
    console.log('onChangeUpdate called: itemId=' + itemId + ', checked=' + checkValue);

    const formData = new FormData();
    formData.append('item_id', itemId);
    formData.append('checked', checkValue);

    fetch(baseUrl + 'diagnosis/update-combine-report', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status !== 'success') {
            alert(result.message || 'Unable to update print combine');
            cb.checked = !cb.checked;
        }
    })
    .catch(error => {
        console.error('onChangeUpdate error:', error);
        cb.checked = !cb.checked;
        alert('Error updating print combine: ' + error);
    });
}

function removeTest(reqId) {
    if (confirm('Are you sure you want to remove this test?')) {
        const formData = new FormData();

        fetch(baseUrl + 'diagnosis/remove-test/' + reqId, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                alert(result.message || 'Item Removed');
                refreshTestList();
                refreshLabDateShow();
            } else {
                alert(result.message || 'Unable to remove test');
            }
        })
        .catch(error => {
            console.error('removeTest error:', error);
            alert('Error removing test: ' + error);
        });
    }
}

function updateLabNo() {
    const labReqId = document.getElementById('lab_req_id').value;
    const labNo = document.getElementById('inputLabNo').value;

    if (!labNo) {
        alert('Please enter a lab test number');
        return;
    }

    const data = new FormData();
    data.append('lab_test_no', labNo);

    fetch(baseUrl + 'diagnosis/update-lab-no/' + labReqId, {
        method: 'POST',
        body: data,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            alert('Lab test number updated successfully');
        } else {
            alert('Error: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating lab test number');
    });
}

function compileReport() {
    const invoiceId = document.getElementById('invoiceId').value;
    const formData = new FormData();

    fetch(baseUrl + 'diagnosis/report-compile/' + invoiceId + '/' + labType, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            alert(result.message || 'Data Compile');
            refreshTestList();
            refreshLabDateShow();
        } else {
            alert(result.message || 'Compile failed');
        }
    })
    .catch(error => {
        console.error('compileReport error:', error);
        alert('Error compiling report: ' + error);
    });
}

function generateReportByTemplate(templateId) {
    const tplId = Number(templateId || 0);
    const invoiceId = document.getElementById('invoiceId').value;
    const url = baseUrl + 'Lab_Admin/print_pdf_create/' + invoiceId + '/' + labType + '/1';
    const finalUrl = tplId > 0 ? (url + '?template_id=' + encodeURIComponent(String(tplId))) : url;
    window.open(finalUrl, '_blank');
}

function uploadReport() {
    const invoiceId = document.getElementById('invoiceId').value;
    alert('PDF upload not yet implemented');
}

function scanReport() {
    const invoiceId = document.getElementById('invoiceId').value;
    alert('Report scanning not yet implemented');
}

function showReports() {
    const invoiceId = document.getElementById('invoiceId').value;
    const url = baseUrl + 'diagnosis/report-file-list/' + invoiceId + '/' + labType;
    window.open(url, '_blank');
}

// Load data when script finishes executing
console.log('Loading data for invoice:', invoiceId, 'labType:', labType);
refreshTestList();
refreshLabDateShow();
</script>

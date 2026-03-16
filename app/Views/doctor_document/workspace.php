<div class="pagetitle">
    <h1>Doctor Documents Workspace</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item active">Doctor Documents</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Template Master</h5>
                    <p class="text-muted mb-3">Create and edit certificate templates with custom fields.</p>
                    <button type="button" class="btn btn-primary" onclick="load_form('<?= base_url('Doc_Admin/doc_list') ?>')">
                        Open Template List
                    </button>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Patient Document Issue</h5>
                    <div class="mb-3">
                        <label class="form-label" for="doc_workspace_patient_id">Patient ID / UHID</label>
                        <input type="text" class="form-control" id="doc_workspace_patient_id" placeholder="Enter UHID / Patient ID / last digits">
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-success" onclick="openPatientDoc()">Open Patient Documents</button>
                        <button type="button" class="btn btn-outline-primary" onclick="load_form('<?= base_url('Report/document_list') ?>', 'Document Issue Report')">Document Issue Report</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function openPatientDoc() {
    var patientKey = (document.getElementById('doc_workspace_patient_id').value || '').trim();
    if (!patientKey) {
        alert('Enter patient id');
        return;
    }

    var resolveUrl = '<?= base_url('Document_Patient/open_by_key') ?>?patient_key=' + encodeURIComponent(patientKey);
    fetch(resolveUrl, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data && Number(data.status) === 1 && Number(data.patient_id) > 0) {
                load_form('<?= base_url('Document_Patient/p_doc_record') ?>/' + Number(data.patient_id));
                return;
            }
            alert((data && data.message) ? data.message : 'Patient not found');
        })
        .catch(function() {
            alert('Unable to resolve patient. Try again.');
        });
}
</script>

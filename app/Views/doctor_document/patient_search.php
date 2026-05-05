<?php
    $search = trim((string) ($search ?? ''));
    $patients = isset($patients) && is_array($patients) ? $patients : [];
    $selfUrl = base_url('doctor_work/patient_search');
?>

<div class="pagetitle">
    <h1>Doctor Patient Search</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item active">Doctor Patient Search</li>
        </ol>
    </nav>
</div>

<section class="section">
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">Search Patient</h5>
            <form onsubmit="return loadDoctorPatientSearch(this);" class="row g-2 align-items-end">
                <div class="col-lg-8">
                    <label class="form-label" for="doctor_patient_search_q">Name / UHID / Mobile / Patient ID</label>
                    <input type="text" class="form-control" id="doctor_patient_search_q" name="q" value="<?= esc($search) ?>" placeholder="Enter patient name, UHID, mobile, or patient id">
                </div>
                <div class="col-lg-4 d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="load_form('<?= esc($selfUrl, 'js') ?>','Doctor Patient Search');">Reset</button>
                </div>
            </form>
            <div class="small text-muted mt-2">Each result includes consult history, patient document issue, and summary counts for OPD, lab tests, and IPD admissions.</div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h5 class="card-title mb-0">Patient Results</h5>
                <span class="badge bg-secondary"><?= count($patients) ?> record(s)</span>
            </div>

            <?php if ($patients === []) : ?>
                <div class="alert alert-info mb-0">No patient found for the current search.</div>
            <?php else : ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0" id="doctorPatientSearchTable">
                        <thead>
                        <tr>
                            <th>UHID</th>
                            <th>Patient</th>
                            <th>Age</th>
                            <th>Last Visit</th>
                            <th>History</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($patients as $patient) : ?>
                            <?php
                                $patientId = (int) ($patient['id'] ?? 0);
                                $historyBackUrl = $selfUrl;
                                if ($search !== '') {
                                    $historyBackUrl .= '?' . http_build_query(['q' => $search]);
                                }
                                $historyUrl = base_url('billing/patient/show_profile_opd') . '/' . $patientId . '/1?' . http_build_query([
                                    'back_url' => $historyBackUrl,
                                    'back_title' => 'Doctor Patient Search',
                                ]);
                                $documentUrl = base_url('Document_Patient/p_doc_record') . '/' . $patientId;
                                    $documentUrl = base_url('Document_Patient/p_doc_record') . '/' . $patientId . '?' . http_build_query([
                                        'back_url' => $historyBackUrl,
                                        'back_title' => 'Doctor Patient Search',
                                    ]);
                                $patientRawName = trim((string) ($patient['p_fname'] ?? '') . ' { ' . (string) ($patient['p_rname'] ?? '') . ' }');
                                $patientAge = function_exists('get_age_1')
                                    ? trim((string) get_age_1($patient['dob'] ?? null, $patient['age'] ?? '', $patient['age_in_month'] ?? '', $patient['estimate_dob'] ?? '', $patient['last_visit'] ?? null))
                                    : trim((string) ($patient['age'] ?? ''));
                            ?>
                            <tr>
                                <td><?= esc((string) ($patient['p_code'] ?? '')) ?></td>
                                <td><?= esc($patientRawName) ?></td>
                                <td><?= esc($patientAge) ?></td>
                                <td><?= esc((string) ($patient['Last_Visit'] ?? '')) ?></td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <span class="badge bg-primary-subtle text-primary border">OPD: <?= (int) ($patient['opd_count'] ?? 0) ?></span>
                                        <span class="badge bg-success-subtle text-success border">Lab: <?= (int) ($patient['lab_count'] ?? 0) ?></span>
                                        <span class="badge bg-warning-subtle text-dark border">IPD: <?= (int) ($patient['ipd_count'] ?? 0) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="javascript:load_form('<?= esc($historyUrl, 'js') ?>','Consult History');" class="btn btn-info btn-sm">
                                            <i class="bi bi-clock-history"></i> History
                                        </a>
                                        <a href="javascript:load_form('<?= esc($documentUrl, 'js') ?>','Patient Documents');" class="btn btn-primary btn-sm">
                                            <i class="bi bi-file-earmark-text"></i> Issue Document
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
function loadDoctorPatientSearch(form) {
    var query = '';
    if (form && form.q) {
        query = String(form.q.value || '').trim();
    }

    var url = '<?= esc($selfUrl, 'js') ?>';
    if (query !== '') {
        url += '?q=' + encodeURIComponent(query);
    }

    load_form(url, 'Doctor Patient Search');
    return false;
}

(function() {
    var table = document.getElementById('doctorPatientSearchTable');
    if (!table || table.dataset.dtInit === '1') {
        return;
    }

    if (window.simpleDatatables && window.simpleDatatables.DataTable) {
        try {
            new window.simpleDatatables.DataTable(table);
            table.dataset.dtInit = '1';
            return;
        } catch (error) {
            console.warn('simple-datatables init failed for doctor patient search table', error);
        }
    }

    if (window.jQuery && $.fn && $.fn.DataTable && $.fn.dataTable && $.fn.dataTable.defaults) {
        try {
            if (!$.fn.DataTable.isDataTable('#doctorPatientSearchTable')) {
                $('#doctorPatientSearchTable').DataTable({
                    order: [[3, 'desc']],
                    pageLength: 25
                });
            }
            table.dataset.dtInit = '1';
        } catch (error) {
            console.warn('jQuery DataTables init failed for doctor patient search table', error);
        }
    }
})();
</script>
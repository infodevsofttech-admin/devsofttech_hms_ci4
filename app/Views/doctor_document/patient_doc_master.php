<?php
$patient = $person_info ?? [];
$patientId = (int) ($pno ?? 0);
$patientName = (string) ($patient['p_fname'] ?? '');
$relativeName = (string) ($patient['p_rname'] ?? '');
$ageText = (string) ($age_text ?? '-');
$genderText = ((int) ($patient['gender'] ?? 0) === 1) ? 'Male' : (((int) ($patient['gender'] ?? 0) === 2) ? 'Female' : 'Other');
?>
<div class="pagetitle">
    <h1>Patient Document Data</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= base_url('/Patient/person_record/' . $patientId) ?>');">Person</a></li>
            <li class="breadcrumb-item active">Documents</li>
        </ol>
    </nav>
</div>

<section class="section">
<form method="post" onsubmit="return false;">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Patient Summary</h5>
            <div class="mb-3">
                <p>
                    <strong>Name :</strong><?= esc($patientName) ?> {<i><?= esc($relativeName) ?></i>}
                    <strong>/ Age :</strong><?= esc($ageText) ?>
                    <strong>/ Gender :</strong><?= esc($genderText) ?>
                    <strong>/ P Code :</strong><?= esc((string) ($patient['p_code'] ?? '')) ?>
                </p>
                <input type="hidden" id="pid" value="<?= $patientId ?>">
                <div class="mt-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="load_form('<?= base_url('Report/document_list') ?>', 'Document Issue Report')">
                        Open Document Issue Report
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Document Type</label>
                        <select class="form-control input-sm" id="doc_format_id">
                            <option value="0">Not in List</option>
                            <?php foreach (($doc_format ?? []) as $df): ?>
                                <option value="<?= (int) ($df['df_id'] ?? 0) ?>"><?= esc((string) ($df['doc_name'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Doctor Name</label>
                        <select class="form-control input-sm" id="doc_name_id">
                            <?php foreach (($doclist ?? []) as $dr): ?>
                                <option value="<?= (int) ($dr['id'] ?? 0) ?>"><?= esc((string) ($dr['p_fname'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <div class="input-group date">
                            <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                            <input class="form-control pull-right input-sm" id="datepicker_doc_date" type="text" value="<?= date('d/m/Y') ?>">
                        </div>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="mb-3 w-100">
                        <button type="button" class="btn btn-primary w-100" id="createdoc">Create</button>
                    </div>
                </div>
            </div>

            <hr>
            <div class="row">
                <div class="col-12 table-responsive">
                    <table class="table table-striped align-middle">
                        <tr>
                            <th style="width:10px">#</th>
                            <th>Issue Date</th>
                            <th>Document Name</th>
                            <th></th>
                        </tr>
                        <?php $n=1; foreach (($patient_doc ?? []) as $row): ?>
                            <tr>
                                <td><?= $n++ ?></td>
                                <td><?= !empty($row['date_issue']) ? esc(date('d-m-Y', strtotime((string) $row['date_issue']))) : '' ?></td>
                                <td><?= esc((string) ($row['doc_name'] ?? '')) ?></td>
                                <td><button type="button" class="btn btn-primary btn-sm" onclick="load_form('<?= base_url('Document_Patient/load_doc/' . (int) ($row['id'] ?? 0)) ?>')">Edit</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
    </div>
</form>
</section>

<script>
$('#createdoc').off('click').on('click', function() {
    var doc_format_id = $('#doc_format_id').val();
    var doc_name_id = $('#doc_name_id').val();
    var datepicker_doc_date = $('#datepicker_doc_date').val();
    var patient_id = $('#pid').val();
    var csrfName = $('input[name="<?= csrf_token() ?>"]').attr('name');
    var csrfHash = $('input[name="<?= csrf_token() ?>"]').val();

    if (!confirm('Are you sure to create?')) {
        return;
    }

    $.post('<?= base_url('Document_Patient/create_doc') ?>', {
        document_format_id: doc_format_id,
        doc_id: doc_name_id,
        patient_id: patient_id,
        doc_issue_date: datepicker_doc_date,
        [csrfName]: csrfHash
    }, function(data) {
        var id = parseInt((data || '').toString(), 10);
        if (id > 0) {
            load_form('<?= base_url('Document_Patient/Pre_Data') ?>/' + id);
        } else {
            alert('Unable to create document');
        }
    });
});
</script>

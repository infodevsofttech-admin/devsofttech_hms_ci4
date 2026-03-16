<?php
$patientDoc = $patient_doc ?? [];
$patient = $person_info ?? [];
$patientDocId = (int) ($patient_doc_id ?? 0);
?>
<div class="pagetitle">
    <h1>Document Input Preview</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= base_url('/Patient/person_record/' . (int) ($patient['id'] ?? 0)) ?>');">Person</a></li>
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= base_url('/Document_Patient/p_doc_record/' . (int) ($patient['id'] ?? 0)) ?>');">Document List</a></li>
            <li class="breadcrumb-item active">Pre-Data</li>
        </ol>
    </nav>
</div>

<section class="section">
    <?= csrf_field() ?>
    <input type="hidden" id="patient_doc_id" value="<?= $patientDocId ?>">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Patient Name: <?= esc((string) ($patient['p_fname'] ?? '')) ?> <small class="text-muted"> / <?= $patientDocId ?></small></h5>
            <div style="max-height:560px;overflow-y:auto;">
            <?php foreach (($doc_format_sub ?? []) as $row): ?>
                <div class="row g-2 align-items-start mb-3">
                    <div class="col-md-3"><label class="form-label mb-0"><?= esc((string) ($row['input_name'] ?? '')) ?></label></div>
                    <div class="col-md-3"><div class="small text-muted"><?= esc((string) ($row['p_doc_raw_value'] ?? '')) ?></div></div>
                    <div class="col-md-4">
                        <?php $inputType = strtolower((string) ($row['input_type'] ?? 'text')); ?>
                        <?php if ($inputType === 'textarea'): ?>
                            <textarea class="form-control" id="input_id_<?= (int) ($row['id'] ?? 0) ?>"><?= esc((string) ($row['p_doc_raw_value'] ?? '')) ?></textarea>
                        <?php else: ?>
                            <input class="form-control" id="input_id_<?= (int) ($row['id'] ?? 0) ?>" type="text" value="<?= esc((string) ($row['p_doc_raw_value'] ?? '')) ?>">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <button onclick="update_data_value(<?= (int) ($row['id'] ?? 0) ?>, document.getElementById('input_id_<?= (int) ($row['id'] ?? 0) ?>').value)" type="button" class="btn btn-outline-primary btn-sm">Save</button>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <button onclick="report_create()" type="button" class="btn btn-primary btn-sm">Report Compile</button>
                <button onclick="load_form('<?= base_url('/Document_Patient/load_doc/' . $patientDocId) ?>')" type="button" class="btn btn-secondary btn-sm">Edit Document</button>
            </div>
        </div>
    </div>
</section>

<script>
function update_data_value(test_id, test_value) {
    var csrfName = $('input[name="<?= csrf_token() ?>"]').attr('name');
    var csrfHash = $('input[name="<?= csrf_token() ?>"]').val();

    $.post('<?= base_url('/Document_Patient/Entry_Update') ?>', {
        test_id: test_id,
        test_value: test_value,
        [csrfName]: csrfHash
    }, function() {
        alert('Saved');
    });
}

function report_create() {
    var patient_doc_id = $('#patient_doc_id').val();
    var csrfName = $('input[name="<?= csrf_token() ?>"]').attr('name');
    var csrfHash = $('input[name="<?= csrf_token() ?>"]').val();

    if (!confirm('Are you sure Re-Compile?')) {
        return;
    }

    $.post('<?= base_url('/Document_Patient/update_doc_field') ?>/' + patient_doc_id, {
        patient_doc_id: patient_doc_id,
        [csrfName]: csrfHash
    }, function(data) {
        alert(data.error_text || 'Done');
        if (parseInt(data.update || '0', 10) === 1) {
            load_form('<?= base_url('/Document_Patient/load_doc') ?>/' + patient_doc_id);
        }
    }, 'json');
}
</script>

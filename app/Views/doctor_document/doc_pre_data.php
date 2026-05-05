<?php
$patientDoc = $patient_doc ?? [];
$patient = $person_info ?? [];
$patientDocId = (int) ($patient_doc_id ?? 0);
$request = service('request');
$backUrl = trim((string) $request->getGet('back_url'));
$backTitle = trim((string) $request->getGet('back_title'));
if ($backUrl === '') {
    $backUrl = base_url('doctor_work/document_workspace');
}
if ($backTitle === '') {
    $backTitle = 'Doctor Documents Workspace';
}
$docListQuery = http_build_query([
    'back_url' => $backUrl,
    'back_title' => $backTitle,
]);
$docListUrl = base_url('/Document_Patient/p_doc_record/' . (int) ($patient['id'] ?? 0));
$docListUrlWithQuery = $docListUrl . ($docListQuery !== '' ? '?' . $docListQuery : '');
?>
<div class="pagetitle">
    <h1>Document Input Preview</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= esc($backUrl, 'js') ?>','<?= esc($backTitle, 'js') ?>');"><?= esc($backTitle) ?></a></li>
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= esc($docListUrlWithQuery, 'js') ?>');">Document List</a></li>
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
                    <?php
                        $inputType = strtolower((string) ($row['input_type'] ?? 'text'));
                        $rawValue = (string) ($row['p_doc_raw_value'] ?? '');
                        $inputValue = $rawValue;
                        if ($inputType === 'date') {
                            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $rawValue, $matches) === 1) {
                                $inputValue = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
                            } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $rawValue, $matches) === 1) {
                                $inputValue = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
                            }
                        }
                    ?>
                    <div class="col-md-3"><label class="form-label mb-0"><?= esc((string) ($row['input_name'] ?? '')) ?></label></div>
                    <div class="col-md-3"><div class="small text-muted" id="saved_value_<?= (int) ($row['id'] ?? 0) ?>"><?= esc($rawValue) ?></div></div>
                    <div class="col-md-4">
                        <?php if ($inputType === 'textarea'): ?>
                            <textarea class="form-control" id="input_id_<?= (int) ($row['id'] ?? 0) ?>" data-input-type="<?= esc($inputType) ?>"><?= esc($rawValue) ?></textarea>
                        <?php elseif ($inputType === 'date'): ?>
                            <input class="form-control" id="input_id_<?= (int) ($row['id'] ?? 0) ?>" data-input-type="<?= esc($inputType) ?>" type="date" value="<?= esc($inputValue) ?>">
                        <?php else: ?>
                            <input class="form-control" id="input_id_<?= (int) ($row['id'] ?? 0) ?>" data-input-type="<?= esc($inputType) ?>" type="text" value="<?= esc($rawValue) ?>">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <button onclick="update_data_value(<?= (int) ($row['id'] ?? 0) ?>)" type="button" class="btn btn-outline-primary btn-sm">Save</button>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <button onclick="report_create()" type="button" class="btn btn-primary btn-sm">Report Compile</button>
                <button onclick="load_form('<?= base_url('/Document_Patient/load_doc/' . $patientDocId) ?>?<?= esc($docListQuery, 'js') ?>')" type="button" class="btn btn-secondary btn-sm">Edit Document</button>
            </div>
        </div>
    </div>
</section>

<script>
function formatInputValueForSave(inputElement) {
    if (!inputElement) {
        return '';
    }

    var rawValue = String(inputElement.value || '').trim();
    if (rawValue === '') {
        return '';
    }

    if ((inputElement.dataset.inputType || '').toLowerCase() !== 'date') {
        return rawValue;
    }

    var match = rawValue.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) {
        return rawValue;
    }

    return match[3] + '/' + match[2] + '/' + match[1];
}

function update_data_value(test_id) {
    var inputElement = document.getElementById('input_id_' + test_id);
    var test_value = formatInputValueForSave(inputElement);
    var csrfName = $('input[name="<?= csrf_token() ?>"]').attr('name');
    var csrfHash = $('input[name="<?= csrf_token() ?>"]').val();

    $.post('<?= base_url('/Document_Patient/Entry_Update') ?>', {
        test_id: test_id,
        test_value: test_value,
        [csrfName]: csrfHash
    }, function() {
        var savedCell = document.getElementById('saved_value_' + test_id);
        if (savedCell) {
            savedCell.textContent = test_value;
        }
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
            load_form('<?= base_url('/Document_Patient/load_doc') ?>/' + patient_doc_id + '?<?= esc($docListQuery, 'js') ?>');
        }
    }, 'json');
}
</script>

<?php
$patientDoc = $patient_doc ?? [];
$patient = $person_info ?? [];
$docId = (int) ($patientDoc['id'] ?? 0);
$html = (string) ($patientDoc['raw_data'] ?? '');
$printTemplates = $print_templates ?? [];
$defaultPrintTemplateId = (int) ($default_print_template_id ?? 0);
?>
<div class="pagetitle">
    <h1>Document Editor</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= base_url('/Patient/person_record/' . (int) ($patient['id'] ?? 0)) ?>');">Person</a></li>
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= base_url('/Document_Patient/p_doc_record/' . (int) ($patient['id'] ?? 0)) ?>');">Document List</a></li>
            <li class="breadcrumb-item active">Editor</li>
        </ol>
    </nav>
</div>

<section class="section">
<form method="post" onsubmit="return false;">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Document Data</h5>
            <div class="mb-3">
                <p>
                    <strong>Name :</strong><?= esc((string) ($patient['p_fname'] ?? '')) ?> {<i><?= esc((string) ($patient['p_rname'] ?? '')) ?></i>}
                    <strong>/ Age :</strong><?= esc((string) ($patient['age'] ?? '')) ?>
                    <strong>/ Gender :</strong><?= ((int) ($patient['gender'] ?? 0) === 1) ? 'Male' : 'Female' ?>
                    <strong>/ P Code :</strong><?= esc((string) ($patient['p_code'] ?? '')) ?>
                </p>
                <input type="hidden" id="document_id" value="<?= $docId ?>">
            </div>

            <textarea id="HTMLData"><?= esc($html) ?></textarea>

            <div class="d-flex flex-wrap gap-2 mt-3">
                <button id="updatereport" type="button" class="btn btn-primary btn-sm">Update</button>
                <button id="editreport" type="button" class="btn btn-outline-primary btn-sm">Refresh</button>
                <button id="Re_Create" type="button" class="btn btn-danger btn-sm">Re-Create</button>
                <?php if (! empty($printTemplates)): ?>
                <div class="btn-group btn-group-sm" role="group" aria-label="Print Group">
                    <?php
                    $defaultTpl = null;
                    $otherTpls = [];
                    foreach ($printTemplates as $tpl) {
                        if ((int) ($tpl['is_default'] ?? 0) === 1) {
                            $defaultTpl = $tpl;
                        } else {
                            $otherTpls[] = $tpl;
                        }
                    }
                    // If non-default templates exist, show only those (hide default).
                    // If only default exists, show it alone.
                    if (! empty($otherTpls)) {
                        $mainTpl   = $otherTpls[0];
                        $dropTpls  = array_slice($otherTpls, 1);
                    } else {
                        $mainTpl   = $defaultTpl ?? $printTemplates[0];
                        $dropTpls  = [];
                    }
                    $mainTplId   = (int) ($mainTpl['id'] ?? 0);
                    $mainTplName = (string) ($mainTpl['template_name'] ?? 'Print (Template)');
                    ?>
                    <button class="btn btn-dark btn-print-tpl" type="button" data-ptid="<?= $mainTplId ?>">
                        <?= esc($mainTplName) ?>
                    </button>
                    <?php if (! empty($dropTpls)): ?>
                    <button type="button" class="btn btn-dark dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle print options</span>
                    </button>
                    <ul class="dropdown-menu">
                        <?php foreach ($dropTpls as $tpl): ?>
                            <?php $tplId = (int) ($tpl['id'] ?? 0); ?>
                            <li>
                                <a class="dropdown-item btn-print-tpl-item" href="#" data-ptid="<?= $tplId ?>">
                                    <?= esc((string) ($tpl['template_name'] ?? ('Template ' . $tplId))) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <a href="javascript:load_form_div('<?= base_url('setting/template/document_print_settings') ?>','maindiv','Document Print Template');" class="btn btn-outline-dark btn-sm">+ Add Print Template</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>
</section>

<script>
if (window.CKEDITOR) {
    if (CKEDITOR.instances.HTMLData) {
        try {
            CKEDITOR.instances.HTMLData.destroy(true);
        } catch (e) {
            console.warn('Unable to destroy stale HTMLData editor instance', e);
        }
    }
    CKEDITOR.replace('HTMLData');
}

$('#updatereport').off('click').on('click', function() {
    var document_id = $('#document_id').val();
    var HTMLData = (window.CKEDITOR && CKEDITOR.instances.HTMLData) ? CKEDITOR.instances.HTMLData.getData() : $('#HTMLData').val();
    var csrfName = $('input[name="<?= csrf_token() ?>"]').attr('name');
    var csrfHash = $('input[name="<?= csrf_token() ?>"]').val();

    $.post('<?= base_url('/Document_Patient/update_doc') ?>', {
        document_id: document_id,
        HTMLData: HTMLData,
        [csrfName]: csrfHash
    }, function(data) {
        alert(data.showcontent || 'Saved');
    }, 'json');
});

$('#editreport').off('click').on('click', function() {
    var document_id = $('#document_id').val();
    load_form('<?= base_url('/Document_Patient/load_doc') ?>/' + document_id);
});

$(document).off('click', '.btn-print-tpl, .btn-print-tpl-item').on('click', '.btn-print-tpl, .btn-print-tpl-item', function(e) {
    e.preventDefault();
    var document_id = $('#document_id').val();
    var ptid = $(this).data('ptid') || '0';
    var $btn = $(this);
    var origHtml = $btn.html();
    var origDisabled = $btn.prop('disabled');

    // Show loading state on the button
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...');

    var url = '<?= base_url('/Document_Patient/create_final') ?>/' + document_id + '?ptid=' + encodeURIComponent(ptid);

    fetch(url, { credentials: 'same-origin' })
        .then(function(response) {
            var ct = response.headers.get('Content-Type') || '';
            if (!response.ok || ct.indexOf('application/pdf') === -1) {
                return response.text().then(function(txt) {
                    throw new Error('PDF generation failed (HTTP ' + response.status + '). ' + txt.substring(0, 300));
                });
            }
            return response.blob();
        })
        .then(function(blob) {
            var blobUrl = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = blobUrl;
            a.target = '_blank';
            a.rel = 'noopener';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(function() { URL.revokeObjectURL(blobUrl); }, 60000);
        })
        .catch(function(err) {
            alert('Print error: ' + err.message);
        })
        .finally(function() {
            $btn.prop('disabled', origDisabled).html(origHtml);
        });
});

$('#Re_Create').off('click').on('click', function() {
    var document_id = $('#document_id').val();
    if (confirm('Are you sure Recreate Document')) {
        load_form('<?= base_url('/Document_Patient/re_create_doc') ?>/' + document_id);
    }
});
</script>

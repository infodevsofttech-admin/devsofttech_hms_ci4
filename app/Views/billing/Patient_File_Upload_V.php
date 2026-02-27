<div class="card">
    <div class="card-body">
        <?php if (!empty($message)) { ?>
            <div class="alert alert-info"><?= esc($message) ?></div>
        <?php } ?>

        <form method="post" enctype="multipart/form-data"
            action="<?= base_url('billing/patient/patient_file_upload') ?>/<?= esc($patient->id) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="doc_type" value="<?= esc($docType ?? '') ?>">
            <input type="hidden" name="update_profile" value="<?= !empty($updateProfile) ? '1' : '0' ?>">

            <div class="mb-2">
                <label class="form-label">Select File</label>
                <input type="file" name="upload_file" class="form-control form-control-sm">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Upload</button>
        </form>
    </div>
</div>

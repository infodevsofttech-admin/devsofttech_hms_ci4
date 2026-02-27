<div class="pagetitle">
    <h1>Insurance Cards</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="javascript:load_form('<?= base_url('billing/patient/person_record') ?>/<?= esc($patient->id) ?>/0');">Profile</a></li>
            <li class="breadcrumb-item active">Insurance</li>
        </ol>
    </nav>
</div>

<section class="section profile">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Insurance Cards</h3>
        </div>
        <div class="card-body">
            <div class="jsError alert alert-info d-none" id="jsError"></div>
            <p class="mb-2">Patient: <strong><?= esc($patient->p_fname ?? '') ?></strong></p>

            <?php
                $card = !empty($cards) ? $cards[0] : null;
                $insCardId = (int) ($card->id ?? 0);
                $insCompanyId = (int) ($card->insurance_id ?? ($selectedInsId ?? 0));
                $insInsuranceNo = (string) ($card->insurance_no ?? '');
                $cardHolderName = (string) ($card->card_holder_name ?? '');
                $relationPatient = (string) ($card->relation_patient_cardholder ?? '');
                $issueDate = !empty($card->issue_date) ? date('d/m/Y', strtotime($card->issue_date)) : date('d/m/Y');
                $expiryDate = !empty($card->expiry_date) ? date('d/m/Y', strtotime($card->expiry_date)) : date('d/m/Y');
            ?>

            <form role="form" class="form1">
                <?= csrf_field() ?>
                <input type="hidden" value="<?= esc((string) $patient->id) ?>" id="p_id" name="p_id" />
                <input type="hidden" value="<?= esc((string) $insCardId) ?>" id="inscard_id" name="inscard_id" />

                <div class="row g-3">
                    <div class="col-lg-4">
                        <label class="form-label">Insurance</label>
                        <select class="form-select" name="Insurance_id" id="Insurance_id">
                            <?php foreach ($insList as $ins) { ?>
                                <option value="<?= esc((string) $ins->id) ?>" <?= $insCompanyId === (int) $ins->id ? 'selected' : '' ?>>
                                    <?= esc($ins->ins_company_name ?? '') ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Insurance ID / No.</label>
                        <input class="form-control" name="input_insurance_id" placeholder="Insurance Number" type="text" value="<?= esc($insInsuranceNo) ?>">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Insurance Card Holder Name</label>
                        <input class="form-control" name="input_card_holder_name" placeholder="Name of Card Holder" type="text" value="<?= esc($cardHolderName) ?>">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Issue Date</label>
                        <input class="form-control datepicker" name="datepicker_issue_date" type="text" value="<?= esc($issueDate) ?>">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Expiry Date</label>
                        <input class="form-control datepicker" name="datepicker_expiry_date" type="text" value="<?= esc($expiryDate) ?>">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Relation</label>
                        <input class="form-control" name="input_Relation" placeholder="Relation" type="text" value="<?= esc($relationPatient) ?>">
                    </div>
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" id="btn_update">Add or Update Record</button>
                    </div>
                </div>
            </form>

            <?php if (empty($cards)) { ?>
                <div class="alert alert-info">No insurance cards found for this patient.</div>
            <?php } else { ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Card No</th>
                                <th>Holder Name</th>
                                <th>Relation</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cards as $card) { ?>
                                <tr>
                                    <td><?= esc($card->ins_company_name ?? '') ?></td>
                                    <td><?= esc($card->insurance_no ?? '') ?></td>
                                    <td><?= esc($card->card_holder_name ?? '') ?></td>
                                    <td><?= esc($card->relation_patient_cardholder ?? '') ?></td>
                                    <td><?= isset($card->status) ? esc((string) $card->status) : '' ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>

            <div class="alert alert-warning mb-0">
                Use the insurance module to add or edit card details.
            </div>
        </div>
    </div>
</section>

<script>
$(document).ready(function() {
    $('#btn_update').click(function() {
        var p_id = $('#p_id').val();
        var csrf_name = '<?= csrf_token() ?>';
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
        var postData = $('form.form1').serializeArray();
        postData.push({ name: csrf_name, value: csrf_value });

        $.post('<?= base_url('billing/patient/update_card') ?>', postData, function(data) {
            if (data.insertid == 0) {
                $('#jsError').removeClass('d-none').text(data.error_text || 'Unable to save insurance card.');
            } else {
                load_form('<?= base_url('billing/patient/person_record') ?>/' + p_id);
            }
        }, 'json');
    });
});
</script>

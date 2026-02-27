<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">OPD Registration</h5>
                    <div class="mb-3">
                        <strong>Name :</strong> <?= esc($person_info[0]->p_fname ?? '') ?>
                        <strong>/ Age :</strong> <?= esc($person_info[0]->age ?? '') ?>
                        <strong>/ Gender :</strong> <?= esc($person_info[0]->xgender ?? '') ?>
                        <strong>/ P Code :</strong>
                        <a href="javascript:load_form('<?= base_url('billing/patient/person_record') ?>/<?= esc($person_info[0]->id ?? 0) ?>/0');">
                            <?= esc($person_info[0]->p_code ?? '') ?>
                        </a>
                    </div>

                    <form class="form1" role="form">
                        <?= csrf_field() ?>
                        <input type="hidden" id="pid" value="<?= esc($person_info[0]->id ?? 0) ?>" />
                        <input type="hidden" id="org_case_id" value="<?= esc($org_case_id ?? 0) ?>" />

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Date</label>
                                <input class="form-control datepicker" id="datepicker_appointment" name="datepicker_appointment"
                                    type="text" value="<?= date('d/m/Y') ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ABHA Address</label>
                                <input class="form-control" id="abha_address" name="abha_address" type="text"
                                    maxlength="18" value="<?= esc($person_info[0]->abha_address ?? $person_info[0]->abha ?? '') ?>"
                                    placeholder="e.g. ravi1234 or ravi.1234">
                            </div>
                        </div>

                        <div class="row g-3 mt-2" id="ShowDoctor">
                            <div class="col-md-8">
                                <label class="form-label">Select Doctor</label>
                                <div class="border rounded p-2">
                                    <?php foreach ($doc_spec_l as $row) : ?>
                                        <label class="d-block">
                                            <input type="radio" id="rdoc_id" name="rdoc_id" class="form-check-input me-1" value="<?= esc($row->id) ?>">
                                            <?= esc($row->p_fname) ?> [<i><?= esc($row->SpecName) ?></i>]
                                        </label>
                                    <?php endforeach ?>
                                </div>
                                <button type="button" class="btn btn-primary mt-2" id="btnnextfee">Next to Fee</button>
                            </div>
                        </div>

                        <div id="showfee" class="mt-3">
                            <div class="row">
                                <div class="col-md-12">
                                    <div id="showfeedocid"></div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-secondary" id="backtodoc">Back to Select Doctor</button>
                                    <button type="button" class="btn btn-primary" id="btnnextconfirm">Confirm And Go for Payment</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    (function() {
        function getCsrfPair() {
            var input = document.querySelector('input[name="<?= csrf_token() ?>"]');
            if (!input) {
                return { name: '<?= csrf_token() ?>', value: '<?= csrf_hash() ?>' };
            }
            return { name: input.getAttribute('name'), value: input.value };
        }

        function updateCsrf(data) {
            if (!data || !data.csrfName || !data.csrfHash) {
                return;
            }
            var input = document.querySelector('input[name="' + data.csrfName + '"]');
            if (input) {
                input.value = data.csrfHash;
            }
        }

        $('#showfee').hide();

        $('#btnnextfee').click(function() {
            var checkValue = $('#rdoc_id:checked').val();
            if (!checkValue) {
                alert('Please Select Doctor Name');
                return;
            }

            var csrf = getCsrfPair();
            $('#showfee').show();
            $.post('<?= base_url('Opd/showfee') ?>', {
                "doc_id": checkValue,
                "org_case_id": $('#org_case_id').val(),
                "pid": $('#pid').val(),
                [csrf.name]: csrf.value
            }, function(data) {
                updateCsrf(data);
                $('#showfeedocid').html(data.content || '');
            }, 'json');

            $('#ShowDoctor').hide();
        });

        $('#backtodoc').click(function() {
            $('#ShowDoctor').show();
            $('#showfee').hide();
        });

        $('#btnnextconfirm').click(function() {
            var checkValue = $('#fee_id:checked').val();
            if (!checkValue) {
                alert('Please Select Fee Name');
                return;
            }

            var csrf = getCsrfPair();
            $.post('<?= base_url('Opd/confirm_opd') ?>', {
                "insurance_fee_found": "0",
                "fee_id": checkValue,
                "doc_id": $('#doc_id').val(),
                "input_clam_id": $('#input_clam_id').val(),
                "pid": $('#pid').val(),
                "datepicker_appointment": $('#datepicker_appointment').val(),
                "abha_address": $('#abha_address').val(),
                [csrf.name]: csrf.value
            }, function(data) {
                updateCsrf(data);
                if (data.insertid == 0) {
                    alert(data.error_text || 'Unable to register OPD.');
                    return;
                }
                load_form('<?= base_url('Opd/invoice') ?>/' + data.insertid);
            }, 'json');
        });
    })();
</script>

<?= form_open() ?>
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0">Refund Amount</h5>
    </div>
    <div class="card-body">
        <input type="hidden" value="<?= esc($refund_order[0]->id ?? 0) ?>" id="refund_id" name="refund_id">
        <div class="row g-3">
            <div class="col-md-4">
                <div><strong>CODE:</strong> <?= esc($refund_order[0]->refund_type_code ?? '') ?></div>
                <div><strong>Patient Name:</strong> <?= esc($refund_order[0]->patient_name ?? '') ?></div>
            </div>
            <div class="col-md-4">
                <div><strong>Refund Reason:</strong> <?= esc($refund_order[0]->refund_type_reason ?? '') ?></div>
                <div><strong>Refund Amount:</strong> Rs. <?= esc($refund_order[0]->refund_amount ?? '') ?></div>
            </div>
            <div class="col-md-4">
                <div><strong>Approved By:</strong> <?= esc($refund_order[0]->approved_by ?? '') ?></div>
                <div><strong>Date Time:</strong> <?= esc($refund_order[0]->approved_datetime ?? '') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="jsError text-danger"></div>

<?php if (($refund_order[0]->refund_process ?? 0) == 0) : ?>
    <div class="card">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Receiver Name</label>
                    <input class="form-control" name="input_name" id="input_name" placeholder="Full Name" type="text" autocomplete="off">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Receiver Phone Number</label>
                    <input class="form-control number" name="input_phone" id="input_phone" placeholder="Phone Number" type="text" autocomplete="off">
                </div>
                <div class="col-md-4 text-md-end">
                    <button type="button" class="btn btn-primary" id="btn_update_refund">Refund Amount</button>
                </div>
            </div>
        </div>
    </div>
<?php else : ?>
    <div class="card">
        <div class="card-body">
            <div class="fw-semibold">Refund Done</div>
        </div>
    </div>
<?php endif; ?>
<?= form_close() ?>

<script>
$(document).ready(function() {
    function enable_btn() {
        $('#btn_update_refund').attr('disabled', false);
    }

    $('#btn_update_refund').click(function() {
        var r_name = $('#input_name').val();
        var r_phone = $('#input_phone').val();
        var r_id = $('#refund_id').val();
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

        $('#btn_update_refund').attr('disabled', true);

        if (confirm('Are you sure process this Refund')) {
            $.post('<?= base_url('Invoice/refund_process') ?>', {
                "r_id": r_id,
                "r_name": r_name,
                "r_phone": r_phone,
                "<?= csrf_token() ?>": csrf_value
            }, function(data) {
                if (data.update == 0) {
                    $('div.jsError').html(data.showcontent);
                    setTimeout(enable_btn, 5000);
                }
                if (typeof load_form === 'function') {
                    load_form('<?= base_url('Invoice/refund_form') ?>/' + r_id);
                }
            }, 'json');
        } else {
            setTimeout(enable_btn, 5000);
        }
    });
});
</script>

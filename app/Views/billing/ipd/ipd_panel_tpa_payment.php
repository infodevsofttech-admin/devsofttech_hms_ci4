<?php
$ipd = $ipd_info ?? null;
$ipdId = (int) ($ipd_id ?? ($ipd->id ?? 0));
?>
<div class="card">
    <div class="card-header">
        <strong>TPA and Other Payment / Discount</strong>
    </div>
    <div class="card-body">
        <input type="hidden" id="hid_ipd_id" name="hid_ipd_id" value="<?= $ipdId ?>" />
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label">Payable By TPA</label>
                <input class="form-control" name="input_payable_by_tpa" id="input_payable_by_tpa" placeholder="0.00" type="number" step="0.01" value="<?= esc($ipd->payable_by_tpa ?? 0) ?>" />
            </div>
            <div class="col-md-4">
                <label class="form-label">Discount For TPA</label>
                <input class="form-control" name="input_discount_for_tpa" id="input_discount_for_tpa" placeholder="0.00" type="number" step="0.01" value="<?= esc($ipd->discount_for_tpa ?? 0) ?>" />
            </div>
        </div>
        <div class="row g-2 mt-2">
            <div class="col-md-4">
                <label class="form-label">Discount By Hospital</label>
                <input class="form-control" name="input_discount_by_hospital" id="input_discount_by_hospital" placeholder="0.00" type="number" step="0.01" value="<?= esc($ipd->discount_by_hospital ?? 0) ?>" />
            </div>
            <div class="col-md-4">
                <label class="form-label">Remark</label>
                <input class="form-control" name="input_discount_by_hospital_remark" id="input_discount_by_hospital_remark" placeholder="" type="text" value="<?= esc($ipd->discount_by_hospital_remark ?? '') ?>" />
            </div>
        </div>
        <div class="row g-2 mt-2">
            <div class="col-md-4">
                <label class="form-label">Discount By Doctor</label>
                <input class="form-control" name="input_discount_by_hospital_2" id="input_discount_by_hospital_2" placeholder="0.00" type="number" step="0.01" value="<?= esc($ipd->discount_by_hospital_2 ?? 0) ?>" />
            </div>
            <div class="col-md-4">
                <label class="form-label">Remark</label>
                <input class="form-control" name="input_discount_by_hospital_2_remark" id="input_discount_by_hospital_2_remark" placeholder="" type="text" value="<?= esc($ipd->discount_by_hospital_2_remark ?? '') ?>" />
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-3">
                <button type="button" class="btn btn-primary" id="btn_update">Update Amount</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        var csrfName = '<?= esc(csrf_token()) ?>';
        var csrfHash = '<?= esc(csrf_hash()) ?>';

        $('#btn_update').on('click', function() {
            var payload = {
                hid_ipd_id: $('#hid_ipd_id').val(),
                input_payable_by_tpa: $('#input_payable_by_tpa').val(),
                input_discount_for_tpa: $('#input_discount_for_tpa').val(),
                input_discount_by_hospital: $('#input_discount_by_hospital').val(),
                input_discount_by_hospital_2: $('#input_discount_by_hospital_2').val(),
                input_discount_by_hospital_remark: $('#input_discount_by_hospital_remark').val(),
                input_discount_by_hospital_2_remark: $('#input_discount_by_hospital_2_remark').val()
            };
            payload[csrfName] = csrfHash;

            $.post('<?= site_url('billing/ipd/payment/tpa') ?>', payload, function(data) {
                if (data.update === 0) {
                    alert(data.show_text || 'Update failed.');
                } else {
                    alert('Updated.');
                }
            }, 'json');
        });
    })();
</script>

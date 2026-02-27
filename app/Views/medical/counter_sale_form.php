<div class="card">
    <div class="card-body pt-3">
        <h5 class="card-title">Walk-in Customer Details</h5>

        <form id="counter-sale-form" class="row g-2" method="post" action="javascript:void(0)">
            <?= csrf_field() ?>

            <div class="col-md-4">
                <label class="form-label">Customer Name</label>
                <input type="text" class="form-control form-control-sm" id="customer_name" name="customer_name" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Phone Number</label>
                <input type="text" class="form-control form-control-sm" id="customer_phone" name="customer_phone" maxlength="10" pattern="[0-9]{10}" inputmode="numeric" autocomplete="off" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Doctor Name</label>
                <select class="form-select form-select-sm" id="doctor_id" name="doctor_id" required>
                    <option value="">Select Doctor</option>
                    <?php foreach (($docList ?? []) as $doc): ?>
                        <option value="<?= (int) ($doc->id ?? 0) ?>"><?= esc($doc->p_fname ?? ('Doctor #' . ($doc->id ?? ''))) ?></option>
                    <?php endforeach; ?>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="col-md-4" id="doctor-other-wrap" style="display:none;">
                <label class="form-label">Other Doctor Name</label>
                <input type="text" class="form-control form-control-sm" id="doctor_other" name="doctor_other">
            </div>

            <div class="col-12 d-flex gap-2 mt-2">
                <button type="button" class="btn btn-primary btn-sm" id="btn_counter_sale_start">Go to Invoice</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="load_form_div('<?= base_url('Medical/search_customer') ?>','medical-main','OPD Search Panel :Pharmacy');">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var doctorSelect = document.getElementById('doctor_id');
    var doctorOtherWrap = document.getElementById('doctor-other-wrap');
    var doctorOtherInput = document.getElementById('doctor_other');
    var phoneInput = document.getElementById('customer_phone');
    var submitBtn = document.getElementById('btn_counter_sale_start');
    var form = document.getElementById('counter-sale-form');

    function toggleDoctorOther() {
        if (!doctorSelect || !doctorOtherWrap) {
            return;
        }

        if (doctorSelect.value === 'other') {
            doctorOtherWrap.style.display = '';
            if (doctorOtherInput) {
                doctorOtherInput.setAttribute('required', 'required');
            }
        } else {
            doctorOtherWrap.style.display = 'none';
            if (doctorOtherInput) {
                doctorOtherInput.removeAttribute('required');
                doctorOtherInput.value = '';
            }
        }
    }

    if (doctorSelect) {
        doctorSelect.addEventListener('change', toggleDoctorOther);
        toggleDoctorOther();
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            var digits = (phoneInput.value || '').replace(/\D/g, '').slice(0, 10);
            phoneInput.value = digits;
        });
    }

    if (!submitBtn || !form) {
        return;
    }

    submitBtn.addEventListener('click', function () {
        var phone = ((phoneInput && phoneInput.value) ? phoneInput.value : '').replace(/\D/g, '');
        if (phone.length !== 10) {
            notify('error', 'Please Attention', 'Phone number must be exactly 10 digits.');
            if (phoneInput) {
                phoneInput.focus();
            }
            return;
        }
        if (phoneInput) {
            phoneInput.value = phone;
        }

        var formData = new FormData(form);
        var body = new URLSearchParams(formData).toString();

        fetch('<?= base_url('Medical/CounterSaleCreate') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (!data || (data.status || 0) === 0) {
                notify('error', 'Please Attention', (data && data.message) ? data.message : 'Unable to create counter sale invoice');
                return;
            }

            notify('success', 'Please Attention', data.message || 'Invoice created');
            if (data.redirect_url) {
                load_form_div(data.redirect_url, 'medical-main', 'Medical Invoice');
            }
        })
        .catch(function () {
            notify('error', 'Please Attention', 'Unable to create counter sale invoice');
        });
    });
})();
</script>

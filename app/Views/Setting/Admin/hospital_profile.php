<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Hospital Profile</h3>
        <span class="badge bg-primary">Used across HMS reports and headers</span>
    </div>
    <div class="card-body">
        <?= csrf_field() ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Hospital Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="hospital_name" value="<?= esc($hospital_name ?? '') ?>" placeholder="Enter hospital name">
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone No</label>
                <input type="text" class="form-control" id="hospital_phone" value="<?= esc($hospital_phone ?? '') ?>" placeholder="Enter hospital phone">
            </div>
            <div class="col-12">
                <label class="form-label">Address <span class="text-danger">*</span></label>
                <textarea class="form-control" id="hospital_address_1" rows="2" placeholder="Enter hospital address"><?= esc($hospital_address_1 ?? '') ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Address Line 2</label>
                <input type="text" class="form-control" id="hospital_address_2" value="<?= esc($hospital_address_2 ?? '') ?>" placeholder="City, State, PIN">
            </div>
            <div class="col-md-6">
                <label class="form-label">Hospital Email</label>
                <input type="text" class="form-control" id="hospital_email" value="<?= esc($hospital_email ?? '') ?>" placeholder="hospital@example.com">
            </div>
            <div class="col-md-6">
                <label class="form-label">Version / Update ID</label>
                <input type="text" class="form-control" id="footer_version" value="<?= esc($footer_version ?? '') ?>" placeholder="e.g. 2026.03.27.01">
                <div class="form-text">Shown in the footer so live deployments can be verified without code changes.</div>
            </div>

            <div class="col-md-7">
                <label class="form-label">Hospital Logo</label>
                <input type="file" class="form-control" id="hospital_logo" accept=".png,.jpg,.jpeg,.webp">
                <div class="form-text">Accepted: PNG, JPG, JPEG, WEBP</div>
            </div>
            <div class="col-md-5">
                <label class="form-label">Current Logo</label>
                <div id="hospital_logo_preview" class="border rounded p-2 text-center bg-light">
                    <?php if (!empty($hospital_logo_url ?? '')): ?>
                        <img src="<?= esc((string) $hospital_logo_url) ?>" alt="Hospital Logo" style="max-height:80px;max-width:100%;">
                        <div class="small text-muted mt-1"><?= esc((string) ($hospital_logo ?? '')) ?></div>
                    <?php else: ?>
                        <span class="text-muted small">No logo uploaded</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 mt-3"><hr><h6 class="mb-2">Pharmacy Information</h6></div>
            <div class="col-md-6">
                <label class="form-label">Pharmacy Name</label>
                <input type="text" class="form-control" id="pharmacy_name" value="<?= esc($pharmacy_name ?? '') ?>" placeholder="Pharmacy display name">
            </div>
            <div class="col-md-6">
                <label class="form-label">Pharmacy Phone</label>
                <input type="text" class="form-control" id="pharmacy_phone" value="<?= esc($pharmacy_phone ?? '') ?>" placeholder="Pharmacy phone">
            </div>
            <div class="col-md-8">
                <label class="form-label">Pharmacy Address</label>
                <input type="text" class="form-control" id="pharmacy_address" value="<?= esc($pharmacy_address ?? '') ?>" placeholder="Pharmacy address">
            </div>
            <div class="col-md-4">
                <label class="form-label">Pharmacy GST</label>
                <input type="text" class="form-control" id="pharmacy_gst" value="<?= esc($pharmacy_gst ?? '') ?>" placeholder="GSTIN">
            </div>
            <div class="col-md-7">
                <label class="form-label">Pharmacy Logo</label>
                <input type="file" class="form-control" id="pharmacy_logo" accept=".png,.jpg,.jpeg,.webp">
                <div class="form-text">Accepted: PNG, JPG, JPEG, WEBP</div>
            </div>
            <div class="col-md-5">
                <label class="form-label">Current Pharmacy Logo</label>
                <div id="pharmacy_logo_preview" class="border rounded p-2 text-center bg-light">
                    <?php if (!empty($pharmacy_logo_url ?? '')): ?>
                        <img src="<?= esc((string) $pharmacy_logo_url) ?>" alt="Pharmacy Logo" style="max-height:80px;max-width:100%;">
                        <div class="small text-muted mt-1"><?= esc((string) ($pharmacy_logo ?? '')) ?></div>
                    <?php else: ?>
                        <span class="text-muted small">No logo uploaded</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap mt-3">
            <button type="button" class="btn btn-primary" id="btn_hospital_profile_save">Save Profile</button>
            <button type="button" class="btn btn-outline-danger" id="btn_hospital_logo_delete">Delete Logo</button>
            <button type="button" class="btn btn-outline-danger" id="btn_pharmacy_logo_delete">Delete Pharmacy Logo</button>
            <button type="button" class="btn btn-outline-dark" id="btn_hospital_profile_reset">Reset All</button>
        </div>

        <div id="hospital_profile_msg" class="mt-3"></div>
    </div>
</div>

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

    function showMessage(type, text) {
        var cls = type === 'success' ? 'alert alert-success' : 'alert alert-danger';
        $('#hospital_profile_msg').html('<div class="' + cls + '">' + $('<div>').text(text || '').html() + '</div>');
    }

    function renderLogo(targetId, url, name) {
        var wrap = $('#' + targetId);
        if (!url) {
            wrap.html('<span class="text-muted small">No logo uploaded</span>');
            return;
        }
        var html = '<img src="' + url + '" alt="Logo" style="max-height:80px;max-width:100%;">'
            + '<div class="small text-muted mt-1">' + $('<div>').text(name || '').html() + '</div>';
        wrap.html(html);
    }

    $('#btn_hospital_profile_save').on('click', function() {
        var name = ($('#hospital_name').val() || '').trim();
        var address = ($('#hospital_address_1').val() || '').trim();
        if (!name || !address) {
            showMessage('error', 'Hospital name and address are required.');
            return;
        }

        var csrf = getCsrfPair();
        var fd = new window.FormData();
        fd.append('hospital_name', name);
        fd.append('hospital_address_1', address);
        fd.append('hospital_address_2', ($('#hospital_address_2').val() || '').trim());
        fd.append('hospital_phone', ($('#hospital_phone').val() || '').trim());
        fd.append('hospital_email', ($('#hospital_email').val() || '').trim());
        fd.append('footer_version', ($('#footer_version').val() || '').trim());
        fd.append('pharmacy_name', ($('#pharmacy_name').val() || '').trim());
        fd.append('pharmacy_address', ($('#pharmacy_address').val() || '').trim());
        fd.append('pharmacy_phone', ($('#pharmacy_phone').val() || '').trim());
        fd.append('pharmacy_gst', ($('#pharmacy_gst').val() || '').trim());
        fd.append(csrf.name, csrf.value);

        var fileInput = document.getElementById('hospital_logo');
        if (fileInput && fileInput.files && fileInput.files.length > 0) {
            fd.append('hospital_logo', fileInput.files[0]);
        }
        var pharmacyLogoInput = document.getElementById('pharmacy_logo');
        if (pharmacyLogoInput && pharmacyLogoInput.files && pharmacyLogoInput.files.length > 0) {
            fd.append('pharmacy_logo', pharmacyLogoInput.files[0]);
        }

        $.ajax({
            url: '<?= base_url('setting/admin/hospital-profile/save') ?>',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function(data) {
            updateCsrf(data || {});
            if ((data.update || 0) !== 1) {
                showMessage('error', data.error_text || 'Unable to save hospital profile.');
                return;
            }
            showMessage('success', data.error_text || 'Saved successfully.');
            if (fileInput) {
                fileInput.value = '';
            }
            if (pharmacyLogoInput) {
                pharmacyLogoInput.value = '';
            }
            renderLogo('hospital_logo_preview', data.logo_url || '', data.logo_name || '');
            renderLogo('pharmacy_logo_preview', data.pharmacy_logo_url || '', data.pharmacy_logo_name || '');
        }).fail(function(xhr) {
            var data = (xhr && xhr.responseJSON) ? xhr.responseJSON : {};
            updateCsrf(data || {});
            showMessage('error', data.error_text || 'Server error while saving profile.');
        });
    });

    $('#btn_hospital_logo_delete').on('click', function() {
        if (!confirm('Delete hospital logo?')) {
            return;
        }
        var csrf = getCsrfPair();
        var payload = {};
        payload[csrf.name] = csrf.value;

        payload.type = 'hospital';

        $.post('<?= base_url('setting/admin/hospital-profile/delete-logo') ?>', payload, function(data) {
            updateCsrf(data || {});
            if ((data.update || 0) !== 1) {
                showMessage('error', data.error_text || 'Unable to delete logo.');
                return;
            }
            showMessage('success', data.error_text || 'Logo deleted.');
            renderLogo('hospital_logo_preview', '', '');
        }, 'json').fail(function(xhr) {
            var data = (xhr && xhr.responseJSON) ? xhr.responseJSON : {};
            updateCsrf(data || {});
            showMessage('error', data.error_text || 'Server error while deleting logo.');
        });
    });

    $('#btn_pharmacy_logo_delete').on('click', function() {
        if (!confirm('Delete pharmacy logo?')) {
            return;
        }
        var csrf = getCsrfPair();
        var payload = {};
        payload[csrf.name] = csrf.value;
        payload.type = 'pharmacy';

        $.post('<?= base_url('setting/admin/hospital-profile/delete-logo') ?>', payload, function(data) {
            updateCsrf(data || {});
            if ((data.update || 0) !== 1) {
                showMessage('error', data.error_text || 'Unable to delete pharmacy logo.');
                return;
            }
            showMessage('success', data.error_text || 'Pharmacy logo deleted.');
            renderLogo('pharmacy_logo_preview', '', '');
        }, 'json').fail(function(xhr) {
            var data = (xhr && xhr.responseJSON) ? xhr.responseJSON : {};
            updateCsrf(data || {});
            showMessage('error', data.error_text || 'Server error while deleting pharmacy logo.');
        });
    });

    $('#btn_hospital_profile_reset').on('click', function() {
        if (!confirm('Reset hospital name, address, phone and logo?')) {
            return;
        }
        var csrf = getCsrfPair();
        var payload = {};
        payload[csrf.name] = csrf.value;

        $.post('<?= base_url('setting/admin/hospital-profile/reset') ?>', payload, function(data) {
            updateCsrf(data || {});
            if ((data.update || 0) !== 1) {
                showMessage('error', data.error_text || 'Unable to reset profile settings.');
                return;
            }

            $('#hospital_name').val('');
            $('#hospital_address_1').val('');
            $('#hospital_address_2').val('');
            $('#hospital_phone').val('');
            $('#hospital_email').val('');
            $('#footer_version').val('');
            $('#pharmacy_name').val('');
            $('#pharmacy_address').val('');
            $('#pharmacy_phone').val('');
            $('#pharmacy_gst').val('');
            var fileInput = document.getElementById('hospital_logo');
            if (fileInput) {
                fileInput.value = '';
            }
            var pharmacyLogoInput = document.getElementById('pharmacy_logo');
            if (pharmacyLogoInput) {
                pharmacyLogoInput.value = '';
            }
            renderLogo('hospital_logo_preview', '', '');
            renderLogo('pharmacy_logo_preview', '', '');
            showMessage('success', data.error_text || 'Profile reset completed.');
        }, 'json').fail(function(xhr) {
            var data = (xhr && xhr.responseJSON) ? xhr.responseJSON : {};
            updateCsrf(data || {});
            showMessage('error', data.error_text || 'Server error while resetting profile.');
        });
    });
})();
</script>

<section class="content">
    <div class="pagetitle mb-3">
        <h1 class="mb-0">OPD Queue TV Display Settings</h1>
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="javascript:showAdminTiles()">Admin Panel</a></li>
                <li class="breadcrumb-item active">OPD Queue TV Settings</li>
            </ol>
        </nav>
    </div>

    <div class="row g-3">
        <!-- Left Side: Ticker & Left Bottom Ad Settings -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bi bi-fonts me-2"></i> Footer Scrolling Ticker Text
                </div>
                <div class="card-body mt-3">
                    <form id="form_ticker_settings" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Scrolling Ticker Message</label>
                            <textarea class="form-control" name="footer_ticker" rows="3" required><?= esc($footer_ticker) ?></textarea>
                            <small class="text-muted">This message scrolls continuously across the bottom of the TV screen.</small>
                        </div>

                        <hr>

                        <h6 class="fw-bold text-primary mb-3">
                            <i class="bi bi-layout-sidebar-inset me-1"></i> Left-Side Bottom Ad Space Settings
                        </h6>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="left_ad_enabled" id="left_ad_enabled" value="1" <?= $left_ad_enabled === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold" for="left_ad_enabled">Enable Left-Side Bottom Ad Space</label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Left-Side Ad Text / Announcement</label>
                            <input type="text" class="form-control" name="left_ad_text" value="<?= esc($left_ad_text) ?>" placeholder="e.g. 24x7 Emergency ICU & Ambulance Available">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Left-Side Custom Image Banner (Optional)</label>
                            <input type="file" class="form-control" name="left_ad_image_file" accept="image/*">
                            <?php if (!empty($left_ad_image)): ?>
                                <div class="mt-2">
                                    <small class="d-block text-muted mb-1">Current Banner:</small>
                                    <img src="<?= base_url($left_ad_image) ?>" style="max-height: 80px; border-radius: 8px;" class="border shadow-sm" />
                                </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary fw-bold px-4">
                            <i class="bi bi-save me-1"></i> Save Ticker &amp; Left Ad Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Side: Showcase & Ad Banners Management -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-success text-white fw-bold">
                    <i class="bi bi-file-earmark-image me-2"></i> Right-Side Showcase Ad Banners
                </div>
                <div class="card-body mt-3">
                    <form id="form_add_banner" enctype="multipart/form-data">
                        <h6 class="fw-bold text-success mb-3">Add New Right-Side Showcase Slide</h6>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small mb-1">Slide Type</label>
                                <select class="form-select form-select-sm" name="slide_type">
                                    <option value="hospital_ad">Hospital Ad Banner</option>
                                    <option value="doctor_profile">Doctor Profile Showcase</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small mb-1">Slide Title *</label>
                                <input type="text" class="form-control form-control-sm" name="title" required placeholder="e.g. In-House 24/7 Pharmacy">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold small mb-1">Tagline / Subtitle</label>
                            <input type="text" class="form-control form-control-sm" name="tagline" placeholder="e.g. 100% Genuine Medicines & Fast Dispense">
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold small mb-1">Description / Details</label>
                            <textarea class="form-control form-control-sm" name="description" rows="2" placeholder="e.g. Available 24/7 for inpatient and outpatient needs..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small mb-1">Upload Banner Image *</label>
                            <input type="file" class="form-control form-control-sm" name="banner_image" accept="image/*" required>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="enabled" id="slide_enabled" value="1" checked>
                            <label class="form-check-label small fw-bold" for="slide_enabled">Active Slide in Carousel</label>
                        </div>

                        <button type="submit" class="btn btn-success btn-sm fw-bold px-3">
                            <i class="bi bi-plus-circle me-1"></i> Add Slide Banner
                        </button>
                    </form>

                    <hr class="my-4">

                    <h6 class="fw-bold text-dark mb-3">Configured Right-Side Slides</h6>
                    <div id="custom_ads_list_container">
                        <?php if (empty($custom_ads)): ?>
                            <div class="alert alert-info small py-2">
                                No custom ad slides uploaded yet. Default system slides are active.
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($custom_ads as $ad): ?>
                                    <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-light">
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($ad['relative_image_url'])): ?>
                                                <img src="<?= base_url($ad['relative_image_url']) ?>" style="width: 50px; height: 40px; object-fit: cover;" class="rounded border" />
                                            <?php else: ?>
                                                <div class="bg-secondary text-white rounded p-1 small" style="width: 50px; text-align: center;">No Img</div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold small"><?= esc($ad['title']) ?></div>
                                                <small class="text-muted d-block opacity-75" style="font-size: 11px;"><?= esc($ad['tagline'] ?? '') ?></small>
                                            </div>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="deleteBanner(<?= (int) ($ad['id'] ?? 0) ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    $('#form_ticker_settings').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: '<?= base_url('setting/admin/opd-queue-tv/save') ?>',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(res) {
                if (res.status === 1) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message || 'Error saving settings');
                }
            }
        });
    });

    $('#form_add_banner').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: '<?= base_url('setting/admin/opd-queue-tv/upload-banner') ?>',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(res) {
                if (res.status === 1) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message || 'Error uploading banner');
                }
            }
        });
    });

    function deleteBanner(bannerId) {
        if (!confirm('Are you sure you want to delete this advertisement slide?')) return;
        $.ajax({
            url: '<?= base_url('setting/admin/opd-queue-tv/delete-banner') ?>',
            type: 'POST',
            data: { banner_id: bannerId },
            dataType: 'json',
            success: function(res) {
                if (res.status === 1) {
                    alert(res.message);
                    location.reload();
                } else {
                    alert(res.message || 'Error deleting banner');
                }
            }
        });
    }
</script>

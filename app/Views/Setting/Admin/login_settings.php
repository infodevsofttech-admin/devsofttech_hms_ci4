<?php $isStandalone = $is_standalone ?? false; ?>
<?php if ($isStandalone): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page Customizer - Admin Panel</title>
    <link href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
    <script src="<?= base_url('assets/vendor/jquery/jquery-3.7.1.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
</head>
<body class="bg-light py-4 px-2 px-md-4">
    <div class="container-fluid" style="max-width: 1200px;">
        <div class="mb-3 d-flex align-items-center justify-content-between">
            <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
            <span class="text-muted small"><i class="bi bi-shield-check text-success me-1"></i> Hospital Administration</span>
        </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h4 class="card-title mb-1 fw-bold text-dark"><i class="bi bi-palette2 text-primary me-2"></i>Login Page Customizer</h4>
            <div class="text-muted small">Configure layout positioning (Left, Center, Right), logo styling, color themes, and wallpapers for the software login page.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('login') ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-box-arrow-up-right me-1"></i> Preview Live Login Page
            </a>
        </div>
    </div>

    <div class="card-body p-4">
        <?= csrf_field() ?>

        <style>
            .layout-choice-card {
                border: 2px solid #e2e8f0;
                border-radius: 12px;
                padding: 14px;
                cursor: pointer;
                transition: all 0.2s ease;
                background: #f8fafc;
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
            .layout-choice-card:hover {
                border-color: #94a3b8;
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
            }
            .layout-choice-card.active {
                border-color: #0d6efd;
                background: #eff6ff;
                box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
            }
            .layout-wireframe {
                height: 70px;
                border-radius: 6px;
                background: #e2e8f0;
                display: flex;
                gap: 4px;
                padding: 5px;
                margin-bottom: 10px;
                overflow: hidden;
            }
            .wire-hero {
                background: #0d3b66;
                border-radius: 4px;
                flex: 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                color: #ffffff;
                font-size: 8px;
            }
            .wire-form {
                width: 38%;
                background: #ffffff;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 3px;
                padding: 4px;
            }
            .wire-input-bar {
                height: 4px;
                width: 80%;
                background: #cbd5e1;
                border-radius: 2px;
            }
            .wire-center-card {
                width: 55%;
                margin: auto;
                background: #ffffff;
                border-radius: 4px;
                box-shadow: 0 2px 6px rgba(0,0,0,0.15);
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 6px 4px;
                gap: 3px;
            }
            .preset-thumb-card {
                border: 2px solid #e2e8f0;
                border-radius: 10px;
                padding: 8px;
                cursor: pointer;
                transition: all 0.2s ease;
                background: #ffffff;
            }
            .preset-thumb-card:hover {
                border-color: #0d6efd;
                transform: translateY(-2px);
            }
            .preset-thumb-card.active {
                border-color: #0d6efd;
                box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
            }
        </style>

        <form id="form_login_settings">
            <!-- 1. Layout & Placement Selection -->
            <div class="mb-4">
                <label class="form-label fw-bold text-dark fs-6 mb-1">
                    <i class="bi bi-layout-sidebar-inset text-primary me-1"></i> 1. Login Panel Placement
                </label>
                <div class="text-muted small mb-3">Choose whether the login credentials form appears on the Left, Center, or Right side of the screen.</div>

                <div class="row g-3">
                    <!-- Left -->
                    <div class="col-md-4">
                        <div class="layout-choice-card <?= ($login_panel_position ?? 'right') === 'left' ? 'active' : '' ?>" data-position="left">
                            <div class="layout-wireframe">
                                <div class="wire-form">
                                    <div class="wire-input-bar" style="background:#0d6efd; width:50%;"></div>
                                    <div class="wire-input-bar"></div>
                                    <div class="wire-input-bar"></div>
                                    <div class="wire-input-bar" style="background:#0d6efd; height:5px;"></div>
                                </div>
                                <div class="wire-hero">
                                    <span>Branding</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="fw-bold d-block text-dark">Left Panel</span>
                                    <span class="text-muted small" style="font-size: 11px;">Form on Left, Wallpaper on Right</span>
                                </div>
                                <input type="radio" name="login_panel_position" value="left" class="form-check-input" <?= ($login_panel_position ?? 'right') === 'left' ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <!-- Center -->
                    <div class="col-md-4">
                        <div class="layout-choice-card <?= ($login_panel_position ?? 'right') === 'center' ? 'active' : '' ?>" data-position="center">
                            <div class="layout-wireframe" style="background: #0d3b66;">
                                <div class="wire-center-card">
                                    <div class="wire-input-bar" style="background:#0d6efd; width:50%;"></div>
                                    <div class="wire-input-bar"></div>
                                    <div class="wire-input-bar"></div>
                                    <div class="wire-input-bar" style="background:#0d6efd; height:5px;"></div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="fw-bold d-block text-dark">Center Modal</span>
                                    <span class="text-muted small" style="font-size: 11px;">Full wallpaper, Centered floating card</span>
                                </div>
                                <input type="radio" name="login_panel_position" value="center" class="form-check-input" <?= ($login_panel_position ?? 'right') === 'center' ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <!-- Right (Default) -->
                    <div class="col-md-4">
                        <div class="layout-choice-card <?= ($login_panel_position ?? 'right') === 'right' ? 'active' : '' ?>" data-position="right">
                            <div class="layout-wireframe">
                                <div class="wire-hero">
                                    <span>Branding</span>
                                </div>
                                <div class="wire-form">
                                    <div class="wire-input-bar" style="background:#0d6efd; width:50%;"></div>
                                    <div class="wire-input-bar"></div>
                                    <div class="wire-input-bar"></div>
                                    <div class="wire-input-bar" style="background:#0d6efd; height:5px;"></div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="fw-bold d-block text-dark">Right Panel <span class="badge bg-secondary ms-1">Standard</span></span>
                                    <span class="text-muted small" style="font-size: 11px;">Wallpaper on Left, Form on Right</span>
                                </div>
                                <input type="radio" name="login_panel_position" value="right" class="form-check-input" <?= ($login_panel_position ?? 'right') === 'right' ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- 2. Logo Presentation & 3D Plaque -->
            <div class="mb-4">
                <label class="form-label fw-bold text-dark fs-6 mb-1">
                    <i class="bi bi-box text-primary me-1"></i> 2. Hospital Logo Container Style
                </label>
                <div class="text-muted small mb-3">Select how the transparent hospital logo is mounted so it always stands out clearly.</div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="p-3 border rounded-3 w-100 h-100 d-flex align-items-center gap-3 cursor-pointer bg-light">
                            <input type="radio" name="login_logo_style" value="3d_box" class="form-check-input flex-shrink-0" <?= ($login_logo_style ?? '3d_box') === '3d_box' ? 'checked' : '' ?>>
                            <div>
                                <span class="fw-bold d-block text-dark">3D Raised Plaque</span>
                                <span class="text-muted small" style="font-size: 12px;">Curved corners, tactile bevel highlight, deep 3D drop shadow (Recommended for dark/photo wallpapers).</span>
                            </div>
                        </label>
                    </div>

                    <div class="col-md-4">
                        <label class="p-3 border rounded-3 w-100 h-100 d-flex align-items-center gap-3 cursor-pointer bg-light">
                            <input type="radio" name="login_logo_style" value="glass" class="form-check-input flex-shrink-0" <?= ($login_logo_style ?? '3d_box') === 'glass' ? 'checked' : '' ?>>
                            <div>
                                <span class="fw-bold d-block text-dark">Frosted Glass Plate</span>
                                <span class="text-muted small" style="font-size: 12px;">Semi-transparent frosted glass with backdrop blur and sleek borders.</span>
                            </div>
                        </label>
                    </div>

                    <div class="col-md-4">
                        <label class="p-3 border rounded-3 w-100 h-100 d-flex align-items-center gap-3 cursor-pointer bg-light">
                            <input type="radio" name="login_logo_style" value="transparent" class="form-check-input flex-shrink-0" <?= ($login_logo_style ?? '3d_box') === 'transparent' ? 'checked' : '' ?>>
                            <div>
                                <span class="fw-bold d-block text-dark">Seamless / Direct</span>
                                <span class="text-muted small" style="font-size: 12px;">No box/mount. Logo rests directly on the wallpaper (Best for white/ivory canvas backgrounds).</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- 3. Text & Typography Theme -->
            <div class="mb-4">
                <label class="form-label fw-bold text-dark fs-6 mb-1">
                    <i class="bi bi-type text-primary me-1"></i> 3. Typography &amp; Heading Color Theme
                </label>
                <div class="text-muted small mb-3">Select text color scheme so heading and labels never blend into or drown in the background.</div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="p-3 border rounded-3 w-100 h-100 d-flex align-items-center gap-3 cursor-pointer" style="background:#0f273d;">
                            <input type="radio" name="login_hero_theme" value="light" class="form-check-input flex-shrink-0" <?= ($login_hero_theme ?? 'light') === 'light' ? 'checked' : '' ?>>
                            <div>
                                <span class="fw-bold d-block text-white">Radiant White (Dark Backgrounds)</span>
                                <span class="text-white-50 small" style="font-size: 12px;">White font, crisp dark contour outline, and soft ambient glow. Highly visible against teal, blue, or dark photos.</span>
                            </div>
                        </label>
                    </div>

                    <div class="col-md-6">
                        <label class="p-3 border rounded-3 w-100 h-100 d-flex align-items-center gap-3 cursor-pointer" style="background:#f8fafc;">
                            <input type="radio" name="login_hero_theme" value="dark" class="form-check-input flex-shrink-0" <?= ($login_hero_theme ?? 'light') === 'dark' ? 'checked' : '' ?>>
                            <div>
                                <span class="fw-bold d-block text-dark">Architectural Navy (Light Backgrounds)</span>
                                <span class="text-muted small" style="font-size: 12px;">Deep corporate navy font (`#0b2545`). Pristine contrast on ivory, white, or botanical wallpapers.</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- 4. Hero Background Overlay Vignette -->
            <div class="mb-4">
                <label class="form-label fw-bold text-dark fs-6 mb-1">
                    <i class="bi bi-brightness-high text-primary me-1"></i> 4. Background Overlay Shading
                </label>
                <div class="text-muted small mb-3">Controls the subtle gradient shading overlaid onto the wallpaper to balance text readability.</div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="p-3 border rounded-3 w-100 h-100 d-flex align-items-center gap-3 cursor-pointer bg-light">
                            <input type="radio" name="login_hero_overlay" value="dark" class="form-check-input flex-shrink-0" <?= ($login_hero_overlay ?? 'dark') === 'dark' ? 'checked' : '' ?>>
                            <div>
                                <span class="fw-bold d-block text-dark">Dark Vignette (Default)</span>
                                <span class="text-muted small" style="font-size: 12px;">Deepens contrast naturally towards the bottom text area.</span>
                            </div>
                        </label>
                    </div>

                    <div class="col-md-4">
                        <label class="p-3 border rounded-3 w-100 h-100 d-flex align-items-center gap-3 cursor-pointer bg-light">
                            <input type="radio" name="login_hero_overlay" value="light" class="form-check-input flex-shrink-0" <?= ($login_hero_overlay ?? 'dark') === 'light' ? 'checked' : '' ?>>
                            <div>
                                <span class="fw-bold d-block text-dark">Light Ambient Wash</span>
                                <span class="text-muted small" style="font-size: 12px;">Soft daylight sheen, ideal for botanical or ivory branding.</span>
                            </div>
                        </label>
                    </div>

                    <div class="col-md-4">
                        <label class="p-3 border rounded-3 w-100 h-100 d-flex align-items-center gap-3 cursor-pointer bg-light">
                            <input type="radio" name="login_hero_overlay" value="none" class="form-check-input flex-shrink-0" <?= ($login_hero_overlay ?? 'dark') === 'none' ? 'checked' : '' ?>>
                            <div>
                                <span class="fw-bold d-block text-dark">None / Pure Wallpaper</span>
                                <span class="text-muted small" style="font-size: 12px;">No shading filter. Wallpaper shows in 100% natural colors.</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- 5. Display Toggles -->
            <div class="mb-4">
                <label class="form-label fw-bold text-dark fs-6 mb-1">
                    <i class="bi bi-eye text-primary me-1"></i> 5. Show / Hide Content Elements
                </label>
                <div class="text-muted small mb-3">Turn on or off specific branding elements on the login page.</div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="form-check form-switch p-3 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                            <label class="form-check-label fw-semibold text-dark mb-0" for="toggle_subtitle">Show Tagline Subtitle</label>
                            <input class="form-check-input ms-2" type="checkbox" id="toggle_subtitle" name="login_show_subtitle" value="1" <?= ($login_show_subtitle ?? '1') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-check form-switch p-3 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                            <label class="form-check-label fw-semibold text-dark mb-0" for="toggle_contact">Show Address &amp; Phone</label>
                            <input class="form-check-input ms-2" type="checkbox" id="toggle_contact" name="login_show_contact_meta" value="1" <?= ($login_show_contact_meta ?? '1') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-check form-switch p-3 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                            <label class="form-check-label fw-semibold text-dark mb-0" for="toggle_pwa">Show Mobile PWA Card</label>
                            <input class="form-check-input ms-2" type="checkbox" id="toggle_pwa" name="login_show_pwa_card" value="1" <?= ($login_show_pwa_card ?? '1') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- 6. Wallpaper Selection & Upload -->
            <div class="mb-4">
                <label class="form-label fw-bold text-dark fs-6 mb-1">
                    <i class="bi bi-image text-primary me-1"></i> 6. Wallpaper Library &amp; Upload
                </label>
                <div class="text-muted small mb-3">Select from curated wallpapers or upload a new custom background.</div>

                <div class="row g-3 mb-3">
                    <?php if (!empty($presets)): ?>
                        <?php foreach ($presets as $p): ?>
                            <?php $isCurrent = ($hospital_wallpaper ?? '') === $p['file']; ?>
                            <div class="col-md-4">
                                <div class="preset-thumb-card <?= $isCurrent ? 'active' : '' ?>" data-preset-file="<?= esc($p['file']) ?>">
                                    <div style="height: 100px; border-radius: 6px; overflow: hidden; background: #e2e8f0; margin-bottom: 6px;">
                                        <img src="<?= base_url('assets/images/' . rawurlencode($p['file'])) ?>" alt="<?= esc($p['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div class="fw-bold small text-dark d-flex align-items-center justify-content-between">
                                        <span><?= esc($p['title']) ?></span>
                                        <?php if ($isCurrent): ?>
                                            <span class="badge bg-primary">Active</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small" style="font-size: 11px;"><?= esc($p['desc'] ?? ($p['subtitle'] ?? '')) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <input type="hidden" id="preset_wallpaper" name="preset_wallpaper" value="">

                <div class="row g-3 align-items-center">
                    <div class="col-md-7">
                        <label class="form-label fw-semibold text-dark">Upload New Custom Wallpaper</label>
                        <input type="file" class="form-control" id="custom_hospital_wallpaper" name="hospital_wallpaper" accept=".png,.jpg,.jpeg,.webp">
                        <div class="form-text">Recommended size: 1920x1080 (PNG, JPG, WEBP).</div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold text-dark">Current Wallpaper Active</label>
                        <div id="active_wallpaper_display" class="border rounded p-2 text-center bg-light">
                            <?php if (!empty($hospital_wallpaper_url ?? '')): ?>
                                <img src="<?= esc((string) $hospital_wallpaper_url) ?>" alt="Wallpaper" style="max-height:60px; max-width:100%; object-fit:cover; border-radius:4px;">
                                <div class="small text-muted mt-1 text-truncate"><?= esc((string) ($hospital_wallpaper ?? '')) ?></div>
                            <?php else: ?>
                                <span class="text-muted small">No wallpaper active</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Controls -->
            <div class="d-flex align-items-center gap-2 flex-wrap pt-3 border-top">
                <button type="button" class="btn btn-primary px-4 py-2 fw-semibold" id="btn_save_login_settings">
                    <i class="bi bi-check-circle me-1"></i> Save Configuration
                </button>
                <a href="<?= base_url('login') ?>" target="_blank" class="btn btn-outline-secondary px-3 py-2">
                    <i class="bi bi-eye me-1"></i> Open Login Page
                </a>
            </div>

            <div id="login_settings_msg" class="mt-3"></div>
        </form>
    </div>
</div>

<script>
(function() {
    // 1. Layout card click handler
    $('.layout-choice-card').on('click', function() {
        $('.layout-choice-card').removeClass('active');
        $(this).addClass('active');
        $(this).find('input[type="radio"]').prop('checked', true);
    });

    // 2. Preset wallpaper click handler
    $('.preset-thumb-card').on('click', function() {
        $('.preset-thumb-card').removeClass('active');
        $(this).addClass('active');
        var file = $(this).data('preset-file');
        $('#preset_wallpaper').val(file);
    });

    // 3. Save AJAX
    $('#btn_save_login_settings').on('click', function() {
        var $btn = $(this);
        var $msg = $('#login_settings_msg');
        $msg.empty();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...');

        var formEl = document.getElementById('form_login_settings');
        var fd = new FormData(formEl);

        // Append CSRF token
        var csrfName = $('input[name="csrf_test_name"]').attr('name') || 'csrf_test_name';
        var csrfVal = $('input[name="csrf_test_name"]').val();
        if (csrfVal) {
            fd.append(csrfName, csrfVal);
        }

        $.ajax({
            url: '<?= base_url('setting/admin/login-settings/save') ?>',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(resp) {
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Save Configuration');

                if (resp.csrfName && resp.csrfHash) {
                    $('input[name="' + resp.csrfName + '"]').val(resp.csrfHash);
                }

                if (resp.update === 1) {
                    $msg.html('<div class="alert alert-success d-flex align-items-center py-2 px-3"><i class="bi bi-check-circle-fill me-2 fs-5"></i><div><strong>Saved!</strong> ' + (resp.error_text || 'Login page configuration updated successfully.') + ' <a href="<?= base_url('login') ?>" target="_blank" class="alert-link ms-2">View Login Page &rarr;</a></div></div>');
                    
                    if (resp.wallpaper_url) {
                        $('#active_wallpaper_display').html('<img src="' + resp.wallpaper_url + '" alt="Wallpaper" style="max-height:60px; max-width:100%; object-fit:cover; border-radius:4px;"><div class="small text-muted mt-1 text-truncate">' + resp.wallpaper_name + '</div>');
                    }
                } else {
                    $msg.html('<div class="alert alert-danger py-2 px-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>' + (resp.error_text || 'An error occurred while saving.') + '</div>');
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Save Configuration');
                $msg.html('<div class="alert alert-danger py-2 px-3"><i class="bi bi-x-circle-fill me-2"></i>Failed to save settings. Please try again.</div>');
            }
        });
    });
})();
</script>

<?php if ($isStandalone): ?>
    </div>
</body>
</html>
<?php endif; ?>

<?php helper('common'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Welcome</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <link href="<?= base_url('assets/img/logo.ico') ?>" rel="icon" type="image/x-icon">
    <link href="<?= base_url('assets/img/favicon.png') ?>" rel="alternate icon" type="image/png">
    <link href="<?= base_url('assets/img/apple-touch-icon.png') ?>" rel="apple-touch-icon">

    <!-- Google Fonts removed: offline app uses system fonts -->

    <link href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/boxicons/css/boxicons.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/quill/quill.snow.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/quill/quill.bubble.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/remixicon/remixicon.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/simple-datatables/style.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/datatables/jquery.dataTables.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/datatables/dataTables.bootstrap5.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/jquery-ui/jquery-ui.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/select2/select2.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/select2/select2-bootstrap4.min.css') ?>" rel="stylesheet">

    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
</head>

<body>

    <?php $user = auth()->user(); ?>
    <?php $footerVersion = hms_footer_version(); ?>

    <header id="header" class="header fixed-top d-flex align-items-center">
        <div class="d-flex align-items-center justify-content-between">
            <a href="<?= base_url('/') ?>" class="logo d-flex align-items-center">
                <img src="<?= base_url('assets/img/logo.png') ?>" alt="">
                <span class="d-none d-lg-block">E-Atria</span>
            </a>
            <i class="bi bi-list toggle-sidebar-btn"></i>
        </div>
        <?= $this->include('partials/header') ?>
    </header>

    <aside id="sidebar" class="sidebar">
        <?= $this->include('partials/sidebar') ?>
    </aside>

    <main id="main" class="main">

    </main>

    <footer id="footer" class="footer">
        <div class="copyright">
            &copy; 2017 - 2026 <strong><span><a href="https://www.e-atria.in" target="_blank" rel="noopener">E-Atria</a></span></strong>. All Rights Reserved |
            <a href="<?= base_url('software-use-license.html') ?>" target="_blank" rel="noopener">Software Use License</a> |
            Version: <?= esc($footerVersion) ?>
        </div>
    </footer>
    <div id="wait"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.6);z-index:9999;">
        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toast-container"></div>
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="<?= base_url('assets/vendor/jquery/jquery-4.0.0.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/jquery-ui/jquery-ui.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/datatables/jquery.dataTables.min.js') ?>"></script>
    <script>
        // The local DataTables file is a lightweight shim in this project.
        // Load Bootstrap 5 adapter only when full DataTables core API exists.
        (function() {
            if (!(window.jQuery && jQuery.fn && jQuery.fn.dataTable && jQuery.fn.dataTable.defaults)) {
                return;
            }

            var script = document.createElement('script');
            script.src = "<?= base_url('assets/vendor/datatables/dataTables.bootstrap5.min.js') ?>";
            document.head.appendChild(script);
        })();
    </script>
    <script>
        if (window.jQuery && typeof jQuery.isArray !== 'function') {
            jQuery.isArray = Array.isArray;
        }
        if (window.jQuery && typeof jQuery.trim !== 'function') {
            jQuery.trim = function(value) {
                return String(value ?? '').trim();
            };
        }
    </script>
    <script src="<?= base_url('assets/vendor/select2/select2.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/apexcharts/apexcharts.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipEls.forEach(function (el) { new bootstrap.Tooltip(el); });
        });
    </script>
    <script src="<?= base_url('assets/vendor/chart.js/chart.umd.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/echarts/echarts.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/quill/quill.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/ckeditor/ckeditor.js') ?>"></script>
    <script>
        if (window.CKEDITOR) {
            CKEDITOR.config.versionCheck = false;
        }
    </script>
    <script src="<?= base_url('assets/vendor/simple-datatables/simple-datatables.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/tinymce/tinymce.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/php-email-form/validate.js') ?>"></script>

    <?php
        // Get user settings (use hospital defaults as fallback)
        $sidebarAutoHideSeconds = (int) hospital_setting_value('SIDEBAR_AUTO_HIDE_SECONDS', '7');
        
        if (function_exists('auth') && auth()->loggedIn()) {
            $authUser = auth()->user();
            if ($authUser && isset($authUser->id)) {
                $userSettingsModel = model('App\Models\UserSettings');
                $userSidebarSetting = $userSettingsModel->getUserSetting(
                    (int) $authUser->id,
                    'SIDEBAR_AUTO_HIDE_SECONDS',
                    ''
                );
                if ($userSidebarSetting !== '') {
                    $sidebarAutoHideSeconds = (int) $userSidebarSetting;
                }
            }
        }
        
        if ($sidebarAutoHideSeconds < 0) {
            $sidebarAutoHideSeconds = 0;
        }
        if ($sidebarAutoHideSeconds > 60) {
            $sidebarAutoHideSeconds = 60;
        }
    ?>
    <script>
        window.SIDEBAR_AUTO_HIDE_DELAY_MS = <?= (int) ($sidebarAutoHideSeconds * 1000) ?>;
    </script>

    <script src="<?= base_url('assets/js/main.js') ?>"></script>

    <script>
        (function() {
            const REQUEST_TIMEOUT_MS = 60000;

            function requireJquery() {
                if (!window.jQuery) {
                    console.error('jQuery is not loaded.');
                    return false;
                }
                return true;
            }

            function handleAuthError(jqXHR) {
                if (jqXHR.status === 401 || jqXHR.status === 403) {
                    alert('You have been logged out. Please login again.');
                    window.location.href = '/login';
                    return true;
                }
                return false;
            }

            window.load_form = function(ourl, top_title = '') {
                if (!requireJquery()) return;
                if (typeof window.pageCleanup === 'function') {
                    try {
                        window.pageCleanup();
                    } catch (e) {
                        console.warn('pageCleanup failed', e);
                    }
                }
                $.ajax({
                        url: ourl,
                        dataType: "html",
                        async: true,
                            timeout: REQUEST_TIMEOUT_MS,
                        beforeSend: function() {
                            $('#main').html('loading...');
                            $("#wait").css("display", "block");
                        }
                    })
                    .done(function(html) {
                        if (typeof delete_varible === 'function') {
                            delete_varible();
                        }
                        $("#wait").css("display", "none");
                        $("#main").html(html);
                        if (typeof initfunc === 'function') {
                            initfunc();
                        }
                        if (top_title !== '') document.title = top_title;
                    })
                    .fail(function(jqXHR, textStatus, errorThrown) {
                        $("#wait").css("display", "none");
                        if (handleAuthError(jqXHR)) return;
                        console.error('load_form failed', {
                            url: ourl,
                            status: jqXHR.status,
                            statusText: jqXHR.statusText,
                            textStatus: textStatus,
                            error: errorThrown
                        });
                        if (textStatus === 'timeout') {
                            $('#main').html('Request timed out. Please try again.');
                            return;
                        }
                        var details = 'Error loading content.';
                        if (jqXHR && jqXHR.status) {
                            details += ' [HTTP ' + jqXHR.status + ']';
                        }
                        var raw = (jqXHR && jqXHR.responseText) ? String(jqXHR.responseText) : '';
                        if (raw !== '') {
                            raw = raw.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                            if (raw !== '') {
                                details += '<br><small>' + raw.substring(0, 220) + '</small>';
                            }
                        }
                        $('#main').html(details);
                    });
            };

            window.load_form_div = function(ourl, xdiv, top_title = '') {
                if (!requireJquery()) return;
                $.ajax({
                        url: ourl,
                        dataType: "html",
                    async: true,
                            timeout: REQUEST_TIMEOUT_MS
                    })
                    .done(function(html) {
                        $("#" + xdiv).html(html);
                        if (typeof initfunc === 'function') {
                            initfunc();
                        }
                        if (top_title !== '') document.title = top_title;
                    })
                    .fail(function(jqXHR, textStatus, errorThrown) {
                        if (handleAuthError(jqXHR)) return;
                        console.error('load_form_div failed', {
                            url: ourl,
                            status: jqXHR.status,
                            statusText: jqXHR.statusText,
                            textStatus: textStatus,
                            error: errorThrown
                        });
                        if (textStatus === 'timeout') {
                            $("#" + xdiv).html('Request timed out. Please try again.');
                            return;
                        }
                        var details = 'Error loading content.';
                        if (jqXHR && jqXHR.status) {
                            details += ' [HTTP ' + jqXHR.status + ']';
                        }
                        var raw = (jqXHR && jqXHR.responseText) ? String(jqXHR.responseText) : '';
                        if (raw !== '') {
                            raw = raw.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                            if (raw !== '') {
                                details += '<br><small>' + raw.substring(0, 220) + '</small>';
                            }
                        }
                        $("#" + xdiv).html(details);
                    });
            };
        })();
    </script>

    <script>
        (function() {
            const initialRoute = <?= json_encode($initial_route ?? '') ?>;
            const initialTitle = <?= json_encode($initial_title ?? '') ?>;
            if (!initialRoute) {
                return;
            }
            const loadInitial = function() {
                if (typeof window.load_form === 'function') {
                    window.load_form(initialRoute, initialTitle);
                }
            };
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', loadInitial);
            } else {
                loadInitial();
            }
        })();
    </script>
    <script>
        if (typeof window.notify !== 'function') {
            window.notify = function(type, title, message) {
                var container = document.getElementById('toast-container');
                if (!container) {
                    return;
                }

                var toast = document.createElement('div');
                var variant = type === 'success' ? 'success' : 'danger';
                toast.className = 'toast align-items-center text-bg-' + variant + ' border-0';
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', 'assertive');
                toast.setAttribute('aria-atomic', 'true');

                toast.innerHTML =
                    '<div class="d-flex">'
                    + '<div class="toast-body">'
                    + '<strong class="me-2">' + (title || '') + '</strong>' + (message || '')
                    + '</div>'
                    + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>'
                    + '</div>';

                container.appendChild(toast);

                if (window.bootstrap && bootstrap.Toast) {
                    var bsToast = bootstrap.Toast.getOrCreateInstance(toast, { delay: 3000 });
                    bsToast.show();
                } else {
                    toast.classList.add('show');
                    setTimeout(function() {
                        toast.classList.remove('show');
                        toast.remove();
                    }, 3000);
                }
            };
        }
    </script>
</body>

</html>
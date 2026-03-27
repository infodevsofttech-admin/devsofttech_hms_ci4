<?php helper('common'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Welcome</title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <link href="<?= base_url('assets/img/favicon.png') ?>" rel="icon">
    <link href="<?= base_url('assets/img/apple-touch-icon.png') ?>" rel="apple-touch-icon">

    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <link href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/boxicons/css/boxicons.min.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/quill/quill.snow.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/quill/quill.bubble.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/remixicon/remixicon.css') ?>" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/simple-datatables/style.css') ?>" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/vendor/jquery-ui/jquery-ui.min.css') ?>" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.3/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet">

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
            &copy; 2017 - 2026 <strong><span>E-Atria</span></strong>. All Rights Reserved | Version: <?= esc($footerVersion) ?>
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
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.3/dist/js/select2.min.js"></script>
    <script src="<?= base_url('assets/vendor/apexcharts/apexcharts.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/chart.js/chart.umd.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/echarts/echarts.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/quill/quill.js') ?>"></script>
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <script>
        if (window.CKEDITOR) {
            CKEDITOR.config.versionCheck = false;
        }
    </script>
    <script src="<?= base_url('assets/vendor/simple-datatables/simple-datatables.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/tinymce/tinymce.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/php-email-form/validate.js') ?>"></script>

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
                        $('#main').html('Error loading content.');
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
                        $("#" + xdiv).html('Error loading content.');
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
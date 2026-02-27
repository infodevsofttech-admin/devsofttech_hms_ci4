<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title><?= lang('Auth.login') ?></title>
    <meta content="" name="description">
    <meta content="" name="keywords">

    <link href="assets/img/favicon.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
    <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
    <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
    <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

    <link href="assets/css/style.css" rel="stylesheet">

</head>

<body>

    <main>
        <div class="container">

            <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

                            <div class="d-flex justify-content-center py-4">
                                <a href="/" class="logo d-flex align-items-center w-auto">
                                    <img src="assets/img/logo.png" alt="">
                                    <span class="d-none d-lg-block">E-Atria</span>
                                </a>
                            </div>

                            <div class="card mb-3">

                                <div class="card-body">

                                    <div class="pt-4 pb-2">
                                        <h5 class="card-title text-center pb-0 fs-4"><?= lang('Auth.loginTitle') ?></h5>
                                        <p class="text-center small"><?= lang('Auth.loginSubtitle') ?></p>
                                    </div>

                                    <?php if (session('error') !== null) : ?>
                                        <div class="alert alert-danger" role="alert"><?= esc(session('error')) ?></div>
                                    <?php elseif (session('errors') !== null) : ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?php if (is_array(session('errors'))) : ?>
                                                <?php foreach (session('errors') as $error) : ?>
                                                    <?= esc($error) ?>
                                                    <br>
                                                <?php endforeach ?>
                                            <?php else : ?>
                                                <?= esc(session('errors')) ?>
                                            <?php endif ?>
                                        </div>
                                    <?php endif ?>

                                    <?php if (session('message') !== null) : ?>
                                        <div class="alert alert-success" role="alert"><?= esc(session('message')) ?></div>
                                    <?php endif ?>

                                    <form action="<?= url_to('login') ?>" method="post" class="row g-3 needs-validation" novalidate>
                                        <?= csrf_field() ?>

                                        <div class="col-12">
                                            <label for="floatingUsernameInput"><?= lang('Auth.username') ?></label>
                                            <div class="input-group has-validation">
                                                <span class="input-group-text" id="inputGroupPrepend">@</span>
                                                <input type="text" class="form-control" id="floatingUsernameInput" name="username" inputmode="text" autocomplete="username" placeholder="<?= lang('Auth.username') ?>" value="<?= old('username') ?>" required>
                                                <div class="invalid-feedback">Please enter your username.</div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label for="floatingPasswordInput"><?= lang('Auth.password') ?></label>
                                            <input type="password" name="password" class="form-control" id="floatingPasswordInput" autocomplete="current-password" required>
                                            <div class="invalid-feedback">Please enter your password!</div>
                                        </div>

                                        <?php if (setting('Auth.sessionConfig')['allowRemembering']) : ?>
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input type="checkbox" name="remember" class="form-check-input" <?php if (old('remember')) : ?> checked<?php endif ?>>
                                                    <label class="form-check-label" for="rememberMe"><?= lang('Auth.rememberMe') ?></label>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary w-100"><?= lang('Auth.login') ?></button>
                                        </div>

                                        <?php if (setting('Auth.allowMagicLinkLogins')) : ?>
                                            <div class="col-12">
                                                <p class="text-center"><?= lang('Auth.forgotPassword') ?> <a href="<?= url_to('magic-link') ?>"><?= lang('Auth.useMagicLink') ?></a></p>
                                            </div>
                                        <?php endif ?>

                                        <?php if (setting('Auth.allowRegistration')) : ?>
                                            <div class="col-12">
                                                <p class="text-center"><?= lang('Auth.needAccount') ?> <a href="<?= url_to('register') ?>"><?= lang('Auth.register') ?></a></p>
                                            </div>
                                        <?php endif ?>

                                    </form>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </section>

        </div>
    </main>

    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

    <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/chart.js/chart.umd.js"></script>
    <script src="assets/vendor/echarts/echarts.min.js"></script>
    <script src="assets/vendor/quill/quill.js"></script>
    <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
    <script src="assets/vendor/tinymce/tinymce.min.js"></script>
    <script src="assets/vendor/php-email-form/validate.js"></script>

    <script src="assets/js/main.js"></script>

</body>

</html>
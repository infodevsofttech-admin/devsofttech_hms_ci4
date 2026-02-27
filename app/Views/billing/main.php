<div class="pagetitle">
            <h1>Welcome</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Home</a></li>
                    <li class="breadcrumb-item active">Blank</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Welcome</h5>
                    <p>User ID: <?= esc((string) ($user->id ?? '')) ?></p>
                    <p>Username: <?= esc($user->username ?? $user->email ?? 'User') ?></p>
                </div>
            </div>
        </section>
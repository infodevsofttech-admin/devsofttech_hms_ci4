<section class="content-header">
    <h1>One-Time DB Setup</h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Existing Database Tables</h3>
        </div>
        <div class="box-body">
            <?php if (!empty($msg ?? '')) : ?>
                <div class="alert alert-info"><?= esc($msg) ?></div>
            <?php endif; ?>

            <p class="text-muted">
                Select unscripted tables to generate migration files. This does not modify existing data.
                Generated files are placed in <strong>app/Database/Migrations</strong>.
            </p>

            <form method="post" action="<?= base_url('setup/db-tools/generate') ?>">
                <?= csrf_field() ?>
                <div class="table-responsive" style="max-height:520px; overflow:auto; border:1px solid #eee;">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th style="width:60px;">Pick</th>
                                <th>Table Name</th>
                                <th style="width:160px;">Migration Script</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tables ?? [])) : ?>
                                <tr><td colspan="3" class="text-center text-muted">No tables found.</td></tr>
                            <?php else : ?>
                                <?php foreach (($tables ?? []) as $row) : ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php if (!$row['scripted']) : ?>
                                                <input type="checkbox" name="tables[]" value="<?= esc($row['name']) ?>">
                                            <?php endif; ?>
                                        </td>
                                        <td><?= esc($row['name']) ?></td>
                                        <td>
                                            <?php if ($row['scripted']) : ?>
                                                <span class="label label-success">Scripted</span>
                                            <?php else : ?>
                                                <span class="label label-warning">Missing</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:12px;">
                    <button type="submit" class="btn btn-primary">Generate Migration For Selected</button>
                    <a class="btn btn-default" href="<?= base_url('setup/db-tools') ?>">Refresh</a>
                </div>
            </form>

            <hr>

            <form method="post" action="<?= base_url('setup/db-tools/complete') ?>" onsubmit="return confirm('This will lock setup page. Continue?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger">Complete Setup and Lock Page</button>
                <p class="text-muted" style="margin-top:8px;">Lock file: <?= esc($lock_file ?? '') ?></p>
            </form>
        </div>
    </div>
</section>

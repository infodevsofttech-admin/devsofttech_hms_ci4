<div class="card">
    <div class="card-body pt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="card-title mb-0"><?= esc($title ?? 'Master Report') ?></h5>
            <a class="btn btn-sm btn-outline-secondary" href="javascript:load_form_div('<?= base_url('Medical/master') ?>','medical-main','Master :Pharmacy');">Back to Master</a>
        </div>

        <?php if (!empty($note ?? '')): ?>
            <div class="alert alert-info"><?= esc($note) ?></div>
        <?php endif; ?>

        <?php if (!empty($columns ?? []) && !empty($rows ?? [])): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-sm align-middle">
                    <thead>
                    <tr>
                        <?php foreach (($columns ?? []) as $col): ?>
                            <th><?= esc((string) $col) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (($rows ?? []) as $row): ?>
                        <tr>
                            <?php foreach (($columns ?? []) as $col): ?>
                                <td><?= esc((string) ($row[$col] ?? '')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

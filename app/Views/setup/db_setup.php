<section class="content-header">
    <h1>One-Time DB Setup</h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Automated Schema Synchronization</h3>
        </div>
        <div class="box-body">
            <?php if (!empty($msg ?? '')) : ?>
                <div class="alert alert-info"><?= esc($msg) ?></div>
            <?php endif; ?>

            <?php if (!empty($diagnostics ?? null)) : ?>
                <?php if (!empty($diagnostics['errors'] ?? [])) : ?>
                    <div class="alert alert-danger">
                        <strong>Installation Diagnostics (Errors)</strong>
                        <?php foreach (($diagnostics['errors'] ?? []) as $diagErr) : ?>
                            <div><?= esc($diagErr) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($diagnostics['warnings'] ?? [])) : ?>
                    <div class="alert alert-warning">
                        <strong>Installation Diagnostics (Warnings)</strong>
                        <?php foreach (($diagnostics['warnings'] ?? []) as $diagWarn) : ?>
                            <div><?= esc($diagWarn) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($diagnostics['info'] ?? [])) : ?>
                    <div class="alert alert-success">
                        <strong>Installation Diagnostics (Info)</strong>
                        <?php foreach (($diagnostics['info'] ?? []) as $diagInfo) : ?>
                            <div><?= esc($diagInfo) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <p class="text-muted">
                Compares <strong>master schema file</strong> (or optional master DB) with <strong>client DB schema</strong> and generates
                <code>CREATE TABLE IF NOT EXISTS</code> / <code>ALTER TABLE</code> statements.
                Default mode is <strong>offline</strong>: export master schema JSON on master system, then use same file on client system.
                Optional DB-to-DB mode can be configured in <code>.env</code> if both DBs are reachable.
            </p>

            <form method="post" action="<?= base_url('setup/db-tools/export-master-schema') ?>" style="margin-bottom:12px;">
                <?= csrf_field() ?>
                <?php if (!empty($setup_key ?? '')) : ?>
                    <input type="hidden" name="key" value="<?= esc($setup_key) ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-success">Export Master Schema File (from current DB)</button>
                <span class="text-muted" style="margin-left:10px;">File: <?= esc($master_schema_file ?? '') ?></span>
                <?php if (!empty($master_schema_exists ?? false)) : ?>
                    <span class="label label-success" style="margin-left:6px;">Found</span>
                <?php else : ?>
                    <span class="label label-warning" style="margin-left:6px;">Not Found</span>
                <?php endif; ?>
            </form>

            <form method="post" action="<?= base_url('setup/db-tools/schema-sync') ?>">
                <?= csrf_field() ?>
                <?php if (!empty($setup_key ?? '')) : ?>
                    <input type="hidden" name="key" value="<?= esc($setup_key) ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Master Source</label>
                            <input type="text" class="form-control" readonly value="<?= esc((!empty($sync_master_database ?? '') ? ('DB: ' . $sync_master_database) : ('File: ' . ($master_schema_file ?? '')))) ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Client DB</label>
                            <input type="text" class="form-control" readonly value="<?= esc($sync_client_database ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" name="sync_action" value="analyze" class="btn btn-info">Analyze Schema Diff</button>
                    <button type="submit" name="sync_action" value="apply" class="btn btn-warning" onclick="return confirm('Apply generated SQL to client DB?');">Apply Sync to Client DB</button>
                </div>
            </form>

            <?php if (!empty($sync_result ?? null)) : ?>
                <?php if (!empty($sync_result['errors'] ?? [])) : ?>
                    <div class="alert alert-danger">
                        <?php foreach (($sync_result['errors'] ?? []) as $err) : ?>
                            <div><?= esc($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="alert alert-success">
                        Master tables: <?= esc((string) ($sync_result['summary']['master_tables'] ?? 0)) ?>,
                        processed tables: <?= esc((string) ($sync_result['summary']['processed_tables'] ?? 0)) ?>/<?= esc((string) ($sync_result['summary']['max_tables_per_run'] ?? 0)) ?>,
                        create statements: <?= esc((string) ($sync_result['summary']['create_tables'] ?? 0)) ?>,
                        alter statements: <?= esc((string) ($sync_result['summary']['alter_statements'] ?? 0)) ?>.
                        <?php if (isset($sync_result['applied'])) : ?>
                            Applied: <?= esc((string) ($sync_result['applied'] ?? 0)) ?>.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($sync_result['truncated'] ?? false)) : ?>
                    <div class="alert alert-warning">
                        Schema sync run was truncated to configured max tables per run.
                        Increase <code>setup.sync.max_tables_per_run</code> in .env if needed.
                    </div>
                <?php endif; ?>

                <?php if (!empty($sync_result['apply_errors'] ?? [])) : ?>
                    <div class="alert alert-danger">
                        <strong>Apply Errors:</strong>
                        <?php foreach (($sync_result['apply_errors'] ?? []) as $err) : ?>
                            <div><?= esc($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Generated SQL</label>
                    <textarea class="form-control" rows="14" readonly><?php
                        $sqlLines = $sync_result['sql'] ?? [];
                        if (is_array($sqlLines)) {
                            $sqlLines = array_slice($sqlLines, 0, 300);
                            echo esc(implode("\n", $sqlLines));
                        }
                    ?></textarea>
                </div>
            <?php endif; ?>

            <hr>

            <form method="post" action="<?= base_url('setup/db-tools/ensure-admin') ?>" onsubmit="return confirm('Create or update admin login for this client database?');" style="margin-bottom:10px;">
                <?= csrf_field() ?>
                <?php if (!empty($setup_key ?? '')) : ?>
                    <input type="hidden" name="key" value="<?= esc($setup_key) ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Create/Update Admin Login</button>
                <span class="text-muted" style="margin-left:10px;">Uses .env keys: <code>setup.admin.username</code>, <code>setup.admin.email</code>, <code>setup.admin.password</code>, <code>setup.admin.group</code>.</span>
            </form>

            <hr>

            <form method="post" action="<?= base_url('setup/db-tools/complete') ?>" onsubmit="return confirm('This will lock setup page. Continue?');">
                <?= csrf_field() ?>
                <?php if (!empty($setup_key ?? '')) : ?>
                    <input type="hidden" name="key" value="<?= esc($setup_key) ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-danger">Complete Setup and Lock Page</button>
                <p class="text-muted" style="margin-top:8px;">Lock file: <?= esc($lock_file ?? '') ?></p>
            </form>
        </div>
    </div>
</section>

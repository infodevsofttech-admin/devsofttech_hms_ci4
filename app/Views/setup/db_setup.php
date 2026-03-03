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
                    <button type="button" id="auto-sync-btn" class="btn btn-primary">Auto Apply Until Done</button>
                    <button type="button" id="auto-sync-stop-btn" class="btn btn-default" disabled>Stop Auto Apply</button>
                </div>
            </form>

            <div id="auto-sync-status" class="alert alert-info" style="display:none;"></div>

            <form method="post" action="<?= base_url('setup/db-tools/prepare-filesystem') ?>" style="margin-top:10px; margin-bottom:10px;">
                <?= csrf_field() ?>
                <?php if (!empty($setup_key ?? '')) : ?>
                    <input type="hidden" name="key" value="<?= esc($setup_key) ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-default" onclick="return confirm('Create/fix required writable folders now?');">Prepare Writable Folders</button>
                <span class="text-muted" style="margin-left:10px;">Ensures writable/cache, logs, session, uploads, debugbar, tmp, public/uploads.</span>
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

                <?php if (!empty($sync_result['apply_truncated'] ?? false)) : ?>
                    <div class="alert alert-warning">
                        Apply phase was truncated to prevent request timeout.
                        Re-run <strong>Apply Sync to Client DB</strong> to continue remaining statements,
                        or increase <code>setup.sync.max_apply_statements_per_run</code> / <code>setup.sync.max_runtime_seconds</code> in .env.
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

            <form method="post" action="<?= base_url('setup/db-tools/repair-auth-schema') ?>" onsubmit="return confirm('Check and repair auth schema now? This will add missing columns like users.deleted_at if needed.');" style="margin-bottom:10px;">
                <?= csrf_field() ?>
                <?php if (!empty($setup_key ?? '')) : ?>
                    <input type="hidden" name="key" value="<?= esc($setup_key) ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-default">Repair Auth Schema (Login Fix)</button>
                <span class="text-muted" style="margin-left:10px;">Fixes Shield auth schema mismatches such as missing <code>users.deleted_at</code>.</span>
            </form>

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

<script>
    (function () {
        var autoBtn = document.getElementById('auto-sync-btn');
        var stopBtn = document.getElementById('auto-sync-stop-btn');
        var statusBox = document.getElementById('auto-sync-status');
        if (!autoBtn || !stopBtn || !statusBox) {
            return;
        }

        var running = false;
        var cancelRequested = false;
        var maxRounds = 30;

        function setStatus(html, klass) {
            statusBox.style.display = 'block';
            statusBox.className = 'alert ' + (klass || 'alert-info');
            statusBox.innerHTML = html;
        }

        function readCsrf() {
            var tokenInput = document.querySelector('input[name="<?= esc(csrf_token()) ?>"]');
            return tokenInput ? tokenInput.value : '';
        }

        function buildFormBody() {
            var csrfName = '<?= esc(csrf_token()) ?>';
            var csrfValue = readCsrf();
            var parts = [];
            if (csrfValue) {
                parts.push(encodeURIComponent(csrfName) + '=' + encodeURIComponent(csrfValue));
            }
            <?php if (!empty($setup_key ?? '')) : ?>
            parts.push('key=' + encodeURIComponent('<?= esc($setup_key) ?>'));
            <?php endif; ?>
            return parts.join('&');
        }

        async function runRound(round) {
            var res = await fetch('<?= base_url('setup/db-tools/schema-sync-step') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: buildFormBody(),
                credentials: 'same-origin'
            });

            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }

            return await res.json();
        }

        autoBtn.addEventListener('click', async function () {
            if (running) {
                return;
            }

            if (!confirm('Auto-apply schema sync in multiple safe chunks?')) {
                return;
            }

            running = true;
            cancelRequested = false;
            autoBtn.disabled = true;
            stopBtn.disabled = false;
            setStatus('Starting auto apply...', 'alert-info');

            try {
                var rounds = 0;
                var totalApplied = 0;

                while (rounds < maxRounds) {
                    if (cancelRequested) {
                        setStatus('Auto apply stopped by user after ' + rounds + ' rounds. You can resume anytime.', 'alert-warning');
                        break;
                    }

                    rounds++;
                    setStatus('Running round ' + rounds + ' of ' + maxRounds + '...', 'alert-info');

                    var data = await runRound(rounds);
                    var result = data.result || {};
                    var appliedNow = parseInt(data.applied || 0, 10);
                    var sqlCount = parseInt(data.sql_count || 0, 10);
                    var applyErrCount = parseInt(data.apply_error_count || 0, 10);
                    totalApplied += appliedNow;

                    if (!data.ok) {
                        var errList = (result.errors || []).join('<br>');
                        setStatus('Stopped: ' + (errList || 'Schema sync step failed.'), 'alert-danger');
                        break;
                    }

                    if (applyErrCount > 0) {
                        var applyErrors = (result.apply_errors || []).slice(0, 5).join('<br>');
                        setStatus('Stopped due to apply errors after ' + rounds + ' rounds.<br>' + applyErrors, 'alert-danger');
                        break;
                    }

                    if (!data.should_continue) {
                        var msg = 'Completed after ' + rounds + ' rounds. Total applied: ' + totalApplied + '. Last round SQL: ' + sqlCount + '.';
                        setStatus(msg + ' Reloading page...', 'alert-success');
                        setTimeout(function () {
                            window.location.reload();
                        }, 900);
                        break;
                    }

                    setStatus('Round ' + rounds + ' done. Applied this round: ' + appliedNow + '. Total applied: ' + totalApplied + '. Continuing...', 'alert-info');
                }

                if (rounds >= maxRounds) {
                    setStatus('Stopped after max rounds (' + maxRounds + '). Click again to continue from current state.', 'alert-warning');
                }
            } catch (error) {
                setStatus('Auto apply failed: ' + (error && error.message ? error.message : 'Unknown error'), 'alert-danger');
            } finally {
                running = false;
                autoBtn.disabled = false;
                stopBtn.disabled = true;
            }
        });

        stopBtn.addEventListener('click', function () {
            if (!running) {
                return;
            }
            cancelRequested = true;
            stopBtn.disabled = true;
            setStatus('Stop requested. Finishing current request...', 'alert-warning');
        });
    })();
</script>

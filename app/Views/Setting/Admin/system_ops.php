<section class="content">
    <div class="pagetitle">
        <h1>System Operations</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Admin</li>
                <li class="breadcrumb-item active">System Operations</li>
            </ol>
        </nav>
    </div>

    <style>
        .ops-card { border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; box-shadow: 0 8px 24px rgba(0,0,0,.04); }
        .ops-card .card-header { background: linear-gradient(135deg, #0d6efd, #0dcaf0); color: white; border-radius: 14px 14px 0 0; }
        .ops-pill { display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:600; }
        .ops-pill.success { background:#d1fae5; color:#065f46; }
        .ops-pill.warning { background:#fef3c7; color:#92400e; }
        .ops-pill.danger { background:#fee2e2; color:#991b1b; }
        .ops-health { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:600; background:#f8fafc; color:#1f2937; }
        .ops-health::before { content:''; width:8px; height:8px; border-radius:50%; background:#22c55e; display:inline-block; }
        .ops-health.warning::before { background:#f59e0b; }
        .ops-health.danger::before { background:#ef4444; }
        .ops-timeline { border-left:2px solid #e2e8f0; margin:0; padding-left:16px; }
        .ops-timeline li { position:relative; padding-bottom:12px; }
        .ops-timeline li::before { content:''; position:absolute; left:-22px; top:4px; width:10px; height:10px; border-radius:50%; background:#0d6efd; }
    </style>

    <div id="systemOpsPanel">
        <?= view('Setting/Admin/system_ops_panel', ['status' => $status, 'history' => $history]) ?>
    </div>

    <script>
        function setHealthState(state) {
            if (typeof $ === 'undefined') return;
            var badge = $('#serverHealthBadge');
            if (!badge.length) return;
            badge.removeClass('warning danger').addClass(state === 'warning' ? 'warning' : (state === 'danger' ? 'danger' : ''));
            badge.text(state === 'warning' ? 'Warning' : (state === 'danger' ? 'Critical' : 'Healthy'));
        }

        function refreshPanel() {
            if (typeof $ === 'undefined') return;
            $.ajax({
                url: '<?= base_url('setting/admin/system-ops/panel') ?>',
                type: 'GET',
                dataType: 'html',
                success: function (html) {
                    $('#systemOpsPanel').html(html);
                },
                error: function () {
                    if (typeof notify === 'function') {
                        notify('warning', 'Refresh', 'Unable to refresh system status right now.');
                    }
                }
            });
        }

        function postAction(url, data, button) {
            if (typeof $ === 'undefined') return;
            if (button) {
                button.disabled = true;
            }
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: data,
                success: function (response) {
                    if (response && response.ok) {
                        notify('success', 'Success', response.message || 'Completed');
                    } else {
                        notify('error', 'Attention', response.message || 'Action could not be completed');
                    }
                },
                error: function () {
                    notify('error', 'Attention', 'Request failed');
                },
                complete: function () {
                    if (button) {
                        button.disabled = false;
                    }
                    setTimeout(function(){ refreshPanel(); }, 1500);
                }
            });
        }

        if (typeof $ !== 'undefined') {
            setHealthState('healthy');
            setInterval(function(){ refreshPanel(); }, 30000);

            $(document).off('click', '#btnUpdateSystem, #btnRestartWeb, #btnRestartPhp, #btnReboot, #btnShutdown')
                .on('click', '#btnUpdateSystem', function(){ if (!confirm('Run the system update workflow now?')) return; postAction('<?= base_url('setting/admin/system-ops/update') ?>', {}, this); })
                .on('click', '#btnRestartWeb', function(){ if (!confirm('Restart the web server now?')) return; postAction('<?= base_url('setting/admin/system-ops/action') ?>', {action: 'restart_web'}, this); })
                .on('click', '#btnRestartPhp', function(){ if (!confirm('Restart PHP-FPM now?')) return; postAction('<?= base_url('setting/admin/system-ops/action') ?>', {action: 'restart_php'}, this); })
                .on('click', '#btnReboot', function(){ if (!confirm('Reboot the server now?')) return; postAction('<?= base_url('setting/admin/system-ops/action') ?>', {action: 'reboot'}, this); })
                .on('click', '#btnShutdown', function(){ if (!confirm('Shut down the server now?')) return; postAction('<?= base_url('setting/admin/system-ops/action') ?>', {action: 'shutdown'}, this); });
        }
    </script>
</section>

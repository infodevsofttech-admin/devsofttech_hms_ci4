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

    <!-- Detail Modal: outside #systemOpsPanel so panel refresh never destroys it -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Operation Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre id="detailContent" style="max-height:400px; overflow-y:auto; background:#f8f9fa; padding:12px; border-radius:4px; white-space:pre-wrap; word-break:break-word;"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
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
            // Skip refresh while detail modal is open to avoid destroying its DOM
            if ($('#detailModal').hasClass('show')) return;
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

            // Populate detail modal from data-detail attribute on the triggering button
            $(document).on('show.bs.modal', '#detailModal', function(e) {
                var btn = e.relatedTarget;
                var detail = btn ? ($(btn).data('detail') || '') : '';
                $('#detailContent').text(detail);
            });

            // Clean up backdrop on modal hidden (prevents stuck overlay after panel refresh)
            $(document).on('hidden.bs.modal', '#detailModal', function() {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('padding-right', '');
            });

            $(document).off('click', '#btnUpdateDirectGithub')
                .on('click', '#btnUpdateDirectGithub', function(){ if (!confirm('Deploy latest HMS code from GitHub?\n\nThis will download the latest code and update all files.')) return; postAction('<?= base_url('setting/admin/system-ops/updateDirect') ?>', {}, this); });
        }
    </script>
</section>

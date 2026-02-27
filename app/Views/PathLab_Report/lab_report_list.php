<?= form_open() ?>
<section class="content">
    <style>
        .report-split {
            display: flex;
            gap: 12px;
            align-items: stretch;
        }
        .report-split-left {
            flex: 0 0 32%;
            min-width: 260px;
        }
        .report-split-right {
            flex: 1 1 auto;
            min-width: 0;
        }
        .report-split-handle {
            flex: 0 0 6px;
            cursor: col-resize;
            border-radius: 6px;
            background: linear-gradient(180deg, #e5e7eb 0%, #cbd5f5 100%);
            opacity: 0.7;
        }
        .report-split-handle:hover {
            opacity: 1;
        }
        @media (max-width: 991.98px) {
            .report-split {
                flex-direction: column;
            }
            .report-split-left,
            .report-split-right {
                flex: 1 1 auto;
                width: 100%;
            }
            .report-split-handle {
                display: none;
            }
        }
    </style>
    <div class="report-split" id="reportSplit">
        <div class="report-split-left">
            <div class="card admin-card">
                <div class="card-header bg-white d-flex align-items-center justify-content-between">
                    <h3 class="mb-0">Report List</h3>
                    <button onclick="load_form_div('<?= base_url('Lab_Admin/reportedit_load/0') ?>','test_div');" type="button" class="btn btn-primary btn-sm">Add New Report</button>
                </div>
                <div class="card-body" style="height:500px;overflow-y:auto;">
                    <table id="report_list" class="table table-striped table-hover align-middle TableData">
                        <thead class="table-light">
                        <tr>
                            <th>Report Title</th>
                            <th>Group</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php for ($i = 0; $i < count($labReport_master); ++$i) { ?>
                            <tr>
                                <td><?= esc($labReport_master[$i]->Title ?? '') ?></td>
                                <td><?= esc($labReport_master[$i]->RepoGrp ?? '') ?></td>
                                <td>
                                    <button onclick="load_form_div('<?= base_url('Lab_Admin/report_test_list') ?>/<?= esc($labReport_master[$i]->mstRepoKey ?? 0) ?>','test_div');" type="button" class="btn btn-outline-primary btn-sm">Test List</button>
                                    <button onclick="load_form_div('<?= base_url('Lab_Admin/reportedit_load') ?>/<?= esc($labReport_master[$i]->mstRepoKey ?? 0) ?>','test_div');" type="button" class="btn btn-outline-secondary btn-sm">Edit</button>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th>Report Title</th>
                            <th>Group</th>
                            <th>Action</th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="report-split-handle" id="reportSplitHandle" aria-hidden="true"></div>
        <div class="report-split-right" id="test_div"></div>
    </div>
</section>
<?= form_close() ?>
<script>
    if (window.jQuery && $.fn && $.fn.DataTable) {
        $('#report_list').DataTable();
    }
</script>
<script>
    (function() {
        var split = document.getElementById('reportSplit');
        var left = split ? split.querySelector('.report-split-left') : null;
        var right = split ? split.querySelector('.report-split-right') : null;
        var handle = document.getElementById('reportSplitHandle');

        if (!split || !left || !right || !handle) {
            return;
        }

        var isDragging = false;
        var startX = 0;
        var startLeftWidth = 0;

        handle.addEventListener('mousedown', function(event) {
            if (window.matchMedia('(max-width: 991.98px)').matches) {
                return;
            }
            isDragging = true;
            startX = event.clientX;
            startLeftWidth = left.getBoundingClientRect().width;
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
        });

        window.addEventListener('mousemove', function(event) {
            if (!isDragging) {
                return;
            }
            var delta = event.clientX - startX;
            var containerWidth = split.getBoundingClientRect().width;
            var newLeftWidth = Math.max(240, Math.min(startLeftWidth + delta, containerWidth * 0.6));
            left.style.flex = '0 0 ' + newLeftWidth + 'px';
        });

        window.addEventListener('mouseup', function() {
            if (!isDragging) {
                return;
            }
            isDragging = false;
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
        });
    })();
</script>

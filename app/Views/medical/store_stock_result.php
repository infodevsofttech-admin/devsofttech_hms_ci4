<?php
$graphLabels = $graph_labels ?? [];
$graphValues = $graph_values ?? [];
$totalItems = (int) ($total_items ?? 0);
$lowStockItems = (int) ($low_stock_items ?? 0);
$expirySummary = $expiry_summary ?? ['expired' => 0, 'd0_30' => 0, 'd31_60' => 0, 'd61_90' => 0];
$topSaleRows = $top_sale_rows ?? [];
$topSaleLabels = $top_sale_labels ?? [];
$topSaleValues = $top_sale_values ?? [];
$ratioRows = $ratio_rows ?? [];
$ratioLabels = $ratio_labels ?? [];
$ratioValues = $ratio_values ?? [];
?>

<div class="row g-2 mb-3">
    <div class="col-md-3">
        <div class="border rounded p-2 h-100">
            <div class="small text-muted">Total Items</div>
            <div class="h5 mb-0"><?= esc((string) $totalItems) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="border rounded p-2 h-100">
            <div class="small text-muted">Low Stock Items</div>
            <div class="h5 mb-0 text-danger"><?= esc((string) $lowStockItems) ?></div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body pt-3">
        <h6 class="mb-3">Pharmacy Stock Graph (Top 10 by Current Unit Qty)</h6>
        <div style="height: 320px;">
            <canvas id="pharmacy-stock-chart"></canvas>
        </div>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-3">
        <div class="border rounded p-2 h-100">
            <div class="small text-muted">Expired Units</div>
            <div class="h6 mb-0 text-danger"><?= esc(number_format((float) ($expirySummary['expired'] ?? 0), 2)) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="border rounded p-2 h-100">
            <div class="small text-muted">Due 0-30 Days</div>
            <div class="h6 mb-0 text-warning"><?= esc(number_format((float) ($expirySummary['d0_30'] ?? 0), 2)) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="border rounded p-2 h-100">
            <div class="small text-muted">Due 31-60 Days</div>
            <div class="h6 mb-0 text-warning"><?= esc(number_format((float) ($expirySummary['d31_60'] ?? 0), 2)) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="border rounded p-2 h-100">
            <div class="small text-muted">Due 61-90 Days</div>
            <div class="h6 mb-0 text-warning"><?= esc(number_format((float) ($expirySummary['d61_90'] ?? 0), 2)) ?></div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body pt-3">
        <h6 class="mb-3">Near Expiry Graph (Current Units)</h6>
        <div style="height: 260px;">
            <canvas id="pharmacy-expiry-chart"></canvas>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body pt-3">
                <h6 class="mb-3">Highest Sale Medicines (Last 30 Days)</h6>
                <div style="height: 280px;">
                    <canvas id="pharmacy-top-sale-chart"></canvas>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table table-sm table-striped">
                        <thead>
                        <tr>
                            <th>Medicine</th>
                            <th class="text-end">Sale Qty (30d)</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($topSaleRows)): ?>
                            <?php foreach ($topSaleRows as $row): ?>
                                <tr>
                                    <td><?= esc((string) ($row['item_name'] ?? '')) ?></td>
                                    <td class="text-end"><?= esc(number_format((float) ($row['sale_qty_30'] ?? 0), 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-muted text-center">No sale data found for last 30 days.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body pt-3">
                <h6 class="mb-3">High Stock / Low Sale Ratio</h6>
                <div style="height: 280px;">
                    <canvas id="pharmacy-ratio-chart"></canvas>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table table-sm table-striped">
                        <thead>
                        <tr>
                            <th>Medicine</th>
                            <th class="text-end">Current Stock</th>
                            <th class="text-end">Sale Qty (30d)</th>
                            <th class="text-end">Ratio</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($ratioRows)): ?>
                            <?php foreach ($ratioRows as $row): ?>
                                <tr>
                                    <td><?= esc((string) ($row['item_name'] ?? '')) ?></td>
                                    <td class="text-end"><?= esc(number_format((float) ($row['current_unit_qty'] ?? 0), 2)) ?></td>
                                    <td class="text-end"><?= esc(number_format((float) ($row['sale_qty_30'] ?? 0), 2)) ?></td>
                                    <td class="text-end"><?= esc(number_format((float) ($row['stock_sale_ratio'] ?? 0), 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-muted text-center">No stock ratio data found.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end mb-2">
    <input type="text" id="medical-store-stock-search" class="form-control form-control-sm" style="max-width: 320px;" placeholder="Search Item / Generic">
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm align-middle" id="medical-store-stock-table">
        <thead>
        <tr>
            <th style="min-width: 240px;">Item Name</th>
            <th style="min-width: 180px;">Generic Name</th>
            <th class="text-end">Current Pak.</th>
            <th class="text-end">Current Unit Qty</th>
            <th class="text-end">Total Sale Pak.</th>
            <th class="text-end">Total Sale Unit Qty</th>
            <th class="text-end">Lost Unit</th>
            <th>Package/Re-Order Qty</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($stock_list ?? [])): ?>
            <?php foreach (($stock_list ?? []) as $row): ?>
                <tr>
                    <td><?= esc($row->item_name ?? '') ?></td>
                    <td><?= esc($row->genericname ?? '') ?></td>
                    <td class="text-end"><?= esc((string) ($row->C_Pak_Qty ?? 0)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row->C_Unit_Stock_Qty ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row->C_Pak_Sale_Qty ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row->sale_unit ?? 0), 2)) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($row->total_lost_unit ?? 0), 2)) ?></td>
                    <td><?= esc((string) ($row->packing ?? 0)) ?>/<?= esc((string) ($row->re_order_qty ?? 0)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" class="text-center text-muted">No stock items found for selected filters.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
(function () {
        let stockDataTableInstance = null;

    const labels = <?= json_encode($graphLabels, JSON_UNESCAPED_UNICODE) ?>;
    const values = <?= json_encode($graphValues, JSON_UNESCAPED_UNICODE) ?>;
    const expiryLabels = ['Expired', '0-30 Days', '31-60 Days', '61-90 Days'];
    const expiryValues = [
        <?= json_encode((float) ($expirySummary['expired'] ?? 0), JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode((float) ($expirySummary['d0_30'] ?? 0), JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode((float) ($expirySummary['d31_60'] ?? 0), JSON_UNESCAPED_UNICODE) ?>,
        <?= json_encode((float) ($expirySummary['d61_90'] ?? 0), JSON_UNESCAPED_UNICODE) ?>
    ];
    const topSaleLabels = <?= json_encode($topSaleLabels, JSON_UNESCAPED_UNICODE) ?>;
    const topSaleValues = <?= json_encode($topSaleValues, JSON_UNESCAPED_UNICODE) ?>;
    const ratioLabels = <?= json_encode($ratioLabels, JSON_UNESCAPED_UNICODE) ?>;
    const ratioValues = <?= json_encode($ratioValues, JSON_UNESCAPED_UNICODE) ?>;

    function renderChart() {
        const canvas = document.getElementById('pharmacy-stock-chart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }
        const ctx = canvas.getContext('2d');
        if (canvas.__chartInstance) {
            canvas.__chartInstance.destroy();
        }

        canvas.__chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Current Unit Qty',
                    data: values,
                    backgroundColor: '#0d6efd'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        const expiryCanvas = document.getElementById('pharmacy-expiry-chart');
        if (!expiryCanvas) {
            return;
        }

        const expiryCtx = expiryCanvas.getContext('2d');
        if (expiryCanvas.__chartInstance) {
            expiryCanvas.__chartInstance.destroy();
        }

        expiryCanvas.__chartInstance = new Chart(expiryCtx, {
            type: 'bar',
            data: {
                labels: expiryLabels,
                datasets: [{
                    label: 'Current Units',
                    data: expiryValues,
                    backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#20c997']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        const topSaleCanvas = document.getElementById('pharmacy-top-sale-chart');
        if (topSaleCanvas) {
            const topSaleCtx = topSaleCanvas.getContext('2d');
            if (topSaleCanvas.__chartInstance) {
                topSaleCanvas.__chartInstance.destroy();
            }
            topSaleCanvas.__chartInstance = new Chart(topSaleCtx, {
                type: 'bar',
                data: {
                    labels: topSaleLabels,
                    datasets: [{
                        label: 'Sale Qty (30 days)',
                        data: topSaleValues,
                        backgroundColor: '#198754'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { maxRotation: 45, minRotation: 0 } },
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        const ratioCanvas = document.getElementById('pharmacy-ratio-chart');
        if (ratioCanvas) {
            const ratioCtx = ratioCanvas.getContext('2d');
            if (ratioCanvas.__chartInstance) {
                ratioCanvas.__chartInstance.destroy();
            }
            ratioCanvas.__chartInstance = new Chart(ratioCtx, {
                type: 'bar',
                data: {
                    labels: ratioLabels,
                    datasets: [{
                        label: 'Stock/Sale Ratio',
                        data: ratioValues,
                        backgroundColor: '#6f42c1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { maxRotation: 45, minRotation: 0 } },
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    }

    function initStockDataTable() {
        if (!window.jQuery || !jQuery.fn || typeof jQuery.fn.DataTable !== 'function') {
            return null;
        }

        const tableId = '#medical-store-stock-table';
        if (!jQuery(tableId).length) {
            return null;
        }

        if (jQuery.fn.dataTable.isDataTable(tableId)) {
            jQuery(tableId).DataTable().destroy();
        }

        stockDataTableInstance = jQuery(tableId).DataTable({
            order: [[0, 'asc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            autoWidth: false
        });

        return stockDataTableInstance;
    }

    function applyManualStockFilter(term) {
        const table = document.getElementById('medical-store-stock-table');
        if (!table) {
            return;
        }

        const search = String(term || '').toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(function (row) {
            const text = (row.textContent || '').toLowerCase();
            row.style.display = text.indexOf(search) >= 0 ? '' : 'none';
        });
    }

    function bindStockSearchInput() {
        const input = document.getElementById('medical-store-stock-search');
        if (!input) {
            return;
        }

        input.oninput = function () {
            const term = input.value || '';
            if (stockDataTableInstance && typeof stockDataTableInstance.search === 'function') {
                stockDataTableInstance.search(term).draw();
                return;
            }
            applyManualStockFilter(term);
        };
    }

    function loadCssOnce(id, href) {
        if (document.getElementById(id)) {
            return;
        }
        const link = document.createElement('link');
        link.id = id;
        link.rel = 'stylesheet';
        link.href = href;
        document.head.appendChild(link);
    }

    function loadScriptOnce(id, src) {
        return new Promise(function (resolve, reject) {
            const existing = document.getElementById(id);
            if (existing) {
                if (existing.getAttribute('data-loaded') === '1') {
                    resolve();
                    return;
                }
                existing.addEventListener('load', function () { resolve(); }, { once: true });
                existing.addEventListener('error', function () { reject(new Error('Failed to load ' + src)); }, { once: true });
                return;
            }

            const script = document.createElement('script');
            script.id = id;
            script.src = src;
            script.onload = function () {
                script.setAttribute('data-loaded', '1');
                resolve();
            };
            script.onerror = function () {
                reject(new Error('Failed to load ' + src));
            };
            document.head.appendChild(script);
        });
    }

    function ensureDataTableAssets() {
        if (!window.jQuery) {
            return Promise.resolve(false);
        }
        if (window.jQuery.fn && typeof window.jQuery.fn.DataTable === 'function') {
            return Promise.resolve(true);
        }

        loadCssOnce('medical-dt-css-core', 'https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css');
        loadCssOnce('medical-dt-css-bs5', 'https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css');

        return loadScriptOnce('medical-dt-js-core', 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js')
            .then(function () {
                return loadScriptOnce('medical-dt-js-bs5', 'https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js');
            })
            .then(function () {
                return !!(window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.DataTable === 'function');
            })
            .catch(function () {
                return false;
            });
    }

    if (typeof Chart === 'undefined') {
        const script = document.createElement('script');
        script.src = '<?= base_url('assets/vendor/chart.js/chart.umd.js') ?>';
        script.onload = renderChart;
        document.head.appendChild(script);
    } else {
        renderChart();
    }

    bindStockSearchInput();

    ensureDataTableAssets().then(function (ready) {
        if (ready) {
            initStockDataTable();

            const input = document.getElementById('medical-store-stock-search');
            if (input && input.value) {
                stockDataTableInstance.search(input.value).draw();
            }
        }
    });
})();
</script>

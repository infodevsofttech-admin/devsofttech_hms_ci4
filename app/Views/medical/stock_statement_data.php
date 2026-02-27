<div class="d-flex justify-content-end mb-2">
    <input type="text" id="medical-stock-statement-search" class="form-control form-control-sm" style="max-width: 320px;" placeholder="Search Item">
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped table-sm align-middle" id="medical-stock-statement-table">
        <thead>
        <tr>
            <th style="min-width: 260px;">Item Name</th>
            <th class="text-end">Pur Pak.</th>
            <th class="text-end">Pur Cost</th>
            <th class="text-end">Current Pak.</th>
            <th class="text-end">Current Unit Qty</th>
            <th class="text-end">Total Sale Pak.</th>
            <th class="text-end">Total Sale Unit Qty</th>
            <th class="text-end">Lost Unit</th>
            <th>Package/Re-Order Qty</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach (($stock_list ?? []) as $row): ?>
            <tr>
                <td><?= esc($row->item_name ?? '') ?></td>
                <td class="text-end"><?= esc(number_format((float) ($row->total_pak_qty ?? 0), 2)) ?></td>
                <td class="text-end"><?= esc(number_format((float) ($row->purchase_cost ?? 0), 2)) ?></td>
                <td class="text-end"><?= esc(number_format((float) ($row->C_Pak_Qty ?? 0), 2)) ?></td>
                <td class="text-end"><?= esc(number_format((float) ($row->C_Unit_Stock_Qty ?? 0), 2)) ?></td>
                <td class="text-end"><?= esc(number_format((float) ($row->C_Pak_Sale_Qty ?? 0), 2)) ?></td>
                <td class="text-end"><?= esc(number_format((float) ($row->sale_unit ?? 0), 2)) ?></td>
                <td class="text-end"><?= esc(number_format((float) ($row->total_lost_unit ?? 0), 2)) ?></td>
                <td><?= esc((string) ($row->packing ?? 0)) ?>/<?= esc((string) ($row->re_order_qty ?? 0)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
(function () {
    let statementDataTable = null;

    function initDataTable() {
        if (!window.jQuery || !jQuery.fn || typeof jQuery.fn.DataTable !== 'function') {
            return null;
        }

        const tableId = '#medical-stock-statement-table';
        if (!jQuery(tableId).length) {
            return null;
        }

        if (jQuery.fn.dataTable.isDataTable(tableId)) {
            jQuery(tableId).DataTable().destroy();
        }

        statementDataTable = jQuery(tableId).DataTable({
            order: [[0, 'asc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            autoWidth: false
        });

        return statementDataTable;
    }

    function applyManualFilter(term) {
        const table = document.getElementById('medical-stock-statement-table');
        if (!table) {
            return;
        }

        const rows = table.querySelectorAll('tbody tr');
        const text = String(term || '').toLowerCase();

        rows.forEach(function (row) {
            const rowText = (row.textContent || '').toLowerCase();
            row.style.display = rowText.indexOf(text) >= 0 ? '' : 'none';
        });
    }

    function bindSearch() {
        const input = document.getElementById('medical-stock-statement-search');
        if (!input) {
            return;
        }

        input.oninput = function () {
            const term = input.value || '';
            if (statementDataTable && typeof statementDataTable.search === 'function') {
                statementDataTable.search(term).draw();
                return;
            }
            applyManualFilter(term);
        };
    }

    bindSearch();
    initDataTable();
})();
</script>

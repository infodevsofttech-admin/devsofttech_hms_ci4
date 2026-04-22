<?= $this->include('storestock/_niceadmin_theme') ?>
<div class="storestock-ui">
<div class="row">
    <div class="col-md-12">
        <div id="stock_result_body" class="box-body" style="height:500px;overflow-y:auto;">
            <table id="stock_result_table" class="table table-bordered table-striped TableData">
                <thead>
                    <tr>
                        <th style="width:300px;">Item Name</th>
                        <th>Current Pak.</th>
                        <th>Current Unit Qty</th>
                        <th>Total Sale Pak.</th>
                        <th>Total Sale Unit Qty</th>
                        <th>Lost Unit</th>
                        <th>Package / Re-Order Qty</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stock_list as $row) { ?>
                        <tr>
                            <td>
                                <p class="text-danger">
                                    <a href="javascript:void(0)"
                                       onclick="loadProductStock(<?= (int)$row->id ?>, <?= (float)($row->packing ?? 1) ?>, '<?= esc($row->item_name) ?>')">
                                        <?= esc($row->item_name) ?>
                                    </a>
                                </p>
                            </td>
                            <td><p class="text-warning"><?= esc($row->C_Pak_Qty ?? '') ?></p></td>
                            <td><p class="text-warning"><?= round($row->C_Unit_Stock_Qty ?? 0, 2) ?></p></td>
                            <td><p class="text-warning"><?= round($row->C_Pak_Sale_Qty ?? 0, 2) ?></p></td>
                            <td><p class="text-warning"><?= round($row->sale_unit ?? 0, 2) ?></p></td>
                            <td><p class="text-danger"><?= round($row->total_lost_unit ?? 0, 2) ?></p></td>
                            <td><p class="text-warning"><?= esc($row->packing ?? '') ?> / <?= esc($row->re_order_qty ?? '') ?></p></td>
                            <td>
                                <button type="button" class="btn btn-primary btn-xs"
                                        onclick="loadProductStock(<?= (int)$row->id ?>, <?= (float)($row->packing ?? 1) ?>, '<?= esc($row->item_name) ?>')">
                                    Show
                                </button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th style="width:300px;">Item Name</th>
                        <th>Current Pak.</th>
                        <th>Current Unit Qty</th>
                        <th>Total Sale Pak.</th>
                        <th>Total Sale Unit Qty</th>
                        <th>Lost Unit</th>
                        <th>Package / Re-Order Qty</th>
                        <th>Detail</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Product Stock History Modal -->
<div class="modal fade" id="productStockModal" tabindex="-1" role="dialog" aria-labelledby="productStockModalLabel">
    <div class="modal-dialog modal-lg" role="document" style="width:95%;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="productStockModalLabel">Product Stock Detail</h4>
            </div>
            <div class="modal-body" style="overflow:auto;" id="productStockModalBody">
                <div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    if ($.fn.DataTable) {
        $('#stock_result_table').DataTable({ "order": [[0, "asc"]] });
    }
});

function loadProductStock(product_id, product_pak, product_name) {
    $('#productStockModalLabel').text('Stock Detail : ' + product_name);
    $('#productStockModalBody').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');
    $('#productStockModal').modal('show');

    $.post('/Storestock/get_product_stock/' + product_id, {
        product_pak: product_pak,
        '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
    }, function (data) {
        $('#productStockModalBody').html(data);
    });
}
</script>
</div>

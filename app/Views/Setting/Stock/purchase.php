<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Purchase Orders & Receiving</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#poCreateBox">New PO</button>
    </div>
    <div class="card-body">
        <div id="purchaseAlert" class="alert alert-danger d-none"></div>

        <div id="poCreateBox" class="collapse show mb-3">
            <form id="poForm" class="row g-2">
                <?= csrf_field() ?>
                <div class="col-md-3">
                    <select class="form-select" name="supplier_id" required>
                        <option value="">Supplier</option>
                        <?php foreach (($suppliers ?? []) as $sp): ?>
                            <option value="<?= (int) ($sp['id'] ?? 0) ?>"><?= esc((string) ($sp['name'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><input class="form-control" type="date" name="order_date"></div>
                <div class="col-md-2"><input class="form-control" type="date" name="expected_date"></div>
                <div class="col-md-3"><input class="form-control" name="remarks" placeholder="PO remarks"></div>
                <div class="col-md-2"><button class="btn btn-success btn-sm" type="submit">Create PO</button></div>
            </form>

            <div class="row g-2 mt-1">
                <div class="col-md-5">
                    <select id="poItemSelect" class="form-select form-select-sm">
                        <option value="">Select item</option>
                        <?php foreach (($items ?? []) as $it): ?>
                            <option value="<?= (int) ($it['id'] ?? 0) ?>"><?= esc((string) (($it['item_code'] ?? '') . ' - ' . ($it['name'] ?? ''))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><input type="number" step="0.01" min="0.01" id="poQty" class="form-control form-control-sm" placeholder="Qty"></div>
                <div class="col-md-2"><input type="number" step="0.01" min="0" id="poRate" class="form-control form-control-sm" placeholder="Rate"></div>
                <div class="col-md-2"><input type="number" step="0.01" min="0" id="poTax" class="form-control form-control-sm" placeholder="Tax %"></div>
                <div class="col-md-1"><button id="addPoItem" type="button" class="btn btn-outline-primary btn-sm w-100">Add</button></div>
            </div>
            <table class="table table-sm mt-2" id="poItemsTable">
                <thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Tax %</th><th></th></tr></thead>
                <tbody></tbody>
            </table>
        </div>

        <h6>Purchase Orders</h6>
        <table class="table table-striped table-sm" id="poTable">
            <thead><tr><th>PO No</th><th>Date</th><th>Supplier</th><th>Status</th><th>Total</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach (($purchaseOrders ?? []) as $po): ?>
                <tr>
                    <td><?= esc((string) ($po['po_code'] ?? '')) ?></td>
                    <td><?= esc((string) ($po['order_date'] ?? '')) ?></td>
                    <td><?= esc((string) ($po['supplier_name'] ?? '')) ?></td>
                    <td><?= esc((string) ($po['status'] ?? 'ordered')) ?></td>
                    <td>-</td>
                    <td>
                        <?php if (! in_array((string) ($po['status'] ?? ''), ['completed', 'cancelled'], true)): ?>
                            <button class="btn btn-outline-success btn-sm receive-po" data-id="<?= (int) ($po['id'] ?? 0) ?>">Receive</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function(){
    var poItems=[];
    function showErr(msg){ var b=document.getElementById('purchaseAlert'); if(!b) return; b.textContent=msg; b.classList.remove('d-none'); }
    function clearErr(){ var b=document.getElementById('purchaseAlert'); if(!b) return; b.textContent=''; b.classList.add('d-none'); }
    function reloadPurchase(){ load_form_div('<?= base_url('setting/admin/hospital-stock/purchase') ?>','stockmaindiv','Stock Purchase'); }

    function renderPoItems(){
        var $tb = $('#poItemsTable tbody'); $tb.empty();
        poItems.forEach(function(it,idx){
            $tb.append('<tr><td>'+it.item_name+'</td><td>'+it.quantity+'</td><td>'+it.unit_rate+'</td><td>'+it.tax_percent+'</td><td><button type="button" class="btn btn-outline-danger btn-sm remove-po-item" data-idx="'+idx+'">X</button></td></tr>');
        });
    }

    $('#addPoItem').on('click', function(){
        clearErr();
        var id = parseInt($('#poItemSelect').val() || '0', 10);
        var qty = parseFloat($('#poQty').val() || '0');
        var rate = parseFloat($('#poRate').val() || '0');
        var tax = parseFloat($('#poTax').val() || '0');
        var itemName = $('#poItemSelect option:selected').text();
        if(id <= 0 || qty <= 0){ showErr('Select item and quantity.'); return; }
        poItems.push({ item_id:id, quantity:qty, unit_rate:rate, tax_percent:tax, item_name:itemName });
        renderPoItems();
        $('#poQty,#poRate,#poTax').val('');
    });

    $(document).on('click','.remove-po-item', function(){
        var idx = parseInt($(this).data('idx') || '-1', 10);
        if(idx >= 0){ poItems.splice(idx,1); renderPoItems(); }
    });

    $('#poForm').on('submit', function(e){
        e.preventDefault(); clearErr();
        if(poItems.length === 0){ showErr('Add at least one item.'); return; }
        var data = $(this).serializeArray();
        poItems.forEach(function(it){
            data.push({name:'item_id[]', value: it.item_id});
            data.push({name:'qty[]', value: it.quantity});
            data.push({name:'unit_cost[]', value: it.unit_rate});
        });
        $.post('<?= base_url('setting/admin/hospital-stock/po/create') ?>', $.param(data))
            .done(function(resp){ if(resp && resp.error_text){showErr(resp.error_text); return;} reloadPurchase(); })
            .fail(function(){ showErr('Save failed.'); });
    });

    $(document).on('click','.receive-po', function(){
        if(!confirm('Mark this PO as received and update stock ledger?')) return;
        $.post('<?= base_url('setting/admin/hospital-stock/po/receive') ?>',{purchase_order_id:$(this).data('id'),'<?= csrf_token() ?>':'<?= csrf_hash() ?>'})
            .done(function(resp){ if(resp && resp.error_text){showErr(resp.error_text); return;} reloadPurchase(); })
            .fail(function(){ showErr('Receive failed.'); });
    });

    if($.fn && $.fn.DataTable){ $('#poTable').DataTable({pageLength:10, order:[[1,'desc']]}); }
})();
</script>

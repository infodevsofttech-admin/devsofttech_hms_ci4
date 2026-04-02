<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Department Indents & Issue</h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#indentCreateBox">New Request</button>
    </div>
    <div class="card-body">
        <div id="indentAlert" class="alert alert-danger d-none"></div>

        <div id="indentCreateBox" class="collapse show mb-3">
            <form id="indentForm" class="row g-2">
                <?= csrf_field() ?>
                <div class="col-md-3">
                    <select class="form-select" name="department_id" required>
                        <option value="">Department</option>
                        <?php foreach (($departments ?? []) as $d): ?>
                            <option value="<?= (int) ($d['iId'] ?? 0) ?>"><?= esc((string) ($d['vName'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><input class="form-control" type="date" name="required_date"></div>
                <div class="col-md-4"><input class="form-control" name="remarks" placeholder="Reason / remarks"></div>
                <div class="col-md-3"><button class="btn btn-success btn-sm" type="submit">Submit Request</button></div>
            </form>

            <div class="row g-2 mt-1">
                <div class="col-md-6">
                    <select id="indentItemSelect" class="form-select form-select-sm">
                        <option value="">Select item</option>
                        <?php foreach (($items ?? []) as $it): ?>
                            <option value="<?= (int) ($it['id'] ?? 0) ?>" data-uom="<?= esc((string) ($it['issue_uom'] ?? ($it['uom'] ?? 'Unit')), 'attr') ?>"><?= esc((string) (($it['item_code'] ?? '') . ' - ' . ($it['name'] ?? ''))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><input type="number" step="0.01" min="0.01" id="indentQty" class="form-control form-control-sm" placeholder="Qty"></div>
                <div class="col-md-2"><input id="indentUom" class="form-control form-control-sm" placeholder="UOM"></div>
                <div class="col-md-2"><button id="addIndentItem" type="button" class="btn btn-outline-primary btn-sm w-100">Add</button></div>
            </div>
            <table class="table table-sm mt-2" id="indentItemsTable">
                <thead><tr><th>Item</th><th>Qty</th><th>UOM</th><th></th></tr></thead>
                <tbody></tbody>
            </table>
        </div>

        <h6>Requests</h6>
        <table class="table table-striped table-sm" id="indentsTable">
            <thead><tr><th>Req No</th><th>Date</th><th>Department</th><th>Status</th><th>Requested By</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach (($indents ?? []) as $in): ?>
                <tr>
                    <td><?= esc((string) ($in['indent_code'] ?? '')) ?></td>
                    <td><?= esc((string) ($in['created_at'] ?? '')) ?></td>
                    <td><?= esc((string) ($in['department_name'] ?? '')) ?></td>
                    <td><?= esc((string) ($in['status'] ?? 'pending')) ?></td>
                    <td><?= esc((string) ($in['requested_by'] ?? '')) ?></td>
                    <td class="d-flex gap-1">
                        <?php if (($in['status'] ?? '') === 'pending'): ?>
                            <button class="btn btn-outline-success btn-sm approve-indent" data-id="<?= (int) ($in['id'] ?? 0) ?>">Approve</button>
                        <?php endif; ?>
                        <?php if (($in['status'] ?? '') === 'approved'): ?>
                            <button class="btn btn-outline-primary btn-sm issue-indent" data-id="<?= (int) ($in['id'] ?? 0) ?>">Issue</button>
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
    var indentItems=[];
    function showErr(msg){ var b=document.getElementById('indentAlert'); if(!b) return; b.textContent=msg; b.classList.remove('d-none'); }
    function clearErr(){ var b=document.getElementById('indentAlert'); if(!b) return; b.textContent=''; b.classList.add('d-none'); }
    function reloadIndents(){ load_form_div('<?= base_url('setting/admin/hospital-stock/indents') ?>','stockmaindiv','Stock Indents'); }

    function renderIndentItems(){
        var $tb = $('#indentItemsTable tbody'); $tb.empty();
        indentItems.forEach(function(it,idx){
            var tr = '<tr><td>'+it.item_name+'</td><td>'+it.quantity+'</td><td>'+it.uom+'</td><td><button type="button" class="btn btn-outline-danger btn-sm remove-indent-item" data-idx="'+idx+'">X</button></td></tr>';
            $tb.append(tr);
        });
    }

    $('#indentItemSelect').on('change', function(){
        var uom = $('#indentItemSelect option:selected').data('uom') || '';
        $('#indentUom').val(uom);
    });

    $('#addIndentItem').on('click', function(){
        clearErr();
        var id = parseInt($('#indentItemSelect').val() || '0', 10);
        var qty = parseFloat($('#indentQty').val() || '0');
        var uom = $('#indentUom').val() || '';
        var itemName = $('#indentItemSelect option:selected').text();
        if(id <= 0 || qty <= 0){ showErr('Select item and quantity.'); return; }
        indentItems.push({ item_id:id, quantity:qty, uom:uom, item_name:itemName });
        renderIndentItems();
        $('#indentQty').val('');
    });

    $(document).on('click','.remove-indent-item', function(){
        var idx = parseInt($(this).data('idx') || '-1', 10);
        if(idx >= 0){ indentItems.splice(idx,1); renderIndentItems(); }
    });

    $('#indentForm').on('submit', function(e){
        e.preventDefault(); clearErr();
        if(indentItems.length === 0){ showErr('Add at least one item.'); return; }
        var data = $(this).serializeArray();
        data.push({name:'department_name', value: $('#indentForm [name="department_id"] option:selected').text()});
        indentItems.forEach(function(it){
            data.push({name:'item_id[]', value: it.item_id});
            data.push({name:'qty[]', value: it.quantity});
        });
        $.post('<?= base_url('setting/admin/hospital-stock/indent/create') ?>', $.param(data))
            .done(function(resp){ if(resp && resp.error_text){showErr(resp.error_text); return;} reloadIndents(); })
            .fail(function(){ showErr('Save failed.'); });
    });

    $(document).on('click','.approve-indent', function(){
        $.post('<?= base_url('setting/admin/hospital-stock/indent/approve') ?>',{indent_id:$(this).data('id'),'<?= csrf_token() ?>':'<?= csrf_hash() ?>'})
            .done(function(resp){ if(resp && resp.error_text){showErr(resp.error_text); return;} reloadIndents(); })
            .fail(function(){ showErr('Approve failed.'); });
    });

    $(document).on('click','.issue-indent', function(){
        var id = $(this).data('id');
        var note = prompt('Issue remarks (optional):', 'Issued by store');
        if(note === null){ return; }
        $.post('<?= base_url('setting/admin/hospital-stock/indent/issue') ?>',{indent_id:id, remarks:note,'<?= csrf_token() ?>':'<?= csrf_hash() ?>'})
            .done(function(resp){ if(resp && resp.error_text){showErr(resp.error_text); return;} reloadIndents(); })
            .fail(function(){ showErr('Issue failed.'); });
    });

    if($.fn && $.fn.DataTable){ $('#indentsTable').DataTable({pageLength:10, order:[[1,'desc']]}); }
})();
</script>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Item-wise Masters</h5></div>
    <div class="card-body">
        <div id="masterAlert" class="alert alert-danger d-none"></div>
        <div class="row g-3">
            <div class="col-lg-4">
                <h6>Category</h6>
                <form id="categoryForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="category_id" value="0">
                    <input class="form-control mb-2" name="name" placeholder="Category name" required>
                    <textarea class="form-control mb-2" name="description" placeholder="Description"></textarea>
                    <select class="form-select mb-2" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-sm" type="submit" id="categorySubmitBtn">Save Category</button>
                        <button class="btn btn-light btn-sm" type="button" id="categoryResetBtn">Clear</button>
                    </div>
                </form>
                <hr/>
                <table class="table table-sm table-striped" id="categoryTable">
                    <thead><tr><th>Category</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach (($categories ?? []) as $cat): ?>
                        <tr>
                            <td><?= esc((string) ($cat['name'] ?? '')) ?></td>
                            <td><?= esc((string) ($cat['status'] ?? 'active')) ?></td>
                            <td>
                                <button class="btn btn-outline-secondary btn-sm edit-category"
                                    data-id="<?= (int) ($cat['id'] ?? 0) ?>"
                                    data-name="<?= esc((string) ($cat['name'] ?? ''), 'attr') ?>"
                                    data-description="<?= esc((string) ($cat['description'] ?? ''), 'attr') ?>"
                                    data-status="<?= esc((string) ($cat['status'] ?? 'active'), 'attr') ?>">Edit</button>
                                <button class="btn btn-outline-danger btn-sm delete-category" data-id="<?= (int) ($cat['id'] ?? 0) ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="col-lg-8">
                <h6>Item</h6>
                <form id="itemForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="item_id_hidden" value="0">
                    <div class="row g-2">
                        <div class="col-md-2"><input class="form-control" name="item_code" placeholder="Item code" required></div>
                        <div class="col-md-4"><input class="form-control" name="name" placeholder="Item name" required></div>
                        <div class="col-md-2"><select class="form-select" name="category_id" required><option value="">Category</option><?php foreach (($categories ?? []) as $cat): ?><option value="<?= (int) ($cat['id'] ?? 0) ?>"><?= esc((string) ($cat['name'] ?? '')) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-2"><input class="form-control" name="item_type" placeholder="Type (paper/liquid/form)"></div>
                        <div class="col-md-2"><input class="form-control" name="store_location" placeholder="Store location"></div>

                        <div class="col-md-2"><input class="form-control" name="uom" value="Unit" placeholder="UOM"></div>
                        <div class="col-md-2"><input class="form-control" name="purchase_uom" value="Unit" placeholder="Purchase UOM"></div>
                        <div class="col-md-2"><input class="form-control" name="issue_uom" value="Unit" placeholder="Issue UOM"></div>
                        <div class="col-md-2"><input class="form-control" name="issue_per_purchase" type="number" step="0.0001" value="1" placeholder="Issue/Purchase"></div>
                        <div class="col-md-2"><input class="form-control" name="current_stock" type="number" step="0.01" placeholder="Stock"></div>
                        <div class="col-md-2"><input class="form-control" name="min_stock_level" type="number" step="0.01" placeholder="Min stock"></div>

                        <div class="col-md-2"><input class="form-control" name="reorder_level" type="number" step="0.01" placeholder="Reorder"></div>
                        <div class="col-md-2"><input class="form-control" name="unit_cost" type="number" step="0.01" placeholder="Unit cost"></div>
                        <div class="col-md-2"><input class="form-control" name="expiry_date" type="date"></div>
                        <div class="col-md-2"><input class="form-control" name="barcode" placeholder="Barcode"></div>
                        <div class="col-md-2"><input class="form-control" name="qr_code" placeholder="QR code"></div>
                        <div class="col-md-2">
                            <select class="form-select" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                        </div>
                        <div class="col-md-2 d-flex align-items-center">
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="is_daily_use" id="is_daily_use"><label class="form-check-label" for="is_daily_use">Daily use</label></div>
                        </div>
                        <div class="col-md-12 d-flex gap-2">
                            <button class="btn btn-primary btn-sm" type="submit" id="itemSubmitBtn">Save Item</button>
                            <button class="btn btn-light btn-sm" type="button" id="itemResetBtn">Clear</button>
                        </div>
                    </div>
                </form>

                <hr/>
                <table class="table table-sm table-striped" id="itemMasterTable">
                    <thead><tr><th>Code</th><th>Name</th><th>Type</th><th>UOM</th><th>Stock</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach (($items ?? []) as $it): ?>
                        <tr>
                            <td><?= esc((string) ($it['item_code'] ?? '')) ?></td>
                            <td><?= esc((string) ($it['name'] ?? '')) ?></td>
                            <td><?= esc((string) ($it['item_type'] ?? '')) ?></td>
                            <td><?= esc((string) ($it['issue_uom'] ?? ($it['uom'] ?? ''))) ?></td>
                            <td><?= esc((string) ($it['current_stock'] ?? '0')) ?></td>
                            <td>
                                <button class="btn btn-outline-secondary btn-sm edit-item"
                                    data-id="<?= (int) ($it['id'] ?? 0) ?>"
                                    data-item_code="<?= esc((string) ($it['item_code'] ?? ''), 'attr') ?>"
                                    data-name="<?= esc((string) ($it['name'] ?? ''), 'attr') ?>"
                                    data-category_id="<?= (int) ($it['category_id'] ?? 0) ?>"
                                    data-item_type="<?= esc((string) ($it['item_type'] ?? ''), 'attr') ?>"
                                    data-store_location="<?= esc((string) ($it['store_location'] ?? ''), 'attr') ?>"
                                    data-uom="<?= esc((string) ($it['uom'] ?? 'Unit'), 'attr') ?>"
                                    data-purchase_uom="<?= esc((string) ($it['purchase_uom'] ?? 'Unit'), 'attr') ?>"
                                    data-issue_uom="<?= esc((string) ($it['issue_uom'] ?? 'Unit'), 'attr') ?>"
                                    data-issue_per_purchase="<?= esc((string) ($it['issue_per_purchase'] ?? '1'), 'attr') ?>"
                                    data-current_stock="<?= esc((string) ($it['current_stock'] ?? '0'), 'attr') ?>"
                                    data-min_stock_level="<?= esc((string) ($it['min_stock_level'] ?? '0'), 'attr') ?>"
                                    data-reorder_level="<?= esc((string) ($it['reorder_level'] ?? '0'), 'attr') ?>"
                                    data-unit_cost="<?= esc((string) ($it['unit_cost'] ?? '0'), 'attr') ?>"
                                    data-expiry_date="<?= esc((string) ($it['expiry_date'] ?? ''), 'attr') ?>"
                                    data-barcode="<?= esc((string) ($it['barcode'] ?? ''), 'attr') ?>"
                                    data-qr_code="<?= esc((string) ($it['qr_code'] ?? ''), 'attr') ?>"
                                    data-status="<?= esc((string) ($it['status'] ?? 'active'), 'attr') ?>"
                                    data-is_daily_use="<?= (int) ($it['is_daily_use'] ?? 0) ?>">Edit</button>
                                <button class="btn btn-outline-danger btn-sm delete-item" data-id="<?= (int) ($it['id'] ?? 0) ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="col-12">
                <h6>Supplier</h6>
                <form id="supplierForm" class="row g-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="supplier_id_hidden" value="0">
                    <div class="col-md-3"><input class="form-control" name="name" placeholder="Supplier name" required></div>
                    <div class="col-md-2"><input class="form-control" name="contact_person" placeholder="Contact person"></div>
                    <div class="col-md-2"><input class="form-control" name="phone" placeholder="Phone"></div>
                    <div class="col-md-2"><input class="form-control" name="email" placeholder="Email"></div>
                    <div class="col-md-2"><input class="form-control" name="gst_no" placeholder="GST"></div>
                    <div class="col-md-1"><select class="form-select" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                    <div class="col-md-12"><textarea class="form-control" name="address" placeholder="Address"></textarea></div>
                    <div class="col-md-12 d-flex gap-2"><button class="btn btn-primary btn-sm" type="submit" id="supplierSubmitBtn">Save Supplier</button><button class="btn btn-light btn-sm" type="button" id="supplierResetBtn">Clear</button></div>
                </form>
                <hr/>
                <table class="table table-sm table-striped" id="supplierTable">
                    <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach (($suppliers ?? []) as $sp): ?>
                        <tr>
                            <td><?= esc((string) ($sp['name'] ?? '')) ?></td>
                            <td><?= esc((string) ($sp['phone'] ?? '')) ?></td>
                            <td><?= esc((string) ($sp['email'] ?? '')) ?></td>
                            <td>
                                <button class="btn btn-outline-secondary btn-sm edit-supplier"
                                    data-id="<?= (int) ($sp['id'] ?? 0) ?>"
                                    data-name="<?= esc((string) ($sp['name'] ?? ''), 'attr') ?>"
                                    data-contact_person="<?= esc((string) ($sp['contact_person'] ?? ''), 'attr') ?>"
                                    data-phone="<?= esc((string) ($sp['phone'] ?? ''), 'attr') ?>"
                                    data-email="<?= esc((string) ($sp['email'] ?? ''), 'attr') ?>"
                                    data-gst_no="<?= esc((string) ($sp['gst_no'] ?? ''), 'attr') ?>"
                                    data-address="<?= esc((string) ($sp['address'] ?? ''), 'attr') ?>"
                                    data-status="<?= esc((string) ($sp['status'] ?? 'active'), 'attr') ?>">Edit</button>
                                <button class="btn btn-outline-danger btn-sm delete-supplier" data-id="<?= (int) ($sp['id'] ?? 0) ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    function showErr(msg){
        var b=document.getElementById('masterAlert');
        if(!b) return;
        b.textContent=msg; b.classList.remove('d-none');
    }
    function clearErr(){
        var b=document.getElementById('masterAlert');
        if(!b) return;
        b.textContent=''; b.classList.add('d-none');
    }
    function reloadPage(){ load_form_div('<?= base_url('setting/admin/hospital-stock/masters') ?>','stockmaindiv','Stock Masters'); }

    function ajaxSubmit($form, saveUrl, updateUrl, idSelector){
        $form.on('submit', function(e){
            e.preventDefault(); clearErr();
            var id = parseInt($(idSelector).val() || '0', 10);
            var url = id > 0 ? updateUrl : saveUrl;
            $.post(url, $form.serialize()).done(function(resp){
                if(resp && resp.error_text){ showErr(resp.error_text); return; }
                reloadPage();
            }).fail(function(){ showErr('Request failed.'); });
        });
    }

    ajaxSubmit($('#categoryForm'), '<?= base_url('setting/admin/hospital-stock/category/save') ?>', '<?= base_url('setting/admin/hospital-stock/category/update') ?>', '#category_id');
    ajaxSubmit($('#itemForm'), '<?= base_url('setting/admin/hospital-stock/item/save') ?>', '<?= base_url('setting/admin/hospital-stock/item/update') ?>', '#item_id_hidden');
    ajaxSubmit($('#supplierForm'), '<?= base_url('setting/admin/hospital-stock/supplier/save') ?>', '<?= base_url('setting/admin/hospital-stock/supplier/update') ?>', '#supplier_id_hidden');

    $('#categoryResetBtn').on('click', function(){ $('#categoryForm')[0].reset(); $('#category_id').val(0); $('#categorySubmitBtn').text('Save Category'); });
    $('#itemResetBtn').on('click', function(){ $('#itemForm')[0].reset(); $('#item_id_hidden').val(0); $('#itemSubmitBtn').text('Save Item'); $('#is_daily_use').prop('checked', false); });
    $('#supplierResetBtn').on('click', function(){ $('#supplierForm')[0].reset(); $('#supplier_id_hidden').val(0); $('#supplierSubmitBtn').text('Save Supplier'); });

    $(document).on('click','.edit-category', function(){
        $('#category_id').val($(this).data('id'));
        $('#categoryForm [name="name"]').val($(this).data('name'));
        $('#categoryForm [name="description"]').val($(this).data('description'));
        $('#categoryForm [name="status"]').val($(this).data('status'));
        $('#categorySubmitBtn').text('Update Category');
    });
    $(document).on('click','.delete-category', function(){
        if(!confirm('Delete this category?')) return;
        $.post('<?= base_url('setting/admin/hospital-stock/category/delete') ?>',{id:$(this).data('id'),'<?= csrf_token() ?>':'<?= csrf_hash() ?>'}).done(function(resp){ if(resp && resp.error_text){showErr(resp.error_text);return;} reloadPage(); }).fail(function(){showErr('Request failed.');});
    });

    $(document).on('click','.edit-item', function(){
        $('#item_id_hidden').val($(this).data('id'));
        var f='#itemForm ';
        $(f+'[name="item_code"]').val($(this).data('item_code'));
        $(f+'[name="name"]').val($(this).data('name'));
        $(f+'[name="category_id"]').val($(this).data('category_id'));
        $(f+'[name="item_type"]').val($(this).data('item_type'));
        $(f+'[name="store_location"]').val($(this).data('store_location'));
        $(f+'[name="uom"]').val($(this).data('uom'));
        $(f+'[name="purchase_uom"]').val($(this).data('purchase_uom'));
        $(f+'[name="issue_uom"]').val($(this).data('issue_uom'));
        $(f+'[name="issue_per_purchase"]').val($(this).data('issue_per_purchase'));
        $(f+'[name="current_stock"]').val($(this).data('current_stock'));
        $(f+'[name="min_stock_level"]').val($(this).data('min_stock_level'));
        $(f+'[name="reorder_level"]').val($(this).data('reorder_level'));
        $(f+'[name="unit_cost"]').val($(this).data('unit_cost'));
        $(f+'[name="expiry_date"]').val($(this).data('expiry_date'));
        $(f+'[name="barcode"]').val($(this).data('barcode'));
        $(f+'[name="qr_code"]').val($(this).data('qr_code'));
        $(f+'[name="status"]').val($(this).data('status'));
        $('#is_daily_use').prop('checked', parseInt($(this).data('is_daily_use')||'0',10)===1);
        $('#itemSubmitBtn').text('Update Item');
    });
    $(document).on('click','.delete-item', function(){
        if(!confirm('Delete this item?')) return;
        $.post('<?= base_url('setting/admin/hospital-stock/item/delete') ?>',{id:$(this).data('id'),'<?= csrf_token() ?>':'<?= csrf_hash() ?>'}).done(function(resp){ if(resp && resp.error_text){showErr(resp.error_text);return;} reloadPage(); }).fail(function(){showErr('Request failed.');});
    });

    $(document).on('click','.edit-supplier', function(){
        $('#supplier_id_hidden').val($(this).data('id'));
        var f='#supplierForm ';
        $(f+'[name="name"]').val($(this).data('name'));
        $(f+'[name="contact_person"]').val($(this).data('contact_person'));
        $(f+'[name="phone"]').val($(this).data('phone'));
        $(f+'[name="email"]').val($(this).data('email'));
        $(f+'[name="gst_no"]').val($(this).data('gst_no'));
        $(f+'[name="address"]').val($(this).data('address'));
        $(f+'[name="status"]').val($(this).data('status'));
        $('#supplierSubmitBtn').text('Update Supplier');
    });
    $(document).on('click','.delete-supplier', function(){
        if(!confirm('Delete this supplier?')) return;
        $.post('<?= base_url('setting/admin/hospital-stock/supplier/delete') ?>',{id:$(this).data('id'),'<?= csrf_token() ?>':'<?= csrf_hash() ?>'}).done(function(resp){ if(resp && resp.error_text){showErr(resp.error_text);return;} reloadPage(); }).fail(function(){showErr('Request failed.');});
    });

    if($.fn && $.fn.DataTable){
        $('#categoryTable').DataTable({pageLength:5, order:[[0,'asc']]});
        $('#itemMasterTable').DataTable({pageLength:8, order:[[0,'asc']]});
        $('#supplierTable').DataTable({pageLength:5, order:[[0,'asc']]});
    }
})();
</script>

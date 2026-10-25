<div class="row g-2">
    <div class="col-md-8">
        <label class="form-label fw-semibold" for="input_drug">Product Search</label>
        <input class="form-control form-control-sm" name="input_drug" id="input_drug" placeholder="Type Item Code, Item Name, Batch..." type="text" autocomplete="off">
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <span class="text-muted small" id="input_product_code"></span>
    </div>
</div>

<div class="row mt-2">
    <div class="col-12">
        <input type="hidden" id="l_ssno" name="l_ssno" value="0">
        <input type="hidden" id="purchase_id" name="purchase_id" value="0">
        <div class="p-2 border rounded bg-light mb-2" id="selected_item_preview" style="min-height: 38px; display: none;">
            <div class="d-flex flex-wrap gap-2 align-items-center" style="font-size: 0.85rem;">
                <span class="text-success fw-bold" id="input_product_name"></span>
                <span class="text-secondary" id="input_batch"></span>
                <span class="text-primary" id="input_product_mrp"></span>
                <span class="text-dark" id="stock_product_qty"></span>
                <span id="input_supplier_info"></span>
                <span id="input_expiry_status"></span>
            </div>
        </div>
    </div>
</div>

<div class="row g-2 align-items-end">
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Unit Rate (P. Rate)</label>
        <input class="form-control form-control-sm" name="input_product_unit_rate" id="input_product_unit_rate" placeholder="Unit Rate" autocomplete="off">
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Return Qty</label>
        <input class="form-control form-control-sm" name="input_product_qty" id="input_product_qty" placeholder="Qty" type="number" min="1" step="any" value="" autocomplete="off">
        <input type="hidden" id="hid_c_qty" name="hid_c_qty" value="0">
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Batch No.</label>
        <input class="form-control form-control-sm" name="input_batch_no" id="input_batch_no" placeholder="Batch No." type="text">
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Exp.Date</label>
        <input class="form-control form-control-sm" name="input_expiry_dt" id="input_expiry_dt" type="date" autocomplete="off">
    </div>
    <div class="col-12 text-end mt-2">
        <button type="button" class="btn btn-primary btn-sm px-4" onclick="remove_custom_item()"><i class="fa fa-plus me-1"></i> Add to Return Invoice</button>
    </div>
</div>

<script>
(function () {
    var currentSuppId = <?= (int) ($supp_id ?? 0) ?>;

    $('#input_drug').autocomplete({
        source: function (request, response) {
            $.getJSON('<?= base_url('Medical_backpanel/get_drug') ?>', request, function (data) {
                response(data || []);
            });
        },
        minLength: 1,
        autofocus: true,
        select: function (event, ui) {
            var item = ui.item || {};
            var purRate = parseFloat(item.l_pur_unit_rate || 0);
            if (purRate <= 0) {
                purRate = parseFloat(item.l_unit_rate || 0);
            }
            var expText = item.l_expiry_display || item.l_Expiry || '-';

            $('#selected_item_preview').show();
            $('#input_product_code').html('<strong>Code:</strong> ' + (item.l_item_code || '-'));
            $('#input_product_name').html('<strong>' + (item.value || '') + '</strong>');
            $('#l_ssno').val(item.l_ss_no || 0);
            $('#purchase_id').val(item.l_purchase_id || 0);
            $('#input_batch').html(' | <strong>Batch:</strong> ' + (item.l_Batch || '-') + ' | <strong>Exp:</strong> ' + expText);
            $('#input_product_mrp').html(' | <strong>MRP:</strong> ₹' + (item.l_mrp || '-') + ' | <strong>P. Rate:</strong> ₹' + purRate.toFixed(2));
            $('#stock_product_qty').html(' | <strong>Avail Stock:</strong> ' + (item.l_c_qty || 0));

            // Supplier display
            if (item.supplier_name && item.supplier_name !== '-') {
                var itemSuppId = parseInt(item.supplier_id || '0', 10);
                var isSameSupp = (currentSuppId > 0 && itemSuppId > 0 && currentSuppId === itemSuppId);
                var badgeClass = isSameSupp ? 'badge bg-success-subtle text-success border border-success-subtle' : 'badge bg-secondary-subtle text-dark border';
                $('#input_supplier_info').html(' | <strong>Supplier:</strong> <span class="' + badgeClass + '"><i class="fa fa-truck"></i> ' + item.supplier_name + (isSameSupp ? ' (Same)' : '') + '</span>');
            } else {
                $('#input_supplier_info').html('');
            }

            // Expiry status badge
            if (item.is_expired == 1 || parseFloat(item.expiry_alert || '999') <= 0) {
                $('#input_expiry_status').html('<span class="badge bg-danger text-white"><i class="fa fa-ban"></i> Expired</span>');
            } else if (parseFloat(item.expiry_alert || '999') <= 3) {
                $('#input_expiry_status').html('<span class="badge bg-warning text-dark"><i class="fa fa-clock-o"></i> Near Expiry</span>');
            } else {
                $('#input_expiry_status').html('');
            }

            $('#input_product_unit_rate').val(purRate > 0 ? purRate.toFixed(2) : (item.l_unit_rate || ''));
            $('#hid_c_qty').val(item.l_c_qty || 0);
            $('#input_product_qty').val(item.l_c_qty || '');
            $('#input_batch_no').val(item.l_Batch || '');
            $('#input_expiry_dt').val(item.l_Expiry || '');

            setTimeout(function() {
                $('#input_product_qty').focus().select();
            }, 60);
        }
    }).autocomplete("instance")._renderItem = function (ul, item) {
        var isExpired = (parseInt(item.is_expired || '0', 10) === 1 || parseFloat(item.expiry_alert || '999') <= 0);
        var isNearExpiry = (!isExpired && parseFloat(item.expiry_alert || '999') <= 3);

        var bulb = (item.l_new_stock || '0') > 0 ? '💡 ' : '';
        var label = item.label || '';

        // Supplier badge
        var itemSuppId = parseInt(item.supplier_id || '0', 10);
        var suppHtml = '';
        if (item.supplier_name && item.supplier_name !== '-') {
            if (currentSuppId > 0 && itemSuppId > 0 && currentSuppId === itemSuppId) {
                suppHtml = '<span class="badge bg-success-subtle text-success border border-success-subtle me-1" style="font-size:0.75rem;"><i class="fa fa-check-circle"></i> Supplier: ' + item.supplier_name + ' (Same)</span>';
            } else {
                suppHtml = '<span class="badge bg-secondary-subtle text-dark border border-secondary-subtle me-1" style="font-size:0.75rem;"><i class="fa fa-truck"></i> Supplier: ' + item.supplier_name + '</span>';
            }
        }

        // Expiry text/badge
        var expDisplay = item.l_expiry_display || item.l_Expiry || '-';
        var expHtml = '';
        if (isExpired) {
            var expText = item.desc || '<span style="color: red; font-weight: bold;">Expired</span>';
            expHtml = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold" style="font-size:0.75rem;"><i class="fa fa-exclamation-triangle"></i> ' + expText + ' [Exp: ' + expDisplay + '] &bull; Returnable</span>';
        } else if (isNearExpiry) {
            expHtml = '<span class="badge bg-warning-subtle text-dark border border-warning-subtle fw-semibold" style="font-size:0.75rem;"><i class="fa fa-clock-o"></i> ' + (item.desc || ('Expiring in ' + item.expiry_alert + ' Mo')) + ' [Exp: ' + expDisplay + ']</span>';
        } else {
            expHtml = '<span class="text-muted small" style="font-size:0.75rem;"><i class="fa fa-calendar"></i> Exp: ' + expDisplay + '</span>';
        }

        // List item - ALWAYS SELECTABLE (no ui-state-disabled)
        var $li = $('<li>')
            .addClass('border-bottom py-1')
            .css({
                'cursor': 'pointer',
                'border-left': isExpired ? '4px solid #dc3545' : (isNearExpiry ? '4px solid #ffc107' : '4px solid transparent'),
                'background-color': isExpired ? '#fff5f5' : (isNearExpiry ? '#fffdf0' : '')
            });

        var html = '<div class="px-2 py-1">' +
            '<div class="fw-semibold text-dark" style="font-size:0.875rem;">' + bulb + label + '</div>' +
            '<div class="d-flex flex-wrap align-items-center gap-1 mt-1">' +
                suppHtml +
                expHtml +
            '</div>' +
        '</div>';

        return $li.append(html).appendTo(ul);
    };

    $('#input_batch_no').autocomplete({
        source: function (request, response) {
            var itemCode = $('#l_ssno').val() || 0;
            if (parseInt(itemCode, 10) <= 0) {
                response([]);
                return;
            }
            $.getJSON('<?= base_url('Medical_backpanel/get_batch') ?>/' + itemCode, request, function (data) {
                response(data || []);
            });
        },
        minLength: 1,
        autofocus: true
    });
})();
</script>

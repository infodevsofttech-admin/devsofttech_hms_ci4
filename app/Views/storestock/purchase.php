<?= $this->include('storestock/_niceadmin_theme') ?>
<div class="storestock-ui">
<section class="content-header">
    <h1>
        Purchase
        <small>Store</small>
    </h1>
</section>

<section class="content">
    <div class="module-hero">
        <h4>Store Purchase Control Center</h4>
        <p>Track supplier invoices, open edits in a persistent sub panel, and keep the invoice list visible while you work.</p>
    </div>

    <div class="box">
        <div class="box-header d-flex justify-content-between align-items-center">
            <h3 class="box-title mb-0">Purchase Invoices</h3>
            <button type="button" class="btn btn-warning btn-sm" id="btnNewPurchase">New Purchase / Challan Invoice</button>
        </div>
        <div class="box-body">
            <form id="purchase-search-form" class="row g-2 align-items-center" method="post" action="javascript:void(0)">
                <?= csrf_field() ?>
                <div class="col-md-6">
                    <input class="form-control" type="text" id="txtsearch" name="txtsearch" placeholder="Search by invoice no. or supplier">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-info">Search Purchase Invoice</button>
                </div>
                <div class="col-auto">
                    <button type="button" id="btnResetPurchase" class="btn btn-outline-secondary">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="box">
        <div class="box-header">
            <h3 class="box-title">Recent Purchase Invoices</h3>
        </div>
        <div class="box-body" id="purchase-main-panel">
            <?= $this->include('storestock/purchase_supp_list') ?>
        </div>
    </div>

    <div id="purchase-sub-main" style="display:none;" class="mt-3">
        <div id="searchresult"></div>
    </div>
</section>
</div>

<script>
(function () {
    window.openStorestockPurchaseSubView = function (url, title) {
        var $root = $('#purchase-sub-main');
        var $listPanel = $('#purchase-main-panel');

        if ($root.length) {
            $listPanel.hide();
            $root.show();
            load_form_div(url, 'searchresult', title || 'Purchase : Store');
            return;
        }

        load_form_div(url, 'maindiv', title || 'Purchase : Store');
    };

    window.closeStorestockPurchaseSubView = function () {
        var $root = $('#purchase-sub-main');
        var $listPanel = $('#purchase-main-panel');

        if ($root.length) {
            $('#searchresult').empty();
            $root.hide();
            $listPanel.show();
        }
    };

    $('#btnNewPurchase').off('click').on('click', function () {
        openStorestockPurchaseSubView('<?= base_url('Storestock/PurchaseNew') ?>', 'New Purchase / Challan Invoice : Store');
    });

    $('#btnResetPurchase').off('click').on('click', function () {
        $('#txtsearch').val('');
        $('#purchase-search-form').trigger('submit');
    });

    $('#purchase-search-form').off('submit').on('submit', function (event) {
        event.preventDefault();
        $.post('<?= base_url('Storestock/PurchaseInvoice') ?>', $(this).serialize(), function (html) {
            $('#purchase-main-panel').html(html || '');
        }).fail(function () {
            $('#purchase-main-panel').html('<div class="alert alert-danger mb-0">Failed to load purchase list.</div>');
        });
    });
})();
</script>
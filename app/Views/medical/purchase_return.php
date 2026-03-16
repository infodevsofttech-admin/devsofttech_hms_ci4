<section class="section">
    <form id="purchase-return-search-form" class="row g-2 mb-3" method="post" action="javascript:void(0)">
        <?= csrf_field() ?>
        <div class="col-md-6">
            <input class="form-control" type="text" id="txtsearch" name="txtsearch" placeholder="Search Purchase Return">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-info">Search Purchase Return</button>
        </div>
        <div class="col-auto">
            <button onclick="load_form_div('<?= base_url('Medical_backpanel/PurchaseReturnNew') ?>','searchresult','Purchase Return : New Invoice');" type="button" class="btn btn-warning">New Purchase Return Invoice</button>
        </div>
    </form>

    <div id="searchresult"></div>
</section>

<script>
(function () {
    $('#purchase-return-search-form').off('submit').on('submit', function (event) {
        event.preventDefault();
        $.post('<?= base_url('Medical_backpanel/PurchaseReturnInvoice') ?>', $(this).serialize(), function (html) {
            $('#searchresult').html(html || '');
        });
    });
})();
</script>

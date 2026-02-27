<section class="section">
    <form id="purchase-search-form" class="row g-2 mb-3" method="post" action="javascript:void(0)">
        <?= csrf_field() ?>
        <div class="col-md-6">
            <input class="form-control" type="text" id="txtsearch" name="txtsearch" placeholder="Search Purchase Invoice">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-info">Search Purchase Invoice</button>
        </div>
        <div class="col-auto">
            <button onclick="load_form_div('<?= base_url('Medical/PurchaseNew') ?>','searchresult','Purchase : New Invoice :Pharmacy');" type="button" class="btn btn-warning">New Purchase/Challan Invoice</button>
        </div>
    </form>

    <div id="searchresult"></div>
</section>

<script>
(function () {
    $('#purchase-search-form').off('submit').on('submit', function (event) {
        event.preventDefault();
        $.post('<?= base_url('Medical/PurchaseInvoice') ?>', $(this).serialize(), function (html) {
            $('#searchresult').html(html || '');
        });
    });
})();
</script>

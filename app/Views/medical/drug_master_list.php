<section class="section">
    <form id="drug-master-search-form" class="row g-2 mb-3" method="post" action="javascript:void(0)">
        <?= csrf_field() ?>
        <div class="col-md-6">
            <input class="form-control" type="text" id="txtsearch" name="txtsearch" placeholder="Search by Product / Generic / ID">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-info">Search Product</button>
        </div>
        <div class="col-auto">
            <button onclick="load_form_div('<?= base_url('Product_master/Product_edit/0') ?>','searchresult','Drug Master : New Product :Pharmacy');" type="button" class="btn btn-warning">Add New Product</button>
        </div>
    </form>

    <div id="searchresult"></div>
</section>

<script>
(function () {
    $('#drug-master-search-form').off('submit').on('submit', function (event) {
        event.preventDefault();
        $.post('<?= base_url('product_master/Product_search') ?>', $(this).serialize(), function (html) {
            $('#searchresult').html(html || '');
        });
    });
})();
</script>

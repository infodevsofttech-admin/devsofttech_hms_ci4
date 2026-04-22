<?= $this->include('storestock/_niceadmin_theme') ?>
<div class="storestock-ui">
<section class="content-header">
    <h1>Store Stock <small>Search</small></h1>
</section>
<section class="content">
    <div class="module-hero">
        <h4>Stock Visibility & Reorder Controls</h4>
        <p>Filter by schedule, quickly export, and inspect product movement batch-wise.</p>
    </div>

    <div class="box">
        <div class="box-body">
            <form role="form" class="form-stock-search" method="post" action="/Storestock/store_Stock_result">
                <?= csrf_field() ?>
                <div class="search-bar">
                    <div>
                        <label style="display:block; margin-bottom:.3rem;">Stock Mode</label>
                        <label style="margin:0; font-weight:600; color:#012970;">
                            <input id="chk_reorder" name="chk_reorder" type="checkbox"> ReOrder List
                        </label>
                    </div>

                    <div>
                        <label style="display:block; margin-bottom:.3rem;">Drug Schedule</label>
                        <select class="form-control select2" id="schedule_id" name="schedule_id[]"
                                multiple="multiple" data-placeholder="Select a Schedule, Blank for All">
                            <option value="1">Schedule H</option>
                            <option value="2">Schedule H1</option>
                            <option value="3">Schedule X</option>
                            <option value="4">Schedule G</option>
                            <option value="5">Narcotic</option>
                            <option value="6">High Risk</option>
                        </select>
                    </div>

                    <div>
                        <label style="display:block; margin-bottom:.3rem;">Search Keyword</label>
                        <div class="input-group input-group-sm">
                            <input class="form-control" type="text" id="txtsearch" name="txtsearch"
                                   placeholder="Item Name / Supplier Name">
                            <span class="input-group-btn">
                                <button type="submit" class="btn btn-primary btn-flat">Search</button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="toolbar-row" style="margin-top:.8rem; margin-bottom:0;">
                    <div class="left"></div>
                    <div class="right">
                        <button type="button" id="btn_excel" class="btn btn-info btn-sm">
                            <i class="fa fa-file-excel-o"></i> Excel Export
                        </button>
                        <button type="button" id="btn_excel_3" class="btn btn-default btn-sm">
                            <i class="fa fa-table"></i> Batch-wise Excel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="box">
        <div class="box-header">
            <h3 class="box-title">Search Result</h3>
        </div>
        <div class="box-body">
            <div class="searchresult" id="searchresult"></div>
        </div>
    </div>
</section>

<script>
$(document).ready(function () {

    $('.select2').select2();

    $('form.form-stock-search').on('submit', function (e) {
        e.preventDefault();
        $.post('/Storestock/store_Stock_result', $(this).serialize(), function (data) {
            $('#searchresult').html(data);
        });
    });

    $('#btn_excel').click(function () {
        var reorder   = $('#chk_reorder').is(':checked') ? '1' : '0';
        var itemName  = $('#txtsearch').val() || '-';
        var schIds    = $('#schedule_id').val();
        var schStr    = (schIds && schIds.length > 0) ? schIds.join('S') : '0';
        window.open('/Storestock/Stock_result_excel/' + reorder + '/' + encodeURIComponent(itemName) + '/' + schStr, '_blank');
    });

    $('#btn_excel_3').click(function () {
        var reorder  = $('#chk_reorder').is(':checked') ? '1' : '0';
        var itemName = $('#txtsearch').val() || '-';
        window.open('/Storestock/Stock_result_excel_3/0/0/' + encodeURIComponent(itemName) + '/' + reorder, '_blank');
    });
});
</script>
</div>

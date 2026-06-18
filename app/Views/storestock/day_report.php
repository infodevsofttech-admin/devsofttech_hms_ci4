<?= $this->include('storestock/_niceadmin_theme') ?>
<div class="storestock-ui">
    <section class="content">
    <div class="module-shell-head">
        <div class="module-shell-title">
            <span class="main">Store Stock</span>
            <span class="sub">Day Report</span>
        </div>
        <div class="module-nav-tabs">
            <a class="tab" href="javascript:load_form('<?= base_url('Storestock') ?>','Hospital Stock');">
                <i class="fa fa-home"></i> Dashboard
            </a>
            <a class="tab " href="javascript:load_form('<?= base_url('Storestock/Indent_List') ?>','Store : Indent');">
                <i class="fa fa-shopping-cart"></i> Indent
            </a>
            <a class="tab active" href="javascript:load_form('<?= base_url('Storestock/Report_2') ?>','Day Report :Store');">
                <i class="fa fa-line-chart"></i> Day Report
            </a>
            <a class="tab" href="javascript:load_form('<?= base_url('Storestock/store_stock') ?>','Store Stock : Store');">
                <i class="fa fa-barcode"></i> Store Stock
            </a>
            <a class="tab" href="javascript:load_form('<?= base_url('Storestock/main_store') ?>','Store Main : Store');">
                <i class="fa fa-desktop"></i> Store Main
            </a>
        </div>
    </div>
    <div class="workspace-shell">
        <div class="col-md-12">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title">Day Report - Store</h3>
            </div>
            <div class="box-body">
                <form class="form-day-report" method="post">
                    <?= csrf_field() ?>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>From Date</label>
                                <input type="date" class="form-control input-sm" id="date_from" name="date_from"
                                    value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>To Date</label>
                                <input type="date" class="form-control input-sm" id="date_to" name="date_to"
                                    value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group" style="margin-top:24px;">
                                <button type="submit" class="btn btn-info btn-sm">Generate Report</button>
                            </div>
                        </div>
                    </div>
                </form>
                <div id="day_report_result" class="row"></div>
            </div>
        </div>
    </div>
    </section>
</div>
<script>
$(document).ready(function () {
    $('form.form-day-report').on('submit', function (e) {
        e.preventDefault();
        $('#day_report_result').html('<p class="text-muted"><i class="fa fa-spinner fa-spin"></i> Loading...</p>');
        // Day report endpoint to be implemented
    });
});
</script>
</div>

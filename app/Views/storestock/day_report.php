<?= $this->include('storestock/_niceadmin_theme') ?>
<div class="storestock-ui">
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

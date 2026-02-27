<link href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" rel="stylesheet">

<style>
    .ipd-hero {
        background: linear-gradient(120deg, #f4f7fb 0%, #eef3ff 100%);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 18px;
        margin-bottom: 16px;
    }
    .ipd-hero h3 {
        font-family: "Poppins", "Nunito", sans-serif;
        font-size: 20px;
        margin: 0;
        color: #0f172a;
    }
    .ipd-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
    }
    .ipd-filters {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 16px;
    }
</style>

<div class="col-md-12">

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h3 class="card-title mb-0">Cash Balance</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-warning d-none" id="datatable-missing">
                DataTable plugin is not loaded. Please include jQuery DataTables to enable filtering.
            </div>
            <div class="row g-3 align-items-end ipd-filters">
                <div class="col-md-4">
                    <label class="form-label">Date Range</label>
                    <div id="reportrange" class="form-control" style="cursor: pointer;">
                        <i class="bi bi-calendar"></i>&nbsp;
                        <span></span> <b class="caret"></b>
                    </div>
                    <input type="hidden" name="ipd_date_range" id="ipd_date_range" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-primary" id="showreport1">Show</button>
                        <button type="button" class="btn btn-outline-secondary" id="showreportexport">Export</button>
                    </div>
                </div>
                <div class="col-md-4 text-muted small">
                    Shows pending balances for direct customers within the date range.
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-12">
                    <div id="show_report"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    $(document).ready(function() {
        var start = moment();
        var end = moment();

        function cb(startDate, endDate) {
            $('#reportrange span').html(startDate.format('D-MM-YYYY') + ' - ' + endDate.format('D-MM-YYYY'));
            $('#ipd_date_range').val(startDate.format('YYYY-MM-DD') + 'S' + endDate.format('YYYY-MM-DD'));
        }

        $('#reportrange').daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, cb);

        cb(start, end);

        $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
            var dateFirst = picker.startDate.format('YYYY-MM-DD');
            var dateSecond = picker.endDate.format('YYYY-MM-DD');
            $('#ipd_date_range').val(dateFirst + 'S' + dateSecond);
        });

        $('#showreport1').click(function() {
            var range = $('#ipd_date_range').val();
            var url = '<?= base_url('billing/ipd/cash-balance/report') ?>?range=' + encodeURIComponent(range);
            load_form_div(url, 'show_report');
        });

        $('#showreportexport').click(function() {
            var range = $('#ipd_date_range').val();
            var url = '<?= base_url('billing/ipd/cash-balance/export') ?>?range=' + encodeURIComponent(range);
            window.open(url, '_blank');
        });
    });
</script>

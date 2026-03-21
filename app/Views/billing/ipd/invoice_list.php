<link href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" rel="stylesheet">

<style>
    
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
            <h3 class="card-title mb-0">IPD Invoice</h3>
        </div>
        <div class="card-body">
            
            <div class="alert alert-warning d-none" id="datatable-missing">
                DataTable plugin is not loaded. Please include jQuery DataTables to enable filtering.
            </div>
            <div class="row g-2 align-items-center ipd-filters">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">
                            <input type="checkbox" id="chk_date" name="chk_date">
                        </span>
                        <div id="reportrange" class="form-control" style="cursor: pointer;">
                            <i class="bi bi-calendar"></i>&nbsp;
                            <span></span> <b class="caret"></b>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="ipd_admit_type" name="ipd_admit_type">
                        <option value="-1">All</option>
                        <option value="0">Admit</option>
                        <option value="1">Discharge</option>
                    </select>
                </div>
                <div class="col-md-5 text-muted small">
                    Use filters to narrow results. Search per column below.
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle TableData" id="ipd-invoice-grid" width="100%">
                    <thead>
                        <tr>
                            <th>IPD Code</th>
                            <th>Patient Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Claim No</th>
                            <th>Doctor</th>
                            <th>Status</th>
                            <th>Registration</th>
                            <th>Discharge</th>
                            <th>Dis. Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <thead>
                        <tr>
                            <td><input class="form-control" type="text" data-column="0"></td>
                            <td><input class="form-control" type="text" data-column="1"></td>
                            <td><input class="form-control" type="text" data-column="2"></td>
                            <td><input class="form-control" type="text" data-column="3"></td>
                            <td><input class="form-control" type="text" data-column="4"></td>
                            <td><input class="form-control" type="text" data-column="5"></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="mt-2" id="show_msg"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    $(document).ready(function() {
        if (!$.fn || !$.fn.DataTable) {
            $('#datatable-missing').removeClass('d-none');
            return;
        }

        var dateFilterReady = (typeof window.moment === 'function') && $.fn && (typeof $.fn.daterangepicker === 'function');
        var start = null;
        var end = null;

        function fmtYmd(dateValue) {
            if (!dateValue) {
                return '';
            }
            if (typeof dateValue.format === 'function') {
                return dateValue.format('YYYY-MM-DD');
            }
            var txt = String(dateValue || '').trim();
            if (/^\d{4}-\d{2}-\d{2}/.test(txt)) {
                return txt.substring(0, 10);
            }

            return '';
        }

        if (dateFilterReady) {
            start = moment();
            end = moment();

            function cb(startDate, endDate) {
                $('#reportrange span').html(startDate.format('MMMM D, YYYY') + ' - ' + endDate.format('MMMM D, YYYY'));
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
        } else {
            $('#chk_date').prop('checked', false).prop('disabled', true);
            $('#reportrange').addClass('text-muted');
            $('#reportrange span').text('Date filter unavailable (moment blocked by browser/privacy settings).');
        }

        var dataTable = $('#ipd-invoice-grid').DataTable({
            order: [[0, 'desc']],
            processing: true,
            serverSide: true,
            columnDefs: [
                { targets: [10], orderable: false, searchable: false }
            ],
            ajax: {
                url: "<?= base_url('billing/ipd/list') ?>",
                type: 'post',
                data: {
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                error: function() {
                    $('.employee-grid-error').html('No data found in the server');
                    $('#show_msg').html('<tbody class="employee-grid-error"><tr><th colspan="3">No data found in the server</th></tr></tbody>');
                }
            }
        });

        $('#ipd-invoice-grid_filter').css('display', 'none');

        $('#chk_date').on('click', function() {
            if (!dateFilterReady) {
                return;
            }
            if (this.checked) {
                var choAdmitType = $('#ipd_admit_type').val();
                var dateFirst = fmtYmd(start);
                var dateSecond = fmtYmd(end);
                dataTable.columns(7).search(dateFirst + '/' + dateSecond + '/' + choAdmitType).draw();
            } else {
                dataTable.columns(7).search('').draw();
            }
        });

        $('#ipd_admit_type').change(function() {
            if (!dateFilterReady) {
                return;
            }
            if ($('#chk_date').is(':checked')) {
                var choAdmitType = $('#ipd_admit_type').val();
                var dateFirst = fmtYmd(start);
                var dateSecond = fmtYmd(end);
                dataTable.columns(7).search(dateFirst + '/' + dateSecond + '/' + choAdmitType).draw();
            }
        });

        $('input[type=text]').on('input', function() {
            var i = $(this).attr('data-column');
            var v = $(this).val();
            dataTable.columns(i).search(v).draw();
        });

        $('#reportrange').on('apply.daterangepicker', function(ev, picker) {
            if (!dateFilterReady) {
                return;
            }
            var dateFirst = picker.startDate.format('YYYY-MM-DD');
            var dateSecond = picker.endDate.format('YYYY-MM-DD');
            start = picker.startDate;
            end = picker.endDate;

            if ($('#chk_date').is(':checked')) {
                var choAdmitType = $('#ipd_admit_type').val();
                dataTable.columns(7).search(dateFirst + '/' + dateSecond + '/' + choAdmitType).draw();
            }
        });

        <?php if (! empty($ipdCode)) : ?>
        var ipdCodeFilter = <?= json_encode((string) $ipdCode) ?>;
        $('input[data-column="0"]').val(ipdCodeFilter);
        dataTable.columns(0).search(ipdCodeFilter).draw();
        <?php endif; ?>
    });
</script>

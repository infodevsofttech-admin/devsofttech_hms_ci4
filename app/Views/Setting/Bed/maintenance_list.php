<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Bed Maintenance Log</h3>
        <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/bed-management') ?>','maindiv','Bed Management');">
            <i class="bi bi-arrow-left"></i>
            Back
        </button>
    </div>
    <div class="card-body">
        <div id="maintenanceAlert" class="alert alert-danger d-none"></div>
        <form id="maintenanceForm">
            <?= csrf_field() ?>
            <input type="hidden" id="maintenance_id" name="id" value="0">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Bed</label>
                    <select class="form-select" id="bed_id" name="bed_id" required>
                        <option value="">Select bed</option>
                        <?php foreach (($beds ?? []) as $bed) {
                            $label = trim(($bed->bed_code ?? '') . ' ' . ($bed->bed_number ?? ''));
                        ?>
                            <option value="<?= esc($bed->id ?? 0) ?>"><?= esc($label) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select class="form-select" id="maintenance_type" name="maintenance_type" required>
                        <option value="cleaning">Cleaning</option>
                        <option value="repair">Repair</option>
                        <option value="inspection">Inspection</option>
                        <option value="replacement">Replacement</option>
                        <option value="sanitization">Sanitization</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Scheduled Date</label>
                    <input class="form-control" id="scheduled_date" name="scheduled_date" type="date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Completed Date</label>
                    <input class="form-control" id="completed_date" name="completed_date" type="datetime-local">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Next Maintenance</label>
                    <input class="form-control" id="next_maintenance_date" name="next_maintenance_date" type="date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cost</label>
                    <input class="form-control" id="cost" name="cost" type="number" step="0.01">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Performed By</label>
                    <input class="form-control" id="performed_by" name="performed_by">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Issue</label>
                    <input class="form-control" id="issue_description" name="issue_description">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Action Taken</label>
                    <input class="form-control" id="action_taken" name="action_taken">
                </div>
                <div class="col-md-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Save Log</button>
                    <button class="btn btn-light" type="button" id="resetMaintenance">Clear</button>
                </div>
            </div>
        </form>
        <hr/>
        <div class="row g-2 mb-2">
            <div class="col-md-3">
                <select class="form-select" id="filterMaintWard">
                    <option value="">All Wards</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filterMaintType">
                    <option value="">All Types</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filterMaintStatus">
                    <option value="">All Status</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="maintenanceTable">
                <thead class="table-light">
                    <tr>
                        <th>Bed</th>
                        <th>Ward</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Scheduled</th>
                        <th>Completed</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($logs ?? []) as $row) {
                        $payload = json_encode($row);
                    ?>
                        <tr>
                            <td><?= esc(($row->bed_code ?? '') . ' ' . ($row->bed_number ?? '')) ?></td>
                            <td><?= esc($row->ward_name ?? '') ?></td>
                            <td><?= esc($row->maintenance_type ?? '') ?></td>
                            <td><?= esc($row->status ?? '') ?></td>
                            <td><?= esc($row->scheduled_date ?? '') ?></td>
                            <td><?= esc($row->completed_date ?? '') ?></td>
                            <td>
                                <button class="btn btn-outline-secondary btn-sm edit-maintenance" data-item="<?= esc($payload, 'attr') ?>">Edit</button>
                                <button class="btn btn-outline-danger btn-sm delete-maintenance" data-id="<?= esc($row->id ?? 0) ?>">Delete</button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    (function() {
        var form = document.getElementById('maintenanceForm');
        var resetButton = document.getElementById('resetMaintenance');
        var alertBox = document.getElementById('maintenanceAlert');

        if (!form || !window.jQuery) {
            return;
        }

        function showAlert(message) {
            alertBox.textContent = message;
            alertBox.classList.remove('d-none');
        }

        function clearAlert() {
            alertBox.textContent = '';
            alertBox.classList.add('d-none');
        }

        function clearForm() {
            form.reset();
            $('#maintenance_id').val(0);
            clearAlert();
        }

        resetButton.addEventListener('click', clearForm);

        $(form).on('submit', function(event) {
            event.preventDefault();
            clearAlert();
            $.post('<?= base_url('setting/admin/bed-maintenance/save') ?>', $(form).serialize())
                .done(function(resp) {
                    if (resp && resp.error_text) {
                        showAlert(resp.error_text);
                        return;
                    }
                    load_form_div('<?= base_url('setting/admin/bed-maintenance') ?>', 'maindiv', 'Bed Maintenance');
                })
                .fail(function() {
                    showAlert('Request failed.');
                });
        });

        $('.edit-maintenance').on('click', function() {
            var item = $(this).data('item');
            if (!item) return;
            $('#maintenance_id').val(item.id || 0);
            $('#bed_id').val(item.bed_id || '');
            $('#maintenance_type').val(item.maintenance_type || 'cleaning');
            $('#status').val(item.status || 'pending');
            $('#scheduled_date').val(item.scheduled_date || '');
            $('#completed_date').val(item.completed_date ? String(item.completed_date).replace(' ', 'T') : '');
            $('#next_maintenance_date').val(item.next_maintenance_date || '');
            $('#cost').val(item.cost || '');
            $('#performed_by').val(item.performed_by || '');
            $('#issue_description').val(item.issue_description || '');
            $('#action_taken').val(item.action_taken || '');
        });

        $('.delete-maintenance').on('click', function() {
            if (!confirm('Delete this log?')) {
                return;
            }
            clearAlert();
            $.post('<?= base_url('setting/admin/bed-maintenance/delete') ?>', {
                id: $(this).data('id'),
                '<?= csrf_token() ?>': $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>'
            }).done(function(resp) {
                if (resp && resp.error_text) {
                    showAlert(resp.error_text);
                    return;
                }
                load_form_div('<?= base_url('setting/admin/bed-maintenance') ?>', 'maindiv', 'Bed Maintenance');
            });
        });

        if (window.jQuery && $.fn && $.fn.DataTable) {
            var table = $('#maintenanceTable').DataTable();

            function initFilter(selectId, columnIndex) {
                var select = $(selectId);
                if (!select.length) return;
                var column = table.column(columnIndex);
                var values = column.data().unique().sort();
                values.each(function(d) {
                    if (d !== null && d !== '') {
                        select.append('<option value="' + d + '">' + d + '</option>');
                    }
                });
                select.on('change', function() {
                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                    column.search(val ? '^' + val + '$' : '', true, false).draw();
                });
            }

            initFilter('#filterMaintWard', 1);
            initFilter('#filterMaintType', 2);
            initFilter('#filterMaintStatus', 3);
        }
    })();
</script>

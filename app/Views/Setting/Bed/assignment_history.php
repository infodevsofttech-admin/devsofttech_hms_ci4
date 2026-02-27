<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Bed Assignment History</h3>
        <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/bed-management') ?>','maindiv','Bed Management');">
            <i class="bi bi-arrow-left"></i>
            Back
        </button>
    </div>
    <div class="card-body">
        <div id="assignmentAlert" class="alert alert-danger d-none"></div>
        <form id="assignmentForm">
            <?= csrf_field() ?>
            <input type="hidden" id="assignment_id" name="id" value="0">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Bed</label>
                    <select class="form-select" id="assignment_bed_id" name="bed_id" required>
                        <option value="">Select bed</option>
                        <?php foreach (($beds ?? []) as $bed) {
                            $label = trim(($bed->bed_code ?? '') . ' ' . ($bed->bed_number ?? ''));
                        ?>
                            <option value="<?= esc($bed->id ?? 0) ?>"><?= esc($label) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ward</label>
                    <select class="form-select" id="assignment_ward_id" name="ward_id" required>
                        <option value="">Select ward</option>
                        <?php foreach (($wards ?? []) as $ward) { ?>
                            <option value="<?= esc($ward->id ?? 0) ?>"><?= esc($ward->ward_name ?? '') ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Assignment Type</label>
                    <select class="form-select" id="assignment_type" name="assignment_type" required>
                        <option value="admission">Admission</option>
                        <option value="transfer">Transfer</option>
                        <option value="discharge">Discharge</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Assigned Date</label>
                    <input class="form-control" id="assigned_date" name="assigned_date" type="datetime-local" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Released Date</label>
                    <input class="form-control" id="released_date" name="released_date" type="datetime-local">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Discharged Date</label>
                    <input class="form-control" id="discharged_date" name="discharged_date" type="datetime-local">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total Days</label>
                    <input class="form-control" id="total_days" name="total_days" type="number" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Release Reason</label>
                    <input class="form-control" id="release_reason" name="release_reason">
                </div>
                <div class="col-md-3">
                    <label class="form-label">IPD ID</label>
                    <input class="form-control" id="ipd_id" name="ipd_id" type="number" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Assigned By</label>
                    <input class="form-control" id="assigned_by" name="assigned_by" type="number" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Transfer From Bed</label>
                    <select class="form-select" id="transfer_from_bed_id" name="transfer_from_bed_id">
                        <option value="">None</option>
                        <?php foreach (($beds ?? []) as $bed) {
                            $label = trim(($bed->bed_code ?? '') . ' ' . ($bed->bed_number ?? ''));
                        ?>
                            <option value="<?= esc($bed->id ?? 0) ?>"><?= esc($label) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Transfer Reason</label>
                    <input class="form-control" id="transfer_reason" name="transfer_reason">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Remarks</label>
                    <input class="form-control" id="assignment_remarks" name="remarks">
                </div>
                <div class="col-md-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Save Assignment</button>
                    <button class="btn btn-light" type="button" id="resetAssignment">Clear</button>
                </div>
            </div>
        </form>
        <hr/>
        <div class="row g-2 mb-2">
            <div class="col-md-3">
                <select class="form-select" id="filterAssignWard">
                    <option value="">All Wards</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filterAssignType">
                    <option value="">All Types</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="assignmentTable">
                <thead class="table-light">
                    <tr>
                        <th>Bed</th>
                        <th>Ward</th>
                        <th>Type</th>
                        <th>Assigned</th>
                        <th>Released</th>
                        <th>Total Days</th>
                        <th>Reason</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($assignments ?? []) as $row) {
                        $payload = json_encode($row);
                    ?>
                        <tr>
                            <td><?= esc(($row->bed_code ?? '') . ' ' . ($row->bed_number ?? '')) ?></td>
                            <td><?= esc($row->ward_name ?? '') ?></td>
                            <td><?= esc($row->assignment_type ?? '') ?></td>
                            <td><?= esc($row->assigned_date ?? '') ?></td>
                            <td><?= esc($row->released_date ?? $row->discharged_date ?? '') ?></td>
                            <td><?= esc($row->total_days ?? '') ?></td>
                            <td><?= esc($row->release_reason ?? $row->transfer_reason ?? '') ?></td>
                            <td>
                                <button class="btn btn-outline-secondary btn-sm edit-assignment" data-item="<?= esc($payload, 'attr') ?>">Edit</button>
                                <button class="btn btn-outline-danger btn-sm delete-assignment" data-id="<?= esc($row->id ?? 0) ?>">Delete</button>
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
        var form = document.getElementById('assignmentForm');
        var resetButton = document.getElementById('resetAssignment');
        var alertBox = document.getElementById('assignmentAlert');

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
            $('#assignment_id').val(0);
            clearAlert();
        }

        resetButton.addEventListener('click', clearForm);

        $(form).on('submit', function(event) {
            event.preventDefault();
            clearAlert();
            $.post('<?= base_url('setting/admin/bed-assignments/save') ?>', $(form).serialize())
                .done(function(resp) {
                    if (resp && resp.error_text) {
                        showAlert(resp.error_text);
                        return;
                    }
                    load_form_div('<?= base_url('setting/admin/bed-assignments') ?>', 'maindiv', 'Bed Assignment History');
                })
                .fail(function() {
                    showAlert('Request failed.');
                });
        });

        $('.edit-assignment').on('click', function() {
            var item = $(this).data('item');
            if (!item) return;
            $('#assignment_id').val(item.id || 0);
            $('#assignment_bed_id').val(item.bed_id || '');
            $('#assignment_ward_id').val(item.ward_id || '');
            $('#assignment_type').val(item.assignment_type || 'admission');
            $('#assigned_date').val(item.assigned_date ? String(item.assigned_date).replace(' ', 'T') : '');
            $('#released_date').val(item.released_date ? String(item.released_date).replace(' ', 'T') : '');
            $('#discharged_date').val(item.discharged_date ? String(item.discharged_date).replace(' ', 'T') : '');
            $('#total_days').val(item.total_days || 0);
            $('#release_reason').val(item.release_reason || '');
            $('#ipd_id').val(item.ipd_id || '');
            $('#assigned_by').val(item.assigned_by || '');
            $('#transfer_from_bed_id').val(item.transfer_from_bed_id || '');
            $('#transfer_reason').val(item.transfer_reason || '');
            $('#assignment_remarks').val(item.remarks || '');
        });

        $('.delete-assignment').on('click', function() {
            if (!confirm('Delete this assignment?')) {
                return;
            }
            clearAlert();
            $.post('<?= base_url('setting/admin/bed-assignments/delete') ?>', {
                id: $(this).data('id'),
                '<?= csrf_token() ?>': $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>'
            }).done(function(resp) {
                if (resp && resp.error_text) {
                    showAlert(resp.error_text);
                    return;
                }
                load_form_div('<?= base_url('setting/admin/bed-assignments') ?>', 'maindiv', 'Bed Assignment History');
            });
        });

        if (window.jQuery && $.fn && $.fn.DataTable) {
            var table = $('#assignmentTable').DataTable();

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

            initFilter('#filterAssignWard', 1);
            initFilter('#filterAssignType', 2);
        }
    })();
</script>

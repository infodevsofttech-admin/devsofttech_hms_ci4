<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Bed Master</h3>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-primary" type="button" onclick="load_form_div('<?= base_url('setting/admin/bed-status') ?>','maindiv','Bed Status');">
                <i class="bi bi-geo-alt"></i>
                Bed Status
            </button>
            <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/bed-management') ?>','maindiv','Bed Management');">
                <i class="bi bi-arrow-left"></i>
                Back
            </button>
        </div>
    </div>
    <div class="card-body">
        <div id="bedAlert" class="alert alert-danger d-none"></div>
        <form id="bedForm">
            <?= csrf_field() ?>
            <input type="hidden" id="bed_id" name="id" value="0">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Bed Code</label>
                    <input class="form-control" id="bed_code" name="bed_code" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bed Number</label>
                    <input class="form-control" id="bed_number" name="bed_number" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ward</label>
                    <select class="form-select" id="ward_id" name="ward_id" required>
                        <option value="">Select ward</option>
                        <?php foreach (($wards ?? []) as $ward) { ?>
                            <option value="<?= esc($ward->id ?? 0) ?>"><?= esc($ward->ward_name ?? '') ?></option>
                        <?php } ?>
                    </select>
                    <div class="mt-1">
                        <button type="button" class="btn btn-link btn-sm p-0" onclick="load_form_div('<?= base_url('setting/admin/wards') ?>','maindiv','Wards');">Manage Wards</button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" id="bed_category_id" name="bed_category_id" required>
                        <option value="">Select category</option>
                        <?php foreach (($categories ?? []) as $cat) { ?>
                            <option value="<?= esc($cat->id ?? 0) ?>"><?= esc($cat->category_name ?? '') ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bed Status</label>
                    <select class="form-select" id="bed_status" name="bed_status">
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="reserved">Reserved</option>
                        <option value="blocked">Blocked</option>
                        <option value="under_maintenance">Under Maintenance</option>
                        <option value="cleaning">Cleaning</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Position</label>
                    <input class="form-control" id="bed_position" name="bed_position">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Base Charge Override</label>
                    <input class="form-control" id="base_charge_override" name="base_charge_override" type="number" step="0.01">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nursing Charge Override</label>
                    <input class="form-control" id="nursing_charge_override" name="nursing_charge_override" type="number" step="0.01">
                </div>
                <div class="col-md-12">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="has_oxygen" name="has_oxygen">
                        <label class="form-check-label" for="has_oxygen">Oxygen</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="has_suction" name="has_suction">
                        <label class="form-check-label" for="has_suction">Suction</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="has_monitor" name="has_monitor">
                        <label class="form-check-label" for="has_monitor">Monitor</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="has_ventilator" name="has_ventilator">
                        <label class="form-check-label" for="has_ventilator">Ventilator</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="is_isolation_bed" name="is_isolation_bed">
                        <label class="form-check-label" for="is_isolation_bed">Isolation Bed</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Remarks</label>
                    <input class="form-control" id="remarks" name="remarks">
                </div>
                <div class="col-md-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Save Bed</button>
                    <button class="btn btn-light" type="button" id="resetBed">Clear</button>
                </div>
            </div>
        </form>
        <hr/>
        <div class="row g-2 mb-2">
            <div class="col-md-3">
                <select class="form-select" id="filterWard">
                    <option value="">All Wards</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filterBedStatus">
                    <option value="">All Bed Status</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filterActive">
                    <option value="">All Active</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="bedTable">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Number</th>
                        <th>Ward</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Active</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($beds ?? []) as $row) {
                        $payload = json_encode($row);
                    ?>
                        <tr>
                            <td><?= esc($row->bed_code ?? '') ?></td>
                            <td><?= esc($row->bed_number ?? '') ?></td>
                            <td><?= esc($row->ward_name ?? '') ?></td>
                            <td><?= esc($row->category_name ?? '') ?></td>
                            <td><?= esc($row->bed_status ?? '') ?></td>
                            <td><?= esc($row->status ?? '') ?></td>
                            <td>
                                <button class="btn btn-outline-secondary btn-sm edit-bed" data-item="<?= esc($payload, 'attr') ?>">Edit</button>
                                <button class="btn btn-outline-danger btn-sm delete-bed" data-id="<?= esc($row->id ?? 0) ?>">Delete</button>
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
        var form = document.getElementById('bedForm');
        var resetButton = document.getElementById('resetBed');
        var alertBox = document.getElementById('bedAlert');

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
            $('#bed_id').val(0);
            clearAlert();
        }

        resetButton.addEventListener('click', clearForm);

        $(form).on('submit', function(event) {
            event.preventDefault();
            clearAlert();
            $.post('<?= base_url('setting/admin/beds/save') ?>', $(form).serialize())
                .done(function(resp) {
                    if (resp && resp.error_text) {
                        showAlert(resp.error_text);
                        return;
                    }
                    load_form_div('<?= base_url('setting/admin/beds') ?>', 'maindiv', 'Beds');
                })
                .fail(function() {
                    showAlert('Request failed.');
                });
        });

        $('.edit-bed').on('click', function() {
            var item = $(this).data('item');
            if (!item) return;
            $('#bed_id').val(item.id || 0);
            $('#bed_code').val(item.bed_code || '');
            $('#bed_number').val(item.bed_number || '');
            $('#ward_id').val(item.ward_id || '');
            $('#bed_category_id').val(item.bed_category_id || '');
            $('#bed_status').val(item.bed_status || 'available');
            $('#bed_position').val(item.bed_position || '');
            $('#base_charge_override').val(item.base_charge_override || '');
            $('#nursing_charge_override').val(item.nursing_charge_override || '');
            $('#has_oxygen').prop('checked', String(item.has_oxygen) === '1');
            $('#has_suction').prop('checked', String(item.has_suction) === '1');
            $('#has_monitor').prop('checked', String(item.has_monitor) === '1');
            $('#has_ventilator').prop('checked', String(item.has_ventilator) === '1');
            $('#is_isolation_bed').prop('checked', String(item.is_isolation_bed) === '1');
            $('#status').val(item.status || 'active');
            $('#remarks').val(item.remarks || '');
        });

        $('.delete-bed').on('click', function() {
            if (!confirm('Delete this bed?')) {
                return;
            }
            clearAlert();
            $.post('<?= base_url('setting/admin/beds/delete') ?>', {
                id: $(this).data('id'),
                '<?= csrf_token() ?>': $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>'
            }).done(function(resp) {
                if (resp && resp.error_text) {
                    showAlert(resp.error_text);
                    return;
                }
                load_form_div('<?= base_url('setting/admin/beds') ?>', 'maindiv', 'Beds');
            });
        });

        if (window.jQuery && $.fn && $.fn.DataTable) {
            var table = $('#bedTable').DataTable();

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

            initFilter('#filterWard', 2);
            initFilter('#filterBedStatus', 4);
            initFilter('#filterActive', 5);
        }
    })();
</script>

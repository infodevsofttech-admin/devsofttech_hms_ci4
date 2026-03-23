<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Ward Master</h3>
        <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/bed-management') ?>','maindiv','Bed Management');">
            <i class="bi bi-arrow-left"></i>
            Back
        </button>
    </div>
    <div class="card-body">
        <div id="wardAlert" class="alert alert-danger d-none"></div>
        <form id="wardForm">
            <?= csrf_field() ?>
            <input type="hidden" id="ward_id" name="id" value="0">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Ward Code</label>
                    <input class="form-control" id="ward_code" name="ward_code" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ward Name</label>
                    <input class="form-control" id="ward_name" name="ward_name" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Department</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <?php foreach (($departments ?? []) as $department) { ?>
                            <option value="<?= esc($department->iId ?? 0) ?>"><?= esc($department->vName ?? '') ?></option>
                        <?php } ?>
                    </select>
                    <div class="mt-1">
                        <button type="button" class="btn btn-link btn-sm p-0" onclick="load_form_div('<?= base_url('setting/admin/departments') ?>','maindiv','Departments');">Manage Departments</button>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Floor</label>
                    <input class="form-control" id="floor_number" name="floor_number" type="number">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Capacity</label>
                    <input class="form-control" id="total_capacity" name="total_capacity" type="number" min="0" value="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ward Type</label>
                    <select class="form-select" id="ward_type" name="ward_type">
                        <option value="General">General</option>
                        <option value="Private">Private</option>
                        <option value="Semi-Private">Semi-Private</option>
                        <option value="Deluxe">Deluxe</option>
                        <option value="ICU">ICU</option>
                        <option value="NICU">NICU</option>
                        <option value="PICU">PICU</option>
                        <option value="HDU">HDU</option>
                        <option value="ICCU">ICCU</option>
                        <option value="CCU">CCU</option>
                        <option value="Emergency">Emergency</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gender Type</label>
                    <select class="form-select" id="gender_type" name="gender_type">
                        <option value="unisex">Unisex</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ward Category</label>
                    <select class="form-select" id="ward_category" name="ward_category">
                        <option value="adult">Adult</option>
                        <option value="pediatric">Pediatric</option>
                        <option value="maternity">Maternity</option>
                        <option value="isolation">Isolation</option>
                        <option value="surgical">Surgical</option>
                        <option value="medical">Medical</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="under_maintenance">Under Maintenance</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Remarks</label>
                    <input class="form-control" id="remarks" name="remarks">
                </div>
                <div class="col-md-6 d-flex align-items-end gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="has_oxygen" name="has_oxygen">
                        <label class="form-check-label" for="has_oxygen">Oxygen</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="has_suction" name="has_suction">
                        <label class="form-check-label" for="has_suction">Suction</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="has_monitor" name="has_monitor">
                        <label class="form-check-label" for="has_monitor">Monitor</label>
                    </div>
                </div>
                <div class="col-md-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Save Ward</button>
                    <button class="btn btn-light" type="button" id="resetWard">Clear</button>
                </div>
            </div>
        </form>

        <hr/>

        <div class="row g-2 mb-2">
            <div class="col-md-3">
                <select class="form-select" id="filterWardType">
                    <option value="">All Types</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filterWardStatus">
                    <option value="">All Status</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="wardTable">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($wards ?? []) as $row) {
                        $payload = json_encode($row);
                    ?>
                        <tr>
                            <td><?= esc($row->ward_code ?? '') ?></td>
                            <td><?= esc($row->ward_name ?? '') ?></td>
                            <td><?= esc($row->department_name ?? 'All Department') ?></td>
                            <td><?= esc($row->ward_type ?? '') ?></td>
                            <td><?= esc($row->total_capacity ?? 0) ?></td>
                            <td><?= esc($row->status ?? '') ?></td>
                            <td>
                                <button class="btn btn-outline-secondary btn-sm edit-ward" data-item="<?= esc($payload, 'attr') ?>">Edit</button>
                                <button class="btn btn-outline-danger btn-sm delete-ward" data-id="<?= esc($row->id ?? 0) ?>">Delete</button>
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
        var form = document.getElementById('wardForm');
        var resetButton = document.getElementById('resetWard');
        var alertBox = document.getElementById('wardAlert');

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
            $('#ward_id').val(0);
            $('#total_capacity').val(0);
            clearAlert();
        }

        resetButton.addEventListener('click', clearForm);

        $(form).on('submit', function(event) {
            event.preventDefault();
            clearAlert();

            $.post('<?= base_url('setting/admin/wards/save') ?>', $(form).serialize())
                .done(function(resp) {
                    if (resp && resp.error_text) {
                        showAlert(resp.error_text);
                        return;
                    }
                    load_form_div('<?= base_url('setting/admin/wards') ?>', 'maindiv', 'Wards');
                })
                .fail(function() {
                    showAlert('Request failed.');
                });
        });

        $('.edit-ward').on('click', function() {
            var item = $(this).data('item');
            if (!item) {
                return;
            }

            $('#ward_id').val(item.id || 0);
            $('#ward_code').val(item.ward_code || '');
            $('#ward_name').val(item.ward_name || '');
            $('#department_id').val(item.department_id !== undefined ? item.department_id : 0);
            $('#floor_number').val(item.floor_number || '');
            $('#total_capacity').val(item.total_capacity || 0);
            $('#ward_type').val(item.ward_type || 'General');
            $('#gender_type').val(item.gender_type || 'unisex');
            $('#ward_category').val(item.ward_category || 'adult');
            $('#status').val(item.status || 'active');
            $('#remarks').val(item.remarks || '');
            $('#has_oxygen').prop('checked', String(item.has_oxygen) === '1');
            $('#has_suction').prop('checked', String(item.has_suction) === '1');
            $('#has_monitor').prop('checked', String(item.has_monitor) === '1');
        });

        $('.delete-ward').on('click', function() {
            if (!confirm('Delete this ward?')) {
                return;
            }

            clearAlert();
            $.post('<?= base_url('setting/admin/wards/delete') ?>', {
                id: $(this).data('id'),
                '<?= csrf_token() ?>': $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>'
            }).done(function(resp) {
                if (resp && resp.error_text) {
                    showAlert(resp.error_text);
                    return;
                }
                load_form_div('<?= base_url('setting/admin/wards') ?>', 'maindiv', 'Wards');
            });
        });

        if (window.jQuery && $.fn && $.fn.DataTable) {
            var table = $('#wardTable').DataTable();

            function initFilter(selectId, columnIndex) {
                var select = $(selectId);
                if (!select.length) {
                    return;
                }
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

            initFilter('#filterWardType', 3);
            initFilter('#filterWardStatus', 5);
        }
    })();
</script>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Department Master</h3>
        <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/bed-management') ?>','maindiv','Bed Management');">
            <i class="bi bi-arrow-left"></i>
            Back
        </button>
    </div>
    <div class="card-body">
        <?php if (($tableMissing ?? false) === true): ?>
            <div class="alert alert-danger">Department table (hc_department) is not available in this database.</div>
        <?php else: ?>
            <div id="departmentAlert" class="alert alert-danger d-none"></div>
            <form id="departmentForm">
                <?= csrf_field() ?>
                <input type="hidden" id="department_id" name="id" value="0">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Department Name</label>
                        <input class="form-control" id="department_name" name="department_name" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department Type ID</label>
                        <input class="form-control" id="hc_type_id" name="hc_type_id" type="number" min="0">
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button class="btn btn-primary" type="submit">Save Department</button>
                        <button class="btn btn-light" type="button" id="resetDepartment">Clear</button>
                    </div>
                </div>
            </form>

            <hr/>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="departmentTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Type ID</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($departments ?? []) as $row) {
                            $payload = json_encode($row);
                        ?>
                            <tr>
                                <td><?= esc($row->iId ?? 0) ?></td>
                                <td><?= esc($row->vName ?? '') ?></td>
                                <td><?= esc($row->hc_type_id ?? '') ?></td>
                                <td>
                                    <button class="btn btn-outline-secondary btn-sm edit-department" data-item="<?= esc($payload, 'attr') ?>">Edit</button>
                                    <button class="btn btn-outline-danger btn-sm delete-department" data-id="<?= esc($row->iId ?? 0) ?>">Delete</button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
    (function() {
        var form = document.getElementById('departmentForm');
        var resetButton = document.getElementById('resetDepartment');
        var alertBox = document.getElementById('departmentAlert');

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
            $('#department_id').val(0);
            clearAlert();
        }

        resetButton.addEventListener('click', clearForm);

        $(form).on('submit', function(event) {
            event.preventDefault();
            clearAlert();

            $.post('<?= base_url('setting/admin/departments/save') ?>', $(form).serialize())
                .done(function(resp) {
                    if (resp && resp.error_text) {
                        showAlert(resp.error_text);
                        return;
                    }
                    load_form_div('<?= base_url('setting/admin/departments') ?>', 'maindiv', 'Departments');
                })
                .fail(function() {
                    showAlert('Request failed.');
                });
        });

        $('.edit-department').on('click', function() {
            var item = $(this).data('item');
            if (!item) {
                return;
            }

            $('#department_id').val(item.iId || 0);
            $('#department_name').val(item.vName || '');
            $('#hc_type_id').val(item.hc_type_id || '');
        });

        $('.delete-department').on('click', function() {
            if (!confirm('Delete this department?')) {
                return;
            }

            clearAlert();
            $.post('<?= base_url('setting/admin/departments/delete') ?>', {
                id: $(this).data('id'),
                '<?= csrf_token() ?>': $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>'
            }).done(function(resp) {
                if (resp && resp.error_text) {
                    showAlert(resp.error_text);
                    return;
                }
                load_form_div('<?= base_url('setting/admin/departments') ?>', 'maindiv', 'Departments');
            });
        });

        if (window.jQuery && $.fn && $.fn.DataTable) {
            $('#departmentTable').DataTable();
        }
    })();
</script>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Bed Categories</h3>
        <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/bed-management') ?>','maindiv','Bed Management');">
            <i class="bi bi-arrow-left"></i>
            Back
        </button>
    </div>
    <div class="card-body">
        <div id="categoryAlert" class="alert alert-danger d-none"></div>
        <form id="bedCategoryForm">
            <?= csrf_field() ?>
            <input type="hidden" id="category_id" name="id" value="0">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Category Code</label>
                    <input class="form-control" id="category_code" name="category_code" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Category Name</label>
                    <input class="form-control" id="category_name" name="category_name" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category Type</label>
                    <select class="form-select" id="category_type" name="category_type">
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
                        <option value="Suite">Suite</option>
                        <option value="VIP">VIP</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Base Charge/Day</label>
                    <input class="form-control" id="base_charge_per_day" name="base_charge_per_day" type="number" step="0.01">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nursing Charge/Day</label>
                    <input class="form-control" id="nursing_charge_per_day" name="nursing_charge_per_day" type="number" step="0.01">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Amenities (JSON or comma list)</label>
                    <input class="form-control" id="amenities" name="amenities">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Description</label>
                    <input class="form-control" id="description" name="description">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Save Category</button>
                    <button class="btn btn-light" type="button" id="resetCategory">Clear</button>
                </div>
            </div>
        </form>
        <hr/>
        <div class="row g-2 mb-2">
            <div class="col-md-3">
                <select class="form-select" id="filterCategoryType">
                    <option value="">All Types</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filterCategoryStatus">
                    <option value="">All Status</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="categoryTable">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Charges</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($categories ?? []) as $row) {
                        $payload = json_encode($row);
                    ?>
                        <tr>
                            <td><?= esc($row->category_code ?? '') ?></td>
                            <td><?= esc($row->category_name ?? '') ?></td>
                            <td><?= esc($row->category_type ?? '') ?></td>
                            <td><?= esc($row->base_charge_per_day ?? '') ?> / <?= esc($row->nursing_charge_per_day ?? '') ?></td>
                            <td><?= esc($row->status ?? '') ?></td>
                            <td>
                                <button class="btn btn-outline-secondary btn-sm edit-category" data-item="<?= esc($payload, 'attr') ?>">Edit</button>
                                <button class="btn btn-outline-danger btn-sm delete-category" data-id="<?= esc($row->id ?? 0) ?>">Delete</button>
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
        var form = document.getElementById('bedCategoryForm');
        var resetButton = document.getElementById('resetCategory');
        var alertBox = document.getElementById('categoryAlert');

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
            $('#category_id').val(0);
            clearAlert();
        }

        resetButton.addEventListener('click', clearForm);

        $(form).on('submit', function(event) {
            event.preventDefault();
            clearAlert();
            $.post('<?= base_url('setting/admin/bed-categories/save') ?>', $(form).serialize())
                .done(function(resp) {
                    if (resp && resp.error_text) {
                        showAlert(resp.error_text);
                        return;
                    }
                    load_form_div('<?= base_url('setting/admin/bed-categories') ?>', 'maindiv', 'Bed Categories');
                })
                .fail(function() {
                    showAlert('Request failed.');
                });
        });

        $('.edit-category').on('click', function() {
            var item = $(this).data('item');
            if (!item) return;
            $('#category_id').val(item.id || 0);
            $('#category_code').val(item.category_code || '');
            $('#category_name').val(item.category_name || '');
            $('#category_type').val(item.category_type || 'General');
            $('#base_charge_per_day').val(item.base_charge_per_day || '');
            $('#nursing_charge_per_day').val(item.nursing_charge_per_day || '');
            $('#amenities').val(item.amenities || '');
            $('#description').val(item.description || '');
            $('#status').val(item.status || 'active');
        });

        $('.delete-category').on('click', function() {
            if (!confirm('Delete this category?')) {
                return;
            }
            clearAlert();
            $.post('<?= base_url('setting/admin/bed-categories/delete') ?>', {
                id: $(this).data('id'),
                '<?= csrf_token() ?>': $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>'
            }).done(function(resp) {
                if (resp && resp.error_text) {
                    showAlert(resp.error_text);
                    return;
                }
                load_form_div('<?= base_url('setting/admin/bed-categories') ?>', 'maindiv', 'Bed Categories');
            });
        });

        if (window.jQuery && $.fn && $.fn.DataTable) {
            var table = $('#categoryTable').DataTable();

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

            initFilter('#filterCategoryType', 2);
            initFilter('#filterCategoryStatus', 4);
        }
    })();
</script>

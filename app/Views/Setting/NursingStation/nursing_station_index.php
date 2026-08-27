<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="text-primary mb-0"><i class="bi bi-hospital me-1"></i> Nursing Station Master</h5>
            <small class="text-muted">Manage hospital nursing stations and assign in-charge staff & floors</small>
        </div>
        <button type="button" class="btn btn-primary btn-sm" id="btn_add_station">
            <i class="bi bi-plus-lg me-1"></i> Add Nursing Station
        </button>
    </div>

    <!-- Search & Filter Card -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2 px-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-9">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" id="station_search_input" placeholder="Search by station name, code, floor, or nurse name..." value="<?= esc($searchQuery ?? '') ?>">
                        <button class="btn btn-primary" type="button" id="btn_station_search">Search</button>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn_refresh_station_list">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stations Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="station_table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;" class="ps-3">#</th>
                            <th style="width: 130px;">Station Code</th>
                            <th>Station Name</th>
                            <th style="width: 140px;">Floor / Location</th>
                            <th>In-charge Nurse</th>
                            <th>Contact No</th>
                            <th style="width: 90px;">Status</th>
                            <th style="width: 110px;" class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="station_table_body">
                        <?php if (empty($stations)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-hospital fs-3 d-block mb-1"></i>
                                    No nursing stations found. Click <strong>"Add Nursing Station"</strong> to create one.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($stations as $idx => $st): ?>
                                <tr id="station_row_<?= (int)$st['id'] ?>" data-station='<?= json_encode($st, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                    <td class="ps-3 fw-bold text-secondary"><?= $idx + 1 ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= esc($st['station_code']) ?></span></td>
                                    <td class="fw-semibold text-primary"><?= esc($st['station_name']) ?></td>
                                    <td><span class="badge bg-info-subtle text-info border border-info-subtle"><?= esc($st['floor_number'] ?: 'Ground Floor') ?></span></td>
                                    <td>
                                        <?php if (!empty($st['incharge_nurse_name'])): ?>
                                            <span class="text-dark"><i class="bi bi-person-heart text-danger me-1"></i><?= esc($st['incharge_nurse_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">- Not Assigned -</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($st['contact_no'] ?: '-') ?></td>
                                    <td>
                                        <?php if ($st['status'] === 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 btn-edit-station" data-id="<?= (int)$st['id'] ?>">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 btn-delete-station" data-id="<?= (int)$st['id'] ?>" data-name="<?= esc($st['station_name']) ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Add/Edit Nursing Station -->
<div class="modal fade" id="nursingStationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light py-2">
                <h5 class="modal-title text-primary fs-6" id="stationModalTitle"><i class="bi bi-plus-lg me-1"></i> Add Nursing Station</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="nursingStationForm">
                <input type="hidden" id="station_id" name="id" value="0">
                <div class="modal-body">
                    <div id="station_form_status" class="alert alert-danger d-none py-2 px-3 mb-3"></div>
                    
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small mb-1">Station Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="station_code" name="station_code" placeholder="e.g. NS-101" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small mb-1">Status</label>
                            <select class="form-select form-select-sm" id="station_status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small mb-1">Station Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="station_name" name="station_name" placeholder="e.g. 2nd Floor Wards Nursing Station" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small mb-1">Floor / Location</label>
                            <select class="form-select form-select-sm" id="station_floor_number" name="floor_number">
                                <option value="Ground Floor">Ground Floor</option>
                                <option value="1st Floor">1st Floor</option>
                                <option value="2nd Floor">2nd Floor</option>
                                <option value="3rd Floor">3rd Floor</option>
                                <option value="4th Floor">4th Floor</option>
                                <option value="ICU Block">ICU Block</option>
                                <option value="OT Block">OT Block</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small mb-1">Contact No</label>
                            <input type="text" class="form-control form-control-sm" id="station_contact_no" name="contact_no" placeholder="Extension / Phone">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small mb-1">In-charge Nursing Staff</label>
                            <select class="form-select form-select-sm" id="station_incharge_nurse_id" name="incharge_nurse_id">
                                <option value="0">-- Select In-charge Nurse --</option>
                                <?php if (!empty($nurses)): ?>
                                    <?php foreach ($nurses as $nurse): ?>
                                        <option value="<?= (int)$nurse['id'] ?>"><?= esc(($nurse['nurse_code'] ? '[' . $nurse['nurse_code'] . '] ' : '') . $nurse['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small mb-1">Remarks / Location Note</label>
                            <textarea class="form-control form-control-sm" id="station_remarks" name="remarks" rows="2" placeholder="Optional notes"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btn_save_station">
                        <i class="bi bi-check-lg me-1"></i> Save Station
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    var saveUrl = '<?= base_url('setting/admin/nursing-station/save') ?>';
    var deleteUrlBase = '<?= base_url('setting/admin/nursing-station/delete') ?>';

    function resetStationForm() {
        $('#station_id').val('0');
        $('#station_code').val('NS-' + Math.floor(100 + Math.random() * 900));
        $('#station_name').val('');
        $('#station_floor_number').val('2nd Floor');
        $('#station_incharge_nurse_id').val('0');
        $('#station_contact_no').val('');
        $('#station_status').val('active');
        $('#station_remarks').val('');
        $('#station_form_status').addClass('d-none').text('');
        $('#stationModalTitle').html('<i class="bi bi-plus-lg me-1"></i> Add Nursing Station');
    }

    $('#btn_add_station').on('click', function() {
        resetStationForm();
        var modalEl = document.getElementById('nursingStationModal');
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            $(modalEl).modal('show');
        }
    });

    $(document).on('click', '.btn-edit-station', function() {
        var id = $(this).data('id');
        var row = $('#station_row_' + id);
        if (!row.length) return;
        var data = row.data('station');
        if (!data) return;

        $('#station_id').val(data.id || '0');
        $('#station_code').val(data.station_code || '');
        $('#station_name').val(data.station_name || '');
        $('#station_floor_number').val(data.floor_number || 'Ground Floor');
        $('#station_incharge_nurse_id').val(data.incharge_nurse_id || '0');
        $('#station_contact_no').val(data.contact_no || '');
        $('#station_status').val(data.status || 'active');
        $('#station_remarks').val(data.remarks || '');
        $('#station_form_status').addClass('d-none').text('');
        $('#stationModalTitle').html('<i class="bi bi-pencil-square me-1"></i> Edit Nursing Station');

        var modalEl = document.getElementById('nursingStationModal');
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            $(modalEl).modal('show');
        }
    });

    $('#nursingStationForm').on('submit', function(e) {
        e.preventDefault();
        var $status = $('#station_form_status');
        $status.addClass('d-none').text('');

        var formData = $(this).serialize();
        $.post(saveUrl, formData, function(resp) {
            if (resp && resp.ok) {
                var modalEl = document.getElementById('nursingStationModal');
                if (window.bootstrap && window.bootstrap.Modal) {
                    var modal = window.bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                } else {
                    $(modalEl).modal('hide');
                }
                load_form_div('<?= base_url('setting/admin/nursing-station') ?>', 'maindiv', 'Nursing Station Master');
            } else {
                $status.removeClass('d-none').text((resp && resp.error) ? resp.error : 'Failed to save station.');
            }
        }, 'json').fail(function() {
            $status.removeClass('d-none').text('Server error while saving station record.');
        });
    });

    $(document).on('click', '.btn-delete-station', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        if (!confirm('Are you sure you want to delete nursing station "' + name + '"?')) return;

        $.post(deleteUrlBase, { id: id, '<?= csrf_token() ?>': '<?= csrf_hash() ?>' }, function(resp) {
            if (resp && resp.ok) {
                load_form_div('<?= base_url('setting/admin/nursing-station') ?>', 'maindiv', 'Nursing Station Master');
            } else {
                alert((resp && resp.error) ? resp.error : 'Failed to delete station.');
            }
        }, 'json').fail(function() {
            alert('Server error while deleting station.');
        });
    });

    $('#btn_station_search').on('click', function() {
        var q = $('#station_search_input').val();
        load_form_div('<?= base_url('setting/admin/nursing-station') ?>?q=' + encodeURIComponent(q), 'maindiv', 'Nursing Station Master');
    });

    $('#station_search_input').on('keyup', function(e) {
        if (e.key === 'Enter') {
            $('#btn_station_search').click();
        }
    });

    $('#btn_refresh_station_list').on('click', function() {
        load_form_div('<?= base_url('setting/admin/nursing-station') ?>', 'maindiv', 'Nursing Station Master');
    });
})();
</script>

<?php
$csrfName = csrf_token();
$csrfHash = csrf_hash();
$items = isset($items) && is_array($items) ? $items : [];
$syncMeta = isset($sync_meta) && is_array($sync_meta) ? $sync_meta : [];
?>
<div class="container-fluid py-3 immunization-master-ui">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0">UIP Schedule Master</h5>
                <div class="small text-muted">
                    Edit vaccine, age, route, dose and notes used for new patient schedules.
                    <?php if (! empty($syncMeta)) : ?>
                        Bridge version: <?= esc((string) ($syncMeta['version_code'] ?? '')) ?><?= ! empty($syncMeta['synced_at']) ? ' synced ' . esc((string) $syncMeta['synced_at']) : '' ?>.
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-success" id="btnSyncUipMaster">Sync From Bridge</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="load_form('<?= site_url('Immunization') ?>','Immunization Record')">Back to Immunization</button>
            </div>
        </div>
        <div class="px-3 pt-3 d-none" id="uipSyncStatus"></div>
        <div class="card-body p-0">
            <input type="hidden" id="uipCsrfName" value="<?= esc($csrfName) ?>">
            <input type="hidden" id="uipCsrfHash" value="<?= esc($csrfHash) ?>">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" id="uipScheduleMasterTable">
                    <thead class="table-light">
                        <tr>
                            <th>Sort</th>
                            <th>Age</th>
                            <th>Vaccine</th>
                            <th>Dose</th>
                            <th>Offset Days</th>
                            <th>Route</th>
                            <th>Target Disease</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($items === []) : ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No UIP schedule found.</td></tr>
                    <?php else : ?>
                        <?php foreach ($items as $item) : ?>
                            <tr data-id="<?= (int) ($item['id'] ?? 0) ?>">
                                <td><?= (int) ($item['sort_order'] ?? 0) ?></td>
                                <td><?= esc((string) ($item['age_label'] ?? '')) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($item['vaccine_name'] ?? '')) ?></div>
                                    <div class="small text-muted"><?= esc((string) ($item['vaccine_code'] ?? '')) ?></div>
                                </td>
                                <td><?= esc((string) ($item['dose_number'] ?? '')) ?></td>
                                <td><?= (int) ($item['age_offset_days'] ?? 0) ?></td>
                                <td><?= esc((string) ($item['route_name'] ?? '')) ?></td>
                                <td><?= esc((string) ($item['target_disease_name'] ?? '')) ?></td>
                                <td><?= ((int) ($item['is_active'] ?? 0) === 1) ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary btnEditSchedule" data-item='<?= esc(json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'attr') ?>'>Edit</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uipEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title">Edit UIP Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="scheduleId">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label mb-1">Age Label</label>
                            <input type="text" class="form-control" id="ageLabel">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1">Age Value</label>
                            <input type="number" min="0" step="1" class="form-control" id="ageValue">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1">Age Unit</label>
                            <select class="form-select" id="ageUnit">
                                <option value="days">days</option>
                                <option value="weeks">weeks</option>
                                <option value="months">months</option>
                                <option value="years">years</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1">Offset Days</label>
                            <input type="number" min="0" step="1" class="form-control" id="ageOffsetDays">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1">Sort</label>
                            <input type="number" min="0" step="1" class="form-control" id="sortOrder">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label mb-1">Vaccine</label>
                            <input type="text" class="form-control" id="vaccineName">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1">Dose</label>
                            <input type="text" class="form-control" id="doseNumber">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1">Series Doses</label>
                            <input type="text" class="form-control" id="seriesDoses">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">Route</label>
                            <input type="text" class="form-control" id="routeName">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1">Target Disease</label>
                            <input type="text" class="form-control" id="targetDiseaseName">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">Site</label>
                            <input type="text" class="form-control" id="siteName">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">Status</label>
                            <select class="form-select" id="isActive">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-1">Notes</label>
                            <textarea class="form-control" rows="2" id="scheduleNotes"></textarea>
                        </div>
                    </div>
                    <div class="mt-3" id="uipMasterStatus"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnSaveSchedule">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function esc(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function csrfPayload() {
        var payload = {};
        payload[$('#uipCsrfName').val()] = $('#uipCsrfHash').val();
        return payload;
    }

    function updateCsrf(response) {
        if (response && response.csrfName && response.csrfHash) {
            $('#uipCsrfName').val(response.csrfName);
            $('#uipCsrfHash').val(response.csrfHash);
        }
    }

    function showStatus(type, message) {
        var cls = type === 'success' ? 'alert-success' : 'alert-warning';
        $('#uipMasterStatus').html('<div class="alert ' + cls + ' py-2 mb-0">' + esc(message) + '</div>');
    }

    function showSyncStatus(type, message) {
        var cls = type === 'success' ? 'alert-success' : 'alert-warning';
        $('#uipSyncStatus').removeClass('d-none').html('<div class="alert ' + cls + ' py-2 mb-0">' + esc(message) + '</div>');
    }

    function fillModal(item) {
        $('#scheduleId').val(item.id || '');
        $('#ageLabel').val(item.age_label || '');
        $('#ageValue').val(item.age_value || 0);
        $('#ageUnit').val(item.age_unit || 'days');
        $('#ageOffsetDays').val(item.age_offset_days || 0);
        $('#sortOrder').val(item.sort_order || 0);
        $('#vaccineName').val(item.vaccine_name || '');
        $('#doseNumber').val(item.dose_number || '');
        $('#seriesDoses').val(item.series_doses || '');
        $('#routeName').val(item.route_name || '');
        $('#targetDiseaseName').val(item.target_disease_name || '');
        $('#siteName').val(item.site_name || '');
        $('#isActive').val(String(item.is_active || 0));
        $('#scheduleNotes').val(item.notes || '');
        $('#uipMasterStatus').empty();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('uipEditModal')).show();
    }

    function saveSchedule() {
        var scheduleId = parseInt($('#scheduleId').val(), 10) || 0;
        if (scheduleId <= 0) {
            return;
        }

        var payload = csrfPayload();
        payload.age_label = $('#ageLabel').val();
        payload.age_value = $('#ageValue').val();
        payload.age_unit = $('#ageUnit').val();
        payload.age_offset_days = $('#ageOffsetDays').val();
        payload.sort_order = $('#sortOrder').val();
        payload.vaccine_name = $('#vaccineName').val();
        payload.dose_number = $('#doseNumber').val();
        payload.series_doses = $('#seriesDoses').val();
        payload.route_name = $('#routeName').val();
        payload.target_disease_name = $('#targetDiseaseName').val();
        payload.site_name = $('#siteName').val();
        payload.is_active = $('#isActive').val();
        payload.notes = $('#scheduleNotes').val();

        $.ajax({
            url: '<?= site_url('Immunization/update_schedule') ?>/' + scheduleId,
            method: 'POST',
            dataType: 'json',
            data: payload,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (data) {
            updateCsrf(data);
            if (parseInt(data.ok || 0, 10) !== 1) {
                showStatus('warning', data.error_text || 'Save failed');
                return;
            }
            showStatus('success', 'Schedule updated. Reloading master...');
            window.setTimeout(function () {
                load_form('<?= site_url('Immunization/schedule_master') ?>', 'UIP Schedule Master');
            }, 450);
        }).fail(function (xhr) {
            updateCsrf(xhr.responseJSON || {});
            var json = xhr.responseJSON || {};
            showStatus('warning', json.error_text || json.message || xhr.statusText || 'Save failed');
        });
    }

    function syncUipMaster() {
        var payload = csrfPayload();
        payload.force = 1;
        $('#btnSyncUipMaster').prop('disabled', true).text('Syncing...');
        showSyncStatus('success', 'Syncing UIP master from bridge...');

        $.ajax({
            url: '<?= site_url('Immunization/sync_uip_master') ?>',
            method: 'POST',
            dataType: 'json',
            data: payload,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function (data) {
            updateCsrf(data);
            if (parseInt(data.ok || 0, 10) !== 1) {
                showSyncStatus('warning', data.error_text || 'Sync failed');
                return;
            }
            showSyncStatus('success', data.message || 'UIP master synced. Reloading...');
            window.setTimeout(function () {
                load_form('<?= site_url('Immunization/schedule_master') ?>', 'UIP Schedule Master');
            }, 650);
        }).fail(function (xhr) {
            updateCsrf(xhr.responseJSON || {});
            var json = xhr.responseJSON || {};
            showSyncStatus('warning', json.error_text || json.message || xhr.statusText || 'Sync failed');
        }).always(function () {
            $('#btnSyncUipMaster').prop('disabled', false).text('Sync From Bridge');
        });
    }

    $('#uipScheduleMasterTable').on('click', '.btnEditSchedule', function () {
        var raw = $(this).attr('data-item') || '{}';
        try {
            fillModal(JSON.parse(raw));
        } catch (error) {
            showStatus('warning', 'Unable to open schedule record.');
        }
    });
    $('#btnSaveSchedule').on('click', saveSchedule);
    $('#btnSyncUipMaster').on('click', syncUipMaster);
})();
</script>
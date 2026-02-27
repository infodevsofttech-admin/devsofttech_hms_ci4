<?php
$master = $lab_request_master[0] ?? null;
$rows = $lab_request_item_entry ?? [];
?>

<?php if (! $master): ?>
    <div class="alert alert-danger mb-0">No test request data found.</div>
<?php else: ?>
    <input type="hidden" id="hid_value_req_id" value="<?= esc($master->id ?? '') ?>">
    <?= csrf_field() ?>

    <div class="mb-3">
        <h5 class="mb-1">Patient Name :<?= esc($master->patient_name ?? '') ?>
            <small class="text-muted"> / <?= esc($master->invoice_code ?? '') ?></small>
        </h5>
    </div>

    <div style="max-height:500px;overflow-y:auto;">
        <?php if (! empty($rows)): ?>
            <table class="table table-bordered table-striped mb-3">
                <thead>
                    <tr>
                        <th>Test Name/Test ID</th>
                        <th>Saved Value</th>
                        <th>New Value</th>
                        <th>FixedNormals</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td>
                            <?= esc($row->Test ?? '') ?>[<?= esc($row->TestID ?? '') ?>]
                            <?php if (! empty($row->Formula)): ?>
                                <br><small class="text-muted"><?= esc($row->Formula) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><div id="update_value_<?= esc($row->id) ?>"><?= esc($row->lab_test_value ?? '') ?></div></td>
                        <td>
                            <?php if (! empty($row->option_value)): ?>
                                <?php $options = explode(',', (string) $row->option_value); ?>
                                <div class="d-flex gap-2">
                                    <select id="test_value_<?= esc($row->id) ?>" class="form-select form-select-sm" style="max-width:260px;">
                                        <?php foreach ($options as $option): ?>
                                            <?php
                                            $cols = explode(':', (string) $option);
                                            $optionVal = $cols[1] ?? '';
                                            $optionLabel = $cols[2] ?? $optionVal;
                                            ?>
                                            <option value="<?= esc($optionVal) ?>">[<?= esc($optionVal) ?>] <?= esc($optionLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="update_test_value(<?= esc($row->id) ?>, document.getElementById('test_value_<?= esc($row->id) ?>').value)">Save</button>
                                </div>
                            <?php elseif (! empty($row->Formula)): ?>
                                <span class="text-muted">After Calculate</span>
                            <?php else: ?>
                                <input id="test_value_<?= esc($row->id) ?>" type="text" class="form-control form-control-sm" value="<?= esc($row->lab_test_value ?? '') ?>" onchange="update_test_value(<?= esc($row->id) ?>, this.value)">
                            <?php endif; ?>
                        </td>
                        <td><?= esc($row->FixedNormals ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning">No test parameters mapped for this report template.</div>
        <?php endif; ?>
    </div>

    <div class="mt-3">
        <button type="button" class="btn btn-primary" onclick="report_create()">Save &amp; Next</button>
    </div>

<?php endif; ?>

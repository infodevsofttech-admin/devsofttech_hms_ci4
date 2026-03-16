<?php $docId = (int) ($doc_id ?? 0); ?>
<div class="card">
    <div class="card-body">
        <h5 class="card-title">Input Parameter List</h5>
        <div class="table-responsive" style="max-height:360px;overflow-y:auto;">
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Code</th>
                <th>Type</th>
                <th>Default</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php $no = 1; foreach (($doc_Item_List ?? []) as $row): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= esc((string) ($row['input_name'] ?? '')) ?></td>
                    <td><?= esc((string) ($row['input_code'] ?? '')) ?></td>
                    <td><?= esc((string) ($row['input_type'] ?? 'text')) ?></td>
                    <td><?= esc((string) ($row['input_default_value'] ?? '')) ?></td>
                    <td><button type="button" class="btn btn-primary btn-sm" onclick="editDocInput(<?= (int) ($row['item_id'] ?? 0) ?>)">Edit</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <hr>
        <h6 class="mb-3">Add / Edit Input</h6>
        <?= csrf_field() ?>
        <input type="hidden" id="doc_sub_id" value="0">
        <div class="mb-3">
            <label class="form-label">Input Name</label>
            <input type="text" class="form-control" id="input_input_name">
        </div>
        <div class="mb-3">
            <label class="form-label">Input Code</label>
            <input type="text" class="form-control" id="input_input_code" placeholder="e.g. DISEASES">
        </div>
        <div class="mb-3">
            <label class="form-label">Input Type</label>
            <select class="form-control" id="input_type">
                <option value="text">Text</option>
                <option value="date">Date</option>
                <option value="textarea">Text Area</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Default Value</label>
            <input type="text" class="form-control" id="input_default_value">
        </div>
        <button type="button" class="btn btn-success btn-sm" onclick="saveDocInput()">Save Input</button>
    </div>
</div>

<script>
function editDocInput(itemId) {
    $.get('<?= base_url('Doc_Admin/input_parameter_load') ?>/' + itemId + '/<?= $docId ?>', function(data) {
        if (!data || parseInt(data.update || '0', 10) !== 1) {
            alert('Unable to load input');
            return;
        }
        var row = data.row || {};
        $('#doc_sub_id').val(row.id || 0);
        $('#input_input_name').val(row.input_name || '');
        $('#input_input_code').val(row.input_code || '');
        $('#input_type').val(row.input_type || 'text');
        $('#input_default_value').val(row.input_default_value || '');
    }, 'json');
}

function saveDocInput() {
    var csrfName = $('input[name="<?= csrf_token() ?>"]').attr('name');
    var csrfHash = $('input[name="<?= csrf_token() ?>"]').val();
    var subId = parseInt($('#doc_sub_id').val() || '0', 10);
    var url = subId > 0
        ? '<?= base_url('Doc_Admin/input_parameter_edit') ?>'
        : '<?= base_url('Doc_Admin/input_parameter_add') ?>';

    $.post(url, {
        doc_id: '<?= $docId ?>',
        doc_sub_id: subId,
        input_input_name: $('#input_input_name').val(),
        input_input_code: $('#input_input_code').val(),
        input_type: $('#input_type').val(),
        input_default_value: $('#input_default_value').val(),
        [csrfName]: csrfHash
    }, function(data) {
        alert(data.showcontent || 'Saved');
        if (data.csrfName && data.csrfHash) {
            $('input[name="' + data.csrfName + '"]').val(data.csrfHash);
        }
        load_form_div('<?= base_url('Doc_Admin/doc_input_list/' . $docId) ?>', 'test_div');
    }, 'json');
}
</script>

<?= $this->include('storestock/_niceadmin_theme') ?>
<div class="storestock-ui">
<div class="col-md-12">
    <div class="box">
        <div class="box-header">
            <h3 class="box-title">New Indent</h3>
        </div>
        <div class="box-body">
            <?= csrf_field() ?>
            <div class="module-hero" style="margin-bottom:.9rem;">
                <h4>Create Indent Request</h4>
                <p>Select date, then choose request origin: location or employee.</p>
            </div>

            <div class="row" style="margin-bottom:.9rem;">
                <div class="col-md-4">
                    <label>Date</label>
                    <div class="input-group input-group-sm date">
                        <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
                        <input class="form-control pull-right datepicker"
                               id="date_indent" name="date_indent" type="text"
                               data-inputmask="'alias': 'dd/mm/yyyy'" data-mask=""
                               value="<?= date('d/m/Y') ?>" />
                    </div>
                </div>
            </div>

            <div class="panel-grid">
                <div class="control-card">
                    <h5><i class="fa fa-map-marker"></i> By Location</h5>
                    <div class="form-group" style="margin-bottom:.5rem;">
                        <label>Select Location</label>
                        <select class="form-control select2" id="location_id" name="location_id">
                            <?php foreach ($location_master as $row) { ?>
                                <option value="<?= $row->l_id ?>"><?= esc($row->loc_name) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="add_indent(1)">
                        <i class="fa fa-plus-circle"></i> Create from Location
                    </button>
                    <div class="help">Use when indent is raised for a ward/department location.</div>
                </div>

                <div class="control-card">
                    <h5><i class="fa fa-user"></i> By Employee</h5>
                    <div class="form-group" style="margin-bottom:.5rem;">
                        <label>Select Employee</label>
                        <select class="form-control select2" id="emp_id" name="emp_id">
                            <?php foreach ($employee_master as $row) { ?>
                                <option value="<?= $row->emp_id ?>"><?= esc($row->emp_code) ?> [<?= esc($row->emp_name) ?>]</option>
                            <?php } ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm" onclick="add_indent(2)">
                        <i class="fa fa-plus-circle"></i> Create from Employee
                    </button>
                    <div class="help">Use when indent is raised in personal accountability mode.</div>
                </div>
            </div>
        </div>
        <div class="box-footer"></div>
    </div>
</div>

<script>
$('.select2').select2();

function add_indent(location_type) {
    var csrf_value = $('input[name="<?= csrf_token() ?>"]').val();
    var loc_id = 0;

    if (location_type == 1) {
        loc_id = $('#location_id').val();
    } else if (location_type == 2) {
        loc_id = $('#emp_id').val();
    }

    if (loc_id > 0) {
        $.post('/Storestock/Indent_create/' + location_type, {
            "loc_id": loc_id,
            "date_indent": $('#date_indent').val(),
            '<?= csrf_token() ?>': csrf_value
        }, function (data) {
            $('#maindiv').html(data);
        });
    } else {
        notify('error', 'Please Attention', 'Select a location or employee first');
    }
}
</script>
</div>

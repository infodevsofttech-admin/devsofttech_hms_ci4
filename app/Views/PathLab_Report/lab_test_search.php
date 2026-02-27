<?= form_open() ?>
<div class="card admin-card">
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
        <h3 class="mb-0">Test Parameter</h3>
        <button onclick="load_form_div('<?= base_url('Lab_Admin/test_parameter_load') ?>/0/<?= esc($repo_id ?? 0) ?>','test_div');" type="button" class="btn btn-primary btn-sm">Add New Test</button>
        <input type="hidden" id="repo_id" value="<?= esc($repo_id ?? 0) ?>" />
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Name of Test</label>
                    <input class="form-control" id="input_Test_name" placeholder="Test Name" type="text" autocomplete="off">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-primary" id="btn_item_search">Search</button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12" id="search_result"></div>
        </div>
    </div>
</div>
<?= form_close() ?>
<script>
    $('#btn_item_search').click(function() {
        var input_Test_name = $('#input_Test_name').val();
        var repo_id = $('#repo_id').val();
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';

        $.post('<?= base_url('Lab_Admin/test_item_search') ?>', {
            "input_Test_name": input_Test_name,
            "repo_id": repo_id,
            "<?= csrf_token() ?>": csrf_value
        }, function(data) {
            $('#search_result').html(data);
        });
    });

    function add_test(repo_id, test_id) {
        var csrf_value = $('input[name="<?= csrf_token() ?>"]').first().val() || '<?= csrf_hash() ?>';
        $.post('<?= base_url('Lab_Admin/add_test_repo') ?>/' + repo_id + '/' + test_id, {
            "repo_id": repo_id,
            "<?= csrf_token() ?>": csrf_value
        }, function(data) {
            if (data.insertid > 0) {
                load_form_div('<?= base_url('Lab_Admin/report_test_list') ?>/' + repo_id, 'test_div');
            }
        }, 'json');
    }
</script>

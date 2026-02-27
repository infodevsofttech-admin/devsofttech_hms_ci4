<?php if (! empty($data_item)) : ?>
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h3 class="card-title mb-0">Package Group: <?= esc($data_item[0]->pakage_group_name ?? '') ?></h3>
            <div class="card-tools ms-auto">
                <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('package/search-itemtype') ?>','maindiv','Package Groups');">
                    <i class="bi bi-arrow-left"></i>
                    Back to List
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="jsError"></div>
            <form action="<?= base_url('package/update-type') ?>" method="post" role="form" class="form1">
                <?= csrf_field() ?>
                <input type="hidden" value="<?= esc($data_item[0]->pak_id ?? '') ?>" id="itemtype_id" name="itemtype_id" />
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Package Group Name</label>
                        <input class="form-control" name="input_Item_type" placeholder="Group Name" value="<?= esc($data_item[0]->pakage_group_name ?? '') ?>" type="text" autocomplete="off">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-primary" id="btn_update">Update Record</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#btn_update').click(function() {
                $.post('<?= base_url('package/update-type') ?>', $('form.form1').serialize(), function(data) {
                    if (data.update == 0) {
                        if (typeof notify === 'function') {
                            notify('error', 'Please Attention', data.error_text || 'Please Check');
                        }
                    } else if (typeof notify === 'function') {
                        notify('success', 'Saved', data.showcontent || 'Data Saved successfully');
                    }
                    updateCsrf(data);
                }, 'json');
            });
        });

        function updateCsrf(data) {
            if (!data || !data.csrfName || !data.csrfHash) {
                return;
            }
            var input = document.querySelector('input[name="' + data.csrfName + '"]');
            if (input) {
                input.value = data.csrfHash;
            }
        }
    </script>
<?php endif ?>

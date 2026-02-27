<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Referral Types</h3>
        <div class="card-tools ms-auto">
            <button class="btn btn-light" type="button" onclick="load_form_div('<?= base_url('setting/admin/reffer') ?>','maindiv','Referral Clients');">
                <i class="bi bi-arrow-left"></i>
                Back to List
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php $message = $message ?? session('message'); ?>
        <?php $errors = $errors ?? session('errors'); ?>
        <?php if (! empty($message)) : ?>
            <div class="alert alert-success"><?= esc($message) ?></div>
        <?php endif ?>
        <?php if (! empty($errors)) : ?>
            <div class="alert alert-danger">
                <?php foreach ((array) $errors as $error) : ?>
                    <div><?= esc($error) ?></div>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <form class="row g-3 align-items-end" id="refferTypeCreateForm" action="<?= base_url('setting/admin/reffer/types/create') ?>" method="post">
            <?= csrf_field() ?>
            <div class="col-md-6">
                <label class="form-label" for="type_desc">Type Name</label>
                <input class="form-control" id="type_desc" name="type_desc" type="text" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Add Type</button>
            </div>
        </form>

        <div class="table-responsive mt-4">
            <table class="table table-striped datatable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($types)) : ?>
                        <?php foreach ($types as $index => $type) : ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <input class="form-control form-control-sm" type="text" value="<?= esc($type->type_desc ?? '') ?>" data-id="<?= esc($type->id ?? '') ?>">
                                </td>
                                <td class="d-flex gap-2">
                                    <button type="button" class="btn btn-success btn-sm" onclick="updateRefferType(this)">Save</button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteRefferType(<?= (int) ($type->id ?? 0) ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">No referral types found.</td>
                        </tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    (function() {
        var form = document.getElementById('refferTypeCreateForm');
        if (!form || !window.jQuery) {
            return;
        }

        $(form).on('submit', function(event) {
            event.preventDefault();
            $.post($(form).attr('action'), $(form).serialize())
                .done(function(html) {
                    $('#maindiv').html(html);
                })
                .fail(function() {
                    alert('Request failed. Please try again.');
                });
        });
    })();

    function updateRefferType(button) {
        if (!window.jQuery) {
            return;
        }
        var row = button.closest('tr');
        var input = row ? row.querySelector('input[data-id]') : null;
        if (!input) {
            return;
        }
        var data = {
            id: input.getAttribute('data-id'),
            type_desc: input.value
        };
        data['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';

        $.post('<?= base_url('setting/admin/reffer/types/update') ?>', data)
            .done(function(html) {
                $('#maindiv').html(html);
            })
            .fail(function() {
                alert('Request failed. Please try again.');
            });
    }

    function deleteRefferType(id) {
        if (!window.jQuery) {
            return;
        }
        var data = { id: id };
        data['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';

        $.post('<?= base_url('setting/admin/reffer/types/delete') ?>', data)
            .done(function(html) {
                $('#maindiv').html(html);
            })
            .fail(function() {
                alert('Request failed. Please try again.');
            });
    }
</script>

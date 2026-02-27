<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Referral Clients</h3>
        <div class="card-tools ms-auto d-flex gap-2 flex-wrap">
            <button onclick="load_form_div('<?= base_url('setting/admin/reffer/types') ?>','maindiv','Referral Types');" type="button" class="btn btn-outline-primary">
                <i class="bi bi-list-check"></i>
                Manage Types
            </button>
            <button onclick="load_form_div('<?= base_url('setting/admin/reffer/new') ?>','maindiv','New Referral Client');" type="button" class="btn btn-primary">
                <i class="bi bi-person-plus"></i>
                Add New
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped datatable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Register Date</th>
                        <th>Status</th>
                        <th>Edit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($refer_master)) : ?>
                        <?php foreach ($refer_master as $row) : ?>
                            <tr>
                                <td><?= esc(($row->title ?? '') . ' ' . ($row->f_name ?? '')) ?></td>
                                <td><?= esc($row->type_desc ?? 'Others') ?></td>
                                <td><?= esc($row->str_dateadd ?? '') ?></td>
                                <td>
                                    <?php if (! empty($row->active)) : ?>
                                        <a href="javascript:toggle_reffer_status(<?= (int) $row->id ?>, 0);">Active</a>
                                    <?php else : ?>
                                        <a href="javascript:toggle_reffer_status(<?= (int) $row->id ?>, 1);">Inactive</a>
                                    <?php endif ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="load_form_div('<?= base_url('setting/admin/reffer/' . $row->id) ?>','maindiv','Referral: <?= esc(($row->title ?? '') . ' ' . ($row->f_name ?? '')) ?>');">Edit</button>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No referral clients found.</td>
                        </tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function toggle_reffer_status(id, active) {
        if (!window.jQuery) {
            return;
        }
        $.post('<?= base_url('setting/admin/reffer/activate') ?>/' + id + '/' + active, {}, function(data) {
            if (data.update == 0) {
                if (typeof notify === 'function') {
                    notify('error', 'Please Attention', data.error_text || 'Please Check');
                }
                return;
            }
            load_form_div('<?= base_url('setting/admin/reffer') ?>', 'maindiv', 'Referral Clients');
        }, 'json');
    }
</script>

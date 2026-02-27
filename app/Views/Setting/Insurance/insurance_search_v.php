<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Insurance Company</h3>
        <div class="card-tools ms-auto">
            <button onclick="load_form_div('<?= base_url('setting/admin/insurance/new') ?>','maindiv','Insurance - New TPA');" type="button" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i>
                Add New
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped datatable">
                <thead>
                    <tr>
                        <th>Company Name</th>
                        <th>Contact Number</th>
                        <th>OPD Fee</th>
                        <th>Active</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($data)) : ?>
                        <?php foreach ($data as $row) : ?>
                            <tr>
                                <td><?= esc($row->ins_company_name ?? '') ?></td>
                                <td><?= esc($row->ins_contact_number ?? '') ?></td>
                                <td><?= esc($row->opd_fee ?? '') ?></td>
                                <td><?= esc($row->activestatus ?? '') ?></td>
                                <td>
                                    <button onclick="load_form_div('<?= base_url('setting/admin/insurance/' . $row->id) ?>','maindiv','Insurance: <?= esc($row->ins_company_name ?? '') ?>');" type="button" class="btn btn-primary btn-sm">Edit</button>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No insurance companies found.</td>
                        </tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

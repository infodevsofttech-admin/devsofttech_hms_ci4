<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h3 class="card-title mb-0">Doctors</h3>
        <div class="card-tools ms-auto d-flex gap-2 flex-wrap">
            <button onclick="load_form_div('<?= base_url('setting/admin/doctor/specs') ?>','maindiv','Specialities');" type="button" class="btn btn-outline-primary">
                <i class="bi bi-journal-medical"></i>
                Manage Specialities
            </button>
            <button onclick="load_form_div('<?= base_url('setting/admin/doctor/ipd-fee-types') ?>','maindiv','IPD Fee Types');" type="button" class="btn btn-outline-primary">
                <i class="bi bi-cash-coin"></i>
                Manage IPD Fee Types
            </button>
            <button onclick="load_form_div('<?= base_url('setting/admin/doctor/new') ?>','maindiv','Add Doctor');" type="button" class="btn btn-primary">
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
                        <th>Phone Number</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($data)) : ?>
                        <?php foreach ($data as $row) : ?>
                            <tr>
                                <td><?= esc(($row->p_title ?? '') . ' ' . ($row->p_fname ?? '')) ?></td>
                                <td><?= esc($row->mphone1 ?? '') ?></td>
                                <td><?= esc($row->email1 ?? '') ?></td>
                                <td>
                                    <button onclick="load_form_div('<?= base_url('setting/admin/doctor/' . $row->id) ?>','maindiv','<?= esc(($row->p_title ?? '') . ' ' . ($row->p_fname ?? '')) ?>');" type="button" class="btn btn-success btn-sm">Edit</button>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No doctors found.</td>
                        </tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

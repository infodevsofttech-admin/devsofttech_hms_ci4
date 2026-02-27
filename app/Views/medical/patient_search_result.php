<div class="card mt-3">
    <div class="card-body pt-3">
        <h6 class="card-title">Result</h6>
        <div class="table-responsive">
            <table id="medical-patient-search-table" class="table table-sm table-striped align-middle">
                <thead>
                    <tr>
                        <th>Sr.No.</th>
                        <th>Patient/UHID Code</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Last Visit</th>
                        <th>Phone No.</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($patients)): ?>
                        <?php foreach ($patients as $i => $row): ?>
                            <tr>
                                <td><?= (int)$i + 1 ?></td>
                                <td><?= esc($codeField ? ($row->{$codeField} ?? '-') : '-') ?></td>
                                <td><?= esc($nameField ? ($row->{$nameField} ?? '-') : '-') ?></td>
                                <td><?= esc($ageField ? ($row->{$ageField} ?? '-') : '-') ?></td>
                                <td><?= esc($lastVisitField ? ($row->{$lastVisitField} ?? '-') : '-') ?></td>
                                <td><?= esc($phoneField ? ($row->{$phoneField} ?? '-') : '-') ?></td>
                                <td>
                                    <a class="btn btn-sm btn-primary" href="javascript:load_form_div('<?= base_url('Medical/check_invoice') ?>/<?= (int)($row->id ?? 0) ?>/0/0','medical-main','Medical Invoice');">Sale</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No patient records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (empty($cases)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> No cases added to this packing yet. Use the search above to add cases.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Sr.</th>
                    <th>Case Code</th>
                    <th>UHID</th>
                    <th>Patient Name</th>
                    <th>Age</th>
                    <th>Type</th>
                    <th>Insurance Co.</th>
                    <th>Insurance No</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sr = 1;
                foreach ($cases as $case): 
                ?>
                <tr>
                    <td><?= $sr++ ?></td>
                    <td><strong><?= esc($case->case_id_code) ?></strong></td>
                    <td><?= esc($case->p_code) ?></td>
                    <td><?= esc($case->p_fname) ?></td>
                    <td><?= esc($case->age) ?></td>
                    <td>
                        <?php if ($case->ipd_opd == 'OPD'): ?>
                            <span class="badge bg-info">OPD</span>
                        <?php else: ?>
                            <span class="badge bg-primary">IPD</span>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($case->insurance_company_name) ?></td>
                    <td>
                        <?= esc($case->insurance_no) ?>
                        <?php if (!empty($case->insurance_no_1)): ?>
                            <br><small class="text-muted"><?= esc($case->insurance_no_1) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="button" 
                                class="btn btn-sm btn-danger" 
                                onclick="removeCase(<?= $case->id ?>)"
                                title="Remove from packing">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-light">
                    <td colspan="9" class="text-end">
                        <strong>Total Cases: <?= count($cases) ?></strong>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php endif; ?>

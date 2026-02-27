<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>Bed Code</th>
                <th>Bed Number</th>
                <th>Ward</th>
                <th>Assigned Date</th>
                <th>Released Date</th>
                <th>Type</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php if (! empty($bed_assignments)) : ?>
                <?php foreach ($bed_assignments as $row) : ?>
                    <tr>
                        <td><?= esc($row->bed_code ?? '') ?></td>
                        <td><?= esc($row->bed_number ?? '') ?></td>
                        <td><?= esc($row->ward_name ?? '') ?></td>
                        <td><?= esc($row->assigned_date ?? '') ?></td>
                        <td><?= esc($row->released_date ?? '') ?></td>
                        <td><?= esc($row->assignment_type ?? '') ?></td>
                        <td><?= esc($row->remarks ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7" class="text-center text-muted">No bed assignments found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

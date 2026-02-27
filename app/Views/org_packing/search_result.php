<?php if (empty($packings)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> No packing records found
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Sr.</th>
                    <th>Label No</th>
                    <th>Date Created</th>
                    <th>Type</th>
                    <th>No. of Cases</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sr = 1;
                foreach ($packings as $packing): 
                ?>
                <tr>
                    <td><?= $sr++ ?></td>
                    <td><strong><?= esc($packing->label_no) ?></strong></td>
                    <td><?= esc($packing->date_created) ?></td>
                    <td>
                        <?php if ($packing->list_type == 'OPD'): ?>
                            <span class="badge bg-info"><?= $packing->list_type ?></span>
                        <?php else: ?>
                            <span class="badge bg-primary"><?= $packing->list_type ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-secondary"><?= $packing->no_records ?></span></td>
                    <td>
                        <?php if ($packing->files_status == 1): ?>
                            <span class="badge bg-success">Completed</span>
                        <?php else: ?>
                            <span class="badge bg-warning">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="<?= site_url('org-packing/edit/' . $packing->id) ?>" 
                               class="btn btn-sm btn-primary" 
                               onclick="load_form_div(event, this.href)"
                               title="Edit Packing">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="<?= site_url('org-packing/print/' . $packing->id) ?>" 
                               class="btn btn-sm btn-success" 
                               target="_blank"
                               title="Print List">
                                <i class="bi bi-printer"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="text-muted mt-2">
        <small>Showing <?= count($packings) ?> packing record(s)</small>
    </div>
<?php endif; ?>

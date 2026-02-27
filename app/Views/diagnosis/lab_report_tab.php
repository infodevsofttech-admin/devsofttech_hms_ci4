<div class="card">
    <div class="card-body">
        <h5 class="card-title">Diagnosis Report List</h5>
        
        <?php if (empty($labreport_preprocess)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <strong>No records found</strong> - Try searching with different criteria.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table id="datashow1" class="table table-striped table-hover" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Invoice No.</th>
                        <th>Day Sr.No.</th>
                        <th>Lab Test No.</th>
                        <th>Person Name</th>
                        <th>Date</th>
                        <th>Tests Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($labreport_preprocess as $row): ?>
                    <tr>
                        <td><?= esc($row->invoice_code ?? '-') ?></td>
                        <td><?= esc($row->daily_sr_no ?? '-') ?></td>
                        <td><?= esc($row->lab_test_no ?? '-') ?></td>
                        <td><?= esc($row->inv_name ?? '-') ?> | <?= esc($row->age ?? '-') ?></td>
                        <td><?= esc($row->inv_date ?? '-') ?></td>
                        <td>
                            <?php
                            if (!empty($row->data_array)) {
                                $dataArray = explode('#', $row->data_array);
                                $hasBadge = false;
                                foreach ($dataArray as $value) {
                                    if (!empty($value)) {
                                        $valueArray = explode(';', $value);
                                        if (count($valueArray) >= 4) {
                                            $testName = $valueArray[0] ?? '';
                                            $isRequested = isset($valueArray[3]) ? $valueArray[3] : 0;
                                            
                                            if ($isRequested < 1) {
                                                echo '<span class="badge bg-danger me-1">' . esc($testName) . '</span> ';
                                            } else {
                                                echo '<span class="badge bg-success me-1">' . esc($testName) . '</span> ';
                                            }
                                            $hasBadge = true;
                                        }
                                    }
                                }
                                if (!$hasBadge) {
                                    echo '-';
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" onclick="show_record(<?= esc($row->inv_id) ?>)">
                                <i class="bi bi-eye"></i> Select
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Invoice No.</th>
                        <th>Day Sr.No.</th>
                        <th>Lab Test No.</th>
                        <th>Person Name</th>
                        <th>Date</th>
                        <th>Tests Name</th>
                        <th>Action</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for Radiology Report -->
<div class="modal fade" id="radiologyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="radiologyLabel">Radiology Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="radiology-body">
                    <!-- Radiology details will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function show_record(inv_id) {
    var labType = document.getElementById('lab_type') ? document.getElementById('lab_type').value : 1;
    var url = '<?= base_url('diagnosis/select-lab-invoice') ?>/' + inv_id + '/' + labType;
    load_form(url, 'Radiology Invoice Details');
}
</script>

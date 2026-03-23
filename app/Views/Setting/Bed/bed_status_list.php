<div class="card">
    <div class="card-header d-flex align-items-center flex-wrap gap-2">
        <h3 class="card-title mb-0">Bed Status (IPD Location Finder)</h3>
    </div>
    <div class="card-body">
        <div class="row g-2 mb-2">
            <div class="col-md-3">
                <select class="form-select" id="filterDepartment">
                    <option value="">All Department</option>
                    <?php foreach (($departments ?? []) as $department): ?>
                        <?php $label = trim((string) ($department->vName ?? '')); ?>
                        <?php if ($label === '') {
                            continue;
                        } ?>
                        <option value="<?= esc($label) ?>"><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filterWard">
                    <option value="">All Wards</option>
                    <?php foreach (($wards ?? []) as $ward): ?>
                        <option value="<?= esc((string) ($ward->ward_name ?? '')) ?>"><?= esc((string) ($ward->ward_name ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="bedStatusTable">
                <thead class="table-light">
                    <tr>
                        <th>IPD Code</th>
                        <th>Patient Code</th>
                        <th>Patient Name</th>
                        <th>Mobile</th>
                        <th>Department</th>
                        <th>Ward</th>
                        <th>Bed No</th>
                        <th>Admit Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($rows ?? []) as $row): ?>
                        <?php
                            $fullName = trim((string) (($row->p_fname ?? '') . ' ' . ($row->p_lname ?? '')));
                            if ($fullName === '') {
                                $fullName = '-';
                            }
                            $mobile = trim((string) ($row->mphone1 ?? ''));
                            if ($mobile === '' || $mobile === '0') {
                                $mobile = trim((string) ($row->mphone2 ?? ''));
                            }
                            if ($mobile === '' || $mobile === '0') {
                                $mobile = '-';
                            }
                            $bedNo = trim((string) ($row->bed_number ?? ''));
                            if ($bedNo === '' || $bedNo === '0') {
                                $bedNo = trim((string) ($row->bed_code ?? ''));
                            }
                            if ($bedNo === '') {
                                $bedNo = '-';
                            }
                            $departmentName = trim((string) ($row->department_name ?? ''));
                            if ($departmentName === '') {
                                $departmentName = 'All Department';
                            }
                            $wardName = trim((string) ($row->ward_name ?? ''));
                            if ($wardName === '') {
                                $wardName = '-';
                            }
                            $admitDate = trim((string) ($row->register_date ?? ''));
                            if ($admitDate === '') {
                                $admitDate = '-';
                            }
                        ?>
                        <tr>
                            <td><?= esc((string) ($row->ipd_code ?? '-')) ?></td>
                            <td><?= esc((string) ($row->p_code ?? '-')) ?></td>
                            <td><?= esc($fullName) ?></td>
                            <td><?= esc($mobile) ?></td>
                            <td><?= esc($departmentName) ?></td>
                            <td><?= esc($wardName) ?></td>
                            <td><?= esc($bedNo) ?></td>
                            <td><?= esc($admitDate) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    (function() {
        if (!window.jQuery || !$.fn || !$.fn.DataTable) {
            return;
        }

        var table = $('#bedStatusTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25
        });

        function bindFilter(selector, columnIndex) {
            var $select = $(selector);
            $select.on('change', function() {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                table.column(columnIndex).search(val ? '^' + val + '$' : '', true, false).draw();
            });
        }

        bindFilter('#filterDepartment', 4);
        bindFilter('#filterWard', 5);
    })();
</script>

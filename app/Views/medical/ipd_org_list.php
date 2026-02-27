<div class="card">
    <div class="card-body pt-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h5 class="card-title mb-0">IPD List (Current Admit)</h5>
            <?php if (!empty($highBalanceCount)): ?>
                <span class="badge bg-danger">High Balance: <?= (int) $highBalanceCount ?></span>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table id="medical-ipd-grid" class="table table-sm table-striped align-middle" width="100%">
                <thead>
                    <tr>
                        <th>IPD Code</th>
                        <th>Name / Patient Code</th>
                        <th>Bed No.[Type]</th>
                        <th>Register Date</th>
                        <th>No. of Days</th>
                        <th>Dr. Name</th>
                        <th>Admit Type</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $ipdId = (int) ($row->id ?? 0);
                                $ipdCode = (string) ($row->ipd_code ?? ('IPD-' . $ipdId));
                                $patientCode = (string) ($row->p_code ?? '-');
                                $patientName = (string) ($row->p_fname ?? '-');
                                $patientRelative = (string) ($row->p_rname ?? '');
                                $bedDesc = (string) ($row->Bed_Desc ?? ($row->bed_desc ?? '-'));
                                $regDate = (string) ($row->str_register_date ?? '');
                                if ($regDate === '' && !empty($row->register_date)) {
                                    $ts = strtotime((string) $row->register_date);
                                    $regDate = $ts ? date('d-m-Y', $ts) : (string) $row->register_date;
                                }
                                $noDays = (string) ($row->no_days ?? '-');
                                $docName = (string) ($row->doc_name ?? '-');
                                $admitType = (string) ($row->admit_type ?? '-');
                                $orgStatus = (string) ($row->Org_Status ?? ($row->org_status ?? ''));
                                $medAmount = (float) ($row->med_amount ?? 0);
                            ?>
                            <tr>
                                <td><?= esc($ipdCode) ?></td>
                                <td>[<?= esc($patientCode) ?>] <?= esc($patientName) ?><?= $patientRelative !== '' ? '<br>' . esc($patientRelative) : '' ?></td>
                                <td><?= esc($bedDesc) ?></td>
                                <td><?= esc($regDate !== '' ? $regDate : '-') ?></td>
                                <td><?= esc($noDays) ?></td>
                                <td><?= esc($docName) ?></td>
                                <td><?= esc($admitType) ?><?= $orgStatus !== '' ? '<br>' . esc($orgStatus) : '' ?></td>
                                <td>M:<?= esc(number_format($medAmount, 2)) ?></td>
                                <td>
                                    <a class="btn btn-sm btn-primary" href="javascript:load_form_div('<?= base_url('Medical/list_med_inv/' . $ipdId) ?>','medical-main','Invoice List :Pharmacy');">Open Invoices</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No currently admitted IPD records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    (function () {
        if (!window.jQuery || !jQuery.fn || typeof jQuery.fn.DataTable !== 'function') {
            return;
        }

        var tableId = '#medical-ipd-grid';
        if (jQuery.fn.dataTable.isDataTable(tableId)) {
            jQuery(tableId).DataTable().destroy();
        }

        jQuery(tableId).DataTable({
            order: [[3, 'desc']],
            pageLength: 25
        });
    })();
</script>

<div class="card">
    <div class="card-body pt-3">
        <h5 class="card-title">ORG List</h5>

        <div class="table-responsive">
            <table id="medical-org-grid" class="table table-sm table-striped align-middle" width="100%">
                <thead>
                    <tr>
                        <th>ORG Code</th>
                        <th>Name / Patient Code</th>
                        <th>Register Date</th>
                        <th>Insurance Company</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $orgId = (int) ($row->org_id ?? 0);
                                $orgCode = (string) ($row->case_id_code ?? ('ORG-' . $orgId));
                            ?>
                            <tr>
                                <td><?= esc($orgCode) ?></td>
                                <td>[<?= esc($row->p_code ?? '-') ?>] <?= esc($row->p_fname ?? '-') ?><?= !empty($row->p_rname) ? '<br>' . esc($row->p_rname) : '' ?></td>
                                <td><?= esc($row->str_register_date ?? '-') ?></td>
                                <td><?= esc($row->insurance_company_name ?? '-') ?></td>
                                <td>
                                    <a class="btn btn-sm btn-primary" href="javascript:load_form_div('<?= base_url('Medical/list_med_orginv/' . (int)($row->org_id ?? 0)) ?>','medical-main','Org Credit Invoice :Pharmacy');">Open Invoices</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No active organization credit records found.</td>
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

        var tableId = '#medical-org-grid';
        if (jQuery.fn.dataTable.isDataTable(tableId)) {
            jQuery(tableId).DataTable().destroy();
        }

        jQuery(tableId).DataTable({
            order: [[2, 'desc']],
            pageLength: 25
        });
    })();
</script>

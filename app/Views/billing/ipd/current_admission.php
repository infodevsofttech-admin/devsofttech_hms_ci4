

<div class="col-md-12">
    
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h3 class="card-title mb-0">Current Admission</h3>
        </div>
        <div class="card-body">

            <div class="alert alert-warning d-none" id="datatable-missing">
                DataTable plugin is not loaded. Please include jQuery DataTables to enable filtering.
            </div>
            <div class="table-responsive">
                <table id="ipd-current-table" class="table table-striped table-hover align-middle TableData">
                    <thead>
                        <tr>
                            <th>IPD Code</th>
                            <th>Name/Patient Code</th>
                            <th>Bed No. [Type]</th>
                            <th>Register Date</th>
                            <th>No. of Days</th>
                            <th>Dr. Name</th>
                            <th>Admit Type</th>
                            <th>Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($records ?? []) as $row) : ?>
                            <tr style="color:<?= esc($row->color ?? '') ?>;">
                                <td><?= esc($row->ipd_code ?? '') ?></td>
                                <td>[<?= esc($row->p_code ?? '') ?>] <?= esc($row->p_fname ?? '') ?><br /><?= esc($row->p_rname ?? '') ?></td>
                                <td><?= esc($row->Bed_Desc ?? '') ?></td>
                                <td><?= esc($row->str_register_date ?? '') ?></td>
                                <td><?= esc($row->no_days ?? '') ?></td>
                                <td><?= esc($row->doc_name ?? '') ?></td>
                                <td><?= esc($row->admit_type ?? '') ?><br><?= esc($row->Org_Status ?? '') ?><br><?= esc($row->insurance_no_1 ?? '') ?></td>
                                <td>C:<?= esc($row->charge_amount ?? '') ?>/M:<?= esc($row->med_amount ?? '') ?>/P:<?= esc($row->paid_amount ?? '') ?>/B:<?= esc($row->balance ?? '') ?></td>
                                <td>
                                    <?php if (! empty($row->id)) : ?>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="load_form_div('<?= base_url('billing/ipd/panel') ?>/<?= (int) ($row->id ?? 0) ?>','maindiv','IPD Panel');">View Invoice</button>
                                    <?php else : ?>
                                        <button type="button" class="btn btn-secondary btn-sm" disabled>View Invoice</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>IPD Code</th>
                            <th>Name/Patient Code</th>
                            <th>Bed No. [Type]</th>
                            <th>Register Date</th>
                            <th>No. of Days</th>
                            <th>Dr. Name</th>
                            <th>Admit Type</th>
                            <th>Amount</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        if (!$.fn || !$.fn.DataTable) {
            $('#datatable-missing').removeClass('d-none');
            return;
        }

        $('#ipd-current-table').DataTable();
    });
</script>

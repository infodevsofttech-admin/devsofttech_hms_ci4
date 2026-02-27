<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>IPD Code</th>
                <th>P Code</th>
                <th>Person Name</th>
                <th>Phone No.</th>
                <th>Admit Date</th>
                <th>Discharge Date</th>
                <th>Doc. Name</th>
                <th class="text-end">Net Amount</th>
                <th class="text-end">Paid Amt.</th>
                <th class="text-end">Balance Amt.</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>
            <?php $srNo = 0; ?>
            <?php foreach (($rows ?? []) as $row) : ?>
                <?php $srNo++; ?>
                <tr>
                    <td><?= $srNo ?></td>
                    <td><?= esc($row->ipd_code ?? '') ?></td>
                    <td><?= esc($row->p_code ?? '') ?></td>
                    <td><?= esc($row->p_fname ?? '') ?></td>
                    <td><?= esc($row->Contact_info ?? '') ?></td>
                    <td><?= esc($row->str_register_date ?? '') ?></td>
                    <td><?= esc($row->str_discharge_date ?? '') ?></td>
                    <td><?= esc($row->doc_name ?? '') ?></td>
                    <td class="text-end"><?= esc($row->net_amount ?? '') ?></td>
                    <td class="text-end"><?= esc($row->sum_of_paid ?? '') ?></td>
                    <td class="text-end"><?= esc($row->balance_amount ?? '') ?></td>
                    <td><?= esc($row->admit_type ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td>#</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>Total</td>
                <td><?= esc(number_format((float) ($totals['total_paid'] ?? 0), 2)) ?></td>
                <td><?= esc(number_format((float) ($totals['total_balance'] ?? 0), 2)) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

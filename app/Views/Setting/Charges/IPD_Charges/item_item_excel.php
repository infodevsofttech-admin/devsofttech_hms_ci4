<?php if (! empty($insuranceId)) : ?>
    <h4>Rate List of <?= esc($data[0]->group_desc ?? 'Charges') ?> for <?= esc($insuranceName ?? '') ?></h4>
<?php else : ?>
    <h4>Rate List of <?= esc($data[0]->group_desc ?? 'Charges') ?></h4>
<?php endif ?>
<table border="1" cellpadding="4" cellspacing="0">
    <thead>
        <tr>
            <th>Charge Name</th>
            <th>Charge Details</th>
            <?php if (! empty($insuranceId)) : ?>
                <th>Amount</th>
                <th>Code</th>
            <?php else : ?>
                <th>Amount</th>
            <?php endif ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row) : ?>
            <tr>
                <td><?= esc($row->idesc ?? '') ?></td>
                <td><?= esc($row->idesc_detail ?? '') ?></td>
                <?php if (! empty($insuranceId)) : ?>
                    <td><?= esc($row->display_amount ?? $row->i_amount ?? $row->amount ?? '') ?></td>
                    <td><?= esc($row->code ?? ($row->i_amount === null ? 'Base Rate' : '')) ?></td>
                <?php else : ?>
                    <td><?= esc($row->amount ?? '') ?></td>
                <?php endif ?>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>

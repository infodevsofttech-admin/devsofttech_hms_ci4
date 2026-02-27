<?php if (! empty($insuranceId)) : ?>
    <h4>Package Rate List for <?= esc($insuranceName ?? '') ?></h4>
<?php else : ?>
    <h4>Package Rate List</h4>
<?php endif ?>
<table border="1" cellpadding="4" cellspacing="0">
    <thead>
        <tr>
            <th>Package Name</th>
            <th>Description</th>
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
                <td><?= esc($row->ipd_pakage_name ?? '') ?></td>
                <td><?= esc($row->Pakage_description ?? '') ?></td>
                <?php if (! empty($insuranceId)) : ?>
                    <td><?= esc($row->display_amount ?? $row->i_amount ?? $row->Pakage_Min_Amount ?? '') ?></td>
                    <td><?= esc($row->code ?? ($row->i_amount === null ? 'Base Rate' : '')) ?></td>
                <?php else : ?>
                    <td><?= esc($row->Pakage_Min_Amount ?? '') ?></td>
                <?php endif ?>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>

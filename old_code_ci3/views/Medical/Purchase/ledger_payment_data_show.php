<p>Opening Balance : <?= $balance_till_date ?> / Total Cr. : <?= $cr_total ?> / Total Dr. : <?= $dr_total ?>
    / Closing Balance : <?= $balance_till_date_close ?>
    <a href="/Medical_backpanel/payment_supplier_data/1/<?=$date_range?>" target="_blank" class="btn btn-success btn-sm">Print Statement</a></p>
<table class="table ">
    <thead>
        <tr>
            <th>Sr.No</th>
            <th>Date Tran</th>
            <th>Description</th>
            <th align="right">Credit</th>
            <th align="right">Debit</th>
            <th align="right">Balance</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $i = 0;
        $balance = $balance_till_date;
        ?>
        <tr>
            <td><?php echo $i; ?></td>
            <td></td>
            <td>Opening Balance</td>
            <td align="right"></td>
            <td align="right"></td>
            <td align="right"><?php echo $balance ?> </td>
        </tr>
        <?php

        foreach ($med_supplier_ledger as $c) {
            $i += 1;
            $amt = $c->credit_debit == 0 ? $c->amount : $c->amount * -1;
            $balance += $amt;
        ?>
            <tr>
                <td><?php echo $i; ?></td>
                <td><?php echo MysqlDate_to_str($c->tran_date); ?></td>
                <td><?php echo $c->mode_desc . ' ' . $c->tran_desc; ?></td>
                <td align="right"><?php echo ($c->credit_debit == 0 ? $c->amount : ''); ?></td>
                <td align="right"><?php echo ($c->credit_debit == 1 ? $c->amount : ''); ?></td>
                <td align="right"><?php echo $balance ?> </td>
            </tr>
        <?php

        } ?>
    </tbody>
    <thead>
        <tr>
            <td>#</td>
            <td></td>
            <td></td>
            <td align="right"><b><?= $cr_total ?></b></td>
            <td align="right"><b><?= $dr_total ?></b></td>
            <td align="right"></td>
        </tr>
    </thead>
</table>
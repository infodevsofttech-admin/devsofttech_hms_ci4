<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>IPD Charges</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/font-awesome.min.css') ?>">
</head>
<body onload="window.print();">
<div class="wrapper">
    <section class="invoice" style="font-size:11px;">
        <style>
            table.rate-table { border-collapse: collapse; width: 100%; }
            table.rate-table th, table.rate-table td { border: 1px solid #000; padding: 4px 6px; }
        </style>
        <?php if (! empty($insuranceId)) : ?>
            <h4 style="margin-bottom: 8px;">Rate List of <?= esc($data[0]->group_desc ?? 'Charges') ?> for <?= esc($insuranceName ?? '') ?></h4>
        <?php else : ?>
            <h4 style="margin-bottom: 8px;">Rate List of <?= esc($data[0]->group_desc ?? 'Charges') ?></h4>
        <?php endif ?>
        <table class="table table-bordered table-striped rate-table">
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
                            <td><?= esc($row->display_amount ?? $row->i_amount ?? '') ?></td>
                            <td><?= esc($row->code ?? ($row->i_amount === null ? 'Base Rate' : '')) ?></td>
                        <?php else : ?>
                            <td><?= esc($row->amount ?? '') ?></td>
                        <?php endif ?>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>

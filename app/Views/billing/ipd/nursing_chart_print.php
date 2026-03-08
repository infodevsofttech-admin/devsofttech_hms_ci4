<?php
$ipd = $ipd_info ?? null;
$person = $person_info ?? null;
$entries = $nursing_entries ?? [];
$chartDate = (string) ($chart_date ?? date('Y-m-d'));
$chartNurse = (string) ($chart_nurse ?? '');
$toFahrenheit = static function ($temperatureC): string {
    if ($temperatureC === null || $temperatureC === '') {
        return '';
    }

    $value = (((float) $temperatureC) * 9 / 5) + 32;

    return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
};

$grouped = [
    'Morning' => [],
    'Evening' => [],
    'Night' => [],
    'Other' => [],
];

foreach ($entries as $entry) {
    $shift = trim((string) ($entry['shift_name'] ?? ''));
    if ($shift === '' || ! isset($grouped[$shift])) {
        $shift = 'Other';
    }
    $grouped[$shift][] = $entry;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Nursing Chart - <?= esc((string) ($ipd->ipd_code ?? '')) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 14px; }
        h2, h3 { margin: 6px 0; }
        .meta { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #333; padding: 6px; vertical-align: top; }
        th { background: #f3f3f3; text-align: left; }
        .muted { color: #666; }
        @media print {
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Print</button>
    <h2>24-Hour Nursing Chart</h2>
    <div class="meta">
        <strong>IPD:</strong> <?= esc((string) ($ipd->ipd_code ?? '')) ?> |
        <strong>Patient:</strong> <?= esc((string) ($person->p_fname ?? '')) ?> |
        <strong>UHID:</strong> <?= esc((string) ($person->p_code ?? '')) ?> |
        <strong>Date:</strong> <?= esc($chartDate) ?>
        <?php if ($chartNurse !== '') : ?>
            | <strong>Nurse:</strong> <?= esc($chartNurse) ?>
        <?php endif; ?>
    </div>

    <?php foreach ($grouped as $shift => $rows) : ?>
        <h3><?= esc($shift) ?> Shift</h3>
        <table>
            <thead>
            <tr>
                <th style="width: 140px;">Time</th>
                <th style="width: 90px;">Type</th>
                <th>Details</th>
                <th style="width: 130px;">Recorded By</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)) : ?>
                <tr>
                    <td colspan="4" class="muted">No entries.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($rows as $entry) : ?>
                    <tr>
                        <td><?= esc((string) ($entry['recorded_at'] ?? '')) ?></td>
                        <td><?= esc((string) ($entry['entry_type'] ?? '')) ?></td>
                        <td>
                            <?php if (($entry['entry_type'] ?? '') === 'vitals') : ?>
                                Temp: <?= esc($toFahrenheit($entry['temperature_c'] ?? null)) ?> °F,
                                Pulse: <?= esc((string) ($entry['pulse_rate'] ?? '')) ?>,
                                Resp: <?= esc((string) ($entry['resp_rate'] ?? '')) ?>,
                                BP: <?= esc((string) ($entry['bp_systolic'] ?? '')) ?>/<?= esc((string) ($entry['bp_diastolic'] ?? '')) ?>,
                                SpO2: <?= esc((string) ($entry['spo2'] ?? '')) ?>,
                                Wt: <?= esc((string) ($entry['weight_kg'] ?? '')) ?>
                            <?php elseif (($entry['entry_type'] ?? '') === 'fluid') : ?>
                                <?= esc((string) ($entry['fluid_direction'] ?? '')) ?>,
                                Route: <?= esc((string) ($entry['fluid_route'] ?? '')) ?>,
                                Amount: <?= esc((string) ($entry['fluid_amount_ml'] ?? '')) ?> ml
                            <?php else : ?>
                                <?= esc((string) ($entry['treatment_text'] ?? '')) ?>
                            <?php endif; ?>
                            <?php if (! empty($entry['general_note'])) : ?>
                                <br><span class="muted">Note: <?= esc((string) $entry['general_note']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= esc((string) ($entry['recorded_by'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Official ABHA Card - <?= esc($patient['p_fname'] ?? '') ?></title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0;
    padding: 20px;
    background: #eef3f9;
    font-family: "Segoe UI", Arial, sans-serif;
  }
  .card-page {
    width: 560px;
    max-width: 100%;
    margin: 0 auto;
  }
  .official-card {
    display: block;
    width: 100%;
    height: auto;
    background: #fff;
    border: 1px solid #cbd8e8;
  }
  .actions {
    margin-top: 18px;
    text-align: center;
  }
  .btn-print {
    border: 0;
    border-radius: 6px;
    padding: 10px 18px;
    background: #104998;
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
  }
  @media print {
    @page { size: auto; margin: 8mm; }
    body { padding: 0; background: #fff; }
    .card-page { width: 100%; }
    .official-card { border: 0; }
    .actions { display: none; }
  }
</style>
</head>
<body>
<?php
$storedCard = trim((string) ($stored_abha_card ?? ''));
$storedCardSrc = str_starts_with($storedCard, 'data:')
    ? $storedCard
    : 'data:image/png;base64,' . $storedCard;
?>
<main class="card-page">
  <img class="official-card" src="<?= esc($storedCardSrc) ?>" alt="Official NHA ABHA card">
  <div class="actions">
    <button type="button" class="btn-print" onclick="window.print()">Print / Save PDF</button>
  </div>
</main>
</body>
</html>

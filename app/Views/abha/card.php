<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ABHA Card — <?= esc($patient['p_fname'] ?? '') ?></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f4f8; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding: 32px 16px; }
  .abha-card {
    width: 380px;
    background: linear-gradient(135deg, #1a6fc4 0%, #0b3e8e 60%, #062a6e 100%);
    border-radius: 18px;
    padding: 24px 22px 20px;
    color: #fff;
    box-shadow: 0 8px 32px rgba(0,0,0,0.25);
    position: relative;
    overflow: hidden;
  }
  .abha-card::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 160px; height: 160px;
    background: rgba(255,255,255,0.07);
    border-radius: 50%;
  }
  .card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
  .card-header img.abdm-logo { height: 36px; filter: brightness(0) invert(1); }
  .card-header .brand { font-size: 14px; font-weight: 700; letter-spacing: .5px; line-height: 1.3; }
  .card-header .brand span { display: block; font-size: 11px; font-weight: 400; opacity: .8; }
  .card-body { display: flex; gap: 16px; align-items: flex-start; }
  .patient-photo {
    width: 72px; height: 72px; border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.7);
    object-fit: cover; background: rgba(255,255,255,0.2);
    flex-shrink: 0;
  }
  .patient-photo-placeholder {
    width: 72px; height: 72px; border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.7);
    background: rgba(255,255,255,0.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 30px; flex-shrink: 0;
  }
  .patient-info { flex: 1; min-width: 0; }
  .patient-name { font-size: 17px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; word-break: break-word; }
  .patient-detail { font-size: 12px; opacity: .85; margin-bottom: 2px; }
  .abha-number-block { margin-top: 18px; background: rgba(255,255,255,0.12); border-radius: 10px; padding: 10px 14px; }
  .abha-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; opacity: .75; margin-bottom: 3px; }
  .abha-number { font-size: 22px; font-weight: 700; letter-spacing: 3px; word-spacing: 2px; }
  .card-footer { margin-top: 14px; font-size: 10px; opacity: .6; text-align: right; }
  .actions { margin-top: 24px; display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
  .btn-print {
    background: #1a6fc4; color: #fff; border: none; border-radius: 8px;
    padding: 10px 22px; font-size: 14px; cursor: pointer; font-weight: 600;
    display: flex; align-items: center; gap: 6px;
  }
  .btn-print:hover { background: #0b3e8e; }
  .btn-abdm {
    background: #fff; color: #1a6fc4; border: 2px solid #1a6fc4; border-radius: 8px;
    padding: 9px 20px; font-size: 14px; cursor: pointer; font-weight: 600; text-decoration: none;
    display: flex; align-items: center; gap: 6px;
  }
  .btn-abdm:hover { background: #e8f0fe; }
  .note { margin-top: 14px; font-size: 12px; color: #666; max-width: 380px; text-align: center; line-height: 1.5; }

  @media print {
    body { background: #fff; padding: 0; }
    .abha-card { box-shadow: none; }
    .actions, .note { display: none; }
  }
</style>
</head>
<body>

<?php
$name       = esc($patient['p_fname'] ?? '');
$abhaNumDisp = esc($abha_num ?? '');
$photoData  = !empty($patient['abha_photo']) ? $patient['abha_photo'] : '';
$genderDisp = esc($gender ?? '');
$dobDisp    = esc($dob ?? '');
$mphone     = esc($patient['mphone1'] ?? '');
$pCode      = esc($patient['p_code'] ?? '');
$abhaNumRaw = preg_replace('/\D/', '', $abha_num ?? '');
?>

<div class="abha-card">
  <div class="card-header">
    <!-- ABDM / NHA branding text instead of logo image -->
    <div style="width:40px;height:40px;background:rgba(255,255,255,0.9);border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:900;color:#0b3e8e;font-size:13px;letter-spacing:-.5px;flex-shrink:0;">NHA</div>
    <div class="brand">Ayushman Bharat Digital Mission
      <span>ABHA — Health Account Card</span>
    </div>
  </div>

  <div class="card-body">
    <?php if ($photoData && str_starts_with($photoData, 'data:image')): ?>
      <img class="patient-photo" src="<?= $photoData ?>" alt="Patient Photo">
    <?php elseif ($photoData): ?>
      <img class="patient-photo" src="data:image/jpeg;base64,<?= esc($photoData) ?>" alt="Patient Photo">
    <?php else: ?>
      <div class="patient-photo-placeholder">👤</div>
    <?php endif; ?>

    <div class="patient-info">
      <div class="patient-name"><?= $name ?: '—' ?></div>
      <?php if ($genderDisp): ?>
        <div class="patient-detail">Gender: <?= $genderDisp ?></div>
      <?php endif; ?>
      <?php if ($dobDisp): ?>
        <div class="patient-detail">DOB: <?= $dobDisp ?></div>
      <?php endif; ?>
      <?php if ($pCode): ?>
        <div class="patient-detail" style="margin-top:4px;opacity:.7;">HMS ID: <?= $pCode ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="abha-number-block">
    <div class="abha-label">ABHA Number</div>
    <div class="abha-number"><?= $abhaNumDisp ?: '—' ?></div>
  </div>

  <div class="card-footer">Government of India &nbsp;|&nbsp; Ministry of Health &amp; Family Welfare</div>
</div>

<div class="actions">
  <button class="btn-print" onclick="window.print()">🖨 Print / Save PDF</button>
  <a class="btn-abdm" href="https://abha.abdm.gov.in/abha/v3/" target="_blank" rel="noopener noreferrer">
    ⬇ Download Official Card
  </a>
</div>

<p class="note">
  <strong>To download the official ABDM-issued ABHA card</strong> (with QR code),
  click "Download Official Card" and log in with mobile number linked to ABHA&nbsp;
  <strong><?= $abhaNumDisp ?></strong>.
</p>

</body>
</html>

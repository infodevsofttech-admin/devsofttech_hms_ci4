<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ABHA Card - <?= esc($patient['p_fname'] ?? '') ?></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: linear-gradient(145deg, #eef4ff 0%, #f6f9ff 45%, #edf7ff 100%);
    min-height: 100vh;
    padding: 24px 12px;
    color: #1f2e44;
    display: flex;
    flex-direction: column;
    align-items: center;
  }
  .abha-shell {
    width: 560px;
    max-width: 100%;
    background: #ffffff;
    border: 1px solid #d6e3f7;
    border-radius: 18px;
    box-shadow: 0 14px 34px rgba(16, 48, 86, 0.16);
    padding: 16px;
  }
  .header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #e5edf9;
    padding-bottom: 10px;
    margin-bottom: 12px;
    gap: 10px;
  }
  .header-left {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
  }
  .header-right {
    flex: 0 0 auto;
  }
  .header-text {
    font-size: 12px;
    line-height: 1.2;
    color: #123a73;
    font-weight: 700;
  }
  .header-text span {
    display: block;
    font-size: 11px;
    color: #5b6f8b;
    font-weight: 600;
  }
  .logo-wrap {
    width: 162px;
    height: 54px;
    border-radius: 10px;
    border: 1px solid #d5e4f7;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex: 0 0 auto;
  }
  .logo-wrap img {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }
  .abdm-logo {
    object-fit: cover;
    object-position: center;
    transform: scale(1.16);
  }
  .brand-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 8px;
    border: 1px solid #d5e4f7;
    border-radius: 10px;
    background: #f7fbff;
  }
  .brand-logo {
    width: 38px;
    height: 38px;
    object-fit: contain;
    border-radius: 7px;
    background: #fff;
    border: 1px solid #d7e5f8;
  }
  .brand-text {
    line-height: 1.1;
  }
  .brand-name {
    font-size: 16px;
    font-weight: 800;
    color: #0f3f7f;
  }
  .brand-sub {
    font-size: 10px;
    color: #4c6485;
    font-weight: 600;
  }
  .hospital-strip {
    border: 1px solid #d5e4f8;
    background: #f4f9ff;
    border-radius: 11px;
    padding: 8px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
  }
  .hospital-info {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
  }
  .hospital-mini-logo {
    width: 32px;
    height: 32px;
    object-fit: contain;
    border-radius: 6px;
    border: 1px solid #cfe0f8;
    background: #fff;
    flex: 0 0 auto;
  }
  .hospital-name {
    font-size: 13px;
    color: #1e4a82;
    min-width: 0;
  }
  .hospital-name strong { color: #0f3f7f; }
  .patient-id-strip {
    width: 180px;
    background: #fff;
    border: 1px solid #d4e3f8;
    border-radius: 8px;
    padding: 4px 6px;
    text-align: center;
    flex: 0 0 auto;
  }
  .patient-id-strip svg {
    width: 100%;
    height: 28px;
  }
  .patient-id-label {
    font-size: 9px;
    color: #5f7291;
    text-transform: uppercase;
    letter-spacing: 0.8px;
  }
  .patient-id-value {
    font-size: 11px;
    color: #123f7d;
    font-weight: 700;
    line-height: 1.1;
  }
  .patient-area {
    display: grid;
    grid-template-columns: 1fr 132px;
    gap: 12px;
    align-items: start;
  }
  .patient-main {
    display: flex;
    gap: 10px;
  }
  .photo {
    width: 82px;
    height: 82px;
    border-radius: 50%;
    border: 3px solid #d9e8fb;
    object-fit: cover;
    background: #eef5ff;
    flex: 0 0 auto;
  }
  .patient-details { min-width: 0; }
  .patient-name {
    font-size: 24px;
    font-weight: 800;
    letter-spacing: 0.4px;
    color: #123f7d;
    text-transform: uppercase;
    line-height: 1.05;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .meta {
    font-size: 13px;
    color: #3d5778;
    margin-bottom: 2px;
  }
  .badge-phone {
    margin-top: 5px;
    display: inline-block;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 8px;
  }
  .verified {
    background: #ddfce8;
    color: #145c2f;
    border: 1px solid #a9efc4;
  }
  .registered {
    background: #eef4ff;
    color: #1f4e95;
    border: 1px solid #c6daf8;
  }
  .qr-card {
    border: 1px solid #d8e6fb;
    background: #fbfdff;
    border-radius: 11px;
    padding: 8px;
    text-align: center;
  }
  .qr-title {
    font-size: 10px;
    color: #5f7291;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    margin-bottom: 5px;
  }
  .qr-image {
    width: 114px;
    height: 114px;
    border-radius: 7px;
    border: 1px solid #e0e8f5;
    background: #fff;
  }
  .qr-value {
    margin-top: 5px;
    font-size: 10px;
    color: #3b5474;
    word-break: break-word;
  }
  .abha-number-wrap {
    margin-top: 12px;
    border: 1px solid #d3e3f9;
    background: #edf5ff;
    border-radius: 11px;
    padding: 9px 11px;
  }
  .abha-label {
    font-size: 10px;
    text-transform: uppercase;
    color: #5d7292;
    letter-spacing: 1px;
    margin-bottom: 2px;
  }
  .abha-number {
    font-size: 24px;
    font-weight: 900;
    letter-spacing: 1.5px;
    color: #0f4186;
    line-height: 1.1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: clip;
  }
  .footer {
    margin-top: 10px;
    text-align: right;
    font-size: 10px;
    color: #5f7291;
  }
  .actions {
    margin-top: 18px;
    display: flex;
    justify-content: center;
  }
  .btn-print {
    background: #104998;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 18px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
  }
  .btn-print:hover { background: #0b3f82; }

  @media (max-width: 520px) {
    .logo-wrap {
      width: 132px;
      height: 46px;
    }
    .brand-name {
      font-size: 14px;
    }
    .brand-sub {
      font-size: 9px;
    }
    .header-text {
      font-size: 11px;
    }
    .header-text span {
      font-size: 10px;
    }
    .hospital-strip {
      flex-direction: column;
      align-items: stretch;
    }
    .patient-id-strip {
      width: 100%;
    }
    .patient-area {
      grid-template-columns: 1fr;
    }
    .qr-card {
      display: inline-block;
      width: 100%;
    }
    .patient-name {
      font-size: 20px;
    }
    .abha-number {
      font-size: 20px;
      letter-spacing: 1px;
    }
  }

  @media print {
    @page {
      size: A5 portrait;
      margin: 8mm;
    }
    body { background: #fff; padding: 0; }
    .abha-shell {
      width: 100%;
      max-width: 148mm;
      box-shadow: none;
      border-color: #d7e5f8;
    }
    .actions { display: none; }
  }
</style>
</head>
<body>

<?php
$name = esc($patient['p_fname'] ?? '');
$abhaNumDisp = esc($abha_num ?? '');
$genderDisp = esc($gender ?? '');
$dobDisp = esc($dob ?? '');
$hospitalName = esc($hospital_name ?? 'Hospital');
$hospitalLogo = esc($hospital_logo_url ?? base_url('assets/img/logo.png'));
$brandName = esc($brand_name ?? 'E-Atria');
$nhaOfficialLogo = esc(base_url('assets/img/abdm_official_logo.svg'));
$profilePhotoUrl = (string) ($profile_photo_url ?? '/assets/images/no_image.svg');
$profilePhotoUrlEsc = esc($profilePhotoUrl);
$hmsId = esc($hms_id ?? '');
$abhaQrUrl = esc($abha_qr_url ?? '');
$barcodeSvg = (string) ($hms_barcode_svg ?? '');
$patientMobile = esc($patient_mobile ?? '');
$mobileVerified = (bool) ($mobile_verified ?? false);
?>

<div class="abha-shell">
  <div class="header">
    <div class="header-left">
      <div class="logo-wrap">
        <img class="abdm-logo" src="<?= $nhaOfficialLogo ?>" alt="ABDM NHA Official Logo">
      </div>
      <div class="header-text">
        Ayushman Bharat Digital Mission
        <span>NHA | ABHA Health Account Card</span>
      </div>
    </div>
    <div class="header-right">
      <div class="brand-wrap">
        <img class="brand-logo" src="<?= $hospitalLogo ?>" alt="Brand Logo">
        <div class="brand-text">
          <div class="brand-name"><?= $brandName !== '' ? $brandName : 'E-Atria' ?></div>
          <div class="brand-sub">Digital Health Platform</div>
        </div>
      </div>
    </div>
  </div>

  <div class="hospital-strip">
    <div class="hospital-info">
      <img class="hospital-mini-logo" src="<?= $hospitalLogo ?>" alt="Hospital Logo">
      <div class="hospital-name">Hospital: <strong><?= $hospitalName ?></strong></div>
    </div>
    <?php if ($barcodeSvg !== '' && $hmsId !== ''): ?>
      <div class="patient-id-strip">
        <div class="patient-id-label">Patient ID Barcode</div>
        <div><?= $barcodeSvg ?></div>
        <div class="patient-id-value"><?= $hmsId ?></div>
      </div>
    <?php endif; ?>
  </div>

  <div class="patient-area">
    <div>
      <div class="patient-main">
        <img class="photo" src="<?= $profilePhotoUrlEsc ?>" alt="Patient Photo">
        <div class="patient-details">
          <div class="patient-name"><?= $name !== '' ? $name : 'PATIENT' ?></div>
          <?php if ($genderDisp !== ''): ?>
            <div class="meta">Gender: <?= $genderDisp ?></div>
          <?php endif; ?>
          <?php if ($dobDisp !== ''): ?>
            <div class="meta">DOB: <?= $dobDisp ?></div>
          <?php endif; ?>
          <?php if ($hmsId !== ''): ?>
            <div class="meta">HMS ID: <?= $hmsId ?></div>
          <?php endif; ?>
          <?php if ($patientMobile !== ''): ?>
            <div class="badge-phone <?= $mobileVerified ? 'verified' : 'registered' ?>">
              <?= $mobileVerified ? 'ABHA Verified Mobile' : 'ABHA Registered Mobile' ?>: <?= $patientMobile ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="abha-number-wrap">
        <div class="abha-label">ABHA Number</div>
        <div class="abha-number"><?= $abhaNumDisp !== '' ? $abhaNumDisp : 'NA' ?></div>
      </div>
    </div>

    <div class="qr-card">
      <div class="qr-title">ABHA ID QR</div>
      <?php if ($abhaQrUrl !== ''): ?>
        <img class="qr-image" src="<?= $abhaQrUrl ?>" alt="ABHA QR Code">
      <?php else: ?>
        <div class="meta">QR unavailable</div>
      <?php endif; ?>
      <div class="qr-value"><?= $abhaNumDisp ?></div>
    </div>
  </div>

  <div class="footer">Government of India | Ministry of Health &amp; Family Welfare</div>
</div>

<div class="actions">
  <button class="btn-print" onclick="window.print()">Print / Save PDF</button>
</div>

</body>
</html>